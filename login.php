<?php
$config = include('db_config.php');
header('Content-Type: text/html; charset=UTF-8');

// Подключение к базе данных
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

// Обработка выхода
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: login.php');
    exit();
}

// Проверяем, есть ли уже активная сессия
if (!empty($_COOKIE[session_name()])) {
    session_start();
    if (!empty($_SESSION['login'])) {
        // Если пользователь уже авторизован, перенаправляем на главную
        header('Location: ./');
        exit();
    }
}

// GET запрос - показываем форму входа
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        form { max-width: 300px; margin: 0 auto; }
        input { width: 100%; padding: 8px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .error { color: red; margin: 10px 0; text-align: center; }
    </style>
</head>
<body>
    <form action="" method="post">
        <h2>Вход в систему</h2>
        <input name="login" placeholder="Логин" required />
        <input name="pass" type="password" placeholder="Пароль" required />
        <button type="submit">Войти</button>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Неверный логин или пароль</div>
        <?php endif; ?>
    </form>
    <div style="text-align: center; margin-top: 15px;">
    <a href="index.php" style="
        display: inline-block;
        padding: 8px 16px;
        background-color: #f0f0f0;
        color: #333;
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
    ">
        📝 Нет аккаунта? Зарегистрироваться
    </a>
</div>
</body>
</html>
<?php
} 
// POST запрос - обрабатываем вход
else {
    $login = trim($_POST['login']);
    $pass = trim($_POST['pass']);
    
    try {
        // Ищем пользователя по логину
        $stmt = $db->prepare("
            SELECT login, pass, request_id 
            FROM UserInfo 
            WHERE login = ?
        ");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Проверяем пароль (в базе хранится md5 хеш)
        if ($user && $user['pass'] === md5($pass)) {
            // Запускаем сессию
            session_start();
            
            // Сохраняем данные в сессию
            $_SESSION['login'] = $user['login'];
            $_SESSION['request_id'] = $user['request_id'];
            
            // Перенаправляем на главную страницу с формой
            header('Location: ./');
            exit();
        } else {
            // Неверный логин или пароль
            header('Location: login.php?error=1');
            exit();
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        header('Location: login.php?error=1');
        exit();
    }
}
?>