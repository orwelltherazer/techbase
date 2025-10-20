<?php
/**
 * medias.php - Gestion de la bibliothèque de médias
 */

// Activer l'affichage des erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';
require_once 'config/database.php';


// Créer le dossier uploads s'il n'existe pas
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Upload de médias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_media') {
    if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
        $uploadedCount = 0;
        $errors = [];
        
        foreach ($_FILES['media']['name'] as $key => $filename) {
            $tmpName = $_FILES['media']['tmp_name'][$key];
            $fileSize = $_FILES['media']['size'][$key];
            $fileError = $_FILES['media']['error'][$key];
            
            // Vérifier les erreurs
            if ($fileError !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur lors de l'upload de $filename";
                continue;
            }
            
            // Vérifier la taille (10 Mo max)
            if ($fileSize > 10 * 1024 * 1024) {
                $errors[] = "$filename est trop volumineux (max 10 Mo)";
                continue;
            }
            
            // Vérifier l'extension
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array($ext, $allowedExts)) {
                $errors[] = "Format non autorisé pour $filename";
                continue;
            }
            
            // Déterminer le type
            $type = in_array($ext, ['jpg', 'jpeg', 'png']) ? 'image' : 'pdf';
            
            // Générer un nom de fichier unique
            $uniqueName = time() . '_' . uniqid() . '.' . $ext;
            $destination = $uploadDir . $uniqueName;
            
            // Déplacer le fichier
            if (move_uploaded_file($tmpName, $destination)) {
                // Enregistrer en base de données
                $url = '/techbase/uploads/' . $uniqueName;
                
                $stmt = $pdo->prepare("INSERT INTO medias (nom, nom_fichier, type, taille, url) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$filename, $uniqueName, $type, $fileSize, $url]);
                
                $uploadedCount++;
            } else {
                $errors[] = "Impossible de déplacer $filename";
            }
        }
        
        // Rediriger avec message
        $message = $uploadedCount > 0 ? "success=$uploadedCount fichier(s) téléversé(s)" : "";
        if (!empty($errors)) {
            $message .= "&errors=" . urlencode(implode(", ", $errors));
        }
        header("Location: ./?page=parametres&$message");
        exit;
    }
}

// Suppression de média
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_media') {
    $id = (int)$_POST['id'];
    
    // Récupérer les infos du fichier
    $stmt = $pdo->prepare("SELECT nom_fichier FROM medias WHERE id = ?");
    $stmt->execute([$id]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($media) {
        // Supprimer le fichier physique
        $filePath = $uploadDir . $media['nom_fichier'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Supprimer de la base
        $stmt = $pdo->prepare("DELETE FROM medias WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: ./?page=parametres&success=Média supprimé");
        exit;
    }
}

// === RÉCUPÉRATION DES MÉDIAS ===

$stmt = $pdo->query("SELECT 
    id,
    nom,
    nom_fichier,
    type,
    taille,
    url,
    date_upload
FROM medias 
ORDER BY date_upload DESC");

$medias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formater les données pour l'affichage
foreach ($medias as &$media) {
    // Formater la taille
    if ($media['taille'] < 1024) {
        $media['taille_format'] = $media['taille'] . ' o';
    } elseif ($media['taille'] < 1024 * 1024) {
        $media['taille_format'] = round($media['taille'] / 1024, 1) . ' Ko';
    } else {
        $media['taille_format'] = round($media['taille'] / (1024 * 1024), 1) . ' Mo';
    }
    
    // Formater la date
    $date = new DateTime($media['date_upload']);
    $media['date_format'] = $date->format('d/m/Y');
}

// === RENDU DU TEMPLATE ===

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader);

echo $twig->render('medias.html.twig', [
    'medias' => $medias,
    'success' => $_GET['success'] ?? null,
    'errors' => $_GET['errors'] ?? null,
    'rubriques' => getRubriques($pdo)
]);
?>