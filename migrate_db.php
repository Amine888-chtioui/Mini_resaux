<?php
/**
 * Script de migration pour ajouter les colonnes de vérification email et reset password
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>Migration de la base de données</h1>";

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email_verified'");
    $emailVerifiedExists = $stmt->rowCount() > 0;
    
    if (!$emailVerifiedExists) {
        echo "<p>Ajout des colonnes de vérification email et reset password...</p>";
        
        // Ajouter les colonnes manquantes
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN verification_code VARCHAR(6) NULL,
            ADD COLUMN verification_expires TIMESTAMP NULL,
            ADD COLUMN reset_token VARCHAR(64) NULL,
            ADD COLUMN reset_expires TIMESTAMP NULL
        ");
        
        echo "<p style='color: green;'>✅ Colonnes ajoutées avec succès !</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Les colonnes existent déjà.</p>";
    }
    
    // Vérifier la structure finale
    echo "<h2>Structure actuelle de la table users :</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th></tr>";
    
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green;'><strong>✅ Migration terminée avec succès !</strong></p>";
    echo "<p><a href='auth/connexion.php'>Aller à la page de connexion</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
