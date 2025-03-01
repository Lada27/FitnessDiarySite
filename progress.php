<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Обработка формы добавления новых измерений
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $notes = trim($_POST['notes']);
    $measurement_date = date('Y-m-d');

    if ($weight > 0 && $height > 0) {
        $sql = "INSERT INTO user_progress (user_id, measurement_date, weight, height, notes) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdds", $user_id, $measurement_date, $weight, $height, $notes);
        
        if ($stmt->execute()) {
            $success = 'Измерения успешно добавлены';
        } else {
            $error = 'Ошибка при добавлении измерений';
        }
    } else {
        $error = 'Введите корректные значения веса и роста';
    }
}

// Обработка удаления измерения
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    
    // Проверяем, принадлежит ли запись пользователю
    $sql = "DELETE FROM user_progress WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $user_id);
    
    if ($stmt->execute()) {
        $success = 'Измерение удалено';
        header("Location: progress.php");
        exit();
    } else {
        $error = 'Ошибка при удалении измерения';
    }
}

// Обработка редактирования измерения
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $notes = trim($_POST['notes']);
    
    if ($weight > 0 && $height > 0) {
        // Проверяем, принадлежит ли запись пользователю
        $sql = "UPDATE user_progress 
                SET weight = ?, height = ?, notes = ? 
                WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddsii", $weight, $height, $notes, $edit_id, $user_id);
        
        if ($stmt->execute()) {
            $success = 'Измерение обновлено';
            header("Location: progress.php");
            exit();
        } else {
            $error = 'Ошибка при обновлении измерения';
        }
    } else {
        $error = 'Введите корректные значения веса и роста';
    }
}

// Получение всех измерений пользователя
$sql = "SELECT * FROM user_progress 
        WHERE user_id = ? 
        ORDER BY measurement_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$measurements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Подготовка данных для графика
$dates = [];
$weights = [];
$heights = [];
foreach ($measurements as $measurement) {
    $dates[] = date('d.m.Y', strtotime($measurement['measurement_date']));
    $weights[] = $measurement['weight'];
    $heights[] = $measurement['height'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой прогресс - FitClub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li class="nav-item">
                        <a class="nav-link" href="workout_diary.php">Дневник тренировок</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="progress.php">Мой прогресс</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Личный кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Выход</a>
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
            <!-- График прогресса -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">График прогресса</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <!-- Таблица измерений -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">История измерений</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Вес (кг)</th>
                                        <th>Рост (см)</th>
                                        <th>Заметки</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach(array_reverse($measurements) as $measurement): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y', strtotime($measurement['measurement_date'])); ?></td>
                                            <td><?php echo $measurement['weight']; ?></td>
                                            <td><?php echo $measurement['height']; ?></td>
                                            <td><?php echo htmlspecialchars($measurement['notes']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editModal<?php echo $measurement['id']; ?>">
                                                    Изменить
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?php echo $measurement['id']; ?>">
                                                    Удалить
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Форма добавления измерений -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Добавить измерения</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="weight" class="form-label">Вес (кг)</label>
                                <input type="number" step="0.1" class="form-control" id="weight" name="weight" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="height" class="form-label">Рост (см)</label>
                                <input type="number" step="0.1" class="form-control" id="height" name="height" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Заметки</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Добавить измерения</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Инициализация графика
        const ctx = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Вес (кг)',
                        data: <?php echo json_encode($weights); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    },
                    {
                        label: 'Рост (см)',
                        data: <?php echo json_encode($heights); ?>,
                        borderColor: 'rgb(153, 102, 255)',
                        tension: 0.1
                    }
                ]
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

    <!-- Модальные окна -->
    <?php foreach($measurements as $measurement): ?>
        <!-- Модальное окно редактирования -->
        <div class="modal fade" id="editModal<?php echo $measurement['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактировать измерение</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="edit_id" value="<?php echo $measurement['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="weight<?php echo $measurement['id']; ?>" class="form-label">Вес (кг)</label>
                                <input type="number" step="0.1" class="form-control" 
                                       id="weight<?php echo $measurement['id']; ?>" 
                                       name="weight" 
                                       value="<?php echo $measurement['weight']; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="height<?php echo $measurement['id']; ?>" class="form-label">Рост (см)</label>
                                <input type="number" step="0.1" class="form-control" 
                                       id="height<?php echo $measurement['id']; ?>" 
                                       name="height" 
                                       value="<?php echo $measurement['height']; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes<?php echo $measurement['id']; ?>" class="form-label">Заметки</label>
                                <textarea class="form-control" 
                                          id="notes<?php echo $measurement['id']; ?>" 
                                          name="notes" 
                                          rows="3"><?php echo htmlspecialchars($measurement['notes']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно удаления -->
        <div class="modal fade" id="deleteModal<?php echo $measurement['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Подтверждение удаления</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Вы действительно хотите удалить это измерение?</p>
                        <p>Дата: <?php echo date('d.m.Y', strtotime($measurement['measurement_date'])); ?></p>
                        <p>Вес: <?php echo $measurement['weight']; ?> кг</p>
                        <p>Рост: <?php echo $measurement['height']; ?> см</p>
                    </div>
                    <div class="modal-footer">
                        <form method="POST" action="">
                            <input type="hidden" name="delete_id" value="<?php echo $measurement['id']; ?>">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-danger">Удалить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html> 