<?php
/**
 * Задача 6. Административная панель с HTTP-авторизацией
 */

$config = include('db_config.php');


$auth_success = false;

// Если переданы логин и пароль
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    try {
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
            $config['user'],
            $config['pass']
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Проверяем администратора в отдельной таблице
        $stmt = $db->prepare("SELECT login, pass FROM Admin WHERE login = ?");
        $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && $admin['pass'] === md5($_SERVER['PHP_AUTH_PW'])) {
            $auth_success = true;
        }
    } catch (PDOException $e) {
        error_log('Admin auth error: ' . $e->getMessage());
    }
}

// Если авторизация не прошла - запрашиваем её
if (!$auth_success) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - Lab5"');
    print('<h1>401 Требуется авторизация</h1>');
    print('<p>Доступ разрешен только администраторам</p>');
    exit();
}

try {
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['user'],
        $config['pass']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

// Обработка удаления записи
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $request_id = (int)$_GET['delete'];
    
    try {
        $db->beginTransaction();
        
        // Удаляем связи с языками
        $stmt = $db->prepare("DELETE FROM Connect WHERE request_id = ?");
        $stmt->execute([$request_id]);
        
        // Удаляем информацию о пользователе
        $stmt = $db->prepare("DELETE FROM UserInfo WHERE request_id = ?");
        $stmt->execute([$request_id]);
        
        // Удаляем саму заявку
        $stmt = $db->prepare("DELETE FROM Frequest WHERE id = ?");
        $stmt->execute([$request_id]);
        
        $db->commit();
        
        // Перенаправляем для избежания повторной отправки
        header('Location: admin.php?msg=deleted');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Ошибка удаления: ' . $e->getMessage();
    }
}

// Обработка редактирования записи
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $errors = false;
    
    if (empty($_POST['FIO']) || strlen($_POST['FIO']) > 150 || !preg_match('/^[a-zA-Zа-яёА-ЯЁ\s\-]+$/u', $_POST['FIO'])) {
        $errors = true;
        $edit_error = 'Неверный формат ФИО';
    }
    
    if (empty($_POST['telep']) || !preg_match('/^[\+\d\s\-\(\)]{6,20}$/', $_POST['telep'])) {
        $errors = true;
        $edit_error = 'Неверный формат телефона';
    }
    
    if (empty($_POST['mail']) || !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
        $errors = true;
        $edit_error = 'Неверный формат Email';
    }
    
    if (empty($_POST['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'])) {
        $errors = true;
        $edit_error = 'Неверный формат даты';
    }
    
    if (empty($_POST['sex']) || !in_array($_POST['sex'], array('Male', 'Female'))) {
        $errors = true;
        $edit_error = 'Неверный формат пола';
    }
    
    $allowed_languages = array('PHP', 'Python', 'Java', 'JavaScript', 'C++', 'Go');
    if (empty($_POST['language'])) {
        $errors = true;
        $edit_error = 'Выберите хотя бы один язык'


;
    } else {
        foreach ($_POST['language'] as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                $errors = true;
                $edit_error = 'Выбран недопустимый язык';
                break;
            }
        }
    }
    
    if (empty($_POST['bio']) || strlen($_POST['bio']) > 1000) {
        $errors = true;
        $edit_error = 'Биография обязательна и не должна превышать 1000 символов';
    }
    
    if (empty($_POST['agreement']) || $_POST['agreement'] !== 'on') {
        $errors = true;
        $edit_error = 'Необходимо подтвердить согласие';
    }
    
    if (!$errors) {
        try {
            $db->beginTransaction();
            
            // Обновляем данные в Frequest
            $stmt = $db->prepare("
                UPDATE Frequest 
                SET name = ?, tel = ?, email = ?, dateborn = ?, sex = ?, bio = ?, agree = ?
                WHERE id = ?
            ");
            $agree = ($_POST['agreement'] == 'on') ? 1 : 0;
            $stmt->execute([
                $_POST['FIO'],
                $_POST['telep'],
                $_POST['mail'],
                $_POST['date'],
                $_POST['sex'],
                $_POST['bio'],
                $agree,
                $edit_id
            ]);
            
            // Удаляем старые связи с языками
            $stmt = $db->prepare("DELETE FROM Connect WHERE request_id = ?");
            $stmt->execute([$edit_id]);
            
            // Добавляем новые связи
            $stmt = $db->prepare("
                INSERT INTO Connect (request_id, language_id) 
                VALUES (?, (SELECT language_id FROM LANGUAGES WHERE language_name = ?))
            ");
            foreach ($_POST['language'] as $lang) {
                $stmt->execute([$edit_id, $lang]);
            }
            
            $db->commit();
            
            header('Location: admin.php?msg=updated');
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $edit_error = 'Ошибка обновления: ' . $e->getMessage();
        }
    }
}


$stmt = $db->prepare("
    SELECT 
        r.id,
        r.name,
        r.tel,
        r.email,
        r.dateborn,
        r.sex,
        r.bio,
        r.agree,
    GROUP_CONCAT(l.language_name SEPARATOR ', ') as languages
    FROM Frequest r
    LEFT JOIN Connect c ON r.id = c.request_id
    LEFT JOIN LANGUAGES l ON c.language_id = l.language_id
    GROUP BY r.id
    ORDER BY r.id DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $db->prepare("
    SELECT l.language_name, COUNT(c.request_id) as user_count FROM LANGUAGES l
    LEFT JOIN Connect c ON l.language_id = c.language_id
    GROUP BY l.language_id
    ORDER BY user_count DESC
");
$stmt->execute();
$language_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);

// Получаем данные для редактирования (если выбран пользователь)
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.name,
            r.tel,
            r.email,
            r.dateborn,
            r.sex,
            r.bio,
            r.agree,
            GROUP_CONCAT(l.language_name SEPARATOR '|') as languages
        FROM Frequest r
        LEFT JOIN Connect c ON r.id = c.request_id
        LEFT JOIN LANGUAGES l ON c.language_id = l.language_id
        WHERE r.id = ?
        GROUP BY r.id
    ");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_user && $edit_user['languages']) {
        $edit_user['languages_array'] = explode('|', $edit_user['languages']);
    }
}

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $message = 'Запись успешно удалена';
    if ($_GET['msg'] == 'updated') $message = 'Запись успешно обновлена';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель - Управление пользователями</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .stats-box {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .stats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .stat-card {
            background: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card strong {
            font-size: 24px;
            color: #4CAF50;
            display: block;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow-x: auto;
            display: block;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #4CAF50;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            transition: opacity 0.3s;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .btn-edit {
            background: #2196F3;
            color: white;
        }
        .btn-delete {
            background: #f44336;
            color: white;
        }
        .btn-save {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            font-size: 14px;
        }
        .btn-cancel {
            background: #999;
            color: white;
        }
        .edit-form {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .edit-form h3 {
            margin-top: 0;
        }
        .edit-form .form-group {
            margin-bottom: 15px;
        }
        .edit-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .edit-form input, .edit-form select, .edit-form textarea {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .edit-form select[multiple] {
            max-width: 300px;
            height: 120px;
        }
        .error {
            color: red;
            margin: 10px 0;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
        }
        .badge {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            display: inline-block;
            margin: 2px;
        }
        .agree-yes {
            color: green;
            font-weight: bold;
        }
        .agree-no {
            color: red;
        }
        @media (max-width: 768px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👑 Административная панель</h1>
        <p>Вы авторизованы как <strong><?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></strong></p>
        
        <?php if ($message): ?>
            <div class="message">✅ <?= $message ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>
        
        
        <div class="stats-box">
            <h2>📊 Статистика</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <strong><?= $total_users ?></strong>
                    <span>Всего пользователей</span>
                </div>
            </div>
            
            <h3>Языки программирования:</h3>
            <div class="stats-grid">
                <?php foreach ($language_stats as $stat): ?>
                    <div class="stat-card">
                        <strong><?= $stat['user_count'] ?></strong>
                        <span><?= htmlspecialchars($stat['language_name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        
        <?php if ($edit_user): ?>
            <div class="edit-form">
                <h3>Редактирование записи #<?= $edit_user['id'] ?></h3>
                <?php if (isset($edit_error)): ?>
                    <div class="error">❌ <?= $edit_error ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="edit_id" value="<?= $edit_user['id'] ?>">
                    
                    <div class="form-group">
                        <label>ФИО:</label>
                        <input type="text" name="FIO" value="<?= htmlspecialchars($edit_user['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон:</label>
                        <input type="text" name="telep" value="<?= htmlspecialchars($edit_user['tel']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="mail" value="<?= htmlspecialchars($edit_user['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Дата рождения:</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($edit_user['dateborn']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Пол:</label>
                        <select name="sex">
                            <option value="Male" <?= $edit_user['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $edit_user['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="form-group">


                        <label>Языки программирования:</label>
                        <select name="language[]" multiple>
                            <option value="PHP" <?= in_array('PHP', (array)($edit_user['languages_array'] ?? [])) ? 'selected' : '' ?>>PHP</option>
                            <option value="Python" <?= in_array('Python', (array)($edit_user['languages_array'] ?? [])) ? 'selected' : '' ?>>Python</option>
                            <option value="Java" <?= in_array('Java', (array)($edit_user['languages_array'] ?? [])) ? 'selected' : '' ?>>Java</option>
                            <option value="JavaScript" <?= in_array('JavaScript', (array)($edit_user['languages_array'] ?? [])) ? 'selected' : '' ?>>JavaScript</option>
                            <option value="C++" <?= in_array('C++', (array)($edit_user['languages_array'] ?? [])) ? 'selected' : '' ?>>C++</option>
                            <option value="Go" <?= in_array('Go', (array)($edit_user['languages_array'] ?? [])) ? 'selected' : '' ?>>Go</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Биография:</label>
                        <textarea name="bio" rows="4" style="width:100%; max-width:500px;"><?= htmlspecialchars($edit_user['bio']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="agreement" <?= $edit_user['agree'] ? 'checked' : '' ?>>
                            Согласие с контрактом
                        </label>
                    </div>
                    
                    <div class="actions">
                        <button type="submit" class="btn btn-save">Сохранить</button>
                        <a href="admin.php" class="btn btn-cancel">Отмена</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
       
        <h2>📋 Все пользователи</h2>
        
        <?php if (empty($users)): ?>
            <p>Нет зарегистрированных пользователей.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки</th>
                        <th>Биография</th>
                        <th>Согласие</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['tel']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['dateborn']) ?></td>
                            <td><?= $user['sex'] == 'Male' ? '👨 Мужской' : '👩 Женский' ?></td>
                            <td>
                                <?php 
                                $langs = explode(', ', $user['languages'] ?? '');
                                foreach ($langs as $lang):
                                    if ($lang):
                                ?>
                                    <span class="badge"><?= htmlspecialchars($lang) ?></span>
                                <?php 
                                    endif;
                                endforeach;
                                ?>
                            </td>
                            <td style="max-width: 250px;"><?= htmlspecialchars(mb_substr($user['bio'] ?? '', 0, 100)) ?>...</td>
                            <td class="<?= $user['agree'] ? 'agree-yes' : 'agree-no' ?>">
                                <?= $user['agree'] ? '✅ Да' : '❌ Нет' ?>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?= $user['id'] ?>" class="btn btn-edit">✏️ Редактировать</a>
                                <a href="?delete=<?= $user['id'] ?>" class="btn btn-delete" 
                                   onclick="return confirm('Вы уверены, что хотите удалить пользователя «<?= htmlspecialchars($user['name']) ?>»?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <p style="margin-top: 30px; text-align: center;">
            <a href="index.php" style="color: #4CAF50;">← Вернуться на главную</a>
        </p>
    </div>
</body>
</html>