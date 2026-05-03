<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    error_log("API Friends: Session non définie - utilisation de l'utilisateur 1 par défaut");
    $_SESSION['user_id'] = 1;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {

        case 'search':
            $query = trim($_GET['q'] ?? '');
            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'users' => []]);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.bio,
                       f1.id as friendship_request_id,
                       CASE
                           WHEN f1.user_id IS NOT NULL AND f1.status = 'accepted' THEN 'friend'
                           WHEN f1.user_id IS NOT NULL AND f1.status = 'pending'  THEN 'pending'
                           WHEN f2.user_id IS NOT NULL AND f2.status = 'pending'  THEN 'received'
                           ELSE 'none'
                       END as friendship_status
                FROM users u
                LEFT JOIN friendships f1 ON (f1.user_id = ? AND f1.friend_id = u.id)
                LEFT JOIN friendships f2 ON (f2.user_id = u.id AND f2.friend_id = ?)
                WHERE u.id != ?
                  AND u.username LIKE ?
                  -- exclure les utilisateurs bloqués (dans les deux sens)
                  AND u.id NOT IN (
                      SELECT blocked_id  FROM blocks WHERE blocker_id = ?
                      UNION
                      SELECT blocker_id  FROM blocks WHERE blocked_id  = ?
                  )
                ORDER BY u.username
                LIMIT 20
            ");
            $stmt->execute([$userId, $userId, $userId, "%$query%", $userId, $userId]);
            echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'list':
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.bio, f.created_at as friends_since
                FROM friendships f
                JOIN users u ON u.id = CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END
                WHERE (f.user_id = ? OR f.friend_id = ?)
                  AND f.status = 'accepted'
                ORDER BY u.username
            ");
            $stmt->execute([$userId, $userId, $userId]);
            echo json_encode(['success' => true, 'friends' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'requests':
            $stmt = $pdo->prepare("
                SELECT f.id, u.id as user_id, u.username, u.bio, f.created_at
                FROM friendships f
                JOIN users u ON u.id = f.user_id
                WHERE f.friend_id = ? AND f.status = 'pending'
                ORDER BY f.created_at DESC
            ");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'add':
            $input    = json_decode(file_get_contents('php://input'), true) ?? [];
            $friendId = (int) ($input['user_id'] ?? $_POST['user_id'] ?? 0);

            if ($friendId === 0 || $friendId === $userId) {
                throw new Exception('ID utilisateur invalide');
            }

            // Vérifier si bloqué
            $stmt = $pdo->prepare("SELECT 1 FROM blocks WHERE (blocker_id=? AND blocked_id=?) OR (blocker_id=? AND blocked_id=?)");
            $stmt->execute([$userId, $friendId, $friendId, $userId]);
            if ($stmt->fetch()) throw new Exception('Action impossible');

            // Vérifier relation existante
            $stmt = $pdo->prepare("SELECT id FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)");
            $stmt->execute([$userId, $friendId, $friendId, $userId]);
            if ($stmt->fetch()) throw new Exception('Une relation existe déjà');

            $pdo->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')")
                ->execute([$userId, $friendId]);

            // Notification
            $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'friend_request', 'Demande d\'ami', ?, ?)")
                ->execute([$friendId, ($_SESSION['username'] ?? 'Quelqu\'un') . ' veut être votre ami', "/profile.php?id=$userId"]);

            echo json_encode(['success' => true, 'message' => 'Demande envoyée']);
            break;

        case 'accept':
            $input     = json_decode(file_get_contents('php://input'), true) ?? [];
            $requestId = (int) ($input['request_id'] ?? $_POST['request_id'] ?? 0);

            if ($requestId === 0) throw new Exception('ID invalide');

            $stmt = $pdo->prepare("UPDATE friendships SET status='accepted' WHERE id=? AND friend_id=? AND status='pending'");
            $stmt->execute([$requestId, $userId]);
            if ($stmt->rowCount() === 0) throw new Exception('Demande introuvable');

            // Récupérer l'expéditeur pour la notification
            $row = $pdo->prepare("SELECT user_id FROM friendships WHERE id=?");
            $row->execute([$requestId]);
            $friendship = $row->fetch();
            if ($friendship) {
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'friend_accept', 'Demande acceptée', ?, ?)")
                    ->execute([$friendship['user_id'], ($_SESSION['username'] ?? 'Quelqu\'un') . ' a accepté votre demande', "/profile.php?id=$userId"]);
            }

            echo json_encode(['success' => true, 'message' => 'Ami ajouté']);
            break;

        case 'reject':
            $input     = json_decode(file_get_contents('php://input'), true) ?? [];
            $requestId = (int) ($input['request_id'] ?? $_POST['request_id'] ?? 0);

            if ($requestId === 0) throw new Exception('ID invalide');

            $stmt = $pdo->prepare("DELETE FROM friendships WHERE id=? AND friend_id=? AND status='pending'");
            $stmt->execute([$requestId, $userId]);
            if ($stmt->rowCount() === 0) throw new Exception('Demande introuvable');

            echo json_encode(['success' => true, 'message' => 'Demande refusée']);
            break;

        case 'remove':
            $input    = json_decode(file_get_contents('php://input'), true) ?? [];
            $friendId = (int) ($input['user_id'] ?? $_POST['user_id'] ?? 0);

            if ($friendId === 0) throw new Exception('ID invalide');

            $pdo->prepare("DELETE FROM friendships WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)")
                ->execute([$userId, $friendId, $friendId, $userId]);

            echo json_encode(['success' => true, 'message' => 'Ami supprimé']);
            break;

        case 'discover':
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.bio,
                       (SELECT COUNT(*) FROM friendships f
                        WHERE (f.user_id = u.id OR f.friend_id = u.id)
                          AND f.status = 'accepted') as friends_count
                FROM users u
                WHERE u.id != ?
                  AND u.id NOT IN (SELECT friend_id FROM friendships WHERE user_id=? AND status='accepted')
                  AND u.id NOT IN (SELECT user_id  FROM friendships WHERE friend_id=? AND status='accepted')
                  AND u.id NOT IN (SELECT friend_id FROM friendships WHERE user_id=? AND status='pending')
                  AND u.id NOT IN (SELECT blocked_id FROM blocks WHERE blocker_id=?)
                  AND u.id NOT IN (SELECT blocker_id FROM blocks WHERE blocked_id=?)
                ORDER BY RAND()
                LIMIT 12
            ");
            $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
            echo json_encode(['success' => true, 'suggestions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}