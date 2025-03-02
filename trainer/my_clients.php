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

// Получение списка клиентов тренера с их программами и прогрессом
$sql = "SELECT u.id, u.name, u.email, u.phone, u.age, u.height, u.weight,
        ct.start_date, ct.end_date,
        (SELECT COUNT(*) FROM workout_diary wd WHERE wd.user_id = u.id) as workouts_count,
        (SELECT workout_date FROM workout_diary wd 
         WHERE wd.user_id = u.id 
         ORDER BY workout_date DESC LIMIT 1) as last_workout,
        GROUP_CONCAT(DISTINCT wp.name SEPARATOR ', ') as available_programs
        FROM users u
        JOIN client_trainer ct ON u.id = ct.client_id
        LEFT JOIN workout_programs wp ON wp.created_by = ct.trainer_id
        WHERE ct.trainer_id = ? AND (ct.end_date IS NULL OR ct.end_date >= CURDATE())
        GROUP BY u.id, u.name, u.email, u.phone, u.age, u.height, u.weight, 
                 ct.start_date, ct.end_date
        ORDER BY u.name";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$active_clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получение бывших клиентов
$sql = "SELECT u.id, u.name, u.email, ct.end_date,
        (SELECT COUNT(*) FROM workout_diary wd WHERE wd.user_id = u.id) as total_workouts
        FROM users u
        JOIN client_trainer ct ON u.id = ct.client_id
        WHERE ct.trainer_id = ? AND ct.end_date < CURDATE()
        ORDER BY ct.end_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$former_clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои клиенты - FitClub</title>
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
                        <a class="nav-link active" href="my_clients.php">Мои клиенты</a>
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
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <h2 class="mb-4">Активные клиенты</h2>
        
        <?php if(empty($active_clients)): ?>
            <div class="alert alert-info">У вас пока нет активных клиентов</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Контакты</th>
                            <th>Параметры</th>
                            <th>Доступные программы</th>
                            <th>Статистика</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($active_clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td>
                                    <div>Email: <?php echo htmlspecialchars($client['email']); ?></div>
                                    <div>Тел: <?php echo htmlspecialchars($client['phone']); ?></div>
                                </td>
                                <td>
                                    <div>Возраст: <?php echo $client['age']; ?></div>
                                    <div>Рост: <?php echo $client['height']; ?> см</div>
                                    <div>Вес: <?php echo $client['weight']; ?> кг</div>
                                </td>
                                <td>
                                    <?php if($client['available_programs']): ?>
                                        <?php echo htmlspecialchars($client['available_programs']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Нет доступных программ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>Всего тренировок: <?php echo $client['workouts_count']; ?></div>
                                    <?php if($client['last_workout']): ?>
                                        <div>Последняя: <?php echo date('d.m.Y', strtotime($client['last_workout'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_progress.php?client_id=<?php echo $client['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            Прогресс
                                        </a>
                                        <a href="client_profile.php?client_id=<?php echo $client['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            Профиль
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if(!empty($former_clients)): ?>
            <h3 class="mt-5 mb-4">Бывшие клиенты</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Дата окончания</th>
                            <th>Всего тренировок</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($former_clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['name']); ?></td>
                                <td><?php echo htmlspecialchars($client['email']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($client['end_date'])); ?></td>
                                <td><?php echo $client['total_workouts']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 