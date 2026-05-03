<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/connexion.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
require_once __DIR__ . '/includes/db.php';

// Récupérer les données de l'utilisateur
$stmt = $pdo->prepare('SELECT id, username, email, bio, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$success = '';
$error = '';

// Traitement des modifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        
        // Validation
        if (empty($username) || empty($email)) {
            $error = 'Le nom d\'utilisateur et l\'email sont requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'L\'adresse email n\'est pas valide.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Le nom d\'utilisateur doit contenir entre 3 et 50 caractères.';
        } elseif (strlen($bio) > 500) {
            $error = 'La bio ne doit pas dépasser 500 caractères.';
        } else {
            // Vérifier si le nom d'utilisateur est déjà utilisé par un autre utilisateur
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $checkStmt->execute([$username, $userId]);
            if ($checkStmt->fetch()) {
                $error = 'Ce nom d\'utilisateur est déjà utilisé.';
            } else {
                // Vérifier si l'email est déjà utilisé par un autre utilisateur
                $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                $checkStmt->execute([$email, $userId]);
                if ($checkStmt->fetch()) {
                    $error = 'Cette adresse email est déjà utilisée.';
                } else {
                    // Mettre à jour le profil
                    $updateStmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?');
                    if ($updateStmt->execute([$username, $email, $bio === '' ? null : $bio, $userId])) {
                        $success = 'Profil mis à jour avec succès !';
                        // Recharger les données
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch();
                    } else {
                        $error = 'Une erreur est survenue lors de la mise à jour.';
                    }
                }
            }
        }
    }
    
    if (isset($_POST['update_password'])) {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        
        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Tous les champs du mot de passe sont requis.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Les nouveaux mots de passe ne correspondent pas.';
        } else {
            // Vérifier le mot de passe actuel
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $userData = $stmt->fetch();
            
            if ($userData && password_verify($currentPassword, $userData['password'])) {
                // Mettre à jour le mot de passe
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                if ($updateStmt->execute([$hashedPassword, $userId])) {
                    $success = 'Mot de passe mis à jour avec succès !';
                } else {
                    $error = 'Une erreur est survenue lors de la mise à jour du mot de passe.';
                }
            } else {
                $error = 'Le mot de passe actuel est incorrect.';
            }
        }
    }
}

$base = BASE_URL;
$initial = mb_strtoupper(mb_substr($user['username'], 0, 1, 'UTF-8'), 'UTF-8');
$pageTitle = 'Paramètres du profil — ' . SITE_NAME;
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/css/profile.css">
</head>

<body class="profile-page">

    <!-- ════════════════════════════════════════════════════════════════════════
     TOPBAR ICONS
════════════════════════════════════════════════════════════════════════ -->
    <nav class="topbar">
        <div class="topbar-inner">

            <!-- Logo -->
            <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/index.php" class="topbar-logo">
                <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>
            </a>

            <!-- Search -->
            <div class="topbar-search">
                <i class="ri-search-line"></i>
                <input type="text" placeholder="Rechercher…" autocomplete="off">
            </div>

            <!-- Nav icons -->
            <div class="topbar-nav">

                <!-- Accueil -->
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/index.php" class="topbar-nav-icon" title="Accueil">
                    <i class="ri-home-5-fill"></i>
                </a>

                <!-- Messages -->
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/messages.php" class="topbar-nav-icon" title="Messages">
                    <i class="ri-message-2-fill"></i>
                </a>

                <!-- Groupes -->
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/groups.php" class="topbar-nav-icon" title="Groupes">
                    <i class="ri-group-fill"></i>
                </a>

                <!-- Notifications -->
                <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/notifications.php" class="topbar-nav-icon" title="Notifications">
                    <i class="ri-notification-3-fill"></i>
                </a>

                <!-- Avatar / menu profil -->
                <div class="dropdown">
                    <button class="topbar-avatar-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="topbar-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                        <li>
                            <a class="dropdown-item" href="<?= htmlspecialchars($base . '/profile.php?id=' . $userId, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="profile-dropdown-user">
                                    <div class="profile-dropdown-avatar"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div>
                                        <strong><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small>Voir votre profil</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item active" href="<?= htmlspecialchars($base . '/settings.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-settings-3-fill me-2"></i>Paramètres</a></li>
                        <li><a class="dropdown-item" href="<?= htmlspecialchars($base . '/friends.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-user-3-fill me-2"></i>Amis</a></li>
                        <li><a class="dropdown-item" href="<?= htmlspecialchars($base . '/messages.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-message-2-fill me-2"></i>Messages</a></li>
                        <li><a class="dropdown-item" href="<?= htmlspecialchars($base . '/groups.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-group-fill me-2"></i>Groupes</a></li>
                        <li><a class="dropdown-item text-danger" href="<?= htmlspecialchars($base . '/auth/logout.php', ENT_QUOTES, 'UTF-8') ?>"><i class="ri-logout-box-r-fill me-2"></i>Se déconnecter</a></li>
                    </ul>
                </div>
            </div><!-- /.topbar-nav -->
        </div>
    </nav><!-- /.topbar -->

    <!-- ════════════════════════════════════════════════════════════════════════
     CONTENU PRINCIPAL
════════════════════════════════════════════════════════════════════════ -->
    <div class="container profile-body mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <!-- Header -->
                <div class="d-flex align-items-center mb-4">
                    <h1 class="h2 mb-0">
                        <i class="ri-settings-3-fill me-2"></i>Paramètres du profil
                    </h1>
                    <a href="<?= htmlspecialchars($base . '/profile.php?id=' . $userId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary ms-auto">
                        <i class="ri-arrow-left-line me-1"></i>Retour au profil
                    </a>
                </div>

                <!-- Alertes -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ri-checkbox-circle-fill me-2"></i><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ri-error-warning-fill me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Informations du profil -->
                <div class="pcard mb-4">
                    <div class="pcard-head">
                        <h3 class="pcard-title mb-0">
                            <i class="ri-user-3-fill me-2"></i>Informations du profil
                        </h3>
                    </div>
                    <div class="pcard-body">
                        <form method="post">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-user-3-line"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>"
                                           required minlength="3" maxlength="50">
                                </div>
                                <small class="form-text text-muted">3-50 caractères</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-mail-line"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                           required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="500"
                                          placeholder="Décrivez-vous en quelques mots…"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                <small class="form-text text-muted">Maximum 500 caractères</small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line me-2"></i>Enregistrer les modifications
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="pcard">
                    <div class="pcard-head">
                        <h3 class="pcard-title mb-0">
                            <i class="ri-lock-password-fill me-2"></i>Changer le mot de passe
                        </h3>
                    </div>
                    <div class="pcard-body">
                        <form method="post">
                            <input type="hidden" name="update_password" value="1">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-lock-line"></i></span>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-lock-2-line"></i></span>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           required minlength="8">
                                </div>
                                <small class="form-text text-muted">Minimum 8 caractères</small>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ri-lock-2-line"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="ri-lock-password-line me-2"></i>Mettre à jour le mot de passe
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
