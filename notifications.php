<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Vérifier session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/connexion.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];
$initial = mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8');

$pageTitle = 'Notifications — ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Profile CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/css/profile.css">
    
    <style>
        .notifications-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .notifications-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .notifications-filters {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
            align-items: center;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 0.25rem;
            scrollbar-width: thin;
        }
        
        .filter-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 40px;
            padding: 0.5rem 0.95rem;
            background: none;
            border: 1px solid #dddfe2;
            border-radius: 20px;
            color: #65676b;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
            flex: 0 0 auto;
        }
        
        .filter-tab:hover {
            background: #f8f9fa;
            border-color: #1877f2;
            color: #1877f2;
        }
        
        .filter-tab.active {
            background: #1877f2;
            border-color: #1877f2;
            color: white;
        }
        
        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .notification-item.unread {
            background: #f8f9ff;
            border-left-color: #1877f2;
        }
        
        .notification-content {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        
        .notification-info {
            flex: 1;
            min-width: 0;
        }
        
        .notification-title {
            font-weight: 600;
            color: #1c1e21;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }
        
        .notification-message {
            color: #65676b;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }
        
        .notification-time {
            font-size: 0.8rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .notification-action {
            background: none;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            color: #65676b;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .notification-action:hover {
            background: #f8f9fa;
            border-color: #1877f2;
            color: #1877f2;
        }
        
        .notification-dot {
            width: 10px;
            height: 10px;
            background: #1877f2;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 0.25rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #65676b;
        }
        
        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .load-more-section {
            text-align: center;
            margin-top: 2rem;
        }
        
        .notification-type-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }
        
        .type-like { background: #e53935; }
        .type-comment { background: #42b72a; }
        .type-friend_request { background: #1877f2; }
        .type-friend_accept { background: #f7981c; }
        .type-message { background: #8e24aa; }
        .type-group_invite { background: #00897b; }
        .type-post_tag { background: #d81b60; }
        
        @media (max-width: 768px) {
            .notifications-container {
                padding: 0 1rem;
            }
            
            .filter-tabs {
                justify-content: flex-start;
            }
            
            .notification-content {
                gap: 0.75rem;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body class="profile-page">

    <!-- Topbar -->
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
                <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/index.php" class="topbar-nav-icon" title="Accueil">
                    <i class="ri-home-5-fill"></i>
                </a>
                
                <button class="topbar-nav-icon" title="Messages" onclick="location.href='<?= BASE_URL ?>/messages.php'">
                    <i class="ri-message-2-fill"></i>
                    <span class="badge-dot" id="msgBadge" style="display: none;">0</span>
                </button>
                
                <button class="topbar-nav-icon" title="Groupes" onclick="location.href='<?= BASE_URL ?>/groups.php'">
                    <i class="ri-group-fill"></i>
                </button>
                
                <button class="topbar-nav-icon active" title="Notifications">
                    <i class="ri-notification-3-fill"></i>
                    <span class="badge-dot" id="notifBadge" style="display: none;">0</span>
                </button>
                
                <div class="dropdown">
                    <button class="topbar-avatar-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="topbar-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                        <li>
                            <a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL . '/profile.php?id=' . $userId, ENT_QUOTES, 'UTF-8') ?>">
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
                        <li><a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/friends.php"><i class="ri-user-3-fill me-2"></i>Amis</a></li>
                        <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/auth/logout.php"><i class="ri-logout-box-r-fill me-2"></i>Se déconnecter</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Notifications -->
    <div class="notifications-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="ri-notification-3-fill me-3"></i>Notifications
                    </h1>
                    <p class="lead mb-0">Restez informé des dernières activités et interactions.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-light" onclick="markAllAsRead()">
                        <i class="ri-check-double-fill me-2"></i>Tout marquer comme lu
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container">
        <div class="notifications-container">
            
            <!-- Filtres -->
            <div class="notifications-filters">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 fw-bold">Filtrer par type</h5>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearReadNotifications()">
                        <i class="ri-delete-bin-fill me-1"></i>Supprimer les lues
                    </button>
                </div>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-type="all">Toutes</button>
                    <button class="filter-tab" data-type="unread">Non lues</button>
                    <button class="filter-tab" data-type="like">
                        <span class="notification-type-icon type-like"></span>J'aime
                    </button>
                    <button class="filter-tab" data-type="comment">
                        <span class="notification-type-icon type-comment"></span>Commentaires
                    </button>
                    <button class="filter-tab" data-type="friend_request">
                        <span class="notification-type-icon type-friend_request"></span>Demandes d'amis
                    </button>
                    <button class="filter-tab" data-type="message">
                        <span class="notification-type-icon type-message"></span>Messages
                    </button>
                    <button class="filter-tab" data-type="group_invite">
                        <span class="notification-type-icon type-group_invite"></span>Groupes
                    </button>
                </div>
            </div>

            <!-- Liste des notifications -->
            <div id="notificationsList">
                <!-- Notifications chargées via JavaScript -->
            </div>

            <!-- Bouton charger plus -->
            <div class="load-more-section">
                <button class="btn btn-outline-primary" id="loadMoreBtn" style="display: none;">
                    <i class="ri-refresh-line me-2"></i>Charger plus
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let notifications = [];
        let currentFilter = 'all';
        let offset = 0;
        let isLoading = false;
        let hasMore = true;
        
        // Configuration des types de notifications
        const notificationConfig = {
            like: { icon: 'ri-thumb-up-fill', color: '#e53935', label: 'J\'aime' },
            comment: { icon: 'ri-chat-1-fill', color: '#42b72a', label: 'Commentaire' },
            friend_request: { icon: 'ri-user-add-fill', color: '#1877f2', label: 'Demande d\'ami' },
            friend_accept: { icon: 'ri-user-follow-fill', color: '#f7981c', label: 'Ami accepté' },
            message: { icon: 'ri-message-2-fill', color: '#8e24aa', label: 'Message' },
            group_invite: { icon: 'ri-group-fill', color: '#00897b', label: 'Invitation groupe' },
            post_tag: { icon: 'ri-at-fill', color: '#d81b60', label: 'Tag' }
        };
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            loadNotifications(true);
            updateBadges();
        });
        
        // Configuration des écouteurs
        function setupEventListeners() {
            // Filtres
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.type;
                    offset = 0;
                    loadNotifications(true);
                });
            });
            
            // Load more
            document.getElementById('loadMoreBtn').addEventListener('click', () => {
                loadNotifications();
            });
        }
        
        // Charger les notifications
        async function loadNotifications(reset = false) {
            if (isLoading) return;
            isLoading = true;
            
            if (reset) {
                offset = 0;
                hasMore = true;
            }
            
            try {
                const response = await fetch(`<?= BASE_URL ?>/api/notifications.php?action=list&limit=20&offset=${offset}`);
                const result = await response.json();
                
                if (result.success) {
                    const newNotifications = result.notifications;
                    
                    if (reset) {
                        notifications = newNotifications;
                        displayNotifications();
                    } else {
                        notifications = notifications.concat(newNotifications);
                        appendNotifications(newNotifications);
                    }
                    
                    hasMore = newNotifications.length === 20;
                    document.getElementById('loadMoreBtn').style.display = hasMore ? 'inline-block' : 'none';
                    
                    if (notifications.length === 0 && offset === 0) {
                        document.getElementById('notificationsList').innerHTML = `
                            <div class="empty-state">
                                <i class="ri-notification-off-line"></i>
                                <h5>Aucune notification</h5>
                                <p>Vous n'avez aucune notification pour le moment.</p>
                            </div>
                        `;
                    }
                    
                    offset += newNotifications.length;
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur lors du chargement des notifications', 'error');
            } finally {
                isLoading = false;
            }
        }
        
        // Afficher les notifications
        function displayNotifications() {
            const container = document.getElementById('notificationsList');
            const filteredNotifications = filterNotifications(notifications);
            
            if (filteredNotifications.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="ri-notification-off-line"></i>
                        <h5>Aucune notification</h5>
                        <p>aucune notification ne correspond à ce filtre.</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filteredNotifications.map(notification => createNotificationHTML(notification)).join('');
        }
        
        // Ajouter des notifications (pour load more)
        function appendNotifications(newNotifications) {
            const container = document.getElementById('notificationsList');
            const filteredNotifications = filterNotifications(newNotifications);
            
            filteredNotifications.forEach(notification => {
                const div = document.createElement('div');
                div.innerHTML = createNotificationHTML(notification);
                container.appendChild(div.firstElementChild);
            });
        }
        
        // Filtrer les notifications
        function filterNotifications(notificationsToFilter) {
            if (currentFilter === 'all') {
                return notificationsToFilter;
            } else if (currentFilter === 'unread') {
                return notificationsToFilter.filter(n => !n.is_read);
            } else {
                return notificationsToFilter.filter(n => n.type === currentFilter);
            }
        }
        
        // Créer le HTML d'une notification
        function createNotificationHTML(notification) {
            const config = notificationConfig[notification.type] || { icon: 'ri-notification-fill', color: '#1877f2', label: notification.type };
            const isUnread = !notification.is_read;
            
            return `
                <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}">
                    <div class="notification-content">
                        <div class="notification-icon" style="background: ${config.color}20; color: ${config.color}">
                            <i class="${config.icon}"></i>
                        </div>
                        <div class="notification-info">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">
                                <i class="ri-time-line"></i>
                                ${formatTime(notification.created_at)}
                            </div>
                            ${notification.link ? `
                                <div class="notification-actions">
                                    <button class="notification-action" onclick="handleNotificationClick('${notification.link}')">
                                        <i class="ri-external-link-fill me-1"></i>Voir
                                    </button>
                                    ${isUnread ? `
                                        <button class="notification-action" onclick="markAsRead(${notification.id})">
                                            <i class="ri-check-fill me-1"></i>Marquer comme lu
                                        </button>
                                    ` : ''}
                                </div>
                            ` : ''}
                        </div>
                        ${isUnread ? '<div class="notification-dot"></div>' : ''}
                    </div>
                </div>
            `;
        }
        
        // Gérer le clic sur une notification
        function handleNotificationClick(link) {
            if (link.startsWith('/')) {
                window.location.href = '<?= BASE_URL ?>' + link;
            } else {
                window.location.href = link;
            }
        }
        
        // Marquer comme lu
        async function markAsRead(notificationId) {
            try {
                const response = await fetch('<?= BASE_URL ?>/api/notifications.php?action=mark_read', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({notification_id: notificationId})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Mettre à jour l'interface
                    const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.classList.remove('unread');
                        const dot = notificationElement.querySelector('.notification-dot');
                        if (dot) dot.remove();
                        
                        // Mettre à jour les actions
                        const actions = notificationElement.querySelector('.notification-actions');
                        if (actions) {
                            const readBtn = actions.querySelector('button:nth-child(2)');
                            if (readBtn) readBtn.remove();
                        }
                    }
                    
                    // Mettre à jour le compteur
                    updateBadges();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur lors du marquage comme lu', 'error');
            }
        }
        
        // Marquer tout comme lu
        async function markAllAsRead() {
            try {
                const response = await fetch('<?= BASE_URL ?>/api/notifications.php?action=mark_read', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Toutes les notifications marquées comme lues', 'success');
                    loadNotifications(true);
                    updateBadges();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }
        
        // Supprimer les notifications lues
        async function clearReadNotifications() {
            if (!confirm('Voulez-vous vraiment supprimer toutes les notifications lues ?')) return;
            
            try {
                const response = await fetch('<?= BASE_URL ?>/api/notifications.php?action=clear', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(`${result.deleted_count} notifications supprimées`, 'success');
                    loadNotifications(true);
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }
        
        // Mise à jour des badges
        async function updateBadges() {
            try {
                // Messages
                const msgResponse = await fetch('<?= BASE_URL ?>/api/messages.php?action=list');
                const msgResult = await msgResponse.json();
                if (msgResult.success) {
                    const unreadCount = msgResult.conversations.filter(c => c.unread_count > 0).reduce((sum, c) => sum + c.unread_count, 0);
                    const msgBadge = document.getElementById('msgBadge');
                    if (unreadCount > 0) {
                        msgBadge.textContent = unreadCount;
                        msgBadge.style.display = 'inline-block';
                    } else {
                        msgBadge.style.display = 'none';
                    }
                }
                
                // Notifications
                const notifResponse = await fetch('<?= BASE_URL ?>/api/notifications.php?action=count');
                const notifResult = await notifResponse.json();
                if (notifResult.success) {
                    const notifBadge = document.getElementById('notifBadge');
                    if (notifResult.unread_count > 0) {
                        notifBadge.textContent = notifResult.unread_count;
                        notifBadge.style.display = 'inline-block';
                    } else {
                        notifBadge.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
        
        // Formatage du temps
        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'À l\'instant';
            if (diff < 3600000) return `Il y a ${Math.floor(diff / 60000)} min`;
            if (diff < 86400000) return `Il y a ${Math.floor(diff / 3600000)} h`;
            if (diff < 604800000) return `Il y a ${Math.floor(diff / 86400000)} j`;
            
            return date.toLocaleDateString('fr-FR');
        }
        
        // Notification
        function showNotification(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
    </script>
    <script>const BASE_URL = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/js/topbar-search.js"></script>
</body>

</html>
