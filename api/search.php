<?php
/**
 * api/search.php — Recherche globale d'utilisateurs pour la topbar
 * Endpoint simple, robuste, sans dépendance à la table `blocks`.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

/* ── Récupérer l'ID de l'utilisateur connecté (0 si non connecté) ── */
$viewerId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

/* ── Paramètre de recherche ── */
$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

$like = '%' . $q . '%';

try {
    if ($viewerId > 0) {
        /*
         * Utilisateur connecté :
         * - On exclut l'utilisateur lui-même
         * - On inclut le statut d'amitié si disponible
         * - On tente d'exclure les utilisateurs bloqués (ignore l'erreur si la table n'existe pas)
         */
        $sql = "
            SELECT
                u.id,
                u.username,
                u.bio,
                CASE
                    WHEN f_accepted.id IS NOT NULL THEN 'friend'
                    WHEN f_pending.id  IS NOT NULL THEN 'pending'
                    WHEN f_received.id IS NOT NULL THEN 'received'
                    ELSE 'none'
                END AS friendship_status
            FROM users u
            LEFT JOIN friendships f_accepted
                ON ((f_accepted.user_id = :me1 AND f_accepted.friend_id = u.id)
                 OR (f_accepted.user_id = u.id AND f_accepted.friend_id = :me2))
                AND f_accepted.status = 'accepted'
            LEFT JOIN friendships f_pending
                ON f_pending.user_id = :me3
                AND f_pending.friend_id = u.id
                AND f_pending.status = 'pending'
            LEFT JOIN friendships f_received
                ON f_received.user_id = u.id
                AND f_received.friend_id = :me4
                AND f_received.status = 'pending'
            WHERE u.id != :me5
              AND u.username LIKE :like
            ORDER BY
                CASE WHEN f_accepted.id IS NOT NULL THEN 0 ELSE 1 END,
                u.username
            LIMIT 10
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':me1'  => $viewerId,
            ':me2'  => $viewerId,
            ':me3'  => $viewerId,
            ':me4'  => $viewerId,
            ':me5'  => $viewerId,
            ':like' => $like,
        ]);
    } else {
        /* Utilisateur non connecté : recherche simple */
        $stmt = $pdo->prepare("
            SELECT id, username, bio, 'none' AS friendship_status
            FROM users
            WHERE username LIKE :like
            ORDER BY username
            LIMIT 10
        ");
        $stmt->execute([':like' => $like]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Nettoyer les champs sensibles */
    $users = array_map(function (array $u): array {
        return [
            'id'                => (int) $u['id'],
            'username'          => $u['username'],
            'bio'               => $u['bio'] ?? null,
            'friendship_status' => $u['friendship_status'] ?? 'none',
        ];
    }, $users);

    echo json_encode(['success' => true, 'users' => $users]);

} catch (\PDOException $e) {
    /* Log discret, réponse propre */
    error_log('[api/search.php] PDOException: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
} catch (\Throwable $e) {
    error_log('[api/search.php] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur.']);
}