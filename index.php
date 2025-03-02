<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitClub - Ваш путь к здоровью</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
    .about-container {
        background-image: url('images/gym-background.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        padding: 40px 20px;
        min-height: 600px;
        position: relative;
    }

    .quote-container {
        background-image: url('images/quote.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        padding: 40px 20px;
        min-height: 600px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quote-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
    }

    .quote-text {
        color: white;
        font-size: 2.5rem !important;
        font-weight: 700;
        text-transform: uppercase;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        position: relative;
        z-index: 1;
        text-align: center;
        max-width: 800px;
        margin: 0 auto;
        line-height: 1.4;
    }

    .quote-author {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.2rem;
        margin-top: 20px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    .about-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
    }

    .about-container > * {
        position: relative;
        z-index: 1;
    }

    .main {
        margin-top: 120px;
        color: white;
        text-align: center;
    }

    .main h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }

    .main p {
        font-size: 1.5rem;
        margin-bottom: 30px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    .service-card {
        transition: transform 0.3s ease;
        border: none;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .service-card:hover {
        transform: translateY(-10px);
    }

    .service-icon {
        font-size: 2.5rem;
        color: #0d6efd;
        margin-bottom: 20px;
    }

    .navbar {
        background: rgba(33, 37, 41, 0.95) !important;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .btn-primary {
        padding: 12px 30px;
        font-size: 1.2rem;
        border-radius: 30px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
    }

    .about-text {
        padding: 30px !important;
        border-radius: 15px !important;
    }

    .about-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 80px 0;
    }

    .about-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 50px;
        text-align: center;
        position: relative;
    }

    .about-title:after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: #0d6efd;
        border-radius: 2px;
    }

    .history-card, .advantages-card {
        background: white;
        border-radius: 20px;
        padding: 40px;
        height: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }

    .history-card:hover, .advantages-card:hover {
        transform: translateY(-10px);
    }

    .section-subtitle {
        color: #0d6efd;
        font-size: 1.8rem;
        margin-bottom: 25px;
        font-weight: 600;
    }

    .history-text {
        color: #6c757d;
        font-size: 1.1rem;
        line-height: 1.8;
    }

    .advantage-item {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .advantage-item:hover {
        background: #e9ecef;
        transform: translateX(10px);
    }

    .advantage-check {
        color: #0d6efd;
        font-size: 1.2rem;
        margin-right: 15px;
    }

    .advantage-text {
        color: #495057;
        font-size: 1.1rem;
        margin: 0;
    }

    .stats-container {
        margin-top: 40px;
        padding: 20px;
        background: #0d6efd;
        border-radius: 15px;
        color: white;
        text-align: center;
    }

    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .stats-text {
        font-size: 1.1rem;
        opacity: 0.9;
    }
    </style>
</head>
<body>
    <!-- Навигационное меню -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">FitClub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Главная</a>
                    </li>
                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
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
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Личный кабинет</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php" onclick="return confirm('Вы действительно хотите выйти из аккаунта?')">Выход</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Вход</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Регистрация</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Главный баннер -->
    <div class="hero-section about-container">
        <div class="container main">
            <h1>Добро пожаловать в FitClub</h1>
            <p>Достигайте своих целей вместе с профессионалами</p>
            <a href="register.php" class="btn btn-primary btn-lg">Начать тренировки</a>
        </div>
    </div>

    <!-- О клубе -->
    <section class="about-section">
        <div class="container">
            <h2 class="about-title">О нашем клубе</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="history-card">
                        <h3 class="section-subtitle">Наша история</h3>
                        <div class="history-text">
                            <p>FitClub был основан в 2020 году группой профессиональных тренеров с целью создания уникального фитнес-пространства, где каждый сможет достичь своих целей в комфортной атмосфере.</p>
                            <p>За это время мы помогли более 1000 клиентам улучшить свою физическую форму и здоровье.</p>
                        </div>
                        <div class="stats-container">
                            <div class="stats-number">1000+</div>
                            <div class="stats-text">довольных клиентов</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="advantages-card">
                        <h3 class="section-subtitle">Наши преимущества</h3>
                        <div class="advantages-list">
                            <div class="advantage-item">
                                <span class="advantage-check">✓</span>
                                <p class="advantage-text">Современное оборудование премиум-класса</p>
                            </div>
                            <div class="advantage-item">
                                <span class="advantage-check">✓</span>
                                <p class="advantage-text">Команда сертифицированных тренеров</p>
                            </div>
                            <div class="advantage-item">
                                <span class="advantage-check">✓</span>
                                <p class="advantage-text">Индивидуальный подход к каждому клиенту</p>
                            </div>
                            <div class="advantage-item">
                                <span class="advantage-check">✓</span>
                                <p class="advantage-text">Просторные залы с кондиционированием</p>
                            </div>
                            <div class="advantage-item">
                                <span class="advantage-check">✓</span>
                                <p class="advantage-text">Удобное расположение и график работы</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Мотивационная цитата -->
    <section class="py-5 quote-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-12 text-center">
                    <p class="quote-text">"Бог дал вам тело, которое может вынести почти всё! Ваша задача — убедить в этом свой разум."</p>
                    <div class="quote-author">
                        <cite>— Винс Ломбарди</cite>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Услуги -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Наши услуги</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card service-card mb-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title">Персональные тренировки</h5>
                            <p class="card-text">Индивидуальный подход и программа тренировок, разработанная специально для вас.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card service-card mb-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title">Групповые занятия</h5>
                            <p class="card-text">Разнообразные групповые программы для всех уровней подготовки.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card service-card mb-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title">Программы питания</h5>
                            <p class="card-text">Профессиональные консультации по питанию и составление индивидуального плана.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Карта -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Как нас найти</h2>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <h4>Наш адрес:</h4>
                    <p>г. Москва, ул. Спортивная, д. 1</p>
                    <h4>Время работы:</h4>
                    <p>Пн-Пт: 7:00 - 23:00<br>
                    Сб-Вс: 9:00 - 22:00</p>
                </div>
                <div class="col-md-6">
                    <div class="map-container" style="height: 300px;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2244.397087990802!2d37.54761771591415!3d55.75973099827405!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46b54a50b315e573%3A0xa886bf5a3d9b2e68!2z0KHQv9C-0YDRgtC40LLQvdCw0Y8g0YPQuy4sIDEsINCc0L7RgdC60LLQsCwg0KDQvtGB0YHQuNGPLCAxMjM0NTY!5e0!3m2!1sru!2sru!4v1635942144815!5m2!1sru!2sru" 
                                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Подвал -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>FitClub</h5>
                    <p>Ваш путь к здоровому образу жизни начинается здесь</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Контакты</h5>
                    <p>+7 (999) 123-45-67</p>
                    <p>info@fitclub.ru</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 