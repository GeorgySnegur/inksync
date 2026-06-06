<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (validate_password($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'admin' || $role === 'user') {
            try {
                $stmt = $dbh->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                $stmt->execute([$username, $password_hash, $role]);
                $message = "User registered successfully.";
            } catch (PDOException $e) {
                $message = "Error: " . htmlspecialchars($e->getMessage());
            }
        } else {
            $message = "Role name is invalid.";
        }
    } else {
        $message = "Password is invalid.";
    }
}

$users = $dbh->query("
    SELECT u.username, u.role, COUNT(g.gen_id) AS today_count
    FROM users u
    LEFT JOIN image_generations g ON g.user_id = u.user_id AND g.generated_at::date = CURRENT_DATE
    GROUP BY u.user_id, u.username, u.role
    ORDER BY u.username
")->fetchAll();

require_once __DIR__ . "/../templates/header.php";
?>

    </div>
</section>

</body>
</html>
