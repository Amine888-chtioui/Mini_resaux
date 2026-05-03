<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Vérifier session
session_start();
if (!isset($_SESSION['user_id'])) {
    // Mode développement: utiliser l'utilisateur 1 par défaut
    error_log("API Photos: Session non définie - utilisation de l'utilisateur 1 par défaut");
    $_SESSION['user_id'] = 1;
    // En production, décommentez les lignes suivantes:
    // http_response_code(401);
    // exit('Non autorisé');
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

// Compatibilité schéma: certains projets utilisent posts/post_likes/comments
$hasPhotosTable = false;
try {
    $q = $pdo->query("SHOW TABLES LIKE 'photos'");
    $hasPhotosTable = $q && $q->fetchColumn() !== false;
} catch (\Exception $e) {
    $hasPhotosTable = false;
}

$photoTable = $hasPhotosTable ? 'photos' : 'posts';
$likeTable = $hasPhotosTable ? 'photo_likes' : 'post_likes';
$commentTable = $hasPhotosTable ? 'photo_comments' : 'comments';
$captionColumn = $hasPhotosTable ? 'p.caption' : 'p.body';
$likeFk = $hasPhotosTable ? 'photo_id' : 'post_id';
$commentFk = $hasPhotosTable ? 'photo_id' : 'post_id';

function normalizePhotoPath(string $path): string
{
    $clean = trim($path);
    if ($clean === '') {
        return $clean;
    }
    if (preg_match('#^https?://#i', $clean)) {
        return $clean;
    }
    if (str_starts_with($clean, '/')) {
        $clean = ltrim($clean, '/');
    }
    if (str_starts_with($clean, 'uploads/')) {
        return $clean;
    }
    return 'uploads/photos/' . basename($clean);
}

try {
    switch ($action) {
        case 'upload':
            // Uploader une photo
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Aucune image fournie');
            }

            $caption = trim($_POST['caption'] ?? '');

            // Valider l'image
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                throw new Exception('Format d\'image non autorisé');
            }

            // Vérifier la taille (max 10MB)
            if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                throw new Exception('Image trop volumineuse (max 10MB)');
            }

            // Créer le répertoire si nécessaire
            $uploadDir = dirname(__DIR__) . '/uploads/photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Générer un nom unique
            $filename = uniqid('photo_', true) . '.' . $ext;
            $filepath = $uploadDir . $filename;

            // Uploader le fichier
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                throw new Exception('Erreur lors de l\'upload');
            }

            // Insérer en base de données
            if ($hasPhotosTable) {
                $stmt = $pdo->prepare("
                    INSERT INTO photos (user_id, caption, image_path) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $caption, 'uploads/photos/' . $filename]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO posts (user_id, body, image_path) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $caption, 'uploads/photos/' . $filename]);
            }
            $photoId = $pdo->lastInsertId();

            // Notifier les amis
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, link)
                SELECT f.friend_id, 'post_tag', 'Nouvelle photo', ?, ?
                FROM friendships f
                WHERE f.user_id = ? AND f.status = 'accepted'
            ");
            $stmt->execute([
                $_SESSION['username'] . ' a publié une nouvelle photo',
                "/profile.php?id=$userId#post-$photoId",
                $userId
            ]);

            echo json_encode([
                'success' => true,
                'photo_id' => $photoId,
                'message' => 'Photo publiée avec succès'
            ]);
            break;

        case 'list':
            // Lister les photos (pour la découverte)
            $limit = (int) ($_GET['limit'] ?? 20);
            $offset = (int) ($_GET['offset'] ?? 0);
            $filter = $_GET['filter'] ?? 'recent'; // recent, popular, following

            $orderBy = match ($filter) {
                'popular' => 'p.likes_count DESC, p.created_at DESC',
                'following' => 'p.created_at DESC', // TODO: filtrer par amis
                default => 'p.created_at DESC'
            };

            $stmt = $pdo->prepare("
                SELECT p.id, p.user_id, p.image_path, p.created_at, {$captionColumn} AS caption, u.username,
                       (SELECT COUNT(*) FROM {$likeTable} WHERE {$likeFk} = p.id) as likes_count,
                       (SELECT COUNT(*) FROM {$commentTable} WHERE {$commentFk} = p.id) as comments_count,
                       EXISTS(SELECT 1 FROM {$likeTable} WHERE {$likeFk} = p.id AND user_id = ?) as liked_by_me
                FROM {$photoTable} p
                JOIN users u ON u.id = p.user_id
                WHERE p.image_path IS NOT NULL
                ORDER BY $orderBy
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($photos as &$photo) {
                $photo['image_path'] = normalizePhotoPath((string) ($photo['image_path'] ?? ''));
            }
            unset($photo);

            echo json_encode(['success' => true, 'photos' => $photos]);
            break;

        case 'like':
            // Aimer ou ne plus aimer une photo
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $photoId = (int) ($input['photo_id'] ?? $_POST['photo_id'] ?? 0);
            if ($photoId === 0) {
                throw new Exception('ID photo invalide');
            }

            // Vérifier si déjà liké
            $stmt = $pdo->prepare("
                SELECT id FROM {$likeTable} 
                WHERE {$likeFk} = ? AND user_id = ?
            ");
            $stmt->execute([$photoId, $userId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Unlike
                $stmt = $pdo->prepare("
                    DELETE FROM {$likeTable} 
                    WHERE {$likeFk} = ? AND user_id = ?
                ");
                $stmt->execute([$photoId, $userId]);

                echo json_encode(['success' => true, 'liked' => false]);
            } else {
                // Like
                $stmt = $pdo->prepare("
                    INSERT INTO {$likeTable} ({$likeFk}, user_id) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$photoId, $userId]);

                // Notifier le propriétaire
                $stmt = $pdo->prepare("
                    SELECT user_id FROM {$photoTable} WHERE id = ?
                ");
                $stmt->execute([$photoId]);
                $photo = $stmt->fetch();

                if ($photo['user_id'] != $userId) {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, type, title, message, link) 
                        VALUES (?, 'like', 'Nouveau like', ?, ?)
                    ");
                    $stmt->execute([
                        $photo['user_id'],
                        $_SESSION['username'] . ' aime votre photo',
                        "/profile.php?id={$photo['user_id']}#post-$photoId"
                    ]);
                }

                echo json_encode(['success' => true, 'liked' => true]);
            }
            break;

        case 'comment':
            // Commenter une photo
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $photoId = (int) ($input['photo_id'] ?? $_POST['photo_id'] ?? 0);
            $body = trim((string) ($input['body'] ?? $_POST['body'] ?? ''));

            if ($photoId === 0 || empty($body)) {
                throw new Exception('Données invalides');
            }

            // Ajouter le commentaire
            $stmt = $pdo->prepare("
                INSERT INTO {$commentTable} ({$commentFk}, user_id, body) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$photoId, $userId, $body]);
            $commentId = $pdo->lastInsertId();

            // Notifier le propriétaire
            $stmt = $pdo->prepare("
                SELECT user_id FROM {$photoTable} WHERE id = ?
            ");
            $stmt->execute([$photoId]);
            $photo = $stmt->fetch();

            if ($photo['user_id'] != $userId) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, title, message, link) 
                    VALUES (?, 'comment', 'Nouveau commentaire', ?, ?)
                ");
                $stmt->execute([
                    $photo['user_id'],
                    $_SESSION['username'] . ' a commenté votre photo',
                    "/profile.php?id={$photo['user_id']}#post-$photoId"
                ]);
            }

            echo json_encode(['success' => true, 'comment_id' => $commentId]);
            break;

        case 'comments':
            // Lister les commentaires d'une photo
            $photoId = (int) ($_GET['photo_id'] ?? 0);
            if ($photoId === 0) {
                throw new Exception('ID photo invalide');
            }

            $stmt = $pdo->prepare("
                SELECT pc.*, u.username
                FROM {$commentTable} pc
                JOIN users u ON u.id = pc.user_id
                WHERE pc.{$commentFk} = ?
                ORDER BY pc.created_at ASC
            ");
            $stmt->execute([$photoId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'comments' => $comments]);
            break;

        case 'my_photos':
            // Photos de l'utilisateur connecté
            $stmt = $pdo->prepare("
                SELECT p.id, p.user_id, p.image_path, p.created_at, {$captionColumn} AS caption,
                       (SELECT COUNT(*) FROM {$likeTable} WHERE {$likeFk} = p.id) as likes_count,
                       (SELECT COUNT(*) FROM {$commentTable} WHERE {$commentFk} = p.id) as comments_count
                FROM {$photoTable} p
                WHERE p.user_id = ?
                  AND p.image_path IS NOT NULL
                ORDER BY p.created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$userId]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($photos as &$photo) {
                $photo['image_path'] = normalizePhotoPath((string) ($photo['image_path'] ?? ''));
            }
            unset($photo);

            echo json_encode(['success' => true, 'photos' => $photos]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
