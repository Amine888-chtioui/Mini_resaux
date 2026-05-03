<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/connexion.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];
$initial  = mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8');
$pageTitle = 'Amis — ' . SITE_NAME;
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
        /* ── Layout ── */
        .friends-page { background: #f0f2f5; min-height: 100vh; }

        /* ── Header Banner ── */
        .friends-banner {
            background: linear-gradient(135deg, #1877f2 0%, #0c3f9e 100%);
            padding: 2.5rem 0 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .friends-banner::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .friends-banner h1 { color: #fff; font-weight: 800; letter-spacing: -0.5px; position: relative; z-index: 1; }
        .friends-banner p  { color: rgba(255,255,255,0.78); position: relative; z-index: 1; }

        /* ── Tabs ── */
        .friends-tabs-nav {
            background: #fff;
            border-bottom: 1px solid #dddfe2;
            position: sticky;
            top: 56px;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .friends-tabs-nav .nav-link {
            color: #65676b;
            font-weight: 600;
            padding: 1rem 1.4rem;
            border-radius: 0;
            border-bottom: 3px solid transparent;
            font-size: 0.92rem;
            transition: color .2s, border-color .2s;
        }
        .friends-tabs-nav .nav-link:hover  { color: #1877f2; background: rgba(24,119,242,.04); }
        .friends-tabs-nav .nav-link.active { color: #1877f2; border-bottom-color: #1877f2; background: transparent; }
        .tab-badge-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px;
            background: #e53935; color: #fff;
            font-size: 10px; font-weight: 700;
            border-radius: 999px; padding: 0 4px;
            margin-left: 6px;
        }

        /* ── Search bar ── */
        .search-hero {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .search-input-wrap {
            position: relative;
        }
        .search-input-wrap i {
            position: absolute;
            left: 14px; top: 50%; transform: translateY(-50%);
            color: #65676b; font-size: 1.1rem; pointer-events: none;
        }
        .search-input-wrap input {
            padding-left: 42px;
            border-radius: 999px;
            border: 2px solid #dddfe2;
            height: 46px;
            font-size: 0.95rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .search-input-wrap input:focus {
            border-color: #1877f2;
            box-shadow: 0 0 0 4px rgba(24,119,242,.10);
            outline: none;
        }
        .search-filter-chips { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; }
        .chip {
            padding: 0.3rem 0.9rem;
            border-radius: 999px;
            border: 1.5px solid #dddfe2;
            background: #f8f9fa;
            color: #65676b;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .chip:hover, .chip.active {
            border-color: #1877f2;
            background: #e7f3ff;
            color: #1877f2;
        }

        /* ── Cards grid ── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.25rem;
        }

        /* ── Friend card ── */
        .friend-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
            transition: transform .25s, box-shadow .25s;
            position: relative;
        }
        .friend-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.13);
        }
        .friend-card-cover {
            height: 70px;
            background: linear-gradient(135deg, #1877f2 0%, #0c3f9e 100%);
        }
        .friend-card-body { padding: 0 1.25rem 1.25rem; }
        .friend-card-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1877f2, #1565d8);
            color: #fff;
            font-size: 1.8rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            border: 4px solid #fff;
            margin-top: -36px;
            margin-bottom: 0.75rem;
        }
        .friend-card-name {
            font-weight: 700;
            font-size: 1rem;
            color: #1c1e21;
            margin-bottom: 0.2rem;
        }
        .friend-card-meta {
            font-size: 0.8rem;
            color: #65676b;
            margin-bottom: 1rem;
        }
        .friend-card-actions { display: flex; gap: 0.5rem; }
        .friend-card-actions .btn { flex: 1; font-size: 0.82rem; font-weight: 600; border-radius: 8px; padding: 0.45rem 0.5rem; }

        /* Kebab menu */
        .card-menu-btn {
            position: absolute;
            top: 10px; right: 10px;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            border: none;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background .2s;
        }
        .card-menu-btn:hover { background: rgba(255,255,255,0.45); }
        .card-menu-btn::after { display: none !important; }

        /* ── Request card ── */
        .request-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: box-shadow .2s;
        }
        .request-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.11); }
        .req-avatar {
            width: 60px; height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f7981c, #e67e00);
            color: #fff;
            font-size: 1.4rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .req-info { flex: 1; min-width: 0; }
        .req-name { font-weight: 700; color: #1c1e21; margin-bottom: 0.2rem; }
        .req-time { font-size: 0.8rem; color: #65676b; }
        .req-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

        /* ── Suggestion card ── */
        .suggestion-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: box-shadow .2s;
        }
        .suggestion-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.11); }
        .sug-avatar {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #42b72a, #36a420);
            color: #fff;
            font-size: 1.3rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sug-info { flex: 1; min-width: 0; }
        .sug-name { font-weight: 700; color: #1c1e21; font-size: 0.95rem; margin-bottom: 0.2rem; }
        .sug-meta { font-size: 0.8rem; color: #65676b; }

        /* ── Blocked card ── */
        .blocked-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .blk-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: #e4e6eb;
            color: #65676b;
            font-size: 1.2rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            filter: grayscale(1);
        }
        .blk-info { flex: 1; }
        .blk-name { font-weight: 700; color: #65676b; }
        .blk-label { font-size: 0.78rem; color: #e53935; font-weight: 600; }

        /* ── Profile modal ── */
        .profile-modal-avatar {
            width: 90px; height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1877f2, #1565d8);
            color: #fff;
            font-size: 2.4rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            border: 4px solid #e7f3ff;
        }
        .profile-modal-cover {
            height: 100px;
            background: linear-gradient(135deg, #1877f2, #0c3f9e);
            border-radius: 12px 12px 0 0;
            margin: -1rem -1rem 0;
        }
        .profile-modal-body { padding: 0 1rem 1rem; }
        .profile-stat-box {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 1rem 0;
        }
        .profile-stat-item {
            text-align: center;
            flex: 1;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 0.75rem 0.5rem;
        }
        .profile-stat-item strong { display: block; font-size: 1.3rem; font-weight: 800; color: #1877f2; }
        .profile-stat-item span   { font-size: 0.75rem; color: #65676b; font-weight: 600; }
        .profile-action-row { display: flex; gap: 0.75rem; margin-top: 1rem; }
        .profile-action-row .btn { flex: 1; border-radius: 10px; font-weight: 700; }

        /* ── Empty state ── */
        .empty-state-modern {
            text-align: center;
            padding: 4rem 1rem;
            color: #65676b;
        }
        .empty-state-modern .empty-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: #e7f3ff;
            color: #1877f2;
            font-size: 2rem;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
        }
        .empty-state-modern h5 { font-weight: 700; color: #1c1e21; margin-bottom: 0.5rem; }
        .empty-state-modern p  { font-size: 0.9rem; max-width: 300px; margin: 0 auto; }

        /* ── Skeleton loader ── */
        .skeleton { animation: shimmer 1.5s infinite; }
        .skeleton-box {
            background: linear-gradient(90deg, #e4e6eb 25%, #f5f6f8 50%, #e4e6eb 75%);
            background-size: 200% 100%;
            border-radius: 6px;
        }
        @keyframes shimmer {
            0%   { background-position: -200% 0; }
            100% { background-position:  200% 0; }
        }

        /* ── Toast notification ── */
        .toast-container-custom {
            position: fixed;
            top: 72px; right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border-radius: 10px;
            padding: 12px 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            min-width: 280px;
            max-width: 360px;
            animation: slideIn .35s cubic-bezier(.4,0,.2,1);
            border-left: 4px solid #1877f2;
        }
        .toast-item.success { border-left-color: #42b72a; }
        .toast-item.error   { border-left-color: #e53935; }
        .toast-item.warning { border-left-color: #f7981c; }
        .toast-icon { font-size: 1.2rem; flex-shrink: 0; }
        .toast-text { flex: 1; font-size: 0.88rem; font-weight: 600; color: #1c1e21; }
        .toast-close { background: none; border: none; color: #65676b; cursor: pointer; font-size: 1rem; padding: 0; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(30px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @media (max-width: 576px) {
            .cards-grid { grid-template-columns: 1fr; }
            .req-actions { flex-direction: column; }
            .friends-tabs-nav .nav-link { padding: .75rem .75rem; font-size: .82rem; }
        }
    </style>
</head>

<body class="profile-page friends-page">

<!-- Toast container -->
<div class="toast-container-custom" id="toastContainer"></div>

<!-- ═══════════════ TOPBAR ═══════════════ -->
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
            <a href="<?= BASE_URL ?>/index.php" class="topbar-nav-icon" title="Accueil">
                <i class="ri-home-5-fill"></i>
            </a>
            <button class="topbar-nav-icon" title="Messages" onclick="location.href='<?= BASE_URL ?>/messages.php'">
                <i class="ri-message-2-fill"></i>
                <span class="badge-dot" id="msgBadge" style="display:none">0</span>
            </button>
            <button class="topbar-nav-icon" title="Groupes" onclick="location.href='<?= BASE_URL ?>/groups.php'">
                <i class="ri-group-fill"></i>
            </button>
            <button class="topbar-nav-icon" title="Notifications" onclick="location.href='<?= BASE_URL ?>/notifications.php'">
                <i class="ri-notification-3-fill"></i>
                <span class="badge-dot" id="notifBadge" style="display:none">0</span>
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
                                <div>
                                    <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <small>Voir votre profil</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item active" href="<?= BASE_URL ?>/friends.php"><i class="ri-user-3-fill me-2"></i>Amis</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="ri-logout-box-r-fill me-2"></i>Se déconnecter</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════ BANNER ═══════════════ -->
<div class="friends-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h2 mb-1"><i class="ri-user-3-fill me-2"></i>Amis</h1>
                <p class="mb-0 fs-6">Gérez vos relations, trouvez de nouvelles personnes et restez connecté.</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-light fw-bold" onclick="switchTab('search')">
                    <i class="ri-search-line me-2"></i>Trouver des amis
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ TABS ═══════════════ -->
<div class="friends-tabs-nav">
    <div class="container">
        <ul class="nav" id="friendsTabs">
            <li class="nav-item">
                <button class="nav-link active" data-tab="friends" onclick="switchTab('friends')">
                    <i class="ri-group-fill me-1"></i>Mes amis
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-tab="requests" onclick="switchTab('requests')">
                    <i class="ri-user-add-fill me-1"></i>Demandes
                    <span class="tab-badge-count" id="requestsBadge" style="display:none">0</span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-tab="suggestions" onclick="switchTab('suggestions')">
                    <i class="ri-compass-3-fill me-1"></i>Suggestions
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-tab="search" onclick="switchTab('search')">
                    <i class="ri-search-fill me-1"></i>Rechercher
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-tab="blocked" onclick="switchTab('blocked')">
                    <i class="ri-shield-user-fill me-1"></i>Bloqués
                </button>
            </li>
        </ul>
    </div>
</div>

<!-- ═══════════════ CONTENT ═══════════════ -->
<main class="container py-4">

    <!-- ── TAB: Mes amis ── -->
    <div id="pane-friends" class="tab-pane-content">
        <div class="cards-grid" id="friendsGrid">
            <!-- skeleton loaders -->
            <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="friend-card skeleton">
                <div class="friend-card-cover skeleton-box"></div>
                <div class="friend-card-body">
                    <div class="skeleton-box" style="width:72px;height:72px;border-radius:50%;margin-top:-36px;margin-bottom:12px"></div>
                    <div class="skeleton-box mb-2" style="height:14px;width:60%"></div>
                    <div class="skeleton-box mb-3" style="height:11px;width:40%"></div>
                    <div class="d-flex gap-2">
                        <div class="skeleton-box" style="height:34px;flex:1;border-radius:8px"></div>
                        <div class="skeleton-box" style="height:34px;flex:1;border-radius:8px"></div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ── TAB: Demandes ── -->
    <div id="pane-requests" class="tab-pane-content" style="display:none">
        <div class="d-flex flex-column gap-3" id="requestsList"></div>
    </div>

    <!-- ── TAB: Suggestions ── -->
    <div id="pane-suggestions" class="tab-pane-content" style="display:none">
        <div class="d-flex flex-column gap-3" id="suggestionsList"></div>
    </div>

    <!-- ── TAB: Rechercher ── -->
    <div id="pane-search" class="tab-pane-content" style="display:none">
        <div class="search-hero">
            <h5 class="fw-bold mb-3"><i class="ri-search-fill me-2 text-primary"></i>Rechercher des utilisateurs</h5>
            <div class="search-input-wrap mb-2">
                <i class="ri-search-line"></i>
                <input type="text" class="form-control" id="searchInput"
                       placeholder="Nom d'utilisateur, email…"
                       autocomplete="off">
            </div>
            <div class="search-filter-chips">
                <span class="chip active" data-filter="all">Tous</span>
                <span class="chip" data-filter="friend">Amis</span>
                <span class="chip" data-filter="none">Non-amis</span>
            </div>
        </div>
        <div id="searchResults" class="d-flex flex-column gap-3">
            <div class="empty-state-modern">
                <div class="empty-icon"><i class="ri-search-2-line"></i></div>
                <h5>Trouvez des personnes</h5>
                <p>Tapez au moins 2 caractères pour rechercher un utilisateur par nom ou email.</p>
            </div>
        </div>
    </div>

    <!-- ── TAB: Bloqués ── -->
    <div id="pane-blocked" class="tab-pane-content" style="display:none">
        <div class="d-flex flex-column gap-3" id="blockedList"></div>
    </div>

</main>

<!-- ═══════════════ PROFILE MODAL ═══════════════ -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-custom" style="border-radius:16px;overflow:hidden">
            <div class="modal-body p-4" id="profileModalBody">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════ CONFIRM MODAL ═══════════════ -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content modal-custom" style="border-radius:14px">
            <div class="modal-body p-4 text-center">
                <div id="confirmIcon" style="font-size:2.5rem;margin-bottom:0.75rem"></div>
                <h6 class="fw-bold mb-2" id="confirmTitle"></h6>
                <p class="text-muted small" id="confirmText"></p>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-outline-secondary flex-1" data-bs-dismiss="modal">Annuler</button>
                    <button class="btn flex-1" id="confirmOkBtn"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const ME_ID    = <?= $userId ?>;
const ME_NAME  = '<?= addslashes($username) ?>';

/* ════════════ STATE ════════════ */
let friends     = [];
let requests    = [];
let suggestions = [];
let blockedUsers= [];
let searchResults = [];
let currentFilter = 'all';
let searchTimer   = null;
let currentTab    = 'friends';

/* ════════════ INIT ════════════ */
document.addEventListener('DOMContentLoaded', () => {
    loadFriends();
    loadRequests();
    loadSuggestions();
    loadBlocked();
    updateBadges();
    setupSearchListeners();
    setupFilterChips();
});

/* ════════════ TAB SWITCH ════════════ */
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-pane-content').forEach(p => p.style.display = 'none');
    document.querySelectorAll('#friendsTabs .nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById(`pane-${tab}`).style.display = '';
    document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
}

/* ════════════ TOAST ════════════ */
function toast(message, type = 'info') {
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const el = document.createElement('div');
    el.className = `toast-item ${type}`;
    el.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-text">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
    `;
    document.getElementById('toastContainer').appendChild(el);
    setTimeout(() => el.remove(), 4500);
}

/* ════════════ CONFIRM DIALOG ════════════ */
function confirm(title, text, btnLabel, btnClass, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmText').textContent  = text;
    const btn = document.getElementById('confirmOkBtn');
    btn.textContent  = btnLabel;
    btn.className    = `btn flex-1 ${btnClass}`;
    btn.onclick      = () => { bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide(); callback(); };
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}

/* ════════════ API HELPERS ════════════ */
async function apiFetch(url, options = {}) {
    const res  = await fetch(url, options);
    const data = await res.json();
    return data;
}

async function postJSON(url, body) {
    return apiFetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
}

/* ════════════ LOAD FRIENDS ════════════ */
async function loadFriends() {
    const data = await apiFetch(`${BASE_URL}/api/friends.php?action=list`);
    if (!data.success) return;
    friends = data.friends;
    renderFriends();
}

function renderFriends() {
    const grid = document.getElementById('friendsGrid');

    if (!friends.length) {
        grid.innerHTML = `
            <div class="col-span-full">
                <div class="empty-state-modern">
                    <div class="empty-icon"><i class="ri-user-3-line"></i></div>
                    <h5>Aucun ami pour l'instant</h5>
                    <p>Commencez par rechercher des personnes ou explorez les suggestions.</p>
                    <button class="btn btn-primary mt-3" onclick="switchTab('search')">
                        <i class="ri-search-line me-2"></i>Trouver des amis
                    </button>
                </div>
            </div>`;
        return;
    }

    grid.innerHTML = friends.map(f => `
        <div class="friend-card" id="friend-${f.id}">
            <div class="friend-card-cover" style="background:${avatarGradient(f.username)}"></div>
            <!-- kebab menu -->
            <div class="dropdown" style="position:absolute;top:10px;right:10px">
                <button class="card-menu-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ri-more-fill"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="border-radius:12px;min-width:170px">
                    <li><button class="dropdown-item" onclick="viewProfile(${f.id})">
                        <i class="ri-user-3-line me-2 text-primary"></i>Voir le profil
                    </button></li>
                    <li><button class="dropdown-item" onclick="startConversation(${f.id}, '${escAttr(f.username)}')">
                        <i class="ri-message-2-line me-2 text-success"></i>Envoyer un message
                    </button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-warning" onclick="confirmBlock(${f.id}, '${escAttr(f.username)}')">
                        <i class="ri-shield-user-line me-2"></i>Bloquer
                    </button></li>
                    <li><button class="dropdown-item text-danger" onclick="confirmRemove(${f.id}, '${escAttr(f.username)}')">
                        <i class="ri-user-unfollow-line me-2"></i>Supprimer
                    </button></li>
                </ul>
            </div>
            <div class="friend-card-body">
                <div class="friend-card-avatar" style="background:${avatarGradient(f.username)}">
                    ${f.username.charAt(0).toUpperCase()}
                </div>
                <div class="friend-card-name">${esc(f.username)}</div>
                <div class="friend-card-meta">
                    <i class="ri-time-line me-1"></i>Ami depuis ${formatDate(f.friends_since)}
                </div>
                <div class="friend-card-actions">
                    <button class="btn btn-primary btn-sm" onclick="startConversation(${f.id}, '${escAttr(f.username)}')">
                        <i class="ri-message-2-fill me-1"></i>Message
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="viewProfile(${f.id})">
                        <i class="ri-user-fill me-1"></i>Profil
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

/* ════════════ LOAD REQUESTS ════════════ */
async function loadRequests() {
    const data = await apiFetch(`${BASE_URL}/api/friends.php?action=requests`);
    if (!data.success) return;
    requests = data.requests;
    renderRequests();
    updateRequestsBadge();
}

function renderRequests() {
    const list = document.getElementById('requestsList');

    if (!requests.length) {
        list.innerHTML = `
            <div class="empty-state-modern">
                <div class="empty-icon"><i class="ri-user-add-line"></i></div>
                <h5>Aucune demande en attente</h5>
                <p>Vous n'avez pas de nouvelles demandes d'amis pour le moment.</p>
            </div>`;
        return;
    }

    list.innerHTML = requests.map(r => `
        <div class="request-card" id="req-${r.id}">
            <div class="req-avatar" style="background:${avatarGradient(r.username)}">
                ${r.username.charAt(0).toUpperCase()}
            </div>
            <div class="req-info">
                <div class="req-name">${esc(r.username)}</div>
                <div class="req-time">
                    <i class="ri-time-line me-1"></i>${formatDate(r.created_at)}
                    ${r.bio ? `<br><span class="text-muted" style="font-size:.78rem">${esc(r.bio.substring(0,60))}${r.bio.length>60?'…':''}</span>` : ''}
                </div>
            </div>
            <div class="req-actions">
                <button class="btn btn-primary btn-sm" onclick="acceptRequest(${r.id})">
                    <i class="ri-check-fill me-1"></i>Accepter
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="viewProfile(${r.user_id || r.id}, true)">
                    <i class="ri-eye-fill me-1"></i>Profil
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="rejectRequest(${r.id})">
                    <i class="ri-close-fill"></i>
                </button>
            </div>
        </div>
    `).join('');
}

/* ════════════ LOAD SUGGESTIONS ════════════ */
async function loadSuggestions() {
    const data = await apiFetch(`${BASE_URL}/api/friends.php?action=discover`);
    if (!data.success) return;
    suggestions = data.suggestions;
    renderSuggestions();
}

function renderSuggestions() {
    const list = document.getElementById('suggestionsList');

    if (!suggestions.length) {
        list.innerHTML = `
            <div class="empty-state-modern">
                <div class="empty-icon"><i class="ri-compass-3-line"></i></div>
                <h5>Aucune suggestion</h5>
                <p>Revenez plus tard pour découvrir de nouvelles personnes.</p>
            </div>`;
        return;
    }

    list.innerHTML = suggestions.map(s => `
        <div class="suggestion-card" id="sug-${s.id}">
            <div class="sug-avatar" style="background:${avatarGradient(s.username)}">
                ${s.username.charAt(0).toUpperCase()}
            </div>
            <div class="sug-info">
                <div class="sug-name">${esc(s.username)}</div>
                <div class="sug-meta">
                    <i class="ri-group-line me-1"></i>${s.friends_count || 0} ami(s) en commun
                    ${s.bio ? `<br><span>${esc(s.bio.substring(0,50))}${s.bio.length>50?'…':''}</span>` : ''}
                </div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <button class="btn btn-primary btn-sm" onclick="addFriend(${s.id})">
                    <i class="ri-user-add-fill me-1"></i>Ajouter
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="viewProfile(${s.id})">
                    <i class="ri-eye-fill"></i>
                </button>
            </div>
        </div>
    `).join('');
}

/* ════════════ LOAD BLOCKED ════════════ */
async function loadBlocked() {
    const data = await apiFetch(`${BASE_URL}/api/block.php?action=list`);
    if (!data.success) return;
    blockedUsers = data.blocked_users;
    renderBlocked();
}

function renderBlocked() {
    const list = document.getElementById('blockedList');

    if (!blockedUsers.length) {
        list.innerHTML = `
            <div class="empty-state-modern">
                <div class="empty-icon" style="background:#fff3e0;color:#f7981c"><i class="ri-shield-user-line"></i></div>
                <h5>Aucun utilisateur bloqué</h5>
                <p>Les personnes que vous bloquez apparaîtront ici.</p>
            </div>`;
        return;
    }

    list.innerHTML = blockedUsers.map(u => `
        <div class="blocked-card" id="blk-${u.id}">
            <div class="blk-avatar">${u.username.charAt(0).toUpperCase()}</div>
            <div class="blk-info">
                <div class="blk-name">${esc(u.username)}</div>
                <div class="blk-label"><i class="ri-shield-fill me-1"></i>Bloqué le ${formatDate(u.blocked_at)}</div>
            </div>
            <button class="btn btn-outline-warning btn-sm" onclick="unblockUser(${u.id}, '${escAttr(u.username)}')">
                <i class="ri-shield-check-fill me-1"></i>Débloquer
            </button>
        </div>
    `).join('');
}

/* ════════════ SEARCH ════════════ */
function setupSearchListeners() {
    const input = document.getElementById('searchInput');
    input.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = input.value.trim();
        if (q.length < 2) {
            document.getElementById('searchResults').innerHTML = `
                <div class="empty-state-modern">
                    <div class="empty-icon"><i class="ri-search-2-line"></i></div>
                    <h5>Trouvez des personnes</h5>
                    <p>Tapez au moins 2 caractères pour lancer la recherche.</p>
                </div>`;
            return;
        }
        searchTimer = setTimeout(() => doSearch(q), 450);
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            clearTimeout(searchTimer);
            const q = input.value.trim();
            if (q.length >= 2) doSearch(q);
        }
    });
}

function setupFilterChips() {
    document.querySelectorAll('.chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentFilter = chip.dataset.filter;
            renderSearchResults();
        });
    });
}

async function doSearch(query) {
    const container = document.getElementById('searchResults');
    container.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary" style="width:2rem;height:2rem"></div></div>`;

    const data = await apiFetch(`${BASE_URL}/api/friends.php?action=search&q=${encodeURIComponent(query)}`);
    if (!data.success) { toast('Erreur lors de la recherche', 'error'); return; }
    searchResults = data.users;
    renderSearchResults();
}

function renderSearchResults() {
    const container = document.getElementById('searchResults');

    let filtered = searchResults;
    if (currentFilter === 'friend') filtered = searchResults.filter(u => u.friendship_status === 'friend');
    if (currentFilter === 'none')   filtered = searchResults.filter(u => u.friendship_status === 'none');

    if (!filtered.length) {
        container.innerHTML = `
            <div class="empty-state-modern">
                <div class="empty-icon"><i class="ri-user-search-line"></i></div>
                <h5>Aucun résultat</h5>
                <p>Essayez un autre terme de recherche ou modifiez le filtre.</p>
            </div>`;
        return;
    }

    container.innerHTML = filtered.map(u => `
        <div class="suggestion-card" id="search-${u.id}">
            <div class="sug-avatar" style="background:${avatarGradient(u.username)}">
                ${u.username.charAt(0).toUpperCase()}
            </div>
            <div class="sug-info">
                <div class="sug-name">${esc(u.username)}</div>
                <div class="sug-meta">${getFriendshipLabel(u.friendship_status)}</div>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                ${getFriendshipActions(u)}
            </div>
        </div>
    `).join('');
}

function getFriendshipLabel(status) {
    const map = {
        friend:   '<span style="color:#42b72a"><i class="ri-user-follow-fill me-1"></i>Ami</span>',
        pending:  '<span style="color:#f7981c"><i class="ri-time-fill me-1"></i>Demande envoyée</span>',
        received: '<span style="color:#1877f2"><i class="ri-user-add-fill me-1"></i>Demande reçue</span>',
        none:     '<span style="color:#65676b"><i class="ri-user-line me-1"></i>Pas encore ami</span>',
    };
    return map[status] || map.none;
}

function getFriendshipActions(u) {
    switch (u.friendship_status) {
        case 'friend':
            return `
                <button class="btn btn-outline-primary btn-sm" onclick="startConversation(${u.id},'${escAttr(u.username)}')">
                    <i class="ri-message-2-fill"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="viewProfile(${u.id})">
                    <i class="ri-eye-fill"></i>
                </button>`;
        case 'pending':
            return `<button class="btn btn-outline-secondary btn-sm" disabled>
                <i class="ri-time-fill me-1"></i>En attente</button>`;
        case 'received':
            return `
                <button class="btn btn-success btn-sm" onclick="acceptRequestByUserId(${u.friendship_request_id})">
                    <i class="ri-check-fill me-1"></i>Accepter
                </button>`;
        default:
            return `
                <button class="btn btn-primary btn-sm" onclick="addFriend(${u.id})">
                    <i class="ri-user-add-fill me-1"></i>Ajouter
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="viewProfile(${u.id})">
                    <i class="ri-eye-fill"></i>
                </button>`;
    }
}

/* ════════════ ACTIONS ════════════ */
async function addFriend(userId) {
    const res = await postJSON(`${BASE_URL}/api/friends.php?action=add`, { user_id: userId });
    if (res.success) {
        toast('Demande d\'ami envoyée !', 'success');
        // update local state
        const u = searchResults.find(x => x.id == userId) || suggestions.find(x => x.id == userId);
        if (u) u.friendship_status = 'pending';
        renderSearchResults();
        renderSuggestions();
    } else {
        toast(res.error || 'Erreur', 'error');
    }
}

async function acceptRequest(requestId) {
    const res = await postJSON(`${BASE_URL}/api/friends.php?action=accept`, { request_id: requestId });
    if (res.success) {
        toast('Demande acceptée ! Vous êtes maintenant amis.', 'success');
        document.getElementById(`req-${requestId}`)?.remove();
        requests = requests.filter(r => r.id != requestId);
        updateRequestsBadge();
        loadFriends();
    } else {
        toast(res.error || 'Erreur', 'error');
    }
}

async function acceptRequestByUserId(requestId) {
    await acceptRequest(requestId);
}

async function rejectRequest(requestId) {
    confirm(
        'Refuser la demande',
        'Cette action ne peut pas être annulée.',
        'Refuser', 'btn-danger',
        async () => {
            const res = await postJSON(`${BASE_URL}/api/friends.php?action=reject`, { request_id: requestId });
            if (res.success) {
                toast('Demande refusée.', 'info');
                document.getElementById(`req-${requestId}`)?.remove();
                requests = requests.filter(r => r.id != requestId);
                updateRequestsBadge();
            } else {
                toast(res.error || 'Erreur', 'error');
            }
        }
    );
}

function confirmRemove(userId, name) {
    confirm(
        `Supprimer ${name} ?`,
        'Vous ne serez plus amis et devrez renvoyer une demande pour vous reconnecter.',
        'Supprimer', 'btn-danger',
        async () => {
            const res = await postJSON(`${BASE_URL}/api/friends.php?action=remove`, { user_id: userId });
            if (res.success) {
                toast(`${name} a été supprimé de vos amis.`, 'info');
                document.getElementById(`friend-${userId}`)?.remove();
                friends = friends.filter(f => f.id != userId);
            } else {
                toast(res.error || 'Erreur', 'error');
            }
        }
    );
}

function confirmBlock(userId, name) {
    confirm(
        `Bloquer ${name} ?`,
        `${name} ne pourra plus vous envoyer de demandes d'ami ni vous contacter.`,
        'Bloquer', 'btn-warning',
        async () => {
            const res = await postJSON(`${BASE_URL}/api/block.php?action=block`, { user_id: userId });
            if (res.success) {
                toast(`${name} a été bloqué.`, 'warning');
                document.getElementById(`friend-${userId}`)?.remove();
                friends = friends.filter(f => f.id != userId);
                loadBlocked();
            } else {
                toast(res.error || 'Erreur', 'error');
            }
        }
    );
}

async function unblockUser(userId, name) {
    confirm(
        `Débloquer ${name} ?`,
        `${name} pourra de nouveau vous envoyer des demandes d'ami.`,
        'Débloquer', 'btn-primary',
        async () => {
            const res = await postJSON(`${BASE_URL}/api/block.php?action=unblock`, { user_id: userId });
            if (res.success) {
                toast(`${name} a été débloqué.`, 'success');
                document.getElementById(`blk-${userId}`)?.remove();
                blockedUsers = blockedUsers.filter(u => u.id != userId);
            } else {
                toast(res.error || 'Erreur', 'error');
            }
        }
    );
}

function startConversation(userId, name) {
    window.location.href = `${BASE_URL}/messages.php`;
}

/* ════════════ VIEW PROFILE (modal) ════════════ */
async function viewProfile(userId, isRequest = false) {
    const modal = new bootstrap.Modal(document.getElementById('profileModal'));
    const body  = document.getElementById('profileModalBody');

    body.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`;
    modal.show();

    // Fetch profile data from friends API (search with ID)
    const data = await apiFetch(`${BASE_URL}/api/friends.php?action=search&q=`);
    // Use existing friend data or fetch minimal profile
    const friend = [...friends, ...requests, ...suggestions, ...searchResults].find(u => u.id == userId || u.user_id == userId);

    const uName   = friend?.username || `Utilisateur #${userId}`;
    const uBio    = friend?.bio || '';
    const uStatus = friend?.friendship_status || (friends.find(f => f.id == userId) ? 'friend' : 'none');

    body.innerHTML = `
        <div class="profile-modal-cover"></div>
        <div class="profile-modal-body">
            <div class="profile-modal-avatar" style="background:${avatarGradient(uName)}; margin-top:-45px">
                ${uName.charAt(0).toUpperCase()}
            </div>
            <h5 class="text-center fw-bold mb-1">${esc(uName)}</h5>
            ${uBio ? `<p class="text-center text-muted small mb-3">${esc(uBio)}</p>` : '<p class="text-center text-muted small mb-3">Pas de bio</p>'}

            <div class="profile-stat-box">
                <div class="profile-stat-item">
                    <strong><i class="ri-user-3-fill"></i></strong>
                    <span>Profil</span>
                </div>
                <div class="profile-stat-item">
                    <strong>${getFriendshipBadge(uStatus)}</strong>
                    <span>Relation</span>
                </div>
            </div>

            <div class="profile-action-row">
                ${getProfileActions(userId, uStatus, uName)}
            </div>

            <div class="d-flex justify-content-center mt-2">
                <a href="${BASE_URL}/profile.php?id=${userId}" class="btn btn-link text-primary fw-semibold" target="_blank">
                    <i class="ri-external-link-fill me-1"></i>Voir le profil complet
                </a>
            </div>
        </div>
    `;
}

function getFriendshipBadge(status) {
    const map = {
        friend:   '<span style="color:#42b72a;font-size:.8rem">✅ Ami</span>',
        pending:  '<span style="color:#f7981c;font-size:.8rem">⏳ En attente</span>',
        received: '<span style="color:#1877f2;font-size:.8rem">📩 Reçue</span>',
        none:     '<span style="color:#65676b;font-size:.8rem">— Aucune</span>',
    };
    return map[status] || map.none;
}

function getProfileActions(userId, status, name) {
    switch (status) {
        case 'friend':
            return `
                <button class="btn btn-primary" onclick="startConversation(${userId},'${escAttr(name)}')">
                    <i class="ri-message-2-fill me-1"></i>Message
                </button>
                <button class="btn btn-outline-danger" onclick="confirmBlock(${userId},'${escAttr(name)}');bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide()">
                    <i class="ri-shield-user-fill me-1"></i>Bloquer
                </button>`;
        case 'pending':
            return `<button class="btn btn-outline-secondary" disabled><i class="ri-time-fill me-1"></i>Demande envoyée</button>`;
        case 'none':
        default:
            return `
                <button class="btn btn-primary" onclick="addFriend(${userId})">
                    <i class="ri-user-add-fill me-1"></i>Ajouter en ami
                </button>
                <button class="btn btn-outline-warning" onclick="confirmBlock(${userId},'${escAttr(name)}');bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide()">
                    <i class="ri-shield-user-fill me-1"></i>Bloquer
                </button>`;
    }
}

/* ════════════ BADGES ════════════ */
function updateRequestsBadge() {
    const badge = document.getElementById('requestsBadge');
    if (requests.length > 0) {
        badge.textContent = requests.length;
        badge.style.display = '';
    } else {
        badge.style.display = 'none';
    }
}

async function updateBadges() {
    try {
        const msg = await apiFetch(`${BASE_URL}/api/messages.php?action=list`);
        if (msg.success) {
            const cnt = msg.conversations.reduce((s, c) => s + (c.unread_count > 0 ? c.unread_count : 0), 0);
            const el  = document.getElementById('msgBadge');
            el.textContent  = cnt;
            el.style.display = cnt > 0 ? '' : 'none';
        }
    } catch(e) {}
}

/* ════════════ HELPERS ════════════ */
function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(str) {
    return String(str).replace(/'/g,"\\'").replace(/"/g,'&quot;');
}
function avatarGradient(name) {
    const colors = [
        ['#1877f2','#1565d8'],['#42b72a','#36a420'],['#e53935','#b71c1c'],
        ['#f7981c','#e67e00'],['#8e24aa','#6a1b9a'],['#00897b','#00695c'],
        ['#1565d8','#0d47a1'],['#d81b60','#880e4f'],
    ];
    const idx = (name.charCodeAt(0) || 0) % colors.length;
    return `linear-gradient(135deg, ${colors[idx][0]}, ${colors[idx][1]})`;
}
function formatDate(dateStr) {
    if (!dateStr) return '';
    const d    = new Date(dateStr);
    const now  = new Date();
    const diff = now - d;
    if (diff < 86400000)    return 'aujourd\'hui';
    if (diff < 172800000)   return 'hier';
    if (diff < 604800000)   return `il y a ${Math.floor(diff/86400000)} j`;
    return d.toLocaleDateString('fr-FR', { day:'numeric', month:'short', year: d.getFullYear()!==now.getFullYear()?'numeric':undefined });
}
</script>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/js/topbar-search.js"></script></body>
</html>