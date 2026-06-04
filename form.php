<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма регистрации / Редактирование</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1><?= $isAuthorized ? 'Редактирование профиля' : 'Регистрация' ?></h1>

    <!-- Блок сообщений (успех, ошибки, сгенерированные creds) -->
    <?php if (!empty($messages)): ?>
        <div class="messages">
            <?php foreach ($messages as $msg): ?>
                <?= $msg ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Форма входа для неавторизованных пользователей -->
    <?php if (!$isAuthorized): ?>
        <div class="login-box" style="background:#f9f9f9; padding:15px; margin-bottom:20px; border-radius:8px;">
            <h3>Вход для редактирования ранее сохранённых данных</h3>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Логин:</label>
                    <input type="text" name="login" required>
                </div>
                <div class="form-group">
                    <label>Пароль:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Войти</button>
            </form>
        </div>
    <?php else: ?>
        <div class="logout-box" style="background:#e0f7fa; padding:15px; margin-bottom:20px; border-radius:8px;">
            <p>Вы вошли как <strong><?= htmlspecialchars($_SESSION['login']) ?></strong></p>
            <a href="?logout=1" class="button" style="background:#f44336;">Выйти</a>
        </div>
    <?php endif; ?>

    <!-- Основная форма (регистрация / редактирование) -->
    <form action="" method="POST">
        <?php foreach (['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'agreement'] as $field): ?>
            <div class="form-group">
                <?php if ($field == 'fio'): ?>
                    <label for="fio">ФИО:</label>
                    <input type="text" id="fio" name="fio" class="<?= $errors['fio'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['fio'] ?? '') ?>" placeholder="Иванов Иван Иванович">
                <?php elseif ($field == 'phone'): ?>
                    <label for="phone">Телефон:</label>
                    <input type="tel" id="phone" name="phone" class="<?= $errors['phone'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['phone'] ?? '') ?>" placeholder="+7 (999) 123-45-67">
                <?php elseif ($field == 'email'): ?>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="<?= $errors['email'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['email'] ?? '') ?>" placeholder="example@domain.com">
                <?php elseif ($field == 'birthdate'): ?>
                    <label for="birthdate">Дата рождения:</label>
                    <input type="date" id="birthdate" name="birthdate" class="<?= $errors['birthdate'] ? 'error' : '' ?>" value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>">
                <?php elseif ($field == 'gender'): ?>
                    <label>Пол:</label>
                    <div class="radio-group">
                        <label><input type="radio" name="gender" value="male" <?= ($values['gender'] ?? '') == 'male' ? 'checked' : '' ?>> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?= ($values['gender'] ?? '') == 'female' ? 'checked' : '' ?>> Женский</label>
                    </div>
                <?php elseif ($field == 'bio'): ?>
                    <label for="bio">Биография:</label>
                    <textarea id="bio" name="bio" rows="5" class="<?= $errors['bio'] ? 'error' : '' ?>" placeholder="Расскажите о себе..."><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
                <div class="form-group">
                    <label>Языки программирования (выберите хотя бы один):</label><br>
                    <?php
                    $allLanguages = getAllLanguages(getDB());
                    $selectedLangs = $values['languages'] ?? [];
                    ?>
                    <?php foreach ($allLanguages as $lang): ?>
                        <label style="display: inline-block; margin-right: 15px;">
                            <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
                                <?= in_array($lang['id'], $selectedLangs) ? 'checked' : '' ?>
                                <?= (!empty($errors['languages'])) ? 'class="error"' : '' ?>
                            > <?= htmlspecialchars($lang['name']) ?>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!empty($errors['languages'])): ?>
                        <span class="error-message"><?= $error_messages['languages'] ?></span>
                    <?php endif; ?>
                </div>
                <?php elseif ($field == 'agreement'): ?>
                    <div class="checkbox-group">
                        <input type="checkbox" id="agreement" name="agreement" <?= ($values['agreement'] ?? '') == 'on' ? 'checked' : '' ?>>
                        <label for="agreement">Я согласен на обработку персональных данных</label>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button type="submit"><?= $isAuthorized ? 'Сохранить изменения' : 'Зарегистрироваться' ?></button>
    </form>
    <a href="index.html" class="admin-link">← На главную</a>
</div>
</body>
</html>