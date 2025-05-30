<?php
// add_video.php - Manual video addition only
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php?message=login_required_for_Youtube');
    exit;
}

require_once 'php/db_connect.php';

$playlist_id = $_GET['playlist_id'] ?? 0;
$playlist = null;
$errors = [];
$success_message = '';

if (!$playlist_id) {
    header('Location: my_playlists.php');
    exit;
}

// Έλεγχος αν η λίστα ανήκει στον τρέχοντα χρήστη
try {
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE playlist_id = ? AND user_id = ?");
    $stmt->execute([$playlist_id, $_SESSION['user_id']]);
    $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$playlist) {
        header('Location: my_playlists.php');
        exit;
    }
} catch (PDOException $e) {
    $errors['db_error'] = "Σφάλμα κατά την ανάκτηση της λίστας: " . $e->getMessage();
}

// Προσθήκη βίντεο στη λίστα
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_video'])) {
    $video_id = trim($_POST['video_id'] ?? '');
    $video_title = trim($_POST['video_title'] ?? '');
    $video_thumbnail = trim($_POST['video_thumbnail'] ?? '');
    
    if (empty($video_id)) {
        $errors['add_video'] = "Το ID του βίντεο είναι υποχρεωτικό.";
    } elseif (empty($video_title)) {
        $errors['add_video'] = "Ο τίτλος του βίντεο είναι υποχρεωτικός.";
    } else {
        // Έλεγχος αν το βίντεο υπάρχει ήδη στη λίστα
        try {
            $stmt = $pdo->prepare("SELECT item_id FROM playlist_items WHERE playlist_id = ? AND video_id = ?");
            $stmt->execute([$playlist_id, $video_id]);
            
            if ($stmt->fetch()) {
                $errors['add_video'] = "Αυτό το βίντεο υπάρχει ήδη στη λίστα.";
            } else {
                // Προσθήκη βίντεο στη λίστα
                $stmt = $pdo->prepare("INSERT INTO playlist_items (playlist_id, video_id, video_title, video_thumbnail) VALUES (?, ?, ?, ?)");
                $stmt->execute([$playlist_id, $video_id, $video_title, $video_thumbnail]);
                
                $success_message = "Το βίντεο προστέθηκε με επιτυχία στη λίστα!";
            }
        } catch (PDOException $e) {
            $errors['add_video'] = "Σφάλμα κατά την προσθήκη του βίντεο: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη Βίντεο - <?php echo htmlspecialchars($playlist['playlist_name'] ?? 'Λίστα'); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .add-video-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .playlist-info {
            background-color: var(--current-accordion-header-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .manual-add {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--current-accordion-header-text);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--current-border-color);
            border-radius: 4px;
            background-color: var(--bg-color);
            color: var(--text-color);
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--nav-link);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
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
        
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
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
        
        .help-section {
            background-color: #e7f3ff;
            border: 1px solid #b6d7ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .help-section h4 {
            margin-top: 0;
            color: #0066cc;
        }
        
        .help-section p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .help-section ol {
            margin-left: 20px;
        }
        
        .help-section li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .example-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 0.9em;
        }
        
        .stats-section {
            background-color: var(--current-accordion-header-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.8em;
            color: var(--text-color);
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="add-video-container">
            <div class="back-link">
                <a href="view_playlist_items.php?playlist_id=<?php echo $playlist_id; ?>">← Επιστροφή στη Λίστα</a>
            </div>

            <?php if ($playlist): ?>
                <div class="playlist-info">
                    <h2>🎥 Προσθήκη Βίντεο στη Λίστα</h2>
                    <p><strong>Λίστα:</strong> <?php echo htmlspecialchars($playlist['playlist_name']); ?></p>
                    <p><strong>Τύπος:</strong> <?php echo $playlist['is_public'] ? 'Δημόσια' : 'Ιδιωτική'; ?></p>
                </div>

                <?php if ($success_message): ?>
                    <div class="success-message">✅ <?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <?php echo htmlspecialchars($error); ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Οδηγίες Χρήσης -->
                <div class="help-section">
                    <h4>🆔 Πώς να βρείτε το Video ID από το YouTube</h4>
                    <p>Για να προσθέσετε ένα βίντεο, χρειάζεστε το <strong>Video ID</strong> του βίντεο από το YouTube:</p>
                    <ol>
                        <li>Μεταβείτε στο βίντεο στο YouTube</li>
                        <li>Αντιγράψτε το URL από τη γραμμή διευθύνσεων</li>
                        <li>Το Video ID είναι το κομμάτι μετά το "v=" ή μετά το "youtu.be/"</li>
                    </ol>
                    
                    <p><strong>Παραδείγματα:</strong></p>
                    <div class="example-box">
                        URL: https://www.youtube.com/watch?v=dQw4w9WgXcQ<br>
                        Video ID: <strong>dQw4w9WgXcQ</strong><br><br>
                        
                        URL: https://youtu.be/ZZ5LpwO-An4<br>
                        Video ID: <strong>ZZ5LpwO-An4</strong>
                    </div>
                    
                    <p>💡 <strong>Συμβουλή:</strong> Μπορείτε να βάλετε ολόκληρο το URL - το σύστημα θα εξάγει αυτόματα το Video ID!</p>
                </div>

                <!-- Στατιστικά Λίστας -->
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as video_count FROM playlist_items WHERE playlist_id = ?");
                    $stmt->execute([$playlist_id]);
                    $video_count = $stmt->fetch()['video_count'];
                    
                    $stmt = $pdo->prepare("SELECT MIN(added_date) as first_added, MAX(added_date) as last_added FROM playlist_items WHERE playlist_id = ?");
                    $stmt->execute([$playlist_id]);
                    $dates = $stmt->fetch();
                    ?>
                    <div class="stats-section">
                        <h3>📊 Στατιστικά Λίστας</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $video_count; ?></span>
                                <div class="stat-label">Συνολικά Βίντεο</div>
                            </div>
                            <?php if ($dates['first_added']): ?>
                                <div class="stat-card">
                                    <span class="stat-number"><?php 
                                        $date = new DateTime($dates['first_added']);
                                        echo $date->format('d/m/Y'); 
                                    ?></span>
                                    <div class="stat-label">Πρώτο Βίντεο</div>
                                </div>
                                <div class="stat-card">
                                    <span class="stat-number"><?php 
                                        $date = new DateTime($dates['last_added']);
                                        echo $date->format('d/m/Y'); 
                                    ?></span>
                                    <div class="stat-label">Τελευταίο Βίντεο</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php } catch (PDOException $e) {
                    // Στατιστικά δεν είναι κρίσιμα
                } ?>

                <!-- Φόρμα Προσθήκης Βίντεο -->
                <div class="manual-add">
                    <h3>➕ Προσθήκη Βίντεο από YouTube</h3>
                    <p>Συμπληρώστε τα παρακάτω στοιχεία για να προσθέσετε ένα βίντεο στη λίστα σας:</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="video_id">YouTube Video ID ή URL *</label>
                            <input type="text" id="video_id" name="video_id" 
                                   placeholder="π.χ. dQw4w9WgXcQ ή https://www.youtube.com/watch?v=dQw4w9WgXcQ" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="video_title">Τίτλος Βίντεο *</label>
                            <input type="text" id="video_title" name="video_title" 
                                   placeholder="Εισάγετε τον τίτλο του βίντεο" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="video_thumbnail">Thumbnail URL (προαιρετικό)</label>
                            <input type="url" id="video_thumbnail" name="video_thumbnail" 
                                   placeholder="https://img.youtube.com/vi/VIDEO_ID/mqdefault.jpg">
                        </div>
                        
                        <button type="submit" name="add_video" class="btn btn-success">➕ Προσθήκη Βίντεο</button>
                        <a href="view_playlist_items.php?playlist_id=<?php echo $playlist_id; ?>" class="btn btn-secondary">❌ Ακύρωση</a>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Auto-extract video ID from YouTube URL
        document.getElementById('video_id').addEventListener('input', function() {
            let input = this.value.trim();
            let videoId = '';
            
            // Extract video ID from various YouTube URL formats
            const patterns = [
                /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
                /^([a-zA-Z0-9_-]{11})$/ // Direct video ID
            ];
            
            for (let pattern of patterns) {
                const match = input.match(pattern);
                if (match) {
                    videoId = match[1];
                    break;
                }
            }
            
            if (videoId && videoId !== input) {
                this.value = videoId;
                
                // Auto-generate thumbnail URL if empty
                const thumbnailInput = document.getElementById('video_thumbnail');
                if (!thumbnailInput.value) {
                    thumbnailInput.value = `https://img.youtube.com/vi/${videoId}/mqdefault.jpg`;
                }
                
                // Visual feedback
                this.style.borderColor = '#28a745';
                this.style.backgroundColor = '#f8fff8';
                setTimeout(() => {
                    this.style.borderColor = '';
                    this.style.backgroundColor = '';
                }, 2000);
            }
        });
        
        // Auto-focus on video ID input
        document.addEventListener('DOMContentLoaded', function() {
            const videoIdInput = document.getElementById('video_id');
            if (videoIdInput) {
                videoIdInput.focus();
            }
        });
        
        // Form validation enhancement
        document.querySelector('form').addEventListener('submit', function(e) {
            const videoId = document.getElementById('video_id').value.trim();
            const videoTitle = document.getElementById('video_title').value.trim();
            
            if (!videoId) {
                alert('Παρακαλώ εισάγετε το Video ID ή URL του βίντεο.');
                e.preventDefault();
                return;
            }
            
            if (!videoTitle) {
                alert('Παρακαλώ εισάγετε τον τίτλο του βίντεο.');
                e.preventDefault();
                return;
            }
            
            // Check if it looks like a valid YouTube video ID
            const validId = /^[a-zA-Z0-9_-]{11}$/.test(videoId);
            if (!validId) {
                const confirmAdd = confirm('Το Video ID δεν φαίνεται να είναι έγκυρο. Θέλετε να συνεχίσετε;');
                if (!confirmAdd) {
                    e.preventDefault();
                }
            }
        });
        
        // Copy-paste URL detection
        document.getElementById('video_id').addEventListener('paste', function() {
            // Give paste operation time to complete
            setTimeout(() => {
                this.dispatchEvent(new Event('input'));
            }, 100);
        });
    </script>
</body>
</html>