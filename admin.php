<?php
require_once 'common.php';
authenticateAdmin();

$pdo = getDB();
$message = '';

// Удаление
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Удаляем связи языков и самого пользователя
    $stmt = $pdo->prepare("DELETE FROM ".DB_TABLE_USER_LANGS." WHERE user_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM ".DB_TABLE_USERS." WHERE id = ?");
    $stmt->execute([$id]);
    $message = '<div class="success-message">Пользователь удалён.</div>';
}

// Редактирование (обработка POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    // Простейшая валидация для админа (можно использовать общую функцию, но для простоты оставим так)
    $sql = "UPDATE ".DB_TABLE_USERS." SET fio=?, phone=?, email=?, birthdate=?, gender=?, bio=?, agreement=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['birthdate'],
        $_POST['gender'], $_POST['bio'], isset($_POST['agreement']) ? 1 : 0, $id
    ]);
    setUserLanguages($id, $_POST['languages'] ?? [], $pdo);
    $message = '<div class="success-message">Данные обновлены.</div>';
}

$users = $pdo->query("SELECT * FROM ".DB_TABLE_USERS." ORDER BY id DESC")->fetchAll();
$allLanguages = getAllLanguages($pdo);
$stats = getLanguageStats($pdo);
?>
<!DOCTYPE html>
<html>
<head><title>Админ-панель</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <h1>Управление пользователями</h1>
    <?= $message ?>
    <h2>Статистика по языкам</h2>
    <ul>
    <?php foreach ($stats as $s): ?>
        <li><?= htmlspecialchars($s['name']) ?>: <?= $s['cnt'] ?> чел.</li>
    <?php endforeach; ?>
    </ul>
    <h2>Список пользователей</h2>
    <table border="1">
        <tr><th>ID</th><th>ФИО</th><th>Email</th><th>Действия</th></tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['fio']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <a href="?edit=<?= $user['id'] ?>">Редактировать</a> |
                <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Удалить?')">Удалить</a>
            </td>
        </tr>
        <?php if (isset($_GET['edit']) && $_GET['edit'] == $user['id']): ?>
        <tr>
            <td colspan="4">
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                    <input type="text" name="fio" value="<?= htmlspecialchars($user['fio']) ?>" required><br>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>"><br>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>
                    <input type="date" name="birthdate" value="<?= $user['birthdate'] ?>"><br>
                    <select name="gender">
                        <option value="male" <?= $user['gender']=='male'?'selected':'' ?>>Мужской</option>
                        <option value="female" <?= $user['gender']=='female'?'selected':'' ?>>Женский</option>
                    </select><br>
                    <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea><br>
                    <?php $userLangs = getUserLanguages($user['id'], $pdo); ?>
                    <?php foreach ($allLanguages as $lang): ?>
                        <label>
                            <input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
                                <?= in_array($lang['id'], $userLangs) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($lang['name']) ?>
                        </label>
                    <?php endforeach; ?><br>
                    <label><input type="checkbox" name="agreement" <?= $user['agreement'] ? 'checked' : '' ?>> Согласие</label><br>
                    <button type="submit" name="edit_user">Сохранить</button>
                </form>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">На главную</a></p>
</div>
</body>
</html>