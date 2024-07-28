<?php
// Inclusion du fichier de configuration de la base de données
require 'config.php';

// Initialisation des variables
$result = null;
$notification = '';
$searchPerformed = false; // Ajouté pour vérifier si une recherche a été effectuée

// Fonction pour récupérer les données combinées des deux tables pour un utilisateur spécifique
function fetchCombinedDataForUser($conn, $id_admin, $searchArticle = '') {
    // Préparer la requête SQL avec une condition de recherche optionnelle
    $query = "SELECT p.ARTICLE, p.DESCRIPTION, p.DESCRIPTIF, 
                     p.UNITE_DE_MESURE, p.QUANTITE_EN_STOCK2, p.pmp,
                     d.qte_dem, d.eb, d.da, d.rfi, d.ao, d.ct, d.cmd, d.statut 
              FROM produits p 
              JOIN dossiers_equipements d ON p.ARTICLE = d.code 
              WHERE d.id_admin = ?";

    // Ajouter la condition de recherche exacte si elle est présente
    if (!empty($searchArticle)) {
        $query .= " AND p.ARTICLE = ?";
    }

    $stmt = mysqli_prepare($conn, $query);

    if (!empty($searchArticle)) {
        mysqli_stmt_bind_param($stmt, 'ss', $id_admin, $searchArticle);
    } else {
        mysqli_stmt_bind_param($stmt, 's', $id_admin);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return $result;
}

// Vérifier l'administrateur connecté (exemple de session PHP)
session_start();
if (isset($_SESSION['id_admin'])) {
    $id_admin = $_SESSION['id_admin'];

    // Vérifier si une recherche par article a été soumise
    if (isset($_POST["search"])) {
        $searchArticle = mysqli_real_escape_string($conn, $_POST["searchArticle"]);
        $result = fetchCombinedDataForUser($conn, $id_admin, $searchArticle);
        $searchPerformed = true; // Marquer la recherche comme effectuée
    } else {
        // Récupérer les données pour l'utilisateur sans recherche
        $result = fetchCombinedDataForUser($conn, $id_admin);
    }

    // Notification si le stock_AE est inférieur à 1
    if ($searchPerformed && $result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Déterminer le statut
            $statut = !empty($row['statut']) ? htmlspecialchars($row['statut']) : "<span style='color: red;'>Commande nécessaire immédiatement</span>";
            
            if ($row['QUANTITE_EN_STOCK2'] < 1) {
                $notification .= "Article: " . htmlspecialchars($row['ARTICLE']) . ", Qte_Dem: " . htmlspecialchars($row['qte_dem']) . ", EB: " . htmlspecialchars($row['eb']) . ", DA: " . htmlspecialchars($row['da']) . ", RFI: " . htmlspecialchars($row['rfi']) . ", AO: " . htmlspecialchars($row['ao']) . ", CT: " . htmlspecialchars($row['ct']) . ", Statut: " . $statut . "<br>";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Affichage des données combinées</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            margin: 0;
            background-color: #009966;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            opacity: 0.9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.9);
            position: relative;
            background: linear-gradient(to bottom, #FFD700, #fff, #fff);
        }
        .search-container {
            margin-bottom: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-container input[type=text] {
            padding: 10px;
            width: 200px;
            font-size: 15px;
            border-radius: 10px;
            border: 2px solid #FFD700;
        }
        form button[type=submit] {
            padding: 8px 20px;
            background-color: #009966;
            color: #FFD700;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 10px;
            margin: 20px;
        }
        form button[type=submit]:hover {
            background-color: #FFD700;
            color: #009966;
        }
        a.back-button {
            position: absolute;
            top: 20px;
            right: 20px;
            text-decoration: none;
            background-color: #009966;
            color: #FFD700;
            padding: 10px 20px;
            border-radius: 5px;
        }
        a.back-button:hover {
            color: #009966;
            background-color: #FFD700;
        }
        .notification {
            background-color: #f8d7da; /* Couleur de fond pour une notification */
            color: #721c24; /* Couleur du texte */
            border: 1px solid #f5c6cb; /* Bordure */
            border-radius: 5px; /* Coins arrondis */
            padding: 15px; /* Espacement intérieur */
            margin-bottom: 15px; /* Marge inférieure */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Ombre légère */
        }

        .notification h3 {
            margin-top: 0; /* Supprimer la marge supérieure */
            color: #721c24; /* Couleur du titre */
        }

        .notification p {
            margin-bottom: 0; /* Supprimer la marge inférieure des paragraphes */
        }

        .item {
            background-color: #fff;
            margin: 25px; /* Adjust margin as needed to create space around the item */
            width: calc(100% - 50px); /* Adjust width to account for the margin */
            border-radius: 8px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 1);
            display: flex;
            flex-direction: column; /* Ensures each element is on a new line */
        }

        .item p {
            margin: 10px 25px; /* Adjust margins to create internal spacing */
            color: #000;
            font-size: 15px;
            font-family: 'Source Sans Pro', sans-serif; /* Consistent font */
        }

        .item p strong {
            color: #009966;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="signout.php" class="back-button">Retourner</a>
        <h2>Affichage des données combinées</h2>
        <div class="search-container">
            <form action="" method="post">
                <input type="text" name="searchArticle" placeholder="Search by Article">
                <button type="submit" name="search">Rechercher</button>
            </form>
            <form action="notification.php" method="post">
                <button type="submit" name="showNotification">Afficher les notifications</button>
            </form>
        </div>

        <!-- Affichage des notifications comme des alertes -->
        <?php if ($searchPerformed && !empty($notification)) : ?>
            <div class="notification">
                <h3>Notification</h3>
                <p><?php echo $notification; ?></p>
            </div>
        <?php endif; ?>

        <!-- Affichage des éléments dans des carrés -->
        <?php if ($searchPerformed && $result && mysqli_num_rows($result) > 0) : ?>
            <?php mysqli_data_seek($result, 0); // Réinitialiser le pointeur de résultat ?>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <div class='item'>
                    <p><strong>ARTICLE:</strong> <?php echo htmlspecialchars($row['ARTICLE']); ?></p>
                    <p><strong>DESCRIPTION:</strong> <?php echo htmlspecialchars($row['DESCRIPTION']); ?></p>
                    <p><strong>DESCRIPTIF:</strong> <?php echo htmlspecialchars($row['DESCRIPTIF']); ?></p>
                    <p><strong>UNITE_DE_MESURE:</strong> <?php echo htmlspecialchars($row['UNITE_DE_MESURE']); ?></p>
                    <p><strong>pmp(DH):</strong> <?php echo htmlspecialchars($row['pmp']); ?></p>
                    <p><strong>QUANTITE_EN_STOCK2:</strong> <?php echo htmlspecialchars($row['QUANTITE_EN_STOCK2']); ?></p>
                    <p><strong>QTE_DEM:</strong> <?php echo htmlspecialchars($row['qte_dem']); ?></p>
                    <p><strong>EB:</strong> <?php echo htmlspecialchars($row['eb']); ?></p>
                    <p><strong>DA:</strong> <?php echo htmlspecialchars($row['da']); ?></p>
                    <p><strong>RFI:</strong> <?php echo htmlspecialchars($row['rfi']); ?></p>
                    <p><strong>AO:</strong> <?php echo htmlspecialchars($row['ao']); ?></p>
                    <p><strong>CT:</strong> <?php echo htmlspecialchars($row['ct']); ?></p>
                    <p><strong>CMD:</strong> <?php echo htmlspecialchars($row['cmd']); ?></p>
                    <p><strong>STATUT:</strong> <?php echo !empty($row['statut']) ? htmlspecialchars($row['statut']) : "<span style='color: red;'>Commande nécessaire immédiatement</span>"; ?></p>
                </div>
            <?php endwhile; ?>
        <?php elseif ($searchPerformed) : ?>
            <p>No results found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
