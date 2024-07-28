<?php
// Inclusion du fichier de configuration de la base de données
require 'config.php';

// Fonction pour récupérer les données combinées des deux tables pour un utilisateur spécifique
function fetchCombinedData($conn, $id_admin) {
    $query = "SELECT p.ARTICLE, p.DESCRIPTION, p.DESCRIPTIF, 
                     p.UNITE_DE_MESURE, p.QUANTITE_EN_STOCK2, p.pmp,
                     d.qte_dem, d.eb, d.da, d.rfi, d.ao, d.ct, d.cmd, d.statut 
              FROM produits p 
              JOIN dossiers_equipements d ON p.ARTICLE = d.code 
              WHERE d.id_admin = $id_admin";
    $result = mysqli_query($conn, $query);
    return $result;
}

// Vérifier l'administrateur connecté (exemple de session PHP)
session_start();
if (isset($_SESSION['id_admin'])) {
    $id_admin = $_SESSION['id_admin'];

    // Récupérer les données combinées pour l'administrateur connecté
    $result = fetchCombinedData($conn, $id_admin);

    // Initialisation de la variable de notifications
    $notifications = [];

    // Parcourir les résultats et générer les notifications
    while ($row = mysqli_fetch_assoc($result)) {
        // Vérifier et définir le statut si nécessaire
        $statut = !empty($row['statut']) ? $row['statut'] : "<span style='color: red;'>Commande nécessaire immédiatement</span>";

        if ($row['QUANTITE_EN_STOCK2'] < 1) {
            $notification = [
                'Article' => htmlspecialchars($row['ARTICLE']),
                'Qte_Dem' => htmlspecialchars($row['qte_dem']),
                'EB' => htmlspecialchars($row['eb']),
                'DA' => htmlspecialchars($row['da']),
                'RFI' => htmlspecialchars($row['rfi']),
                'AO' => htmlspecialchars($row['ao']),
                'CT' => htmlspecialchars($row['ct']),
                'Statut' => $statut
            ];
            $notifications[] = $notification;
        }
    }
} else {
    // Gérer le cas où aucun administrateur n'est connecté
    $notifications[] = ['message' => 'Aucun administrateur n\'est connecté'];
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
            background-color: #009966;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            opacity: 0.9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.9);
        }

        .notification {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f2f2f2;
            border-left: 6px solid #f44336;
            border-radius: 5px;
        }

        .notification strong {
            font-size: 16px;
            display: inline-block;
            width: 100px;
        }

        .notification p {
            display: inline-block;
            margin: 5px 0;
            line-height: 1.5;
            vertical-align: top;
        }

        .button {
            display: block;
            text-align: center;
            margin-top: 20px;
            background-color: #f44336;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }

        .button:hover {
            background-color: #009966;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Notifications</h2>
        <a href="formulaire3.php" class="button">Retourner</a>
        <?php foreach ($notifications as $notification) : ?>
            <div class="notification">
                <?php if (isset($notification['Article'])) : ?>
                    <strong>Article:</strong> <p><?php echo $notification['Article']; ?></p><br>
                    <strong>Qte_Dem:</strong> <p><?php echo $notification['Qte_Dem']; ?></p><br>
                    <strong>EB:</strong> <p><?php echo $notification['EB']; ?></p><br>
                    <strong>DA:</strong> <p><?php echo $notification['DA']; ?></p><br>
                    <strong>RFI:</strong> <p><?php echo $notification['RFI']; ?></p><br>
                    <strong>AO:</strong> <p><?php echo $notification['AO']; ?></p><br>
                    <strong>CT:</strong> <p><?php echo $notification['CT']; ?></p><br>
                    <strong>Statut:</strong> <p><?php echo $notification['Statut']; ?></p><br>
                <?php else: ?>
                    <p><?php echo $notification['message']; ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
