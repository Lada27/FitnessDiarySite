<?php
$host = 'localhost';
$username = 'root';
$password = 'root'; // Измените на ваш пароль от MAMP
$database = 'fitness_diary'; // Изменено с fitness_db на fitness_diary

// Создание соединения
$conn = new mysqli($host, $username, $password, $database);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Установка кодировки
$conn->set_charset("utf8mb4"); 