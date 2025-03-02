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

// Обработка добавления программы
if (isset($_POST['add_program'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $type = trim($_POST['type']);
    $difficulty_level = trim($_POST['difficulty_level']);
    $trainer_id = intval($_POST['trainer_id']);
    
    $sql = "INSERT INTO workout_programs (name, description, type, difficulty_level, created_by) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $description, $type, $difficulty_level, $trainer_id);
    
    if ($stmt->execute()) {
        $success = 'Программа успешно добавлена';
        header("Location: manage_programs.php");
        exit();
    } else {
        $error = 'Ошибка при добавлении программы';
    }
}

// Обработка редактирования программы
if (isset($_POST['edit_program'])) {
    $program_id = intval($_POST['program_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $type = trim($_POST['type']);
    $difficulty_level = trim($_POST['difficulty_level']);
    $trainer_id = intval($_POST['trainer_id']);
    
    $sql = "UPDATE workout_programs SET name = ?, description = ?, type = ?, 
            difficulty_level = ?, created_by = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $name, $description, $type, $difficulty_level, $trainer_id, $program_id);
    
    if ($stmt->execute()) {
        $success = 'Программа успешно обновлена';
        header("Location: manage_programs.php");
        exit();
    } else {
        $error = 'Ошибка при обновлении программы';
    }
}

// Обработка удаления программы
if (isset($_POST['delete_program'])) {
    $program_id = intval($_POST['program_id']);
    
    // Проверяем использование программы в тренировках
    $check_sql = "SELECT COUNT(*) as count FROM workout_diary WHERE program_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $program_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = 'Невозможно удалить программу, она используется в тренировках';
    } else {
        $sql = "DELETE FROM workout_programs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $program_id);
        
        if ($stmt->execute()) {
            $success = 'Программа успешно удалена';
        } else {
            $error = 'Ошибка при удалении программы';
        }
    }
}

// Получение списка тренеров для выбора автора программы
$sql = "SELECT id, name FROM users WHERE role = 'trainer' ORDER BY name";
$trainers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Обновим запрос получения программ, добавив информацию о тренере
$sql = "SELECT wp.*, u.name as trainer_name 
        FROM workout_programs wp 
        LEFT JOIN users u ON wp.created_by = u.id 
        ORDER BY wp.type, wp.difficulty_level, wp.name";
$programs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Группировка программ по типу
$grouped_programs = [];
foreach ($programs as $program) {
    $grouped_programs[$program['type']][] = $program;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление программами - FitClub</title>
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
                        <a class="nav-link active" href="manage_programs.php">Программы</a>
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
                <h4 class="mb-0">Управление программами тренировок</h4>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#programModal">
                    Добавить программу
                </button>
            </div>
            <div class="card-body">
                <?php foreach($grouped_programs as $type => $programs): ?>
                    <h5 class="mt-4"><?php echo htmlspecialchars($type); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th>Сложность</th>
                                    <th>Автор</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($programs as $program): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($program['name']); ?></td>
                                        <td><?php echo htmlspecialchars($program['description']); ?></td>
                                        <td>
                                            <?php 
                                                $difficulty_levels = [
                                                    'easy' => 'Начинающий',
                                                    'normal' => 'Средний',
                                                    'hard' => 'Продвинутый'
                                                ];
                                                echo htmlspecialchars($difficulty_levels[$program['difficulty_level']] ?? $program['difficulty_level']); 
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($program['trainer_name'] ?? 'Не указан'); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="editProgram(<?php echo htmlspecialchars(json_encode($program)); ?>)">
                                                Редактировать
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                <button type="submit" name="delete_program" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Вы действительно хотите удалить эту программу?')">
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

    <!-- Модальное окно добавления программы -->
    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="programModalLabel">Добавить программу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="program_id" id="program_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" class="form-control" name="name" id="program_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" id="program_description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Тип программы</label>
                            <input type="text" class="form-control" name="type" id="program_type" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Уровень сложности</label>
                            <select class="form-select" name="difficulty_level" id="program_difficulty" required>
                                <option value="easy">Начинающий</option>
                                <option value="normal">Средний</option>
                                <option value="hard">Продвинутый</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Автор программы</label>
                            <select class="form-select" name="trainer_id" id="program_trainer" required>
                                <option value="">Выберите тренера</option>
                                <?php foreach($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['id']; ?>">
                                        <?php echo htmlspecialchars($trainer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_program" id="submitBtn" class="btn btn-primary">Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Добавим JavaScript для управления модальным окном -->
    <script>
        let programModal = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            programModal = new bootstrap.Modal(document.getElementById('programModal'), {
                backdrop: 'static',
                keyboard: true
            });
            
            // Обработчик для кнопки добавления
            document.querySelector('[data-bs-target="#programModal"]').addEventListener('click', function() {
                resetModal();
            });
        });

        function resetModal() {
            document.getElementById('program_id').value = '';
            document.getElementById('program_name').value = '';
            document.getElementById('program_description').value = '';
            document.getElementById('program_type').value = '';
            document.getElementById('program_difficulty').value = 'normal';
            document.getElementById('program_trainer').value = '';
            
            document.getElementById('programModalLabel').textContent = 'Добавить программу';
            document.getElementById('submitBtn').name = 'add_program';
            document.getElementById('submitBtn').textContent = 'Добавить';
        }

        function editProgram(program) {
            document.getElementById('program_id').value = program.id;
            document.getElementById('program_name').value = program.name;
            document.getElementById('program_description').value = program.description.trim();
            document.getElementById('program_type').value = program.type;
            document.getElementById('program_difficulty').value = program.difficulty_level;
            document.getElementById('program_trainer').value = program.created_by;
            
            document.getElementById('programModalLabel').textContent = 'Изменение программы';
            document.getElementById('submitBtn').name = 'edit_program';
            document.getElementById('submitBtn').textContent = 'Сохранить';
            
            programModal.show();
        }
    </script>

    <!-- Скрипты Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html> 