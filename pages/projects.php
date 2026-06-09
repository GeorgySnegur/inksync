<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/check_login.php';

if ($role === 'guest' || !isset($_SESSION['USER'])) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$username = $_SESSION['USER']['username'];
$user_id = $_SESSION['USER']['user_id'];
$output = '';

try {
    $stmt = $dbh->prepare(
        "SELECT project_id, user_id, name 
        FROM projects 
        WHERE user_id = ?
        ORDER BY project_id ASC"
        );

    $stmt->execute([$user_id]);
    $output = $stmt->fetchAll();

    // echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

require_once __DIR__ . '/../templates/header.php';
?>
<section class="main-section">

    <div class="card">

    <ul>
        <?php
        foreach ($output as $row):
        ?>
            <li>
                <h2><?= $row->name ?></h2>
                <p> Project ID: <?= $row->project_id ?></p>
            </li>
        <?php
        endforeach;
        ?>
    </ul>
    </div>

</section>