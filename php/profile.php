<?php
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

$user_info = null;
$error_message = '';
$success_message = $_GET['message'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, username, email, registration_date FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        $error_message = "Δεν βρέθηκαν πληροφορίες για τον χρήστη.";
    }
} catch (PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση πληροφοριών χρήστη: " . $e->getMessage();
}

// Λήψη στατιστικών χρήστη<?php
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

$user_info = null;
$error_message = '';
$success_message = $_GET['message'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, username, email, registration_date FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) {
        $error_message = "Δεν βρέθηκαν πληροφορίες για τον χρήστη.";
    }
} catch (PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση πληροφοριών χρήστη: " . $e->getMessage();
}

// Λήψη στατιστικών χρήστη
$stats = [
    'my_playlists' => 0,
    'total_videos' => 0,
    'following' => 0,
    'followers' => 0
];

try {
    // Οι λίστες μου
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM playlists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['my_playlists'] = $stmt->fetch()['count'];
    
    // Συνολικά βίντεο στις λίστες μου
    $stmt = $pdo->prepare("
        SELECT COUNT(pi.item_id) as count 
        FROM playlist_items pi 
        JOIN playlists p ON pi.playlist_id = p.playlist_id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_videos'] = $stmt->fetch()['count'];
    
    // Πόσους ακολουθώ
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['following'] = $stmt->fetch()['count'];
    
    // Πόσοι με ακολουθούν
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['followers'] = $stmt->fetch()['count'];
} catch (PDOException $e) {
    // Stats are not critical
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προφίλ Χρήστη - <?php echo isset($user_info['username']) ? htmlspecialchars($user_info['username']) : 'Ροή μου'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--current-accordion-content-bg) 100%);
            border: 1px solid var(--current-border-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
        }
        
        .profile-info h2 {
            margin: 0 0 10px 0;
            color: var(--current-accordion-header-text);
            font-size: 1.8em;
        }
        
        .profile-username {
            color: var(--nav-link);
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        
        .profile-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        
        .meta-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
        }
        
        .meta-label {
            font-size: 0.9em;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .section {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-header {
            background-color: var(--current-accordion-header-bg);
            padding: 20px;
            border-bottom: 1px solid var(--current-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            margin: 0;
            color: var(--current-accordion-header-text);
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .playlists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .playlist-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .playlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .playlist-thumbnail {
            height: 120px;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
            position: relative;
        }
        
        .playlist-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .playlist-card:hover .playlist-overlay {
            opacity: 1;
        }
        
        .play-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: white;
            color: var(--nav-link);
            border: none;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        
        .play-button:hover {
            transform: scale(1.1);
        }
        
        .playlist-info {
            padding: 15px;
        }
        
        .playlist-title {
            margin: 0 0 8px 0;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .playlist-title a {
            color: var(--text-color);
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .playlist-title a:hover {
            color: var(--nav-link);
        }
        
        .playlist-meta {
            color: var(--text-color);
            font-size: 0.9em;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .playlist-creator {
            color: var(--nav-link);
            font-weight: bold;
            font-size: 0.85em;
        }
        
        .privacy-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        .privacy-public {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .privacy-private {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9em;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--button-bg) 0%, var(--nav-link) 100%);
            color: var(--button-text);
            box-shadow: 0 2px 10px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.4);
        }
        
        .btn-secondary {
            background-color: var(--current-accordion-header-bg);
            color: var(--text-color);
            border: 1px solid var(--current-border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--current-border-color);
            transform: translateY(-1px);
        }
        
        .btn-icon {
            font-size: 1.1em;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-color);
            opacity: 0.7;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
            color: var(--current-accordion-header-text);
        }
        
        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(21,87,36,0.1);
        }
        
        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(114,28,36,0.1);
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            gap: 5px;
            background-color: var(--current-accordion-header-bg);
            padding: 5px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .tab-button {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: var(--text-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .tab-button.active {
            background-color: var(--button-bg);
            color: var(--button-text);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .profile-meta {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .playlists-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .tab-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="profile-container">
            <?php if ($success_message): ?>
                <div class="success-message">✅ <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">❌ <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($user_info): ?>
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_info['first_name'], 0, 1) . substr($user_info['last_name'], 0, 1)); ?>
                    </div>
                    
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></h2>
                        <div class="profile-username">@<?php echo htmlspecialchars($user_info['username']); ?></div>
                        
                        <div class="profile-meta">
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['my_playlists']; ?></span>
                                <div class="meta-label">Οι Λίστες μου</div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['total_videos']; ?></span>
                                <div class="meta-label">Συνολικά Βίντεο</div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['following']; ?></span>
                                <div class="meta-label">Ακολουθώ</div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['followers']; ?></span>
                                <div class="meta-label">Followers</div>
                            </div>
                        </div>
                        
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn btn-primary">
                                <span class="btn-icon">⚙️</span> Επεξεργασία Προφίλ
                            </a>
                            <a href="my_playlists.php" class="btn btn-secondary">
                                <span class="btn-icon">📋</span> Διαχείριση Λιστών
                            </a>
                            <a href="create_playlist.php" class="btn btn-secondary">
                                <span class="btn-icon">➕</span> Νέα Λίστα
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Content Tabs -->
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="switchTab('my-playlists')">
                            🎵 Οι Λίστες μου
                        </button>
                        <button class="tab-button" onclick="switchTab('followed-playlists')">
                            👥 Λίστες που Ακολουθώ
                        </button>
                        <button class="tab-button" onclick="switchTab('following-users')">
                            🔗 Χρήστες που Ακολουθώ
                        </button>
                    </div>

                    <!-- My Playlists Tab -->
                    <div id="my-playlists" class="tab-content active">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <span>🎵</span> Οι Λίστες μου
                                </h3>
                                <a href="my_playlists.php" class="btn btn-secondary">Προβολή όλων</a>
                            </div>
                            <div class="section-content">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT p.playlist_id, p.playlist_name, p.is_public, p.creation_date,
                                               COUNT(pi.item_id) as item_count
                                        FROM playlists p 
                                        LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
                                        WHERE p.user_id = ? 
                                        GROUP BY p.playlist_id
                                        ORDER BY item_count DESC
                                        LIMIT 6
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $user_playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($user_playlists): ?>
                                        <div class="playlists-grid">
                                            <?php foreach ($user_playlists as $playlist): ?>
                                                <div class="playlist-card">
                                                    <div class="playlist-thumbnail">
                                                        🎵
                                                        <div class="playlist-overlay">
                                                            <button class="play-button" onclick="playPlaylist(<?php echo $playlist['playlist_id']; ?>)">
                                                                ▶️
                                                            </button>
                                                        </div>
                                                        <div class="privacy-badge <?php echo $playlist['is_public'] ? 'privacy-public' : 'privacy-private'; ?>">
                                                            <?php echo $playlist['is_public'] ? 'Δημόσια' : 'Ιδιωτική'; ?>
                                                        </div>
                                                    </div>
                                                    <div class="playlist-info">
                                                        <h4 class="playlist-title">
                                                            <a href="view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                                                <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                                                            </a>
                                                        </h4>
                                                        <div class="playlist-meta">
                                                            <?php echo $playlist['item_count']; ?> βίντεο • 
                                                            <?php 
                                                                $date = new DateTime($playlist['creation_date']);
                                                                echo $date->format('d/m/Y'); 
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <h4>Δεν έχετε δημιουργήσει ακόμα λίστες</h4>
                                            <p>Δημιουργήστε την πρώτη σας λίστα για να οργανώσετε τα αγαπημένα σας βίντεο!</p>
                                            <a href="create_playlist.php" class="btn btn-primary">
                                                <span class="btn-icon">➕</span> Δημιουργία Πρώτης Λίστας
                                            </a>
                                        </div>
                                    <?php endif;
                                } catch (PDOException $e) {
                                    echo "<div class='error-message'>Σφάλμα κατά την ανάκτηση λιστών.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Followed Playlists Tab -->
                    <div id="followed-playlists" class="tab-content">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <span>👥</span> Λίστες από Χρήστες που Ακολουθώ
                                </h3>
                            </div>
                            <div class="section-content">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT p.playlist_id, p.playlist_name, p.creation_date,
                                               u.username, u.first_name, u.last_name, u.user_id,
                                               COUNT(pi.item_id) as item_count
                                        FROM playlists p 
                                        JOIN users u ON p.user_id = u.user_id
                                        JOIN follows f ON f.followed_user_id = u.user_id
                                        LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
                                        WHERE p.is_public = 1 AND f.follower_user_id = ?
                                        GROUP BY p.playlist_id
                                        ORDER BY item_count DESC
                                        LIMIT 12
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $followed_playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($followed_playlists): ?>
                                        <div class="playlists-grid">
                                            <?php foreach ($followed_playlists as $playlist): ?>
                                                <div class="playlist-card">
                                                    <div class="playlist-thumbnail">
                                                        👥
                                                        <div class="playlist-overlay">
                                                            <button class="play-button" onclick="playPlaylist(<?php echo $playlist['playlist_id']; ?>)">
                                                                ▶️
                                                            </button>
                                                        </div>
                                                        <div class="privacy-badge privacy-public">Δημόσια</div>
                                                    </div>
                                                    <div class="playlist-info">
                                                        <h4 class="playlist-title">
                                                            <a href="view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                                                <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                                                            </a>
                                                        </h4>
                                                        <div class="playlist-creator">
                                                            από <?php echo htmlspecialchars($playlist['first_name'] . ' ' . $playlist['last_name']); ?>
                                                        </div>
                                                        <div class="playlist-meta">
                                                            <?php echo $playlist['item_count']; ?> βίντεο • 
                                                            <?php 
                                                                $date = new DateTime($playlist['creation_date']);
                                                                echo $date->format('d/m/Y'); 
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <h4>Δεν ακολουθείτε κανέναν ή δεν έχουν δημόσιες λίστες</h4>
                                            <p>Ανακαλύψτε και ακολουθήστε άλλους χρήστες για να δείτε τις λίστες τους εδώ!</p>
                                            <a href="search_content.php?show_public=true" class="btn btn-primary">
                                                <span class="btn-icon">🔍</span> Αναζήτηση Χρηστών
                                            </a>
                                        </div>
                                    <?php endif;
                                } catch (PDOException $e) {
                                    echo "<div class='error-message'>Σφάλμα κατά την ανάκτηση λιστών.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Following Users Tab -->
                    <div id="following-users" class="tab-content">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <span>🔗</span> Χρήστες που Ακολουθώ
                                </h3>
                            </div>
                            <div class="section-content">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT u.user_id, u.username, u.first_name, u.last_name, f.follow_date,
                                               COUNT(p.playlist_id) as public_playlists
                                        FROM follows f
                                        JOIN users u ON f.followed_user_id = u.user_id
                                        LEFT JOIN playlists p ON u.user_id = p.user_id AND p.is_public = 1
                                        WHERE f.follower_user_id = ?
                                        GROUP BY u.user_id
                                        ORDER BY f.follow_date DESC
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $following_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($following_users): ?>
                                        <div class="playlists-grid">
                                            <?php foreach ($following_users as $user): ?>
                                                <div class="playlist-card">
                                                    <div class="playlist-thumbnail">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="playlist-info">
                                                        <h4 class="playlist-title">
                                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        </h4>
                                                        <div class="playlist-creator">
                                                            @<?php echo htmlspecialchars($user['username']); ?>
                                                        </div>
                                                        <div class="playlist-meta">
                                                            <?php echo $user['public_playlists']; ?> δημόσιες λίστες
                                                        </div>
                                                        <div style="margin-top: 10px;">
                                                            <a href="follow_user.php?user_id=<?php echo $user['user_id']; ?>&action=unfollow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                               class="btn btn-secondary" 
                                                               onclick="return confirm('Είστε σίγουροι ότι θέλετε να σταματήσετε να ακολουθείτε αυτόν τον χρήστη;')">
                                                                <span class="btn-icon">❌</span> Unfollow
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <h4>Δεν ακολουθείτε κανέναν χρήστη</h4>
                                            <p>Ανακαλύψτε άλλους χρήστες και ακολουθήστε τους για να δείτε τις λίστες τους!</p>
                                            <a href="search_content.php?show_public=true" class="btn btn-primary">
                                                <span class="btn-icon">🔍</span> Αναζήτηση Χρηστών
                                            </a>
                                        </div>
                                    <?php endif;
                                } catch (PDOException $e) {
                                    echo "<div class='error-message'>Σφάλμα κατά την ανάκτηση χρηστών.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif(!$error_message): ?>
                <div class="error-message">Δεν ήταν δυνατή η φόρτωση των πληροφοριών του προφίλ σας.</div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script src="js/follow_system.js"></script>
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function playPlaylist(playlistId) {
            // Redirect to playlist view
            window.location.href = `view_playlist_items.php?playlist_id=${playlistId}&autoplay=1`;
        }
        
        // Add smooth scrolling and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.playlist-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
$stats = [
    'my_playlists' => 0,
    'total_videos' => 0,
    'following' => 0,
    'followers' => 0
];

try {
    // Οι λίστες μου
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM playlists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['my_playlists'] = $stmt->fetch()['count'];
    
    // Συνολικά βίντεο στις λίστες μου
    $stmt = $pdo->prepare("
        SELECT COUNT(pi.item_id) as count 
        FROM playlist_items pi 
        JOIN playlists p ON pi.playlist_id = p.playlist_id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_videos'] = $stmt->fetch()['count'];
    
    // Πόσους ακολουθώ
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['following'] = $stmt->fetch()['count'];
    
    // Πόσοι με ακολουθούν
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE followed_user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['followers'] = $stmt->fetch()['count'];
} catch (PDOException $e) {
    // Stats are not critical
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προφίλ Χρήστη - <?php echo isset($user_info['username']) ? htmlspecialchars($user_info['username']) : 'Ροή μου'; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--current-accordion-content-bg) 100%);
            border: 1px solid var(--current-border-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
        }
        
        .profile-info h2 {
            margin: 0 0 10px 0;
            color: var(--current-accordion-header-text);
            font-size: 1.8em;
        }
        
        .profile-username {
            color: var(--nav-link);
            font-size: 1.1em;
            margin-bottom: 15px;
        }
        
        .profile-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        
        .meta-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
        }
        
        .meta-label {
            font-size: 0.9em;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .section {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-header {
            background-color: var(--current-accordion-header-bg);
            padding: 20px;
            border-bottom: 1px solid var(--current-border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            margin: 0;
            color: var(--current-accordion-header-text);
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .playlists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .playlist-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .playlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .playlist-thumbnail {
            height: 120px;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
            position: relative;
        }
        
        .playlist-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .playlist-card:hover .playlist-overlay {
            opacity: 1;
        }
        
        .play-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: white;
            color: var(--nav-link);
            border: none;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        
        .play-button:hover {
            transform: scale(1.1);
        }
        
        .playlist-info {
            padding: 15px;
        }
        
        .playlist-title {
            margin: 0 0 8px 0;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .playlist-title a {
            color: var(--text-color);
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .playlist-title a:hover {
            color: var(--nav-link);
        }
        
        .playlist-meta {
            color: var(--text-color);
            font-size: 0.9em;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .playlist-creator {
            color: var(--nav-link);
            font-weight: bold;
            font-size: 0.85em;
        }
        
        .privacy-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        .privacy-public {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .privacy-private {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9em;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--button-bg) 0%, var(--nav-link) 100%);
            color: var(--button-text);
            box-shadow: 0 2px 10px rgba(0,123,255,0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.4);
        }
        
        .btn-secondary {
            background-color: var(--current-accordion-header-bg);
            color: var(--text-color);
            border: 1px solid var(--current-border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--current-border-color);
            transform: translateY(-1px);
        }
        
        .btn-icon {
            font-size: 1.1em;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-color);
            opacity: 0.7;
        }
        
        .empty-state h4 {
            margin-bottom: 10px;
            color: var(--current-accordion-header-text);
        }
        
        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(21,87,36,0.1);
        }
        
        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(114,28,36,0.1);
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            gap: 5px;
            background-color: var(--current-accordion-header-bg);
            padding: 5px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .tab-button {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: var(--text-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .tab-button.active {
            background-color: var(--button-bg);
            color: var(--button-text);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .profile-meta {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .playlists-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .tab-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="profile-container">
            <?php if ($success_message): ?>
                <div class="success-message">✅ <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">❌ <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($user_info): ?>
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_info['first_name'], 0, 1) . substr($user_info['last_name'], 0, 1)); ?>
                    </div>
                    
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></h2>
                        <div class="profile-username">@<?php echo htmlspecialchars($user_info['username']); ?></div>
                        
                        <div class="profile-meta">
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['my_playlists']; ?></span>
                                <div class="meta-label">Οι Λίστες μου</div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['total_videos']; ?></span>
                                <div class="meta-label">Συνολικά Βίντεο</div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['following']; ?></span>
                                <div class="meta-label">Ακολουθώ</div>
                            </div>
                            <div class="meta-item">
                                <span class="meta-value"><?php echo $stats['followers']; ?></span>
                                <div class="meta-label">Followers</div>
                            </div>
                        </div>
                        
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn btn-primary">
                                <span class="btn-icon">⚙️</span> Επεξεργασία Προφίλ
                            </a>
                            <a href="my_playlists.php" class="btn btn-secondary">
                                <span class="btn-icon">📋</span> Διαχείριση Λιστών
                            </a>
                            <a href="create_playlist.php" class="btn btn-secondary">
                                <span class="btn-icon">➕</span> Νέα Λίστα
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Content Tabs -->
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="switchTab('my-playlists')">
                            🎵 Οι Λίστες μου
                        </button>
                        <button class="tab-button" onclick="switchTab('followed-playlists')">
                            👥 Λίστες που Ακολουθώ
                        </button>
                        <button class="tab-button" onclick="switchTab('following-users')">
                            🔗 Χρήστες που Ακολουθώ
                        </button>
                    </div>

                    <!-- My Playlists Tab -->
                    <div id="my-playlists" class="tab-content active">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <span>🎵</span> Οι Λίστες μου
                                </h3>
                                <a href="my_playlists.php" class="btn btn-secondary">Προβολή όλων</a>
                            </div>
                            <div class="section-content">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT p.playlist_id, p.playlist_name, p.is_public, p.creation_date,
                                               COUNT(pi.item_id) as item_count
                                        FROM playlists p 
                                        LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
                                        WHERE p.user_id = ? 
                                        GROUP BY p.playlist_id
                                        ORDER BY item_count DESC
                                        LIMIT 6
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $user_playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($user_playlists): ?>
                                        <div class="playlists-grid">
                                            <?php foreach ($user_playlists as $playlist): ?>
                                                <div class="playlist-card">
                                                    <div class="playlist-thumbnail">
                                                        🎵
                                                        <div class="playlist-overlay">
                                                            <button class="play-button" onclick="playPlaylist(<?php echo $playlist['playlist_id']; ?>)">
                                                                ▶️
                                                            </button>
                                                        </div>
                                                        <div class="privacy-badge <?php echo $playlist['is_public'] ? 'privacy-public' : 'privacy-private'; ?>">
                                                            <?php echo $playlist['is_public'] ? 'Δημόσια' : 'Ιδιωτική'; ?>
                                                        </div>
                                                    </div>
                                                    <div class="playlist-info">
                                                        <h4 class="playlist-title">
                                                            <a href="view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                                                <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                                                            </a>
                                                        </h4>
                                                        <div class="playlist-meta">
                                                            <?php echo $playlist['item_count']; ?> βίντεο • 
                                                            <?php 
                                                                $date = new DateTime($playlist['creation_date']);
                                                                echo $date->format('d/m/Y'); 
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <h4>Δεν έχετε δημιουργήσει ακόμα λίστες</h4>
                                            <p>Δημιουργήστε την πρώτη σας λίστα για να οργανώσετε τα αγαπημένα σας βίντεο!</p>
                                            <a href="create_playlist.php" class="btn btn-primary">
                                                <span class="btn-icon">➕</span> Δημιουργία Πρώτης Λίστας
                                            </a>
                                        </div>
                                    <?php endif;
                                } catch (PDOException $e) {
                                    echo "<div class='error-message'>Σφάλμα κατά την ανάκτηση λιστών.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Followed Playlists Tab -->
                    <div id="followed-playlists" class="tab-content">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <span>👥</span> Λίστες από Χρήστες που Ακολουθώ
                                </h3>
                            </div>
                            <div class="section-content">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT p.playlist_id, p.playlist_name, p.creation_date,
                                               u.username, u.first_name, u.last_name, u.user_id,
                                               COUNT(pi.item_id) as item_count
                                        FROM playlists p 
                                        JOIN users u ON p.user_id = u.user_id
                                        JOIN follows f ON f.followed_user_id = u.user_id
                                        LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
                                        WHERE p.is_public = 1 AND f.follower_user_id = ?
                                        GROUP BY p.playlist_id
                                        ORDER BY item_count DESC
                                        LIMIT 12
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $followed_playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($followed_playlists): ?>
                                        <div class="playlists-grid">
                                            <?php foreach ($followed_playlists as $playlist): ?>
                                                <div class="playlist-card">
                                                    <div class="playlist-thumbnail">
                                                        👥
                                                        <div class="playlist-overlay">
                                                            <button class="play-button" onclick="playPlaylist(<?php echo $playlist['playlist_id']; ?>)">
                                                                ▶️
                                                            </button>
                                                        </div>
                                                        <div class="privacy-badge privacy-public">Δημόσια</div>
                                                    </div>
                                                    <div class="playlist-info">
                                                        <h4 class="playlist-title">
                                                            <a href="view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                                                <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                                                            </a>
                                                        </h4>
                                                        <div class="playlist-creator">
                                                            από <?php echo htmlspecialchars($playlist['first_name'] . ' ' . $playlist['last_name']); ?>
                                                        </div>
                                                        <div class="playlist-meta">
                                                            <?php echo $playlist['item_count']; ?> βίντεο • 
                                                            <?php 
                                                                $date = new DateTime($playlist['creation_date']);
                                                                echo $date->format('d/m/Y'); 
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <h4>Δεν ακολουθείτε κανέναν ή δεν έχουν δημόσιες λίστες</h4>
                                            <p>Ανακαλύψτε και ακολουθήστε άλλους χρήστες για να δείτε τις λίστες τους εδώ!</p>
                                            <a href="search_content.php?show_public=true" class="btn btn-primary">
                                                <span class="btn-icon">🔍</span> Αναζήτηση Χρηστών
                                            </a>
                                        </div>
                                    <?php endif;
                                } catch (PDOException $e) {
                                    echo "<div class='error-message'>Σφάλμα κατά την ανάκτηση λιστών.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Following Users Tab -->
                    <div id="following-users" class="tab-content">
                        <div class="section">
                            <div class="section-header">
                                <h3 class="section-title">
                                    <span>🔗</span> Χρήστες που Ακολουθώ
                                </h3>
                            </div>
                            <div class="section-content">
                                <?php
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT u.user_id, u.username, u.first_name, u.last_name, f.follow_date,
                                               COUNT(p.playlist_id) as public_playlists
                                        FROM follows f
                                        JOIN users u ON f.followed_user_id = u.user_id
                                        LEFT JOIN playlists p ON u.user_id = p.user_id AND p.is_public = 1
                                        WHERE f.follower_user_id = ?
                                        GROUP BY u.user_id
                                        ORDER BY f.follow_date DESC
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $following_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if ($following_users): ?>
                                        <div class="playlists-grid">
                                            <?php foreach ($following_users as $user): ?>
                                                <div class="playlist-card">
                                                    <div class="playlist-thumbnail">
                                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="playlist-info">
                                                        <h4 class="playlist-title">
                                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        </h4>
                                                        <div class="playlist-creator">
                                                            @<?php echo htmlspecialchars($user['username']); ?>
                                                        </div>
                                                        <div class="playlist-meta">
                                                            <?php echo $user['public_playlists']; ?> δημόσιες λίστες
                                                        </div>
                                                        <div style="margin-top: 10px;">
                                                            <a href="follow_user.php?user_id=<?php echo $user['user_id']; ?>&action=unfollow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                               class="btn btn-secondary" 
                                                               onclick="return confirm('Είστε σίγουροι ότι θέλετε να σταματήσετε να ακολουθείτε αυτόν τον χρήστη;')">
                                                                <span class="btn-icon">❌</span> Unfollow
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="empty-state">
                                            <h4>Δεν ακολουθείτε κανέναν χρήστη</h4>
                                            <p>Ανακαλύψτε άλλους χρήστες και ακολουθήστε τους για να δείτε τις λίστες τους!</p>
                                            <a href="search_content.php?show_public=true" class="btn btn-primary">
                                                <span class="btn-icon">🔍</span> Αναζήτηση Χρηστών
                                            </a>
                                        </div>
                                    <?php endif;
                                } catch (PDOException $e) {
                                    echo "<div class='error-message'>Σφάλμα κατά την ανάκτηση χρηστών.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif(!$error_message): ?>
                <div class="error-message">Δεν ήταν δυνατή η φόρτωση των πληροφοριών του προφίλ σας.</div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script src="js/follow_system.js"></script>
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function playPlaylist(playlistId) {
            // Redirect to playlist view
            window.location.href = `view_playlist_items.php?playlist_id=${playlistId}&autoplay=1`;
        }
        
        // Add smooth scrolling and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.playlist-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
