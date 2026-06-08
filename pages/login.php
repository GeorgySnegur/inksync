<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

$login_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_input = $_POST['username'] ?? '';
    $pass_input = $_POST['password'] ?? '';

    $result = check_login($user_input, $pass_input);

    if ($result !== false) {
        [$success, $role, $user_id] = $result;

        $_SESSION['USER'] = [
        'username' => $user_input,
        'user_id'  => $user_id,
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
    <div class="field">
        <input type="text"     name="username" placeholder="Username">
        <input type="password" name="password" placeholder="Password">
    </div>
    <div class="field">
        <button type="submit">Login</button>
    </div>
</form>

<?= htmlspecialchars($login_message) ?>