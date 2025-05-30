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

$tab = $_GET['tab'] ?? 'following';
$search = trim($_GET['search'] ?? '');
$success_message = $_GET['message'] ?? '';
$error_message = $_GET['error'] ?? '';

$following_users = [];
$followers_users = [];

try {
    // Λήψη χρηστών που ακολουθώ
    $following_query = "
        SELECT u.user_id, u.username, u.first_name, u.last_name, f.follow_date,
               COUNT(p.playlist_id) as public_playlists,
               (SELECT COUNT(*) FROM follows WHERE followed_user_id = u.user_id) as followers_count
        FROM follows f
        JOIN users u ON f.followed_user_id = u.user_id
        LEFT JOIN playlists p ON u.user_id = p.user_id AND p.is_public = 1
        WHERE f.follower_user_id = ?
    ";
    
    $following_params = [$_SESSION['user_id']];
    
    if (!empty($search)) {
        $following_query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ?)";
        $search_term = '%' . $search . '%';
        $following_params[] = $search_term;
        $following_params[] = $search_term;
        $following_params[] = $search_term;
    }
    
    $following_query .= " GROUP BY u.user_id ORDER BY f.follow_date DESC";
    
    $stmt = $pdo->prepare($following_query);
    $stmt->execute($following_params);
    $following_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Λήψη followers
    $followers_query = "
        SELECT u.user_id, u.username, u.first_name, u.last_name, f.follow_date,
               COUNT(p.playlist_id) as public_playlists,
               (SELECT COUNT(*) FROM follows WHERE followed_user_id = u.user_id) as followers_count,
               (SELECT COUNT(*) FROM follows WHERE follower_user_id = ? AND followed_user_id = u.user_id) as is_following_back
        FROM follows f
        JOIN users u ON f.follower_user_id = u.user_id
        LEFT JOIN playlists p ON u.user_id = p.user_id AND p.is_public = 1
        WHERE f.followed_user_id = ?
    ";
    
    $followers_params = [$_SESSION['user_id'], $_SESSION['user_id']];
    
    if (!empty($search)) {
        $followers_query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ?)";
        $followers_params[] = $search_term;
        $followers_params[] = $search_term;
        $followers_params[] = $search_term;
    }
    
    $followers_query .= " GROUP BY u.user_id ORDER BY f.follow_date DESC";
    
    $stmt = $pdo->prepare($followers_query);
    $stmt->execute($followers_params);
    $followers_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση δεδομένων: " . $e->getMessage();
}

// Στατιστικά
$stats = [
    'following_count' => count($following_users),
    'followers_count' => count($followers_users),
    'mutual_follows' => 0
];

// Υπολογισμός mutual follows
foreach ($followers_users as $follower) {
    if ($follower['is_following_back'] > 0) {
        $stats['mutual_follows']++;
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Διαχείριση Follows - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .follow-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .follow-header {
            background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--current-accordion-content-bg) 100%);
            border: 1px solid var(--current-border-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .follow-header h2 {
            margin: 0 0 20px 0;
            color: var(--current-accordion-header-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-color);
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .search-section {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--nav-link);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .tab-container {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .tab-buttons {
            display: flex;
            background-color: var(--current-accordion-header-bg);
            border-bottom: 1px solid var(--current-border-color);
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            border: none;
            background: transparent;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: var(--nav-link);
            border-bottom-color: var(--nav-link);
            background-color: var(--bg-color);
        }
        
        .tab-button:hover:not(.active) {
            background-color: var(--current-border-color);
        }
        
        .tab-content {
            padding: 25px;
            min-height: 400px;
        }
        
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .user-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-color: var(--nav-link);
        }
        
        .user-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            font-weight: bold;
            color: white;
            flex-shrink: 0;
        }
        
        .user-info {
            flex-grow: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            margin: 0 0 5px 0;
            color: var(--current-accordion-header-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-username {
            color: var(--nav-link);
            font-size: 0.9em;
            margin: 0;
        }
        
        .user-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.85em;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .follow-date {
            color: var(--text-color);
            font-size: 0.8em;
            opacity: 0.6;
            margin-bottom: 15px;
        }
        
        .user-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.8em;
            flex: 1;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--button-bg) 0%, var(--nav-link) 100%);
            color: var(--button-text);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background-color: var(--current-accordion-header-bg);
            color: var(--text-color);
            border: 1px solid var(--current-border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--current-border-color);
        }
        
        .btn-unfollow {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
            color: white;
        }
        
        .btn-unfollow:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220,53,69,0.3);
        }
        
        .btn-follow {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-follow:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40,167,69,0.3);
        }
        
        .mutual-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-color);
        }
        
        .empty-state h3 {
            margin-bottom: 15px;
            color: var(--current-accordion-header-text);
        }
        
        .back-link {
            margin-bottom: 20px;
        }
        
        .back-link a {
            color: var(--nav-link);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .back-link a:hover {
            color: var(--nav-link-hover);
        }
        
        .success-message,
        .error-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .users-grid {
                grid-template-columns: 1fr;
            }
            
            .tab-buttons {
                flex-direction: column;
            }
            
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="follow-container">
            <div class="back-link">
                <a href="profile.php">← Επιστροφή στο Προφίλ</a>
            </div>

            <?php if ($success_message): ?>
                <div class="success-message">✅ <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="error-message">❌ <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="follow-header">
                <h2>👥 Διαχείριση Follows</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $stats['following_count']; ?></span>
                        <div class="stat-label">Ακολουθώ</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $stats['followers_count']; ?></span>
                        <div class="stat-label">Followers</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $stats['mutual_follows']; ?></span>
                        <div class="stat-label">Αμοιβαία</div>
                    </div>
                </div>
            </div>

            <div class="search-section">
                <form class="search-form" method="GET">
                    <input type="text" name="search" class="search-input" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="🔍 Αναζήτηση χρηστών...">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <button type="submit" class="btn btn-primary">Αναζήτηση</button>
                    <?php if (!empty($search)): ?>
                        <a href="follow_management.php?tab=<?php echo htmlspecialchars($tab); ?>" class="btn btn-secondary">Καθαρισμός</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button <?php echo $tab === 'following' ? 'active' : ''; ?>" 
                            onclick="switchTab('following')">
                        🔗 Ακολουθώ (<?php echo count($following_users); ?>)
                    </button>
                    <button class="tab-button <?php echo $tab === 'followers' ? 'active' : ''; ?>" 
                            onclick="switchTab('followers')">
                        👥 Followers (<?php echo count($followers_users); ?>)
                    </button>
                </div>

                <div class="tab-content">
                    <?php if ($tab === 'following'): ?>
                        <!-- Following Tab -->
                        <?php if (empty($following_users)): ?>
                            <div class="empty-state">
                                <h3>🔍 <?php echo !empty($search) ? 'Δεν βρέθηκαν αποτελέσματα' : 'Δεν ακολουθείτε κανέναν'; ?></h3>
                                <p>
                                    <?php if (!empty($search)): ?>
                                        Δοκιμάστε διαφορετικούς όρους αναζήτησης.
                                    <?php else: ?>
                                        Ανακαλύψτε και ακολουθήστε άλλους χρήστες για να δείτε τις λίστες τους!
                                    <?php endif; ?>
                                </p>
                                <a href="search_content.php?show_public=true" class="btn btn-primary">
                                    🔍 Αναζήτηση Χρηστών
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="users-grid">
                                <?php foreach ($following_users as $user): ?>
                                    <div class="user-card">
                                        <div class="user-header">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="user-info">
                                                <h4 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                                <p class="user-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="user-stats">
                                            <span>📋 <?php echo $user['public_playlists']; ?> λίστες</span>
                                            <span>👥 <?php echo $user['followers_count']; ?> followers</span>
                                        </div>
                                        
                                        <div class="follow-date">
                                            Ακολουθείτε από: <?php 
                                                $date = new DateTime($user['follow_date']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        </div>
                                        
                                        <div class="user-actions">
                                            <a href="follow_user.php?user_id=<?php echo $user['user_id']; ?>&action=unfollow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                               class="btn btn-unfollow" 
                                               onclick="return confirm('Είστε σίγουροι ότι θέλετε να σταματήσετε να ακολουθείτε αυτόν τον χρήστη;')">
                                                ❌ Unfollow
                                            </a>
                                            <a href="search_content.php?creator=<?php echo urlencode($user['username']); ?>" 
                                               class="btn btn-secondary">
                                                👁️ Λίστες
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Followers Tab -->
                        <?php if (empty($followers_users)): ?>
                            <div class="empty-state">
                                <h3>👥 <?php echo !empty($search) ? 'Δεν βρέθηκαν αποτελέσματα' : 'Δεν έχετε followers'; ?></h3>
                                <p>
                                    <?php if (!empty($search)): ?>
                                        Δοκιμάστε διαφορετικούς όρους αναζήτησης.
                                    <?php else: ?>
                                        Δημιουργήστε περισσότερες δημόσιες λίστες για να προσελκύσετε followers!
                                    <?php endif; ?>
                                </p>
                                <a href="create_playlist.php" class="btn btn-primary">
                                    ➕ Δημιουργία Λίστας
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="users-grid">
                                <?php foreach ($followers_users as $user): ?>
                                    <div class="user-card">
                                        <?php if ($user['is_following_back'] > 0): ?>
                                            <div class="mutual-badge">🤝 Αμοιβαία</div>
                                        <?php endif; ?>
                                        
                                        <div class="user-header">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="user-info">
                                                <h4 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                                <p class="user-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="user-stats">
                                            <span>📋 <?php echo $user['public_playlists']; ?> λίστες</span>
                                            <span>👥 <?php echo $user['followers_count']; ?> followers</span>
                                        </div>
                                        
                                        <div class="follow-date">
                                            Σας ακολουθεί από: <?php 
                                                $date = new DateTime($user['follow_date']);
                                                echo $date->format('d/m/Y'); 
                                            ?>
                                        </div>
                                        
                                        <div class="user-actions">
                                            <?php if ($user['is_following_back'] > 0): ?>
                                                <a href="follow_user.php?user_id=<?php echo $user['user_id']; ?>&action=unfollow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                   class="btn btn-unfollow"
                                                   onclick="return confirm('Είστε σίγουροι ότι θέλετε να σταματήσετε να ακολουθείτε αυτόν τον χρήστη;')">
                                                    ❌ Unfollow
                                                </a>
                                            <?php else: ?>
                                                <a href="follow_user.php?user_id=<?php echo $user['user_id']; ?>&action=follow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                                   class="btn btn-follow">
                                                    ➕ Follow Back
                                                </a>
                                            <?php endif; ?>
                                            <a href="search_content.php?creator=<?php echo urlencode($user['username']); ?>" 
                                               class="btn btn-secondary">
                                                👁️ Λίστες
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script src="js/follow_system.js"></script>
    <script>
        function switchTab(newTab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', newTab);
            if (url.searchParams.get('search')) {
                url.searchParams.delete('search'); // Clear search when switching tabs
            }
            window.location.href = url.toString();
        }
        
        // Animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.user-card');
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