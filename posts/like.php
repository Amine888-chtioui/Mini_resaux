<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$postId = (int) ($_POST['post_id'] ?? 0);
$redirect = app_redirect_target((string) ($_POST['redirect'] ?? ''), BASE_URL . '/index.php');
if ($postId < 1) {
    header('Location: ' . $redirect);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

$userId = (int) $_SESSION['user_id'];
$check = $pdo->prepare('SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = ?');
$check->execute([$userId, $postId]);
if ($check->fetch()) {
    $pdo->prepare('DELETE FROM post_likes WHERE user_id = ? AND post_id = ?')->execute([$userId, $postId]);
} else {
    $pdo->prepare('INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)')->execute([$userId, $postId]);
}

header('Location: ' . $redirect);
exit;
