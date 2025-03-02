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

// Обработка удаления пользователя
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    
    // Проверяем, не пытается ли админ удалить сам себя
    if ($user_id === $_SESSION['user_id']) {
        $error = 'Вы не можете удалить свой аккаунт';
    } else {
        // Начинаем транзакцию
        $conn->begin_transaction();
        
        try {
            // Удаляем связанные записи
            $tables = [
                'client_trainer',
                'workout_diary',
                'user_progress',
                'notifications',
                'trainer_comments'
            ];
            
            foreach ($tables as $table) {
                $sql = "DELETE FROM $table WHERE user_id = ? OR 
                        (CASE 
                            WHEN '$table' = 'client_trainer' THEN client_id = ? OR trainer_id = ?
                            WHEN '$table' = 'notifications' THEN from_user_id = ? OR to_user_id = ?
                            WHEN '$table' = 'trainer_comments' THEN trainer_id = ?
                            ELSE FALSE 
                        END)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
                $stmt->execute();
            }
            
            // Удаляем пользователя
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $conn->commit();
            $success = 'Пользователь успешно удален';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Ошибка при удалении пользователя: ' . $e->getMessage();
        }
    }
}

// Обработка изменения даты окончания
if (isset($_POST['update_end_date'])) {
    $client_trainer_id = intval($_POST['client_trainer_id']);
    $end_date = $_POST['end_date'] ?: null;
    
    $sql = "UPDATE client_trainer SET end_date = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $end_date, $client_trainer_id);
    
    if ($stmt->execute()) {
        $success = 'Дата окончания успешно обновлена';
    } else {
        $error = 'Ошибка при обновлении даты';
    }
}

// Обработка добавления новой связи клиент-тренер
if (isset($_POST['add_client_trainer'])) {
    $client_id = intval($_POST['client_id']);
    $trainer_id = intval($_POST['trainer_id']);
    $start_date = $_POST['start_date'];
    
    $sql = "INSERT INTO client_trainer (client_id, trainer_id, start_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $client_id, $trainer_id, $start_date);
    
    if ($stmt->execute()) {
        $success = 'Тренер успешно назначен клиенту';
    } else {
        $error = 'Ошибка при назначении тренера';
    }
}

// Получение списка всех пользователей с информацией о тренерах
$sql = "SELECT u.*, 
        ct.id as client_trainer_id,
        ct.start_date,
        ct.end_date,
        t.name as trainer_name,
        t.id as trainer_id
        FROM users u 
        LEFT JOIN client_trainer ct ON u.id = ct.client_id
        LEFT JOIN users t ON ct.trainer_id = t.id
        ORDER BY u.role, u.name";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Получение списка клиентов без тренера
$sql = "SELECT u.* FROM users u 
        LEFT JOIN client_trainer ct ON u.id = ct.client_id AND ct.end_date IS NULL
        WHERE u.role = 'client' AND ct.id IS NULL";
$unassigned_clients = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Получение списка всех тренеров
$sql = "SELECT * FROM users WHERE role = 'trainer'";
$trainers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
                        <a class="nav-link active" href="manage_users.php">Пользователи</a>
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
                        <a class="nav-link" href="system_stats.php">Статистика</a>
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Управление пользователями</h4>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addClientTrainerModal">
                    Назначить тренера
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Роль</th>
                                <th>Тренер</th>
                                <th>Дата начала</th>
                                <th>Дата окончания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['trainer_name'] ?? 'Не назначен'); ?></td>
                                    <td><?php echo $user['start_date'] ? date('d.m.Y', strtotime($user['start_date'])) : '-'; ?></td>
                                    <td><?php echo $user['end_date'] ? date('d.m.Y', strtotime($user['end_date'])) : 'Активен'; ?></td>
                                    <td>
                                        <?php if($user['role'] === 'client' && $user['client_trainer_id']): ?>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editDateModal<?php echo $user['client_trainer_id']; ?>">
                                                Изменить даты
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления связи клиент-тренер -->
    <div class="modal fade" id="addClientTrainerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Назначить тренера клиенту</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Клиент</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Выберите клиента</option>
                                <?php foreach($unassigned_clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?> 
                                        (<?php echo htmlspecialchars($client['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Тренер</label>
                            <select class="form-select" name="trainer_id" required>
                                <option value="">Выберите тренера</option>
                                <?php foreach($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['id']; ?>">
                                        <?php echo htmlspecialchars($trainer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Дата начала</label>
                            <input type="date" class="form-control" name="start_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_client_trainer" class="btn btn-primary">Назначить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна изменения дат -->
    <?php foreach($users as $user): ?>
        <?php if($user['role'] === 'client' && $user['client_trainer_id']): ?>
            <div class="modal fade" id="editDateModal<?php echo $user['client_trainer_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Изменить даты тренировок</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="client_trainer_id" 
                                       value="<?php echo $user['client_trainer_id']; ?>">
                                
                                <p>
                                    <strong>Клиент:</strong> <?php echo htmlspecialchars($user['name']); ?><br>
                                    <strong>Тренер:</strong> <?php echo htmlspecialchars($user['trainer_name']); ?>
                                </p>
                                
                                
                                <div class="mb-3">
                                    <label class="form-label">Дата окончания</label>
                                    <input type="date" class="form-control" name="end_date"
                                           value="<?php echo $user['end_date']; ?>">
                                    <small class="text-muted">Оставьте пустым для активных тренировок</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                <button type="submit" name="update_end_date" class="btn btn-primary">Сохранить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 