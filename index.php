<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/profile.php?id=' . (int) $_SESSION['user_id']);
    exit;
}

$base = BASE_URL;
$logoFile = is_file(__DIR__ . '/images/ensiasd-logo.png') ? 'ensiasd-logo.png' : 'ensiasd-logo.svg';
$logoUrl = $base . '/images/' . $logoFile;
$pageTitle = 'Accueil — ' . SITE_NAME . ' · ' . SITE_PLACE;

$registerUrl = htmlspecialchars($base . '/auth/connexion.php?mode=inscription', ENT_QUOTES, 'UTF-8');
$loginUrl = htmlspecialchars($base . '/auth/connexion.php', ENT_QUOTES, 'UTF-8');
$profileUrl = !empty($_SESSION['user_id'])
    ? htmlspecialchars($base . '/profile.php?id=' . (int) $_SESSION['user_id'], ENT_QUOTES, 'UTF-8')
    : '';

$phoneLabel = htmlspecialchars(CONTACT_PHONE_LABEL, ENT_QUOTES, 'UTF-8');
$phoneTel   = htmlspecialchars(CONTACT_PHONE_TEL, ENT_QUOTES, 'UTF-8');
$emailContact = htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8');
$wa = preg_replace('/\D+/', '', CONTACT_WHATSAPP);
$whatsappUrl = htmlspecialchars('https://wa.me/' . $wa, ENT_QUOTES, 'UTF-8');
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
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Space+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════════════
           ROOT VARIABLES
        ═══════════════════════════════════════════════════════ */
        :root {
            --lp-primary:        #1565c0;
            --lp-primary-dark:   #0d47a1;
            --lp-primary-light:  #1e88e5;
            --lp-accent:         #00c8ff;
            --lp-bg:             #f0f4fb;
            --lp-white:          #ffffff;
            --lp-dark:           #0a0f1e;
            --lp-dark-card:      #111827;
            --lp-text:           #1e293b;
            --lp-text-muted:     #64748b;
            --lp-border:         #e2e8f0;
            --lp-gradient:       linear-gradient(135deg, #1565c0 0%, #00c8ff 100%);
            --lp-shadow-sm:      0 2px 8px rgba(21,101,192,0.10);
            --lp-shadow-md:      0 8px 32px rgba(21,101,192,0.14);
            --lp-shadow-lg:      0 20px 60px rgba(21,101,192,0.18);
            --lp-radius:         16px;
            --lp-radius-sm:      10px;
            --lp-transition:     all .25s cubic-bezier(.4,0,.2,1);
        }

        /* ═══════════════════════════════════════════════════════
           GLOBAL RESET
        ═══════════════════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; overflow-x: hidden; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--lp-bg);
            color: var(--lp-text);
            overflow-x: hidden;
            line-height: 1.6;
            max-width: 100vw;
        }

        img { max-width: 100%; height: auto; display: block; }

        /* ═══════════════════════════════════════════════════════
           NAVBAR — FIXED TOP — MOBILE FIXED
        ═══════════════════════════════════════════════════════ */
        .lp-navbar {
            background: rgba(255,255,255,.97);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--lp-border);
            padding: 10px 0;
            transition: var(--lp-transition);
            z-index: 1050;
            width: 100%;
            left: 0; right: 0;
        }
        .lp-navbar .container {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
        }
        .lp-navbar.scrolled { box-shadow: var(--lp-shadow-md); padding: 6px 0; }

        /* Brand */
        .lp-brand {
            text-decoration: none !important;
            display: flex !important;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            min-width: 0;
        }
        .lp-brand-img { border-radius: 10px; object-fit: contain; flex-shrink: 0; width: 36px; height: 36px; }
        .lp-brand-name {
            font-weight: 800;
            font-size: .95rem;
            color: var(--lp-primary);
            line-height: 1.2;
            white-space: nowrap;
        }
        .lp-brand-place {
            font-size: .65rem;
            color: var(--lp-text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        /* Toggler */
        .lp-toggler {
            color: var(--lp-primary) !important;
            border: 2px solid var(--lp-primary) !important;
            padding: 6px 11px !important;
            border-radius: 10px !important;
            background: rgba(21,101,192,.05) !important;
            margin-left: auto;
            flex-shrink: 0;
        }
        .lp-toggler i { color: var(--lp-primary); font-size: 1.3rem; display: block; }
        .lp-toggler:focus { box-shadow: 0 0 0 3px rgba(21,101,192,.2) !important; outline: none !important; }

        /* Nav links */
        .lp-nav-link {
            font-weight: 600;
            font-size: .9rem;
            color: var(--lp-text) !important;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: var(--lp-transition);
        }
        .lp-nav-link:hover { color: var(--lp-primary) !important; background: rgba(21,101,192,.07); }

        /* ── Mobile collapse panel ── */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                position: absolute;
                top: 100%;
                left: 0; right: 0;
                background: var(--lp-white);
                border-top: 1px solid var(--lp-border);
                box-shadow: 0 8px 32px rgba(0,0,0,.12);
                padding: 16px 20px 20px;
                z-index: 1049;
                max-height: calc(100vh - 70px);
                overflow-y: auto;
            }
            .navbar-nav { flex-direction: column; gap: 2px !important; }
            .lp-nav-link { display: block; border-radius: 8px; padding: 10px 14px !important; }
            .navbar-collapse .d-flex {
                margin-top: 14px !important;
                padding-top: 14px;
                border-top: 1px solid var(--lp-border);
                flex-direction: column;
                gap: 8px !important;
            }
            .navbar-collapse .d-flex .btn {
                width: 100% !important;
                text-align: center;
                padding: 12px 20px !important;
                font-size: .95rem !important;
                border-radius: 10px !important;
            }
        }

        /* Buttons */
        .lp-btn-primary {
            background: var(--lp-gradient);
            color: #fff !important;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: .875rem;
            padding: 9px 20px;
            transition: var(--lp-transition);
            text-decoration: none !important;
        }
        .lp-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(21,101,192,.4);
            opacity: .92;
        }
        .lp-btn-outline {
            background: transparent;
            color: var(--lp-primary) !important;
            border: 2px solid var(--lp-primary);
            border-radius: 10px;
            font-weight: 700;
            font-size: .875rem;
            padding: 7px 20px;
            transition: var(--lp-transition);
            text-decoration: none !important;
        }
        .lp-btn-outline:hover {
            background: var(--lp-primary);
            color: #fff !important;
            transform: translateY(-1px);
        }
        .lp-btn-lg { padding: 13px 28px !important; font-size: 1rem !important; border-radius: 12px !important; }

        /* ═══════════════════════════════════════════════════════
           HERO
        ═══════════════════════════════════════════════════════ */
        .lp-hero {
            position: relative;
            background: linear-gradient(160deg, #e8f0fe 0%, #f0f4fb 50%, #e3f2fd 100%);
            overflow: hidden;
            padding-top: 80px;
        }
        /* Prevent any child from causing horizontal overflow */
        .lp-hero .container, .lp-hero .row { overflow-x: hidden; }
        .lp-hero-bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .35;
            pointer-events: none;
        }
        .orb1 { width: 500px; height: 500px; background: #1565c0; top: -150px; left: -150px; }
        .orb2 { width: 400px; height: 400px; background: #00c8ff; bottom: -100px; right: -100px; }
        .orb3 { width: 300px; height: 300px; background: #7c3aed; top: 50%; left: 50%; transform: translate(-50%,-50%); }

        .lp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(90deg,rgba(21,101,192,.12),rgba(0,200,255,.12));
            color: var(--lp-primary);
            border: 1px solid rgba(21,101,192,.25);
            border-radius: 999px;
            padding: 6px 16px;
            font-size: .8rem;
            font-weight: 700;
        }
        .lp-hero-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 900;
            line-height: 1.15;
            color: var(--lp-dark);
        }
        .lp-gradient-text {
            background: var(--lp-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .lp-hero-sub { font-size: 1.05rem; color: var(--lp-text-muted); max-width: 520px; }

        /* ── Hero Image ── */
        .lp-hero-img-wrap {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 540px;
            margin: 0 auto;
            /* Contain floating cards inside on mobile */
            padding: 0 20px;
        }
        @media (min-width: 576px) { .lp-hero-img-wrap { padding: 0 24px; } }
        @media (min-width: 992px) { .lp-hero-img-wrap { padding: 0 30px; } }
        .lp-hero-img {
            width: 100%;
            border-radius: 24px;
            box-shadow: var(--lp-shadow-lg);
            object-fit: cover;
            aspect-ratio: 4/3;
        }

        /* Floating cards */
        .lp-floating-card {
            position: absolute;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(12px);
            border: 1px solid var(--lp-border);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: .8rem;
            font-weight: 600;
            box-shadow: var(--lp-shadow-md);
            white-space: nowrap;
            animation: float 3s ease-in-out infinite;
            /* Stay inside wrapper */
            max-width: calc(100% - 20px);
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card1 { top: 8%; left: 0; animation-delay: 0s; }
        .card2 { top: 46%; right: 0; animation-delay: 1s; }
        .card3 { bottom: 8%; left: 8%; animation-delay: 2s; }
        @keyframes float {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
        @media (max-width: 575px) {
            .lp-floating-card { font-size: .68rem; padding: 6px 10px; border-radius: 8px; }
        }

        /* Stats */
        .lp-hero-stats { gap: 28px; flex-wrap: wrap; }
        .lp-stat { text-align: center; }
        .lp-stat-num {
            display: inline-block;
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--lp-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        .lp-stat small { display: block; font-size: .75rem; color: var(--lp-text-muted); font-weight: 500; margin-top: 2px; }
        .lp-stat-divider { width: 1px; background: var(--lp-border); height: 40px; align-self: center; }

        /* Wave */
        .lp-hero-wave { margin-top: -2px; line-height: 0; }
        .lp-hero-wave svg { width: 100%; display: block; }

        /* ═══════════════════════════════════════════════════════
           SECTIONS
        ═══════════════════════════════════════════════════════ */
        .lp-section { padding: 80px 0; }
        @media (max-width: 767px) { .lp-section { padding: 56px 0; } }

        .lp-section-light  { background: var(--lp-bg); }
        .lp-section-white  { background: var(--lp-white); }
        .lp-section-dark   { background: var(--lp-dark); }

        .lp-section-tag {
            display: inline-block;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--lp-primary);
            background: rgba(21,101,192,.1);
            border-radius: 999px;
            padding: 5px 14px;
        }
        .lp-section-tag--light { color: #7dd3fc; background: rgba(125,211,252,.12); }
        .lp-section-title { font-size: clamp(1.5rem, 4vw, 2.2rem); font-weight: 900; color: var(--lp-dark); }
        .lp-section-title--light { color: #fff; }
        .lp-section-sub { font-size: 1rem; color: var(--lp-text-muted); }
        .lp-section-sub--light { color: #94a3b8; }

        /* ═══════════════════════════════════════════════════════
           STEPS — tous en bleu
        ═══════════════════════════════════════════════════════ */
        .lp-step-card {
            background: var(--lp-gradient);
            border-radius: var(--lp-radius);
            padding: 32px 28px;
            height: 100%;
            box-shadow: var(--lp-shadow-md);
            border: none;
            position: relative;
            transition: var(--lp-transition);
            overflow: hidden;
        }
        .lp-step-card::before {
            content: '';
            position: absolute;
            width: 180px; height: 180px;
            background: rgba(255,255,255,.07);
            border-radius: 50%;
            top: -60px; right: -60px;
            pointer-events: none;
        }
        .lp-step-card:hover { transform: translateY(-6px); box-shadow: 0 20px 48px rgba(21,101,192,.35); }

        .lp-step-num {
            font-size: 3.5rem;
            font-weight: 900;
            color: rgba(255,255,255,.18);
            line-height: 1;
            margin-bottom: 8px;
        }
        .lp-step-icon {
            width: 56px; height: 56px;
            background: rgba(255,255,255,.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 16px;
        }
        .lp-step-card h4 { font-size: 1.05rem; font-weight: 800; margin-bottom: 8px; color: #fff; }
        .lp-step-card h4 a { color: #fff; text-decoration: none; }
        .lp-step-card h4 a:hover { text-decoration: underline; opacity: .9; }
        .lp-step-card p  { font-size: .875rem; color: rgba(255,255,255,.82); margin: 0; }
        .lp-step-arrow {
            position: absolute;
            right: -20px; top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            color: rgba(21,101,192,.3);
            z-index: 1;
        }

        /* ═══════════════════════════════════════════════════════
           FEATURES
        ═══════════════════════════════════════════════════════ */
        .lp-feature-img-wrap {
            background: linear-gradient(135deg,#e8f0fe,#e3f2fd);
            border-radius: 24px;
            padding: 32px;
            box-shadow: var(--lp-shadow-md);
        }
        .lp-feature-list { display: flex; flex-direction: column; gap: 20px; }
        .lp-feature-item {
            display: flex; gap: 16px; align-items: flex-start;
            padding: 18px 20px;
            background: var(--lp-bg);
            border-radius: var(--lp-radius-sm);
            border: 1px solid var(--lp-border);
            transition: var(--lp-transition);
        }
        .lp-feature-item:hover { transform: translateX(4px); box-shadow: var(--lp-shadow-sm); }
        .lp-feature-icon {
            width: 44px; height: 44px; flex-shrink: 0;
            background: rgba(21,101,192,.1);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: var(--lp-primary);
        }
        .lp-feature-item h5 { font-size: .95rem; font-weight: 800; margin-bottom: 4px; color: var(--lp-dark); }
        .lp-feature-item p  { font-size: .8rem; color: var(--lp-text-muted); margin: 0; }

        /* ═══════════════════════════════════════════════════════
           COMMUNITY
        ═══════════════════════════════════════════════════════ */
        .lp-exp-card {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: var(--lp-radius);
            padding: 28px 16px;
            text-align: center;
            transition: var(--lp-transition);
            cursor: default;
        }
        .lp-exp-card:hover {
            background: rgba(255,255,255,.1);
            transform: translateY(-4px);
            border-color: var(--card-accent);
            box-shadow: 0 8px 32px rgba(0,0,0,.2);
        }
        .lp-exp-card i { font-size: 2rem; color: var(--card-accent); display: block; margin-bottom: 12px; }
        .lp-exp-card p { font-size: .8rem; color: #cbd5e1; margin: 0; font-weight: 500; line-height: 1.4; }

        /* ═══════════════════════════════════════════════════════
           MAP SECTION
        ═══════════════════════════════════════════════════════ */
        .lp-map-section { padding: 80px 0; background: var(--lp-white); }
        @media (max-width: 767px) { .lp-map-section { padding: 56px 0; } }

        .lp-map-wrap {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--lp-shadow-lg);
            border: 1px solid var(--lp-border);
            height: 420px;
        }
        .lp-map-wrap iframe { width: 100%; height: 100%; border: 0; display: block; }
        @media (max-width: 767px) { .lp-map-wrap { height: 300px; } }

        .lp-map-info {
            background: var(--lp-bg);
            border-radius: 20px;
            padding: 32px 28px;
            height: 100%;
            border: 1px solid var(--lp-border);
        }
        .lp-map-info h4 { font-size: 1.2rem; font-weight: 800; color: var(--lp-dark); margin-bottom: 16px; }
        .lp-map-detail {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--lp-border);
        }
        .lp-map-detail:last-child { border-bottom: none; }
        .lp-map-detail i { font-size: 1.2rem; color: var(--lp-primary); margin-top: 2px; flex-shrink: 0; }
        .lp-map-detail span { font-size: .875rem; color: var(--lp-text-muted); line-height: 1.5; }
        .lp-map-detail strong { display: block; font-size: .9rem; color: var(--lp-dark); font-weight: 700; }

        /* ═══════════════════════════════════════════════════════
           CTA BOX
        ═══════════════════════════════════════════════════════ */
        .lp-cta-box {
            background: var(--lp-gradient);
            border-radius: 24px;
            padding: 56px 48px;
            position: relative;
            overflow: hidden;
        }
        @media (max-width: 575px) { .lp-cta-box { padding: 36px 24px; } }
        .lp-cta-bg-orb {
            position: absolute; width: 500px; height: 500px;
            background: rgba(255,255,255,.07);
            border-radius: 50%; top: -200px; right: -150px;
            pointer-events: none;
        }
        .lp-cta-title { font-size: clamp(1.5rem,4vw,2.2rem); font-weight: 900; color: #fff; }
        .lp-cta-sub { color: rgba(255,255,255,.8); font-size: 1rem; margin-top: 8px; }
        .lp-cta-box .lp-btn-outline {
            border-color: rgba(255,255,255,.6) !important;
            color: #fff !important;
        }
        .lp-cta-box .lp-btn-outline:hover { background: rgba(255,255,255,.15) !important; border-color: #fff !important; }
        .lp-cta-box .lp-btn-primary { background: rgba(255,255,255,.15) !important; border: 2px solid rgba(255,255,255,.7) !important; backdrop-filter: blur(8px); }
        .lp-cta-box .lp-btn-primary:hover { background: rgba(255,255,255,.28) !important; }

        /* ═══════════════════════════════════════════════════════
           CONTACT
        ═══════════════════════════════════════════════════════ */
        .lp-contact-card {
            background: var(--lp-white);
            border-radius: var(--lp-radius);
            padding: 32px 24px;
            text-align: center;
            border: 1px solid var(--lp-border);
            box-shadow: var(--lp-shadow-sm);
            transition: var(--lp-transition);
            height: 100%;
        }
        .lp-contact-card:hover { transform: translateY(-4px); box-shadow: var(--lp-shadow-md); }
        .lp-contact-icon {
            width: 60px; height: 60px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 16px;
        }
        .lp-contact-card h5 { font-size: .95rem; font-weight: 800; color: var(--lp-dark); margin-bottom: 10px; }
        .lp-contact-card a {
            font-size: .875rem;
            color: var(--lp-primary);
            text-decoration: none;
            font-weight: 600;
            word-break: break-all;
        }
        .lp-contact-card a:hover { text-decoration: underline; }
        .lp-whatsapp-btn {
            display: inline-flex; align-items: center;
            background: #25d366;
            color: #fff !important;
            padding: 8px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: .85rem;
            text-decoration: none !important;
            transition: var(--lp-transition);
        }
        .lp-whatsapp-btn:hover { background: #1da851; transform: scale(1.03); }

        /* ═══════════════════════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════════════════════ */
        .lp-footer {
            background: var(--lp-dark);
            color: #e2e8f0;
        }
        .lp-footer-heading { font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 14px; }
        .lp-footer-text { font-size: .85rem; color: #64748b; line-height: 1.6; }
        .lp-footer-links { list-style: none; margin: 0; padding: 0; }
        .lp-footer-links li { margin-bottom: 8px; }
        .lp-footer-links a { font-size: .85rem; color: #94a3b8; text-decoration: none; transition: var(--lp-transition); }
        .lp-footer-links a:hover { color: var(--lp-accent); }
        .lp-footer-bar {
            border-top: 1px solid rgba(255,255,255,.07);
            padding: 20px 0;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .lp-footer-bar p { margin: 0; font-size: .8rem; color: #475569; }
        .lp-footer-socials { display: flex; gap: 10px; }
        .lp-footer-socials a {
            width: 38px; height: 38px;
            background: rgba(255,255,255,.06);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #94a3b8;
            font-size: 1rem;
            transition: var(--lp-transition);
        }
        .lp-footer-socials a:hover { background: var(--lp-primary); color: #fff; }

        /* ═══════════════════════════════════════════════════════
           UTILITIES
        ═══════════════════════════════════════════════════════ */
        .min-vh-100 { min-height: 100vh; }
        @media (max-width: 767px) { .min-vh-100 { min-height: auto; padding: 60px 0 40px; } }

        /* Scroll-linked navbar */
        .lp-navbar.scrolled { box-shadow: 0 4px 24px rgba(0,0,0,.1); }
    </style>
</head>

<body>

    <!-- ═══════════════════════ NAVBAR ═══════════════════════ -->
    <nav class="navbar navbar-expand-lg lp-navbar fixed-top" id="mainNavbar" style="position:fixed;">
        <div class="container" style="position:relative;">
            <a class="navbar-brand lp-brand d-flex align-items-center" href="<?= htmlspecialchars($base . '/index.php', ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" width="36" height="36" alt="Logo" class="lp-brand-img me-2">
                <div>
                    <span class="lp-brand-name"><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></span>
                    <small class="lp-brand-place d-block"><?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </a>

            <!-- Toggler — visible on mobile -->
            <button class="navbar-toggler lp-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navMenu"
                aria-controls="navMenu"
                aria-expanded="false"
                aria-label="Ouvrir le menu">
                <i class="ri-menu-3-line"></i>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav mx-auto gap-1">
                    <li class="nav-item"><a class="nav-link lp-nav-link" href="#home">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link lp-nav-link" href="#service">Fonctionnalités</a></li>
                    <li class="nav-item"><a class="nav-link lp-nav-link" href="#experience">Communauté</a></li>
                    <li class="nav-item"><a class="nav-link lp-nav-link" href="#localisation">Localisation</a></li>
                    <li class="nav-item"><a class="nav-link lp-nav-link" href="#contact">Contact</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <?php if (empty($_SESSION['user_id'])): ?>
                        <a href="<?= $registerUrl ?>" class="btn lp-btn-outline">S'inscrire</a>
                        <a href="<?= $loginUrl ?>" class="btn lp-btn-primary">Se connecter</a>
                    <?php else: ?>
                        <a href="<?= $profileUrl ?>" class="btn lp-btn-primary">
                            <i class="ri-user-3-fill me-1"></i> Mon profil
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- ═══════════════════════ HERO ═══════════════════════ -->
    <section class="lp-hero" id="home">
        <div class="lp-hero-bg-orb orb1"></div>
        <div class="lp-hero-bg-orb orb2"></div>
        <div class="lp-hero-bg-orb orb3"></div>
        <div class="container">
            <div class="row align-items-center min-vh-100 py-5">
                <div class="col-lg-6 order-2 order-lg-1 mt-4 mt-lg-0" data-aos="fade-right" data-aos-duration="900">
                    <div class="lp-badge mb-3">
                        <i class="ri-sparkling-2-fill"></i> Réseau Officiel Étudiant
                    </div>
                    <h1 class="lp-hero-title">
                        Le réseau social officiel des étudiants
                        <span class="lp-gradient-text"><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></span>
                        à <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <p class="lp-hero-sub mt-3 mb-4">
                        Retrouvez les informations de l'école, votre profil membre et la communauté
                        <strong><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></strong> à <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?>.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <?php if (empty($_SESSION['user_id'])): ?>
                            <a href="<?= $registerUrl ?>" class="btn lp-btn-primary lp-btn-lg">
                                <i class="ri-user-add-fill me-2"></i>Créer un compte
                            </a>
                            <a href="<?= $loginUrl ?>" class="btn lp-btn-outline lp-btn-lg">
                                <i class="ri-login-circle-fill me-2"></i>Se connecter
                            </a>
                        <?php else: ?>
                            <a href="<?= $profileUrl ?>" class="btn lp-btn-primary lp-btn-lg">
                                <i class="ri-user-3-fill me-2"></i>Mon profil
                            </a>
                            <a href="#contact" class="btn lp-btn-outline lp-btn-lg">
                                <i class="ri-customer-service-2-fill me-2"></i>Contact
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="lp-hero-stats mt-5 d-flex flex-wrap gap-4">
                        <div class="lp-stat">
                            <span class="lp-stat-num" data-count="500">0</span><span class="lp-stat-num">+</span>
                            <small>Étudiants</small>
                        </div>
                        <div class="lp-stat-divider"></div>
                        <div class="lp-stat">
                            <span class="lp-stat-num" data-count="3">0</span><span class="lp-stat-num"> étapes</span>
                            <small>Pour rejoindre</small>
                        </div>
                        <div class="lp-stat-divider"></div>
                        <div class="lp-stat">
                            <span class="lp-stat-num">100%</span>
                            <small>Gratuit</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 order-1 order-lg-2 text-center" data-aos="fade-left" data-aos-duration="900" data-aos-delay="200">
                    <div class="lp-hero-img-wrap">
                        <!-- ✅ IMAGE RÉELLE DE L'ENSIASD -->
                        <img
                            src="https://ensiasd.uiz.ac.ma/wp-content/uploads/2025/05/012e3a98-5470-42c2-80cd-8921ceee3ea1-5-3.webp"
                            alt="ENSIASD Taroudant — École Nationale Supérieure d'Intelligence Artificielle et Sciences des Données"
                            class="lp-hero-img"
                            loading="eager"
                            onerror="this.onerror=null;this.src='<?= htmlspecialchars($base.'/images/landing-hero.svg',ENT_QUOTES,'UTF-8') ?>';"
                        >
                        <div class="lp-floating-card card1">
                            <i class="ri-shield-check-fill text-success me-2"></i> Compte vérifié
                        </div>
                        <div class="lp-floating-card card2">
                            <i class="ri-notification-3-fill text-warning me-2"></i> Nouvelle annonce
                        </div>
                        <div class="lp-floating-card card3">
                            <i class="ri-group-fill text-primary me-2"></i> +12 membres aujourd'hui
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lp-hero-wave">
            <svg viewBox="0 0 1440 80" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,40 C360,80 1080,0 1440,40 L1440,80 L0,80 Z" fill="var(--lp-bg)" />
            </svg>
        </div>
    </section>

    <!-- ═══════════════════════ STEPS ═══════════════════════ -->
    <section class="lp-section lp-section-light" id="steps">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="lp-section-tag">COMMENT ÇA MARCHE</span>
                <h2 class="lp-section-title mt-2">Trois étapes pour rejoindre le réseau</h2>
                <p class="lp-section-sub">Simple, rapide, et entièrement gratuit pour tous les étudiants.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="lp-step-card">
                        <div class="lp-step-num">01</div>
                        <div class="lp-step-icon"><i class="ri-user-add-fill"></i></div>
                        <h4><a href="<?= $registerUrl ?>">Créez un compte</a></h4>
                        <p>Inscrivez-vous en quelques minutes pour accéder à votre profil et au réseau.</p>
                        <div class="lp-step-arrow d-none d-md-block"><i class="ri-arrow-right-line"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="lp-step-card">
                        <div class="lp-step-num">02</div>
                        <div class="lp-step-icon"><i class="ri-edit-2-fill"></i></div>
                        <h4>Complétez votre profil</h4>
                        <p>Présentez-vous à la communauté pour faciliter les échanges et les mises en relation.</p>
                        <div class="lp-step-arrow d-none d-md-block"><i class="ri-arrow-right-line"></i></div>
                    </div>
                </div>
                <div class="col-12 col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="lp-step-card">
                        <div class="lp-step-num">03</div>
                        <div class="lp-step-icon"><i class="ri-school-fill"></i></div>
                        <h4>Participez !</h4>
                        <p>Découvrez les infos du réseau et restez proche de la communauté de l'école.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ FEATURES ═══════════════════════ -->
    <section class="lp-section lp-section-white" id="service">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5 text-center" data-aos="zoom-in" data-aos-duration="800">
                    <div class="lp-feature-img-wrap">
                        <img src="<?= htmlspecialchars($base . '/images/landing-service.svg', ENT_QUOTES, 'UTF-8') ?>" alt="Fonctionnalités" class="img-fluid">
                    </div>
                </div>
                <div class="col-lg-7" data-aos="fade-left" data-aos-duration="800">
                    <span class="lp-section-tag">AU CŒUR DU RÉSEAU</span>
                    <h2 class="lp-section-title mt-2 mb-4">Une expérience pensée pour la vie étudiante</h2>
                    <div class="lp-feature-list">
                        <div class="lp-feature-item" data-aos="fade-left" data-aos-delay="100">
                            <div class="lp-feature-icon"><i class="ri-user-smile-fill"></i></div>
                            <div>
                                <h5>Profil membre</h5>
                                <p>Présentez-vous et retrouvez les informations de votre compte au sein du réseau.</p>
                            </div>
                        </div>
                        <div class="lp-feature-item" data-aos="fade-left" data-aos-delay="200">
                            <div class="lp-feature-icon"><i class="ri-notification-3-fill"></i></div>
                            <div>
                                <h5>Infos &amp; annonces</h5>
                                <p>Centralisez les informations utiles pour votre scolarité et la vie du campus.</p>
                            </div>
                        </div>
                        <div class="lp-feature-item" data-aos="fade-left" data-aos-delay="300">
                            <div class="lp-feature-icon"><i class="ri-links-fill"></i></div>
                            <div>
                                <h5>Liens entre promotions</h5>
                                <p>Connectez-vous avec vos camarades et anciens élèves facilement.</p>
                            </div>
                        </div>
                        <div class="lp-feature-item" data-aos="fade-left" data-aos-delay="400">
                            <div class="lp-feature-icon"><i class="ri-award-fill"></i></div>
                            <div>
                                <h5>Mise en avant des projets</h5>
                                <p>Valorisez vos réalisations et découvrez celles de vos pairs.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ COMMUNITY ═══════════════════════ -->
    <section class="lp-section lp-section-dark" id="experience">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="lp-section-tag lp-section-tag--light">VIE DE CAMPUS</span>
                <h2 class="lp-section-title lp-section-title--light mt-2">Ce que le réseau vous apporte au quotidien</h2>
                <p class="lp-section-sub lp-section-sub--light">Tout ce dont vous avez besoin pour votre vie étudiante.</p>
            </div>
            <div class="row g-3 justify-content-center">
                <?php
                $cards = [
                    ['ri-shield-check-fill',       'Espace dédié à l\'école',   '#4caf50'],
                    ['ri-time-fill',               'Actualités en temps réel',  '#2196f3'],
                    ['ri-links-fill',              'Liens entre promotions',    '#9c27b0'],
                    ['ri-award-fill',              'Mise en avant des projets', '#ff9800'],
                    ['ri-customer-service-2-fill', 'Contact &amp; entraide',    '#f44336'],
                    ['ri-smartphone-fill',         'Accessible sur mobile',     '#00bcd4'],
                ];
                foreach ($cards as $i => [$icon, $label, $color]):
                ?>
                    <div class="col-6 col-sm-4 col-lg-2" data-aos="zoom-in" data-aos-delay="<?= $i * 80 ?>">
                        <div class="lp-exp-card" style="--card-accent:<?= $color ?>">
                            <i class="<?= $icon ?>"></i>
                            <p><?= $label ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ MAP ═══════════════════════ -->
    <section class="lp-map-section" id="localisation">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="lp-section-tag">LOCALISATION</span>
                <h2 class="lp-section-title mt-2">Nous trouver</h2>
                <p class="lp-section-sub">ENSIASD — Taroudant, Maroc</p>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-12 col-lg-8" data-aos="fade-right" data-aos-duration="800">
                    <div class="lp-map-wrap">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3408.44!2d-8.866443!3d30.493312!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd8db730014dc1c1%3A0x3f25041f092230833!2s%C3%89cole%20Nationale%20Sup%C3%A9rieure%20de%20l'Intelligence%20Artificielle%20et%20Sciences%20des%20Donn%C3%A9es!5e0!3m2!1sfr!2sma!4v1700000000000!5m2!1sfr!2sma"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Localisation ENSIASD Taroudant"
                        ></iframe>
                    </div>
                </div>
                <div class="col-12 col-lg-4" data-aos="fade-left" data-aos-duration="800">
                    <div class="lp-map-info">
                        <h4><i class="ri-map-pin-2-fill me-2" style="color:var(--lp-primary)"></i>ENSIASD Taroudant</h4>
                        <div class="lp-map-detail">
                            <i class="ri-building-2-fill"></i>
                            <span>
                                <strong>École</strong>
                                École Nationale Supérieure de l'Intelligence Artificielle et Sciences des Données
                            </span>
                        </div>
                        <div class="lp-map-detail">
                            <i class="ri-map-pin-line"></i>
                            <span>
                                <strong>Adresse</strong>
                                F4VM+7CW, Taroudant, Maroc
                            </span>
                        </div>
                        <div class="lp-map-detail">
                            <i class="ri-time-fill"></i>
                            <span>
                                <strong>Horaires</strong>
                                Lundi – Vendredi : 8h30 – 18h15<br>
                                Samedi / Dimanche : Fermé
                            </span>
                        </div>
                        <div class="lp-map-detail">
                            <i class="ri-star-fill" style="color:#f59e0b"></i>
                            <span>
                                <strong>Note Google</strong>
                                5.0 / 5 ⭐ — Établissement reconnu
                            </span>
                        </div>
                        <a
                            href="https://maps.google.com/?q=30.493312,-8.866443"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn lp-btn-primary w-100 mt-4"
                            style="border-radius:12px; padding:12px 0; font-size:.9rem;"
                        >
                            <i class="ri-navigation-fill me-2"></i>Ouvrir dans Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ CTA ═══════════════════════ -->
    <section class="lp-section lp-section-light" id="cta-join">
        <div class="container" data-aos="fade-up">
            <div class="lp-cta-box">
                <div class="lp-cta-bg-orb"></div>
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2 class="lp-cta-title">Rejoignez la communauté<br><span style="opacity:.85"><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></span></h2>
                        <p class="lp-cta-sub">Accédez à votre espace membre. Un seul compte pour le réseau de l'école.</p>
                    </div>
                    <div class="col-lg-4 d-flex flex-column flex-sm-row flex-lg-column gap-2 align-items-lg-end">
                        <?php if (empty($_SESSION['user_id'])): ?>
                            <a href="<?= $registerUrl ?>" class="btn lp-btn-primary lp-btn-lg">
                                <i class="ri-rocket-2-fill me-2"></i>Créer un compte gratuitement
                            </a>
                            <a href="<?= $loginUrl ?>" class="btn lp-btn-outline lp-btn-lg">
                                <i class="ri-login-circle-fill me-2"></i>J'ai déjà un compte
                            </a>
                        <?php else: ?>
                            <a href="<?= $profileUrl ?>" class="btn lp-btn-primary lp-btn-lg">
                                <i class="ri-user-3-fill me-2"></i>Mon profil
                            </a>
                            <a href="#contact" class="btn lp-btn-outline lp-btn-lg">Contact</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ CONTACT ═══════════════════════ -->
    <section class="lp-section lp-section-white" id="contact">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="lp-section-tag">CONTACT</span>
                <h2 class="lp-section-title mt-2">L'école et le réseau</h2>
                <p class="lp-section-sub">Une question ? Contactez-nous directement.</p>
            </div>
            <div class="row justify-content-center g-4">
                <div class="col-12 col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="lp-contact-card">
                        <div class="lp-contact-icon" style="background:#e3f2fd">
                            <i class="ri-phone-fill" style="color:#1877f2; font-size:1.5rem;"></i>
                        </div>
                        <h5>Téléphone</h5>
                        <a href="tel:<?= $phoneTel ?>"><?= $phoneLabel ?></a>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="lp-contact-card">
                        <div class="lp-contact-icon" style="background:#fce4ec">
                            <i class="ri-mail-fill" style="color:#e91e63; font-size:1.5rem;"></i>
                        </div>
                        <h5>Email</h5>
                        <a href="mailto:<?= $emailContact ?>"><?= $emailContact ?></a>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="lp-contact-card">
                        <div class="lp-contact-icon" style="background:#e8f5e9">
                            <i class="ri-whatsapp-fill" style="color:#25d366; font-size:1.5rem;"></i>
                        </div>
                        <h5>WhatsApp</h5>
                        <a href="<?= $whatsappUrl ?>" target="_blank" rel="noopener noreferrer" class="lp-whatsapp-btn">
                            <i class="ri-whatsapp-fill me-1"></i> Envoyer un message
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════ FOOTER ═══════════════════════ -->
    <footer class="lp-footer">
        <div class="container">
            <div class="row g-4 py-5">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" width="36" height="36" alt="" class="rounded-circle">
                        <span class="fw-bold text-white"><?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p class="lp-footer-text">Le réseau social officiel des étudiants de l'école.</p>
                </div>
                <div class="col-6 col-lg-3">
                    <h6 class="lp-footer-heading">Compte</h6>
                    <ul class="list-unstyled lp-footer-links">
                        <li><a href="<?= $loginUrl ?>">Connexion</a></li>
                        <li><a href="<?= $registerUrl ?>">Inscription</a></li>
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <li><a href="<?= $profileUrl ?>">Mon profil</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-6 col-lg-3">
                    <h6 class="lp-footer-heading">Navigation</h6>
                    <ul class="list-unstyled lp-footer-links">
                        <li><a href="#home">Accueil</a></li>
                        <li><a href="#service">Fonctionnalités</a></li>
                        <li><a href="#experience">Communauté</a></li>
                        <li><a href="#localisation">Localisation</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <h6 class="lp-footer-heading">Lieu</h6>
                    <p class="lp-footer-text"><?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?>, Maroc</p>
                    <div class="lp-footer-socials mt-3">
                        <a href="<?= htmlspecialchars($base . '/index.php#home', ENT_QUOTES, 'UTF-8') ?>" aria-label="Accueil"><i class="ri-home-4-fill"></i></a>
                        <a href="mailto:<?= $emailContact ?>" aria-label="Email"><i class="ri-mail-fill"></i></a>
                        <a href="<?= $whatsappUrl ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="ri-whatsapp-fill"></i></a>
                    </div>
                </div>
            </div>
            <div class="lp-footer-bar">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(SITE_PLACE, ENT_QUOTES, 'UTF-8') ?></p>
                <p>Fait avec <i class="ri-heart-fill" style="color:#e91e63"></i> pour les étudiants</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

    <script>
        /* ── AOS Init ── */
        AOS.init({ once: true, offset: 60, easing: 'ease-out-cubic' });

        /* ── Navbar scroll shadow ── */
        const nav = document.getElementById('mainNavbar');
        window.addEventListener('scroll', () => {
            nav.classList.toggle('scrolled', window.scrollY > 40);
        }, { passive: true });

        /* ── Smooth close navbar on mobile link click ── */
        document.querySelectorAll('#navMenu .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const collapse = document.getElementById('navMenu');
                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                if (bsCollapse) bsCollapse.hide();
            });
        });

        /* ── Counter animation ── */
        const counters = document.querySelectorAll('[data-count]');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = +el.getAttribute('data-count');
                const duration = 1400;
                const step = target / (duration / 16);
                let current = 0;
                const timer = setInterval(() => {
                    current = Math.min(current + step, target);
                    el.textContent = Math.floor(current);
                    if (current >= target) clearInterval(timer);
                }, 16);
                observer.unobserve(el);
            });
        }, { threshold: .5 });
        counters.forEach(c => observer.observe(c));
    </script>
</body>
</html>