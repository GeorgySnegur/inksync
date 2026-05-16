<?php
    require "functions.php";

?>

<?php
$login_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_input = $_POST['username'] ?? '';
    $pass_input = $_POST['password'] ?? '';

    if (check_login($user_input, $pass_input)) {
        $_SESSION['USER'] = $user_input;
        header("Location: index.php");
        exit;
    }
    else {
        $login_message = "Login Daten unvollständig";
    }
}
?>

<?php
    require "templates/header.php";
?>

<form method="POST" action="login.php">
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Passwort">
    <button type="submit">Submit</button>
</form>
<?= $login_message; var_dump($_SESSION['USER'])?>

