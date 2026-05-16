<?php

    require '../backend/config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        
        $username = $_POST['username'];
        $password = $_POST['password'];

        // validate user input and create hash(){};
        
        $stmt = $dbh->prepare('INSERT INTO users (username, password) VALUES (? ,?)');
        $stmt = $dbh->execute([$username, $password]);
    }

    require "../templates/header.php";
?>

<form method="POST" action="register.php">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Passwort">
    <button type="submit">Submit</button>
</form>

