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

// Обработка добавления упражнения
if (isset($_POST['add_exercise'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $muscle_group = trim($_POST['muscle_group']);
    
    $sql = "INSERT INTO exercises (name, description, muscle_group) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $description, $muscle_group);
    
    if ($stmt->execute()) {
        $success = 'Упражнение успешно добавлено';
        // Перенаправляем, чтобы избежать повторной отправки формы
        header("Location: manage_exercises.php");
        exit();
    } else {
        $error = 'Ошибка при добавлении упражнения';
    }
}

// Обработка удаления упражнения
if (isset($_POST['delete_exercise'])) {
    $exercise_id = intval($_POST['exercise_id']);
    
    // Проверяем использование упражнения в тренировках
    $check_sql = "SELECT COUNT(*) as count FROM workout_details WHERE exercise_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $exercise_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = 'Невозможно удалить упражнение, оно используется в тренировках';
    } else {
        $sql = "DELETE FROM exercises WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $exercise_id);
        
        if ($stmt->execute()) {
            $success = 'Упражнение успешно удалено';
        } else {
            $error = 'Ошибка при удалении упражнения';
        }
    }
}

// Получение списка упражнений
$sql = "SELECT * FROM exercises ORDER BY muscle_group, name";
$exercises = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Группировка упражнений по группам мышц
$grouped_exercises = [];
foreach ($exercises as $exercise) {
    $grouped_exercises[$exercise['muscle_group']][] = $exercise;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление упражнениями - FitClub</title>
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
                        <a class="nav-link" href="trainers.php">Тренеры</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_exercises.php">Упражнения</a>
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
            <div class="card-header">
                <h4 class="mb-0">Управление упражнениями</h4>
            </div>
            <div class="card-body">
                <!-- Форма добавления упражнения -->
                <form method="POST" class="mb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Группа мышц</label>
                            <input type="text" class="form-control" name="muscle_group" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_exercise" class="btn btn-success d-block">Добавить упражнение</button>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                </form>

                <hr>

                <!-- Существующий код таблицы упражнений -->
                <?php foreach($grouped_exercises as $muscle_group => $exercises): ?>
                    <h5 class="mt-4"><?php echo htmlspecialchars($muscle_group); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($exercises as $exercise): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exercise['name']); ?></td>
                                        <td><?php echo htmlspecialchars($exercise['description']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                                                <button type="submit" name="delete_exercise" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Вы действительно хотите удалить это упражнение?')">
                                                    Удалить
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 