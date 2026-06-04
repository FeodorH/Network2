<?php
session_start(); // Стартуем сессию для авторизации
header('Content-Type: text/html; charset=UTF-8');

// ===== НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К БД =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'u82411');
define('DB_USER', 'u82411');
define('DB_PASS', '5250734');   // замените на реальный
define('DB_TABLE', 'form_users');
// =====================================

// Подключение к БД и создание таблицы при необходимости (как в предыдущей версии)
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Автоматически добавляем колонки, если их нет (для старых таблиц)
            //$db->exec("ALTER TABLE ".DB_TABLE." ADD COLUMN IF NOT EXISTS login VARCHAR(50) UNIQUE, ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255)");
        } catch (PDOException $e) {
            die("Ошибка БД: " . $e->getMessage());
        }
    }
    return $db;
}

// Генерация случайного логина (не занятого)
function generateUniqueLogin($pdo) {
    do {
        $login = 'user_' . bin2hex(random_bytes(5)); // 10 символов
        $stmt = $pdo->prepare("SELECT id FROM ".DB_TABLE." WHERE login = ?");
        $stmt->execute([$login]);
    } while ($stmt->fetch());
    return $login;
}

// Генерация случайного пароля (8 символов)
function generatePassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, 8);
}

// Проверка логина/пароля (возвращает ID пользователя или false)
function authenticate($login, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM ".DB_TABLE." WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user['id'];
    }
    return false;
}

// Получение данных пользователя по ID
function getUserById($id, $pdo) {
    $stmt = $pdo->prepare("SELECT fio, phone, email, birthdate, gender, bio, agreement FROM ".DB_TABLE." WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Обновление данных пользователя
function updateUser($id, $data, $pdo) {
    $sql = "UPDATE ".DB_TABLE." SET fio=:fio, phone=:phone, email=:email, birthdate=:birthdate, gender=:gender, bio=:bio, agreement=:agreement WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':fio' => $data['fio'],
        ':phone' => $data['phone'],
        ':email' => $data['email'],
        ':birthdate' => $data['birthdate'],
        ':gender' => $data['gender'],
        ':bio' => $data['bio'],
        ':agreement' => $data['agreement'] === 'on' ? 1 : 0,
        ':id' => $id
    ]);
}

// Создание нового пользователя (возвращает id и сгенерированные логин/пароль)
function createUser($data, $pdo, &$login, &$plainPassword) {
    $login = generateUniqueLogin($pdo);
    $plainPassword = generatePassword();
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $sql = "INSERT INTO ".DB_TABLE." (fio, phone, email, birthdate, gender, bio, agreement, login, password_hash)
            VALUES (:fio, :phone, :email, :birthdate, :gender, :bio, :agreement, :login, :hash)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fio' => $data['fio'],
        ':phone' => $data['phone'],
        ':email' => $data['email'],
        ':birthdate' => $data['birthdate'],
        ':gender' => $data['gender'],
        ':bio' => $data['bio'],
        ':agreement' => $data['agreement'] === 'on' ? 1 : 0,
        ':login' => $login,
        ':hash' => $passwordHash
    ]);
    return $pdo->lastInsertId();
}

// ------ ВАЛИДАЦИЯ ПОЛЕЙ (общая для всех) ------
$fields = ['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'agreement'];
$patterns = [
    'fio' => '/^[А-Яа-яЁёA-Za-z\s\-]{2,}$/u',
    'phone' => '/^\+?[0-9\s\-\(\)]{10,}$/',
    'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
    'birthdate' => '/^\d{4}-\d{2}-\d{2}$/',
    'gender' => '/^(male|female)$/',
    'bio' => '/^[\s\S]{10,}$/',
    'agreement' => '/^on$/'
];
$error_messages = [
    'fio' => 'ФИО должно содержать минимум 2 символа: буквы, пробелы и дефисы.',
    'phone' => 'Телефон должен содержать минимум 10 цифр. Допустимы: +, пробелы, скобки, дефисы.',
    'email' => 'Введите корректный email.',
    'birthdate' => 'Введите дату в формате ГГГГ-ММ-ДД.',
    'gender' => 'Выберите пол.',
    'bio' => 'Биография должна содержать минимум 10 символов.',
    'agreement' => 'Необходимо согласие на обработку данных.'
];

// ------ ОБРАБОТКА ВХОДА (POST login) ------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $pdo = getDB();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $userId = authenticate($login, $password, $pdo);
    if ($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['login'] = $login;
        // Перенаправляем на форму с авторизацией
        header('Location: index.php');
        exit();
    } else {
        setcookie('auth_error', 'Неверный логин или пароль', time() + 60); // на 1 минуту
        header('Location: index.php');
        exit();
    }
}

// ------ ОБРАБОТКА ВЫХОДА ------
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// ------ ОСНОВНАЯ ЛОГИКА (GET / POST form) ------
$pdo = getDB();
$isAuthorized = isset($_SESSION['user_id']);
$userId = $isAuthorized ? $_SESSION['user_id'] : null;

// --- GET: показ формы ---
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = [];
    $errors = [];
    $values = [];

    // Сообщение об успешном сохранении (из куки)
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success-message">Спасибо, результаты сохранены.</div>';
    }
    // Сообщение об ошибке авторизации
    if (!empty($_COOKIE['auth_error'])) {
        $messages[] = '<div class="error">' . htmlspecialchars($_COOKIE['auth_error']) . '</div>';
        setcookie('auth_error', '', 100000);
    }
    // Сообщение о сгенерированных логине/пароле (если есть)
    if (!empty($_COOKIE['generated_creds'])) {
        $messages[] = '<div class="success-message">' . htmlspecialchars($_COOKIE['generated_creds']) . '</div>';
        setcookie('generated_creds', '', 100000);
    }

    if ($isAuthorized) {
        // Авторизованный: берём данные из БД
        $userData = getUserById($userId, $pdo);
        if ($userData) {
            foreach ($fields as $field) {
                $values[$field] = $userData[$field] ?? '';
                // для чекбокса agreement приводим к 'on' если 1
                if ($field == 'agreement') {
                    $values[$field] = ($userData['agreement'] == 1) ? 'on' : '';
                }
            }
        }
        // У авторизованного нет ошибок (при GET)
        foreach ($fields as $field) $errors[$field] = false;
    } else {
        // Неавторизованный: читаем cookies ошибок и значений (как в задании 4)
        foreach ($fields as $field) {
            $error_flag = !empty($_COOKIE[$field . '_error']);
            $errors[$field] = $error_flag;
            if ($error_flag) {
                setcookie($field . '_error', '', 100000);
                setcookie($field . '_value', '', 100000);
                if (isset($error_messages[$field])) {
                    $messages[] = '<div class="error">' . $error_messages[$field] . '</div>';
                }
            }
            $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
        }
    }

    include('form.php');
    exit();
}

// --- POST: обработка отправки формы (сохранение/обновление) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $errors = false;
    $input = [];
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? '';
        $input[$field] = $value;
        // Валидация
        if ($field === 'agreement') {
            if (empty($value) || $value !== 'on') {
                setcookie($field . '_error', '1', time() + 24 * 60 * 60);
                $errors = true;
            }
        } elseif (!preg_match($patterns[$field], $value)) {
            setcookie($field . '_error', '1', time() + 24 * 60 * 60);
            $errors = true;
        }
        // Сохраняем в куки (независимо от авторизации, для неавторизованных)
        setcookie($field . '_value', $value, time() + 30 * 24 * 60 * 60);
    }

    // Доп. проверка уникальности email (только для новых пользователей)
    if (!$isAuthorized && !empty($input['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM ".DB_TABLE." WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) {
            setcookie('email_error', '1', time() + 24 * 60 * 60);
            setcookie('email_error_message', 'Этот email уже зарегистрирован.', time() + 24 * 60 * 60);
            $errors = true;
        }
    }

    if ($errors) {
        header('Location: index.php');
        exit();
    }

    // --- Сохранение / обновление ---
    if ($isAuthorized) {
        // Обновляем существующую запись
        updateUser($userId, $input, $pdo);
        // Удаляем cookies ошибок
        foreach ($fields as $field) {
            setcookie($field . '_error', '', 100000);
        }
        setcookie('save', '1');
        header('Location: index.php');
    } else {
        // Новый пользователь: создаём запись, генерируем логин/пароль
        $plainPassword = '';
        $login = '';
        $newId = createUser($input, $pdo, $login, $plainPassword);
        // Показываем логин/пароль один раз через куку
        $credsMsg = "Ваш логин: $login, пароль: $plainPassword. Сохраните их для редактирования данных.";
        setcookie('generated_creds', $credsMsg, time() + 60); // на 1 минуту
        // Очищаем cookies ошибок
        foreach ($fields as $field) {
            setcookie($field . '_error', '', 100000);
        }
        setcookie('save', '1');
        header('Location: index.php');
    }
    exit();
}
