<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/functions.php';

    $register_message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        if (validate_password($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if ($role === 'admin' || $role === 'user') {
                try {
                $stmt = $dbh->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                $stmt = $stmt->execute([$username, $password_hash, $role]);
                } catch (PDOException $e) {
                    $e->getMessage();
                }

                // exit();
            }
            else {
                echo " role name is invalid";
            }
        } else {
            $register_message = "Password is INVALID: " . $register_message;            
        } 
    }

    require "../templates/header.php";
?>

<form method="POST" action="register.php">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Passwort">
    <select name="role" id="role">
        <option value="user">user</option>
        <option value="admin">admin</option>
    </select>
    <button type="submit">Submit</button>
</form> 

<?= $register_message; var_dump((htmlspecialchars($_SESSION['USER'])), $_POST['password'])?>


