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

// Обработка удаления программы
if (isset($_POST['delete_program'])) {
    $program_id = intval($_POST['program_id']);
    
    // Проверяем, принадлежит ли программа этому тренеру
    $check_sql = "SELECT id FROM workout_programs WHERE id = ? AND created_by = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $program_id, $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Проверяем использование программы
        $usage_sql = "SELECT COUNT(*) as count FROM workout_diary WHERE program_id = ?";
        $usage_stmt = $conn->prepare($usage_sql);
        $usage_stmt->bind_param("i", $program_id);
        $usage_stmt->execute();
        $usage = $usage_stmt->get_result()->fetch_assoc();
        
        if ($usage['count'] > 0) {
            $error = 'Невозможно удалить программу, она используется в тренировках';
        } else {
            // Удаляем упражнения программы и саму программу
            $conn->begin_transaction();
            
            try {
                // Удаляем детали программы
                $sql = "DELETE FROM workout_details WHERE workout_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $program_id);
                $stmt->execute();
                
                // Удаляем программу
                $sql = "DELETE FROM workout_programs WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $program_id);
                $stmt->execute();
                
                $conn->commit();
                $success = 'Программа успешно удалена';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Ошибка при удалении программы: ' . $e->getMessage();
            }
        }
    }
}

// Получение списка программ тренера
$sql = "SELECT wp.*, 
        COUNT(DISTINCT wd.user_id) as active_users,
        GROUP_CONCAT(DISTINCT e.name SEPARATOR ', ') as exercises
        FROM workout_programs wp 
        LEFT JOIN workout_diary wd ON wp.id = wd.program_id
        LEFT JOIN workout_details wdt ON wp.id = wdt.workout_id
        LEFT JOIN exercises e ON wdt.exercise_id = e.id
        WHERE wp.created_by = ?
        GROUP BY wp.id
        ORDER BY wp.type, wp.difficulty_level, wp.name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Группировка программ по типу
$grouped_programs = [];
foreach ($programs as $program) {
    $grouped_programs[$program['type']][] = $program;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои программы - FitClub</title>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Мои программы тренировок</h2>
            <a href="create_program.php" class="btn btn-success">Создать новую программу</a>
        </div>

        <?php if(empty($programs)): ?>
            <div class="alert alert-info">
                У вас пока нет созданных программ тренировок
            </div>
        <?php else: ?>
            <?php foreach($grouped_programs as $type => $programs): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($type); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Описание</th>
                                        <th>Сложность</th>
                                        <th>Упражнения</th>
                                        <th>Активных пользователей</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($programs as $program): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($program['name']); ?></td>
                                            <td><?php echo htmlspecialchars($program['description']); ?></td>
                                            <td>
                                                <?php 
                                                    $difficulty_levels = [
                                                        'easy' => 'Начинающий',
                                                        'normal' => 'Средний',
                                                        'hard' => 'Продвинутый'
                                                    ];
                                                    echo $difficulty_levels[$program['difficulty_level']] ?? $program['difficulty_level'];
                                                ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo htmlspecialchars($program['exercises'] ?: 'Нет упражнений'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $program['active_users']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit_program.php?id=<?php echo $program['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        Редактировать
                                                    </a>
                                                    <?php if($program['active_users'] == 0): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="program_id" 
                                                                   value="<?php echo $program['id']; ?>">
                                                            <button type="submit" name="delete_program" 
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Вы уверены, что хотите удалить эту программу?')">
                                                                Удалить
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 