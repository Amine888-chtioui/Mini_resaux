# Mini Réseau Social

Un réseau social minimaliste développé en PHP avec Bootstrap 5.

## 📁 Structure du Projet

```
Mini_resaux/
├── 📄 Pages Principales
│   ├── index.php              # Page d'accueil
│   ├── profile.php            # Profil utilisateur
│   ├── friends.php            # Gestion des amis
│   ├── groups.php             # Gestion des groupes
│   ├── messages.php           # Messagerie
│   ├── notifications.php      # Notifications
│   └── (section discover supprimée)
│
├── 🔐 Authentification
│   ├── auth/
│   │   ├── login.php          # Connexion
│   │   ├── register.php       # Inscription
│   │   ├── logout.php         # Déconnexion
│   │   └── connexion.php      # Formulaire de connexion
│
├── 🌐 API Endpoints
│   └── api/
│       ├── friends.php        # API amis
│       ├── groups.php         # API groupes
│       ├── messages.php       # API messagerie
│       ├── notifications.php  # API notifications
│       └── photos.php         # API photos
│
├── 🎨 Assets
│   ├── css/
│   │   ├── style.css          # Styles généraux
│   │   ├── profile.css        # Styles profil
│   │   ├── auth.css           # Styles authentification
│   │   └── landing.css        # Styles page d'accueil
│   ├── js/
│   │   ├── app.js             # JavaScript principal
│   │   ├── auth.js            # JavaScript authentification
│   │   └── landing.js         # JavaScript accueil
│   └── images/
│       ├── ensiasd-logo.png  # Logo ENSIASD
│       ├── ensiasd-logo.svg  # Logo SVG
│       └── landing-*.svg     # Illustrations accueil
│
├── 📁 Includes
│   ├── config.php             # Configuration
│   ├── db.php                 # Connexion base de données
│   ├── header.php             # En-tête HTML
│   └── footer.php             # Pied de page HTML
│
├── 📤 Uploads
│   └── uploads/
│       ├── photos/            # Photos uploadées
│       └── messages/          # Fichiers messages
│
├── 📝 Posts
│   ├── posts/
│   │   ├── create.php         # Création de posts
│   │   ├── like.php           # Gestion des likes
│   │   └── comment.php        # Gestion des commentaires
│
└── 📋 Documentation
    ├── README.md               # Ce fichier
    ├── README_SOCIAL.md       # Documentation système social
    ├── composer.json           # Dépendances PHP
    └── composer.lock           # Lock des dépendances
```

## 🚀 Fonctionnalités

### 👥 Social
- **Gestion des amis** : Recherche, ajout, acceptation/refus
- **Groupes** : Création, rejoindre, quitter, gestion des membres
- **Messagerie** : Conversations privées en temps réel
- **Notifications** : Alertes pour les activités sociales

### 📸 Contenu
- **Photos** : Upload, likes, commentaires
- **Posts** : Création et partage de contenu

### 🔐 Sécurité
- **Authentification** : Login/Register sécurisés
- **Session** : Gestion des sessions utilisateur
- **Validation** : Protection contre les injections

## 🛠️ Installation

1. **Cloner le projet**
   ```bash
   git clone <repository-url>
   cd Mini_resaux
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer la base de données**
   - Importer le schéma SQL
   - Configurer `includes/config.php`

4. **Configurer le serveur web**
   - Pointer le document root vers `Mini_resaux/`
   - Assurer PHP 8+ et MySQL/MariaDB

## 📱 Utilisation

1. **Créer un compte** via `/auth/register.php`
2. **Se connecter** via `/auth/login.php`
3. **Explorer** les fonctionnalités sociales

## 🌐 Pages

| Page | Description | Route |
|------|-------------|-------|
| Accueil | Page d'accueil du réseau | `/` |
| Profil | Profil utilisateur et paramètres | `/profile.php` |
| Amis | Gestion des relations sociales | `/friends.php` |
| Groupes | Communautés et discussions | `/groups.php` |
| Messages | Messagerie privée | `/messages.php` |
| Notifications | Centre de notifications | `/notifications.php` |

## 🔧 Configuration

### Base de données
```php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mini_resaux');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### URL de base
```php
// includes/config.php
define('BASE_URL', 'http://localhost/Mini_resaux');
```

## 📝 Notes de Développement

- **Architecture MVC** simplifiée
- **API REST** pour les fonctionnalités AJAX
- **Responsive Design** avec Bootstrap 5
- **Mode développement** intégré (user_id = 1 par défaut)

## 🤝 Contributeurs

Projet développé pour l'ENSIASD - Mini réseau social éducatif.

## 📄 Licence

Projet éducatif - Usage académique uniquement.
"# mini-social-network" 
"# mini-social-network" 
