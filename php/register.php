<?php
// register.php
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}
require_once 'php/db_connect.php'; 

$errors = []; 
$success_message = '';

$first_name = '';
$last_name = '';
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($first_name)) { $errors['first_name'] = "Το όνομα είναι υποχρεωτικό."; }
    if (empty($last_name)) { $errors['last_name'] = "Το επώνυμο είναι υποχρεωτικό."; }
    if (empty($username)) {
        $errors['username'] = "Το όνομα χρήστη (username) είναι υποχρεωτικό.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = "Το username πρέπει να περιέχει 3-20 αλφαριθμητικούς χαρακτήρες ή κάτω παύλα (_).";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) { $errors['username'] = "Αυτό το όνομα χρήστη υπάρχει ήδη. Παρακαλώ επιλέξτε άλλο."; }
    }
    if (empty($email)) {
        $errors['email'] = "Το email είναι υποχρεωτικό.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Το email δεν έχει έγκυρη μορφή.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) { $errors['email'] = "Αυτό το email χρησιμοποιείται ήδη. Παρακαλώ επιλέξτε άλλο ή συνδεθείτε."; }
    }
    if (empty($password)) {
        $errors['password'] = "Ο κωδικός πρόσβασης είναι υποχρεωτικός.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Ο κωδικός πρόσβασης πρέπει να είναι τουλάχιστον 8 χαρακτήρες.";
    }
    if (empty($password_confirm)) {
        $errors['password_confirm'] = "Η επιβεβαίωση κωδικού είναι υποχρεωτική.";
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = "Οι κωδικοί πρόσβασης δεν ταιριάζουν.";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $username, $email, $password_hash]);
            $success_message = "Η εγγραφή σας ολοκληρώθηκε με επιτυχία! Μπορείτε τώρα να <a href='login.php'>συνδεθείτε</a>.";
            $_POST = []; 
            $first_name = $last_name = $username = $email = '';
        } catch (PDOException $e) {
            $errors['db_error'] = "Προέκυψε ένα σφάλμα κατά την εγγραφή. Παρακαλώ προσπαθήστε ξανά.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εγγραφή Χρήστη - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; 
        }
        .form-group input.error-input { border-color: red; }
        .error-message { color: red; font-size: 0.9em; margin-top: 5px; }
        .success-message { color: green; background-color: #e6ffe6; border: 1px solid green; padding: 10px; margin-bottom:15px; border-radius: 4px;}
        .submit-button { padding: 10px 20px; font-size: 1em; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>
    <main>
        <h2>Εγγραφή Νέου Χρήστη</h2>
        <?php if ($success_message): ?><p class="success-message"><?php echo $success_message; ?></p><?php endif; ?>
        <?php if (!empty($errors['db_error'])): ?><p class="error-message" style="background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px;"><?php echo htmlspecialchars($errors['db_error']); ?></p><?php endif; ?>
        <form id="registrationForm" action="register.php" method="POST" novalidate>
            <div class="form-group">
                <label for="first_name">Όνομα:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                <?php if (!empty($errors['first_name'])): ?><div class="error-message" id="first_name_error"><?php echo htmlspecialchars($errors['first_name']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="last_name">Επώνυμο:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                <?php if (!empty($errors['last_name'])): ?><div class="error-message" id="last_name_error"><?php echo htmlspecialchars($errors['last_name']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required pattern="^[a-zA-Z0-9_]{3,20}$">
                <small>3-20 αλφαριθμητικοί χαρακτήρες ή κάτω παύλα (_).</small>
                <?php if (!empty($errors['username'])): ?><div class="error-message" id="username_error"><?php echo htmlspecialchars($errors['username']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <?php if (!empty($errors['email'])): ?><div class="error-message" id="email_error"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Κωδικός Πρόσβασης:</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small>Τουλάχιστον 8 χαρακτήρες.</small>
                <?php if (!empty($errors['password'])): ?><div class="error-message" id="password_error"><?php echo htmlspecialchars($errors['password']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password_confirm">Επιβεβαίωση Κωδικού:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
                <?php if (!empty($errors['password_confirm'])): ?><div class="error-message" id="password_confirm_error"><?php echo htmlspecialchars($errors['password_confirm']); ?></div><?php endif; ?>
            </div>
            <button type="submit" class="submit-button">Εγγραφή</button>
        </form>
    </main>
    <?php include 'php/partials/footer.php'; ?>
    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        const form = document.getElementById('registrationForm');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmInput = document.getElementById('password_confirm');
        function showError(inputElement, message) { /* ... (όπως ορίστηκε πριν) ... */ }
        function clearError(inputElement) { /* ... (όπως ορίστηκε πριν) ... */ }
        // ... (το υπόλοιπο JavaScript για client-side validation ως είχε) ...
    </script>
</body>
</html>