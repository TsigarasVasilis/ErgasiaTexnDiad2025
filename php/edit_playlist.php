<?php
// edit_playlist.php
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

$playlist_id = $_GET['playlist_id'] ?? 0;
$playlist = null;
$errors = [];
$success_message = '';

if (!$playlist_id) {
    header('Location: my_playlists.php');
    exit;
}

// Έλεγχος αν η λίστα ανήκει στον τρέχοντα χρήστη
try {
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE playlist_id = ? AND user_id = ?");
    $stmt->execute([$playlist_id, $_SESSION['user_id']]);
    $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$playlist) {
        header('Location: my_playlists.php');
        exit;
    }
} catch (PDOException $e) {
    $errors['db_error'] = "Σφάλμα κατά την ανάκτηση της λίστας: " . $e->getMessage();
}

// Διαχείριση φόρμας ενημέρωσης
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_playlist'])) {
    $playlist_name = trim($_POST['playlist_name'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation
    if (empty($playlist_name)) {
        $errors['playlist_name'] = "Το όνομα της λίστας είναι υποχρεωτικό.";
    } elseif (strlen($playlist_name) > 100) {
        $errors['playlist_name'] = "Το όνομα της λίστας δεν μπορεί να υπερβαίνει τους 100 χαρακτήρες.";
    } elseif ($playlist_name !== $playlist['playlist_name']) {
        // Έλεγχος αν υπάρχει ήδη λίστα με το ίδιο όνομα για τον χρήστη
        try {
            $stmt = $pdo->prepare("SELECT playlist_id FROM playlists WHERE user_id = ? AND playlist_name = ? AND playlist_id != ?");
            $stmt->execute([$_SESSION['user_id'], $playlist_name, $playlist_id]);
            if ($stmt->fetch()) {
                $errors['playlist_name'] = "Έχετε ήδη μια λίστα με αυτό το όνομα.";
            }
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τον έλεγχο του ονόματος: " . $e->getMessage();
        }
    }
    
    // Αν δεν υπάρχουν σφάλματα, ενημέρωσε τη λίστα
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE playlists SET playlist_name = ?, is_public = ? WHERE playlist_id = ? AND user_id = ?");
            $stmt->execute([$playlist_name, $is_public, $playlist_id, $_SESSION['user_id']]);
            
            $success_message = "Η λίστα ενημερώθηκε με επιτυχία!";
            
            // Ενημέρωση του $playlist array
            $playlist['playlist_name'] = $playlist_name;
            $playlist['is_public'] = $is_public;
            
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά την ενημέρωση της λίστας: " . $e->getMessage();
        }
    }
}

// Λήψη στατιστικών λίστας
$playlist_stats = null;
if ($playlist) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_videos,
                MIN(added_date) as first_video_date,
                MAX(added_date) as last_video_date
            FROM playlist_items 
            WHERE playlist_id = ?
        ");
        $stmt->execute([$playlist_id]);
        $playlist_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Σφάλμα στα στατιστικά δεν είναι κρίσιμο
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επεξεργασία Λίστας - <?php echo htmlspecialchars($playlist['playlist_name'] ?? 'Λίστα'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .edit-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .playlist-header {
            background-color: var(--current-accordion-header-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .playlist-header h2 {
            margin: 0 0 15px 0;
            color: var(--current-accordion-header-text);
        }
        
        .playlist-meta {
            color: var(--text-color);
            font-size: 0.9em;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .meta-value {
            opacity: 0.8;
        }
        
        .edit-form {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--text-color);
        }
        
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--current-border-color);
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--nav-link);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        .privacy-explanation {
            background-color: var(--current-accordion-header-bg);
            border-left: 4px solid var(--nav-link);
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .privacy-explanation h4 {
            margin-top: 0;
            color: var(--nav-link);
        }
        
        .help-text {
            font-size: 0.9em;
            color: var(--text-color);
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .general-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
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
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .back-link {
            margin-bottom: 20px;
        }
        
        .back-link a {
            color: var(--nav-link);
            text-decoration: none;
        }
        
        .back-link a:hover {
            color: var(--nav-link-hover);
        }
        
        .quick-actions {
            background-color: var(--current-accordion-header-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .quick-actions h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--current-accordion-header-text);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.9em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.8em;
            color: var(--text-color);
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="edit-container">
            <div class="back-link">
                <a href="view_playlist_items.php?playlist_id=<?php echo $playlist_id; ?>">← Επιστροφή στη Λίστα</a>
            </div>

            <?php if ($playlist): ?>
                <div class="playlist-header">
                    <h2>⚙️ Επεξεργασία Λίστας</h2>
                    <div class="playlist-meta">
                        <div class="meta-item">
                            <div class="meta-label">Τρέχον Όνομα:</div>
                            <div class="meta-value"><?php echo htmlspecialchars($playlist['playlist_name']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Ορατότητα:</div>
                            <div class="meta-value"><?php echo $playlist['is_public'] ? 'Δημόσια' : 'Ιδιωτική'; ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Δημιουργήθηκε:</div>
                            <div class="meta-value"><?php 
                                $date = new DateTime($playlist['creation_date']);
                                echo $date->format('d/m/Y H:i'); 
                            ?></div>
                        </div>
                    </div>
                    
                    <?php if ($playlist_stats): ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $playlist_stats['total_videos']; ?></span>
                                <div class="stat-label">Βίντεο</div>
                            </div>
                            <?php if ($playlist_stats['first_video_date']): ?>
                                <div class="stat-card">
                                    <span class="stat-number"><?php 
                                        $date = new DateTime($playlist_stats['first_video_date']);
                                        echo $date->format('d/m'); 
                                    ?></span>
                                    <div class="stat-label">Πρώτο Βίντεο</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-number"><?php 
                                        $date = new DateTime($playlist_stats['last_video_date']);
                                        echo $date->format('d/m'); 
                                    ?></span>
                                    <div class="stat-label">Τελευταίο Βίντεο</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($success_message): ?>
                    <div class="success-message">✅ <?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors['db_error'])): ?>
                    <div class="general-error"><?php echo htmlspecialchars($errors['db_error']); ?></div>
                <?php endif; ?>

                <!-- Γρήγορες Ενέργειες -->
                <div class="quick-actions">
                    <h3>🚀 Γρήγορες Ενέργειες</h3>
                    <div class="action-buttons">
                        <a href="view_playlist_items.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-secondary btn-small">👁️ Προβολή Λίστας</a>
                        <a href="add_video.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-success btn-small">➕ Προσθήκη Βίντεο</a>
                        <a href="my_playlists.php" class="btn btn-secondary btn-small">📋 Όλες οι Λίστες</a>
                    </div>
                </div>

                <!-- Φόρμα Επεξεργασίας -->
                <div class="edit-form">
                    <h3>📝 Επεξεργασία Στοιχείων</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="playlist_name">Όνομα Λίστας *</label>
                            <input 
                                type="text" 
                                id="playlist_name" 
                                name="playlist_name" 
                                value="<?php echo htmlspecialchars($playlist['playlist_name']); ?>" 
                                maxlength="100"
                                required
                                placeholder="π.χ. Τα Καλύτερα Τραγούδια 2024"
                            >
                            <?php if (!empty($errors['playlist_name'])): ?>
                                <div class="error-message"><?php echo htmlspecialchars($errors['playlist_name']); ?></div>
                            <?php endif; ?>
                            <div class="help-text">Μέγιστος αριθμός χαρακτήρων: 100</div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input 
                                    type="checkbox" 
                                    id="is_public" 
                                    name="is_public" 
                                    value="1"
                                    <?php echo $playlist['is_public'] ? 'checked' : ''; ?>
                                >
                                <label for="is_public">Κάντε τη λίστα δημόσια</label>
                            </div>
                            
                            <div class="privacy-explanation">
                                <h4>🔒 Τι σημαίνει αυτό;</h4>
                                <p><strong>Ιδιωτική λίστα:</strong> Μόνο εσείς μπορείτε να τη δείτε και να τη διαχειριστείτε.</p>
                                <p><strong>Δημόσια λίστα:</strong> Άλλοι χρήστες μπορούν να τη δουν και να την παρακολουθήσουν, αλλά μόνο εσείς μπορείτε να τη διαχειριστείτε.</p>
                                <?php if ($playlist['is_public']): ?>
                                    <p><em>💡 Η λίστα σας είναι ήδη δημόσια και ορατή σε άλλους χρήστες.</em></p>
                                <?php else: ?>
                                    <p><em>🔒 Η λίστα σας είναι ιδιωτική και ορατή μόνο σε εσάς.</em></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_playlist" class="btn btn-primary">💾 Αποθήκευση Αλλαγών</button>
                            <a href="view_playlist_items.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-secondary">❌ Ακύρωση</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Character counter for playlist name
        document.getElementById('playlist_name').addEventListener('input', function() {
            const input = this;
            const maxLength = 100;
            const remaining = maxLength - input.value.length;
            
            // Update help text
            const helpText = input.parentNode.querySelector('.help-text');
            helpText.textContent = `Υπολοίπουν χαρακτήρες: ${remaining}`;
            
            if (remaining < 10) {
                helpText.style.color = '#dc3545';
                helpText.style.fontWeight = 'bold';
            } else if (remaining < 20) {
                helpText.style.color = '#856404';
                helpText.style.fontWeight = 'normal';
            } else {
                helpText.style.color = '';
                helpText.style.fontWeight = 'normal';
            }
        });

        // Privacy toggle explanation
        document.getElementById('is_public').addEventListener('change', function() {
            const explanation = document.querySelector('.privacy-explanation');
            if (this.checked) {
                explanation.style.borderLeftColor = '#28a745';
                explanation.querySelector('h4').style.color = '#28a745';
            } else {
                explanation.style.borderLeftColor = 'var(--nav-link)';
                explanation.querySelector('h4').style.color = 'var(--nav-link)';
            }
        });

        // Auto-focus on playlist name input
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('playlist_name');
            nameInput.focus();
            nameInput.select(); // Select all text for easy editing
        });
    </script>
</body>
</html>