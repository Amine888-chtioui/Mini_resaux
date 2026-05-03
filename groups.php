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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Profile CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/css/profile.css">

    <style>
        .groups-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .groups-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #dddfe2;
        }

        .groups-tab {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: #65676b;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .groups-tab:hover {
            color: #1877f2;
        }

        .groups-tab.active {
            color: #1877f2;
            border-bottom-color: #1877f2;
        }

        .groups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .group-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .group-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .group-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dddfe2;
        }

        .group-icon-large {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }

        .group-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1c1e21;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .group-members-count {
            text-align: center;
            color: #65676b;
            font-size: 0.9rem;
        }

        .group-body {
            padding: 1.5rem;
        }

        .group-description {
            color: #65676b;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }

        .group-actions {
            display: flex;
            gap: 0.5rem;
        }

        .group-actions .btn {
            flex: 1;
        }

        .group-role {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin {
            background: #e3f2fd;
            color: #1976d2;
        }

        .role-member {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .discover-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .discover-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .discover-card:hover {
            transform: translateY(-2px);
        }

        .discover-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .discover-info {
            flex: 1;
            min-width: 0;
        }

        .discover-name {
            font-weight: 600;
            color: #1c1e21;
            margin-bottom: 0.25rem;
        }

        .discover-meta {
            font-size: 0.85rem;
            color: #65676b;
        }

        .create-group-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .group-members-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .member-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .member-item:hover {
            background: #f8f9fa;
        }

        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1877f2, #1565d8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .member-info {
            flex: 1;
        }

        .member-name {
            font-weight: 600;
            color: #1c1e21;
            margin-bottom: 0.25rem;
        }

        .member-role {
            font-size: 0.85rem;
            color: #65676b;
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

        @media (max-width: 768px) {
            .groups-tabs {
                overflow-x: auto;
                white-space: nowrap;
            }

            .groups-grid {
                grid-template-columns: 1fr;
            }

            .discover-grid {
                grid-template-columns: 1fr;
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

                <button class="topbar-nav-icon active" title="Groupes">
                    <i class="ri-group-fill"></i>
                </button>

                <button class="topbar-nav-icon" title="Notifications" onclick="location.href='<?= BASE_URL ?>/notifications.php'">
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
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/friends.php"><i class="ri-user-3-fill me-2"></i>Amis</a></li>
                        <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/auth/logout.php"><i class="ri-logout-box-r-fill me-2"></i>Se déconnecter</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Groupes -->
    <div class="groups-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="ri-group-fill me-3"></i>Groupes
                    </h1>
                    <p class="lead mb-0">Rejoignez des communautés, créez vos groupes et partagez avec des personnes ayant les mêmes intérêts.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-light btn-lg" onclick="switchTab('create')">
                        <i class="ri-add-circle-fill me-2"></i>Créer un groupe
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="container">
        <!-- Onglets -->
        <div class="groups-tabs">
            <button class="groups-tab active" data-tab="my-groups">
                <i class="ri-group-fill me-2"></i>Mes groupes
            </button>
            <button class="groups-tab" data-tab="discover">
                <i class="ri-compass-3-fill me-2"></i>Découvrir
            </button>
            <button class="groups-tab" data-tab="create">
                <i class="ri-add-circle-fill me-2"></i>Créer
            </button>
        </div>

        <!-- Contenu des onglets -->
        <div id="tabContent">
            <!-- Tab: Mes groupes -->
            <div class="tab-pane" id="tab-my-groups">
                <div class="groups-grid" id="myGroupsGrid">
                    <!-- Groupes chargés via JavaScript -->
                </div>
            </div>

            <!-- Tab: Découvrir -->
            <div class="tab-pane" id="tab-discover" style="display: none;">
                <div class="discover-grid" id="discoverGrid">
                    <!-- Groupes découverts via JavaScript -->
                </div>
            </div>

            <!-- Tab: Créer -->
            <div class="tab-pane" id="tab-create" style="display: none;">
                <div class="create-group-section">
                    <h4 class="mb-4">Créer un nouveau groupe</h4>
                    <form id="createGroupForm">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom du groupe *</label>
                            <input type="text" class="form-control" name="name" required maxlength="100" placeholder="Entrez le nom du groupe">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Décrivez votre groupe..."></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Icône</label>
                                <select class="form-select" name="icon">
                                    <option value="ri-group-fill">👥 Groupe</option>
                                    <option value="ri-building-4-fill">🏢 Bâtiment</option>
                                    <option value="ri-code-box-fill">💻 Code</option>
                                    <option value="ri-football-fill">⚽ Sport</option>
                                    <option value="ri-music-fill">🎵 Musique</option>
                                    <option value="ri-book-fill">📚 Livre</option>
                                    <option value="ri-gamepad-fill">🎮 Jeu</option>
                                    <option value="ri-camera-fill">📷 Photo</option>
                                    <option value="ri-restaurant-fill">🍽️ Restaurant</option>
                                    <option value="ri-plane-fill">✈️ Voyage</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Couleur</label>
                                <input type="color" class="form-control form-control-color" name="color" value="#1877f2">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Confidentialité</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="privacy" id="privacyPublic" value="public" checked>
                                <label class="form-check-label" for="privacyPublic">
                                    <i class="ri-earth-fill me-1"></i>Public - Tout le monde peut rejoindre
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="privacy" id="privacyPrivate" value="private">
                                <label class="form-check-label" for="privacyPrivate">
                                    <i class="ri-lock-fill me-1"></i>Privé - Sur invitation uniquement
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-check-fill me-2"></i>Créer le groupe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails du groupe -->
    <div class="modal fade" id="groupDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-custom">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupDetailsTitle">Détails du groupe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="groupDetailsContent">
                        <!-- Contenu chargé via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const CURRENT_USER_ID = <?= (int) $userId ?>;
        // Variables globales
        let myGroups = [];
        let discoverGroups = [];
        let currentTab = 'my-groups';

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            loadMyGroups();
            loadDiscoverGroups();
            updateBadges();
        });

        // Configuration des écouteurs
        function setupEventListeners() {
            // Onglets
            document.querySelectorAll('.groups-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.dataset.tab;
                    switchTab(tabName);
                });
            });

            // Formulaire de création
            document.getElementById('createGroupForm').addEventListener('submit', handleCreateGroup);
        }

        // Changer d'onglet
        function switchTab(tabName) {
            currentTab = tabName;

            // Mettre à jour les onglets
            document.querySelectorAll('.groups-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

            // Afficher le contenu correspondant
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.style.display = 'none';
            });
            document.getElementById(`tab-${tabName}`).style.display = 'block';
        }

        // Charger mes groupes
        async function loadMyGroups() {
            try {
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=list');
                const result = await response.json();

                if (result.success) {
                    myGroups = result.groups;
                    displayMyGroups();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Afficher mes groupes
        function displayMyGroups() {
            const container = document.getElementById('myGroupsGrid');

            if (myGroups.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="ri-group-line"></i>
                            <h5>Aucun groupe</h5>
                            <p>Rejoignez des groupes ou créez le vôtre pour commencer.</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = myGroups.map(group => `
                <div class="group-card">
                    <div class="group-header">
                        <div class="group-icon-large" style="background: ${group.color}20; color: ${group.color}">
                            <i class="${group.icon}"></i>
                        </div>
                        <h5 class="group-name">${group.name}</h5>
                        <div class="group-members-count">${group.members_count} membres</div>
                    </div>
                    <div class="group-body">
                        ${group.description ? `<p class="group-description">${group.description}</p>` : ''}
                        <div class="mb-3">
                            <span class="group-role ${group.role === 'admin' ? 'role-admin' : 'role-member'}">
                                ${group.role === 'admin' ? 'Admin' : 'Membre'}
                            </span>
                        </div>
                        <div class="group-actions">
                            <button class="btn btn-primary btn-sm" onclick="viewGroupDetails(${group.id})">
                                <i class="ri-eye-fill"></i> Voir
                            </button>
                            ${group.role === 'admin' ? `
                                <button class="btn btn-outline-secondary btn-sm" onclick="manageGroup(${group.id})">
                                    <i class="ri-settings-fill"></i> Gérer
                                </button>
                            ` : `
                                <button class="btn btn-outline-danger btn-sm" onclick="leaveGroup(${group.id})">
                                    <i class="ri-logout-box-r-fill"></i> Quitter
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Charger les groupes à découvrir
        async function loadDiscoverGroups() {
            try {
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=discover');
                const result = await response.json();

                if (result.success) {
                    discoverGroups = result.groups;
                    displayDiscoverGroups();
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Afficher les groupes à découvrir
        function displayDiscoverGroups() {
            const container = document.getElementById('discoverGrid');

            if (discoverGroups.length === 0) {
                container.innerHTML = `
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="ri-compass-3-line"></i>
                            <h5>Aucun groupe à découvrir</h5>
                            <p>Créez des groupes ou revenez plus tard.</p>
                        </div>
                    </div>
                `;
                return;
            }

            container.innerHTML = discoverGroups.map(group => `
                <div class="discover-card">
                    <div class="discover-icon" style="background: ${group.color}20; color: ${group.color}">
                        <i class="${group.icon}"></i>
                    </div>
                    <div class="discover-info">
                        <div class="discover-name">${group.name}</div>
                        <div class="discover-meta">${group.members_count} membres</div>
                        ${group.description ? `<div class="discover-meta">${group.description.substring(0, 50)}...</div>` : ''}
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="joinGroup(${group.id})">
                        <i class="ri-add-fill"></i>
                    </button>
                </div>
            `).join('');
        }

        // Créer un groupe
        async function handleCreateGroup(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="ri-loader-4-line spin me-2"></i>Création...';

            try {
                console.log('Envoi du formulaire de création de groupe'); // Debug
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=create', {
                    method: 'POST',
                    body: formData
                });

                console.log('Response status:', response.status); // Debug
                const result = await response.json();
                console.log('Response result:', result); // Debug

                if (result.success) {
                    showNotification('Groupe créé avec succès !', 'success');
                    e.target.reset();

                    // Recharger les groupes
                    loadMyGroups();
                    switchTab('my-groups');
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
        }

        // Rejoindre un groupe
        async function joinGroup(groupId) {
            console.log('joinGroup appelé avec groupId:', groupId); // Debug
            try {
                console.log('Envoi de la requête join pour groupId:', groupId); // Debug
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=join', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        group_id: groupId
                    })
                });

                console.log('Response status:', response.status); // Debug
                const result = await response.json();
                console.log('Response result:', result); // Debug

                if (result.success) {
                    showNotification('Vous avez rejoint le groupe !', 'success');
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

        // Quitter un groupe
        async function leaveGroup(groupId) {
            console.log('leaveGroup appelé avec groupId:', groupId); // Debug
            if (!confirm('Voulez-vous vraiment quitter ce groupe ?')) return;

            try {
                console.log('Envoi de la requête leave pour groupId:', groupId); // Debug
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=leave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        group_id: groupId
                    })
                });

                console.log('Response status:', response.status); // Debug
                const result = await response.json();
                console.log('Response result:', result); // Debug

                if (result.success) {
                    showNotification('Vous avez quitté le groupe', 'info');
                    loadMyGroups();
                } else {
                    throw new Error(result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        // Voir les détails d'un groupe
        async function viewGroupDetails(groupId) {
            console.log('viewGroupDetails appelé avec groupId:', groupId); // Debug
            try {
                console.log('Requête members pour groupId:', groupId); // Debug
                const response = await fetch(`<?= BASE_URL ?>/api/groups.php?action=members&group_id=${groupId}`);
                console.log('Response status:', response.status); // Debug
                const result = await response.json();
                console.log('Response result:', result); // Debug

                if (result.success) {
                    const group = myGroups.find(g => g.id == groupId);
                    const modal = new bootstrap.Modal(document.getElementById('groupDetailsModal'));

                    document.getElementById('groupDetailsTitle').textContent = group.name;
                    document.getElementById('groupDetailsContent').innerHTML = `
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="group-icon-large" style="background: ${group.color}20; color: ${group.color}">
                                    <i class="${group.icon}"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">${group.name}</h5>
                                    <p class="text-muted mb-0">${group.members_count} membres</p>
                                </div>
                            </div>
                            ${group.description ? `<p class="text-muted">${group.description}</p>` : ''}
                            <div>
                                <span class="group-role ${group.role === 'admin' ? 'role-admin' : 'role-member'}">
                                    ${group.role === 'admin' ? 'Admin' : 'Membre'}
                                </span>
                            </div>
                        </div>
                        ${group.role === 'admin' ? `
                            <div class="mb-4 p-3 border rounded">
                                <h6 class="fw-bold mb-3">Gestion admin</h6>
                                <div class="d-flex gap-2 mb-3">
                                    <input type="text" class="form-control" id="renameGroupInput" maxlength="100" value="${group.name}" placeholder="Nouveau nom du groupe">
                                    <button class="btn btn-outline-primary" onclick="renameGroup(${group.id})">
                                        <i class="ri-edit-2-line me-1"></i>Renommer
                                    </button>
                                </div>
                                <div class="d-flex gap-2">
                                    <input type="text" class="form-control" id="newMemberUsernameInput" placeholder="Nom d'utilisateur à ajouter">
                                    <button class="btn btn-primary" onclick="addMemberToGroup(${group.id})">
                                        <i class="ri-user-add-fill me-1"></i>Ajouter
                                    </button>
                                </div>
                                <small class="text-muted">Seuls vos amis peuvent être ajoutés.</small>
                            </div>
                        ` : ''}
                        
                        <h6 class="fw-bold mb-3">Membres (${result.members.length})</h6>
                        <div class="group-members-list">
                            ${result.members.map(member => `
                                <div class="member-item">
                                    <div class="member-avatar">${member.username.charAt(0).toUpperCase()}</div>
                                    <div class="member-info">
                                        <div class="member-name">${member.username}</div>
                                        <div class="member-role">${member.role === 'admin' ? 'Admin' : 'Membre'} - Membre depuis ${formatDate(member.joined_at)}</div>
                                    </div>
                                    ${group.role === 'admin' && member.role !== 'admin' ? `
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeMemberFromGroup(${group.id}, ${member.id})">
                                            <i class="ri-delete-bin-6-line"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    `;

                    modal.show();
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur lors du chargement des détails', 'error');
            }
        }

        // Gérer un groupe
        function manageGroup(groupId) {
            viewGroupDetails(groupId);
        }

        async function addMemberToGroup(groupId) {
            const input = document.getElementById('newMemberUsernameInput');
            const username = (input.value || '').trim();
            if (!username) {
                showNotification('Veuillez saisir un nom d\'utilisateur valide', 'error');
                return;
            }

            try {
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=add_member', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: groupId, username })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'Ajout impossible');

                showNotification('Membre ajouté avec succès', 'success');
                input.value = '';
                viewGroupDetails(groupId);
            } catch (error) {
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        async function renameGroup(groupId) {
            const input = document.getElementById('renameGroupInput');
            const name = (input.value || '').trim();
            if (!name) {
                showNotification('Nom du groupe requis', 'error');
                return;
            }

            try {
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=rename', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: groupId, name })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'Renommage impossible');

                showNotification('Nom du groupe mis à jour', 'success');
                await loadMyGroups();
                await loadDiscoverGroups();
                viewGroupDetails(groupId);
            } catch (error) {
                showNotification('Erreur: ' + error.message, 'error');
            }
        }

        async function removeMemberFromGroup(groupId, memberId) {
            if (!confirm('Supprimer ce membre du groupe ?')) return;

            try {
                const response = await fetch('<?= BASE_URL ?>/api/groups.php?action=remove_member', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: groupId, user_id: memberId })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.error || 'Suppression impossible');

                showNotification('Membre supprimé', 'success');
                viewGroupDetails(groupId);
            } catch (error) {
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
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Formatage de la date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;

            if (diff < 86400000) {
                return "aujourd'hui";
            } else if (diff < 172800000) {
                return 'hier';
            } else if (diff < 604800000) {
                return `il y a ${Math.floor(diff / 86400000)} jours`;
            } else {
                return date.toLocaleDateString('fr-FR', {
                    day: 'numeric',
                    month: 'short',
                    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
                });
            }
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

        // Animation spinner
        const style = document.createElement('style');
        style.textContent = '.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    </script>
    <script>const BASE_URL = '<?= BASE_URL ?>';</script>
    <script src="<?= BASE_URL ?>/js/topbar-search.js"></script>
</body>

</html>