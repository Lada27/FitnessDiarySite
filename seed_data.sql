-- Добавление пользователей
INSERT INTO users (email, password, role, phone, name, age, height, weight) VALUES
('admin@fitclub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+7(999)111-11-11', 'Администратор', 35, 180, 80),
('trainer1@fitclub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', '+7(999)222-22-22', 'Иван Петров', 30, 185, 85),
('trainer2@fitclub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', '+7(999)333-33-33', 'Мария Сидорова', 28, 170, 60),
('client1@fitclub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', '+7(999)444-44-44', 'Алексей Иванов', 25, 175, 75),
('client2@fitclub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', '+7(999)555-55-55', 'Елена Смирнова', 27, 165, 55),
('client3@fitclub.ru', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', '+7(999)666-66-66', 'Дмитрий Козлов', 32, 182, 88);

-- Примечание: пароль для всех пользователей: 'password'

-- Связь клиент-тренер
INSERT INTO client_trainer (client_id, trainer_id) VALUES
(4, 2), -- Алексей Иванов - Иван Петров
(5, 2), -- Елена Смирнова - Иван Петров
(6, 3); -- Дмитрий Козлов - Мария Сидорова

-- Добавление упражнений
INSERT INTO exercises (name, description, muscle_group) VALUES
('Жим штанги лежа', 'Базовое упражнение для развития грудных мышц', 'Грудь'),
('Приседания со штангой', 'Базовое упражнение для ног', 'Ноги'),
('Становая тяга', 'Базовое упражнение для спины', 'Спина'),
('Отжимания на брусьях', 'Упражнение для трицепса и груди', 'Грудь, Трицепс'),
('Подтягивания', 'Упражнение для спины и бицепса', 'Спина, Бицепс'),
('Планка', 'Статическое упражнение для пресса', 'Пресс');

-- Добавление программ тренировок
INSERT INTO workout_programs (name, description, type, difficulty_level, created_by) VALUES
('Начальный уровень', 'Программа для новичков', 'Общая физическая подготовка', 'easy', 2),
('Набор массы', 'Программа для набора мышечной массы', 'Силовая', 'normal', 2),
('Похудение', 'Программа для снижения веса', 'Кардио', 'normal', 2),
('Функциональный тренинг', 'Программа для развития функциональной силы', 'Функциональная', 'hard', 3),
('Силовая подготовка', 'Программа для развития силы', 'Силовая', 'hard', 3);

-- Добавление записей в дневник тренировок
INSERT INTO workout_diary (id, user_id, workout_date, program_id, notes) VALUES
(1, 4, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), 1, 'Первая тренировка'),
(2, 4, DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), 1, 'Вторая тренировка'),
(3, 4, DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), 2, 'Переход на новую программу'),
(4, 5, DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY), 3, 'Начало программы похудения'),
(5, 5, DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), 3, 'Продолжаем работать'),
(6, 6, CURRENT_DATE, 4, 'Пробная тренировка');

-- Добавление деталей тренировок (связь тренировок с упражнениями)
INSERT INTO workout_details (workout_id, exercise_id, sets, reps, weight) VALUES
-- Для тренировки 1
(1, 1, 3, 10, 40),  -- Жим штанги лежа
(1, 2, 3, 12, 30),  -- Приседания
(1, 6, 3, 60, NULL), -- Планка

-- Для тренировки 2
(2, 3, 3, 8, 50),   -- Становая тяга
(2, 4, 3, 12, NULL), -- Отжимания
(2, 5, 3, 8, NULL),  -- Подтягивания

-- Для тренировки 3
(3, 1, 4, 8, 45),   -- Жим штанги
(3, 2, 4, 10, 35),  -- Приседания

-- Для тренировки 4
(4, 2, 3, 15, 25),  -- Приседания
(4, 6, 4, 45, NULL), -- Планка

-- Для тренировки 5
(5, 3, 3, 12, 40),  -- Становая тяга
(5, 5, 4, 10, NULL); -- Подтягивания


-- Добавление комментариев тренера
INSERT INTO trainer_comments (workout_id, trainer_id, comment) VALUES
(1, 2, 'Хорошее начало, следите за техникой в приседаниях'),
(2, 2, 'Увеличьте вес в становой тяге на следующей тренировке'),
(3, 2, 'Отличный прогресс в жиме лежа'),
(4, 3, 'Хорошая работа, добавим кардио на следующей тренировке'),
(5, 3, 'Увеличьте темп выполнения упражнений');

-- Добавление уведомлений
INSERT INTO notifications (from_user_id, to_user_id, message, is_read) VALUES
(2, 4, 'Ваша следующая тренировка завтра в 10:00', FALSE),
(2, 4, 'Не забудьте заполнить дневник тренировок', FALSE),
(3, 5, 'Изменения в программе тренировок', FALSE),
(2, 4, 'Отличный прогресс на этой неделе!', FALSE),
(3, 6, 'Подтвердите время следующей тренировки', FALSE);

-- Добавление записей прогресса
INSERT INTO user_progress (user_id, measurement_date, weight, height, notes) VALUES
(4, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY), 77, 175, 'Начальные измерения'),
(4, DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY), 76, 175, 'Снижение веса'),
(4, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 75, 175, 'Продолжаем снижать вес'),
(5, DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), 57, 165, 'Начальные измерения'),
(5, DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY), 56, 165, 'Прогресс идет'),
(6, CURRENT_DATE, 88, 182, 'Первые измерения'); 