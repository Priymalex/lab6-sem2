<?php
$config = include('db_config.php');

header('Content-Type: text/html; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $messages = array();

// Проверяем, авторизован ли пользователь
$isAuthorized = false;
if (!empty($_COOKIE[session_name()])) {
    session_start();
    if (!empty($_SESSION['login'])) {
        $isAuthorized = true;
        $messages[] = sprintf('Вы вошли как <strong>%s</strong>. <a href="login.php?logout=1">Выйти</a>', 
            htmlspecialchars($_SESSION['login']));
    }
}

// Обработка сообщения о сохранении
if (!empty($_COOKIE['save'])) {
    setcookie('save', '', 100000);
    $messages[] = 'Спасибо, результаты сохранены.';
    
    // Показываем логин/пароль ТОЛЬКО если пользователь НЕ авторизован
    if (!$isAuthorized && !empty($_COOKIE['pass'])) {
        $messages[] = sprintf('Вы можете <a href="login.php">Войти</a> с логином <strong>%s</strong>
            и паролем <strong>%s</strong> для изменения данных.',
            strip_tags($_COOKIE['login']),
            strip_tags($_COOKIE['pass']));
        setcookie('login', '', 100000);
        setcookie('pass', '', 100000);
    }
}

  
  $errors = array();
  $errors['FIO'] = !empty($_COOKIE['FIO_error']);
  $errors['telep'] = !empty($_COOKIE['telep_error']);
  $errors['mail'] = !empty($_COOKIE['mail_error']);
  $errors['date'] = !empty($_COOKIE['date_error']);
  $errors['sex'] = !empty($_COOKIE['sex_error']);
  $errors['language'] = !empty($_COOKIE['language_error']);
  $errors['bio'] = !empty($_COOKIE['bio_error']);
  $errors['agreement'] = !empty($_COOKIE['agreement_error']);

  
  
   $error_messages = array();
    
    if ($errors['FIO']) {
        $error_messages['FIO'] = $_COOKIE['FIO_msg'] ?? 'Ошибка в поле ФИО';
        setcookie('FIO_error', '', 100000);
        setcookie('FIO_msg', '', 100000);
    }
    
    if ($errors['telep']) {
        $error_messages['telep'] = $_COOKIE['telep_msg'] ?? 'Ошибка в поле Телефон';
        setcookie('telep_error', '', 100000);
        setcookie('telep_msg', '', 100000);
    }
    
    if ($errors['mail']) {
        $error_messages['mail'] = $_COOKIE['mail_msg'] ?? 'Ошибка в поле Email';
        setcookie('mail_error', '', 100000);
        setcookie('mail_msg', '', 100000);
    }
    
    if ($errors['date']) {
        $error_messages['date'] = $_COOKIE['date_msg'] ?? 'Ошибка в поле Дата рождения';
        setcookie('date_error', '', 100000);
        setcookie('date_msg', '', 100000);
    }
    
    if ($errors['sex']) {
        $error_messages['sex'] = $_COOKIE['sex_msg'] ?? 'Ошибка в поле Пол';
        setcookie('sex_error', '', 100000);
        setcookie('sex_msg', '', 100000);
    }
    
    if ($errors['language']) {
        $error_messages['language'] = $_COOKIE['language_msg'] ?? 'Ошибка в поле Языки программирования';
        setcookie('language_error', '', 100000);
        setcookie('language_msg', '', 100000);
    }
    
    if ($errors['bio']) {
        $error_messages['bio'] = $_COOKIE['bio_msg'] ?? 'Ошибка в поле Биография';
        setcookie('bio_error', '', 100000);
        setcookie('bio_msg', '', 100000);
    }
    
    if ($errors['agreement']) {
        $error_messages['agreement'] = $_COOKIE['agreement_msg'] ?? 'Необходимо подтвердить согласие';
        setcookie('agreement_error', '', 100000);
        setcookie('agreement_msg', '', 100000);
    }

  $values = array();
  $values['FIO'] = empty($_COOKIE['FIO_value']) ? '' : strip_tags($_COOKIE['FIO_value']);
  $values['telep'] = empty($_COOKIE['telep_value']) ? '' : strip_tags($_COOKIE['telep_value']);
  $values['mail'] = empty($_COOKIE['mail_value']) ? '' : strip_tags($_COOKIE['mail_value']);
  $values['date'] = empty($_COOKIE['date_value']) ? '' : strip_tags($_COOKIE['date_value']);
  $values['sex'] = empty($_COOKIE['sex_value']) ? '' : strip_tags($_COOKIE['sex_value']);
  $values['language'] = empty($_COOKIE['language_value']) ? [] : explode('|',strip_tags($_COOKIE['language_value']));
  $values['bio'] = empty($_COOKIE['bio_value']) ? '' : strip_tags($_COOKIE['bio_value']);
  $values['agreement'] = empty($_COOKIE['agreement_value']) ? '' : strip_tags($_COOKIE['agreement_value']);


  // Если нет предыдущих ошибок ввода, есть кука сессии, начали сессию и
  // ранее в сессию записан факт успешного логина.
  if (empty($errors) && !empty($_COOKIE[session_name()]) &&
      session_start() && !empty($_SESSION['login'])) {
    try {
        $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", 
        $config['user'], 
        $config['pass']
    );
      $stmt = $db->prepare("
        SELECT r.name, r.tel, r.email, r.dateborn, r.sex, r.bio, r.agree,
               GROUP_CONCAT(l.language_name SEPARATOR '|') as LANGUAGES
        FROM Frequest r
        JOIN UserInfo u ON r.id = u.request_id
        LEFT JOIN Connect c ON r.id = c.request_id
        LEFT JOIN LANGUAGES l ON c.language_id = l.language_id
        WHERE u.login = ?
        GROUP BY r.id
      ");
      $stmt->execute([$_SESSION['login']]);
      $userData = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($userData) {
        $values['FIO'] = htmlspecialchars($userData['name']);
        $values['telep'] = htmlspecialchars($userData['tel']);
        $values['mail'] = htmlspecialchars($userData['email']);
        $values['date'] = $userData['dateborn'];
        $values['sex'] = ($userData['sex'] == 'M') ? 'Male' : 'Female';
        $values['language'] = $userData['languages'] ? explode('|', $userData['languages']) : [];
        $values['bio'] = htmlspecialchars($userData['bio']);
        $values['agreement'] = $userData['agree'] ? 'on' : '';
      }
      
      printf('Вход с логином %s, uid %d', $_SESSION['login'], $_SESSION['uid']);
    } catch (PDOException $e) {
      error_log('Database error: ' . $e->getMessage());
    }
  }
  
  include('form.php');
}
// Иначе, если запрос был методом POST, т.е. нужно проверить данные и сохранить их в базе данных.
else {

   $errors = false;
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ ФИО ==========
    if (empty($_POST['FIO'])) {
        setcookie('FIO_error', '1', 0);
        setcookie('FIO_msg', 'ФИО обязательно для заполнения. Допустимы: буквы, пробелы, дефис.', 0);
        $errors = true;
    } elseif (strlen($_POST['FIO']) > 150) {
        setcookie('FIO_error', '1', 0);
        setcookie('FIO_msg', 'ФИО слишком длинное (максимум 150 символов)', 0);
        $errors = true;
    } elseif (!preg_match('/^[a-zA-Zа-яёА-ЯЁ\s\-]+$/u', $_POST['FIO'])) {
        setcookie('FIO_error', '1', 0);
        setcookie('FIO_msg', 'В ФИО допустимы только буквы, пробелы и дефис', 0);
        $errors = true;
    }
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ ТЕЛЕФОН ==========
    if (empty($_POST['telep'])) {
        setcookie('telep_error', '1', 0);
        setcookie('telep_msg', 'Номер телефона обязателен для заполнения. Допустимый формат: +7 (999) 123-45-67', 0);
        $errors = true;
    } elseif (!preg_match('/^[\+\d\s\-\(\)]{6,20}$/', $_POST['telep'])) {
        setcookie('telep_error', '1', 0);
        setcookie('telep_msg', 'Телефон введён некорректно. Допустимые символы: +, цифры, пробелы, скобки, дефис. Длина: 6-20 символов', 0);
        $errors = true;
    }
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ EMAIL ==========
    if (empty($_POST['mail'])) {
        setcookie('mail_error', '1', 0);
        setcookie('mail_msg', 'Email обязателен для заполнения. Формат: name@domain.ru', 0);
        $errors = true;
    } elseif (!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
        setcookie('mail_error', '1', 0);
        setcookie('mail_msg', 'Email введён неправильно. Допустимый формат: user@example.com', 0);
        $errors = true;
    }
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ ДАТА ==========
    if (empty($_POST['date'])) {
        setcookie('date_error', '1', 0);
        setcookie('date_msg', 'Дата рождения обязательна для заполнения. Формат: ГГГГ-ММ-ДД', 0);
        $errors = true;
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'])) {
        setcookie('date_error', '1', 0);
        setcookie('date_msg', 'Дата рождения должна быть в формате ГГГГ-ММ-ДД', 0);
        $errors = true;
    } else {
        $date_parts = explode('-', $_POST['date']);
        if (!checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
            setcookie('date_error', '1', 0);
            setcookie('date_msg', 'Дата рождения некорректна (проверьте число и месяц)', 0);
            $errors = true;
        }
    }
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ ПОЛ ==========
    if (empty($_POST['sex'])) {
        setcookie('sex_error', '1', 0);
        setcookie('sex_msg', 'Необходимо выбрать пол', 0);
        $errors = true;
    } elseif (!in_array($_POST['sex'], array('Male', 'Female'))) {
        setcookie('sex_error', '1', 0);
        setcookie('sex_msg', 'Выбрано недопустимое значение пола', 0);
        $errors = true;
    }
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ ЯЗЫКИ ==========
    $allowed_languages = array('PHP', 'Python', 'Java', 'JavaScript', 'C++', 'Go');
    if (empty($_POST['language'])) {
        setcookie('language_error', '1', 0);
        setcookie('language_msg', 'Выберите хотя бы один язык программирования', 0);
        $errors = true;
    } else {
        foreach ($_POST['language'] as $lang) {
            if (!in_array($lang, $allowed_languages)) {
                setcookie('language_error', '1', 0);
                setcookie('language_msg', 'Выбран недопустимый язык программирования. Допустимы: PHP, Python, Java, JavaScript, C++, Go', 0);
                $errors = true;
                break;
            }
        }
    }
    
    // ========== ВАЛИДАЦИЯ ПОЛЯ БИОГРАФИЯ ==========
    if (empty($_POST['bio'])) {
        setcookie('bio_error', '1', 0);
        setcookie('bio_msg', 'Биография обязательна для заполнения', 0);
        $errors = true;
    } elseif (strlen($_POST['bio']) > 1000) {
        setcookie('bio_error', '1', 0);
        setcookie('bio_msg', 'Биография не должна превышать 1000 символов', 0);
        $errors = true;
    } elseif (!preg_match('/^[a-zA-Zа-яёА-ЯЁ0-9\s\.,!?\-:;\'"]+$/u', $_POST['bio'])) {
        setcookie('bio_error', '1', 0);
        setcookie('bio_msg', 'Биография содержит недопустимые символы. Допустимы: буквы, цифры, пробелы, знаки препинания (.,!?-:;\'")', 0);
        $errors = true;
    }
    
    // ========== ВАЛИДАЦИЯ СОГЛАСИЯ ==========
    if (empty($_POST['agreement'])) {
        setcookie('agreement_error', '1', 0);
        setcookie('agreement_msg', 'Необходимо подтвердить согласие с контрактом', 0);
        $errors = true;
    } elseif ($_POST['agreement'] !== 'on') {
        setcookie('agreement_error', '1', 0);
        setcookie('agreement_msg', 'Необходимо отметить согласие с контрактом', 0);
        $errors = true;
    }

    setcookie('FIO_value', $_POST['FIO'], 0);
    setcookie('telep_value', $_POST['telep'], 0);
    setcookie('mail_value', $_POST['mail'], 0);
    setcookie('date_value', $_POST['date'], 0);
    setcookie('sex_value', $_POST['sex'], 0);
    if (!empty($_POST['language'])) {
        setcookie('language_value', implode('|', $_POST['language']), 0);
    }
    setcookie('bio_value', $_POST['bio'], 0);
    setcookie('agreement_value', $_POST['agreement'], 0);

    

  if ($errors) {
    header('Location: index.php');
    exit();
  }
   
    setcookie('FIO_error', '', 100000);
    setcookie('FIO_msg', '', 100000);
    setcookie('telep_error', '', 100000);
    setcookie('telep_msg', '', 100000);
    setcookie('mail_error', '', 100000);
    setcookie('mail_msg', '', 100000);
    setcookie('date_error', '', 100000);
    setcookie('date_msg', '', 100000);
    setcookie('sex_error', '', 100000);
    setcookie('sex_msg', '', 100000);
    setcookie('language_error', '', 100000);
    setcookie('language_msg', '', 100000);
    setcookie('bio_error', '', 100000);
    setcookie('bio_msg', '', 100000);
    setcookie('agreement_error', '', 100000);
    setcookie('agreement_msg', '', 100000);

    setcookie('FIO_value', $_POST['FIO'], time() + 365 * 24 * 60 * 60);
    setcookie('telep_value', $_POST['telep'], time() + 365 * 24 * 60 * 60);
    setcookie('mail_value', $_POST['mail'], time() + 365 * 24 * 60 * 60);
    setcookie('date_value', $_POST['date'], time() + 365 * 24 * 60 * 60);
    setcookie('sex_value', $_POST['sex'], time() + 365 * 24 * 60 * 60);
    if (!empty($_POST['language'])) {
        setcookie('language_value', implode('|', $_POST['language']), time() + 365 * 24 * 60 * 60);
    }
    setcookie('bio_value', $_POST['bio'], time() + 365 * 24 * 60 * 60);
    setcookie('agreement_value', $_POST['agreement'], time() + 365 * 24 * 60 * 60);

  

  // Проверяем меняются ли ранее сохраненные данные или отправляются новые.
  if ($isAuthorized) {
    try {
        $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", 
        $config['user'], 
        $config['pass']
    );
      // Получаем request_id пользователя
      $stmt = $db->prepare("SELECT request_id FROM UserInfo WHERE login = ?");
      $stmt->execute([$_SESSION['login']]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($user) {
        $request_id = $user['request_id'];
        
        // Обновляем данные в Request
        $stmt = $db->prepare("
          UPDATE Frequest 
          SET name = ?, tel = ?, email = ?, dateborn = ?, sex = ?, bio = ?, agree = ? WHERE id = ?");
        
        $sex = ($_POST['sex'] == 'Male') ? 'Male' : 'Female';
        $agree = ($_POST['agreement'] == 'on') ? 1 : 0;
        
        $stmt->execute([
          $_POST['FIO'],
          $_POST['telep'],
          $_POST['mail'],
          $_POST['date'],
          $sex,
          $_POST['bio'],
          $agree,
          $request_id /////подумать
        ]);
        
        // Удаляем старые связи с языками
        $stmt = $db->prepare("DELETE FROM Connect WHERE request_id = ?");
        $stmt->execute([$request_id]);
        
        // Добавляем новые связи
        $stmt = $db->prepare("
          INSERT INTO Connect (request_id, language_id) 
          VALUES (?, (SELECT language_id FROM LANGUAGES WHERE language_name = ?))
        ");
        
        foreach ($_POST['language'] as $lang) {
          $stmt->execute([$request_id, $lang]);
        }
      }
    } catch (PDOException $e) {
      error_log('Database update error: ' . $e->getMessage());
    }
  
  }
  else {
    // Генерируем уникальный логин и пароль.
    // TODO: сделать механизм генерации, например функциями rand(), uniquid(), md5(), substr().
    $login = substr(md5(uniqid(rand(),true)), 0 , 10);
    $pass = substr(md5(uniqid(rand(),true)), 0 , 8);;
    // Сохраняем в Cookies.
    setcookie('login', $login, time() + 365 * 24 * 60 * 60);
    setcookie('pass', $pass, time() + 365 * 24 * 60 * 60);

    // TODO: Сохранение данных формы, логина и хеш md5() пароля в базу данных.
    // ...

    try {
        $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", 
        $config['user'], 
        $config['pass']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->beginTransaction();

    $sex = ($_POST['sex'] == 'Male') ? 'Male' : 'Female';
    $agreement = ($_POST['agreement'] == 'on') ? 1:0;
    $stmt = $db->prepare("INSERT INTO Frequest (name, tel, email, dateborn, sex, bio, agree) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['FIO'],
        $_POST['telep'],
        $_POST['mail'],
        $_POST['date'],
        $sex,
        $_POST['bio'],
        $agreement
    ]);

    $requestId = $db->lastInsertId();

    $getLangId = $db->prepare("SELECT language_id FROM LANGUAGES WHERE language_name = ?");
    $insertConn = $db->prepare("INSERT INTO Connect (request_id, language_id) VALUES (?, ?)");

    foreach ($_POST['language'] as $langName) {
        $getLangId->execute([$langName]);
        $row = $getLangId->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $insertConn->execute([$requestId, $row['language_id']]);
        }
    }

    $stmt = $db->prepare("INSERT INTO UserInfo (request_id,login,pass) VALUES (?, ?, ?)");
    $password_hash = md5($pass);
    $stmt->execute([$requestId, $login, $password_hash]);

    $db->commit();
    echo "<h2>Данные успешно сохранены!</h2>";

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Ошибка базы данных: " . $e->getMessage();
}
  }

  
 setcookie('save', '1', time() + 365 * 24 * 60 * 60);

  
  header('Location: ./');
}
