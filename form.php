<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрационная форма</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main>
        <div class="form-container">
            <fieldset>
                <legend>Регистрационная форма</legend>
                
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $message): ?>
                        <?php echo $message; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <!-- Поле ФИО -->
                    <div class="form-group">
                        <label for="FIO">ФИО:</label>
                        <input type="text" id="FIO" name="FIO" placeholder="Иванов Иван Иванович" 
                               <?php echo !empty($errors['FIO']) ? 'class="error"' : ''; ?> 
                               value="<?php echo htmlspecialchars($values['FIO'] ?? ''); ?>" />
                               <?php if (!empty($errors['FIO']) && !empty($error_messages['FIO'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['FIO']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Телефон -->
                    <div class="form-group">
                        <label for="telep">Телефон:</label>
                        <input type="tel" id="telep" name="telep" placeholder="+7 (999) 123-45-67" 
                               <?php echo !empty($errors['telep']) ? 'class="error"' : ''; ?> 
                               value="<?php echo htmlspecialchars($values['telep'] ?? ''); ?>" />
                        <?php if (!empty($errors['telep']) && !empty($error_messages['telep'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['telep']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Email -->
                    <div class="form-group">
                        <label for="mail">Электронная почта:</label>
                        <input type="email" id="mail" name="mail" placeholder="yourmail@mail.ru" 
                               <?php echo !empty($errors['mail']) ? 'class="error"' : ''; ?> 
                               value="<?php echo htmlspecialchars($values['mail'] ?? ''); ?>" />
                        <?php if (!empty($errors['mail']) && !empty($error_messages['mail'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['mail']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Дата -->
                    <div class="form-group">
                        <label for="date">Дата рождения:</label>
                        <input type="date" id="date" name="date" 
                               <?php echo !empty($errors['date']) ? 'class="error"' : ''; ?> 
                               value="<?php echo htmlspecialchars($values['date'] ?? ''); ?>" />
                        <?php if (!empty($errors['date']) && !empty($error_messages['date'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['date']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Пол -->
                    <div class="form-group">
                        <label>Пол:</label>
                        <div class="radio-group">
                            <label><input type="radio" name="sex" value="Male" 
                                  <?php echo (($values['sex'] ?? '') == 'Male') ? 'checked' : ''; ?> /> Мужской</label>
                            <label><input type="radio" name="sex" value="Female" 
                                  <?php echo (($values['sex'] ?? '') == 'Female') ? 'checked' : ''; ?> /> Женский</label>
                        </div>
                        <?php if (!empty($errors['sex']) && !empty($error_messages['sex'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['sex']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Языки -->
                    <div class="form-group">
                        <label for="language">Любимый язык программирования:</label>
                        <select id="language" name="language[]" multiple="multiple" 
                                <?php echo !empty($errors['language']) ? 'class="error"' : ''; ?>>
                            <option value="PHP" <?php echo in_array('PHP', ($values['language'] ?? array())) ? 'selected' : ''; ?>>PHP</option>
                            <option value="Python" <?php echo in_array('Python', ($values['language'] ?? array())) ? 'selected' : ''; ?>>Python</option>
                            <option value="Java" <?php echo in_array('Java', ($values['language'] ?? array())) ? 'selected' : ''; ?>>Java</option>
                            <option value="JavaScript" <?php echo in_array('JavaScript', ($values['language'] ?? array())) ? 'selected' : ''; ?>>JavaScript</option>
                            <option value="C++" <?php echo in_array('C++', ($values['language'] ?? array())) ? 'selected' : ''; ?>>C++</option>
                            <option value="Go" <?php echo in_array('Go', ($values['language'] ?? array())) ? 'selected' : ''; ?>>Go</option>
                        </select>
                        <?php if (!empty($errors['language']) && !empty($error_messages['language'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['language']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Биография -->
                    <div class="form-group">
                        <label for="bio">Биография:</label>
                        <textarea id="bio" name="bio" placeholder="Расскажите о себе" 
                               <?php echo !empty($errors['bio']) ? 'class="error"' : ''; ?>><?php echo htmlspecialchars($values['bio'] ?? ''); ?></textarea>
                        <?php if (!empty($errors['bio']) && !empty($error_messages['bio'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['bio']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Поле Согласие -->
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="agreement" name="agreement" value="on" 
                                   <?php echo (($values['agreement'] ?? '') == 'on') ? 'checked' : ''; ?> 
                                   <?php echo !empty($errors['agreement']) ? 'class="error"' : ''; ?> />
                            <label for="agreement">С контрактом ознакомлен(а)</label>
                        </div>
                        <?php if (!empty($errors['agreement']) && !empty($error_messages['agreement'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error_messages['agreement']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit">Отправить</button>
                </form>
                    <div style="text-align: center; margin-top: 20px;">
                    <a href="login.php" style="
                        display: inline-block;
                        padding: 10px 20px;
                        background-color: #4CAF50;
                        color: white;
                        text-decoration: none;
                        border-radius: 5px;
                        font-size: 14px;
                    ">
                    Войти 
                    </a>
            </fieldset>
        </div>
    </main>
</body>
</html>