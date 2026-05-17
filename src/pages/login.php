<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/functions.php';
?>

<?php
$login_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_input = $_POST['username'] ?? '';
    $pass_input = $_POST['password'] ?? '';

    $result = check_login($user_input, $pass_input);

    if ($result !== false) {
        // array destructuring: $result is made of -> [true/false, $output->role]
        [$success, $role] = $result;
        $_SESSION['USER'] = $user_input;
        $_SESSION['role'] = $role;
        header("Location: ../index.php");
        exit;
    } else {
        $login_message = "Login Daten unvollständig";
    }}
?>

<?php
    require_once __DIR__ . "/../templates/header.php";
?>

<form method="POST" action="login.php">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Passwort">
    <button type="submit">Submit</button>
</form>
<?= $login_message; var_dump(htmlspecialchars($_SESSION['USER']))?>

