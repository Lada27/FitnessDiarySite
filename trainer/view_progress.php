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

// Получение ID клиента из GET-параметра
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Проверка, является ли этот клиент клиентом данного тренера
$sql = "SELECT u.*, ct.start_date as training_start 
        FROM users u 
        JOIN client_trainer ct ON u.id = ct.client_id 
        WHERE u.id = ? AND ct.trainer_id = ? AND (ct.end_date IS NULL OR ct.end_date >= CURDATE())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $client_id, $_SESSION['user_id']);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

if (!$client) {
    header("Location: my_clients.php");
    exit();
}

// Получение записей прогресса клиента
$sql = "SELECT * FROM user_progress 
        WHERE user_id = ? 
        ORDER BY measurement_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$progress_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получение статистики тренировок
$sql = "SELECT 
            COUNT(*) as total_workouts,
            MIN(workout_date) as first_workout,
            MAX(workout_date) as last_workout,
            (SELECT COUNT(DISTINCT workout_date) 
             FROM workout_diary 
             WHERE user_id = ? 
             AND workout_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as workouts_last_month
        FROM workout_diary 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $client_id, $client_id);
$stmt->execute();
$workout_stats = $stmt->get_result()->fetch_assoc();

// Получение последних комментариев тренера
$sql = "SELECT tc.*, wd.workout_date 
        FROM trainer_comments tc
        JOIN workout_diary wd ON tc.workout_id = wd.id
        WHERE wd.user_id = ? AND tc.trainer_id = ?
        ORDER BY tc.created_at DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $client_id, $_SESSION['user_id']);
$stmt->execute();
$recent_comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Прогресс клиента - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a class="nav-link" href="my_programs.php">Мои программы</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../profile.php">Личный кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php" onclick="return confirm('Вы действительно хотите выйти из аккаунта?')">Выход</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Информация о клиенте</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Личные данные</h5>
                                <p>
                                    <strong>Имя:</strong> <?php echo htmlspecialchars($client['name']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?><br>
                                    <strong>Телефон:</strong> <?php echo htmlspecialchars($client['phone']); ?><br>
                                    <strong>Возраст:</strong> <?php echo $client['age']; ?> лет
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h5>Физические параметры</h5>
                                <p>
                                    <strong>Рост:</strong> <?php echo $client['height']; ?> см<br>
                                    <strong>Вес:</strong> <?php echo $client['weight']; ?> кг<br>
                                    <strong>Тренируется с:</strong> <?php echo date('d.m.Y', strtotime($client['training_start'])); ?>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h5>Статистика тренировок</h5>
                                <p>
                                    <strong>Всего тренировок:</strong> <?php echo $workout_stats['total_workouts']; ?><br>
                                    <strong>За последний месяц:</strong> <?php echo $workout_stats['workouts_last_month']; ?><br>
                                    <?php if($workout_stats['last_workout']): ?>
                                        <strong>Последняя тренировка:</strong> 
                                        <?php echo date('d.m.Y', strtotime($workout_stats['last_workout'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">График прогресса</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Последние комментарии</h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($recent_comments)): ?>
                            <p class="text-muted">Нет комментариев</p>
                        <?php else: ?>
                            <?php foreach($recent_comments as $comment): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        Тренировка от <?php echo date('d.m.Y', strtotime($comment['workout_date'])); ?>
                                    </small>
                                    <p class="mb-0"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Подготовка данных для графика
        const progressData = {
            labels: <?php echo json_encode(array_map(function($record) {
                return date('d.m.Y', strtotime($record['measurement_date']));
            }, $progress_records)); ?>,
            weight: <?php echo json_encode(array_map(function($record) {
                return $record['weight'];
            }, $progress_records)); ?>
        };

        // Создание графика
        const ctx = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: progressData.labels,
                datasets: [{
                    label: 'Вес (кг)',
                    data: progressData.weight,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 