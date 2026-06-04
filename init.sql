-- Создаём или используем существующую БД
USE u82411;  -- замените на вашу БД

-- Таблица form_users (если ещё не создана, добавим поля login и password_hash)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Если таблица уже существует, но нет колонок login/password_hash, выполняем ALTER (однократно)
-- ALTER TABLE form_users ADD COLUMN login VARCHAR(50) UNIQUE, ADD COLUMN password_hash VARCHAR(255);