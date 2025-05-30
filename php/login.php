<?php
// login.php - ΚΑΘΑΡΗ ΕΚΔΟΣΗ
session_start();

// Αν είναι ήδη συνδεδεμένος, πήγαινε στο profile
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php'); 
    exit;
}

$errors = [];
$login_identifier = ''; 
$debug_info = []; // Για να δούμε τι συμβαίνει

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $debug_info[] = "🔍 Έλαβα POST request";
    
    $login_identifier = trim($_POST['login_identifier'] ?? ''); 
    $password = $_POST['password'] ?? '';
    
    $debug_info[] = "📝 Username/Email: " . htmlspecialchars($login_identifier);
    $debug_info[] = "🔑 Password Length: " . strlen($password);
    
    // Βασικοί έλεγχοι
    if (empty($login_identifier)) { 
        $errors['login_identifier'] = "Το όνομα χρήστη ή το email είναι υποχρεωτικό."; 
    }
    if (empty($password)) { 
        $errors['password'] = "Ο κωδικός πρόσβασης είναι υποχρεωτικός."; 
    }
    
    if (empty($errors)) {
        $debug_info[] = "✅ Πέρασα τους βασικούς ελέγχους";
        
        // Σύνδεση με βάση
        try {
            $debug_info[] = "🔌 Προσπαθώ σύνδεση με βάση...";
            
            $pdo = new PDO("mysql:host=db;dbname=di_internet_technologies_project;charset=utf8mb4", "webuser", "webpass");

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $debug_info[] = "✅ Συνδέθηκα με τη βάση";
            
            // Αναζήτηση χρήστη
            $debug_info[] = "🔍 Ψάχνω για χρήστη...";
            
            $sql = "SELECT user_id, username, password_hash, first_name FROM users WHERE username = ? OR email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$login_identifier, $login_identifier]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $debug_info[] = "👤 Βρήκα χρήστη: " . htmlspecialchars($user['username']);
                $debug_info[] = "🔐 Hash στη βάση: " . substr($user['password_hash'], 0, 20) . "...";
                
                // Έλεγχος κωδικού
                if (password_verify($password, $user['password_hash'])) {
                    $debug_info[] = "✅ Ο κωδικός είναι σωστός!";
                    
                    // Δημιουργία session
                    session_regenerate_id(true); 
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['first_name'] = $user['first_name'];
                    
                    $debug_info[] = "✅ Session δημιουργήθηκε";
                    
                    // Ανακατεύθυνση
                    if (isset($_SESSION['redirect_url'])) {
                        $redirect_url = $_SESSION['redirect_url'];
                        unset($_SESSION['redirect_url']); 
                        header("Location: " . $redirect_url);
                    } else {
                        header("Location: profile.php"); 
                    }
                    exit;
                } else {
                    $debug_info[] = "❌ Λάθος κωδικός";
                    $errors['login_failed'] = "Λανθασμένο όνομα χρήστη/email ή κωδικός πρόσβασης.";
                }
            } else {
                $debug_info[] = "❌ Δεν βρήκα χρήστη με αυτό το username/email";
                $errors['login_failed'] = "Λανθασμένο όνομα χρήστη/email ή κωδικός πρόσβασης.";
            }
            
        } catch (PDOException $e) {
            $debug_info[] = "💥 Σφάλμα PDO: " . htmlspecialchars($e->getMessage());
            $errors['db_error'] = "Προέκυψε σφάλμα κατά τη σύνδεση: " . $e->getMessage();
        } catch (Exception $e) {
            $debug_info[] = "💥 Άλλο σφάλμα: " . htmlspecialchars($e->getMessage());
            $errors['db_error'] = "Προέκυψε σφάλμα: " . $e->getMessage();
        }
    } else {
        $debug_info[] = "❌ Αποτυχία στους βασικούς ελέγχους";
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Σύνδεση Χρήστη - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="password"] {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .error-message { color: red; font-size: 0.9em; margin-top: 5px; }
        .submit-button { padding: 10px 20px; font-size: 1em; cursor: pointer; }
        .general-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .debug-box { background-color: #e7f3ff; border: 1px solid #b6d7ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .debug-box h4 { margin-top: 0; color: #0066cc; }
        .debug-info { font-family: monospace; font-size: 0.9em; }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>
    <main>
        <h2>Σύνδεση Χρήστη</h2>
        
        <!-- Debug Πληροφορίες -->
        <?php if (!empty($debug_info)): ?>
        <div class="debug-box">
            <h4>🔍 Debug Πληροφορίες (θα αφαιρεθούν αργότερα):</h4>
            <div class="debug-info">
                <?php foreach ($debug_info as $info): ?>
                    <?php echo $info; ?><br>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Μηνύματα Σφαλμάτων -->
        <?php if (!empty($errors['login_failed'])): ?>
            <p class="general-error"><?php echo htmlspecialchars($errors['login_failed']); ?></p>
        <?php endif; ?>
        <?php if (!empty($errors['db_error'])): ?>
            <p class="general-error"><?php echo htmlspecialchars($errors['db_error']); ?></p>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="login_identifier">Username ή Email:</label>
                <input type="text" id="login_identifier" name="login_identifier" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
                <?php if (!empty($errors['login_identifier'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['login_identifier']); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="password">Κωδικός Πρόσβασης:</label>
                <input type="password" id="password" name="password" required>
                <?php if (!empty($errors['password'])): ?>
                    <div class="error-message"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="submit-button">Σύνδεση</button>
        </form>
        
        <p style="margin-top: 20px;">Δεν έχετε λογαριασμό; <a href="register.php">Εγγραφείτε εδώ</a>.</p>
        
        <hr>
        <p><strong>Για δοκιμή:</strong> <a href="debug_login.php">Έλεγχος Βάσης</a></p>
    </main>
    
    <?php include 'php/partials/footer.php'; ?>
    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
</body>
</html>