<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/connexion.php');
    exit;
}

$userId   = (int)  $_SESSION['user_id'];
$username = $_SESSION['username'];
$initial  = mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8');
$pageTitle = 'Messages — ' . SITE_NAME;

$unreadMsgCount = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = FALSE");
    $s->execute([$userId]);
    $unreadMsgCount = (int) $s->fetchColumn();
} catch (\Exception $e) {}

$unreadNotifCount = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $s->execute([$userId]);
    $unreadNotifCount = (int) $s->fetchColumn();
} catch (\Exception $e) {}
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

    <style>
        html, body { height: 100%; overflow: hidden; }
        body.profile-page { padding-top: 56px; }

        .msg-layout {
            display: flex;
            height: calc(100vh - 56px);
            background: #f0f2f5;
        }

        /* ══ SIDEBAR ══════════════════════════════════════ */
        .msg-sidebar {
            width: 340px; min-width: 280px;
            background: #fff;
            border-right: 1px solid #dddfe2;
            display: flex; flex-direction: column; flex-shrink: 0;
        }
        .sidebar-header { padding: 16px; border-bottom: 1px solid #f0f2f5; flex-shrink: 0; }
        .sidebar-title  { font-size: 1.3rem; font-weight: 800; color: #1c1e21; margin-bottom: 12px; }

        .sidebar-search { position: relative; }
        .sidebar-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #65676b; pointer-events: none; }
        .sidebar-search input {
            width: 100%; padding: 9px 14px 9px 36px;
            background: #f0f2f5; border: none; border-radius: 999px;
            font-size: 0.88rem; color: #1c1e21; outline: none; font-family: inherit;
        }
        .sidebar-search input:focus { background: #e4e6eb; }

        .sidebar-tabs { display: flex; border-bottom: 1px solid #f0f2f5; flex-shrink: 0; }
        .sidebar-tab {
            flex: 1; padding: 10px; font-size: 0.82rem; font-weight: 700;
            text-align: center; cursor: pointer; color: #65676b;
            border-bottom: 3px solid transparent;
            transition: color .2s, border-color .2s;
            background: none; border-top: none; border-left: none; border-right: none;
        }
        .sidebar-tab:hover { color: #1877f2; }
        .sidebar-tab.active { color: #1877f2; border-bottom-color: #1877f2; }
        .sidebar-tab-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 16px; height: 16px; background: #e53935; color: #fff;
            font-size: 9px; font-weight: 700; border-radius: 999px; padding: 0 3px;
            margin-left: 4px; vertical-align: middle;
        }

        .contacts-list { flex: 1; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #dddfe2 transparent; }
        .contacts-section-label { padding: 10px 16px 4px; font-size: 0.73rem; font-weight: 700; color: #65676b; letter-spacing: .8px; text-transform: uppercase; }

        .contact-item {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 14px; cursor: pointer; transition: background .15s;
            border-radius: 10px; margin: 1px 6px;
        }
        .contact-item:hover  { background: #f2f3f5; }
        .contact-item.active { background: #e7f3ff; }
        .contact-item.unread { background: #f0f5ff; }

        .contact-av { width: 48px; height: 48px; border-radius: 50%; color: #fff; font-weight: 800; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .contact-info { flex: 1; min-width: 0; }
        .contact-name-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2px; }
        .contact-name { font-weight: 700; font-size: 0.92rem; color: #1c1e21; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
        .contact-time { font-size: 0.72rem; color: #65676b; white-space: nowrap; }
        .contact-preview { font-size: 0.82rem; color: #65676b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .contact-item.unread .contact-preview { color: #1c1e21; font-weight: 600; }
        .contact-unread-badge { min-width: 18px; height: 18px; background: #1877f2; color: #fff; font-size: 0.7rem; font-weight: 700; border-radius: 999px; padding: 0 4px; display: flex; align-items: center; justify-content: center; }
        .contact-friend-tag { font-size: 0.7rem; font-weight: 700; color: #42b72a; }

        /* ══ CHAT AREA ════════════════════════════════════ */
        .chat-area { flex: 1; display: flex; flex-direction: column; min-width: 0; background: #f0f2f5; }

        .chat-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #65676b; text-align: center; padding: 2rem; }
        .chat-empty-icon { width: 80px; height: 80px; border-radius: 50%; background: #e7f3ff; color: #1877f2; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        .chat-empty h5 { font-weight: 800; color: #1c1e21; margin-bottom: 0.5rem; }
        .chat-empty p  { font-size: 0.9rem; max-width: 280px; margin: 0 auto; }

        #chatActive { display: none; flex-direction: column; height: 100%; }

        .chat-header {
            background: #fff; padding: 12px 20px; border-bottom: 1px solid #dddfe2;
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .chat-hd-av { width: 42px; height: 42px; border-radius: 50%; color: #fff; font-weight: 800; font-size: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .chat-hd-info { flex: 1; }
        .chat-hd-name { font-weight: 800; font-size: 1rem; color: #1c1e21; }
        .chat-hd-status { font-size: 0.78rem; color: #42b72a; display: flex; align-items: center; gap: 4px; }
        .chat-hd-status::before { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #42b72a; }
        .chat-hd-btn { width: 36px; height: 36px; border-radius: 50%; background: none; border: none; color: #65676b; font-size: 1.15rem; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .2s; }
        .chat-hd-btn:hover { background: #f0f2f5; color: #1877f2; }

        .chat-messages { flex: 1; overflow-y: auto; padding: 20px 16px; display: flex; flex-direction: column; gap: 4px; scrollbar-width: thin; scrollbar-color: #dddfe2 transparent; }

        .msg-date-sep { text-align: center; margin: 12px 0; }
        .msg-date-sep span { background: rgba(0,0,0,.07); color: #65676b; font-size: 0.72rem; font-weight: 700; padding: 4px 12px; border-radius: 999px; }

        .msg-row { display: flex; gap: 8px; max-width: 72%; }
        .msg-row.mine   { align-self: flex-end; flex-direction: row-reverse; }
        .msg-row.theirs { align-self: flex-start; }

        .msg-av-sm { width: 30px; height: 30px; border-radius: 50%; color: #fff; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; align-self: flex-end; }
        .msg-row.mine .msg-av-sm { display: none; }

        .msg-bubble-wrap { display: flex; flex-direction: column; gap: 2px; }
        .msg-row.mine   .msg-bubble-wrap { align-items: flex-end; }
        .msg-row.theirs .msg-bubble-wrap { align-items: flex-start; }

        .msg-bubble {
            padding: 10px 14px; border-radius: 18px;
            font-size: 0.92rem; line-height: 1.45; word-break: break-word;
        }
        .msg-row.theirs .msg-bubble { background: #fff; color: #1c1e21; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .msg-row.mine   .msg-bubble { background: #1877f2; color: #fff; border-bottom-right-radius: 4px; }

        /* Bubble image */
        .msg-bubble.has-image { padding: 4px; background: transparent !important; box-shadow: none !important; }
        .msg-bubble.has-image img {
            max-width: 280px; max-height: 320px;
            border-radius: 14px; display: block; cursor: zoom-in;
            object-fit: cover;
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
        }
        .msg-row.mine .msg-bubble.has-image img { border-bottom-right-radius: 4px; }
        .msg-row.theirs .msg-bubble.has-image img { border-bottom-left-radius: 4px; }
        /* Image + texte */
        .msg-bubble.has-image.has-text { padding: 4px 4px 10px; }
        .msg-bubble.has-image.has-text .msg-caption { padding: 6px 10px 2px; font-size: 0.92rem; line-height: 1.4; }
        .msg-row.mine .msg-bubble.has-image.has-text { background: #1877f2 !important; color: #fff; padding: 4px 4px 10px; box-shadow: none !important; border-bottom-right-radius: 4px; }
        .msg-row.theirs .msg-bubble.has-image.has-text { background: #fff !important; color: #1c1e21; padding: 4px 4px 10px; box-shadow: 0 1px 3px rgba(0,0,0,.08) !important; border-bottom-left-radius: 4px; }

        .msg-time { font-size: 0.68rem; color: #65676b; padding: 0 4px; }

        .shared-post-card {
            margin-top: 8px;
            border: 1px solid #d9e6ff;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            max-width: 320px;
        }
        .msg-row.mine .shared-post-card { border-color: rgba(255,255,255,.35); }
        .shared-post-head {
            padding: 8px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            color: #5b6473;
            background: #f4f7ff;
            border-bottom: 1px solid #e6eeff;
        }
        .msg-row.mine .shared-post-head { background: rgba(255,255,255,.2); color: #fff; border-bottom-color: rgba(255,255,255,.25); }
        .shared-post-body { padding: 10px; }
        .shared-post-author { font-size: 0.8rem; font-weight: 700; margin-bottom: 4px; }
        .shared-post-text { font-size: 0.86rem; line-height: 1.35; }
        .shared-post-image {
            display: block;
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            cursor: zoom-in;
            border-top: 1px solid #eef2f8;
        }
        .shared-post-open {
            margin-top: 8px;
            border: none;
            background: #1877f2;
            color: #fff;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }
        .shared-post-open:hover { background: #1565d8; }

        /* Message en cours d'envoi */
        .msg-row.sending .msg-bubble { opacity: 0.6; }
        .msg-sending-dot { width: 6px; height: 6px; border-radius: 50%; background: #adb5bd; display: inline-block; margin-left: 4px; animation: pulse 1s infinite; }
        @keyframes pulse { 0%,100%{opacity:.4} 50%{opacity:1} }

        /* First message CTA */
        .chat-first-msg { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2rem; }
        .chat-first-av { width: 80px; height: 80px; border-radius: 50%; color: #fff; font-size: 2rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; border: 4px solid #e7f3ff; }
        .chat-first-name { font-weight: 800; font-size: 1.2rem; color: #1c1e21; margin-bottom: 0.4rem; }
        .chat-first-sub  { font-size: 0.88rem; color: #65676b; }

        /* ══ INPUT AREA ════════════════════════════════════ */
        .chat-input-area { background: #fff; border-top: 1px solid #dddfe2; flex-shrink: 0; }

        /* Barre de prévisualisation image */
        .img-preview-bar {
            display: none;
            align-items: center; gap: 10px;
            padding: 10px 16px 0;
            animation: slideDown .2s ease;
        }
        .img-preview-bar.show { display: flex; }
        @keyframes slideDown { from { opacity:0; transform: translateY(-8px); } to { opacity:1; transform: translateY(0); } }

        .img-preview-thumb {
            position: relative; display: inline-block;
        }
        .img-preview-thumb img {
            width: 72px; height: 72px; border-radius: 10px;
            object-fit: cover; border: 2px solid #dddfe2;
            display: block;
        }
        .img-preview-thumb .img-remove {
            position: absolute; top: -6px; right: -6px;
            width: 20px; height: 20px; border-radius: 50%;
            background: #e53935; color: #fff; border: 2px solid #fff;
            font-size: 0.7rem; display: flex; align-items: center; justify-content: center;
            cursor: pointer; line-height: 1;
        }
        .img-preview-info { flex: 1; }
        .img-preview-name { font-size: 0.82rem; font-weight: 600; color: #1c1e21; }
        .img-preview-size { font-size: 0.75rem; color: #65676b; }

        /* Barre de progression upload */
        .upload-progress {
            display: none;
            height: 3px;
            background: #e4e6eb;
            margin: 0;
        }
        .upload-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #1877f2, #42b72a);
            border-radius: 99px;
            transition: width .3s ease;
            width: 0%;
        }

        .chat-input-inner { display: flex; align-items: flex-end; gap: 10px; padding: 12px 16px; }

        .chat-attach-btn {
            width: 38px; height: 38px; border-radius: 50%; background: none; border: none;
            color: #65676b; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background .2s, color .2s; flex-shrink: 0;
        }
        .chat-attach-btn:hover { background: #f0f2f5; color: #1877f2; }
        .chat-attach-btn.has-file { color: #1877f2; background: #e7f3ff; }

        .chat-input-box {
            flex: 1; background: #f0f2f5; border-radius: 22px;
            padding: 10px 16px; display: flex; align-items: center;
        }
        .chat-textarea {
            flex: 1; border: none; background: transparent; outline: none;
            font-family: inherit; font-size: 0.93rem; color: #1c1e21;
            resize: none; max-height: 120px; line-height: 1.4;
        }

        .chat-send-btn {
            width: 42px; height: 42px; border-radius: 50%;
            background: #1877f2; color: #fff; border: none; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background .2s, transform .15s; flex-shrink: 0;
        }
        .chat-send-btn:hover:not(:disabled) { background: #1565d8; transform: scale(1.05); }
        .chat-send-btn:disabled { background: #e4e6eb; color: #adb5bd; cursor: not-allowed; }
        .chat-send-btn.sending { background: #1877f2; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ══ LIGHTBOX ════════════════════════════════════ */
        .lightbox-overlay {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,.92); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; cursor: zoom-out;
        }
        .lightbox-overlay.show { display: flex; animation: fadeIn .2s ease; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .lightbox-overlay img { max-width: 92vw; max-height: 88vh; border-radius: 8px; object-fit: contain; box-shadow: 0 8px 40px rgba(0,0,0,.6); }
        .lightbox-close {
            position: absolute; top: 16px; right: 20px;
            background: rgba(255,255,255,.15); border: none; color: #fff;
            width: 40px; height: 40px; border-radius: 50%; font-size: 1.3rem;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .lightbox-close:hover { background: rgba(255,255,255,.25); }

        /* ══ TOAST ════════════════════════════════════ */
        .msg-toast {
            position: fixed; top: 72px; right: 20px; z-index: 9998;
            background: #fff; border-radius: 10px; padding: 12px 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
            display: flex; align-items: center; gap: 10px;
            border-left: 4px solid #e53935; min-width: 280px;
            animation: slideIn .3s ease;
        }
        .msg-toast.success { border-left-color: #42b72a; }
        @keyframes slideIn { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }

        .post-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.78);
            z-index: 10001;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .post-modal.show { display: flex; }
        .post-modal-box {
            width: min(680px, 100%);
            max-height: 90vh;
            overflow: auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,.35);
        }
        .post-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 14px;
            border-bottom: 1px solid #eef1f5;
            font-weight: 800;
        }
        .post-modal-close {
            border: none;
            background: #f0f2f5;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
        }
        .post-modal-body { padding: 14px; }
        .post-modal-meta { font-size: .8rem; color: #667085; margin-bottom: 8px; }
        .post-modal-text { font-size: .95rem; line-height: 1.45; white-space: pre-wrap; }
        .post-modal-image {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 10px;
            cursor: zoom-in;
        }

        @media (max-width: 768px) {
            .msg-sidebar { position: absolute; top:0; left:0; height:100%; width:100%; z-index:10; transition: transform .3s ease; }
            .msg-sidebar.hidden { transform: translateX(-100%); }
            .msg-row { max-width: 88%; }
            .msg-bubble.has-image img { max-width: 220px; }
        }
    </style>
</head>
<body class="profile-page">

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="ri-close-line"></i></button>
    <img id="lightboxImg" src="" alt="">
</div>

<div class="post-modal" id="sharedPostModal" onclick="closeSharedPostModal(event)">
    <div class="post-modal-box">
        <div class="post-modal-head">
            <span>Publication partagée</span>
            <button class="post-modal-close" onclick="closeSharedPostModal()"><i class="ri-close-line"></i></button>
        </div>
        <div class="post-modal-body" id="sharedPostModalBody">
            <div class="text-muted">Chargement...</div>
        </div>
    </div>
</div>

<!-- TOPBAR -->
<nav class="topbar">
    <div class="topbar-inner">
        <a href="<?= BASE_URL ?>/index.php" class="topbar-logo"><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></a>
        <div class="topbar-search"><i class="ri-search-line"></i><input type="text" placeholder="Rechercher…" autocomplete="off"></div>
        <div class="topbar-nav">
            <a href="<?= BASE_URL ?>/index.php" class="topbar-nav-icon"><i class="ri-home-5-fill"></i></a>
            <button class="topbar-nav-icon active">
                <i class="ri-message-2-fill"></i>
                <?php if ($unreadMsgCount > 0): ?><span class="badge-dot"><?= $unreadMsgCount ?></span><?php endif; ?>
            </button>
            <button class="topbar-nav-icon" onclick="location.href='<?= BASE_URL ?>/groups.php'"><i class="ri-group-fill"></i></button>
            <button class="topbar-nav-icon" onclick="location.href='<?= BASE_URL ?>/notifications.php'">
                <i class="ri-notification-3-fill"></i>
                <?php if ($unreadNotifCount > 0): ?><span class="badge-dot"><?= $unreadNotifCount ?></span><?php endif; ?>
            </button>
            <div class="dropdown">
                <button class="topbar-avatar-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="topbar-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php?id=<?= $userId ?>">
                            <div class="profile-dropdown-user">
                                <div class="profile-dropdown-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                                <div><strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong><small>Voir votre profil</small></div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/friends.php"><i class="ri-user-3-fill me-2"></i>Amis</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="ri-logout-box-r-fill me-2"></i>Se déconnecter</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="msg-layout">

    <!-- SIDEBAR -->
    <aside class="msg-sidebar" id="msgSidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">Messages</div>
            <div class="sidebar-search"><i class="ri-search-line"></i><input type="text" id="sidebarSearch" placeholder="Rechercher une conversation…"></div>
        </div>
        <div class="sidebar-tabs">
            <button class="sidebar-tab active" data-stab="conversations" onclick="switchSidebarTab('conversations')">
                Conversations <span class="sidebar-tab-badge" id="unreadBadge" style="display:none">0</span>
            </button>
            <button class="sidebar-tab" data-stab="friends" onclick="switchSidebarTab('friends')">Amis</button>
            <button class="sidebar-tab" data-stab="groups" onclick="switchSidebarTab('groups')">Groupes</button>
        </div>
        <div class="contacts-list" id="contactsList">
            <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
    </aside>

    <!-- CHAT AREA -->
    <section class="chat-area">
        <div class="chat-empty" id="chatEmpty">
            <div class="chat-empty-icon"><i class="ri-message-2-line"></i></div>
            <h5>Vos messages</h5>
            <p>Sélectionnez un ami pour démarrer ou reprendre une conversation.</p>
        </div>

        <div id="chatActive">
            <div class="chat-header" id="chatHeader"></div>

            <!-- Barre de progression upload -->
            <div class="upload-progress" id="uploadProgress">
                <div class="upload-progress-bar" id="uploadProgressBar"></div>
            </div>

            <div class="chat-messages" id="chatMessages"></div>

            <!-- Input -->
            <div class="chat-input-area">
                <!-- Preview image sélectionnée -->
                <div class="img-preview-bar" id="imgPreviewBar">
                    <div class="img-preview-thumb">
                        <img id="imgPreviewThumb" src="" alt="">
                        <div class="img-remove" onclick="clearSelectedImage()" title="Retirer"><i class="ri-close-line"></i></div>
                    </div>
                    <div class="img-preview-info">
                        <div class="img-preview-name" id="imgPreviewName"></div>
                        <div class="img-preview-size" id="imgPreviewSize"></div>
                    </div>
                </div>

                <div class="chat-input-inner">
                    <label class="chat-attach-btn" id="attachBtn" title="Envoyer une image (JPG, PNG, GIF, WebP • max 8 Mo)">
                        <i class="ri-image-add-line"></i>
                        <input type="file" id="attachImg" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                    </label>
                    <div class="chat-input-box">
                        <textarea class="chat-textarea" id="chatInput" placeholder="Écrire un message…" rows="1"></textarea>
                    </div>
                    <button class="chat-send-btn" id="sendBtn" title="Envoyer (Entrée)" disabled>
                        <i class="ri-send-plane-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL   = '<?= BASE_URL ?>';
const ME_ID      = <?= $userId ?>;
const ME_NAME    = '<?= addslashes($username) ?>';
const ME_INITIAL = '<?= $initial ?>';

// STATE
let allFriends      = [];
let conversations   = [];
let groupConversations = [];
let currentContact  = null;
let currentMessages = [];
let pollTimer       = null;
let sidebarTab      = 'conversations';
let searchQuery     = '';
let isSending       = false;
let selectedFile    = null;  // fichier image sélectionné
let currentChatType = 'direct';
let currentGroupMembers = [];

// ── BOOT ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([loadFriends(), loadConversations(), loadGroupConversations()]);
    renderSidebar();
    setupInputListeners();

    const urlParams = new URLSearchParams(window.location.search);
    const targetId  = parseInt(urlParams.get('user_id'));
    const targetGroupId = parseInt(urlParams.get('group_id'));
    if (targetId) {
        const friend = allFriends.find(f => f.id === targetId);
        if (friend) openChat(friend.id, friend.username);
    }
    if (!targetId && targetGroupId) {
        const group = groupConversations.find(g => g.id == targetGroupId);
        if (group) openGroupChat(group.id, group.name);
    }
});

// ── DATA ──────────────────────────────────────────
async function loadFriends() {
    try {
        const r = await fetch(`${BASE_URL}/api/friends.php?action=list`);
        const d = await r.json();
        if (d.success) allFriends = d.friends;
    } catch(e) {}
}

async function loadConversations() {
    try {
        const r = await fetch(`${BASE_URL}/api/messages.php?action=list`);
        const d = await r.json();
        if (d.success) conversations = d.conversations;
    } catch(e) {}
}

async function loadGroupConversations() {
    try {
        const r = await fetch(`${BASE_URL}/api/messages.php?action=list_groups`);
        const d = await r.json();
        if (d.success) {
            groupConversations = d.groups;
        }
    } catch(e) {}
}

async function loadMessages(userId) {
    try {
        const r = await fetch(`${BASE_URL}/api/messages.php?action=get&user_id=${userId}`);
        const d = await r.json();
        if (d.success) { currentMessages = d.messages; return true; }
    } catch(e) {}
    currentMessages = [];
    return false;
}

async function loadGroupMessages(groupId) {
    try {
        const r = await fetch(`${BASE_URL}/api/messages.php?action=get_group&group_id=${groupId}`);
        const d = await r.json();
        if (d.success) { currentMessages = d.messages; return true; }
    } catch(e) {}
    currentMessages = [];
    return false;
}

async function loadGroupMembers(groupId) {
    try {
        const r = await fetch(`${BASE_URL}/api/groups.php?action=members&group_id=${groupId}`);
        const d = await r.json();
        if (d.success) {
            currentGroupMembers = d.members || [];
            return true;
        }
    } catch(e) {}
    currentGroupMembers = [];
    return false;
}

// ── SIDEBAR ───────────────────────────────────────
function switchSidebarTab(tab) {
    sidebarTab = tab;
    document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.toggle('active', t.dataset.stab === tab));
    renderSidebar();
}

function renderSidebar() {
    const container = document.getElementById('contactsList');
    const q = searchQuery.toLowerCase();

    if (sidebarTab === 'conversations') {
        const withMsg = conversations
            .filter(c => { const f = allFriends.find(x => x.id == c.other_user_id); return f && (!q || f.username.toLowerCase().includes(q)); })
            .sort((a,b) => new Date(b.last_time) - new Date(a.last_time));

        const withMsgIds = new Set(withMsg.map(c => c.other_user_id));
        const withoutMsg = allFriends.filter(f => !withMsgIds.has(f.id) && (!q || f.username.toLowerCase().includes(q)));

        if (!withMsg.length && !withoutMsg.length) {
            container.innerHTML = emptyHtml(q ? 'Aucun résultat' : 'Aucune conversation');
            return;
        }

        let html = '';
        if (withMsg.length) {
            html += `<div class="contacts-section-label">Récents</div>`;
            html += withMsg.map(c => {
                const f = allFriends.find(x => x.id == c.other_user_id) || { username: c.username };
                const preview = c.last_message
                    ? (c.last_message.startsWith('[Image]') ? '🖼️ Image' : esc(formatMessagePreview(c.last_message)))
                    : '';
                return contactHtml({ id: c.other_user_id, username: f.username, preview, time: formatTime(c.last_time), unread: parseInt(c.unread_count) > 0, unreadCount: parseInt(c.unread_count), hasHistory: true });
            }).join('');
        }
        if (withoutMsg.length) {
            html += `<div class="contacts-section-label">Amis — Premier message</div>`;
            html += withoutMsg.map(f => contactHtml({ id: f.id, username: f.username, preview: null, time: null, unread: false, unreadCount: 0, hasHistory: false })).join('');
        }
        container.innerHTML = html;
    } else if (sidebarTab === 'friends') {
        const filtered = allFriends.filter(f => !q || f.username.toLowerCase().includes(q));
        if (!filtered.length) { container.innerHTML = emptyHtml(q ? 'Aucun résultat' : 'Aucun ami'); return; }
        container.innerHTML = `<div class="contacts-section-label">${filtered.length} ami${filtered.length > 1 ? 's' : ''}</div>`
            + filtered.map(f => contactHtml({ id: f.id, username: f.username, preview: null, time: null, unread: false, unreadCount: 0, hasHistory: false })).join('');
    } else {
        const filteredGroups = groupConversations
            .filter(g => !q || g.name.toLowerCase().includes(q))
            .sort((a, b) => {
                const da = a.last_time ? new Date(a.last_time).getTime() : 0;
                const db = b.last_time ? new Date(b.last_time).getTime() : 0;
                return db - da;
            });
        if (!filteredGroups.length) {
            container.innerHTML = emptyHtml(q ? 'Aucun résultat' : 'Aucun groupe');
            return;
        }
        container.innerHTML = `<div class="contacts-section-label">${filteredGroups.length} groupe${filteredGroups.length > 1 ? 's' : ''}</div>`
            + filteredGroups.map(g => groupHtml(g)).join('');
    }

    highlightActive();
    updateUnreadBadge();
}

function groupHtml(group) {
    const preview = group.last_message ? esc(group.last_message) : '<span class="contact-friend-tag">👥 Groupe</span>';
    const badge = group.role === 'admin' ? '<span class="contact-friend-tag">👑 Admin</span>' : '';
    return `
        <div class="contact-item ${currentChatType === 'group' && currentContact?.id==group.id?'active':''}" data-gid="${group.id}" onclick="openGroupChat(${group.id},'${esc(group.name)}')">
            <div class="contact-av" style="background:${group.color || '#1877f2'}">${group.name.charAt(0).toUpperCase()}</div>
            <div class="contact-info">
                <div class="contact-name-row">
                    <span class="contact-name">${esc(group.name)}</span>
                    ${group.last_time ? `<span class="contact-time">${formatTime(group.last_time)}</span>` : ''}
                </div>
                <div class="contact-preview">${preview} ${badge}</div>
            </div>
        </div>`;
}

function contactHtml({ id, username, preview, time, unread, unreadCount, hasHistory }) {
    return `
        <div class="contact-item ${unread?'unread':''} ${currentContact?.id==id?'active':''}" data-uid="${id}" onclick="openChat(${id},'${esc(username)}')">
            <div class="contact-av" style="background:${gradient(username)}">${username.charAt(0).toUpperCase()}</div>
            <div class="contact-info">
                <div class="contact-name-row">
                    <span class="contact-name">${esc(username)}</span>
                    ${time ? `<span class="contact-time">${time}</span>` : ''}
                </div>
                <div class="contact-preview">${hasHistory ? (preview||'') : `<span class="contact-friend-tag">👤 Ami • Premier message</span>`}</div>
            </div>
            ${unreadCount > 0 ? `<div class="contact-unread-badge">${unreadCount}</div>` : ''}
        </div>`;
}

function emptyHtml(msg) {
    return `<div style="text-align:center;padding:3rem 1rem;color:#65676b"><i class="ri-message-2-line" style="font-size:2.5rem;opacity:.3;display:block;margin-bottom:.75rem"></i><div style="font-weight:700">${msg}</div></div>`;
}

function highlightActive() {
    document.querySelectorAll('.contact-item').forEach(el => el.classList.toggle('active', parseInt(el.dataset.uid) === currentContact?.id));
}

function updateUnreadBadge() {
    const total = conversations.reduce((s,c) => s + (parseInt(c.unread_count)||0), 0);
    const badge = document.getElementById('unreadBadge');
    badge.textContent = total;
    badge.style.display = total > 0 ? '' : 'none';
}

// ── OPEN CHAT ──────────────────────────────────────
async function openChat(userId, username) {
    currentChatType = 'direct';
    currentContact = { id: userId, username };
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatActive').style.display = 'flex';
    document.getElementById('chatMessages').innerHTML = `<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>`;

    highlightActive();
    const conv = conversations.find(c => c.other_user_id == userId);
    if (conv) conv.unread_count = 0;
    updateUnreadBadge();
    renderSidebar();

    renderChatHeader(userId, username);
    await loadMessages(userId);
    renderMessages(userId, username);

    if (window.innerWidth <= 768) document.getElementById('msgSidebar').classList.add('hidden');
    setTimeout(() => document.getElementById('chatInput').focus(), 100);

    clearInterval(pollTimer);
    pollTimer = setInterval(() => pollMessages(userId), 2500);
    history.replaceState({}, '', `${window.location.pathname}?user_id=${userId}`);
}

async function openGroupChat(groupId, groupName) {
    currentChatType = 'group';
    currentContact = { id: groupId, username: groupName };
    document.getElementById('chatEmpty').style.display = 'none';
    document.getElementById('chatActive').style.display = 'flex';
    document.getElementById('chatMessages').innerHTML = `<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>`;

    highlightActive();
    renderSidebar();

    await loadGroupMembers(groupId);
    const membersCount = currentGroupMembers.length;
    const membersPreview = currentGroupMembers.slice(0, 4).map(m => m.username).join(', ');
    const extraCount = membersCount > 4 ? ` +${membersCount - 4}` : '';

    document.getElementById('chatHeader').innerHTML = `
        <button class="chat-hd-btn d-md-none" onclick="closeChatMobile()"><i class="ri-arrow-left-line"></i></button>
        <div class="chat-hd-av" style="background:#1877f2">${groupName.charAt(0).toUpperCase()}</div>
        <div class="chat-hd-info">
            <div class="chat-hd-name">${escHtml(groupName)}</div>
            <div class="chat-hd-status">${membersCount} membres${membersPreview ? ` • ${escHtml(membersPreview)}${extraCount}` : ''}</div>
        </div>
    `;

    await loadGroupMessages(groupId);
    renderMessages(groupId, groupName);

    if (window.innerWidth <= 768) document.getElementById('msgSidebar').classList.add('hidden');
    setTimeout(() => document.getElementById('chatInput').focus(), 100);

    clearInterval(pollTimer);
    pollTimer = setInterval(() => pollMessages(groupId), 2500);
    history.replaceState({}, '', `${window.location.pathname}?group_id=${groupId}`);
}

function renderChatHeader(userId, username) {
    document.getElementById('chatHeader').innerHTML = `
        <button class="chat-hd-btn d-md-none" onclick="closeChatMobile()"><i class="ri-arrow-left-line"></i></button>
        <div class="chat-hd-av" style="background:${gradient(username)}">${username.charAt(0).toUpperCase()}</div>
        <div class="chat-hd-info">
            <div class="chat-hd-name">${esc(username)}</div>
            <div class="chat-hd-status">En ligne</div>
        </div>
        <button class="chat-hd-btn" onclick="location.href='${BASE_URL}/profile.php?id=${userId}'" title="Profil"><i class="ri-user-3-fill"></i></button>
    `;
}

// ── RENDER MESSAGES ────────────────────────────────
function renderMessages(userId, username, scrollToBottom = true) {
    const container = document.getElementById('chatMessages');
    let cta = document.getElementById('chatFirstMsg');

    if (!currentMessages.length) {
        container.style.display = 'none';
        if (!cta) {
            cta = document.createElement('div');
            cta.id = 'chatFirstMsg';
            cta.className = 'chat-first-msg';
            document.getElementById('chatActive').insertBefore(cta, document.querySelector('.chat-input-area'));
        }
        cta.style.display = 'flex';
        cta.innerHTML = `
            <div class="chat-first-av" style="background:${gradient(username)}">${username.charAt(0).toUpperCase()}</div>
            <div class="chat-first-name">${esc(username)}</div>
            <p class="chat-first-sub">Vous êtes amis ! Envoyez le premier message.</p>`;
        return;
    }

    if (cta) cta.style.display = 'none';
    container.style.display = 'flex';

    const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 80;

    let lastDate = null;
    container.innerHTML = currentMessages.map(msg => {
        const isMine = parseInt(msg.sender_id) === ME_ID;
        const d = new Date(msg.created_at);
        const dateKey = d.toDateString();
        let sep = '';
        if (dateKey !== lastDate) { lastDate = dateKey; sep = `<div class="msg-date-sep"><span>${formatDateFull(d)}</span></div>`; }

        const gr = gradient(msg.sender_name || username);
        const avatarHtml = !isMine ? `<div class="msg-av-sm" style="background:${gr}">${(msg.sender_name||username).charAt(0).toUpperCase()}</div>` : '';

        // Construire la bulle
        let bubbleContent = '';
        let bubbleClass = 'msg-bubble';
        const hasImage = msg.image_path && msg.image_path !== 'null';
        const { cleanBody, sharedPostId } = parseSharedPostToken(msg.body || '');
        const hasText  = cleanBody && cleanBody.trim() !== '';

        if (hasImage) {
            bubbleClass += ' has-image';
            if (hasText) bubbleClass += ' has-text';
            const imgUrl = `${BASE_URL}/${msg.image_path}`;
            bubbleContent = `<img src="${escAttr(imgUrl)}" alt="Image" onclick="openLightbox('${escAttr(imgUrl)}')" loading="lazy">`;
            if (hasText) bubbleContent += `<div class="msg-caption">${escHtml(cleanBody)}</div>`;
        } else {
            bubbleContent = hasText ? escHtml(cleanBody) : '';
        }

        if (sharedPostId) {
            bubbleContent += `
                <div class="shared-post-card" data-shared-post-id="${sharedPostId}">
                    <div class="shared-post-head">Publication partagée</div>
                    <div class="shared-post-body">
                        <div class="shared-post-text">Chargement de la publication...</div>
                    </div>
                </div>
            `;
        }

        return `${sep}
            <div class="msg-row ${isMine?'mine':'theirs'}">
                ${avatarHtml}
                <div class="msg-bubble-wrap">
                    <div class="${bubbleClass}">${bubbleContent}</div>
                    <div class="msg-time">${formatTimeFull(d)}</div>
                </div>
            </div>`;
    }).join('');

    if (scrollToBottom && (wasAtBottom || currentMessages.length <= 15)) {
        container.scrollTop = container.scrollHeight;
    }

    hydrateSharedPostCards();
}

// ── SEND MESSAGE ───────────────────────────────────
async function sendMessage() {
    const input = document.getElementById('chatInput');
    const text  = input.value.trim();
    if ((!text && !selectedFile) || !currentContact || isSending) return;

    isSending = true;
    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.classList.add('sending');
    sendBtn.innerHTML = '<i class="ri-loader-4-line"></i>';

    const originalText = text;
    const originalFile = selectedFile;
    input.value = '';
    input.style.height = 'auto';
    clearSelectedImage(false);

    // Afficher la progression si image
    const progressBar = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('uploadProgressBar');
    if (originalFile) {
        progressBar.style.display = 'block';
        progressFill.style.width = '0%';
    }

    try {
        let response, data;

        if (originalFile) {
            // Envoi multipart/form-data avec image + texte
            const fd = new FormData();
            fd.append(currentChatType === 'group' ? 'group_id' : 'receiver_id', currentContact.id);
            fd.append('body', originalText);
            fd.append('image', originalFile);

            // Upload avec XMLHttpRequest pour avoir la progression
            data = await uploadWithProgress(fd, progressFill, currentChatType);
        } else {
            // Envoi JSON (texte seul, plus rapide)
            response = await fetch(`${BASE_URL}/api/messages.php?action=${currentChatType === 'group' ? 'send_group' : 'send'}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(
                    currentChatType === 'group'
                        ? { group_id: currentContact.id, body: originalText }
                        : { receiver_id: currentContact.id, body: originalText }
                )
            });
            data = await response.json();
        }

        if (data.success) {
            if (data.message) {
                currentMessages.push(data.message);
            } else {
                currentMessages.push({
                    id: Date.now(), sender_id: ME_ID, sender_name: ME_NAME,
                    body: originalText, image_path: null,
                    created_at: new Date().toISOString()
                });
            }
            renderMessages(currentContact.id, currentContact.username);
            await loadConversations();
            await loadGroupConversations();
            renderSidebar();
        } else {
            showToast('Erreur : ' + (data.error || 'Impossible d\'envoyer'), 'error');
            input.value = originalText;
        }
    } catch(e) {
        console.error(e);
        showToast('Erreur réseau. Réessayez.', 'error');
        input.value = originalText;
    } finally {
        isSending = false;
        sendBtn.disabled = !input.value.trim() && !selectedFile;
        sendBtn.classList.remove('sending');
        sendBtn.innerHTML = '<i class="ri-send-plane-fill"></i>';
        progressBar.style.display = 'none';
        progressFill.style.width = '0%';
    }
}

// Upload avec barre de progression via XHR
function uploadWithProgress(formData, progressEl) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', `${BASE_URL}/api/messages.php?action=${currentChatType === 'group' ? 'send_group' : 'send'}`);

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable) {
                const pct = Math.round((e.loaded / e.total) * 90); // 90% = upload, 10% = traitement
                progressEl.style.width = pct + '%';
            }
        });

        xhr.addEventListener('load', () => {
            progressEl.style.width = '100%';
            try {
                resolve(JSON.parse(xhr.responseText));
            } catch(e) {
                reject(new Error('Réponse invalide du serveur'));
            }
        });

        xhr.addEventListener('error', () => reject(new Error('Erreur réseau')));
        xhr.send(formData);
    });
}

// ── IMAGE SÉLECTIONNÉE ─────────────────────────────
document.getElementById('attachImg').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    const maxSize = 8 * 1024 * 1024;
    if (file.size > maxSize) {
        showToast('Image trop lourde (max 8 Mo)', 'error');
        this.value = '';
        return;
    }

    const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!allowed.includes(file.type)) {
        showToast('Format non autorisé (JPG, PNG, GIF, WebP)', 'error');
        this.value = '';
        return;
    }

    selectedFile = file;

    // Afficher la preview
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('imgPreviewThumb').src = e.target.result;
        document.getElementById('imgPreviewName').textContent = file.name;
        document.getElementById('imgPreviewSize').textContent = formatFileSize(file.size);
        document.getElementById('imgPreviewBar').classList.add('show');
        document.getElementById('attachBtn').classList.add('has-file');
    };
    reader.readAsDataURL(file);

    updateSendBtn();
});

function clearSelectedImage(resetInput = true) {
    selectedFile = null;
    document.getElementById('imgPreviewBar').classList.remove('show');
    document.getElementById('attachBtn').classList.remove('has-file');
    document.getElementById('imgPreviewThumb').src = '';
    if (resetInput) {
        document.getElementById('attachImg').value = '';
    }
    updateSendBtn();
}

function updateSendBtn() {
    const input = document.getElementById('chatInput');
    document.getElementById('sendBtn').disabled = !input.value.trim() && !selectedFile;
}

// ── POLLING ───────────────────────────────────────
async function pollMessages(userId) {
    if (!currentContact || currentContact.id !== userId || isSending) return;
    try {
        const endpoint = currentChatType === 'group'
            ? `${BASE_URL}/api/messages.php?action=get_group&group_id=${userId}`
            : `${BASE_URL}/api/messages.php?action=get&user_id=${userId}`;
        const r = await fetch(endpoint);
        const d = await r.json();
        if (!d.success) return;
        if (d.messages.length !== currentMessages.length) {
            currentMessages = d.messages;
            renderMessages(userId, currentContact.username);
            await loadConversations();
            await loadGroupConversations();
            renderSidebar();
        }
    } catch(e) {}
}

// ── INPUT LISTENERS ────────────────────────────────
function setupInputListeners() {
    const input   = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');

    input.addEventListener('input', () => {
        updateSendBtn();
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendBtn.disabled && !isSending) sendMessage();
        }
    });

    sendBtn.addEventListener('click', () => { if (!isSending) sendMessage(); });

    document.getElementById('sidebarSearch').addEventListener('input', e => {
        searchQuery = e.target.value.trim();
        renderSidebar();
    });

    // Paste image depuis le presse-papiers
    document.addEventListener('paste', e => {
        if (!currentContact) return;
        const items = e.clipboardData?.items;
        if (!items) return;
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                const file = item.getAsFile();
                if (file) {
                    // Simuler la sélection du fichier
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    const input = document.getElementById('attachImg');
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change'));
                }
                break;
            }
        }
    });
}

// ── LIGHTBOX ──────────────────────────────────────
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('show');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
    document.getElementById('lightboxImg').src = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

// ── MOBILE ────────────────────────────────────────
function closeChatMobile() {
    document.getElementById('msgSidebar').classList.remove('hidden');
    clearInterval(pollTimer);
    currentContact = null;
    currentChatType = 'direct';
    history.replaceState({}, '', window.location.pathname);
}

// ── TOAST ─────────────────────────────────────────
function showToast(message, type = 'info') {
    document.querySelector('.msg-toast')?.remove();
    const el = document.createElement('div');
    el.className = `msg-toast ${type === 'success' ? 'success' : ''}`;
    el.innerHTML = `
        <i class="ri-${type==='success'?'checkbox-circle-fill':'error-warning-fill'}" style="color:${type==='success'?'#42b72a':'#e53935'};font-size:1.2rem"></i>
        <span style="flex:1;font-size:.88rem;font-weight:600">${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#65676b;cursor:pointer"><i class="ri-close-line"></i></button>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 5000);
}

// ── UTILS ─────────────────────────────────────────
function esc(s)     { return String(s).replace(/'/g,"\\'").replace(/"/g,'&quot;'); }
function escAttr(s) { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>'); }
function parseSharedPostToken(body) {
    const text = String(body ?? '');
    const match = text.match(/\[POST_SHARE:(\d+)\]/);
    const sharedPostId = match ? parseInt(match[1], 10) : 0;
    const cleanBody = text.replace(/\s*\[POST_SHARE:\d+\]\s*/g, '\n').trim();
    return { cleanBody, sharedPostId };
}
function formatMessagePreview(body) {
    const { cleanBody, sharedPostId } = parseSharedPostToken(body);
    if (sharedPostId && !cleanBody) return 'Publication partagée';
    if (sharedPostId) return `${cleanBody} • Publication partagée`;
    return cleanBody;
}

const sharedPostsCache = new Map();
function resolvePostImageUrl(imagePath) {
    const path = String(imagePath || '').trim();
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    if (path.startsWith('/uploads/')) return `${BASE_URL}${path}`;
    if (path.startsWith('uploads/')) return `${BASE_URL}/${path}`;
    return `${BASE_URL}/uploads/${path.split('/').pop()}`;
}
async function fetchSharedPost(postId) {
    if (sharedPostsCache.has(postId)) return sharedPostsCache.get(postId);
    const r = await fetch(`${BASE_URL}/api/messages.php?action=get_shared_post&post_id=${postId}`);
    const d = await r.json();
    if (!d.success) throw new Error(d.error || 'Publication introuvable');
    sharedPostsCache.set(postId, d.post);
    return d.post;
}
async function hydrateSharedPostCards() {
    const cards = Array.from(document.querySelectorAll('.shared-post-card[data-shared-post-id]'));
    for (const card of cards) {
        if (card.dataset.loaded === '1') continue;
        const postId = parseInt(card.dataset.sharedPostId || '0', 10);
        if (!postId) continue;
        try {
            const post = await fetchSharedPost(postId);
            card.dataset.loaded = '1';
            const text = (post.body || '').trim();
            card.innerHTML = `
                <div class="shared-post-head">Publication partagée</div>
                <div class="shared-post-body">
                    <div class="shared-post-author">${escHtml(post.username || 'Utilisateur')}</div>
                    <div class="shared-post-text">${text ? escHtml(text) : '<em>Aucun texte</em>'}</div>
                    <button class="shared-post-open" onclick="openSharedPostModal(${postId})">Voir la publication</button>
                </div>
                ${post.image_path ? `<img class="shared-post-image" src="${escAttr(resolvePostImageUrl(post.image_path))}" alt="Image publication" onclick="openLightbox('${escAttr(resolvePostImageUrl(post.image_path))}')" loading="lazy">` : ''}
            `;
        } catch (e) {
            card.dataset.loaded = '1';
            card.innerHTML = `
                <div class="shared-post-head">Publication partagée</div>
                <div class="shared-post-body">
                    <div class="shared-post-text">Cette publication n'est plus disponible.</div>
                </div>
            `;
        }
    }
}

async function openSharedPostModal(postId) {
    const modal = document.getElementById('sharedPostModal');
    const body = document.getElementById('sharedPostModalBody');
    modal.classList.add('show');
    body.innerHTML = '<div class="text-muted">Chargement...</div>';
    try {
        const post = await fetchSharedPost(Number(postId));
        const imgUrl = resolvePostImageUrl(post.image_path);
        body.innerHTML = `
            <div class="post-modal-meta"><strong>${escHtml(post.username || 'Utilisateur')}</strong> • ${escHtml(formatTime(post.created_at))}</div>
            <div class="post-modal-text">${post.body ? escHtml(post.body) : '<em>Aucun texte</em>'}</div>
            ${imgUrl ? `<img class="post-modal-image" src="${escAttr(imgUrl)}" alt="Image publication" onclick="openLightbox('${escAttr(imgUrl)}')">` : ''}
        `;
    } catch (e) {
        body.innerHTML = '<div class="text-danger">Impossible d\'afficher la publication.</div>';
    }
}

function closeSharedPostModal(event) {
    if (event && event.target && event.target.id !== 'sharedPostModal') return;
    document.getElementById('sharedPostModal').classList.remove('show');
}

function gradient(name) {
    const p = [['#1877f2','#1565d8'],['#42b72a','#36a420'],['#e53935','#b71c1c'],['#f7981c','#e67e00'],['#8e24aa','#6a1b9a'],['#00897b','#00695c'],['#d81b60','#880e4f']];
    const i = (name.charCodeAt(0)||0) % p.length;
    return `linear-gradient(135deg, ${p[i][0]}, ${p[i][1]})`;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr), now = new Date(), diff = now - d;
    if (diff < 60000)     return 'À l\'instant';
    if (diff < 3600000)   return `${Math.floor(diff/60000)} min`;
    if (diff < 86400000)  return d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
    if (diff < 604800000) return d.toLocaleDateString('fr-FR',{weekday:'short'});
    return d.toLocaleDateString('fr-FR',{day:'numeric',month:'short'});
}
function formatTimeFull(d) { return d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'}); }
function formatDateFull(d) {
    const now=new Date(), diff=now-d;
    if (diff<86400000)  return 'Aujourd\'hui';
    if (diff<172800000) return 'Hier';
    return d.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long'});
}
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' Ko';
    return (bytes/1024/1024).toFixed(1) + ' Mo';
}
</script>
<script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/js/topbar-search.js"></script>
</body>
</html>