<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

// Router pour authentification
$action = $_GET['mode'] ?? 'login';

if ($action === 'login') {
    // Page de connexion
    $pageTitle = 'Connexion — ' . SITE_NAME;
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
        <link href="<?= BASE_URL ?>/css/auth.css" rel="stylesheet">
        <style>
            .auth-container {
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .auth-card {
                background: white;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                max-width: 400px;
                width: 100%;
            }

            .auth-header {
                text-align: center;
                margin-bottom: 30px;
            }

            .auth-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 20px;
            }

            .auth-title {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 8px;
                color: #333;
            }

            .auth-subtitle {
                color: #6c757d;
                margin-bottom: 0;
            }

            .auth-form {
                margin-bottom: 20px;
            }

            .auth-footer {
                text-align: center;
                color: #6c757d;
            }

            .auth-footer a {
                color: #007bff;
                text-decoration: none;
            }

            .auth-footer a:hover {
                text-decoration: underline;
            }
        </style>
    </head>

    <body>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="<?= BASE_URL ?>/images/ensiasd-logo.svg" alt="<?= SITE_NAME ?>" class="auth-logo">
                    <h1 class="auth-title">Connexion</h1>
                    <p class="auth-subtitle">Connectez-vous à votre compte</p>
                </div>

                <form class="auth-form" method="POST" action="connexion.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">
                            Se souvenir de moi
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>

                <div class="auth-footer">
                    <p>Pas encore de compte ? <a href="?page=auth&mode=register">S'inscrire</a></p>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
} elseif ($action === 'register') {
    // Page d'inscription
    $pageTitle = 'Inscription — ' . SITE_NAME;
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
        <link href="<?= BASE_URL ?>/css/auth.css" rel="stylesheet">
        <style>
            .auth-container {
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .auth-card {
                background: white;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                max-width: 400px;
                width: 100%;
            }

            .auth-header {
                text-align: center;
                margin-bottom: 30px;
            }

            .auth-logo {
                width: 60px;
                height: 60px;
                margin-bottom: 20px;
            }

            .auth-title {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 8px;
                color: #333;
            }

            .auth-subtitle {
                color: #6c757d;
                margin-bottom: 0;
            }

            .auth-form {
                margin-bottom: 20px;
            }

            .auth-footer {
                text-align: center;
                color: #6c757d;
            }

            .auth-footer a {
                color: #007bff;
                text-decoration: none;
            }

            .auth-footer a:hover {
                text-decoration: underline;
            }
        </style>
    </head>

    <body>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="<?= BASE_URL ?>/images/ensiasd-logo.svg" alt="<?= SITE_NAME ?>" class="auth-logo">
                    <h1 class="auth-title">Inscription</h1>
                    <p class="auth-subtitle">Créez votre compte</p>
                </div>

                <form class="auth-form" method="POST" action="connexion.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                </form>

                <div class="auth-footer">
                    <p>Déjà un compte ? <a href="?page=auth&mode=login">Se connecter</a></p>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
}
?>