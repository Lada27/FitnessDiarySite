<?php
$host = 'localhost';
$username = 'root';
$password = ''; //
$database = 'fitness_diary'; 

// Создание соединения
$conn = new mysqli($host, $username, $password, $database);

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Установка кодировки
$conn->set_charset("utf8mb4"); 