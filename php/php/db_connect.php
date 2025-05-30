<?php
// php/db_connect.php - Complete Docker version

// Database connection parameters for Docker environment
$db_host = 'db'; // Docker container name
$db_port = '3306';                      // MySQL port
$db_name = 'di_internet_technologies_project'; // Database name from docker-compose.yml
$db_user = 'webuser';                   // Database user from docker-compose.yml
$db_pass = 'webpass';                   // Database password from docker-compose.yml
$charset = 'utf8mb4';

// Data Source Name with explicit port
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=$charset";


// PDO connection options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    PDO::ATTR_TIMEOUT            => 10,                     // Connection timeout (10 seconds)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE {$charset}_unicode_ci"
];

try {
    // Create PDO connection
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Test the connection with a simple query
    $pdo->query("SELECT 1");
    
    // Optional: Check if required tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        error_log("Database Warning: 'users' table does not exist. Database may not be fully initialized.");
    }
    
} catch (\PDOException $e) {
    // Enhanced error handling
    $error_message = "Σφάλμα σύνδεσης με τη βάση δεδομένων.";
    
    // Log the actual error for debugging
    error_log("Database Connection Error: " . $e->getMessage());
    error_log("Connection details: Host=$db_host, Port=$db_port, Database=$db_name, User=$db_user");
    
    // Provide user-friendly error messages based on error type
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        $error_message = "Η βάση δεδομένων '$db_name' δεν υπάρχει.";
    } elseif (strpos($e->getMessage(), "Access denied") !== false) {
        $error_message = "Δεν είναι δυνατή η σύνδεση με τη βάση δεδομένων. Λάθος στοιχεία πρόσβασης.";
    } elseif (strpos($e->getMessage(), "Connection refused") !== false || 
              strpos($e->getMessage(), "No such file or directory") !== false) {
        $error_message = "Η βάση δεδομένων δεν είναι ακόμα έτοιμη. Περιμένετε λίγο και ανανεώστε τη σελίδα.";
    } elseif (strpos($e->getMessage(), "timed out") !== false) {
        $error_message = "Η σύνδεση με τη βάση δεδομένων έληξε. Δοκιμάστε ξανά.";
    }
    
    // In development/debugging, you might want to show more details
    // Uncomment the line below for debugging (remove in production):
    // $error_message .= "<br><small>Debug: " . htmlspecialchars($e->getMessage()) . "</small>";
    
    // Die with user-friendly error message
    die($error_message);
} catch (\Exception $e) {
    // Handle any other exceptions
    error_log("Unexpected database error: " . $e->getMessage());
    die("Προέκυψε ένα απροσδόκητο σφάλμα κατά τη σύνδεση με τη βάση δεδομένων.");
}

// The $pdo object is now available for use in files that include this one
// Example usage in other files:
// require_once 'php/db_connect.php';
// $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
// $stmt->execute([$user_id]);
// $user = $stmt->fetch();
?>