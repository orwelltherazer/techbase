<?php
// Activer l'affichage des erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';
require_once 'config/database.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Récupérer les paramètres
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Fonction pour générer un slug
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// Fonction pour récupérer les rubriques avec le nombre de fiches
function getRubriquesWithCount($pdo) {
    $stmt = $pdo->query("
        SELECT r.*, COUNT(f.id) as nb_fiches
        FROM rubriques r
        LEFT JOIN fiches f ON f.rubrique_id = r.id
        WHERE r.parent_id IS NULL
        GROUP BY r.id
        ORDER BY r.ordre
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Route pour la liste des rubriques
if ($action === 'list') {
    $rubriques = getRubriquesWithCount($pdo);
    
    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader);
    
    echo $twig->render('rubriques/list.html.twig', [
        'rubriques' => $rubriques,
        'rubriques_menu' => $rubriques
    ]);
    exit;
}

// Route pour créer une nouvelle rubrique
if ($action === 'new') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Récupérer l'ordre maximum pour suggérer le suivant
        $stmt = $pdo->query("SELECT MAX(ordre) as max_ordre FROM rubriques WHERE parent_id IS NULL");
        $maxOrdre = $stmt->fetchColumn() ?? 0;
        
        $loader = new FilesystemLoader(__DIR__ . '/templates');
        $twig = new Environment($loader);
        
        echo $twig->render('rubriques/form.html.twig', [
            'rubrique' => null,
            'suggested_ordre' => $maxOrdre + 1,
            'rubriques' => getRubriquesWithCount($pdo)
        ]);
        exit;
    }
    
    // Traitement du formulaire POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = trim($_POST['nom'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $ordre = (int)($_POST['ordre'] ?? 1);
        
        if (empty($nom)) {
            $error = "Le nom de la rubrique est obligatoire.";
        } else {
            // Générer le slug si vide
            if (empty($slug)) {
                $slug = generateSlug($nom);
            }
            
            // Vérifier que le slug n'existe pas déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rubriques WHERE slug = ?");
            $stmt->execute([$slug]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Ce slug existe déjà. Veuillez en choisir un autre.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO rubriques (nom, slug, ordre, parent_id)
                    VALUES (?, ?, ?, NULL)
                ");
                $stmt->execute([$nom, $slug, $ordre]);
                
                header('Location: rubriques.php?action=list&success=created');
                exit;
            }
        }
        
        // En cas d'erreur, réafficher le formulaire
        $loader = new FilesystemLoader(__DIR__ . '/templates');
        $twig = new Environment($loader);
        
        echo $twig->render('rubriques/form.html.twig', [
            'rubrique' => ['nom' => $nom, 'slug' => $slug, 'ordre' => $ordre],
            'error' => $error,
            'rubriques' => getRubriquesWithCount($pdo)
        ]);
        exit;
    }
}

// Route pour éditer une rubrique
if ($action === 'edit' && $id) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM rubriques WHERE id = ?");
        $stmt->execute([(int)$id]);
        $rubrique = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rubrique) {
            header('Location: rubriques.php?action=list&error=notfound');
            exit;
        }
        
        $loader = new FilesystemLoader(__DIR__ . '/templates');
        $twig = new Environment($loader);
        
        echo $twig->render('rubriques/form.html.twig', [
            'rubrique' => $rubrique,
            'rubriques' => getRubriquesWithCount($pdo)
        ]);
        exit;
    }
    
    // Traitement du formulaire POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = trim($_POST['nom'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $ordre = (int)($_POST['ordre'] ?? 1);
        
        if (empty($nom)) {
            $error = "Le nom de la rubrique est obligatoire.";
        } else {
            // Générer le slug si vide
            if (empty($slug)) {
                $slug = generateSlug($nom);
            }
            
            // Vérifier que le slug n'existe pas déjà (sauf pour cette rubrique)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rubriques WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, (int)$id]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Ce slug existe déjà. Veuillez en choisir un autre.";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE rubriques
                    SET nom = ?, slug = ?, ordre = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $slug, $ordre, (int)$id]);
                
                header('Location: rubriques.php?action=list&success=updated');
                exit;
            }
        }
        
        // En cas d'erreur, réafficher le formulaire
        $loader = new FilesystemLoader(__DIR__ . '/templates');
        $twig = new Environment($loader);
        
        echo $twig->render('rubriques_edit.html.twig', [
            'rubrique' => ['id' => $id, 'nom' => $nom, 'slug' => $slug, 'ordre' => $ordre],
            'error' => $error,
            'rubriques' => getRubriquesWithCount($pdo)
        ]);
        exit;
    }
}

// Route pour supprimer une rubrique
if ($action === 'delete' && $id) {
    // Vérifier qu'il n'y a pas de fiches associées
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fiches WHERE rubrique_id = ?");
    $stmt->execute([(int)$id]);
    $nbFiches = $stmt->fetchColumn();
    
    if ($nbFiches > 0) {
        header('Location: rubriques.php?action=list&error=hasfiches&count=' . $nbFiches);
        exit;
    }
    
    // Supprimer la rubrique
    $stmt = $pdo->prepare("DELETE FROM rubriques WHERE id = ?");
    $stmt->execute([(int)$id]);
    
    header('Location: rubriques.php?action=list&success=deleted');
    exit;
}

// Redirection par défaut
header('Location: rubriques.php?action=list');
exit;