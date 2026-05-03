<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($userId < 1) {
    http_response_code(404);
    exit('Utilisateur introuvable.');
}

require_once __DIR__ . '/includes/db.php';

$viewerId       = (int) ($_SESSION['user_id'] ?? 0);
$isOwn          = $viewerId === $userId;
$viewerUsername = (string) ($_SESSION['username'] ?? '');
$viewerInitial  = $viewerUsername !== ''
    ? mb_strtoupper(mb_substr($viewerUsername, 0, 1, 'UTF-8'), 'UTF-8')
    : 'U';

/* ── Sauvegarde bio ──────────────────────────────────────────────────────── */
if ($isOwn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bio'])) {
    $bio = trim((string) ($_POST['bio'] ?? ''));
    if (strlen($bio) <= 500) {
        $pdo->prepare('UPDATE users SET bio = ? WHERE id = ?')
            ->execute([$bio === '' ? null : $bio, $userId]);
    }
    header('Location: ' . BASE_URL . '/profile.php?id=' . $userId);
    exit;
}

/* ── Suppression de post ────────────────────────────────────────────────── */
if ($isOwn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $delId = (int) ($_POST['post_id'] ?? 0);
    if ($delId > 0) {
        $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?')
            ->execute([$delId, $userId]);
    }
    header('Location: ' . BASE_URL . '/profile.php?id=' . $userId);
    exit;
}

/* ── Données profil ─────────────────────────────────────────────────────── */
$stmt = $pdo->prepare('SELECT id, username, bio, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch();
if (!$profile) {
    http_response_code(404);
    exit('Utilisateur introuvable.');
}

$pageTitle      = $profile['username'] . ' — Profil';
$profilePageUrl = BASE_URL . '/profile.php?id=' . $userId;
$initial        = mb_strtoupper(mb_substr($profile['username'], 0, 1, 'UTF-8'), 'UTF-8');
$memberSince    = date('F Y', strtotime($profile['created_at']));

/* ── Posts ──────────────────────────────────────────────────────────────── */
$pst = $pdo->prepare(
    'SELECT p.id, p.body, p.image_path, p.created_at,
            (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
            EXISTS(SELECT 1 FROM post_likes pl WHERE pl.post_id = p.id AND pl.user_id = ?) AS liked_by_me
     FROM posts p
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC
     LIMIT 50'
);
$pst->execute([$viewerId, $userId]);
$posts = $pst->fetchAll();

/* ── Vrais amis depuis la DB ─────────────────────────────────────────────── */
$friendsStmt = $pdo->prepare("
    SELECT u.id, u.username
    FROM friendships f
    JOIN users u ON u.id = CASE
        WHEN f.user_id = ? THEN f.friend_id
        ELSE f.user_id
    END
    WHERE (f.user_id = ? OR f.friend_id = ?)
      AND f.status = 'accepted'
    ORDER BY u.username
");
$friendsStmt->execute([$userId, $userId, $userId]);
$realFriends = $friendsStmt->fetchAll();

/* ── Palette avatars ────────────────────────────────────────────────────── */
$avatarColors = [
    '#1877f2','#42b72a','#e53935','#f7981c',
    '#8e24aa','#00897b','#1565d8','#d81b60','#558b2f',
];
function friendAvatarColor(string $name, array $colors): string {
    return $colors[ord($name[0]) % count($colors)];
}

/* ── Messages non lus ───────────────────────────────────────────────────── */
$unreadMsgCount = 0;
if ($viewerId > 0) {
    $msgCountStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = FALSE");
    $msgCountStmt->execute([$viewerId]);
    $unreadMsgCount = (int) $msgCountStmt->fetchColumn();
}

/* ── Notifications non lues ─────────────────────────────────────────────── */
$unreadNotifCount = 0;
if ($viewerId > 0) {
    $notifCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $notifCountStmt->execute([$viewerId]);
    $unreadNotifCount = (int) $notifCountStmt->fetchColumn();
}

/* ── Notifications récentes ─────────────────────────────────────────────── */
$notifications = [];
if ($viewerId > 0) {
    $notifStmt = $pdo->prepare("
        SELECT type, title, message, created_at, is_read
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $notifStmt->execute([$viewerId]);
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ── Groupes réels ──────────────────────────────────────────────────────── */
$groupsData = [];
try {
    $grpStmt = $pdo->prepare("
        SELECT g.id, g.name, g.icon, g.color,
               (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id = g.id) AS member_count
        FROM groups g
        JOIN group_members gm ON gm.group_id = g.id
        WHERE gm.user_id = ?
        ORDER BY g.name
        LIMIT 9
    ");
    $grpStmt->execute([$userId]);
    $groupsData = $grpStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    // table groups peut ne pas exister encore
    $groupsData = [];
}

/* ── Fallback groups si table vide ─────────────────────────────────────── */
if (empty($groupsData)) {
    $groupsData = [
        ['id'=>0,'name'=>'Groupe Lycée',      'icon'=>'ri-building-4-fill',  'color'=>'#1877f2','member_count'=>128],
        ['id'=>0,'name'=>'Club Informatique',  'icon'=>'ri-code-box-fill',    'color'=>'#42b72a','member_count'=>47],
        ['id'=>0,'name'=>'Sport & Santé',      'icon'=>'ri-football-fill',    'color'=>'#f7981c','member_count'=>89],
        ['id'=>0,'name'=>'Musique',             'icon'=>'ri-music-fill',       'color'=>'#e53935','member_count'=>34],
        ['id'=>0,'name'=>'Cinéma',              'icon'=>'ri-film-fill',        'color'=>'#8e24aa','member_count'=>23],
        ['id'=>0,'name'=>'Théâtre',             'icon'=>'ri-theater-fill',     'color'=>'#d81b60','member_count'=>17],
    ];
}

/* ── Notification icon mapping ──────────────────────────────────────────── */
$notifIconMap = [
    'like'           => ['icon'=>'ri-thumb-up-fill',    'color'=>'#e53935'],
    'comment'        => ['icon'=>'ri-chat-1-fill',      'color'=>'#42b72a'],
    'friend_request' => ['icon'=>'ri-user-add-fill',    'color'=>'#1877f2'],
    'friend_accept'  => ['icon'=>'ri-user-follow-fill', 'color'=>'#f7981c'],
    'message'        => ['icon'=>'ri-message-2-fill',   'color'=>'#8e24aa'],
    'group_invite'   => ['icon'=>'ri-group-fill',       'color'=>'#00897b'],
    'post_tag'       => ['icon'=>'ri-at-fill',          'color'=>'#d81b60'],
];

$err = $_GET['err'] ?? '';
$tab = $_GET['tab'] ?? 'posts';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/css/profile.css">
</head>

<body class="profile-page">

<!-- ════════════════════════════════════════════════
     TOPBAR
════════════════════════════════════════════════ -->
<nav class="topbar">
    <div class="topbar-inner">

        <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/index.php" class="topbar-logo">
            <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>
        </a>

        <div class="topbar-search">
            <i class="ri-search-line"></i>
            <input type="text" placeholder="Rechercher…" autocomplete="off">
        </div>

        <div class="topbar-nav">

            <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/index.php"
               class="topbar-nav-icon" title="Accueil">
                <i class="ri-home-5-fill"></i>
            </a>

            <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/messages.php"
               class="topbar-nav-icon" title="Messages">
                <i class="ri-message-2-fill"></i>
                <?php if ($unreadMsgCount > 0): ?>
                    <span class="badge-dot"><?= $unreadMsgCount ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/groups.php"
               class="topbar-nav-icon" title="Groupes">
                <i class="ri-group-fill"></i>
            </a>

            <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/notifications.php"
               class="topbar-nav-icon" title="Notifications">
                <i class="ri-notification-3-fill"></i>
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="badge-dot"><?= $unreadNotifCount ?></span>
                <?php endif; ?>
            </a>

            <div class="dropdown">
                <button class="topbar-avatar-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="topbar-avatar"><?= htmlspecialchars($viewerInitial, ENT_QUOTES, 'UTF-8') ?></div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                    <li>
                        <a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL . '/profile.php?id=' . $viewerId, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="profile-dropdown-user">
                                <div class="profile-dropdown-avatar"><?= htmlspecialchars($viewerInitial, ENT_QUOTES, 'UTF-8') ?></div>
                                <div>
                                    <strong><?= htmlspecialchars($viewerUsername, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small>Voir votre profil</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL . '/settings.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-settings-3-fill me-2"></i>Paramètres</a></li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL . '/friends.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-user-3-fill me-2"></i>Amis</a></li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL . '/messages.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-message-2-fill me-2"></i>Messages</a></li>
                    <li><a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL . '/groups.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-group-fill me-2"></i>Groupes</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars(BASE_URL . '/auth/logout.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-logout-box-r-fill me-2"></i>Se déconnecter</a></li>
                </ul>
            </div>

        </div>
    </div>
</nav>

<!-- ════════════════════════════════════════════════
     COVER + PROFIL HERO
════════════════════════════════════════════════ -->
<div class="profile-cover-section">
    <div class="profile-cover-img" aria-hidden="true"></div>
    <div class="container profile-hero-container">
        <div class="profile-hero-row">

            <div class="profile-avatar-wrapper">
                <div class="profile-avatar-lg" aria-hidden="true">
                    <?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php if ($isOwn): ?>
                    <button class="avatar-edit-btn" title="Changer la photo"
                            data-bs-toggle="modal" data-bs-target="#modalEditProfile">
                        <i class="ri-camera-fill"></i>
                    </button>
                <?php endif; ?>
            </div>

            <div class="profile-hero-info">
                <h1 class="profile-hero-name"><?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="profile-hero-sub">
                    <i class="ri-time-line"></i> Membre depuis <?= $memberSince ?>
                    &nbsp;·&nbsp;
                    <i class="ri-map-pin-line"></i> <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?>
                    &nbsp;·&nbsp;
                    <i class="ri-user-3-line"></i> <?= count($realFriends) ?> ami<?= count($realFriends) > 1 ? 's' : '' ?>
                </p>
            </div>

            <div class="profile-hero-actions ms-auto">
                <?php if ($isOwn): ?>
                    <a href="<?= htmlspecialchars(BASE_URL . '/settings.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="btn btn-outline-primary btn-profile">
                        <i class="ri-pencil-fill"></i> Modifier le profil
                    </a>
                <?php else: ?>
                    <?php
                    // Vérifier le statut d'amitié avec le visiteur
                    $friendStatusStmt = $pdo->prepare("
                        SELECT id, status, user_id FROM friendships
                        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
                        LIMIT 1
                    ");
                    $friendStatusStmt->execute([$viewerId, $userId, $userId, $viewerId]);
                    $friendRow = $friendStatusStmt->fetch();
                    $friendStatus = $friendRow ? $friendRow['status'] : 'none';
                    $isSender = $friendRow && $friendRow['user_id'] == $viewerId;
                    ?>
                    <?php if ($friendStatus === 'accepted'): ?>
                        <span class="btn btn-success btn-profile disabled">
                            <i class="ri-user-follow-fill"></i> Amis
                        </span>
                    <?php elseif ($friendStatus === 'pending' && $isSender): ?>
                        <span class="btn btn-outline-secondary btn-profile disabled">
                            <i class="ri-time-fill"></i> Demande envoyée
                        </span>
                    <?php elseif ($friendStatus === 'pending' && !$isSender): ?>
                        <button class="btn btn-primary btn-profile" onclick="acceptFriendRequest(<?= $friendRow['id'] ?>)">
                            <i class="ri-user-add-fill"></i> Accepter la demande
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-profile" onclick="sendFriendRequest(<?= $userId ?>)">
                            <i class="ri-user-add-fill"></i> Ajouter en ami
                        </button>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars(BASE_URL . '/messages.php?user_id=' . $userId, ENT_QUOTES, 'UTF-8') ?>"
                       class="btn btn-outline-secondary btn-profile ms-2">
                        <i class="ri-message-2-fill"></i> Message
                    </a>
                <?php endif; ?>
            </div>

        </div>

        <!-- Tabs -->
        <div class="profile-tabs">
            <a href="?id=<?= $userId ?>&tab=posts"
               class="profile-tab<?= ($tab === 'posts' || $tab === '') ? ' active' : '' ?>">Publications</a>
            <a href="?id=<?= $userId ?>&tab=about"
               class="profile-tab<?= $tab === 'about' ? ' active' : '' ?>">À propos</a>
            <a href="?id=<?= $userId ?>&tab=friends"
               class="profile-tab<?= $tab === 'friends' ? ' active' : '' ?>">
                Amis
                <?php if (count($realFriends) > 0): ?>
                    <span class="tab-badge"><?= count($realFriends) ?></span>
                <?php endif; ?>
            </a>
            <a href="?id=<?= $userId ?>&tab=photos"
               class="profile-tab<?= $tab === 'photos' ? ' active' : '' ?>">Photos</a>
            <a href="?id=<?= $userId ?>&tab=notifications"
               class="profile-tab<?= $tab === 'notifications' ? ' active' : '' ?>">
                Notifications
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="tab-badge"><?= $unreadNotifCount ?></span>
                <?php endif; ?>
            </a>
        </div>

    </div>
</div>

<!-- ════════════════════════════════════════════════
     CORPS PRINCIPAL
════════════════════════════════════════════════ -->
<div class="container profile-body">
    <div class="row g-4">

        <!-- ── COLONNE GAUCHE ───────────────────────────────────── -->
        <div class="col-lg-4">

            <!-- Intro -->
            <div class="pcard">
                <div class="pcard-head">
                    <h2 class="pcard-title">Intro</h2>
                </div>
                <div class="pcard-body">
                    <?php if (!empty($profile['bio'])): ?>
                        <p class="profile-bio-text">
                            <?= nl2br(htmlspecialchars((string) $profile['bio'], ENT_QUOTES, 'UTF-8')) ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($isOwn): ?>
                        <button class="btn btn-sm btn-outline-primary w-100 mt-2"
                                data-bs-toggle="modal" data-bs-target="#modalEditBio">
                            <i class="ri-pencil-line me-1"></i>
                            <?= empty($profile['bio']) ? 'Ajouter une bio' : 'Modifier la bio' ?>
                        </button>
                    <?php endif; ?>
                    <ul class="intro-list mt-3">
                        <li><i class="ri-time-fill"></i> Membre depuis <?= $memberSince ?></li>
                        <li><i class="ri-map-pin-fill"></i> <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?></li>
                        <li><i class="ri-user-3-fill"></i> <?= count($realFriends) ?> ami<?= count($realFriends) > 1 ? 's' : '' ?></li>
                    </ul>
                </div>
            </div>

            <!-- Groupes -->
            <div class="pcard">
                <div class="pcard-head d-flex justify-content-between align-items-center">
                    <h2 class="pcard-title mb-0">Groupes</h2>
                    <a href="<?= htmlspecialchars(BASE_URL . '/groups.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="pcard-link">Voir tout</a>
                </div>
                <div class="pcard-body">
                    <?php if (empty($groupsData)): ?>
                        <p class="text-muted small">Aucun groupe pour l'instant.</p>
                    <?php else: ?>
                        <div class="group-list">
                            <?php foreach (array_slice($groupsData, 0, 3) as $g): ?>
                                <div class="group-item">
                                    <div class="group-icon"
                                         style="background:<?= htmlspecialchars($g['color'], ENT_QUOTES, 'UTF-8') ?>20;
                                                color:<?= htmlspecialchars($g['color'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="<?= htmlspecialchars($g['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    </div>
                                    <div class="group-info">
                                        <strong><?= htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span><?= (int) $g['member_count'] ?> membres</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Amis (grille miniature — vrais amis) -->
            <div class="pcard">
                <div class="pcard-head d-flex justify-content-between align-items-center">
                    <h2 class="pcard-title mb-0">
                        Amis
                        <?php if (count($realFriends) > 0): ?>
                            <small class="text-muted fw-normal fs-6">(<?= count($realFriends) ?>)</small>
                        <?php endif; ?>
                    </h2>
                    <a href="?id=<?= $userId ?>&tab=friends" class="pcard-link">Voir tout</a>
                </div>
                <div class="pcard-body">
                    <?php if (empty($realFriends)): ?>
                        <p class="text-muted small text-center py-2">Aucun ami pour l'instant.</p>
                    <?php else: ?>
                        <div class="friends-grid">
                            <?php foreach (array_slice($realFriends, 0, 9) as $f): ?>
                                <div class="friend-thumb">
                                    <a href="<?= htmlspecialchars(BASE_URL . '/profile.php?id=' . $f['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="friend-avatar"
                                             style="background:<?= friendAvatarColor($f['username'], $avatarColors) ?>">
                                            <?= strtoupper(mb_substr($f['username'], 0, 1)) ?>
                                        </div>
                                    </a>
                                    <span><?= htmlspecialchars($f['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.col gauche -->

        <!-- ── COLONNE DROITE ──────────────────────────────────── -->
        <div class="col-lg-8">

            <!-- Alertes erreur -->
            <?php if ($err === 'empty'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-fill me-2"></i>Écrivez quelque chose avant de publier.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($err === 'image'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-fill me-2"></i>Format d'image non autorisé (JPEG, PNG, GIF, WebP).
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($err === 'upload'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="ri-error-warning-fill me-2"></i>Échec du téléversement de l'image.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ══ TAB : PUBLICATIONS ══════════════════════════════ -->
            <?php if ($tab === 'posts' || $tab === ''): ?>

                <?php if ($isOwn): ?>
                    <div class="pcard composer-card">
                        <div class="pcard-body">
                            <div class="composer-top-row">
                                <div class="composer-av"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                                <button class="composer-trigger"
                                        data-bs-toggle="modal" data-bs-target="#modalCompose">
                                    Que voulez-vous partager,
                                    <?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?> ?
                                </button>
                            </div>
                            <hr class="composer-divider">
                            <div class="composer-shortcuts">
                                <button class="composer-shortcut"
                                        data-bs-toggle="modal" data-bs-target="#modalCompose">
                                    <i class="ri-image-fill" style="color:#42b72a"></i> Photo
                                </button>
                                <button class="composer-shortcut"
                                        data-bs-toggle="modal" data-bs-target="#modalCompose">
                                    <i class="ri-video-fill" style="color:#e53935"></i> Vidéo
                                </button>
                                <button class="composer-shortcut"
                                        data-bs-toggle="modal" data-bs-target="#modalCompose">
                                    <i class="ri-emotion-fill" style="color:#f7981c"></i> Humeur
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="timeline" id="timeline">
                    <?php if (empty($posts)): ?>
                        <div class="pcard empty-state">
                            <i class="ri-quill-pen-line"></i>
                            <p>Aucune publication pour l'instant.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="pcard post-card" id="post-<?= (int) $post['id'] ?>">

                                <div class="pcard-body">
                                    <div class="post-head">
                                        <div class="post-av"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="post-meta">
                                            <strong><?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <time datetime="<?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="ri-time-line"></i>
                                                <?= htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                                &nbsp;<i class="ri-earth-fill" title="Public"></i>
                                            </time>
                                        </div>
                                        <?php if ($isOwn): ?>
                                            <div class="ms-auto dropdown">
                                                <button class="post-menu-btn dropdown-toggle"
                                                        data-bs-toggle="dropdown">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button class="dropdown-item"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#modalEditPost"
                                                                data-id="<?= (int) $post['id'] ?>"
                                                                data-body="<?= htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="ri-pencil-fill me-2 text-primary"></i>Modifier
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <form method="post"
                                                              action="<?= htmlspecialchars($profilePageUrl, ENT_QUOTES, 'UTF-8') ?>"
                                                              onsubmit="return confirm('Supprimer ce post ?')">
                                                            <input type="hidden" name="delete_post" value="1">
                                                            <input type="hidden" name="post_id"
                                                                   value="<?= (int) $post['id'] ?>">
                                                            
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($post['body'])): ?>
                                        <p class="post-body">
                                            <?= nl2br(htmlspecialchars($post['body'], ENT_QUOTES, 'UTF-8')) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($post['image_path'])): ?>
                                    <div class="post-media">
                                        <img src="<?= htmlspecialchars(BASE_URL . '/uploads/' . basename($post['image_path']), ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Photo de <?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                <?php endif; ?>

                                <div class="pcard-body post-stats-row">
                                    <?php if ($post['like_count'] > 0): ?>
                                        <span class="post-stat-likes">
                                            <span class="like-bubble"><i class="ri-thumb-up-fill"></i></span>
                                            <?= (int) $post['like_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($viewerId > 0): ?>
                                    <div class="post-actions-bar">
                                        <form class="inline-like" method="post"
                                              action="<?= htmlspecialchars(BASE_URL . '/posts/like.php', ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                                            <input type="hidden" name="redirect"
                                                   value="<?= htmlspecialchars($profilePageUrl, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit"
                                                    class="post-action-btn<?= !empty($post['liked_by_me']) ? ' liked' : '' ?>">
                                                <i class="ri-thumb-up-<?= !empty($post['liked_by_me']) ? 'fill' : 'line' ?>"></i>
                                                J'aime
                                            </button>
                                        </form>
                                        <button class="post-action-btn" type="button"
                                                onclick="toggleComments(<?= (int) $post['id'] ?>)">
                                            <i class="ri-chat-1-line"></i> Commenter
                                        </button>
                                        <button class="post-action-btn" type="button"
                                                onclick='openSharePostModal(<?= (int) $post["id"] ?>, <?= json_encode((string) $profile["username"], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>
                                            <i class="ri-share-forward-line"></i> Partager
                                        </button>
                                    </div>

                                    <div class="post-comments" id="comments-<?= (int) $post['id'] ?>">
                                        <?php
                                        $cstmt = $pdo->prepare(
                                            'SELECT c.body, c.created_at, u.username
                                             FROM comments c
                                             INNER JOIN users u ON u.id = c.user_id
                                             WHERE c.post_id = ? ORDER BY c.created_at ASC'
                                        );
                                        $cstmt->execute([(int) $post['id']]);
                                        foreach ($cstmt->fetchAll() as $c):
                                        ?>
                                            <div class="comment-item">
                                                <div class="comment-av">
                                                    <?= strtoupper(substr($c['username'], 0, 1)) ?>
                                                </div>
                                                <div class="comment-bubble">
                                                    <strong><?= htmlspecialchars($c['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span><?= nl2br(htmlspecialchars($c['body'], ENT_QUOTES, 'UTF-8')) ?></span>
                                                    <time><?= htmlspecialchars($c['created_at'], ENT_QUOTES, 'UTF-8') ?></time>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="comment-write-row">
                                            <div class="comment-av">
                                                <?= htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <form method="post"
                                                  action="<?= htmlspecialchars(BASE_URL . '/posts/comment.php', ENT_QUOTES, 'UTF-8') ?>"
                                                  class="comment-form">
                                                <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                                                <input type="hidden" name="redirect"
                                                       value="<?= htmlspecialchars($profilePageUrl, ENT_QUOTES, 'UTF-8') ?>">
                                                <div class="comment-input-row">
                                                    <input type="text" name="body" maxlength="2000"
                                                           placeholder="Écrire un commentaire…" required autocomplete="off">
                                                    <button type="submit"><i class="ri-send-plane-fill"></i></button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <!-- ══ TAB : NOTIFICATIONS ════════════════════════════ -->
            <?php elseif ($tab === 'notifications'): ?>
                <div class="pcard">
                    <div class="pcard-head d-flex justify-content-between align-items-center">
                        <h2 class="pcard-title mb-0">Notifications</h2>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="badge bg-danger"><?= $unreadNotifCount ?> non lue<?= $unreadNotifCount > 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="pcard-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="empty-state py-4">
                                <i class="ri-notification-off-line"></i>
                                <p>Aucune notification pour l'instant.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n):
                                $nc = $notifIconMap[$n['type']] ?? ['icon'=>'ri-notification-fill','color'=>'#1877f2'];
                                $isUnread = !(bool) $n['is_read'];
                            ?>
                                <div class="notif-item<?= $isUnread ? ' unread' : '' ?>">
                                    <div class="notif-icon"
                                         style="background:<?= $nc['color'] ?>20; color:<?= $nc['color'] ?>">
                                        <i class="<?= $nc['icon'] ?>"></i>
                                    </div>
                                    <div class="notif-text">
                                        <p><?= htmlspecialchars($n['message'] ?? $n['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                        <time><?= htmlspecialchars($n['created_at'], ENT_QUOTES, 'UTF-8') ?></time>
                                    </div>
                                    <?php if ($isUnread): ?>
                                        <div class="notif-dot"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <div class="p-3 text-center border-top">
                                <a href="<?= htmlspecialchars(BASE_URL . '/notifications.php', ENT_QUOTES, 'UTF-8') ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="ri-notification-3-fill me-1"></i>Voir toutes les notifications
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- ══ TAB : À PROPOS ════════════════════════════════ -->
            <?php elseif ($tab === 'about'): ?>
                <div class="pcard">
                    <div class="pcard-head">
                        <h2 class="pcard-title">À propos</h2>
                    </div>
                    <div class="pcard-body">
                        <ul class="about-list">
                            <li><i class="ri-user-3-fill"></i>
                                <span><strong>Nom :</strong> <?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                            <li><i class="ri-time-fill"></i>
                                <span><strong>Membre depuis :</strong> <?= $memberSince ?></span>
                            </li>
                            <li><i class="ri-map-pin-fill"></i>
                                <span><strong>Lieu :</strong> <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                            <li><i class="ri-user-3-fill"></i>
                                <span><strong>Amis :</strong> <?= count($realFriends) ?></span>
                            </li>
                        </ul>
                        <?php if (!empty($profile['bio'])): ?>
                            <hr>
                            <p class="profile-bio-text">
                                <?= nl2br(htmlspecialchars((string) $profile['bio'], ENT_QUOTES, 'UTF-8')) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- ══ TAB : AMIS (vrais amis) ════════════════════════ -->
            <?php elseif ($tab === 'friends'): ?>
                <div class="pcard">
                    <div class="pcard-head d-flex justify-content-between align-items-center">
                        <h2 class="pcard-title mb-0">
                            Amis
                            <?php if (count($realFriends) > 0): ?>
                                <small class="text-muted fw-normal fs-6">(<?= count($realFriends) ?>)</small>
                            <?php endif; ?>
                        </h2>
                        <?php if ($isOwn): ?>
                            <a href="<?= htmlspecialchars(BASE_URL . '/friends.php', ENT_QUOTES, 'UTF-8') ?>"
                               class="btn btn-sm btn-primary">
                                <i class="ri-user-add-fill me-1"></i>Gérer les amis
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="pcard-body">
                        <?php if (empty($realFriends)): ?>
                            <div class="empty-state">
                                <i class="ri-user-3-line"></i>
                                <p>Aucun ami pour l'instant.</p>
                                <?php if ($isOwn): ?>
                                    <a href="<?= htmlspecialchars(BASE_URL . '/friends.php', ENT_QUOTES, 'UTF-8') ?>"
                                       class="btn btn-primary mt-3">
                                        <i class="ri-search-line me-1"></i>Trouver des amis
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="friends-grid-lg">
                                <?php foreach ($realFriends as $f): ?>
                                    <div class="friend-card">
                                        <a href="<?= htmlspecialchars(BASE_URL . '/profile.php?id=' . $f['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <div class="friend-card-av"
                                                 style="background:<?= friendAvatarColor($f['username'], $avatarColors) ?>">
                                                <?= strtoupper(mb_substr($f['username'], 0, 1)) ?>
                                            </div>
                                        </a>
                                        <strong><?= htmlspecialchars($f['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <a href="<?= htmlspecialchars(BASE_URL . '/messages.php?user_id=' . $f['id'], ENT_QUOTES, 'UTF-8') ?>"
                                           class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="ri-message-2-fill me-1"></i>Message
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- ══ TAB : PHOTOS ══════════════════════════════════ -->
            <?php elseif ($tab === 'photos'): ?>
                <div class="pcard">
                    <div class="pcard-head">
                        <h2 class="pcard-title">Photos</h2>
                    </div>
                    <div class="pcard-body">
                        <?php
                        $photoStmt = $pdo->prepare(
                            'SELECT image_path FROM posts WHERE user_id = ? AND image_path IS NOT NULL
                             ORDER BY created_at DESC LIMIT 12'
                        );
                        $photoStmt->execute([$userId]);
                        $photos = $photoStmt->fetchAll();
                        ?>
                        <?php if (empty($photos)): ?>
                            <div class="empty-state">
                                <i class="ri-image-2-line"></i>
                                <p>Aucune photo publiée.</p>
                            </div>
                        <?php else: ?>
                            <div class="photo-grid">
                                <?php foreach ($photos as $ph): ?>
                                    <div class="photo-thumb">
                                        <img src="<?= htmlspecialchars(BASE_URL . '/uploads/' . basename($ph['image_path']), ENT_QUOTES, 'UTF-8') ?>"
                                             alt="">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /.col droite -->
    </div><!-- /.row -->
</div><!-- /.container -->


<!-- ════════════════════════════════════════════════
     OFFCANVAS — GROUPES
════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end offcanvas-custom" tabindex="-1" id="offcanvasGroups">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="ri-group-fill me-2"></i>Groupes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="group-list-full">
            <?php foreach ($groupsData as $g): ?>
                <div class="group-full-item">
                    <div class="group-icon-lg"
                         style="background:<?= htmlspecialchars($g['color'], ENT_QUOTES, 'UTF-8') ?>20;
                                color:<?= htmlspecialchars($g['color'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="<?= htmlspecialchars($g['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div class="group-full-info">
                        <strong><?= htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= (int) $g['member_count'] ?> membres</span>
                    </div>
                    <a href="<?= htmlspecialchars(BASE_URL . '/groups.php', ENT_QUOTES, 'UTF-8') ?>"
                       class="btn btn-sm btn-primary ms-auto">Voir</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════════
     MODAL — BIO
════════════════════════════════════════════════ -->
<?php if ($isOwn): ?>
<div class="modal fade" id="modalEditBio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-pencil-fill me-2"></i>Modifier la bio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="<?= htmlspecialchars($profilePageUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <textarea name="bio" rows="4" maxlength="500"
                              class="form-control mb-3"
                              placeholder="Décrivez-vous en quelques mots…"><?= htmlspecialchars((string) $profile['bio'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="save_bio" value="1" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL — COMPOSER (créer un post)
════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCompose" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom">
            <div class="modal-header">
                <h5 class="modal-title">Créer une publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post"
                      action="<?= htmlspecialchars(BASE_URL . '/posts/create.php', ENT_QUOTES, 'UTF-8') ?>"
                      enctype="multipart/form-data" id="composeForm">
                    <input type="hidden" name="redirect"
                           value="<?= htmlspecialchars($profilePageUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="compose-user-row">
                        <div class="compose-av"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                        <div>
                            <strong><?= htmlspecialchars($profile['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="badge-public"><i class="ri-earth-fill"></i> Public</span>
                        </div>
                    </div>
                    <textarea name="body" rows="4" maxlength="10000"
                              placeholder="Que voulez-vous partager ?"
                              class="compose-textarea" id="composeBody"></textarea>

                    <div id="imgPreviewWrap" class="img-preview-wrap" style="display:none">
                        <img id="imgPreview" src="" alt="">
                        <button type="button" class="img-remove-btn" id="removeImg">
                            <i class="ri-close-circle-fill"></i>
                        </button>
                    </div>

                    <div class="compose-footer">
                        <div class="compose-add-row">
                            <span class="compose-add-label">Ajouter :</span>
                            <label class="compose-add-btn" title="Photo">
                                <i class="ri-image-fill" style="color:#42b72a"></i>
                                <input type="file" name="image" id="imageInput"
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       style="display:none">
                            </label>
                            <button type="button" class="compose-add-btn" title="Humeur">
                                <i class="ri-emotion-fill" style="color:#f7981c"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-publish"
                                id="publishBtn" disabled>Publier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     MODAL — MODIFIER UN POST
════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEditPost" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-pencil-fill me-2"></i>Modifier la publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post"
                      action="<?= htmlspecialchars(BASE_URL . '/posts/edit.php', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="post_id" id="editPostId">
                    <input type="hidden" name="redirect"
                           value="<?= htmlspecialchars($profilePageUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <textarea name="body" id="editPostBody" rows="4" maxlength="10000"
                              class="compose-textarea mb-3"
                              placeholder="Modifier votre publication…"></textarea>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     MODAL — PARTAGER UN POST
════════════════════════════════════════════════ -->
<div class="modal fade" id="modalSharePost" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-share-forward-fill me-2"></i>Partager la publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Choisissez un ami puis cliquez sur "Partager".</p>

                <textarea id="sharePostNote" class="form-control mb-3" rows="2" maxlength="300"
                          placeholder="Ajouter un petit message (optionnel)…"></textarea>

                <div id="shareFriendsList" class="list-group">
                    <div class="text-center py-3 text-muted">
                        <i class="ri-loader-4-line spin me-1"></i>Chargement des amis...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Activer/désactiver bouton Publier ─────────────── */
const composeBody = document.getElementById('composeBody');
const publishBtn  = document.getElementById('publishBtn');
const imageInput  = document.getElementById('imageInput');
const imgPreview  = document.getElementById('imgPreview');
const imgPreviewWrap = document.getElementById('imgPreviewWrap');
const removeImgBtn   = document.getElementById('removeImg');

function checkPublish() {
    if (!publishBtn) return;
    const hasText  = composeBody && composeBody.value.trim().length > 0;
    const hasImage = imageInput && imageInput.files && imageInput.files.length > 0;
    publishBtn.disabled = !(hasText || hasImage);
}
if (composeBody) composeBody.addEventListener('input', checkPublish);

/* ── Aperçu image ──────────────────────────────────── */
if (imageInput) {
    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                imgPreview.src = e.target.result;
                imgPreviewWrap.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
        checkPublish();
    });
}
if (removeImgBtn) {
    removeImgBtn.addEventListener('click', () => {
        imageInput.value = '';
        imgPreviewWrap.style.display = 'none';
        imgPreview.src = '';
        checkPublish();
    });
}

/* ── Modal modifier post ───────────────────────────── */
const modalEditPost = document.getElementById('modalEditPost');
if (modalEditPost) {
    modalEditPost.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('editPostId').value   = btn.dataset.id;
        document.getElementById('editPostBody').value = btn.dataset.body;
    });
}

/* ── Afficher/masquer commentaires ────────────────── */
function toggleComments(postId) {
    const el = document.getElementById('comments-' + postId);
    if (!el) return;
    el.classList.toggle('open');
    const input = el.querySelector('input[name="body"]');
    if (input && el.classList.contains('open')) input.focus();
}

/* ── Partager un post en message privé ───────────── */
let sharePostId = 0;
let sharePostAuthor = '';

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderShareFriends(friends) {
    const list = document.getElementById('shareFriendsList');
    if (!list) return;

    if (!friends.length) {
        list.innerHTML = `
            <div class="text-center py-3 text-muted">
                Aucun ami disponible pour le partage.
            </div>
        `;
        return;
    }

    list.innerHTML = friends.map(friend => `
        <div class="list-group-item d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="topbar-avatar" style="width:32px;height:32px;font-size:.85rem;">${escapeHtml(friend.username.charAt(0).toUpperCase())}</span>
                <strong>${escapeHtml(friend.username)}</strong>
            </div>
            <button class="btn btn-sm btn-primary js-share-friend-btn"
                    data-friend-id="${Number(friend.id)}"
                    data-friend-name="${escapeHtml(String(friend.username))}">
                Partager
            </button>
        </div>
    `).join('');

    list.querySelectorAll('.js-share-friend-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const friendId = Number(btn.dataset.friendId || 0);
            const friendName = btn.dataset.friendName || 'cet ami';
            sharePostToFriend(friendId, friendName, btn);
        });
    });
}

async function openSharePostModal(postId, authorName) {
    sharePostId = Number(postId) || 0;
    sharePostAuthor = String(authorName || '');

    const list = document.getElementById('shareFriendsList');
    if (list) {
        list.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="ri-loader-4-line spin me-1"></i>Chargement des amis...
            </div>
        `;
    }

    const modalEl = document.getElementById('modalSharePost');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    try {
        const response = await fetch('<?= BASE_URL ?>/api/friends.php?action=list');
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Impossible de charger vos amis');
        }
        renderShareFriends(result.friends || []);
    } catch (error) {
        if (list) {
            list.innerHTML = `
                <div class="text-center py-3 text-danger">
                    Erreur de chargement. Réessayez.
                </div>
            `;
        }
    }
}

async function sharePostToFriend(friendId, friendName, buttonEl) {
    if (!sharePostId) {
        showToast('Publication invalide.', 'danger');
        return;
    }
    if (!friendId) {
        showToast('Ami invalide.', 'danger');
        return;
    }

    if (buttonEl) {
        buttonEl.disabled = true;
        buttonEl.innerHTML = '<i class="ri-loader-4-line spin me-1"></i>Envoi...';
    }

    const note = (document.getElementById('sharePostNote')?.value || '').trim();
    const shareToken = `[POST_SHARE:${sharePostId}]`;
    const introText = `Publication partagée de ${sharePostAuthor || 'cet utilisateur'}`;
    const body = note ? `${note}\n\n${introText}\n${shareToken}` : `${introText}\n${shareToken}`;

    try {
        const response = await fetch('<?= BASE_URL ?>/api/messages.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                receiver_id: Number(friendId),
                body: body
            })
        });
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Envoi impossible');
        }

        showToast(`Publication partagée avec ${friendName}.`, 'success');
        const modalEl = document.getElementById('modalSharePost');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }
        const noteEl = document.getElementById('sharePostNote');
        if (noteEl) {
            noteEl.value = '';
        }
    } catch (error) {
        showToast(error.message || 'Erreur lors du partage.', 'danger');
        if (buttonEl) {
            buttonEl.disabled = false;
            buttonEl.textContent = 'Partager';
        }
    }
}

/* ── Composer trigger ─────────────────────────────── */
document.querySelectorAll('.composer-trigger').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('modalCompose'));
        modal.show();
        setTimeout(() => { if (composeBody) composeBody.focus(); }, 400);
    });
});

/* ── Envoyer une demande d'ami ────────────────────── */
async function sendFriendRequest(userId) {
    try {
        const r = await fetch('<?= BASE_URL ?>/api/friends.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const d = await r.json();
        if (d.success) {
            showToast('Demande d\'ami envoyée !', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.error || 'Erreur', 'danger');
        }
    } catch(e) { showToast('Erreur réseau', 'danger'); }
}

/* ── Accepter une demande d'ami ───────────────────── */
async function acceptFriendRequest(requestId) {
    try {
        const r = await fetch('<?= BASE_URL ?>/api/friends.php?action=accept', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ request_id: requestId })
        });
        const d = await r.json();
        if (d.success) {
            showToast('Vous êtes maintenant amis !', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(d.error || 'Erreur', 'danger');
        }
    } catch(e) { showToast('Erreur réseau', 'danger'); }
}

/* ── Toast notification ───────────────────────────── */
function showToast(message, type = 'info') {
    const el = document.createElement('div');
    el.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    el.style.cssText = 'top:76px;right:20px;z-index:9999;min-width:280px;box-shadow:0 4px 20px rgba(0,0,0,.15)';
    el.innerHTML = message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}
</script>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/js/topbar-search.js"></script>
</body>
</html>