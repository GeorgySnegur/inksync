<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$username = $_SERVER['USER'];


$stmt = $dbh->prepare("SELECT username FROM users WHERE id = ?");
$user_id = $stmt->fetch($username);
$output = '';

try {
    $stmt = $dbh->prepare(
        "SELECT project_id, user_id, name 
        FROM projects 
        WHERE user_id = ?
        ORDER BY project_id ASC"
        );

    $output = $stmt->fetchAll($user_id);

    var_dump($output, $user_id, $username);


    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

require_once __DIR__ . '/templates/header.php';
?>
<section class="main-section">

    <div class="card">

    <?php
    foreach ($output as $row):
    ?>

    <li>
        <h2><?= $output['name'] ?></h2>
        <p> Project ID: <?= $output['project_id'] ?></p>
    </li>

    <?php
    endforeach;
    ?>
    </div>

</section>