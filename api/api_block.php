<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Non autorisé']));
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'block':
            $input = json_decode(file_get_contents('php://input'), true);
            $targetId = (int) ($input['user_id'] ?? 0);

            if ($targetId === 0 || $targetId === $userId) {
                throw new Exception('ID utilisateur invalide');
            }

            // Vérifier si déjà bloqué
            $stmt = $pdo->prepare("SELECT id FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
            $stmt->execute([$userId, $targetId]);
            if ($stmt->fetch()) {
                throw new Exception('Utilisateur déjà bloqué');
            }

            // Supprimer l'amitié si elle existe
            $pdo->prepare("DELETE FROM friendships WHERE 
                (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)")
                ->execute([$userId, $targetId, $targetId, $userId]);

            // Bloquer
            $pdo->prepare("INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)")
                ->execute([$userId, $targetId]);

            echo json_encode(['success' => true, 'message' => 'Utilisateur bloqué']);
            break;

        case 'unblock':
            $input = json_decode(file_get_contents('php://input'), true);
            $targetId = (int) ($input['user_id'] ?? 0);

            if ($targetId === 0) {
                throw new Exception('ID utilisateur invalide');
            }

            $stmt = $pdo->prepare("DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
            $stmt->execute([$userId, $targetId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Cet utilisateur n\'est pas bloqué');
            }

            echo json_encode(['success' => true, 'message' => 'Utilisateur débloqué']);
            break;

        case 'list':
            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.bio, b.created_at as blocked_at
                FROM blocks b
                JOIN users u ON u.id = b.blocked_id
                WHERE b.blocker_id = ?
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([$userId]);
            $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'blocked_users' => $blocked]);
            break;

        case 'check':
            $targetId = (int) ($_GET['user_id'] ?? 0);
            if ($targetId === 0) {
                throw new Exception('ID invalide');
            }

            $stmt = $pdo->prepare("SELECT id FROM blocks WHERE blocker_id = ? AND blocked_id = ?");
            $stmt->execute([$userId, $targetId]);
            $isBlocked = (bool) $stmt->fetch();

            echo json_encode(['success' => true, 'is_blocked' => $isBlocked]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}