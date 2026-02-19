<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма регистрации</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>

        <?php if (!empty($messages)): ?>
            <div class="messages">
                <?php foreach ($messages as $message): ?>
                    <?= $message ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="fio">ФИО:</label>
                <input type="text"
                       id="fio"
                       name="fio"
                       class="<?= $errors['fio'] ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($values['fio'] ?? '') ?>"
                       placeholder="Иванов Иван Иванович">
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel"
                       id="phone"
                       name="phone"
                       class="<?= $errors['phone'] ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                       placeholder="+7 (999) 123-45-67">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email"
                       id="email"
                       name="email"
                       class="<?= $errors['email'] ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                       placeholder="example@domain.com">
            </div>

            <div class="form-group">
                <label for="birthdate">Дата рождения:</label>
                <input type="date"
                       id="birthdate"
                       name="birthdate"
                       class="<?= $errors['birthdate'] ? 'error' : '' ?>"
                       value="<?= htmlspecialchars($values['birthdate'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Пол:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio"
                               name="gender"
                               value="male"
                               <?= ($values['gender'] ?? '') == 'male' ? 'checked' : '' ?>>
                        Мужской
                    </label>
                    <label>
                        <input type="radio"
                               name="gender"
                               value="female"
                               <?= ($values['gender'] ?? '') == 'female' ? 'checked' : '' ?>>
                        Женский
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="bio">Биография:</label>
                <textarea id="bio"
                          name="bio"
                          class="<?= $errors['bio'] ? 'error' : '' ?>"
                          rows="5"
                          placeholder="Расскажите о себе..."><?= htmlspecialchars($values['bio'] ?? '') ?></textarea>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox"
                       id="agreement"
                       name="agreement"
                       <?= ($values['agreement'] ?? '') == 'on' ? 'checked' : '' ?>>
                <label for="agreement">Я согласен на обработку персональных данных</label>
            </div>

            <input type="submit" value="Отправить">
        </form>

        <a href="index.html" class="admin-link">← На главную</a>
    </div>
</body>
</html>