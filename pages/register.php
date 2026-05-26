<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

$register_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (validate_password($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'admin' || $role === 'user') {
            try {
                $stmt = $dbh->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                $stmt->execute([$username, $password_hash, $role]);
                $register_message = "User registered successfully.";
            } catch (PDOException $e) {
                $register_message = "Error: " . htmlspecialchars($e->getMessage());
            }
        } else {
            $register_message = "Role name is invalid.";
        }
    } else {
        $register_message = "Password is invalid.";
    }
}

require_once __DIR__ . "/../templates/header.php";
?>

<form method="POST" action="<?= BASE_URL ?>/pages/register.php">
    <input type="text"     name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Password">
    <select name="role" id="role">
        <option value="user">user</option>
        <option value="admin">admin</option>
    </select>
    <button type="submit">Register</button>
</form>

<?= htmlspecialchars($register_message) ?>