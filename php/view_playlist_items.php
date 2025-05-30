<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'php/db_connect.php';

$playlist_id = $_GET['playlist_id'] ?? 0;
$autoplay = isset($_GET['autoplay']) && $_GET['autoplay'] == '1';
$playlist = null;
$playlist_items = [];
$error_message = '';
$is_owner = false;
$is_following = false;

if (!$playlist_id) {
    header('Location: index.php');
    exit;
}

try {
    // Λήψη πληροφοριών λίστας
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.first_name, u.last_name, u.user_id as creator_id,
               (SELECT COUNT(*) FROM follows WHERE followed_user_id = u.user_id) as followers_count
        FROM playlists p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.playlist_id = ?
    ");
    $stmt->execute([$playlist_id]);
    $playlist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$playlist) {
        header('Location: index.php');
        exit;
    }

    // Έλεγχος δικαιωμάτων πρόσβασης
    $is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $playlist['creator_id'];
    
    if (!$playlist['is_public'] && !$is_owner) {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php?message=login_required_to_view_playlist');
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    }

    // Έλεγχος follow status
    if (isset($_SESSION['user_id']) && !$is_owner) {
        $stmt = $pdo->prepare("SELECT follow_id FROM follows WHERE follower_user_id = ? AND followed_user_id = ?");
        $stmt->execute([$_SESSION['user_id'], $playlist['creator_id']]);
        $is_following = (bool)$stmt->fetch();
    }

    // Λήψη περιεχομένων λίστας
    $stmt = $pdo->prepare("
        SELECT * FROM playlist_items 
        WHERE playlist_id = ? 
        ORDER BY added_date ASC
    ");
    $stmt->execute([$playlist_id]);
    $playlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Σφάλμα κατά την ανάκτηση της λίστας: " . $e->getMessage();
}

// Διαχείριση διαγραφής βίντεο
if (isset($_POST['delete_video']) && $is_owner) {
    try {
        $stmt = $pdo->prepare("DELETE FROM playlist_items WHERE item_id = ? AND playlist_id = ?");
        $stmt->execute([$_POST['item_id'], $playlist_id]);
        
        header("Location: view_playlist_items.php?playlist_id=" . $playlist_id);
        exit;
    } catch (PDOException $e) {
        $error_message = "Σφάλμα κατά τη διαγραφή του βίντεο: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $playlist ? htmlspecialchars($playlist['playlist_name']) : 'Λίστα'; ?> - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .playlist-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 30px;
            min-height: 600px;
        }
        
        .playlist-sidebar {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 15px;
            overflow: hidden;
            height: fit-content;
            position: sticky;
            top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .playlist-header {
            background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--current-accordion-content-bg) 100%);
            padding: 25px;
            border-bottom: 1px solid var(--current-border-color);
        }
        
        .playlist-cover {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4em;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .play-all-button {
            position: absolute;
            bottom: 15px;
            right: 15px;
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
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .play-all-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .playlist-title {
            font-size: 1.4em;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: var(--current-accordion-header-text);
            line-height: 1.3;
        }
        
        .privacy-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75em;
            font-weight: bold;
            margin-bottom: 15px;
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
        
        .creator-section {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .creator-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .creator-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1em;
            font-weight: bold;
            color: white;
        }
        
        .creator-details h4 {
            margin: 0;
            color: var(--current-accordion-header-text);
            font-size: 1em;
        }
        
        .creator-username {
            color: var(--nav-link);
            font-size: 0.9em;
            margin: 0;
        }
        
        .creator-stats {
            color: var(--text-color);
            font-size: 0.8em;
            opacity: 0.8;
        }
        
        .playlist-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 12px;
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
        }
        
        .stat-value {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
        }
        
        .stat-label {
            font-size: 0.8em;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .playlist-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9em;
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
        
        .btn-follow {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-follow:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40,167,69,0.4);
        }
        
        .btn-unfollow {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
            color: white;
        }
        
        .btn-unfollow:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220,53,69,0.4);
        }
        
        .main-content {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .player-section {
            background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--current-accordion-content-bg) 100%);
            padding: 25px;
            border-bottom: 1px solid var(--current-border-color);
        }
        
        .current-video {
            display: none;
        }
        
        .current-video.active {
            display: block;
        }
        
        .video-player {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .video-player iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .video-info {
            margin-top: 20px;
            padding: 20px;
            background-color: var(--bg-color);
            border-radius: 10px;
        }
        
        .video-title {
            font-size: 1.3em;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: var(--current-accordion-header-text);
        }
        
        .video-meta {
            color: var(--text-color);
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .playlist-content {
            padding: 25px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--current-accordion-header-text);
            margin: 0;
        }
        
        .video-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .video-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .video-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-color: var(--nav-link);
        }
        
        .video-item.active {
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            color: white;
            border-color: var(--nav-link);
        }
        
        .video-item.active .video-item-title,
        .video-item.active .video-item-meta {
            color: white;
        }
        
        .video-thumbnail {
            width: 120px;
            height: 68px;
            border-radius: 6px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-number {
            position: absolute;
            top: 5px;
            left: 5px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: bold;
        }
        
        .video-details {
            flex-grow: 1;
            min-width: 0;
        }
        
        .video-item-title {
            font-weight: 600;
            margin: 0 0 8px 0;
            color: var(--text-color);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .video-item-meta {
            color: var(--text-color);
            font-size: 0.8em;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .video-item-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 0.7em;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
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
        
        .error-message {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(114,28,36,0.1);
        }
        
        @media (max-width: 1200px) {
            .playlist-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .playlist-sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .video-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .video-thumbnail {
                width: 100%;
                height: 180px;
            }
            
            .playlist-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="back-link">
            <a href="<?php echo $is_owner ? 'my_playlists.php' : 'search_content.php'; ?>">
                ← Επιστροφή στις λίστες
            </a>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">❌ <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($playlist): ?>
            <div class="playlist-container">
                <!-- Sidebar -->
                <div class="playlist-sidebar">
                    <div class="playlist-header">
                        <div class="playlist-cover">
                            🎵
                            <?php if (!empty($playlist_items)): ?>
                                <button class="play-all-button" onclick="playVideo(0)" title="Αναπαραγωγή όλων">
                                    ▶️
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <h2 class="playlist-title"><?php echo htmlspecialchars($playlist['playlist_name']); ?></h2>
                        
                        <span class="privacy-badge <?php echo $playlist['is_public'] ? 'privacy-public' : 'privacy-private'; ?>">
                            <?php echo $playlist['is_public'] ? '🌐 Δημόσια' : '🔒 Ιδιωτική'; ?>
                        </span>
                        
                        <div class="creator-section">
                            <div class="creator-info">
                                <div class="creator-avatar">
                                    <?php echo strtoupper(substr($playlist['first_name'], 0, 1) . substr($playlist['last_name'], 0, 1)); ?>
                                </div>
                                <div class="creator-details">
                                    <h4><?php echo htmlspecialchars($playlist['first_name'] . ' ' . $playlist['last_name']); ?></h4>
                                    <p class="creator-username">@<?php echo htmlspecialchars($playlist['username']); ?></p>
                                    <div class="creator-stats">👥 <?php echo $playlist['followers_count']; ?> followers</div>
                                </div>
                            </div>
                            
                            <?php if (isset($_SESSION['user_id']) && !$is_owner): ?>
                                <?php if ($is_following): ?>
                                    <a href="follow_user.php?user_id=<?php echo $playlist['creator_id']; ?>&action=unfollow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                       class="btn btn-unfollow">
                                        ❌ Unfollow
                                    </a>
                                <?php else: ?>
                                    <a href="follow_user.php?user_id=<?php echo $playlist['creator_id']; ?>&action=follow&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                                       class="btn btn-follow">
                                        ➕ Follow
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="playlist-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo count($playlist_items); ?></span>
                                <div class="stat-label">Βίντεο</div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"><?php 
                                    $date = new DateTime($playlist['creation_date']);
                                    echo $date->format('d/m'); 
                                ?></span>
                                <div class="stat-label">Δημιουργήθηκε</div>
                            </div>
                        </div>
                        
                        <?php if ($is_owner): ?>
                            <div class="playlist-actions">
                                <a href="search_youtube.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-primary">
    ➕ Προσθήκη Βίντεο
</a>
                                    ➕ Προσθήκη Βίντεο
                                </a>
                                <a href="edit_playlist.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-secondary">
                                    ⚙️ Επεξεργασία
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="main-content">
                    <?php if (!empty($playlist_items)): ?>
                        <!-- Video Player -->
                        <div class="player-section">
                            <?php foreach ($playlist_items as $index => $item): ?>
                                <div class="current-video" id="video-<?php echo $index; ?>">
                                    <div class="video-player">
                                        <iframe id="player-<?php echo $index; ?>" 
                                                data-video-id="<?php echo htmlspecialchars($item['video_id']); ?>"
                                                src="<?php echo $autoplay && $index === 0 ? 'https://www.youtube.com/embed/' . htmlspecialchars($item['video_id']) . '?autoplay=1&rel=0' : ''; ?>"
                                                allowfullscreen></iframe>
                                    </div>
                                    <div class="video-info">
                                        <h3 class="video-title"><?php echo htmlspecialchars($item['video_title']); ?></h3>
                                        <div class="video-meta">
                                            Προστέθηκε: <?php 
                                                $date = new DateTime($item['added_date']);
                                                echo $date->format('d/m/Y H:i'); 
                                            ?> • 
                                            <a href="https://www.youtube.com/watch?v=<?php echo htmlspecialchars($item['video_id']); ?>" 
                                               target="_blank">Προβολή στο YouTube</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Playlist Content -->
                        <div class="playlist-content">
                            <div class="section-header">
                                <h3 class="section-title">📋 Περιεχόμενα Λίστας</h3>
                            </div>
                            
                            <div class="video-list">
                                <?php foreach ($playlist_items as $index => $item): ?>
                                    <div class="video-item <?php echo $autoplay && $index === 0 ? 'active' : ''; ?>" 
                                         onclick="playVideo(<?php echo $index; ?>)" 
                                         id="item-<?php echo $index; ?>">
                                        <div class="video-thumbnail">
                                            <?php if ($item['video_thumbnail']): ?>
                                                <img src="<?php echo htmlspecialchars($item['video_thumbnail']); ?>" 
                                                     alt="Thumbnail" loading="lazy">
                                            <?php endif; ?>
                                            <div class="video-number"><?php echo $index + 1; ?></div>
                                        </div>
                                        
                                        <div class="video-details">
                                            <h4 class="video-item-title"><?php echo htmlspecialchars($item['video_title']); ?></h4>
                                            <div class="video-item-meta">
                                                Προστέθηκε: <?php 
                                                    $date = new DateTime($item['added_date']);
                                                    echo $date->format('d/m/Y'); 
                                                ?>
                                            </div>
                                            
                                            <?php if ($is_owner): ?>
                                                <div class="video-item-actions" onclick="event.stopPropagation();">
                                                    <form style="display: inline;" method="POST" 
                                                          onsubmit="return confirm('Είστε σίγουροι ότι θέλετε να αφαιρέσετε αυτό το βίντεο;');">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                        <button type="submit" name="delete_video" class="btn btn-danger btn-small">
                                                            🗑️ Αφαίρεση
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>📭 Η λίστα είναι κενή</h3>
                            <?php if ($is_owner): ?>
                                <p>Προσθέστε βίντεο από το YouTube για να ξεκινήσετε!</p>
                                <a href="search_youtube.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-primary">
    ➕ Προσθήκη Βίντεο
</a>
                                    ➕ Προσθήκη Πρώτου Βίντεο
                                </a>
                            <?php else: ?>
                                <p>Ο δημιουργός δεν έχει προσθέσει ακόμα περιεχόμενο σε αυτή τη λίστα.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        let currentVideoIndex = <?php echo $autoplay ? '0' : '-1'; ?>;
        const totalVideos = <?php echo count($playlist_items); ?>;
        
        function playVideo(index) {
            // Hide all videos
            document.querySelectorAll('.current-video').forEach(video => {
                video.classList.remove('active');
            });
            
            // Remove active class from all items
            document.querySelectorAll('.video-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected video
            const videoElement = document.getElementById(`video-${index}`);
            const itemElement = document.getElementById(`item-${index}`);
            const playerElement = document.getElementById(`player-${index}`);
            
            if (videoElement && itemElement && playerElement) {
                videoElement.classList.add('active');
                itemElement.classList.add('active');
                
                // Load video if not already loaded
                const videoId = playerElement.getAttribute('data-video-id');
                if (!playerElement.src) {
                    playerElement.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
                }
                
                currentVideoIndex = index;
                
                // Scroll to video player on mobile
                if (window.innerWidth <= 1200) {
                    videoElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
        
        function playNext() {
            if (currentVideoIndex < totalVideos - 1) {
                playVideo(currentVideoIndex + 1);
            }
        }
        
        function playPrevious() {
            if (currentVideoIndex > 0) {
                playVideo(currentVideoIndex - 1);
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            switch(e.key) {
                case 'ArrowDown':
                case 'j':
                    e.preventDefault();
                    playNext();
                    break;
                case 'ArrowUp':
                case 'k':
                    e.preventDefault();
                    playPrevious();
                    break;
                case ' ':
                    e.preventDefault();
                    // This would require YouTube API integration to control playback
                    break;
            }
        });
        
        // Auto-load first video if autoplay is enabled
        <?php if ($autoplay && !empty($playlist_items)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            playVideo(0);
        });
        <?php endif; ?>
        
        // Smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.video-item');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>