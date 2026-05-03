<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/includes/db.php';

// ─── PHPMailer (via Composer ou manuel) ────────────────────────────────────
// Si Composer : require_once dirname(__DIR__) . '/vendor/autoload.php';
// Si manuel   : adaptez le chemin ci-dessous
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─── Constantes SMTP ─── MODIFIEZ CES VALEURS ──────────────────────────────
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'alaeddineelouaddah09@gmail.com');   // Votre adresse Gmail
define('SMTP_PASS', 'cfyp toxy gtjh uwgi');      // Mot de passe d'application Google
define('SMTP_PORT', 587);
define('SMTP_FROM_NAME', SITE_NAME);

// ─── Fonction d'envoi d'email ───────────────────────────────────────────────
function sendMail(string $to, string $subject, string $body): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ─── Template email ─────────────────────────────────────────────────────────
function emailTemplate(string $title, string $content): string
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'MonSite';
    return "
    <div style='font-family:Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);'>
      <div style='background:#1877f2;padding:32px 40px 24px;text-align:center;'>
        <h1 style='color:#fff;margin:0;font-size:1.7rem;letter-spacing:-0.5px;'>{$siteName}</h1>
      </div>
      <div style='padding:36px 40px;'>
        <h2 style='color:#1877f2;font-size:1.2rem;margin:0 0 16px;'>{$title}</h2>
        {$content}
        <p style='color:#888;font-size:12px;margin-top:32px;border-top:1px solid #eee;padding-top:16px;'>
          Si vous n'avez pas effectué cette action, ignorez cet email.
        </p>
      </div>
    </div>";
}

// ─── Redirection si déjà connecté ───────────────────────────────────────────
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/profile.php?id=' . (int) $_SESSION['user_id']);
    exit;
}

// ─── État initial ────────────────────────────────────────────────────────────
$message = '';
$messageType = 'error';   // 'error' | 'success'
$errorSource = '';
$showActif = isset($_GET['mode']) && $_GET['mode'] === 'inscription';
$showVerif = false;
$showReset = false;
$showNewPass = false;
$showResetCode = false;
$verifEmail = '';
$resetEmail = '';
$resetToken = isset($_GET['reset_token']) ? trim($_GET['reset_token']) : '';

// ─── Paramètre GET : mode ────────────────────────────────────────────────────
if (isset($_GET['mode'])) {
    if ($_GET['mode'] === 'verify') {
        $showVerif = true;
        $verifEmail = trim($_GET['email'] ?? '');
    }
    if ($_GET['mode'] === 'reset') {
        $showReset = true;
    }
    if ($_GET['mode'] === 'resetcode') {
        $showResetCode = true;
        $resetEmail = trim($_GET['email'] ?? '');
    }
    if ($_GET['mode'] === 'newpass' && $resetToken !== '') {
        $showNewPass = true;
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  TRAITEMENTS POST
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── 1. INSCRIPTION ──────────────────────────────────────────────────────
    if (isset($_POST['inscription'])) {
        $errorSource = 'inscription';
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $message = 'Remplissez tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Adresse email invalide.';
        } elseif (strlen($password) < 8) {
            $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $code = (string) random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 heure

            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, email, password_hash, email_verified, verification_code, verification_expires)
                     VALUES (?, ?, ?, 0, ?, ?)'
                );
                $stmt->execute([$username, $email, $hash, $code, $expires]);

                $body = emailTemplate(
                    'Vérification de votre adresse email',
                    "<p style='color:#333;font-size:1rem;'>Bonjour <strong>{$username}</strong>,</p>
                     <p style='color:#333;'>Votre code de vérification est :</p>
                     <div style='text-align:center;margin:28px 0;'>
                       <span style='font-size:2.5rem;font-weight:bold;letter-spacing:12px;color:#1877f2;background:#e7f3ff;padding:16px 28px;border-radius:10px;'>{$code}</span>
                     </div>
                     <p style='color:#555;font-size:0.95rem;'>Ce code expire dans <strong>1 heure</strong>.</p>"
                );

                if (!sendMail($email, SITE_NAME . ' — Code de vérification', $body)) {
                    $message = "Compte créé mais l'email n'a pas pu être envoyé. Contactez le support.";
                    $messageType = 'error';
                    $showActif = true;
                } else {
                    header('Location: ' . BASE_URL . '/auth/connexion.php?mode=verify&email=' . urlencode($email));
                    exit;
                }
            } catch (\PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    $message = 'Ce nom d\'utilisateur ou cet email existe déjà.';
                } else {
                    $message = 'Inscription impossible pour le moment.';
                }
            }
        }
        $showActif = true;
    }

    // ── 2. VÉRIFICATION DU CODE EMAIL ───────────────────────────────────────
    elseif (isset($_POST['verify_code'])) {
        $showVerif = true;
        $verifEmail = trim((string) ($_POST['verif_email'] ?? ''));
        $inputCode = trim((string) ($_POST['code'] ?? ''));

        $stmt = $pdo->prepare(
            'SELECT id, username, verification_code, verification_expires
             FROM users WHERE email = ? AND email_verified = 0 LIMIT 1'
        );
        $stmt->execute([$verifEmail]);
        $row = $stmt->fetch();

        if (!$row) {
            $message = 'Aucun compte en attente pour cet email.';
        } elseif (new DateTime() > new DateTime($row['verification_expires'])) {
            $message = 'Code expiré. Veuillez vous réinscrire.';
        } elseif (!hash_equals($row['verification_code'], $inputCode)) {
            $message = 'Code incorrect.';
        } else {
            $pdo->prepare('UPDATE users SET email_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = ?')
                ->execute([$row['id']]);
            $_SESSION['user_id'] = (int) $row['id'];
            $_SESSION['username'] = $row['username'];
            header('Location: ' . BASE_URL . '/profile.php?id=' . $_SESSION['user_id']);
            exit;
        }
        $errorSource = 'verify';
    }

    // ── 3. RENVOI DU CODE ───────────────────────────────────────────────────
    elseif (isset($_POST['resend_code'])) {
        $showVerif = true;
        $verifEmail = trim((string) ($_POST['verif_email'] ?? ''));

        $stmt = $pdo->prepare(
            'SELECT id, username FROM users WHERE email = ? AND email_verified = 0 LIMIT 1'
        );
        $stmt->execute([$verifEmail]);
        $row = $stmt->fetch();

        if ($row) {
            $code = (string) random_int(100000, 999999);
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $pdo->prepare('UPDATE users SET verification_code = ?, verification_expires = ? WHERE id = ?')
                ->execute([$code, $expires, $row['id']]);

            $body = emailTemplate(
                'Nouveau code de vérification',
                "<p style='color:#333;'>Voici votre nouveau code :</p>
                 <div style='text-align:center;margin:28px 0;'>
                   <span style='font-size:2.5rem;font-weight:bold;letter-spacing:12px;color:#1877f2;background:#e7f3ff;padding:16px 28px;border-radius:10px;'>{$code}</span>
                 </div>
                 <p style='color:#555;font-size:0.95rem;'>Ce code expire dans <strong>1 heure</strong>.</p>"
            );
            sendMail($verifEmail, SITE_NAME . ' — Nouveau code', $body);
            $message = 'Un nouveau code a été envoyé à votre adresse email.';
            $messageType = 'success';
        } else {
            $message = 'Aucun compte en attente pour cet email.';
        }
        $errorSource = 'verify';
    }

    // ── 4. CONNEXION ────────────────────────────────────────────────────────
    elseif (isset($_POST['connexion'])) {
        $errorSource = 'connexion';
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $message = 'Remplissez tous les champs.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, username, password_hash, email_verified FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if (!(bool) $user['email_verified']) {
                    header('Location: ' . BASE_URL . '/auth/connexion.php?mode=verify&email=' . urlencode($email));
                    exit;
                }
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: ' . BASE_URL . '/profile.php?id=' . $_SESSION['user_id']);
                exit;
            }
            $message = 'Email ou mot de passe incorrect.';
        }
        $showActif = false;
    }

    // ── 5. MOT DE PASSE OUBLIÉ : demande ────────────────────────────────────
    elseif (isset($_POST['forgot_password'])) {
        $showReset = true;
        $errorSource = 'reset';
        $email = trim((string) ($_POST['reset_email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Adresse email invalide.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username FROM users WHERE email = ? AND email_verified = 1 LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Message neutre pour éviter l'énumération d'emails
            $message = 'Si cet email est enregistré, un code de réinitialisation vous a été envoyé.';
            $messageType = 'success';

            if ($user) {
                $code = (string) random_int(100000, 999999);
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 heure
                $pdo->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?')
                    ->execute([$code, $expires, $user['id']]);

                $body = emailTemplate(
                    'Code de réinitialisation de mot de passe',
                    "<p style='color:#333;'>Bonjour <strong>{$user['username']}</strong>,</p>
                     <p style='color:#333;'>Votre code de réinitialisation de mot de passe est :</p>
                     <div style='text-align:center;margin:28px 0;'>
                       <span style='font-size:2.5rem;font-weight:bold;letter-spacing:12px;color:#1877f2;background:#e7f3ff;padding:16px 28px;border-radius:10px;'>{$code}</span>
                     </div>
                     <p style='color:#555;font-size:0.95rem;'>Ce code expire dans <strong>1 heure</strong>.</p>
                     <p style='color:#888;font-size:0.85rem;'>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>"
                );
                sendMail($email, SITE_NAME . ' — Code de réinitialisation', $body);
            }

            // Rediriger vers la page de saisie du code dans tous les cas pour éviter l'énumération
            header('Location: ' . BASE_URL . '/auth/connexion.php?mode=resetcode&email=' . urlencode($email));
            exit;
        }
    }

    // ── 6. MOT DE PASSE OUBLIÉ : vérification du code ─────────────────────────
    elseif (isset($_POST['verify_reset_code'])) {
        $showResetCode = true;
        $resetEmail = trim((string) ($_POST['reset_email'] ?? ''));
        $inputCode = trim((string) ($_POST['code'] ?? ''));

        $stmt = $pdo->prepare(
            'SELECT id, username, reset_token, reset_expires
             FROM users WHERE email = ? AND email_verified = 1 LIMIT 1'
        );
        $stmt->execute([$resetEmail]);
        $row = $stmt->fetch();

        if (!$row) {
            $message = 'Aucun compte trouvé pour cet email.';
        } elseif (new DateTime() > new DateTime($row['reset_expires'])) {
            $message = 'Code expiré. Veuillez demander un nouveau code.';
        } elseif (!hash_equals($row['reset_token'], $inputCode)) {
            $message = 'Code incorrect.';
        } else {
            // Code valide, rediriger vers la page de nouveau mot de passe
            header('Location: ' . BASE_URL . '/auth/connexion.php?mode=newpass&reset_token=' . $inputCode);
            exit;
        }
        $errorSource = 'resetcode';
    }

    // ── 7. MOT DE PASSE OUBLIÉ : nouveau mot de passe ───────────────────────
    elseif (isset($_POST['new_password'])) {
        $errorSource = 'newpass';
        $resetToken = trim((string) ($_POST['reset_token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $password2 = (string) ($_POST['password2'] ?? '');

        if ($password === '' || $password2 === '') {
            $message = 'Remplissez tous les champs.';
        } elseif (strlen($password) < 8) {
            $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($password !== $password2) {
            $message = 'Les mots de passe ne correspondent pas.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1'
            );
            $stmt->execute([$resetToken]);
            $user = $stmt->fetch();

            if (!$user) {
                $message = 'Code invalide ou expiré.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
                    ->execute([$hash, $user['id']]);
                $message = 'Mot de passe mis à jour ! Vous pouvez vous connecter.';
                $messageType = 'success';
                $showNewPass = false;
            }
        }
    }
}

// ─── Variables de vue ────────────────────────────────────────────────────────
$base = BASE_URL;
$pageTitle = 'Connexion — ' . SITE_NAME;
$conteneurClass = 'conteneur' . ($showActif ? ' actif' : '');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
    </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/css/auth.css">
</head>

<body class="auth-page">

    <div class="auth-back">
        <a href="<?= htmlspecialchars($base . '/index.php', ENT_QUOTES, 'UTF-8') ?>">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Accueil
        </a>
    </div>

    <?php if ($showVerif): ?>
        <!-- ══════════════════════════════════════════════════════════════════════
       PANNEAU : VÉRIFICATION EMAIL
  ══════════════════════════════════════════════════════════════════════ -->
        <div class="panel-solo">
            <div class="panel-solo__icon"><i class="fas fa-envelope-open-text"></i></div>
            <h2 class="panel-solo__title">Vérifiez votre email</h2>
            <p class="panel-solo__text">
                Un code à 6 chiffres a été envoyé à<br>
                <strong>
                    <?= htmlspecialchars($verifEmail, ENT_QUOTES, 'UTF-8') ?>
                </strong><br>
                Il expire dans <strong>1 heure</strong>.
            </p>

            <?php if ($message !== ''): ?>
                <p class="<?= $messageType === 'success' ? 'success-hint' : 'error-message' ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post"
                action="<?= htmlspecialchars($base . '/auth/connexion.php?mode=verify&email=' . urlencode($verifEmail), ENT_QUOTES, 'UTF-8') ?>"
                class="panel-solo__form">
                <input type="hidden" name="verif_email" value="<?= htmlspecialchars($verifEmail, ENT_QUOTES, 'UTF-8') ?>">
                <div class="code-inputs">
                    <input type="text" name="code" id="code-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}"
                        placeholder="••••••" autocomplete="one-time-code" required>
                </div>
                <button type="submit" name="verify_code" value="1" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Vérifier
                </button>
            </form>


        </div>

    <?php elseif ($showReset): ?>
        <!-- ══════════════════════════════════════════════════════════════════════
       PANNEAU : MOT DE PASSE OUBLIÉ
  ══════════════════════════════════════════════════════════════════════ -->
        <div class="panel-solo" data-mode="reset">
            <div class="panel-solo__icon"><i class="fas fa-key"></i></div>
            <h2 class="panel-solo__title">Mot de passe oublié</h2>
            <p class="panel-solo__text">Entrez votre email et nous vous enverrons un code de réinitialisation.</p>

            <?php if ($message !== ''): ?>
                <p class="<?= $messageType === 'success' ? 'success-hint' : 'error-message' ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <?php if ($messageType !== 'success'): ?>
                <form method="post"
                    action="<?= htmlspecialchars($base . '/auth/connexion.php?mode=reset', ENT_QUOTES, 'UTF-8') ?>"
                    class="panel-solo__form">
                    <input type="email" name="reset_email" placeholder="Votre adresse email" required autocomplete="email">
                    <button type="submit" name="forgot_password" value="1" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Envoyer le code
                    </button>
                </form>
            <?php endif; ?>

            <a href="<?= htmlspecialchars($base . '/auth/connexion.php', ENT_QUOTES, 'UTF-8') ?>" class="btn-ghost"
                style="display:inline-block;margin-top:1rem;">
                <i class="fas fa-arrow-left"></i> Retour à la connexion
            </a>
        </div>

    <?php elseif ($showResetCode): ?>
        <!-- ══════════════════════════════════════════════════════════════════════
           PANNEAU : CODE DE RÉINITIALISATION
       ══════════════════════════════════════════════════════════════════════ -->
        <div class="panel-solo" data-mode="resetcode">
            <div class="panel-solo__icon"><i class="fas fa-shield-alt"></i></div>
            <h2 class="panel-solo__title">Code de réinitialisation</h2>
            <p class="panel-solo__text">
                Un code à 6 chiffres a été envoyé à<br>
                <strong>
                    <?= htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8') ?>
                </strong><br>
                Il expire dans <strong>1 heure</strong>.
            </p>

            <?php if ($message !== ''): ?>
                <p class="<?= $messageType === 'success' ? 'success-hint' : 'error-message' ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post"
                action="<?= htmlspecialchars($base . '/auth/connexion.php?mode=resetcode&email=' . urlencode($resetEmail), ENT_QUOTES, 'UTF-8') ?>"
                class="panel-solo__form">
                <input type="hidden" name="reset_email" value="<?= htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8') ?>">
                <div class="code-inputs">
                    <input type="text" name="code" id="reset-code-input" maxlength="6" inputmode="numeric"
                        pattern="[0-9]{6}" placeholder="••••••" autocomplete="one-time-code" required>
                </div>
                <button type="submit" name="verify_reset_code" value="1" class="btn-primary">
                    <i class="fas fa-check-circle"></i> Vérifier le code
                </button>
            </form>

            <form method="post"
                action="<?= htmlspecialchars($base . '/auth/connexion.php?mode=reset', ENT_QUOTES, 'UTF-8') ?>"
                style="margin-top:1rem;">
                <input type="hidden" name="reset_email" value="<?= htmlspecialchars($resetEmail, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="forgot_password" value="1">
                <button type="submit" class="btn-ghost">
                    <i class="fas fa-redo"></i> Renvoyer un code
                </button>
            </form>

            <a href="<?= htmlspecialchars($base . '/auth/connexion.php', ENT_QUOTES, 'UTF-8') ?>" class="btn-ghost"
                style="display:inline-block;margin-top:1rem;">
                <i class="fas fa-arrow-left"></i> Retour à la connexion
            </a>
        </div>

    <?php elseif ($showNewPass): ?>
        <!-- ══════════════════════════════════════════════════════════════════════
           PANNEAU : NOUVEAU MOT DE PASSE
       ══════════════════════════════════════════════════════════════════════ -->
        <div class="panel-solo" data-mode="newpass">
            <div class="panel-solo__icon"><i class="fas fa-lock-open"></i></div>
            <h2 class="panel-solo__title">Nouveau mot de passe</h2>
            <p class="panel-solo__text">Choisissez un nouveau mot de passe sécurisé (min. 8 caractères).</p>

            <?php if ($message !== ''): ?>
                <p class="<?= $messageType === 'success' ? 'success-hint' : 'error-message' ?>">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <form method="post"
                action="<?= htmlspecialchars($base . '/auth/connexion.php?mode=newpass&reset_token=' . urlencode($resetToken), ENT_QUOTES, 'UTF-8') ?>"
                class="panel-solo__form">
                <input type="hidden" name="reset_token" value="<?= htmlspecialchars($resetToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="input-eye-wrap">
                    <input type="password" name="password" id="np1" placeholder="Nouveau mot de passe" required
                        minlength="8" autocomplete="new-password">
                    <button type="button" class="eye-btn" data-target="np1"><i class="fas fa-eye"></i></button>
                </div>
                <div class="input-eye-wrap">
                    <input type="password" name="password2" id="np2" placeholder="Confirmer le mot de passe" required
                        minlength="8" autocomplete="new-password">
                    <button type="button" class="eye-btn" data-target="np2"><i class="fas fa-eye"></i></button>
                </div>
                <div id="strength-bar-wrap" style="width:100%;margin-top:4px;">
                    <div id="strength-bar"></div>
                    <span id="strength-label"></span>
                </div>
                <button type="submit" name="new_password" value="1" class="btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </form>
        </div>

    <?php else: ?>
        <!-- ══════════════════════════════════════════════════════════════════════
       PANNEAU PRINCIPAL : CONNEXION / INSCRIPTION
  ══════════════════════════════════════════════════════════════════════ -->
        <div class="auth-mobile-bar">
            <button type="button"
                class="js-show-connexion js-mb-conn<?= !$showActif ? ' is-active' : '' ?>">Connexion</button>
            <button type="button"
                class="js-show-inscription js-mb-insc<?= $showActif ? ' is-active' : '' ?>">Inscription</button>
        </div>

        <div class="<?= htmlspecialchars($conteneurClass, ENT_QUOTES, 'UTF-8') ?>" id="conteneur">

            <!-- ── CONNEXION ─────────────────────────────────────────────────── -->
            <div class="conteneur-formulaire connexion">
                <form method="post" action="<?= htmlspecialchars($base . '/auth/connexion.php', ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="on">
                    <h1>Connexion</h1>
                    <input type="email" name="email" placeholder="Email" required autocomplete="email"
                        value="<?= $errorSource === 'connexion' ? htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
                    <div class="input-eye-wrap">
                        <input type="password" name="password" id="conn-pw" placeholder="Mot de passe" required
                            autocomplete="current-password">
                        <button type="button" class="eye-btn" data-target="conn-pw"><i class="fas fa-eye"></i></button>
                    </div>
                    <?php if ($message !== '' && $errorSource === 'connexion'): ?>
                        <p class="error-message">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>
                    <button type="submit" name="connexion" value="1">Se connecter</button>
                    <a href="<?= htmlspecialchars($base . '/auth/connexion.php?mode=reset', ENT_QUOTES, 'UTF-8') ?>"
                        class="forgot-link">
                        <i class="fas fa-key"></i> Mot de passe oublié ?
                    </a>
                </form>
            </div>

            <!-- ── INSCRIPTION ───────────────────────────────────────────────── -->
            <div class="conteneur-formulaire inscription">
                <form method="post" action="<?= htmlspecialchars($base . '/auth/connexion.php', ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="on">
                    <h1>Créer un compte</h1>
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required maxlength="64"
                        autocomplete="username"
                        value="<?= $errorSource === 'inscription' ? htmlspecialchars((string) ($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
                    <input type="email" name="email" placeholder="Email" required autocomplete="email"
                        value="<?= $errorSource === 'inscription' ? htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') : '' ?>">
                    <div class="input-eye-wrap">
                        <input type="password" name="password" id="insc-pw" placeholder="Mot de passe (min. 8 caractères)"
                            required minlength="8" autocomplete="new-password">
                        <button type="button" class="eye-btn" data-target="insc-pw"><i class="fas fa-eye"></i></button>
                    </div>
                    <div id="strength-bar-wrap" style="width:100%;margin-top:4px;">
                        <div id="strength-bar-insc"></div>
                        <span id="strength-label-insc"></span>
                    </div>
                    <?php if ($message !== '' && $errorSource === 'inscription'): ?>
                        <p class="error-message">
                            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>
                    <button type="submit" name="inscription" value="1">S'inscrire</button>
                </form>
            </div>

            <!-- ── BASCULE ───────────────────────────────────────────────────── -->
            <div class="conteneur-bascule">
                <div class="bascule">
                    <div class="panneau-bascule panneau-gauche">
                        <h1>Content de vous revoir&nbsp;!</h1>
                        <p>Connectez-vous pour accéder à votre espace
                            <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>.
                        </p>
                        <button type="button" class="caché js-show-connexion">Connexion</button>
                    </div>
                    <div class="panneau-bascule panneau-droit">
                        <h1>Bienvenue&nbsp;!</h1>
                        <p>Rejoignez le réseau social de l'école à
                            <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?>.
                        </p>
                        <button type="button" class="caché js-show-inscription">Créer un compte</button>
                    </div>
                </div>
            </div>

        </div><!-- /.conteneur -->
    <?php endif; ?>

    <script src="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/js/auth.js"></script>
    <script>
        // ── Afficher / Cacher mot de passe ─────────────────────────────────────
        document.querySelectorAll('.eye-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const inp = document.getElementById(btn.dataset.target);
                if (!inp) return;
                const show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        });

        // ── Indicateur de force du mot de passe ───────────────────────────────
        function passwordStrength(pw) {
            let score = 0;
            if (pw.length >= 8) score++;
            if (pw.length >= 12) score++;
            if (/[A-Z]/.test(pw)) score++;
            if (/[0-9]/.test(pw)) score++;
            if (/[^A-Za-z0-9]/.test(pw)) score++;
            return score;
        }

        function applyStrength(inputId, barId, labelId) {
            const inp = document.getElementById(inputId);
            const bar = document.getElementById(barId);
            const lbl = document.getElementById(labelId);
            if (!inp || !bar || !lbl) return;
            inp.addEventListener('input', () => {
                const s = passwordStrength(inp.value);
                const labels = ['', 'Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
                const colors = ['', '#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#27ae60'];
                bar.style.width = (s * 20) + '%';
                bar.style.background = colors[s] || '#ddd';
                lbl.textContent = inp.value.length ? labels[s] : '';
                lbl.style.color = colors[s] || '';
            });
        }
        applyStrength('insc-pw', 'strength-bar-insc', 'strength-label-insc');
        applyStrength('np1', 'strength-bar', 'strength-label');

        // ── Auto-focus code OTP ────────────────────────────────────────────────
        const codeInput = document.getElementById('code-input');
        if (codeInput) {
            codeInput.focus();
            codeInput.addEventListener('input', () => {
                codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
            });
        }

        // ── Auto-focus reset code OTP ───────────────────────────────────────────
        const resetCodeInput = document.getElementById('reset-code-input');
        if (resetCodeInput) {
            resetCodeInput.focus();
            resetCodeInput.addEventListener('input', () => {
                resetCodeInput.value = resetCodeInput.value.replace(/\D/g, '').slice(0, 6);
            });
        }
    </script>
</body>

</html>