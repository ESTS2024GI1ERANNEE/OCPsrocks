<?php 
require 'config.php';
session_start();

function fetchProduits($conn, $id_admin) {
    $query = "SELECT * FROM produits WHERE id_admin = '$id_admin'";
    $result = mysqli_query($conn, $query);
    return $result;
}

if (isset($_POST["import"])) {
    $uploadDirectory = "uploads/";
    $fileName = $_FILES["excel"]["name"];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = date("Y.m.d_H.i.s") . "." . $fileExtension;
    $targetFilePath = $uploadDirectory . $newFileName;

    if (move_uploaded_file($_FILES["excel"]["tmp_name"], $targetFilePath)) {
        require 'excelReader/excel_reader2.php';
        require 'excelReader/SpreadsheetReader.php';
        $reader = new SpreadsheetReader($targetFilePath);
        $id_admin = $_SESSION['id_admin'];

        foreach ($reader as $row) {
            $values = array_map(function($value) use ($conn) {
                return mysqli_real_escape_string($conn, $value);
            }, $row);

            list($article, $description, $descriptif, $unite_mesure, $QUANTITE_EN_STOCK2, $pmp) = $values;

            $checkQuery = "SELECT * FROM produits WHERE ARTICLE='$article' AND id_admin='$id_admin'";
            $checkResult = mysqli_query($conn, $checkQuery);

            if ($checkResult === false) {
                echo "Query error: " . mysqli_error($conn) . "<br>";
            } else {
                if (mysqli_num_rows($checkResult) == 0) {
                    $query = "INSERT INTO produits 
                              (ARTICLE, DESCRIPTION, DESCRIPTIF, 
                               UNITE_DE_MESURE, QUANTITE_EN_STOCK2, id_admin, pmp)
                              VALUES 
                              ('$article', '$description', '$descriptif', 
                               '$unite_mesure', '$QUANTITE_EN_STOCK2', '$id_admin', '$pmp')";

                    if (mysqli_query($conn, $query)) {
                        echo "Record inserted successfully<br>";
                    } else {
                        echo "Error inserting record: " . mysqli_error($conn) . "<br>";
                    }
                } else {
                    echo "Record already exists for ARTICLE: $article<br>";
                }
            }
        }

        mysqli_close($conn);
        unlink($targetFilePath);

        echo "<script>
              alert('Importation réussie');
              window.location.href = '';
              </script>";
    } else {
        echo "Échec du téléchargement du fichier.";
    }
}

$showTable = false;
$updateForm = false;
if (isset($_POST["search"])) {
    $searchArticle = mysqli_real_escape_string($conn, $_POST["searchArticle"]);
    $id_admin = $_SESSION['id_admin'];
    $query = "SELECT * FROM produits WHERE ARTICLE = '$searchArticle' AND id_admin = '$id_admin'";
    $result = mysqli_query($conn, $query);
    $showTable = true;
}

if (isset($_POST["deleteAll"])) {
    $id_admin = $_SESSION['id_admin'];
    $query = "DELETE FROM produits WHERE id_admin='$id_admin'";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Tous les enregistrements ont été supprimés avec succès.');</script>";
        echo "<script>window.location.href = '';</script>";
    } else {
        echo "Erreur lors de la suppression des enregistrements: " . mysqli_error($conn);
    }
}

if (isset($_POST["edit"])) {
    $articleToUpdate = mysqli_real_escape_string($conn, $_POST["articleToEdit"]);
    $id_admin = $_SESSION['id_admin'];
    
    // Fetch current data for the article
    $query = "SELECT * FROM produits WHERE ARTICLE='$articleToUpdate' AND id_admin='$id_admin'";
    $result = mysqli_query($conn, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $currentDescription = $row['DESCRIPTION'];
        $currentDescriptif = $row['DESCRIPTIF'];
        $currentUniteMesure = $row['UNITE_DE_MESURE'];
        $currentQuantiteEnStock2 = $row['QUANTITE_EN_STOCK2'];
        $currentPmp = $row['pmp'];
        $updateForm = true;
    } else {
        echo "Aucun enregistrement trouvé pour l'article : $articleToUpdate.";
    }
}

if (isset($_POST["updateRecord"])) {
    $articleToUpdate = mysqli_real_escape_string($conn, $_POST["articleToUpdate"]);
    $newDescription = mysqli_real_escape_string($conn, $_POST["newDescription"]);
    $newDescriptif = mysqli_real_escape_string($conn, $_POST["newDescriptif"]);
    $newUniteMesure = mysqli_real_escape_string($conn, $_POST["newUniteMesure"]);
    $newQuantiteEnStock2 = mysqli_real_escape_string($conn, $_POST["newQuantiteEnStock2"]);
    $newPmp = mysqli_real_escape_string($conn, $_POST["newPmp"]);
    $id_admin = $_SESSION['id_admin'];
    
    $query = "UPDATE produits SET 
              DESCRIPTION='$newDescription', 
              DESCRIPTIF='$newDescriptif', 
              UNITE_DE_MESURE='$newUniteMesure', 
              QUANTITE_EN_STOCK2='$newQuantiteEnStock2', 
              pmp='$newPmp'
              WHERE ARTICLE='$articleToUpdate' AND id_admin='$id_admin'";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Enregistrement mis à jour avec succès.');</script>";
        echo "<script>window.location.href = '';</script>";
    } else {
        echo "Erreur lors de la mise à jour de l'enregistrement : " . mysqli_error($conn);
    }
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
            background: linear-gradient(to bottom,#FFD700, #fff, #fff);
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
            padding: 50px;
            width: 500px;
            border-radius: 8px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 1);
            display: flex;
            flex-direction: column;
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
                <input type="text" name="searchArticle" placeholder="Search by Article">
                <button type="submit" name="search">Rechercher</button>
            </form>
        </div>

        <?php if ($showTable): ?>
            <div class="item-container">
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <div class="item">
                        <h3><?php echo htmlspecialchars($row['ARTICLE']); ?></h3>
                        <p><strong>DESCRIPTION:</strong> <?php echo htmlspecialchars($row['DESCRIPTION']); ?></p>
                        <p><strong>DESCRIPTIF:</strong> <?php echo htmlspecialchars($row['DESCRIPTIF']); ?></p>
                        <p><strong>UNITE_DE_MESURE:</strong> <?php echo htmlspecialchars($row['UNITE_DE_MESURE']); ?></p>
                        <p><strong>QUANTITE_EN_STOCK2:</strong> <?php echo htmlspecialchars($row['QUANTITE_EN_STOCK2']); ?></p>
                        <p><strong>PMP (DH):</strong> <?php echo htmlspecialchars($row['pmp']); ?></p>
                        <form action="" method="post" style="display:inline;">
                            <input type="hidden" name="articleToEdit" value="<?php echo htmlspecialchars($row['ARTICLE']); ?>">
                            <button type="submit" name="edit">Modifier</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <?php if ($updateForm): ?>
            <div class="update-form-container">
                <h2>Modifier</h2>
                <form action="" method="post">
                    <input type="hidden" name="articleToUpdate" value="<?php echo htmlspecialchars($articleToUpdate); ?>">
                    <label for="newDescription">Nouveau DESCRIPTION:</label>
                    <input type="text" id="newDescription" name="newDescription" value="<?php echo htmlspecialchars($currentDescription); ?>" required>
                    <label for="newDescriptif">Nouveau DESCRIPTIF:</label>
                    <input type="text" id="newDescriptif" name="newDescriptif" value="<?php echo htmlspecialchars($currentDescriptif); ?>" required>
                    <label for="newUniteMesure">Nouvelle UNITE_DE_MESURE:</label>
                    <input type="text" id="newUniteMesure" name="newUniteMesure" value="<?php echo htmlspecialchars($currentUniteMesure); ?>" required>
                    <label for="newQuantiteEnStock2">Nouvelle QUANTITE_EN_STOCK2:</label>
                    <input type="text" id="newQuantiteEnStock2" name="newQuantiteEnStock2" value="<?php echo htmlspecialchars($currentQuantiteEnStock2); ?>" required>
                    <label for="newPmp">Nouveau PMP (DH):</label>
                    <input type="text" id="newPmp" name="newPmp" value="<?php echo htmlspecialchars($currentPmp); ?>" required>
                    <button type="submit" name="updateRecord">Mettre à jour</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
