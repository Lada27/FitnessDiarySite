<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Получение данных пользователя
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $age = !empty($_POST['age']) ? intval($_POST['age']) : null;
    $height = !empty($_POST['height']) ? intval($_POST['height']) : null;
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверка email на уникальность
    if ($email !== $user['email']) {
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Этот email уже используется';
        }
    }

    // Если нет ошибок, обновляем данные
    if (empty($error)) {
        if (!empty($current_password)) {
            // Если указан текущий пароль, проверяем его и обновляем на новый
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET name = ?, email = ?, phone = ?, 
                            age = ?, height = ?, weight = ?, password = ? 
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssiidsi", $name, $email, $phone, $age, 
                                    $height, $weight, $hashed_password, $user_id);
                } else {
                    $error = 'Новые пароли не совпадают';
                }
            } else {
                $error = 'Неверный текущий пароль';
            }
        } else {
            // Обновляем данные без изменения пароля
            $sql = "UPDATE users SET name = ?, email = ?, phone = ?, 
                    age = ?, height = ?, weight = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiidi", $name, $email, $phone, $age, 
                            $height, $weight, $user_id);
        }

        if ($stmt->execute()) {
            $success = 'Профиль успешно обновлен';
            // Обновляем данные пользователя для отображения
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Ошибка при обновлении профиля';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля - FitClub</title>
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
                        <a class="nav-link" href="profile.php">Назад в профиль</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Редактирование профиля</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Имя</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Телефон</label>
                                    <input type="text" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Возраст</label>
                                    <input type="number" class="form-control" name="age" 
                                           value="<?php echo $user['age']; ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Рост (см)</label>
                                    <input type="number" class="form-control" name="height" 
                                           value="<?php echo $user['height']; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Вес (кг)</label>
                                    <input type="number" step="0.1" class="form-control" name="weight" 
                                           value="<?php echo $user['weight']; ?>">
                                </div>
                            </div>

                            <hr>

                            <h5>Изменение пароля</h5>
                            <p class="text-muted">Оставьте поля пустыми, если не хотите менять пароль</p>

                            <div class="mb-3">
                                <label class="form-label">Текущий пароль</label>
                                <input type="password" class="form-control" name="current_password">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Новый пароль</label>
                                    <input type="password" class="form-control" name="new_password">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Подтверждение нового пароля</label>
                                    <input type="password" class="form-control" name="confirm_password">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                                <a href="profile.php" class="btn btn-secondary">Отмена</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 