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

// Обработка отправки уведомления
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_notification'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $sql = "INSERT INTO notifications (from_user_id, to_user_id, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $_SESSION['user_id'], $client_id, $message);
        
        if ($stmt->execute()) {
            $success = 'Уведомление успешно отправлено';
        } else {
            $error = 'Ошибка при отправке уведомления';
        }
    } else {
        $error = 'Введите текст уведомления';
    }
}

// Получение истории уведомлений
$sql = "SELECT * FROM notifications 
        WHERE (from_user_id = ? AND to_user_id = ?) 
           OR (from_user_id = ? AND to_user_id = ?)
        ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $_SESSION['user_id'], $client_id, $client_id, $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль клиента - FitClub</title>
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

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Профиль клиента: <?php echo htmlspecialchars($client['name']); ?></h4>
                        <a href="view_progress.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary">
                            Просмотр прогресса
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Контактная информация</h5>
                                <p>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($client['email']); ?><br>
                                    <strong>Телефон:</strong> <?php echo htmlspecialchars($client['phone']); ?><br>
                                    <strong>Тренируется с:</strong> <?php echo date('d.m.Y', strtotime($client['training_start'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Отправить уведомление</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="message" class="form-label">Текст уведомления</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="send_notification" class="btn btn-primary">Отправить</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">История уведомлений</h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($notifications)): ?>
                            <p class="text-muted">Нет уведомлений</p>
                        <?php else: ?>
                            <?php foreach($notifications as $notification): ?>
                                <div class="mb-3 p-2 <?php echo $notification['from_user_id'] == $_SESSION['user_id'] ? 'bg-light' : ''; ?>">
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?>
                                        <?php echo $notification['from_user_id'] == $_SESSION['user_id'] ? '(Вы)' : '(Клиент)'; ?>
                                    </small>
                                    <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 