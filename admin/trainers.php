<?php
session_start();
require_once '../config/db.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Обработка редактирования тренера
if (isset($_POST['edit_trainer'])) {
    $user_id = intval($_POST['user_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Проверка email на уникальность
    $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $error = 'Этот email уже используется другим пользователем';
    } else {
        // Обновление данных тренера
        $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = 'trainer'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Данные тренера успешно обновлены';
        } else {
            $error = 'Ошибка при обновлении данных';
        }
    }
}

// Получение списка тренеров с их клиентами
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM client_trainer ct 
         WHERE ct.trainer_id = u.id AND ct.end_date IS NULL) as active_clients
        FROM users u 
        WHERE u.role = 'trainer'
        ORDER BY u.name";
$result = $conn->query($sql);
$trainers = $result->fetch_all(MYSQLI_ASSOC);

// Для каждого тренера получаем список активных клиентов
$trainer_clients = [];
foreach ($trainers as $trainer) {
    $sql = "SELECT u.name, u.email, ct.start_date
            FROM users u
            JOIN client_trainer ct ON u.id = ct.client_id
            WHERE ct.trainer_id = ? AND ct.end_date IS NULL
            ORDER BY ct.start_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trainer['id']);
    $stmt->execute();
    $trainer_clients[$trainer['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление тренерами - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="trainers.php">Тренеры</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_exercises.php">Упражнения</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_programs.php">Программы</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system_stats.php">Статистика</a>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Управление тренерами</h4>
                <a href="add_trainer.php" class="btn btn-success">Добавить тренера</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach($trainers as $trainer): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($trainer['name']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <p>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($trainer['email']); ?><br>
                                        <strong>Телефон:</strong> <?php echo htmlspecialchars($trainer['phone']); ?><br>
                                        <strong>Активных клиентов:</strong> <?php echo $trainer['active_clients']; ?>
                                    </p>

                                    <?php if(!empty($trainer_clients[$trainer['id']])): ?>
                                        <h6>Клиенты:</h6>
                                        <ul class="list-group">
                                            <?php foreach($trainer_clients[$trainer['id']] as $client): ?>
                                                <li class="list-group-item">
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                    <small class="text-muted d-block">
                                                        с <?php echo date('d.m.Y', strtotime($client['start_date'])); ?>
                                                    </small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">Нет активных клиентов</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?php echo $trainer['id']; ?>">
                                        Редактировать
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальные окна для редактирования -->
    <?php foreach($trainers as $trainer): ?>
        <div class="modal fade" id="editModal<?php echo $trainer['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактировать тренера</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="trainers.php">
                        <div class="modal-body">
                            <input type="hidden" name="user_id" value="<?php echo $trainer['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Имя</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($trainer['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($trainer['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Телефон</label>
                                <input type="text" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($trainer['phone']); ?>">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" name="edit_trainer" class="btn btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 