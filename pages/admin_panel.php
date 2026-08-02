<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Reject requests with a missing or wrong CSRF token
    csrf_verify_post();

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
                // Don't leak raw DB error text (e.g. constraint/column names,
                // driver details) to the admin UI -- log it server-side and
                // show a generic message instead. A unique-violation here
                // almost always just means the username is taken.
                error_log("admin_panel.php: user registration failed: " . $e->getMessage());
                $message = (strpos($e->getMessage(), 'unique') !== false || $e->getCode() === '23505')
                    ? "Error: that username is already taken."
                    : "Error: could not register user. Please try again.";
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

<section class="main-section">
    <div class="card">

    <div class="field">
        <h2>Users</h2>
    </div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:2rem;">
            <thead>
                <tr>
                    <th style="text-align:left; padding:8px; border-bottom:1px solid #ccc;">Username</th>
                    <th style="text-align:left; padding:8px; border-bottom:1px solid #ccc;">Role</th>
                    <th style="text-align:left; padding:8px; border-bottom:1px solid #ccc;">Generations Today</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="padding:8px;"><?= htmlspecialchars($u->username) ?></td>
                    <td style="padding:8px;"><?= htmlspecialchars($u->role) ?></td>
                    <td style="padding:8px;">
                        <?= $u->today_count ?>
                        <?= $u->role !== 'admin' ? ' / 40' : ' (unlimited)' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <hr>
        <br>
        <form method="POST" action="<?= BASE_URL ?>/admin">
            <!-- CSRF hidden field — server checks this matches the session token -->
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="field">
                <h2>Register New User</h2>
            </div>
            <div class="field">
                <input type="text"     name="username" placeholder="Username">
                <input type="password" name="password" placeholder="Password">
                <select name="role" id="role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                </select>
            </div>
                <button type="submit">Register</button>
        </form>

        <?php if ($message): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

    </div>
</section>

</body>
</html>
