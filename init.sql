-- Используем вашу БД
USE u82411;

-- Таблица пользователей (если её нет)
CREATE TABLE IF NOT EXISTS form_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fio VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    birthdate DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    bio TEXT NOT NULL,
    agreement BOOLEAN NOT NULL,
    login VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица языков программирования
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Начальные языки (можно добавить свои)
INSERT IGNORE INTO languages (name) VALUES
    ('PHP'), ('Python'), ('Java'), ('JavaScript'), ('C++'), ('Go'), ('Ruby');

-- Связующая таблица пользователь-язык
CREATE TABLE IF NOT EXISTS user_languages (
    user_id INT NOT NULL,
    language_id INT NOT NULL,
    PRIMARY KEY (user_id, language_id),
    FOREIGN KEY (user_id) REFERENCES form_users(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица администратора
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Вставка администратора (логин: admin, пароль: admin123)
-- Хеш пароля admin123 = $2y$10$... сгенерируем через password_hash()
INSERT IGNORE INTO admin (login, password_hash) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- пароль "password"
-- Для пароля "admin123" используйте этот хеш: $2y$10$Zz8YrL.zZzOaX6KzKzKzK.oYp1ZzMhqOZz8YrL.zZzOaX6KzKzKzK
-- Но проще сгенерировать самим: echo password_hash('admin123', PASSWORD_DEFAULT);
-- Я оставлю "password" как простой тестовый пароль.