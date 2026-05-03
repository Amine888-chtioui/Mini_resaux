<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Vérifier session
session_start();
if (!isset($_SESSION['user_id'])) {
    // Mode développement: utiliser l'utilisateur 1 par défaut
    error_log("API Notifications: Session non définie - utilisation de l'utilisateur 1 par défaut");
    $_SESSION['user_id'] = 1;
    // En production, décommentez les lignes suivantes:
    // http_response_code(401);
    // exit('Non autorisé');
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'list':
            // Lister les notifications
            $limit = (int) ($_GET['limit'] ?? 20);
            $offset = (int) ($_GET['offset'] ?? 0);

            $stmt = $pdo->prepare("
                SELECT n.*, 
                       CASE 
                           WHEN n.type IN ('friend_request', 'friend_accept') THEN 
                               (SELECT username FROM users WHERE id = (
                                   CASE 
                                       WHEN n.type = 'friend_request' THEN 
                                           (SELECT user_id FROM friendships WHERE friend_id = ? AND created_at = n.created_at LIMIT 1)
                                       ELSE 
                                           (SELECT friend_id FROM friendships WHERE user_id = ? AND created_at = n.created_at LIMIT 1)
                                   END
                               ))
                           WHEN n.type = 'message' THEN 
                               (SELECT username FROM users WHERE id = (
                                   SELECT sender_id FROM messages WHERE receiver_id = ? AND created_at = n.created_at LIMIT 1
                               ))
                           ELSE NULL
                       END as actor_name
                FROM notifications n
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $userId, $userId, $userId, $limit, $offset]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;

        case 'count':
            // Compter les notifications non lues
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as unread_count 
                FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            $count = $stmt->fetch();

            echo json_encode(['success' => true, 'unread_count' => (int) $count['unread_count']]);
            break;

        case 'mark_read':
            // Marquer une notification comme lue
            $notifId = (int) ($_POST['notification_id'] ?? 0);
            if ($notifId === 0) {
                // Marquer toutes comme lues
                $stmt = $pdo->prepare("
                    UPDATE notifications 
                    SET is_read = TRUE 
                    WHERE user_id = ? AND is_read = FALSE
                ");
                $stmt->execute([$userId]);
                echo json_encode(['success' => true, 'message' => 'Toutes les notifications marquées comme lues']);
            } else {
                // Marquer une notification spécifique
                $stmt = $pdo->prepare("
                    UPDATE notifications 
                    SET is_read = TRUE 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$notifId, $userId]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Notification introuvable');
                }

                echo json_encode(['success' => true, 'message' => 'Notification marquée comme lue']);
            }
            break;

        case 'clear':
            // Supprimer toutes les notifications lues
            $stmt = $pdo->prepare("
                DELETE FROM notifications 
                WHERE user_id = ? AND is_read = TRUE
            ");
            $stmt->execute([$userId]);
            $deleted = $stmt->rowCount();

            echo json_encode(['success' => true, 'deleted_count' => $deleted]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
