<?php
require_once __DIR__ . '/../backend/bootstrap.php';
require_once __DIR__ . '/../backend/functions.php';

$role = $_SESSION['role'] ?? 'guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InkSync — Storyboard Generator</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/frontend/styles.css">
</head>
<body>

<header>
    <ul>
        <?php if ($role === 'admin'): ?>
            <li><a href="<?= BASE_URL ?>/pages/register.php">Admin Panel</a></li>
        <?php endif; ?>

        <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>

        <div>
            <h1>InkSync</h1>
            <p>AI Storyboard Generator — Prototype v0.2</p>
        </div>

        <?php if ($role !== 'guest' && $role !== null): ?>
            <li><a href="<?= BASE_URL ?>/pages/logout.php">Logout (<?= htmlspecialchars($_SESSION['USER']) ?>)</a></li>
        <?php else: ?>
            <li><a href="<?= BASE_URL ?>/pages/login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</header>