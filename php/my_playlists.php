<?php
// my_playlists.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

require_once 'php/db_connect.php';

$user_playlists = [];
$error_message = '';
$success_message = '';

// Έλεγχος για μηνύματα
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'playlist_created':
            $success_message = "Η λίστα δημιουργήθηκε με επιτυχία!";
            break;
        case 'playlist_deleted':
            $success_message = "Η λίστα διαγράφηκε με επιτυχία!";
            break;
        case 'playlist_updated':
            $success_message = "Η λίστα ενημερώθηκε με επιτυχία!";
            break;
    }
}

// Λήψη λιστών του χρήστη
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.playlist_id, 
            p.playlist_name, 
            p.is_public, 
            p.creation_date,
            COUNT(pi.item_id) as item_count
        FROM playlists p 
        LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id 
        WHERE p.user_id = ? 
        GROUP BY p.playlist_id 
        ORDER BY p.creation_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση των λιστών: " . $e->getMessage();
}

// Διαχείριση διαγραφής λίστας
if (isset($_POST['delete_playlist']) && isset($_POST['playlist_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM playlists WHERE playlist_id = ? AND user_id = ?");
        $stmt->execute([$_POST['playlist_id'], $_SESSION['user_id']]);
        
        header('Location: my_playlists.php?message=playlist_deleted');
        exit;
    } catch (PDOException $e) {
        $error_message = "Σφάλμα κατά τη διαγραφή της λίστας: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Οι Λίστες μου - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .playlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .playlist-card {
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 15px;
            background-color: var(--current-accordion-content-bg);
            transition: box-shadow 0.3s ease;
        }
        
        .playlist-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .playlist-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .playlist-title {
            font-size: 1.2em;
            font-weight: bold;
            margin: 0;
            flex-grow: 1;
        }
        
        .playlist-privacy {
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: 10px;
        }
        
        .privacy-public {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .privacy-private {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .playlist-info {
            color: var(--text-color);
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .playlist-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: var(--button-bg);
            color: var(--button-text);
        }
        
        .btn-primary:hover {
            background-color: var(--button-hover-bg);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .create-playlist-section {
            text-align: center;
            padding: 20px;
            margin-bottom: 30px;
            border: 2px dashed var(--current-border-color);
            border-radius: 8px;
            background-color: var(--current-accordion-header-bg);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-color);
        }
        
        .success-message {
            color: green;
            background-color: #e6ffe6;
            border: 1px solid green;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: red;
            background-color: #ffe6e6;
            border: 1px solid red;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .delete-form {
            display: inline;
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <h2>Οι Λίστες μου</h2>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="create-playlist-section">
            <h3>Δημιουργία Νέας Λίστας</h3>
            <p>Δημιουργήστε μια νέα λίστα για να οργανώσετε τα αγαπημένα σας βίντεο από το YouTube.</p>
            <a href="create_playlist.php" class="btn btn-primary">+ Δημιουργία Νέας Λίστας</a>
        </div>

        <?php if (empty($user_playlists)): ?>
            <div class="empty-state">
                <h3>Δεν έχετε δημιουργήσει ακόμα λίστες</h3>
                <p>Κάντε κλικ στο κουμπί παραπάνω για να δημιουργήσετε την πρώτη σας λίστα!</p>
            </div>
        <?php else: ?>
            <div class="playlist-grid">
                <?php foreach ($user_playlists as $playlist): ?>
                    <div class="playlist-card">
                        <div class="playlist-header">
                            <h3 class="playlist-title"><?php echo htmlspecialchars($playlist['playlist_name']); ?></h3>
                            <span class="playlist-privacy <?php echo $playlist['is_public'] ? 'privacy-public' : 'privacy-private'; ?>">
                                <?php echo $playlist['is_public'] ? 'Δημόσια' : 'Ιδιωτική'; ?>
                            </span>
                        </div>
                        
                        <div class="playlist-info">
                            <p><strong>Περιεχόμενα:</strong> <?php echo $playlist['item_count']; ?> βίντεο</p>
                            <p><strong>Δημιουργήθηκε:</strong> <?php 
                                $date = new DateTime($playlist['creation_date']);
                                echo $date->format('d/m/Y H:i'); 
                            ?></p>
                        </div>
                        
                        <div class="playlist-actions">
                            <a href="view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>" class="btn btn-primary">Προβολή</a>
                            <a href="add_video.php?playlist_id=<?php echo $playlist['playlist_id']; ?>" class="btn btn-secondary">Προσθήκη Βίντεο</a>
                            <a href="edit_playlist.php?playlist_id=<?php echo $playlist['playlist_id']; ?>" class="btn btn-secondary">Επεξεργασία</a>
                            
                            <form class="delete-form" method="POST" onsubmit="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή τη λίστα;');">
                                <input type="hidden" name="playlist_id" value="<?php echo $playlist['playlist_id']; ?>">
                                <button type="submit" name="delete_playlist" class="btn btn-danger">Διαγραφή</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
</body>
</html>