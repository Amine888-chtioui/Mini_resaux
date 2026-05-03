<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Démarrer session
session_start();

// Mode développement: forcer l'utilisateur 1 si non connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

function ensureGroupsSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_group_user (group_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $roleCol = $pdo->query("SHOW COLUMNS FROM group_members LIKE 'role'");
    if ($roleCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE group_members ADD COLUMN role ENUM('admin','member') NOT NULL DEFAULT 'member' AFTER user_id");
    }
}

function isGroupAdmin(PDO $pdo, int $groupId, int $userId): bool
{
    $stmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    $role = $stmt->fetchColumn();
    return $role === 'admin';
}

function detectGroupsCreatorColumn(PDO $pdo): string
{
    $creatorIdCol = $pdo->query("SHOW COLUMNS FROM groups LIKE 'creator_id'");
    if ($creatorIdCol->rowCount() > 0) {
        return 'creator_id';
    }

    $createdByCol = $pdo->query("SHOW COLUMNS FROM groups LIKE 'created_by'");
    if ($createdByCol->rowCount() > 0) {
        return 'created_by';
    }

    throw new Exception("La table groups doit contenir creator_id ou created_by");
}

function areFriends(PDO $pdo, int $userA, int $userB): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM friendships
        WHERE status = 'accepted'
          AND ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
        LIMIT 1
    ");
    $stmt->execute([$userA, $userB, $userB, $userA]);
    return (bool) $stmt->fetchColumn();
}

try {
    ensureGroupsSchema($pdo);

    switch ($action) {
        case 'list':
            // Lister les groupes de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT g.*,
                       (SELECT COUNT(*) FROM group_members gmc WHERE gmc.group_id = g.id) as member_count,
                       gm.role,
                       1 as is_member
                FROM groups g
                JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                ORDER BY g.created_at DESC
            ");
            $stmt->execute([$userId]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'groups' => $groups]);
            break;

        case 'discover':
            // Découvrir d'autres groupes
            $stmt = $pdo->prepare("
                SELECT g.*, 
                       (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) as member_count,
                       gm.user_id IS NOT NULL as is_member
                FROM groups g
                LEFT JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = ?
                WHERE g.id NOT IN (
                    SELECT group_id FROM group_members WHERE user_id = ?
                )
                ORDER BY g.created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$userId, $userId]);
            $discoverGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'groups' => $discoverGroups]);
            break;

        case 'join':
            // Récupérer l'ID depuis JSON ou POST
            $input = json_decode(file_get_contents('php://input'), true);
            $groupId = (int) ($input['group_id'] ?? $_POST['group_id'] ?? 0);
            
            if ($groupId === 0) {
                echo json_encode(['success' => false, 'error' => 'ID groupe invalide']);
                exit;
            }
            
            // Vérifier si déjà membre
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            $count = $stmt->fetch();
            
            if ($count['count'] > 0) {
                echo json_encode(['success' => false, 'error' => 'Déjà membre de ce groupe']);
                exit;
            }
            
            // Ajouter comme membre
            $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
            $stmt->execute([$groupId, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Groupe rejoint avec succès']);
            break;

        case 'leave':
            // Récupérer l'ID depuis JSON ou POST
            $input = json_decode(file_get_contents('php://input'), true);
            $groupId = (int) ($input['group_id'] ?? $_POST['group_id'] ?? 0);
            
            if ($groupId === 0) {
                echo json_encode(['success' => false, 'error' => 'ID groupe invalide']);
                exit;
            }
            
            // Supprimer le membre
            $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->execute([$groupId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'error' => 'Vous n\'êtes pas membre de ce groupe']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Groupe quitté avec succès']);
            break;

        case 'create':
            // Créer un groupe
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icon = trim($_POST['icon'] ?? 'ri-group-fill');
            $color = trim($_POST['color'] ?? '#1877f2');
            $privacy = $_POST['privacy'] ?? 'public';

            if (empty($name)) {
                echo json_encode(['success' => false, 'error' => 'Nom du groupe requis']);
                exit;
            }

            // Créer le groupe
            $slug = strtolower(str_replace(' ', '-', $name)) . '-' . time();
            $creatorColumn = detectGroupsCreatorColumn($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO groups (name, slug, description, icon, color, privacy, {$creatorColumn})
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $description, $icon, $color, $privacy, $userId]);
            $groupId = $pdo->lastInsertId();

            // Ajouter le créateur comme membre
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO group_members (group_id, user_id, role)
                    VALUES (?, ?, 'admin')
                ");
                $stmt->execute([$groupId, $userId]);
            } catch (Exception $e) {
                // Si l'insertion échoue, on continue quand même
            }

            echo json_encode(['success' => true, 'group_id' => $groupId, 'message' => 'Groupe créé']);
            break;

        case 'members':
            // Lister les membres d'un groupe
            $groupId = (int) ($_GET['group_id'] ?? 0);
            if ($groupId === 0) {
                echo json_encode(['success' => false, 'error' => 'ID groupe invalide']);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT u.id, u.username, u.bio, gm.role, gm.joined_at
                FROM group_members gm
                JOIN users u ON u.id = gm.user_id
                WHERE gm.group_id = ?
                ORDER BY (gm.role = 'admin') DESC, u.username
            ");
            $stmt->execute([$groupId]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'members' => $members]);
            break;

        case 'rename':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $groupId = (int) ($input['group_id'] ?? $_POST['group_id'] ?? 0);
            $newName = trim((string) ($input['name'] ?? $_POST['name'] ?? ''));

            if ($groupId === 0 || $newName === '') {
                echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                exit;
            }

            if (!isGroupAdmin($pdo, $groupId, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Seul un admin peut renommer le groupe']);
                exit;
            }

            if (mb_strlen($newName) > 100) {
                echo json_encode(['success' => false, 'error' => 'Nom trop long (100 caractères max)']);
                exit;
            }

            $newSlug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $newName), '-')) . '-' . time();
            $update = $pdo->prepare("UPDATE groups SET name = ?, slug = ? WHERE id = ?");
            $update->execute([$newName, $newSlug, $groupId]);

            echo json_encode(['success' => true, 'message' => 'Nom du groupe mis à jour']);
            break;

        case 'add_member':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $groupId = (int) ($input['group_id'] ?? $_POST['group_id'] ?? 0);
            $targetUserId = (int) ($input['user_id'] ?? $_POST['user_id'] ?? 0);
            $targetUsername = trim((string) ($input['username'] ?? $_POST['username'] ?? ''));

            if ($groupId === 0) {
                echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                exit;
            }

            if (!isGroupAdmin($pdo, $groupId, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Seul un admin peut ajouter des membres']);
                exit;
            }

            if ($targetUserId === 0 && $targetUsername === '') {
                echo json_encode(['success' => false, 'error' => 'Nom d\'utilisateur requis']);
                exit;
            }

            if ($targetUserId === 0) {
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $checkUser->execute([$targetUsername]);
                $found = $checkUser->fetch(PDO::FETCH_ASSOC);
                if (!$found) {
                    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
                    exit;
                }
                $targetUserId = (int) $found['id'];
            } else {
                $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                $checkUser->execute([$targetUserId]);
                $found = $checkUser->fetch(PDO::FETCH_ASSOC);
                if (!$found) {
                    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
                    exit;
                }
            }

            if ($targetUserId === $userId) {
                echo json_encode(['success' => false, 'error' => 'Vous êtes déjà dans le groupe']);
                exit;
            }

            if (!areFriends($pdo, $userId, $targetUserId)) {
                echo json_encode(['success' => false, 'error' => 'Vous pouvez ajouter seulement vos amis']);
                exit;
            }

            $insert = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
            $insert->execute([$groupId, $targetUserId]);

            echo json_encode(['success' => true, 'message' => 'Membre ajouté']);
            break;

        case 'remove_member':
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $groupId = (int) ($input['group_id'] ?? $_POST['group_id'] ?? 0);
            $targetUserId = (int) ($input['user_id'] ?? $_POST['user_id'] ?? 0);

            if ($groupId === 0 || $targetUserId === 0) {
                echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                exit;
            }

            if (!isGroupAdmin($pdo, $groupId, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Seul un admin peut supprimer des membres']);
                exit;
            }

            if ($targetUserId === $userId) {
                echo json_encode(['success' => false, 'error' => 'Un admin ne peut pas se supprimer lui-même']);
                exit;
            }

            $targetRoleStmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
            $targetRoleStmt->execute([$groupId, $targetUserId]);
            $targetRole = $targetRoleStmt->fetchColumn();
            if ($targetRole === false) {
                echo json_encode(['success' => false, 'error' => 'Membre introuvable']);
                exit;
            }

            if ($targetRole === 'admin') {
                echo json_encode(['success' => false, 'error' => 'Impossible de supprimer un autre admin']);
                exit;
            }

            $delete = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $delete->execute([$groupId, $targetUserId]);

            echo json_encode(['success' => true, 'message' => 'Membre supprimé']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
