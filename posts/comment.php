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
$body = trim((string) ($_POST['body'] ?? ''));
$redirect = app_redirect_target((string) ($_POST['redirect'] ?? ''), BASE_URL . '/index.php');

if ($postId < 1 || $body === '') {
    header('Location: ' . $redirect);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

$exists = $pdo->prepare('SELECT 1 FROM posts WHERE id = ?');
$exists->execute([$postId]);
if (!$exists->fetch()) {
    header('Location: ' . $redirect);
    exit;
}

$pdo->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)')
    ->execute([$postId, (int) $_SESSION['user_id'], $body]);

$anchor = '#post-' . $postId;
header('Location: ' . $redirect . $anchor);
exit;
