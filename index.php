<?php
session_start(); // Стартуем сессию для авторизации
require_once 'common.php';
header('Content-Type: text/html; charset=UTF-8');

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
        $values['languages'] = getUserLanguages($userId, $pdo);
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
        $values['languages'] = [];
        $errors['languages'] = !empty($_COOKIE['languages_error']);
        if ($errors['languages']) {
            setcookie('languages_error', '', 100000);
            $messages[] = '<div class="error">' . ($error_messages['languages'] ?? 'Выберите язык') . '</div>';
        }
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
    $languages_ok = isset($_POST['languages']) && is_array($_POST['languages']) && count($_POST['languages']) > 0;
    if (!$languages_ok) {
        setcookie('languages_error', '1', time() + 24 * 3600);
        $errors = true;
    }
    foreach ($fields as $field) {
        if ($field === 'languages') continue;
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
        setUserLanguages($userId, $_POST['languages'] ?? [], $pdo);
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
        setUserLanguages($newId, $_POST['languages'] ?? [], $pdo);
        header('Location: index.php');
    }
    exit();
}
