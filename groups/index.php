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

$pageTitle = 'Groupes — ' . SITE_NAME;
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
    <style>
        .group-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .group-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .group-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .icon-btn:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .icon-btn.active {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
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
                    <h1 class="h3 mb-1">Groupes</h1>
                    <p class="text-muted mb-0">Rejoignez des communautés et créez vos groupes</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="ri-add-line me-2"></i>Créer un groupe
                </button>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="groupsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="my-groups-tab" data-bs-toggle="tab" data-bs-target="#my-groups" type="button" role="tab">
                        Mes groupes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="discover-groups-tab" data-bs-toggle="tab" data-bs-target="#discover-groups" type="button" role="tab">
                        Découvrir
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="groupsTabsContent">
                <!-- My Groups -->
                <div class="tab-pane fade show active" id="my-groups" role="tabpanel">
                    <div id="myGroupsContent" class="row g-4">
                        <!-- Groups will be loaded here -->
                    </div>
                </div>

                <!-- Discover Groups -->
                <div class="tab-pane fade" id="discover-groups" role="tabpanel">
                    <div id="discoverGroupsContent" class="row g-4">
                        <!-- Groups will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Créer un groupe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createGroupForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="groupName" class="form-label">Nom du groupe *</label>
                            <input type="text" class="form-control" id="groupName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="groupDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="groupDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="groupPrivacy" class="form-label">Confidentialité</label>
                            <select class="form-select" id="groupPrivacy" name="privacy">
                                <option value="public">Public</option>
                                <option value="private">Privé</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line me-2"></i>Créer
                        </button>
                    </div>
                </form>
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
        let myGroups = [];
        let discoverGroups = [];

        // Load groups when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadMyGroups();
            loadDiscoverGroups();
        });

        // Load user's groups
        async function loadMyGroups() {
            try {
                const response = await fetch(`${BASE_URL}/api/groups.php?action=list`);
                const result = await response.json();

                if (result.success) {
                    myGroups = result.groups;
                    renderMyGroups();
                }
            } catch (error) {
                console.error('Error loading groups:', error);
            }
        }

        // Load discover groups
        async function loadDiscoverGroups() {
            try {
                const response = await fetch(`${BASE_URL}/api/groups.php?action=discover`);
                const result = await response.json();

                if (result.success) {
                    discoverGroups = result.groups;
                    renderDiscoverGroups();
                }
            } catch (error) {
                console.error('Error loading discover groups:', error);
            }
        }

        // Render my groups
        function renderMyGroups() {
            const container = document.getElementById('myGroupsContent');

            if (myGroups.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="ri-group-line display-1 text-muted"></i>
                            <h4 class="mt-3">Aucun groupe</h4>
                            <p class="text-muted">Rejoignez des groupes ou créez le vôtre pour commencer.</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = myGroups.map(group => `
                <div class="col-md-6 col-lg-4">
                    <div class="card group-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="group-icon me-3" style="background: ${group.color}20; color: ${group.color}">
                                    <i class="${group.icon}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">${group.name}</h5>
                                    <p class="text-muted small mb-2">${group.member_count} membres</p>
                                </div>
                            </div>
                            ${group.description ? `<p class="card-text text-muted small">${group.description.substring(0, 100)}...</p>` : ''}
                            <div class="d-flex gap-2 mt-3">
                                ${group.is_member ? 
                                    `<button class="btn btn-sm btn-outline-danger" onclick="leaveGroup(${group.id})">
                                        <i class="ri-logout-box-r-line me-1"></i>Quitter
                                    </button>` :
                                    `<button class="btn btn-sm btn-primary" onclick="joinGroup(${group.id})">
                                        <i class="ri-add-line me-1"></i>Rejoindre
                                    </button>`
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Render discover groups
        function renderDiscoverGroups() {
            const container = document.getElementById('discoverGroupsContent');

            if (discoverGroups.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="ri-compass-3-line display-1 text-muted"></i>
                            <h4 class="mt-3">Aucun groupe à découvrir</h4>
                            <p class="text-muted">Créez le premier groupe pour commencer.</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = discoverGroups.map(group => `
                <div class="col-md-6 col-lg-4">
                    <div class="card group-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start mb-3">
                                <div class="group-icon me-3" style="background: ${group.color}20; color: ${group.color}">
                                    <i class="${group.icon}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">${group.name}</h5>
                                    <p class="text-muted small mb-2">${group.member_count} membres</p>
                                </div>
                            </div>
                            ${group.description ? `<p class="card-text text-muted small">${group.description.substring(0, 100)}...</p>` : ''}
                            <div class="d-flex gap-2 mt-3">
                                ${group.is_member ? 
                                    `<button class="btn btn-sm btn-outline-danger" onclick="leaveGroup(${group.id})">
                                        <i class="ri-logout-box-r-line me-1"></i>Quitter
                                    </button>` :
                                    `<button class="btn btn-sm btn-primary" onclick="joinGroup(${group.id})">
                                        <i class="ri-add-line me-1"></i>Rejoindre
                                    </button>`
                                }
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Join group
        async function joinGroup(groupId) {
            try {
                const response = await fetch(`${BASE_URL}/api/groups.php?action=join`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        group_id: groupId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Groupe rejoint avec succès !', 'success');
                    loadMyGroups();
                    loadDiscoverGroups();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        // Leave group
        async function leaveGroup(groupId) {
            if (!confirm('Voulez-vous vraiment quitter ce groupe ?')) return;

            try {
                const response = await fetch(`${BASE_URL}/api/groups.php?action=leave`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        group_id: groupId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Vous avez quitté le groupe', 'info');
                    loadMyGroups();
                    loadDiscoverGroups();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        // Create group form
        document.getElementById('createGroupForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line spin me-2"></i>Création...';

            try {
                const response = await fetch(`${BASE_URL}/api/groups.php?action=create`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Groupe créé avec succès !', 'success');
                    e.target.reset();

                    // Recharger les groupes
                    loadMyGroups();

                    // Fermer le modal
                    bootstrap.Modal.getInstance(document.getElementById('createGroupModal')).hide();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
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