<?php
// create_playlist.php
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

$errors = [];
$playlist_name = '';
$is_public = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $playlist_name = trim($_POST['playlist_name'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation
    if (empty($playlist_name)) {
        $errors['playlist_name'] = "Το όνομα της λίστας είναι υποχρεωτικό.";
    } elseif (strlen($playlist_name) > 100) {
        $errors['playlist_name'] = "Το όνομα της λίστας δεν μπορεί να υπερβαίνει τους 100 χαρακτήρες.";
    } else {
        // Έλεγχος αν υπάρχει ήδη λίστα με το ίδιο όνομα για τον χρήστη
        try {
            $stmt = $pdo->prepare("SELECT playlist_id FROM playlists WHERE user_id = ? AND playlist_name = ?");
            $stmt->execute([$_SESSION['user_id'], $playlist_name]);
            if ($stmt->fetch()) {
                $errors['playlist_name'] = "Έχετε ήδη μια λίστα με αυτό το όνομα.";
            }
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τον έλεγχο του ονόματος: " . $e->getMessage();
        }
    }
    
    // Αν δεν υπάρχουν σφάλματα, δημιούργησε τη λίστα
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO playlists (user_id, playlist_name, is_public) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $playlist_name, $is_public]);
            
            header('Location: my_playlists.php?message=playlist_created');
            exit;
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τη δημιουργία της λίστας: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Δημιουργία Νέας Λίστας - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
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
        
        .general-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background-color: var(--current-accordion-header-bg);
            border: 1px solid var(--current-border-color);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: var(--current-accordion-header-text);
        }
        
        .privacy-explanation {
            background-color: var(--current-accordion-content-bg);
            border-left: 4px solid var(--nav-link);
            padding: 15px;
            margin: 15px 0;
        }
        
        .privacy-explanation h4 {
            margin-top: 0;
            color: var(--nav-link);
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="form-container">
            <h2>Δημιουργία Νέας Λίστας</h2>

            <div class="info-box">
                <h3>📝 Οδηγίες</h3>
                <p>Δημιουργήστε μια νέα λίστα για να οργανώσετε τα αγαπημένα σας βίντεο από το YouTube. Μπορείτε να επιλέξετε αν η λίστα θα είναι ιδιωτική ή δημόσια.</p>
            </div>

            <?php if (!empty($errors['db_error'])): ?>
                <div class="general-error">
                    <?php echo htmlspecialchars($errors['db_error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="playlist_name">Όνομα Λίστας *</label>
                    <input 
                        type="text" 
                        id="playlist_name" 
                        name="playlist_name" 
                        value="<?php echo htmlspecialchars($playlist_name); ?>" 
                        maxlength="100"
                        required
                        placeholder="π.χ. Τα Αγαπημένα μου Τραγούδια"
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
                            <?php echo $is_public ? 'checked' : ''; ?>
                        >
                        <label for="is_public">Κάντε τη λίστα δημόσια</label>
                    </div>
                    
                    <div class="privacy-explanation">
                        <h4>🔒 Τι σημαίνει αυτό;</h4>
                        <p><strong>Ιδιωτική λίστα:</strong> Μόνο εσείς μπορείτε να τη δείτε και να τη διαχειριστείτε.</p>
                        <p><strong>Δημόσια λίστα:</strong> Άλλοι χρήστες μπορούν να τη δουν και να την παρακολουθήσουν, αλλά μόνο εσείς μπορείτε να τη διαχειριστείτε.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Δημιουργία Λίστας</button>
                    <a href="my_playlists.php" class="btn btn-secondary">Ακύρωση</a>
                </div>
            </form>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Client-side validation
        document.getElementById('playlist_name').addEventListener('input', function() {
            const input = this;
            const maxLength = 100;
            const remaining = maxLength - input.value.length;
            
            // Update help text
            const helpText = input.parentNode.querySelector('.help-text');
            helpText.textContent = `Υπολοίπουν χαρακτήρες: ${remaining}`;
            
            if (remaining < 10) {
                helpText.style.color = '#dc3545';
            } else {
                helpText.style.color = '';
            }
        });
    </script>
</body>
</html>