<?php
// logout.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Καθαρισμός όλων των session μεταβλητών
$_SESSION = array();

// 2. Διαγραφή του session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Καταστροφή του session
session_destroy();

// 4. Ανακατεύθυνση στην αρχική σελίδα ή στη σελίδα σύνδεσης
header("Location: index.php?logout=success"); // Μπορείς να εμφανίσεις ένα μήνυμα στην index.php
exit;
?>