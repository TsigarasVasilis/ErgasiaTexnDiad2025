<?php
// edit_profile.php
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
$success_message = '';
$user_info = null;

// Λήψη τρεχόντων στοιχείων χρήστη
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, username, email FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_info) {
        header('Location: profile.php');
        exit;
    }
} catch (PDOException $e) {
    $errors['db_error'] = "Σφάλμα κατά την ανάκτηση στοιχείων: " . $e->getMessage();
}

// Διαχείριση φόρμας ενημέρωσης στοιχείων
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_info'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($first_name)) {
        $errors['first_name'] = "Το όνομα είναι υποχρεωτικό.";
    }
    if (empty($last_name)) {
        $errors['last_name'] = "Το επώνυμο είναι υποχρεωτικό.";
    }
    if (empty($username)) {
        $errors['username'] = "Το όνομα χρήστη είναι υποχρεωτικό.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = "Το username πρέπει να περιέχει 3-20 αλφαριθμητικούς χαρακτήρες ή κάτω παύλα (_).";
    } elseif ($username !== $user_info['username']) {
        // Έλεγχος αν το νέο username είναι ήδη σε χρήση
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors['username'] = "Αυτό το όνομα χρήστη χρησιμοποιείται ήδη.";
            }
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τον έλεγχο username: " . $e->getMessage();
        }
    }
    
    if (empty($email)) {
        $errors['email'] = "Το email είναι υποχρεωτικό.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Το email δεν έχει έγκυρη μορφή.";
    } elseif ($email !== $user_info['email']) {
        // Έλεγχος αν το νέο email είναι ήδη σε χρήση
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors['email'] = "Αυτό το email χρησιμοποιείται ήδη.";
            }
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τον έλεγχο email: " . $e->getMessage();
        }
    }
    
    // Αν δεν υπάρχουν σφάλματα, ενημέρωσε τα στοιχεία
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE user_id = ?");
            $stmt->execute([$first_name, $last_name, $username, $email, $_SESSION['user_id']]);
            
            // Ενημέρωση session variables
            $_SESSION['username'] = $username;
            $_SESSION['first_name'] = $first_name;
            
            $success_message = "Τα στοιχεία σας ενημερώθηκαν με επιτυχία!";
            
            // Ενημέρωση του $user_info array
            $user_info['first_name'] = $first_name;
            $user_info['last_name'] = $last_name;
            $user_info['username'] = $username;
            $user_info['email'] = $email;
            
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά την ενημέρωση: " . $e->getMessage();
        }
    }
}

// Διαχείριση αλλαγής κωδικού
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($current_password)) {
        $errors['current_password'] = "Ο τρέχων κωδικός είναι υποχρεωτικός.";
    } else {
        // Έλεγχος τρέχοντος κωδικού
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_password = $stmt->fetch();
            
            if (!$user_password || !password_verify($current_password, $user_password['password_hash'])) {
                $errors['current_password'] = "Ο τρέχων κωδικός είναι λανθασμένος.";
            }
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τον έλεγχο κωδικού: " . $e->getMessage();
        }
    }
    
    if (empty($new_password)) {
        $errors['new_password'] = "Ο νέος κωδικός είναι υποχρεωτικός.";
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = "Ο νέος κωδικός πρέπει να είναι τουλάχιστον 8 χαρακτήρες.";
    }
    
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Η επιβεβαίωση κωδικού είναι υποχρεωτική.";
    } elseif ($new_password !== $confirm_password) {
        $errors['confirm_password'] = "Οι κωδικοί δεν ταιριάζουν.";
    }
    
    // Αν δεν υπάρχουν σφάλματα, άλλαξε τον κωδικό
    if (empty($errors)) {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$password_hash, $_SESSION['user_id']]);
            
            $success_message = "Ο κωδικός σας άλλαξε με επιτυχία!";
            
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά την αλλαγή κωδικού: " . $e->getMessage();
        }
    }
}

// Διαχείριση διαγραφής προφίλ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_profile'])) {
    $confirm_password = $_POST['delete_password'] ?? '';
    
    if (empty($confirm_password)) {
        $errors['delete_password'] = "Πρέπει να εισάγετε τον κωδικό σας για επιβεβαίωση.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_password = $stmt->fetch();
            
            if ($user_password && password_verify($confirm_password, $user_password['password_hash'])) {
                // Διαγραφή χρήστη (CASCADE θα διαγράψει και τις λίστες)
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Καταστροφή session
                session_destroy();
                
                header('Location: index.php?message=profile_deleted_successfully');
                exit;
            } else {
                $errors['delete_password'] = "Λανθασμένος κωδικός.";
            }
        } catch (PDOException $e) {
            $errors['db_error'] = "Σφάλμα κατά τη διαγραφή προφίλ: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επεξεργασία Προφίλ - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .edit-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .section {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--current-accordion-header-text);
            border-bottom: 2px solid var(--current-border-color);
            padding-bottom: 10px;
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
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--current-border-color);
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--nav-link);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
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
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
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
        
        .danger-zone {
            border-color: #dc3545;
            background-color: #fdf2f2;
        }
        
        .danger-zone h3 {
            color: #dc3545;
            border-bottom-color: #dc3545;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .warning-box h4 {
            margin-top: 0;
            color: #856404;
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
        
        .help-text {
            font-size: 0.9em;
            color: var(--text-color);
            opacity: 0.8;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="edit-container">
            <div class="back-link">
                <a href="profile.php">← Επιστροφή στο Προφίλ</a>
            </div>
            
            <h2>⚙️ Επεξεργασία Προφίλ</h2>

            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors['db_error'])): ?>
                <div class="general-error"><?php echo htmlspecialchars($errors['db_error']); ?></div>
            <?php endif; ?>

            <!-- Ενημέρωση Στοιχείων -->
            <div class="section">
                <h3>📝 Βασικά Στοιχεία</h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="first_name">Όνομα *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user_info['first_name'] ?? ''); ?>" required>
                        <?php if (!empty($errors['first_name'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Επώνυμο *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user_info['last_name'] ?? ''); ?>" required>
                        <?php if (!empty($errors['last_name'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user_info['username'] ?? ''); ?>" 
                               pattern="^[a-zA-Z0-9_]{3,20}$" required>
                        <div class="help-text">3-20 αλφαριθμητικοί χαρακτήρες ή κάτω παύλα (_)</div>
                        <?php if (!empty($errors['username'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['username']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="update_info" class="btn btn-primary">💾 Ενημέρωση Στοιχείων</button>
                </form>
            </div>

            <!-- Αλλαγή Κωδικού -->
            <div class="section">
                <h3>🔒 Αλλαγή Κωδικού</h3>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Τρέχων Κωδικός *</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <?php if (!empty($errors['current_password'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['current_password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Νέος Κωδικός *</label>
                        <input type="password" id="new_password" name="new_password" minlength="8" required>
                        <div class="help-text">Τουλάχιστον 8 χαρακτήρες</div>
                        <?php if (!empty($errors['new_password'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['new_password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Επιβεβαίωση Νέου Κωδικού *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary">🔐 Αλλαγή Κωδικού</button>
                </form>
            </div>

            <!-- Διαγραφή Προφίλ -->
            <div class="section danger-zone">
                <h3>⚠️ Επικίνδυνη Ζώνη</h3>
                
                <div class="warning-box">
                    <h4>🚨 Προσοχή!</h4>
                    <p>Η διαγραφή του προφίλ σας είναι <strong>μόνιμη</strong> και δεν μπορεί να αναιρεθεί.</p>
                    <p>Θα διαγραφούν επίσης:</p>
                    <ul>
                        <li>Όλες οι λίστες περιεχομένου που έχετε δημιουργήσει</li>
                        <li>Όλα τα βίντεο που έχετε προσθέσει</li>
                        <li>Οι ακολουθήσεις σας και όσοι σας ακολουθούν</li>
                    </ul>
                </div>
                
                <form method="POST" onsubmit="return confirm('Είστε ΑΠΟΛΥΤΩΣ σίγουροι ότι θέλετε να διαγράψετε το προφίλ σας; Αυτή η ενέργεια δεν μπορεί να αναιρεθεί!');">
                    <div class="form-group">
                        <label for="delete_password">Εισάγετε τον κωδικό σας για επιβεβαίωση *</label>
                        <input type="password" id="delete_password" name="delete_password" required 
                               placeholder="Κωδικός για επιβεβαίωση διαγραφής">
                        <?php if (!empty($errors['delete_password'])): ?>
                            <div class="error-message"><?php echo htmlspecialchars($errors['delete_password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="delete_profile" class="btn btn-danger">🗑️ Διαγραφή Προφίλ</button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="profile.php" class="btn btn-secondary">← Επιστροφή στο Προφίλ</a>
            </div>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Οι κωδικοί δεν ταιριάζουν');
            } else {
                this.setCustomValidity('');
            }
        });

        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const pattern = /^[a-zA-Z0-9_]{3,20}$/;
            
            if (username && !pattern.test(username)) {
                this.setCustomValidity('Το username πρέπει να περιέχει 3-20 αλφαριθμητικούς χαρακτήρες ή κάτω παύλα (_)');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>