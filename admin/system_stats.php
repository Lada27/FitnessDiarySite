<?php
session_start();
require_once '../config/db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Получение общей статистики
$stats = [];

// Общее количество пользователей по ролям
$sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$result = $conn->query($sql);
$stats['users_by_role'] = $result->fetch_all(MYSQLI_ASSOC);

// Количество активных клиентов (с действующими тренировками)
$sql = "SELECT COUNT(DISTINCT client_id) as count 
        FROM client_trainer 
        WHERE end_date IS NULL OR end_date >= CURDATE()";
$result = $conn->query($sql);
$stats['active_clients'] = $result->fetch_assoc()['count'];

// Количество программ по уровням сложности
$sql = "SELECT difficulty_level, COUNT(*) as count 
        FROM workout_programs 
        GROUP BY difficulty_level";
$result = $conn->query($sql);
$stats['programs_by_difficulty'] = $result->fetch_all(MYSQLI_ASSOC);

// Топ-5 самых активных тренеров (по количеству активных клиентов)
$sql = "SELECT u.name, COUNT(ct.client_id) as clients_count 
        FROM users u 
        LEFT JOIN client_trainer ct ON u.id = ct.trainer_id 
        WHERE u.role = 'trainer' 
        AND (ct.end_date IS NULL OR ct.end_date >= CURDATE())
        GROUP BY u.id 
        ORDER BY clients_count DESC 
        LIMIT 5";
$result = $conn->query($sql);
$stats['top_trainers'] = $result->fetch_all(MYSQLI_ASSOC);

// Количество упражнений по группам мышц
$sql = "SELECT muscle_group, COUNT(*) as count 
        FROM exercises 
        GROUP BY muscle_group";
$result = $conn->query($sql);
$stats['exercises_by_muscle'] = $result->fetch_all(MYSQLI_ASSOC);

// Статистика по программам тренировок
$sql = "SELECT wp.type, COUNT(*) as count 
        FROM workout_programs wp 
        GROUP BY wp.type";
$result = $conn->query($sql);
$stats['programs_by_type'] = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика системы - FitClub</title>
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
                        <a class="nav-link" href="manage_users.php">Пользователи</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="trainers.php">Тренеры</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_exercises.php">Упражнения</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_programs.php">Программы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="system_stats.php">Статистика</a>
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
        <h2 class="mb-4">Статистика системы</h2>
        
        <div class="row">
            <!-- Статистика пользователей -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Пользователи по ролям</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach($stats['users_by_role'] as $role_stat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php 
                                        $role_names = [
                                            'admin' => 'Администраторы',
                                            'trainer' => 'Тренеры',
                                            'client' => 'Клиенты'
                                        ];
                                    ?>
                                    <?php echo $role_names[$role_stat['role']] ?? $role_stat['role']; ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $role_stat['count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Активные клиенты</strong>
                                <span class="badge bg-success rounded-pill"><?php echo $stats['active_clients']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Топ тренеров -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Топ-5 активных тренеров</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach($stats['top_trainers'] as $trainer): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($trainer['name']); ?>
                                    <span class="badge bg-info rounded-pill">
                                        <?php echo $trainer['clients_count']; ?> клиентов
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Статистика программ -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Программы по уровню сложности</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php 
                                $difficulty_names = [
                                    'easy' => 'Начинающий',
                                    'normal' => 'Средний',
                                    'hard' => 'Продвинутый'
                                ];
                                foreach($stats['programs_by_difficulty'] as $program): 
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo $difficulty_names[$program['difficulty_level']] ?? $program['difficulty_level']; ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $program['count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Статистика упражнений -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Упражнения по группам мышц</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach($stats['exercises_by_muscle'] as $exercise): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($exercise['muscle_group']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $exercise['count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Типы программ -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Программы по типам</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach($stats['programs_by_type'] as $type): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($type['type']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $type['count']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 