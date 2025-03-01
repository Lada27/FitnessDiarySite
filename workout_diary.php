<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$error = '';
$success = '';

// Получение тренера пользователя
$sql = "SELECT trainer_id FROM client_trainer WHERE client_id = ? AND end_date IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trainer_result = $stmt->get_result();
$trainer_id = $trainer_result->fetch_assoc()['trainer_id'];

// Получение программ тренировок от тренера
$sql = "SELECT id, name, type, difficulty_level FROM workout_programs WHERE created_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trainer_id);
$stmt->execute();
$programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Если отправлена форма добавления тренировки
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $program_id = $_POST['program_id'];
    $notes = trim($_POST['notes']);
    $workout_date = $_POST['workout_date'];
    
    // Проверяем и форматируем дату
    if (!empty($workout_date)) {
        try {
            $date = new DateTime($workout_date);
            $workout_date = $date->format('Y-m-d');
            
            $sql = "INSERT INTO workout_diary (user_id, workout_date, program_id, notes) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isis", $user_id, $workout_date, $program_id, $notes);
            
            if ($stmt->execute()) {
                $success = 'Тренировка успешно добавлена';
                // Перенаправляем на ту же страницу с выбранной датой
                header("Location: workout_diary.php?date=" . $workout_date);
                exit();
            } else {
                $error = 'Ошибка при добавлении тренировки';
            }
        } catch (Exception $e) {
            $error = 'Неверный формат даты';
        }
    } else {
        $error = 'Дата не может быть пустой';
    }
}

// Получение всех тренировок на выбранную дату
$sql = "SELECT wd.*, wp.name as program_name, wp.type, wp.difficulty_level 
        FROM workout_diary wd
        LEFT JOIN workout_programs wp ON wd.program_id = wp.id
        WHERE wd.user_id = ? AND wd.workout_date = ?
        ORDER BY wd.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$workouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получаем упражнения и комментарии для всех тренировок
$all_exercises = [];
$all_comments = [];

if (!empty($workouts)) {
    foreach ($workouts as $workout) {
        // Добавляем отладочную информацию
        echo "<script>console.log('Debug: Тренировка ID " . $workout['id'] . 
             ", Программа ID " . $workout['program_id'] . 
             ", Дата " . $workout['workout_date'] . "');</script>";
        
        // Получение упражнений для программы тренировок
        $sql = "SELECT e.*, wd.sets, wd.reps, wd.weight 
                FROM exercises e 
                JOIN workout_details wd ON e.id = wd.exercise_id 
                WHERE wd.workout_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $workout['program_id']); // Теперь подставляется ID программы
        $stmt->execute();
        $result = $stmt->get_result();
        $exercises = $result->fetch_all(MYSQLI_ASSOC);
        
        // Отладочная информация о количестве найденных упражнений
        echo "<script>console.log('Найдено упражнений: " . count($exercises) . "');</script>";
        
        // Дополнительная отладочная информация
        $debug_sql = str_replace('?', $workout['id'], $sql);
        $debug_sql = str_replace("\n", ' ', $debug_sql); // Убираем переносы строк
        $debug_sql = str_replace("\r", '', $debug_sql); // Убираем возвраты каретки
        echo "<script>console.log('SQL запрос:', " . json_encode($debug_sql) . ");</script>";
        
        // Проверка результата запроса
        if ($stmt->error) {
            echo "<script>console.log('SQL Error:', " . json_encode($stmt->error) . ");</script>";
        }
        
        // Выводим результат запроса для отладки
        echo "<script>console.log('Результат запроса:', " . json_encode($exercises) . ");</script>";
        
        // Сохраняем упражнения в массив
        $all_exercises[$workout['id']] = $exercises;
        
        // Получение комментариев тренера для каждой тренировки
        $sql = "SELECT tc.*, u.name as trainer_name
                FROM trainer_comments tc
                JOIN users u ON tc.trainer_id = u.id
                WHERE tc.workout_id = ?
                ORDER BY tc.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $workout['id']);
        $stmt->execute();
        $all_comments[$workout['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // После выполнения запроса на получение упражнений
        if ($stmt->error) {
            echo "<script>console.log('SQL Error: " . $stmt->error . "');</script>";
        }
        
        // Добавим вывод SQL-запроса с подставленными значениями
        $debug_sql = str_replace('?', $workout['id'], $sql);
        echo "<script>console.log('Полный SQL запрос: " . $debug_sql . "');</script>";
    }
}

// Получение всех дат с тренировками для текущего месяца
$month = date('m', strtotime($selected_date));
$year = date('Y', strtotime($selected_date));
$sql = "SELECT workout_date FROM workout_diary 
        WHERE user_id = ? AND MONTH(workout_date) = ? AND YEAR(workout_date) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$workout_dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$workout_dates = array_column($workout_dates, 'workout_date');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дневник тренировок - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script>
    function showWorkoutDetails(workoutId, exercises) {
        console.log('Тренировка ID:', workoutId);
        console.log('Упражнения:', exercises);
    }
    </script>
    <style>
        .calendar {
            width: 100%;
        }
        .calendar td {
            width: 14.28%;
            padding: 10px;
            text-align: center;
            cursor: pointer;
        }
        .calendar td.has-workout {
            background-color: #e3f2fd;
        }
        .calendar td.selected {
            background-color: #bbdefb;
        }
        .calendar td:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <!-- Навигационное меню -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">FitClub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="workout_diary.php">Дневник тренировок</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progress.php">Мой прогресс</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Личный кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Выход</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Календарь -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Календарь тренировок</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $firstDay = date('N', strtotime($year . '-' . $month . '-01')) - 1;
                        $daysInMonth = date('t', strtotime($year . '-' . $month . '-01'));
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <a href="?date=<?php echo date('Y-m-d', strtotime($selected_date . ' -1 month')); ?>" 
                               class="btn btn-sm btn-outline-primary">&lt;</a>
                            <h5 class="mb-0"><?php echo date('F Y', strtotime($selected_date)); ?></h5>
                            <a href="?date=<?php echo date('Y-m-d', strtotime($selected_date . ' +1 month')); ?>" 
                               class="btn btn-sm btn-outline-primary">&gt;</a>
                        </div>
                        <table class="calendar">
                            <tr>
                                <th>Пн</th>
                                <th>Вт</th>
                                <th>Ср</th>
                                <th>Чт</th>
                                <th>Пт</th>
                                <th>Сб</th>
                                <th>Вс</th>
                            </tr>
                            <?php
                            $day = 1;
                            $cells = 0;
                            while ($day <= $daysInMonth) {
                                if ($cells % 7 == 0) echo "<tr>";
                                
                                if ($cells < $firstDay) {
                                    echo "<td></td>";
                                } else {
                                    $currentDate = sprintf('%s-%02d-%02d', $year, $month, $day);
                                    $classes = [];
                                    if (in_array($currentDate, $workout_dates)) $classes[] = 'has-workout';
                                    if ($currentDate == $selected_date) $classes[] = 'selected';
                                    
                                    echo "<td class='" . implode(' ', $classes) . "'>";
                                    echo "<a href='?date=" . $currentDate . "' class='text-dark text-decoration-none'>" . $day . "</a>";
                                    echo "</td>";
                                    $day++;
                                }
                                $cells++;
                                
                                if ($cells % 7 == 0) echo "</tr>";
                            }
                            while ($cells % 7 != 0) {
                                echo "<td></td>";
                                $cells++;
                            }
                            ?>
                        </table>
                    </div>
                </div>

                <!-- Форма добавления тренировки -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Добавить тренировку</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="workout_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                            
                            <div class="mb-3">
                                <label for="program_id" class="form-label">Программа тренировок</label>
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="">Выберите программу</option>
                                    <?php foreach($programs as $program): ?>
                                        <option value="<?php echo $program['id']; ?>">
                                            <?php echo htmlspecialchars($program['name']); ?> 
                                            (<?php echo htmlspecialchars($program['type']); ?> - 
                                            <?php echo htmlspecialchars($program['difficulty_level']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Заметки</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Добавить тренировку</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Детали тренировок -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Тренировки на <?php echo date('d.m.Y', strtotime($selected_date)); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($workouts)): ?>
                            <?php foreach($workouts as $workout): ?>
                                <div class="workout-block mb-4">
                                    <?php
                                    // Отладочная информация для каждой тренировки
                                    echo "<script>showWorkoutDetails(" . 
                                         json_encode($workout['id']) . ", " . 
                                         json_encode($all_exercises[$workout['id']]) . 
                                         ");</script>";
                                    ?>
                                    <h5>Программа: <?php echo htmlspecialchars($workout['program_name']); ?></h5>
                                    <p class="text-muted">
                                        Тип: <?php echo htmlspecialchars($workout['type']); ?> | 
                                        Сложность: <?php echo htmlspecialchars($workout['difficulty_level']); ?>
                                    </p>
                                    
                                    <?php if($workout['notes']): ?>
                                        <div class="mb-4">
                                            <h6>Заметки:</h6>
                                            <p><?php echo nl2br(htmlspecialchars($workout['notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($all_exercises[$workout['id']])): ?>
                                        <h6>Упражнения:</h6>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Упражнение</th>
                                                        <th>Подходы</th>
                                                        <th>Повторения</th>
                                                        <th>Вес (кг)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($all_exercises[$workout['id']] as $exercise): ?>
                                                        <tr>
                                                            <td>
                                                                <?php echo htmlspecialchars($exercise['name']); ?>
                                                                <small class="text-muted d-block">
                                                                    <?php echo htmlspecialchars($exercise['muscle_group']); ?>
                                                                </small>
                                                            </td>
                                                            <td><?php echo $exercise['sets']; ?></td>
                                                            <td><?php echo $exercise['reps']; ?></td>
                                                            <td><?php echo $exercise['weight'] ?: '-'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($all_comments[$workout['id']])): ?>
                                        <h6>Комментарии тренера:</h6>
                                        <?php foreach($all_comments[$workout['id']] as $comment): ?>
                                            <div class="card mb-2">
                                                <div class="card-body py-2">
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($comment['trainer_name']); ?> | 
                                                        <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                                    </small>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <hr class="my-4">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Нет записей о тренировках на выбранную дату</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 