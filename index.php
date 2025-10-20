<?php
// Activer l'affichage des erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';
require_once 'config/database.php';

// 🧩 Ajout de Parsedown
require_once __DIR__ . '/lib/Parsedown.php';
require_once __DIR__ . '/lib/ParsedownTargetBlank.php';

$Parsedown = new ParsedownTargetBlank();
$Parsedown->setSafeMode(true); // protège contre le HTML non désiré

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Récupérer les paramètres
$action = $_GET['action'] ?? 'dashboard';
$id = $_GET['id'] ?? null;
$rubrique_slug = $_GET['rubrique'] ?? null;

// Route pour la page paramètres
$page = $_GET['page'] ?? null;

if ($page === 'medias') {
    require_once __DIR__ . '/medias.php';
    exit;
}

// Fonction pour récupérer les groupes de tags
function getGroupesTags($pdo) {
    $stmt = $pdo->query("
        SELECT gt.id AS groupe_id, gt.nom AS groupe_nom, t.id AS tag_id, t.nom AS tag_nom
        FROM groupe_tags gt
        LEFT JOIN tags t ON t.groupe_id = gt.id
        ORDER BY gt.ordre, t.ordre
    ");
    
    $groupes_tags = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $groupe_nom = $row['groupe_nom'];
        if (!isset($groupes_tags[$groupe_nom])) {
            $groupes_tags[$groupe_nom] = [
                'id' => $row['groupe_id'],
                'nom' => $groupe_nom,
                'tags' => []
            ];
        }
        if ($row['tag_id']) {
            $groupes_tags[$groupe_nom]['tags'][] = [
                'id' => $row['tag_id'],
                'nom' => $row['tag_nom']
            ];
        }
    }
    
    return array_values($groupes_tags);
}

// Fonction pour récupérer les rubriques
function getRubriques($pdo) {
    $stmt = $pdo->query("SELECT id, nom, slug FROM rubriques WHERE parent_id IS NULL ORDER BY ordre");
    return $stmt->fetchAll();
}

// Route pour afficher les fiches d'une rubrique
if ($rubrique_slug) {
    $stmt = $pdo->prepare("SELECT * FROM rubriques WHERE slug = ?");
    $stmt->execute([$rubrique_slug]);
    $rubrique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rubrique) {
        http_response_code(404);
        echo "Rubrique non trouvée.";
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT f.*, r.nom AS rubrique_nom
        FROM fiches f
        LEFT JOIN rubriques r ON f.rubrique_id = r.id
        WHERE f.rubrique_id = ?
        ORDER BY f.updated_at DESC
    ");
    $stmt->execute([$rubrique['id']]);
    $fiches = $stmt->fetchAll();

    // Récupérer les tags et convertir le markdown
    foreach ($fiches as &$fiche) {
        $tagStmt = $pdo->prepare("
            SELECT t.nom
            FROM fiche_tag ft
            JOIN tags t ON ft.tag_id = t.id
            WHERE ft.fiche_id = ?
        ");
        $tagStmt->execute([$fiche['id']]);
        $fiche['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        // 🧩 Conversion Markdown → HTML
        $fiche['description_html'] = $Parsedown->text($fiche['description'] ?? '');
    }
    unset($fiche);
    
    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader, ['debug' => true, 'cache' => false]);
    
    echo $twig->render('fiches/list.html.twig', [
        'rubrique' => $rubrique,
        'fiches' => $fiches,
        'rubriques' => getRubriques($pdo),
        'active_rubrique' => $rubrique['slug']
    ]);
    exit;
}

// Route pour créer une nouvelle fiche
if ($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader);

    // Récupérer l'ID de rubrique passé en paramètre (si présent)
    $preselect_rubrique_id = $_GET['rubrique_id'] ?? null;

    echo $twig->render('fiches/form.html.twig', [
        'fiche' => null,
        'groupes_tags' => getGroupesTags($pdo),
        'rubriques' => getRubriques($pdo),
        'preselect_rubrique_id' => $preselect_rubrique_id
    ]);
    exit;
}

// Route pour éditer une fiche
if ($action === 'edit' && $id) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM fiches WHERE id = ?");
        $stmt->execute([(int)$id]);
        $fiche = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fiche) {
            http_response_code(404);
            echo "Fiche non trouvée.";
            exit;
        }

        $tagStmt = $pdo->prepare("SELECT t.nom FROM fiche_tag ft JOIN tags t ON ft.tag_id = t.id WHERE ft.fiche_id = ?");
        $tagStmt->execute([$fiche['id']]);
        $fiche['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        $loader = new FilesystemLoader(__DIR__ . '/templates');
        $twig = new Environment($loader);

        echo $twig->render('fiches/form.html.twig', [
            'fiche' => $fiche,
            'groupes_tags' => getGroupesTags($pdo),
            'rubriques' => getRubriques($pdo)
        ]);
        exit;
    }

    // Traitement du formulaire POST
    $titre = $_POST['titre'] ?? '';
    $rubrique_id = $_POST['rubrique_id'] ?? null;
    $etat = $_POST['etat'] ?? '';
    $description = $_POST['description'] ?? '';
    $tags = $_POST['tags'] ?? '';

    if ($titre && $etat) {
        $stmt = $pdo->prepare("
            UPDATE fiches
            SET titre = ?, rubrique_id = ?, etat = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$titre, $rubrique_id ?: null, $etat, $description, (int)$id]);

        $pdo->prepare("DELETE FROM fiche_tag WHERE fiche_id = ?")->execute([(int)$id]);
        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        foreach ($tagList as $tag) {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE nom = ?");
            $stmt->execute([$tag]);
            $tagId = $stmt->fetchColumn();
            if ($tagId) {
                $pdo->prepare("INSERT INTO fiche_tag (fiche_id, tag_id) VALUES (?, ?)")->execute([(int)$id, $tagId]);
            }
        }

        header('Location: ./?action=view&id=' . $id);
        exit;
    }
}

// Route pour sauvegarder une nouvelle fiche
if ($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'] ?? '';
    $rubrique_id = $_POST['rubrique_id'] ?? null;
    $etat = $_POST['etat'] ?? '';
    $description = $_POST['description'] ?? '';
    $tags = $_POST['tags'] ?? '';

    if ($titre && $etat) {
        $stmt = $pdo->prepare("
            INSERT INTO fiches (titre, rubrique_id, etat, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$titre, $rubrique_id ?: null, $etat, $description]);
        $ficheId = $pdo->lastInsertId();

        $tagList = array_filter(array_map('trim', explode(',', $tags)));
        foreach ($tagList as $tag) {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE nom = ?");
            $stmt->execute([$tag]);
            $tagId = $stmt->fetchColumn();
            if ($tagId) {
                $pdo->prepare("INSERT INTO fiche_tag (fiche_id, tag_id) VALUES (?, ?)")->execute([$ficheId, $tagId]);
            }
        }

        header('Location: ./?action=view&id=' . $ficheId);
        exit;
    }
}

// Route pour supprimer une fiche
if ($action === 'delete' && $id) {
    // Vérifier que la fiche existe
    $stmt = $pdo->prepare("SELECT * FROM fiches WHERE id = ?");
    $stmt->execute([(int)$id]);
    $fiche = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fiche) {
        http_response_code(404);
        echo "Fiche non trouvée.";
        exit;
    }
    
    // Supprimer les associations de tags
    $pdo->prepare("DELETE FROM fiche_tag WHERE fiche_id = ?")->execute([(int)$id]);
    
    // Supprimer la fiche
    $pdo->prepare("DELETE FROM fiches WHERE id = ?")->execute([(int)$id]);
    
    // Rediriger vers le dashboard avec un message de succès
    header('Location: ./?success=deleted');
    exit;
}

// 🧩 Route pour afficher une fiche
if ($action === 'view' && $id) {
    $stmt = $pdo->prepare("
        SELECT f.*, r.nom AS rubrique_nom
        FROM fiches f
        LEFT JOIN rubriques r ON f.rubrique_id = r.id
        WHERE f.id = ?
    ");
    $stmt->execute([(int)$id]);
    $fiche = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fiche) {
        http_response_code(404);
        echo "Fiche non trouvée.";
        exit;
    }

    // 🧩 Conversion Markdown → HTML
    $fiche['description_html'] = $Parsedown->text($fiche['description'] ?? '');
    $tagStmt = $pdo->prepare("
        SELECT t.nom
        FROM fiche_tag ft
        JOIN tags t ON ft.tag_id = t.id
        WHERE ft.fiche_id = ?
    ");
    $tagStmt->execute([$fiche['id']]);
    $fiche['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader);
    echo $twig->render('fiches/view.html.twig', [
        'fiche' => $fiche,
        'rubriques' => getRubriques($pdo)
    ]);
    exit;
}

// Route pour la recherche
if ($action === 'recherche') {
    $search = $_GET['q'] ?? '';
    
    if (empty(trim($search))) {
        header('Location: ./');
        exit;
    }
    
    $searchTerm = '%' . $search . '%';
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT f.*, r.nom AS rubrique_nom
        FROM fiches f
        LEFT JOIN rubriques r ON f.rubrique_id = r.id
        LEFT JOIN fiche_tag ft ON f.id = ft.fiche_id
        LEFT JOIN tags t ON ft.tag_id = t.id
        WHERE f.titre LIKE ? 
           OR f.description LIKE ?
           OR t.nom LIKE ?
        ORDER BY f.updated_at DESC
        LIMIT 50
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $fiches = $stmt->fetchAll();

    foreach ($fiches as &$fiche) {
        $tagStmt = $pdo->prepare("
            SELECT t.nom
            FROM fiche_tag ft
            JOIN tags t ON ft.tag_id = t.id
            WHERE ft.fiche_id = ?
        ");
        $tagStmt->execute([$fiche['id']]);
        $fiche['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        // 🧩 Conversion Markdown → HTML
        $fiche['description_html'] = $Parsedown->text($fiche['description'] ?? '');
    }
    unset($fiche);

    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader);

    echo $twig->render('search_results.html.twig', [
        'fiches' => $fiches,
        'search' => $search,
        'rubriques' => getRubriques($pdo)
    ]);
    exit;
}

// Dashboard par défaut
$stmt = $pdo->query("SELECT COUNT(*) FROM fiches");
$total = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM fiches WHERE etat = 'À faire'");
$a_faire = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM fiches WHERE etat = 'En cours'");
$en_cours = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM fiches WHERE etat = 'termine'");
$termine = $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT f.*, r.nom AS rubrique_nom
    FROM fiches f
    LEFT JOIN rubriques r ON f.rubrique_id = r.id
    ORDER BY f.updated_at DESC
    LIMIT 10
");
$recent_fiches = $stmt->fetchAll();

foreach ($recent_fiches as &$fiche) {
    $tagStmt = $pdo->prepare("
        SELECT t.nom
        FROM fiche_tag ft
        JOIN tags t ON ft.tag_id = t.id
        WHERE ft.fiche_id = ?
    ");
    $tagStmt->execute([$fiche['id']]);
    $fiche['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

    // 🧩 Conversion Markdown → HTML
    $fiche['description_html'] = $Parsedown->text($fiche['description'] ?? '');
}
unset($fiche);

$loader = new FilesystemLoader(__DIR__ . '/templates');
$twig = new Environment($loader);

echo $twig->render('dashboard.html.twig', [
    'active_route' => 'dashboard',
    'search' => '',
    'stats' => [
        'total' => $total,
        'a_faire' => $a_faire,
        'en_cours' => $en_cours,
        'termine' => $termine
    ],
    'recent_fiches' => $recent_fiches,
    'rubriques' => getRubriques($pdo)
]);
