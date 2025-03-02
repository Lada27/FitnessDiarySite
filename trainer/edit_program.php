<?php
session_start();
require_once '../config/db.php';

// Проверка прав тренера
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Получение ID программы
$program_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Проверка существования программы и прав на её редактирование
$sql = "SELECT * FROM workout_programs WHERE id = ? AND created_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $program_id, $_SESSION['user_id']);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();

if (!$program) {
    header("Location: my_programs.php");
    exit();
}

// Получение текущих упражнений программы
$sql = "SELECT wd.*, e.name as exercise_name, e.muscle_group 
        FROM workout_details wd 
        JOIN exercises e ON wd.exercise_id = e.id 
        WHERE wd.workout_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $program_id);
$stmt->execute();
$current_exercises = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получение всех доступных упражнений
$sql = "SELECT * FROM exercises ORDER BY muscle_group, name";
$exercises = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Группировка упражнений по группам мышц
$grouped_exercises = [];
foreach ($exercises as $exercise) {
    $grouped_exercises[$exercise['muscle_group']][] = $exercise;
}

// Обработка формы обновления
if (isset($_POST['update_program'])) {
    $conn->begin_transaction();
    
    try {
        // Обновление основной информации
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $type = trim($_POST['type']);
        $difficulty_level = trim($_POST['difficulty_level']);
        
        $sql = "UPDATE workout_programs 
                SET name = ?, description = ?, type = ?, difficulty_level = ? 
                WHERE id = ? AND created_by = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $name, $description, $type, $difficulty_level, $program_id, $_SESSION['user_id']);
        $stmt->execute();
        
        // Удаление старых упражнений
        $sql = "DELETE FROM workout_details WHERE workout_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        
        // Добавление новых упражнений
        if (isset($_POST['exercises']) && is_array($_POST['exercises'])) {
            $sql = "INSERT INTO workout_details (workout_id, exercise_id, sets, reps, weight) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($_POST['exercises'] as $exercise_id => $details) {
                if (!empty($details['sets']) && !empty($details['reps'])) {
                    $sets = intval($details['sets']);
                    $reps = intval($details['reps']);
                    $weight = !empty($details['weight']) ? floatval($details['weight']) : null;
                    
                    $stmt->bind_param("iiiid", $program_id, $exercise_id, $sets, $reps, $weight);
                    $stmt->execute();
                }
            }
        }
        
        $conn->commit();
        $success = 'Программа успешно обновлена';
        
        // Обновляем данные программы для отображения
        $stmt = $conn->prepare("SELECT * FROM workout_programs WHERE id = ?");
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $program = $stmt->get_result()->fetch_assoc();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Ошибка при обновлении программы: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование программы - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Навигационное меню -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">FitClub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_clients.php">Мои клиенты</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_program.php">Создать программу</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my_programs.php">Мои программы</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../profile.php">Личный кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Выход</a>
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

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Редактирование программы тренировок</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Название программы</label>
                        <input type="text" class="form-control" name="name" 
                               value="<?php echo htmlspecialchars($program['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Описание программы</label>
                        <textarea class="form-control" name="description" rows="3" required><?php 
                            echo htmlspecialchars($program['description']); 
                        ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Тип программы</label>
                        <input type="text" class="form-control" name="type" 
                               value="<?php echo htmlspecialchars($program['type']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Уровень сложности</label>
                        <select class="form-select" name="difficulty_level" required>
                            <option value="easy" <?php echo $program['difficulty_level'] === 'easy' ? 'selected' : ''; ?>>
                                Начинающий
                            </option>
                            <option value="normal" <?php echo $program['difficulty_level'] === 'normal' ? 'selected' : ''; ?>>
                                Средний
                            </option>
                            <option value="hard" <?php echo $program['difficulty_level'] === 'hard' ? 'selected' : ''; ?>>
                                Продвинутый
                            </option>
                        </select>
                    </div>

                    <hr class="my-4">
                    
                    <h5>Упражнения программы</h5>
                    
                    <?php foreach($grouped_exercises as $muscle_group => $exercises): ?>
                        <div class="mb-4">
                            <h6><?php echo htmlspecialchars($muscle_group); ?></h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Упражнение</th>
                                            <th>Подходы</th>
                                            <th>Повторения</th>
                                            <th>Вес (кг)</th>
                                            <th>Добавить</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($exercises as $exercise): 
                                            $current_exercise = array_filter($current_exercises, function($e) use ($exercise) {
                                                return $e['exercise_id'] == $exercise['id'];
                                            });
                                            $current_exercise = reset($current_exercise); // Получаем первый элемент или false
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exercise['name']); ?></td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="exercises[<?php echo $exercise['id']; ?>][sets]" 
                                                           min="1" max="10" style="width: 70px"
                                                           value="<?php echo $current_exercise ? $current_exercise['sets'] : ''; ?>">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="exercises[<?php echo $exercise['id']; ?>][reps]" 
                                                           min="1" max="100" style="width: 70px"
                                                           value="<?php echo $current_exercise ? $current_exercise['reps'] : ''; ?>">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm" 
                                                           name="exercises[<?php echo $exercise['id']; ?>][weight]" 
                                                           min="0" step="0.5" style="width: 80px"
                                                           value="<?php echo $current_exercise ? $current_exercise['weight'] : ''; ?>">
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input exercise-checkbox" 
                                                               type="checkbox" 
                                                               data-exercise-id="<?php echo $exercise['id']; ?>"
                                                               <?php echo $current_exercise ? 'checked' : ''; ?>>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" name="update_program" class="btn btn-primary">Сохранить изменения</button>
                    <a href="my_programs.php" class="btn btn-secondary">Отмена</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Управление активацией/деактивацией полей при клике на чекбокс
        document.querySelectorAll('.exercise-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const exerciseId = this.dataset.exerciseId;
                const inputs = document.querySelectorAll(`input[name^="exercises[${exerciseId}]"]`);
                
                inputs.forEach(function(input) {
                    input.disabled = !checkbox.checked;
                    if (!checkbox.checked) {
                        input.value = '';
                    }
                });
            });
            
            // Инициализация начального состояния
            const exerciseId = checkbox.dataset.exerciseId;
            const inputs = document.querySelectorAll(`input[name^="exercises[${exerciseId}]"]`);
            inputs.forEach(function(input) {
                input.disabled = !checkbox.checked;
            });
        });
    });
    </script>
</body>
</html> 