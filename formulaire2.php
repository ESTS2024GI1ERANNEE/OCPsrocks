<?php
session_start();
$id_admin = $_SESSION['id_admin']; // Assurez-vous que l'utilisateur est connecté et que son identifiant est stocké dans la session

// Inclusion du fichier de configuration de la base de données
require 'config.php';

// Fonction pour récupérer les données de la base de données pour un utilisateur spécifique
function fetchProduits($conn, $id_admin) {
    $query = "SELECT * FROM dossiers_equipements WHERE id_admin = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_admin);
    $stmt->execute();
    return $stmt->get_result();
}

// Vérifier si le formulaire a été soumis pour l'importation
if (isset($_POST["import"])) {
    // Chemin du répertoire cible pour télécharger le fichier
    $uploadDirectory = "uploads/";

    // Récupérer le nom du fichier et l'extension
    $fileName = $_FILES["excel"]["name"];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    // Générer un nouveau nom de fichier unique basé sur la date et l'heure
    $newFileName = date("Y.m.d_H.i.s") . "." . $fileExtension;

    // Chemin complet pour enregistrer le fichier téléchargé
    $targetFilePath = $uploadDirectory . $newFileName;

    // Vérifier si le fichier a été téléchargé avec succès
    if (move_uploaded_file($_FILES["excel"]["tmp_name"], $targetFilePath)) {
        // Inclure les classes pour lire le fichier Excel
        require 'excelReader/excel_reader2.php';
        require 'excelReader/SpreadsheetReader.php';

        // Initialiser le lecteur pour lire le fichier Excel
        $reader = new SpreadsheetReader($targetFilePath);

        // Préparer la requête d'insertion avec l'identifiant de l'utilisateur
        $query = "INSERT INTO dossiers_equipements (no_dossier, equipement, designation, code, qte_inst, qte_dem, eb, date_eb, da, date_da, rfi, ao, ct, cmd, date_cmd, acheteur, gestionnaire, fournisseur, statut, id_admin) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        // Boucler à travers chaque ligne du fichier Excel
        foreach ($reader as $row) {
            $stmt->bind_param(
                "ssssssssssssssssssssi",
                $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8],
                $row[9], $row[10], $row[11], $row[12], $row[13], $row[14], $row[15], $row[16], $row[17],
                $row[18], $id_admin
            );

            if ($stmt->execute()) {
                echo "Record inserted successfully<br>";
            } else {
                echo "Error inserting record: " . $stmt->error . "<br>";
            }
        }

        // Fermer la connexion à la base de données
        $stmt->close();
        $conn->close();

        // Supprimer le fichier téléchargé après l'importation
        unlink($targetFilePath);

        // Afficher un message d'importation réussie
        echo "<script>
              alert('Importation réussie');
              window.location.href = '';
              </script>";
    } else {
        // Afficher un message si le téléchargement du fichier a échoué
        echo "Échec du téléchargement du fichier.";
    }
}

// Initialiser la variable pour les résultats de la recherche
$result = null;

// Initialiser la variable pour le formulaire de mise à jour
$updateForm = false;

// Vérifier si une recherche par article a été soumise
if (isset($_POST["search"])) {
    $searchArticle = $_POST["searchArticle"];

    // Requête SQL pour rechercher par code pour l'utilisateur connecté
    $query = "SELECT * FROM dossiers_equipements WHERE code = ? AND id_admin = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $searchArticle, $id_admin);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Vérifier si des résultats sont disponibles pour afficher les éléments
$showItems = ($result && $result->num_rows > 0);

if (isset($_POST["deleteAll"])) {
    $query = "DELETE FROM dossiers_equipements WHERE id_admin=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_admin);

    if ($stmt->execute()) {
        echo "<script>alert('Tous les enregistrements ont été supprimés avec succès.');</script>";
        // Optionnel: Redirect or refresh the page after deletion
        echo "<script>window.location.href = '';</script>";
    } else {
        echo "Erreur lors de la suppression des enregistrements: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}

if (isset($_POST["edit"])) {
    $articleToUpdate = $_POST["articleToEdit"];
    $query = "SELECT * FROM dossiers_equipements WHERE code = ? AND id_admin = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $articleToUpdate, $id_admin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $currentValues = $row;
        $updateForm = true;
    } else {
        echo "Aucun enregistrement trouvé pour le code : $articleToUpdate.";
    }
}

if (isset($_POST["updateRecord"])) {
    $articleToUpdate = $_POST["articleToUpdate"];
    $newValues = [
        'designation' => $_POST["newDesignation"],
        'code' => $_POST["newCode"],
        'qte_inst' => $_POST["newQteInst"],
        'qte_dem' => $_POST["newQteDem"],
        'eb' => $_POST["newEb"],
        'date_eb' => $_POST["newDateEb"],
        'da' => $_POST["newDa"],
        'date_da' => $_POST["newDateDa"],
        'rfi' => $_POST["newRfi"],
        'ao' => $_POST["newAo"],
        'ct' => $_POST["newCt"],
        'cmd' => $_POST["newCmd"],
        'date_cmd' => $_POST["newDateCmd"],
        'acheteur' => $_POST["newAcheteur"],
        'gestionnaire' => $_POST["newGestionnaire"],
        'fournisseur' => $_POST["newFournisseur"],
        'statut' => $_POST["newStatut"]
    ];

    $query = "UPDATE dossiers_equipements SET 
              designation = ?, code = ?, qte_inst = ?, qte_dem = ?, eb = ?, date_eb = ?, da = ?, date_da = ?, rfi = ?, ao = ?, ct = ?, cmd = ?, date_cmd = ?, acheteur = ?, gestionnaire = ?, fournisseur = ?, statut = ?
              WHERE code = ? AND id_admin = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "ssssssssssssssssssi",
        $newValues['designation'], $newValues['code'], $newValues['qte_inst'], $newValues['qte_dem'], $newValues['eb'], $newValues['date_eb'], $newValues['da'], $newValues['date_da'],
        $newValues['rfi'], $newValues['ao'], $newValues['ct'], $newValues['cmd'], $newValues['date_cmd'], $newValues['acheteur'], $newValues['gestionnaire'], $newValues['fournisseur'],
        $newValues['statut'], $articleToUpdate, $id_admin
    );

    if ($stmt->execute()) {
        echo "<script>alert('Enregistrement mis à jour avec succès.');</script>";
    } else {
        echo "Erreur lors de la mise à jour de l'enregistrement : " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Importer Excel vers MySQL</title>
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

        form input[type=file] {
            padding: 8px;
            font-size: 15px;
            border: 2px solid #FFD700;
            border-radius: 10px;
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

        .item {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        .item h3 {
            margin-top: 0;
            color: #FFD700;
            font-size: 20px;
            font-family: 'Source Sans Pro', sans-serif;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .item p {
            margin: 10px 0;
            color: #000;
            font-size: 15px;
            font-family: 'Source Sans Pro', sans-serif;
        }

        .item p strong {
            color: #009966;
        }
        .update-form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }
        .update-form-container input, .update-form-container button {
            margin-top: 10px;
            display: block;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="signout.php" class="back-button">Retourner</a>
        <h2>Importer Excel vers MySQL</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="excel" required>
            <button type="submit" name="import">Importer</button>
        </form>
        <form action="" method="post">
            <button type="submit" name="deleteAll">Supprimer tous les enregistrements</button>
        </form>

        <div class="search-container">
            <form action="" method="post">
                <input type="text" name="searchArticle" placeholder="Search by code">
                <button type="submit" name="search">Rechercher</button>
            </form>
        </div>

        <?php if ($showItems): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="item">
                    <h3><?php echo htmlspecialchars($row['no_dossier']); ?></h3>
                    <p><strong>EQUIPEMENT:</strong> <?php echo htmlspecialchars($row['equipement']); ?></p>
                    <p><strong>DESIGNATION:</strong> <?php echo htmlspecialchars($row['designation']); ?></p>
                    <p><strong>CODE:</strong> <?php echo htmlspecialchars($row['code']); ?></p>
                    <p><strong>QTE INST:</strong> <?php echo htmlspecialchars($row['qte_inst']); ?></p>
                    <p><strong>QTE DEM:</strong> <?php echo htmlspecialchars($row['qte_dem']); ?></p>
                    <p><strong>EB:</strong> <?php echo htmlspecialchars($row['eb']); ?></p>
                    <p><strong>DATE EB:</strong> <?php echo htmlspecialchars($row['date_eb']); ?></p>
                    <p><strong>DA:</strong> <?php echo htmlspecialchars($row['da']); ?></p>
                    <p><strong>DATE DA:</strong> <?php echo htmlspecialchars($row['date_da']); ?></p>
                    <p><strong>RFI:</strong> <?php echo htmlspecialchars($row['rfi']); ?></p>
                    <p><strong>AO:</strong> <?php echo htmlspecialchars($row['ao']); ?></p>
                    <p><strong>CT:</strong> <?php echo htmlspecialchars($row['ct']); ?></p>
                    <p><strong>CMD:</strong> <?php echo htmlspecialchars($row['cmd']); ?></p>
                    <p><strong>DATE CMD:</strong> <?php echo htmlspecialchars($row['date_cmd']); ?></p>
                    <p><strong>Acheteur:</strong> <?php echo htmlspecialchars($row['acheteur']); ?></p>
                    <p><strong>Gestionnaire:</strong> <?php echo htmlspecialchars($row['gestionnaire']); ?></p>
                    <p><strong>FOURNISSEUR:</strong> <?php echo htmlspecialchars($row['fournisseur']); ?></p>
                    <p><strong>STATUT:</strong> <?php echo htmlspecialchars($row['statut']); ?></p>
                    <form action="" method="post" style="display:inline;">
                            <input type="hidden" name="articleToEdit" value="<?php echo htmlspecialchars($row['code']); ?>">
                            <button type="submit" name="edit">Modifier</button>
                        </form>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <?php if ($updateForm): ?>
            <div class="update-form-container">
            <h2>Modifier</h2>
                <form method="post">
                    <input type="hidden" name="articleToUpdate" value="<?php echo htmlspecialchars($currentValues['code']); ?>">
                    <p><strong>Numéro de Dossier:</strong> <?php echo htmlspecialchars($currentValues['no_dossier']); ?></p>
                    <p><strong>Équipement:</strong> <?php echo htmlspecialchars($currentValues['equipement']); ?></p>
                    <p><strong>Désignation:</strong> <input type="text" name="newDesignation" value="<?php echo htmlspecialchars($currentValues['designation']); ?>" /></p>
                    <p><strong>Code:</strong> <input type="text" name="newCode" value="<?php echo htmlspecialchars($currentValues['code']); ?>" /></p>
                    <p><strong>Quantité Installée:</strong> <input type="text" name="newQteInst" value="<?php echo htmlspecialchars($currentValues['qte_inst']); ?>" /></p>
                    <p><strong>Quantité Demandée:</strong> <input type="text" name="newQteDem" value="<?php echo htmlspecialchars($currentValues['qte_dem']); ?>" /></p>
                    <p><strong>EB:</strong> <input type="text" name="newEb" value="<?php echo htmlspecialchars($currentValues['eb']); ?>" /></p>
                    <p><strong>Date EB:</strong> <input type="text" name="newDateEb" value="<?php echo htmlspecialchars($currentValues['date_eb']); ?>" /></p>
                    <p><strong>DA:</strong> <input type="text" name="newDa" value="<?php echo htmlspecialchars($currentValues['da']); ?>" /></p>
                    <p><strong>Date DA:</strong> <input type="text" name="newDateDa" value="<?php echo htmlspecialchars($currentValues['date_da']); ?>" /></p>
                    <p><strong>RFI:</strong> <input type="text" name="newRfi" value="<?php echo htmlspecialchars($currentValues['rfi']); ?>" /></p>
                    <p><strong>AO:</strong> <input type="text" name="newAo" value="<?php echo htmlspecialchars($currentValues['ao']); ?>" /></p>
                    <p><strong>CT:</strong> <input type="text" name="newCt" value="<?php echo htmlspecialchars($currentValues['ct']); ?>" /></p>
                    <p><strong>CMD:</strong> <input type="text" name="newCmd" value="<?php echo htmlspecialchars($currentValues['cmd']); ?>" /></p>
                    <p><strong>Date CMD:</strong> <input type="text" name="newDateCmd" value="<?php echo htmlspecialchars($currentValues['date_cmd']); ?>" /></p>
                    <p><strong>Acheteur:</strong> <input type="text" name="newAcheteur" value="<?php echo htmlspecialchars($currentValues['acheteur']); ?>" /></p>
                    <p><strong>Gestionnaire:</strong> <input type="text" name="newGestionnaire" value="<?php echo htmlspecialchars($currentValues['gestionnaire']); ?>" /></p>
                    <p><strong>Fournisseur:</strong> <input type="text" name="newFournisseur" value="<?php echo htmlspecialchars($currentValues['fournisseur']); ?>" /></p>
                    <p><strong>Statut:</strong> <input type="text" name="newStatut" value="<?php echo htmlspecialchars($currentValues['statut']); ?>" /></p>
                    <button type="submit" name="updateRecord">Mettre à jour</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
