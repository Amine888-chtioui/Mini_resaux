<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'User';
}

$userId = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ── Servir la page HTML si aucune action API ──────────────────────────────────
if ($action === '') {
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f2f5;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Sidebar ── */
        #sidebar {
            width: 320px;
            background: #fff;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }

        #sidebar h2 {
            padding: 16px;
            font-size: 1.1rem;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        #conversations { flex: 1; overflow-y: auto; }

        .conv-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s;
        }

        .conv-item:hover, .conv-item.active { background: #f0f2f5; }

        .conv-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #0084ff;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .conv-info { flex: 1; min-width: 0; }
        .conv-name { font-weight: 600; font-size: .9rem; color: #111; }
        .conv-last {
            font-size: .8rem;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-badge {
            background: #0084ff;
            color: #fff;
            border-radius: 12px;
            padding: 2px 7px;
            font-size: .75rem;
            font-weight: 600;
        }

        /* ── Chat area ── */
        #chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        #chat-header {
            padding: 14px 20px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
            font-size: 1rem;
            color: #333;
            background: #fff;
        }

        #messages-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .msg-row { display: flex; }
        .msg-row.mine { justify-content: flex-end; }

        .msg-bubble {
            max-width: 60%;
            padding: 9px 14px;
            border-radius: 18px;
            font-size: .9rem;
            line-height: 1.4;
            word-break: break-word;
        }

        .msg-row.mine .msg-bubble {
            background: #0084ff;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg-row:not(.mine) .msg-bubble {
            background: #f0f2f5;
            color: #111;
            border-bottom-left-radius: 4px;
        }

        .msg-bubble img {
            max-width: 260px;
            max-height: 260px;
            border-radius: 10px;
            display: block;
            margin-top: 4px;
            cursor: pointer;
        }

        .msg-time {
            font-size: .7rem;
            color: #aaa;
            margin-top: 3px;
            text-align: right;
        }

        .shared-post-card {
            margin-top: 8px;
            border: 1px solid #dbe7ff;
            background: #f6f9ff;
            border-radius: 12px;
            padding: 10px;
        }

        .shared-post-title {
            font-size: .82rem;
            color: #4c5d7a;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .shared-post-btn {
            border: none;
            background: #1877f2;
            color: #fff;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
        }

        .shared-post-btn:hover { background: #1464cc; }

        /* ── Input bar ── */
        #input-bar {
            padding: 12px 16px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: flex-end;
            gap: 8px;
            background: #fff;
        }

        /* Image preview */
        #image-preview-wrapper {
            display: none;
            padding: 8px 16px 0;
            background: #fff;
        }

        #image-preview-wrapper.visible { display: flex; align-items: center; gap: 10px; }

        #image-preview-wrapper img {
            max-height: 80px;
            border-radius: 8px;
            border: 2px solid #0084ff;
        }

        #remove-image {
            background: none; border: none;
            cursor: pointer; color: #e74c3c;
            font-size: 1.2rem; line-height: 1;
        }

        /* Attach button */
        #attach-btn {
            width: 38px; height: 38px;
            border: none;
            background: none;
            cursor: pointer;
            color: #888;
            font-size: 1.3rem;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }

        #attach-btn:hover { background: #f0f2f5; color: #0084ff; }
        #attach-btn.has-image { color: #0084ff; }

        #msg-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 9px 14px;
            font-size: .9rem;
            outline: none;
            resize: none;
            max-height: 120px;
            overflow-y: auto;
            font-family: inherit;
            line-height: 1.4;
        }

        #msg-input:focus { border-color: #0084ff; }

        #send-btn {
            width: 38px; height: 38px;
            border: none;
            background: #0084ff;
            color: #fff;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            transition: background 0.15s, transform 0.1s;
        }

        #send-btn:hover { background: #006edb; }
        #send-btn:active { transform: scale(0.93); }
        #send-btn:disabled { background: #ccc; cursor: not-allowed; }

        /* Empty state */
        #empty-state {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 1rem;
        }

        /* Lightbox */
        #lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.85);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #lightbox.open { display: flex; }

        #lightbox img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
            box-shadow: 0 4px 30px rgba(0,0,0,.5);
        }

        #lightbox-close {
            position: absolute;
            top: 20px; right: 24px;
            color: #fff; font-size: 2rem;
            cursor: pointer; line-height: 1;
        }

        #post-preview-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.65);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 14px;
        }

        #post-preview-modal.open { display: flex; }

        .post-preview-content {
            width: min(620px, 100%);
            max-height: 90vh;
            overflow: auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,.35);
            padding: 16px;
        }

        .post-preview-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .post-preview-close {
            border: none;
            background: #f0f2f5;
            border-radius: 8px;
            width: 32px;
            height: 32px;
            cursor: pointer;
            font-size: 1rem;
        }

        .post-preview-meta {
            font-size: .82rem;
            color: #6c7687;
            margin-bottom: 10px;
        }

        .post-preview-image {
            width: 100%;
            border-radius: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <h2>💬 Messages</h2>
    <div id="conversations"></div>
</div>

<!-- Chat -->
<div id="chat">
    <div id="empty-state">← Sélectionnez une conversation</div>
</div>

<!-- Lightbox (image plein écran) -->
<div id="lightbox">
    <span id="lightbox-close" title="Fermer">✕</span>
    <img id="lightbox-img" src="" alt="">
</div>

<!-- Aperçu publication partagée -->
<div id="post-preview-modal">
    <div class="post-preview-content">
        <div class="post-preview-head">
            <strong>Publication partagée</strong>
            <button class="post-preview-close" id="post-preview-close" title="Fermer">✕</button>
        </div>
        <div id="post-preview-body"></div>
    </div>
</div>

<script>
const API = location.pathname; // même fichier PHP
let currentUserId = null;
let pollTimer = null;

/* ── INIT ─────────────────────────────────────────────────────────────── */
loadConversations();

/* ── CONVERSATIONS ────────────────────────────────────────────────────── */
async function loadConversations() {
    const res = await fetch(`${API}?action=list`);
    const data = await res.json();
    if (!data.success) return;

    const container = document.getElementById('conversations');
    container.innerHTML = '';

    data.conversations.forEach(c => {
        const div = document.createElement('div');
        div.className = 'conv-item' + (c.id == currentUserId ? ' active' : '');
        div.dataset.id = c.id;
        div.dataset.name = c.username;
        div.innerHTML = `
            <div class="conv-avatar">${c.username[0].toUpperCase()}</div>
            <div class="conv-info">
                <div class="conv-name">${esc(c.username)}</div>
                <div class="conv-last">${esc(c.last_message || 'Image')}</div>
            </div>
            ${c.unread_count > 0 ? `<span class="conv-badge">${c.unread_count}</span>` : ''}
        `;
        div.addEventListener('click', () => openConversation(c.id, c.username));
        container.appendChild(div);
    });
}

/* ── OUVRIR UNE CONVERSATION ──────────────────────────────────────────── */
function openConversation(userId, userName) {
    currentUserId = userId;
    clearInterval(pollTimer);

    // Marquer actif
    document.querySelectorAll('.conv-item').forEach(el => {
        el.classList.toggle('active', el.dataset.id == userId);
    });

    // Construire l'interface chat
    const chat = document.getElementById('chat');
    chat.innerHTML = `
        <div id="chat-header">Conversation avec ${esc(userName)}</div>
        <div id="messages-list"></div>

        <!-- Prévisualisation image -->
        <div id="image-preview-wrapper">
            <img id="image-preview" src="" alt="Aperçu">
            <button id="remove-image" title="Supprimer l'image">✕</button>
        </div>

        <!-- Barre d'envoi -->
        <div id="input-bar">
            <input type="file" id="file-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
            <button id="attach-btn" title="Joindre une image">📎</button>
            <textarea id="msg-input" rows="1" placeholder="Aa…"></textarea>
            <button id="send-btn" title="Envoyer">➤</button>
        </div>
    `;

    // Événements
    document.getElementById('attach-btn').addEventListener('click', () => {
        document.getElementById('file-input').click();
    });

    document.getElementById('file-input').addEventListener('change', onFileSelect);
    document.getElementById('remove-image').addEventListener('click', clearImage);
    document.getElementById('send-btn').addEventListener('click', sendMessage);
    document.getElementById('msg-input').addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    loadMessages();
    pollTimer = setInterval(loadMessages, 3000);
}

/* ── SÉLECTION D'IMAGE ────────────────────────────────────────────────── */
function onFileSelect(e) {
    const file = e.target.files[0];
    if (!file) return;

    const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!allowed.includes(file.type)) {
        alert('Format non supporté. Utilisez JPG, PNG, GIF ou WebP.');
        return;
    }
    if (file.size > 8 * 1024 * 1024) {
        alert('Image trop lourde (max 8 Mo).');
        return;
    }

    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('image-preview').src = ev.target.result;
        document.getElementById('image-preview-wrapper').classList.add('visible');
        document.getElementById('attach-btn').classList.add('has-image');
    };
    reader.readAsDataURL(file);
}

function clearImage() {
    document.getElementById('file-input').value = '';
    document.getElementById('image-preview').src = '';
    document.getElementById('image-preview-wrapper').classList.remove('visible');
    document.getElementById('attach-btn').classList.remove('has-image');
}

/* ── ENVOI ────────────────────────────────────────────────────────────── */
async function sendMessage() {
    const input   = document.getElementById('msg-input');
    const fileEl  = document.getElementById('file-input');
    const sendBtn = document.getElementById('send-btn');
    const body    = input.value.trim();
    const file    = fileEl.files[0];

    if (!body && !file) return;

    sendBtn.disabled = true;

    try {
        let res;

        if (file) {
            // multipart/form-data (texte + image)
            const fd = new FormData();
            fd.append('receiver_id', currentUserId);
            fd.append('body', body);
            fd.append('image', file);

            res = await fetch(`${API}?action=send`, { method: 'POST', body: fd });
        } else {
            // JSON (texte seul)
            res = await fetch(`${API}?action=send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ receiver_id: currentUserId, body })
            });
        }

        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        input.value = '';
        clearImage();
        loadMessages();
        loadConversations();
    } catch (err) {
        alert('Erreur : ' + err.message);
    } finally {
        sendBtn.disabled = false;
    }
}

/* ── AFFICHAGE DES MESSAGES ───────────────────────────────────────────── */
async function loadMessages() {
    if (!currentUserId) return;
    const res  = await fetch(`${API}?action=get&user_id=${currentUserId}`);
    const data = await res.json();
    if (!data.success) return;

    const list     = document.getElementById('messages-list');
    if (!list) return;
    const atBottom = list.scrollHeight - list.scrollTop <= list.clientHeight + 60;

    list.innerHTML = '';

    data.messages.forEach(m => {
        const isMine = (m.sender_id == <?= $userId ?>);
        const row    = document.createElement('div');
        row.className = 'msg-row' + (isMine ? ' mine' : '');

        let content = '';
        let messageBody = m.body || '';
        const sharedPostMatch = String(messageBody).match(/\[POST_SHARE:(\d+)\]/);
        const sharedPostId = sharedPostMatch ? Number(sharedPostMatch[1]) : 0;

        if (sharedPostId) {
            messageBody = String(messageBody).replace(/\s*\[POST_SHARE:\d+\]\s*/g, '').trim();
        }

        if (messageBody) content += `<div>${esc(messageBody)}</div>`;
        if (sharedPostId) {
            content += `
                <div class="shared-post-card">
                    <div class="shared-post-title">Publication partagée</div>
                    <button class="shared-post-btn" onclick="openSharedPostPreview(${sharedPostId})">Voir la publication</button>
                </div>
            `;
        }
        if (m.image_path) {
            const src = '/' + m.image_path;
            content += `<img src="${src}" alt="image" loading="lazy"
                             onclick="openLightbox('${src}')">`;
        }

        const time = new Date(m.created_at).toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});

        row.innerHTML = `
            <div class="msg-bubble">
                ${content}
                <div class="msg-time">${time}</div>
            </div>
        `;
        list.appendChild(row);
    });

    if (atBottom) list.scrollTop = list.scrollHeight;
}

/* ── LIGHTBOX ─────────────────────────────────────────────────────────── */
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('open');
}

document.getElementById('lightbox').addEventListener('click', e => {
    if (e.target === e.currentTarget || e.target.id === 'lightbox-close') {
        document.getElementById('lightbox').classList.remove('open');
    }
});

/* ── APERÇU PUBLICATION PARTAGÉE ─────────────────────────────────────── */
async function openSharedPostPreview(postId) {
    try {
        const res = await fetch(`${API}?action=get_shared_post&post_id=${Number(postId)}`);
        const data = await res.json();
        if (!data.success || !data.post) {
            throw new Error(data.error || 'Publication introuvable');
        }

        const p = data.post;
        const container = document.getElementById('post-preview-body');
        container.innerHTML = `
            <div class="post-preview-meta">
                <strong>${esc(p.username || 'Utilisateur')}</strong> • ${esc(p.created_at || '')}
            </div>
            ${p.body ? `<div>${esc(p.body)}</div>` : '<div class="text-muted">Aucun texte</div>'}
            ${p.image_path ? `<img class="post-preview-image" src="/${esc(p.image_path)}" alt="image publication">` : ''}
        `;
        document.getElementById('post-preview-modal').classList.add('open');
    } catch (err) {
        alert('Erreur : ' + err.message);
    }
}

function closeSharedPostPreview() {
    document.getElementById('post-preview-modal').classList.remove('open');
}

document.getElementById('post-preview-close').addEventListener('click', closeSharedPostPreview);
document.getElementById('post-preview-modal').addEventListener('click', e => {
    if (e.target.id === 'post-preview-modal') closeSharedPostPreview();
});

/* ── UTILS ────────────────────────────────────────────────────────────── */
function esc(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
    <?php
    exit;
}

// ── API JSON ──────────────────────────────────────────────────────────────────
header('Content-Type: application/json');

function ensureGroupMessagesSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_group_user (group_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            sender_id INT NOT NULL,
            body TEXT NULL,
            image_path VARCHAR(500) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_group_date (group_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function assertGroupMember(PDO $pdo, int $groupId, int $userId): void
{
    $stmt = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('Vous n\'êtes pas membre de ce groupe');
    }
}

function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image trop lourde (limite serveur).',
        UPLOAD_ERR_PARTIAL => 'Upload incomplet, réessayez.',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier reçu.',
        UPLOAD_ERR_NO_TMP_DIR => 'Serveur mal configuré: dossier temporaire manquant.',
        UPLOAD_ERR_CANT_WRITE => 'Serveur: écriture disque impossible.',
        UPLOAD_ERR_EXTENSION => 'Upload bloqué par une extension PHP.',
        default => 'Erreur upload inconnue.'
    };
}

function storeUploadedImage(array $file, string $prefix): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception(uploadErrorMessage((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
    }

    $maxSize = 8 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxSize) {
        throw new Exception('Image trop lourde (max 8 Mo)');
    }

    $tmpName = $file['tmp_name'] ?? '';
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new Exception('Upload invalide (fichier temporaire introuvable)');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpName);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowedMimes[$mime])) {
        throw new Exception('Format d\'image non autorisé (JPG, PNG, GIF, WebP)');
    }

    $uploadDir = dirname(__DIR__) . '/uploads/messages/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new Exception('Impossible de créer le dossier uploads/messages');
    }
    if (!is_writable($uploadDir)) {
        throw new Exception('Le dossier uploads/messages n\'est pas accessible en écriture');
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMimes[$mime];
    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($tmpName, $dest)) {
        throw new Exception('Échec move_uploaded_file (droits dossier ou config PHP)');
    }

    return 'uploads/messages/' . $filename;
}

// Détecter si la colonne image_path existe
$hasImagePath = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM messages LIKE 'image_path'");
    $hasImagePath = $cols->rowCount() > 0;
} catch (\Exception $e) {}

// Si elle n'existe pas, on la crée automatiquement
if (!$hasImagePath) {
    try {
        $pdo->exec("ALTER TABLE messages ADD COLUMN image_path VARCHAR(500) NULL");
        $hasImagePath = true;
    } catch (\Exception $e) {
        // ignore, on continuera sans
    }
}

try {
    ensureGroupMessagesSchema($pdo);

    switch ($action) {

        /* ── LISTE DES CONVERSATIONS ─────────────────── */
        case 'list':
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
                    u.username,
                    u.id,
                    (SELECT body FROM messages
                        WHERE ((sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?))
                        ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages
                        WHERE ((sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?))
                        ORDER BY created_at DESC LIMIT 1) as last_time,
                    (SELECT COUNT(*) FROM messages
                        WHERE receiver_id = ? AND sender_id = u.id AND is_read = FALSE) as unread_count
                FROM messages m
                JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
                WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
                ORDER BY last_time DESC
            ");
            $stmt->execute([$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId]);
            echo json_encode(['success' => true, 'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        /* ── MESSAGES D'UNE CONVERSATION ─────────────── */
        case 'get':
            $otherUserId = (int)($_GET['user_id'] ?? 0);
            if ($otherUserId === 0) throw new Exception('ID utilisateur invalide');

            $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ?")
                ->execute([$userId, $otherUserId]);

            $imageCol = $hasImagePath ? ', m.image_path' : ", NULL as image_path";
            $stmt = $pdo->prepare("
                SELECT m.id, m.sender_id, m.receiver_id, m.body, m.created_at, u.username as sender_name
                {$imageCol}
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                ORDER BY m.created_at ASC
                LIMIT 100
            ");
            $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
            echo json_encode(['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        /* ── ENVOI D'UN MESSAGE (texte + image) ──────── */
        case 'send':
            $receiverId = 0;
            $body = '';
            $imagePath = null;

            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            // Cas 1 : JSON (texte seul)
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
                $receiverId = (int)($input['receiver_id'] ?? 0);
                $body = trim($input['body'] ?? '');
            }
            // Cas 2 : multipart/form-data (texte + image possible)
            else {
                $receiverId = (int)($_POST['receiver_id'] ?? 0);
                $body = trim($_POST['body'] ?? '');

                // Traitement de l'image
                if (isset($_FILES['image'])) {
                    $imagePath = storeUploadedImage($_FILES['image'], 'msg_' . $userId);
                }
            }

            if ($receiverId === 0) throw new Exception('Destinataire invalide');
            if (empty($body) && $imagePath === null) throw new Exception('Message vide');

            // Vérifier que le destinataire existe
            $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $checkUser->execute([$receiverId]);
            if (!$checkUser->fetch()) throw new Exception('Utilisateur introuvable');

            // Insérer le message
            if ($hasImagePath) {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, body, image_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $receiverId, $body, $imagePath]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $receiverId, $body]);
            }
            $messageId = $pdo->lastInsertId();

            // Notification
            try {
                $senderName = $_SESSION['username'] ?? 'Quelqu\'un';
                $notifMsg = $imagePath ? "$senderName vous a envoyé une image" : "$senderName vous a envoyé un message";
                $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'message', 'Nouveau message', ?, ?)")
                    ->execute([$receiverId, $notifMsg, "/messages.php?user_id=$userId"]);
            } catch (\Exception $e) {}

            // Retourner le message créé
            $imageColSelect = $hasImagePath ? ', m.image_path' : ", NULL as image_path";
            $newMsg = $pdo->prepare("
                SELECT m.id, m.sender_id, m.receiver_id, m.body, m.created_at, u.username as sender_name
                {$imageColSelect}
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE m.id = ?
            ");
            $newMsg->execute([$messageId]);
            $message = $newMsg->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'message' => $message]);
            break;

        /* ── RÉCUPÉRER UN POST PARTAGÉ ───────────────── */
        case 'get_shared_post':
            $postId = (int) ($_GET['post_id'] ?? 0);
            if ($postId <= 0) {
                throw new Exception('ID publication invalide');
            }

            $stmt = $pdo->prepare("
                SELECT p.id, p.body, p.image_path, p.created_at, u.username
                FROM posts p
                JOIN users u ON u.id = p.user_id
                WHERE p.id = ?
                LIMIT 1
            ");
            $stmt->execute([$postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                throw new Exception('Publication introuvable');
            }

            echo json_encode(['success' => true, 'post' => $post]);
            break;

        /* ── LISTE DES GROUPES (MESSAGERIE) ───────────── */
        case 'list_groups':
            $stmt = $pdo->prepare("
                SELECT
                    g.id,
                    g.name,
                    g.icon,
                    g.color,
                    gm.role,
                    (
                        SELECT gm2.body
                        FROM group_messages gm2
                        WHERE gm2.group_id = g.id
                        ORDER BY gm2.created_at DESC
                        LIMIT 1
                    ) as last_message,
                    (
                        SELECT gm3.created_at
                        FROM group_messages gm3
                        WHERE gm3.group_id = g.id
                        ORDER BY gm3.created_at DESC
                        LIMIT 1
                    ) as last_time
                FROM groups g
                JOIN group_members gm ON gm.group_id = g.id
                WHERE gm.user_id = ?
                ORDER BY last_time DESC, g.created_at DESC
            ");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'groups' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        /* ── MESSAGES D'UN GROUPE ─────────────────────── */
        case 'get_group':
            $groupId = (int) ($_GET['group_id'] ?? 0);
            if ($groupId === 0) {
                throw new Exception('ID groupe invalide');
            }

            assertGroupMember($pdo, $groupId, $userId);

            $stmt = $pdo->prepare("
                SELECT
                    gm.id,
                    gm.group_id,
                    gm.sender_id,
                    gm.body,
                    gm.image_path,
                    gm.created_at,
                    u.username as sender_name
                FROM group_messages gm
                JOIN users u ON u.id = gm.sender_id
                WHERE gm.group_id = ?
                ORDER BY gm.created_at ASC
                LIMIT 150
            ");
            $stmt->execute([$groupId]);
            echo json_encode(['success' => true, 'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        /* ── ENVOI MESSAGE GROUPE ─────────────────────── */
        case 'send_group':
            $groupId = 0;
            $body = '';
            $imagePath = null;
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
                $groupId = (int) ($input['group_id'] ?? 0);
                $body = trim($input['body'] ?? '');
            } else {
                $groupId = (int) ($_POST['group_id'] ?? 0);
                $body = trim($_POST['body'] ?? '');

                if (isset($_FILES['image'])) {
                    $imagePath = storeUploadedImage($_FILES['image'], 'gmsg_' . $groupId . '_' . $userId);
                }
            }

            if ($groupId === 0) {
                throw new Exception('Groupe invalide');
            }
            if (empty($body) && $imagePath === null) {
                throw new Exception('Message vide');
            }

            assertGroupMember($pdo, $groupId, $userId);

            $stmt = $pdo->prepare("
                INSERT INTO group_messages (group_id, sender_id, body, image_path)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$groupId, $userId, $body, $imagePath]);
            $msgId = (int) $pdo->lastInsertId();

            $newStmt = $pdo->prepare("
                SELECT
                    gm.id,
                    gm.group_id,
                    gm.sender_id,
                    gm.body,
                    gm.image_path,
                    gm.created_at,
                    u.username as sender_name
                FROM group_messages gm
                JOIN users u ON u.id = gm.sender_id
                WHERE gm.id = ?
            ");
            $newStmt->execute([$msgId]);

            echo json_encode(['success' => true, 'message' => $newStmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        default:
            throw new Exception('Action inconnue');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}