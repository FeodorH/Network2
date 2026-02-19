<?php
header('Content-Type: text/html; charset=UTF-8');

// ===== НАСТРОЙКИ ПОДКЛЮЧЕНИЯ К ВАШЕЙ БД =====
define('DB_HOST', 'localhost');        // обычно localhost
define('DB_NAME', 'u82411');           // ваша база данных
define('DB_USER', 'u82411');            // ваше имя пользователя
define('DB_PASS', '5250734');        // ваш пароль от БД
define('DB_TABLE', 'form_users');       // новая таблица для формы
// ============================================

// Подключение к БД
function getDB() {
    static $db = null;

    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Создаем таблицу, если её нет
            createTableIfNotExists($db);

        } catch (PDOException $e) {
            die("<div style='color:red; padding:20px;'>
                <h3>Ошибка подключения к БД:</h3>
                <p>" . $e->getMessage() . "</p>
                <p>Проверьте:</p>
                <ul>
                    <li>Имя БД: " . DB_NAME . "</li>
                    <li>Пользователь: " . DB_USER . "</li>
                    <li>Пароль: " . (DB_PASS ? '****' : 'не указан') . "</li>
                </ul>
            </div>");
        }
    }
    return $db;
}

// Создание таблицы, если её нет
function createTableIfNotExists($db) {
    $tableName = DB_TABLE;

    // Проверяем существование таблицы
    $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        $sql = "CREATE TABLE $tableName (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fio VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            birthdate DATE NOT NULL,
            gender ENUM('male', 'female') NOT NULL,
            bio TEXT NOT NULL,
            agreement BOOLEAN NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->exec($sql);
    }
}

// Поля формы
$fields = [
    'fio' => 'ФИО',
    'phone' => 'Телефон',
    'email' => 'Email',
    'birthdate' => 'Дата рождения',
    'gender' => 'Пол',
    'bio' => 'Биография',
    'agreement' => 'Согласие'
];

// Регулярные выражения
$patterns = [
    'fio' => '/^[А-Яа-яЁёA-Za-z\s\-]{2,}$/u',
    'phone' => '/^\+?[0-9\s\-\(\)]{10,}$/',
    'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
    'birthdate' => '/^\d{4}-\d{2}-\d{2}$/',
    'gender' => '/^(male|female)$/',
    'bio' => '/^[\s\S]{10,}$/',
    'agreement' => '/^on$/'
];

// Сообщения об ошибках
$error_messages = [
    'fio' => 'ФИО должно содержать минимум 2 символа: буквы, пробелы и дефисы.',
    'phone' => 'Телефон должен содержать минимум 10 цифр. Допустимы: +, пробелы, скобки, дефисы.',
    'email' => 'Введите корректный email.',
    'birthdate' => 'Введите дату в формате ГГГГ-ММ-ДД.',
    'gender' => 'Выберите пол.',
    'bio' => 'Биография должна содержать минимум 10 символов.',
    'agreement' => 'Необходимо согласие на обработку данных.'
];

// Проверка существования email
function emailExists($email, $pdo) {
    $table = DB_TABLE;
    $stmt = $pdo->prepare("SELECT id FROM $table WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

// Сохранение пользователя
function saveUser($data, $pdo) {
    $table = DB_TABLE;
    $sql = "INSERT INTO $table (fio, phone, email, birthdate, gender, bio, agreement)
            VALUES (:fio, :phone, :email, :birthdate, :gender, :bio, :agreement)";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':fio' => $data['fio'],
        ':phone' => $data['phone'],
        ':email' => $data['email'],
        ':birthdate' => $data['birthdate'],
        ':gender' => $data['gender'],
        ':bio' => $data['bio'],
        ':agreement' => $data['agreement'] === 'on' ? 1 : 0
    ]);
}

// Основная логика
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();

    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success-message">Спасибо, результаты сохранены.</div>';
    }

    $errors = [];
    $values = [];

    foreach ($fields as $field => $label) {
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

    include('form.php');
}
else {
    // POST обработка
    try {
        $pdo = getDB();
        $errors = false;

        foreach ($fields as $field => $label) {
            $value = $_POST[$field] ?? '';

            if ($field === 'agreement') {
                if (empty($value) || $value !== 'on') {
                    setcookie($field . '_error', '1', time() + 24 * 60 * 60);
                    $errors = true;
                }
            }
            elseif (!preg_match($patterns[$field], $value)) {
                setcookie($field . '_error', '1', time() + 24 * 60 * 60);
                $errors = true;
            }

            setcookie($field . '_value', $value, time() + 30 * 24 * 60 * 60);
        }

        // Проверка уникальности email
        if (!empty($_POST['email']) && emailExists($_POST['email'], $pdo)) {
            setcookie('email_error', '1', time() + 24 * 60 * 60);
            setcookie('email_error_message', 'Этот email уже зарегистрирован.', time() + 24 * 60 * 60);
            $errors = true;
        }

        if ($errors) {
            header('Location: index.php');
            exit();
        }
        else {
            // Удаляем ошибки
            foreach ($fields as $field => $label) {
                setcookie($field . '_error', '', 100000);
            }
            setcookie('email_error_message', '', 100000);

            // Сохраняем в БД
            saveUser($_POST, $pdo);

            // Сохраняем в Cookies на год
            foreach ($fields as $field => $label) {
                setcookie($field . '_value', $_POST[$field], time() + 365 * 24 * 60 * 60);
            }

            setcookie('save', '1');
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        // Ошибка БД
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        setcookie('email_error_message', 'Ошибка БД: ' . $e->getMessage(), time() + 24 * 60 * 60);
        header('Location: index.php');
        exit();
    }
}
?>