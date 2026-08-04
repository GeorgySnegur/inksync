<?php

require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';
$login_message = "";
if (isset($_GET['declined'])) {
    $login_message = "You must accept the privacy policy / terms of use to use InkSync.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Reject requests with a missing or wrong CSRF token
    csrf_verify_post();
    $user_input = $_POST['username'] ?? '';
    $pass_input = $_POST['password'] ?? '';
    $result = check_login($user_input, $pass_input);
    if ($result === false) {
        $login_message = "Username or password is incorrect.";
    } elseif (is_array($result) && $result[0] === 'locked') {
        $login_message = "Too many failed attempts. Please try again in {$result[1]} minute(s).";
    } else {
        [$success, $role, $user_id] = $result;
    // Prevent session fixation: swap the pre-login session ID for a fresh one
        session_regenerate_id(true);
        $_SESSION['USER'] = [
        'username' => $user_input,
        'user_id'  => $user_id,
        'role'     => $role ];
        $_SESSION['role'] = $role;
        header("Location: " . BASE_URL . "/");
        exit;
    }
}

require_once __DIR__ . "/../templates/header.php";
?>

<form method="POST" action="<?= BASE_URL ?>/login">
    <!-- CSRF hidden field — server checks this matches the session token -->
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <div class="field">
        <input type="text"     name="username" placeholder="Username">
        <input type="password" name="password" placeholder="Password">
    </div>
    <div class="field">
        <button type="submit">Login</button>
    </div>
</form>

<?= htmlspecialchars($login_message) ?>
