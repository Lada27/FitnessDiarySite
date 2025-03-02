<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Получение данных пользователя
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Получение дополнительных данных в зависимости от роли
$additional_data = [];

if ($role == 'client') {
    // Получение тренера клиента
    $sql = "SELECT u.name, u.email, u.phone 
            FROM users u 
            JOIN client_trainer ct ON u.id = ct.trainer_id 
            WHERE ct.client_id = ? AND ct.end_date IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $trainer = $stmt->get_result()->fetch_assoc();
    
    // Получение последних тренировок
    $sql = "SELECT wd.*, wp.name as program_name 
            FROM workout_diary wd
            LEFT JOIN workout_programs wp ON wd.program_id = wp.id
            WHERE wd.user_id = ?
            ORDER BY wd.workout_date DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $workouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} elseif ($role == 'trainer') {
    // Получение списка клиентов тренера
    $sql = "SELECT u.id, u.name, u.email, u.phone 
            FROM users u 
            JOIN client_trainer ct ON u.id = ct.client_id 
            WHERE ct.trainer_id = ? AND ct.end_date IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Получение программ тренировок тренера
    $sql = "SELECT * FROM workout_programs WHERE created_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $programs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Получение непрочитанных уведомлений
$sql = "SELECT * FROM notifications 
        WHERE to_user_id = ? AND is_read = 0 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Обработка отметки уведомления как прочитанного
if (isset($_POST['mark_read'])) {
    $notification_id = intval($_POST['notification_id']);
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND to_user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
}

// Обработка отметки всех уведомлений как прочитанных
if (isset($_POST['mark_all_read'])) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE to_user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Получение детальной информации о тренировках
$sql = "SELECT wd.*, wp.name as program_name, wp.type, wp.difficulty_level,
        GROUP_CONCAT(CONCAT(e.name, ' (', wdt.sets, 'x', wdt.reps, ' ', IFNULL(CONCAT(wdt.weight, 'кг'), ''), ')') SEPARATOR '\n') as exercises,
        GROUP_CONCAT(tc.comment SEPARATOR '\n') as trainer_comments
        FROM workout_diary wd
        LEFT JOIN workout_programs wp ON wd.program_id = wp.id
        LEFT JOIN workout_details wdt ON wd.id = wdt.workout_id
        LEFT JOIN exercises e ON wdt.exercise_id = e.id
        LEFT JOIN trainer_comments tc ON wd.id = tc.workout_id
        WHERE wd.user_id = ?
        GROUP BY wd.id
        ORDER BY wd.workout_date DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$workouts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
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
                    <?php if($_SESSION['role'] === 'client'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="workout_diary.php">Дневник тренировок</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="progress.php">Мой прогресс</a>
                        </li>
                    <?php elseif($_SESSION['role'] === 'trainer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="trainer/my_clients.php">Мои клиенты</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="trainer/create_program.php">Создать программу</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="trainer/my_programs.php">Мои программы</a>
                        </li>
                    <?php elseif($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/manage_users.php">Пользователи</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/trainers.php">Тренеры</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/manage_exercises.php">Упражнения</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/manage_programs.php">Программы</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/system_stats.php">Статистика</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">Личный кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php" onclick="return confirm('Вы действительно хотите выйти из аккаунта?')">Выход</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <!-- Левая колонка - Профиль -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Профиль</h4>
                    </div>
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($user['name'] ?: 'Имя не указано'); ?></h5>
                        <p class="text-muted"><?php echo ucfirst($role); ?></p>
                        <ul class="list-unstyled">
                            <li><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
                            <li><strong>Телефон:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Не указан'); ?></li>
                            <?php if($role == 'client'): ?>
                                <li><strong>Возраст:</strong> <?php echo $user['age'] ?: 'Не указан'; ?></li>
                                <li><strong>Рост:</strong> <?php echo $user['height'] ? $user['height'] . ' см' : 'Не указан'; ?></li>
                                <li><strong>Вес:</strong> <?php echo $user['weight'] ? $user['weight'] . ' кг' : 'Не указан'; ?></li>
                            <?php endif; ?>
                        </ul>
                        <a href="edit_profile.php" class="btn btn-primary">Редактировать профиль</a>
                    </div>
                </div>

                <!-- Уведомления -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Уведомления</h5>
                        <?php if (!empty($notifications)): ?>
                            <form method="POST" class="m-0">
                                <button type="submit" name="mark_all_read" class="btn btn-sm btn-secondary">
                                    Отметить все как прочитанные
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <p class="text-muted">Нет новых уведомлений</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item p-3 mb-2 <?php echo $notification['is_read'] ? 'bg-light' : 'bg-info bg-opacity-10'; ?> rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="mark_read" class="btn btn-sm btn-primary">
                                                    Отметить прочитанным
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Правая колонка - Основной контент -->
            <div class="col-md-8">
                <?php if($role == 'client'): ?>
                    <!-- Контент для клиента -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0">Мой тренер</h4>
                        </div>
                        <div class="card-body">
                            <?php if($trainer): ?>
                                <h5><?php echo htmlspecialchars($trainer['name']); ?></h5>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($trainer['email']); ?></p>
                                <p><strong>Телефон:</strong> <?php echo htmlspecialchars($trainer['phone']); ?></p>
                            <?php else: ?>
                                <p>Тренер не назначен</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Последние тренировки</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($workouts)): ?>
                                <p class="text-muted">Нет записей о тренировках</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Дата</th>
                                                <th>Программа</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($workouts as $workout): ?>
                                                <tr>
                                                    <td><?php echo date('d.m.Y', strtotime($workout['workout_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($workout['program_name']); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#workoutModal<?php echo $workout['id']; ?>">
                                                            Подробнее
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif($role == 'trainer'): ?>
                    <!-- Контент для тренера -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0">Мои клиенты</h4>
                        </div>
                        <div class="card-body">
                            <?php if(empty($clients)): ?>
                                <p>У вас пока нет клиентов</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Имя</th>
                                                <th>Email</th>
                                                <th>Телефон</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($clients as $client): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['phone']); ?></td>
                                                    <td>
                                                        <a href="trainer/client_profile.php?id=<?php echo $client['id']; ?>" 
                                                           class="btn btn-sm btn-primary">Просмотр</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Мои программы тренировок</h4>
                        </div>
                        <div class="card-body">
                            <?php if(empty($programs)): ?>
                                <p>У вас пока нет созданных программ</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Название</th>
                                                <th>Тип</th>
                                                <th>Сложность</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($programs as $program): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($program['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($program['type']); ?></td>
                                                    <td><?php echo htmlspecialchars($program['difficulty_level']); ?></td>
                                                    <td>
                                                        <a href="trainer/edit_program.php?id=<?php echo $program['id']; ?>" 
                                                           class="btn btn-sm btn-primary">Редактировать</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            <a href="trainer/create_program.php" class="btn btn-success mt-3">Создать программу</a>
                        </div>
                    </div>

                <?php elseif($role == 'admin'): ?>
                    <!-- Контент для администратора -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Панель администратора</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="admin/manage_users.php" class="btn btn-primary btn-lg w-100">
                                        Управление пользователями
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="admin/manage_exercises.php" class="btn btn-primary btn-lg w-100">
                                        Управление упражнениями
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="admin/system_stats.php" class="btn btn-primary btn-lg w-100">
                                        Статистика системы
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="admin/manage_programs.php" class="btn btn-primary btn-lg w-100">
                                        Управление программами
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальные окна для тренировок -->
    <?php foreach ($workouts as $workout): ?>
        <div class="modal fade" id="workoutModal<?php echo $workout['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Тренировка от <?php echo date('d.m.Y', strtotime($workout['workout_date'])); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Программа:</h6>
                                <p><?php echo htmlspecialchars($workout['program_name'] ?? 'Не указана'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Тип программы:</h6>
                                <p><?php echo htmlspecialchars($workout['type'] ?? 'Не указан'); ?></p>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            Подробнее об упражнениях смотрите в дневнике тренировок
                        </div>

                        <?php if (!empty($workout['trainer_comments'])): ?>
                            <h6>Комментарии тренера:</h6>
                            <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($workout['trainer_comments']); ?></pre>
                        <?php endif; ?>

                        <?php if (!empty($workout['notes'])): ?>
                            <h6>Заметки:</h6>
                            <p><?php echo nl2br(htmlspecialchars($workout['notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <a href="workout_diary.php?date=<?php echo $workout['workout_date']; ?>" class="btn btn-primary">
                            Перейти в дневник
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 