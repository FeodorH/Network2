<?php
// common.php – общие функции для заданий 5 и 6

define('DB_HOST', 'localhost');
define('DB_NAME', 'u82411');
define('DB_USER', 'u82411');
define('DB_PASS', '5250734');   // ваш пароль
define('DB_TABLE_USERS', 'form_users');
define('DB_TABLE_LANGUAGES', 'languages');
define('DB_TABLE_USER_LANGS', 'user_languages');
define('DB_TABLE_ADMIN', 'admin');

// Поля формы и правила валидации
$fields = ['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'agreement', 'languages'];
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
    'agreement' => 'Необходимо согласие на обработку данных.',
    'languages' => 'Выберите хотя бы один язык программирования.'
];

function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Ошибка БД: " . $e->getMessage());
        }
    }
    return $db;
}

// Работа с языками
function getAllLanguages($pdo) {
    return $pdo->query("SELECT * FROM ".DB_TABLE_LANGUAGES." ORDER BY id")->fetchAll();
}

function getUserLanguages($userId, $pdo) {
    $stmt = $pdo->prepare("SELECT language_id FROM ".DB_TABLE_USER_LANGS." WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function setUserLanguages($userId, $languageIds, $pdo) {
    $stmt = $pdo->prepare("DELETE FROM ".DB_TABLE_USER_LANGS." WHERE user_id = ?");
    $stmt->execute([$userId]);
    if (!empty($languageIds)) {
        $stmt = $pdo->prepare("INSERT INTO ".DB_TABLE_USER_LANGS." (user_id, language_id) VALUES (?, ?)");
        foreach ($languageIds as $langId) {
            $stmt->execute([$userId, $langId]);
        }
    }
}

// Статистика для админа
function getLanguageStats($pdo) {
    $sql = "SELECT l.name, COUNT(ul.user_id) as cnt
            FROM ".DB_TABLE_LANGUAGES." l
            LEFT JOIN ".DB_TABLE_USER_LANGS." ul ON l.id = ul.language_id
            GROUP BY l.id ORDER BY cnt DESC";
    return $pdo->query($sql)->fetchAll();
}

// Админская HTTP-авторизация
function authenticateAdmin() {
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Авторизация необходима';
        exit;
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT password_hash FROM ".DB_TABLE_ADMIN." WHERE login = ?");
        $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
            header('WWW-Authenticate: Basic realm="Admin Panel"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Неверный логин или пароль';
            exit;
        }
    }
}

// Алиас для таблицы пользователей (чтобы в index.php работало DB_TABLE)
define('DB_TABLE', DB_TABLE_USERS);

// Генерация уникального логина
function generateUniqueLogin($pdo) {
    do {
        $login = 'user_' . bin2hex(random_bytes(5));
        $stmt = $pdo->prepare("SELECT id FROM ".DB_TABLE." WHERE login = ?");
        $stmt->execute([$login]);
    } while ($stmt->fetch());
    return $login;
}

// Генерация пароля
function generatePassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, 8);
}

// Аутентификация
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

// Обновление пользователя
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

// Создание нового пользователя
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
?>