<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?message=login_required_to_follow');
    exit;
}

require_once 'php/db_connect.php';

$target_user_id = $_GET['user_id'] ?? 0;
$action = $_GET['action'] ?? 'follow';
$redirect_url = $_GET['redirect'] ?? 'search_content.php';
$ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Validation
if (!$target_user_id || !is_numeric($target_user_id)) {
    $error = 'Μή έγκυρος χρήστης.';
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
    header('Location: ' . $redirect_url . '?error=' . urlencode($error));
    exit;
}

if ($target_user_id == $_SESSION['user_id']) {
    $error = 'Δεν μπορείτε να ακολουθήσετε τον εαυτό σας.';
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
    header('Location: ' . $redirect_url . '?error=' . urlencode($error));
    exit;
}

try {
    // Έλεγχος αν ο χρήστης προς ακολούθηση υπάρχει
    $stmt = $pdo->prepare("SELECT user_id, username, first_name, last_name FROM users WHERE user_id = ?");
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$target_user) {
        $error = 'Ο χρήστης δεν βρέθηκε.';
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
        header('Location: ' . $redirect_url . '?error=' . urlencode($error));
        exit;
    }
    
    // Έλεγχος τρέχουσας κατάστασης ακολούθησης
    $stmt = $pdo->prepare("SELECT follow_id FROM follows WHERE follower_user_id = ? AND followed_user_id = ?");
    $stmt->execute([$_SESSION['user_id'], $target_user_id]);
    $existing_follow = $stmt->fetch();
    
    $message = '';
    $new_status = '';
    $followers_count = 0;
    
    if ($action === 'follow') {
        if ($existing_follow) {
            $message = "Ακολουθείτε ήδη τον χρήστη " . htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name']) . ".";
            $new_status = 'following';
        } else {
            // Προσθήκη ακολούθησης
            $stmt = $pdo->prepare("INSERT INTO follows (follower_user_id, followed_user_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $target_user_id]);
            $message = "Ακολουθείτε τώρα τον χρήστη " . htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name']) . "!";
            $new_status = 'following';
        }
    } elseif ($action === 'unfollow') {
        if (!$existing_follow) {
            $message = "Δεν ακολουθείτε αυτόν τον χρήστη.";
            $new_status = 'not_following';
        } else {
            // Αφαίρεση ακολούθησης
            $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_user_id = ? AND followed_user_id = ?");
            $stmt->execute([$_SESSION['user_id'], $target_user_id]);
            $message = "Σταματήσατε να ακολουθείτε τον χρήστη " . htmlspecialchars($target_user['first_name'] . ' ' . $target_user['last_name']) . ".";
            $new_status = 'not_following';
        }
    } else {
        $error = "Μή έγκυρη ενέργεια.";
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
        header('Location: ' . $redirect_url . '?error=' . urlencode($error));
        exit;
    }
    
    // Λήψη ενημερωμένου αριθμού followers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_user_id = ?");
    $stmt->execute([$target_user_id]);
    $followers_count = $stmt->fetch()['count'];
    
    // Response για AJAX
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_status' => $new_status,
            'followers_count' => $followers_count,
            'user_name' => $target_user['first_name'] . ' ' . $target_user['last_name']
        ]);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Follow/Unfollow Error: " . $e->getMessage());
    $error = "Προέκυψε σφάλμα κατά την επεξεργασία του αιτήματος.";
    
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
}

// Ανακατεύθυνση πίσω με μήνυμα (για non-AJAX requests)
$separator = strpos($redirect_url, '?') !== false ? '&' : '?';
if (isset($error)) {
    header('Location: ' . $redirect_url . $separator . 'error=' . urlencode($error));
} else {
    header('Location: ' . $redirect_url . $separator . 'message=' . urlencode($message));
}
exit;
?>