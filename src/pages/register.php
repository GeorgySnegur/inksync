<?php

    require __DIR__ . '/../backend/config.php';
    require_once __DIR__ . '/../backend/functions.php';

    $register_message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        
        $username = $_POST['username'];
        // $password = $_POST['password'];

        $password = $_POST['password'];
        if (validate_password($password)) {
            $register_message = $register_message . " validate_password returns true with " . $password . ":";
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $dbh->prepare('INSERT INTO users (username, password) VALUES (? ,?)');
                $stmt = $stmt->execute([$username, $password_hash]);
            } catch (PDOException $e) {
                $e->getMessage();
            }

            // exit();
        } else {
            $register_message = "Password is INVALID: " . $register_message;            
        } 
    }

    require "../templates/header.php";
?>

<form method="POST" action="register.php">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Passwort">
    <button type="submit">Submit</button>
</form> 

<?= $register_message; var_dump($_SESSION['USER'], $_POST['password'])?>


