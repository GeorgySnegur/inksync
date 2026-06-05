<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

$login_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_input = $_POST['username'] ?? '';
    $pass_input = $_POST['password'] ?? '';

    $result = check_login($user_input, $pass_input);

    if ($result !== false) {
        [$success, $role] = $result;

        $stmt = $dbh->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$user_input]);
        $user = $stmt->fetch();

        $_SESSION['USER'] = [
        'username' => $user_input,
        'user_id'  => $user->user_id,
        'role'     => $role ];

        $_SESSION['role'] = $role;
        header("Location: " . BASE_URL . "/index.php");
        exit;
    } else {
        $login_message = "Username or password is incorrect.";
    }
}

require_once __DIR__ . "/../templates/header.php";
?>

<form method="POST" action="<?= BASE_URL ?>/pages/login.php">
    <input type="text"     name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Password">
    <button type="submit">Login</button>
</form>

<?= htmlspecialchars($login_message) ?>