<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Vérifier session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=auth');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];
$initial = mb_strtoupper(mb_substr($username, 0, 1, 'UTF-8'), 'UTF-8');

$pageTitle = 'Amis — ' . SITE_NAME;
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
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container py-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Amis</h1>
                    <p class="text-muted mb-0">Gérez vos relations et trouvez de nouveaux amis</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#findFriendsModal">
                    <i class="ri-search-line me-2"></i>Trouver des amis
                </button>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="friendsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-friends-tab" data-bs-toggle="tab" data-bs-target="#all-friends" type="button" role="tab">
                        Tous les amis
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="friend-requests-tab" data-bs-toggle="tab" data-bs-target="#friend-requests" type="button" role="tab">
                        Demandes d'amis
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="find-friends-tab" data-bs-toggle="tab" data-bs-target="#find-friends" type="button" role="tab">
                        Trouver des amis
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="friendsTabsContent">
                <!-- All Friends -->
                <div class="tab-pane fade show active" id="all-friends" role="tabpanel">
                    <div id="allFriendsContent" class="row g-4">
                        <!-- Friends will be loaded here -->
                    </div>
                </div>

                <!-- Friend Requests -->
                <div class="tab-pane fade" id="friend-requests" role="tabpanel">
                    <div id="friendRequestsContent" class="row g-4">
                        <!-- Friend requests will be loaded here -->
                    </div>
                </div>

                <!-- Find Friends -->
                <div class="tab-pane fade" id="find-friends" role="tabpanel">
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" placeholder="Rechercher des amis par nom ou email...">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="ri-search-line"></i>
                            </button>
                        </div>
                    </div>
                    <div id="findFriendsContent" class="row g-4">
                        <!-- Search results will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Find Friends Modal -->
    <div class="modal fade" id="findFriendsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Trouver des amis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modalSearchInput" class="form-label">Rechercher par nom ou email</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="modalSearchInput" placeholder="Entrez un nom ou email...">
                            <button class="btn btn-primary" type="button" id="modalSearchBtn">
                                <i class="ri-search-line me-2"></i>Rechercher
                            </button>
                        </div>
                    </div>
                    <div id="modalSearchResults">
                        <!-- Search results will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        let allFriends = [];
        let friendRequests = [];
        let searchResults = [];

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadAllFriends();
            loadFriendRequests();
        });

        // Load all friends
        async function loadAllFriends() {
            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=list`);
                const result = await response.json();

                if (result.success) {
                    allFriends = result.friends;
                    renderAllFriends();
                }
            } catch (error) {
                console.error('Error loading friends:', error);
            }
        }

        // Load friend requests
        async function loadFriendRequests() {
            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=requests`);
                const result = await response.json();

                if (result.success) {
                    friendRequests = result.requests;
                    renderFriendRequests();
                }
            } catch (error) {
                console.error('Error loading friend requests:', error);
            }
        }

        // Render all friends
        function renderAllFriends() {
            const container = document.getElementById('allFriendsContent');

            if (allFriends.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="ri-user-unfollow-line display-1 text-muted"></i>
                            <h4 class="mt-3">Aucun ami</h4>
                            <p class="text-muted">Trouvez des amis pour commencer à socialiser.</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = allFriends.map(friend => `
                <div class="col-md-6 col-lg-4">
                    <div class="card friend-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="friend-avatar me-3">
                                    ${friend.username.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">${friend.username}</h6>
                                    <p class="text-muted small mb-0">Ami depuis ${new Date(friend.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="${BASE_URL}?page=messages&user_id=${friend.id}" class="btn btn-sm btn-outline-primary">
                                    <i class="ri-message-3-line me-1"></i>Message
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeFriend(${friend.id})">
                                    <i class="ri-user-unfollow-line me-1"></i>Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Render friend requests
        function renderFriendRequests() {
            const container = document.getElementById('friendRequestsContent');

            if (friendRequests.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="ri-user-add-line display-1 text-muted"></i>
                            <h4 class="mt-3">Aucune demande d'ami</h4>
                            <p class="text-muted">Vous n'avez pas de demandes d'amis en attente.</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = friendRequests.map(request => `
                <div class="col-md-6 col-lg-4">
                    <div class="card friend-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="friend-avatar me-3">
                                    ${request.username.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">${request.username}</h6>
                                    <p class="text-muted small mb-0">Demande envoyée le ${new Date(request.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-success" onclick="acceptRequest(${request.id})">
                                    <i class="ri-check-line me-1"></i>Accepter
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="rejectRequest(${request.id})">
                                    <i class="ri-close-line me-1"></i>Refuser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Search friends
        async function searchFriends(query) {
            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=search&q=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (result.success) {
                    searchResults = result.users;
                    renderSearchResults();
                }
            } catch (error) {
                console.error('Error searching friends:', error);
            }
        }

        // Render search results
        function renderSearchResults() {
            const container = document.getElementById('findFriendsContent');
            const modalContainer = document.getElementById('modalSearchResults');

            const renderFunction = (container) => {
                if (searchResults.length === 0) {
                    container.innerHTML = `
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="ri-search-line display-1 text-muted"></i>
                                <h4 class="mt-3">Aucun résultat</h4>
                                <p class="text-muted">Essayez une autre recherche.</p>
                            </div>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = searchResults.map(user => `
                    <div class="col-md-6 col-lg-4">
                        <div class="card friend-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="friend-avatar me-3">
                                        ${user.username.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1">${user.username}</h6>
                                        <p class="text-muted small mb-0">${user.bio || 'Pas de bio'}</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    ${getFriendshipActions(user)}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            };

            renderFunction(container);
            renderFunction(modalContainer);
        }

        // Get friendship actions
        function getFriendshipActions(user) {
            switch (user.friendship_status) {
                case 'friend':
                    return `
                        <a href="${BASE_URL}?page=messages&user_id=${user.id}" class="btn btn-sm btn-outline-primary">
                            <i class="ri-message-3-line me-1"></i>Message
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFriend(${user.id})">
                            <i class="ri-user-unfollow-line me-1"></i>Supprimer
                        </button>
                    `;
                case 'pending':
                    return `<button class="btn btn-sm btn-outline-secondary" disabled>En attente</button>`;
                case 'received':
                    return `
                        <button class="btn btn-sm btn-success" onclick="acceptRequest(${user.friendship_request_id})">
                            <i class="ri-check-line me-1"></i>Accepter
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="rejectRequest(${user.friendship_request_id})">
                            <i class="ri-close-line me-1"></i>Refuser
                        </button>
                    `;
                default:
                    return `<button class="btn btn-sm btn-primary" onclick="addFriend(${user.id})">
                        <i class="ri-user-add-line me-1"></i>Ajouter
                    </button>`;
            }
        }

        // Add friend
        async function addFriend(userId) {
            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=add`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Demande d\'ami envoyée !', 'success');
                    loadFriendRequests();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        // Accept friend request
        async function acceptRequest(requestId) {
            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=accept`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        request_id: requestId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification 'Demande acceptée !', 'success');
                loadAllFriends();
                loadFriendRequests();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('Erreur: ' + error.message, 'error');
        }
        }

        // Reject friend request
        async function rejectRequest(requestId) {
            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        request_id: requestId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Demande refusée', 'info');
                    loadFriendRequests();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        // Remove friend
        async function removeFriend(userId) {
            if (!confirm('Voulez-vous vraiment supprimer cet ami ?')) return;

            try {
                const response = await fetch(`${BASE_URL}/api/friends.php?action=remove`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Ami supprimé', 'info');
                    loadAllFriends();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        // Search functionality
        document.getElementById('searchBtn').addEventListener('click', function() {
            const query = document.getElementById('searchInput').value.trim();
            if (query.length >= 2) {
                searchFriends(query);
            }
        });

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query.length >= 2) {
                    searchFriends(query);
                }
            }
        });

        document.getElementById('modalSearchBtn').addEventListener('click', function() {
            const query = document.getElementById('modalSearchInput').value.trim();
            if (query.length >= 2) {
                searchFriends(query);
            }
        });

        document.getElementById('modalSearchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query.length >= 2) {
                    searchFriends(query);
                }
            }
        });

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
</body>

</html>