<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/connexion.php');
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';

$me = (int) $_SESSION['user_id'];
$redirectDefault = BASE_URL . '/profile.php?id=' . $me;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectDefault);
    exit;
}

$body = trim((string) ($_POST['body'] ?? ''));
$uploadDir = dirname(__DIR__) . '/uploads';
$error = '';

if ($body === '') {
    $t = app_redirect_target((string) ($_POST['redirect'] ?? ''), $redirectDefault);
    header('Location: ' . $t . (strpos($t, '?') !== false ? '&' : '?') . 'err=empty');
    exit;
}

$imagePath = null;
if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($_FILES['image']['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        $error = 'image';
    } else {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $dest = $uploadDir . '/' . $name;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $error = 'upload';
        } else {
            $imagePath = $name;
        }
    }
}

if ($error === '') {
    $stmt = $pdo->prepare('INSERT INTO posts (user_id, body, image_path) VALUES (?, ?, ?)');
    $stmt->execute([$me, $body, $imagePath]);
}

$target = app_redirect_target((string) ($_POST['redirect'] ?? ''), $redirectDefault);
if ($error !== '') {
    $target .= (strpos($target, '?') !== false ? '&' : '?') . 'err=' . $error;
}
header('Location: ' . $target);
exit;
