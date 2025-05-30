<?php
// ΑΡΧΕΙΟ: index.php
// ΔΙΑΔΡΟΜΗ: /index.php (ρίζα του φακέλου)
// ΠΕΡΙΓΡΑΦΗ: Modern homepage με YouTube-style design

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'php/db_connect.php';

// Λήψη στατιστικών για το hero section
$stats = [
    'total_users' => 0,
    'total_playlists' => 0,
    'total_videos' => 0,
    'public_playlists' => 0
];

try {
    // Συνολικοί χρήστες
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Συνολικές λίστες
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM playlists");
    $stats['total_playlists'] = $stmt->fetch()['count'];
    
    // Δημόσιες λίστες
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM playlists WHERE is_public = 1");
    $stats['public_playlists'] = $stmt->fetch()['count'];
    
    // Συνολικά βίντεο
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM playlist_items");
    $stats['total_videos'] = $stmt->fetch()['count'];
} catch (PDOException $e) {
    // Αν υπάρχει πρόβλημα με τη βάση, χρησιμοποίησε default values
}

// Λήψη δημοφιλών δημόσιων λιστών
$featured_playlists = [];
try {
    $stmt = $pdo->query("
        SELECT p.playlist_id, p.playlist_name, p.creation_date,
               u.first_name, u.last_name, u.username,
               COUNT(pi.item_id) as video_count
        FROM playlists p 
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN playlist_items pi ON p.playlist_id = pi.playlist_id
        WHERE p.is_public = 1
        GROUP BY p.playlist_id
        HAVING video_count > 0
        ORDER BY video_count DESC, p.creation_date DESC 
        LIMIT 6
    ");
    $featured_playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Non-critical error
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ροή μου - Η Πλατφόρμα Περιεχομένου σας</title>
    <meta name="description" content="Δημιουργήστε, οργανώστε και μοιραστείτε τις αγαπημένες σας λίστες περιεχομένου από το YouTube. Ανακαλύψτε νέο περιεχόμενο και ακολουθήστε άλλους δημιουργούς.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        /* Homepage Specific Styles */
        .homepage-container {
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, 
                var(--nav-link) 0%, 
                var(--button-bg) 50%, 
                #6f42c1 100%);
            color: white;
            padding: 80px 0 120px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="50" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="30" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .hero-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            font-size: 3.5em;
            font-weight: bold;
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        .youtube-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 8px 25px rgba(255, 0, 0, 0.3);
        }
        
        .youtube-logo::after {
            content: '';
            width: 0;
            height: 0;
            border-left: 20px solid white;
            border-top: 12px solid transparent;
            border-bottom: 12px solid transparent;
            margin-left: 4px;
        }
        
        .hero-title {
            font-size: 3.2em;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3em;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.4;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-cta {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }
        
        .cta-button {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 200px;
            justify-content: center;
        }
        
        .cta-primary {
            background: white;
            color: var(--nav-link);
            box-shadow: 0 4px 15px rgba(255,255,255,0.3);
        }
        
        .cta-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255,255,255,0.4);
            color: var(--nav-link);
        }
        
        .cta-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        
        .cta-secondary:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-3px);
            color: white;
        }
        
        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 2.5em;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 8px;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            font-size: 1em;
            opacity: 0.9;
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background-color: var(--bg-color);
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--current-accordion-header-text);
        }
        
        .section-subtitle {
            font-size: 1.2em;
            color: var(--text-color);
            opacity: 0.8;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
        }
        
        .feature-card {
            background: linear-gradient(135deg, var(--current-accordion-content-bg) 0%, var(--current-accordion-header-bg) 100%);
            border: 1px solid var(--current-border-color);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--nav-link), var(--button-bg));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            font-size: 3em;
            margin-bottom: 20px;
            display: block;
        }
        
        .feature-title {
            font-size: 1.4em;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--current-accordion-header-text);
        }
        
        .feature-description {
            color: var(--text-color);
            opacity: 0.8;
            line-height: 1.6;
        }
        
        /* Featured Playlists Section */
        .featured-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--current-accordion-content-bg) 100%);
        }
        
        .featured-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .playlists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .playlist-card {
            background-color: var(--bg-color);
            border: 1px solid var(--current-border-color);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .playlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .playlist-thumbnail {
            height: 180px;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3em;
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
        
        .play-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--nav-link);
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .play-icon:hover {
            transform: scale(1.1);
        }
        
        .playlist-info {
            padding: 20px;
        }
        
        .playlist-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--current-accordion-header-text);
            line-height: 1.3;
        }
        
        .playlist-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .playlist-title a:hover {
            color: var(--nav-link);
        }
        
        .playlist-creator {
            color: var(--nav-link);
            font-size: 0.9em;
            margin-bottom: 8px;
        }
        
        .playlist-stats {
            color: var(--text-color);
            opacity: 0.7;
            font-size: 0.85em;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
            color: white;
            text-align: center;
        }
        
        .cta-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .cta-title {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .cta-description {
            font-size: 1.2em;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        /* Messages */
        .success-message {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(21,87,36,0.1);
        }
        
        /* Animations */
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2em;
            }
            
            .hero-subtitle {
                font-size: 1.1em;
            }
            
            .hero-cta {
                flex-direction: column;
                align-items: center;
            }
            
            .cta-button {
                width: 100%;
                max-width: 300px;
            }
            
            .hero-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .playlists-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 2em;
            }
            
            .youtube-logo {
                width: 60px;
                height: 60px;
            }
            
            .hero-logo {
                font-size: 2.5em;
            }
        }
        
        @media (max-width: 480px) {
            .hero-section {
                padding: 60px 0 80px 0;
            }
            
            .features-section,
            .featured-section,
            .cta-section {
                padding: 60px 0;
            }
            
            .hero-title {
                font-size: 1.8em;
            }
            
            .stat-number {
                font-size: 2em;
            }
        }
    </style>
</head>
<body class="homepage-container">
    <?php include 'php/partials/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-logo">
                <div class="youtube-logo"></div>
                <span>Ροή μου</span>
            </div>
            
            <h1 class="hero-title">Δημιουργήστε τη δική σας<br>πλατφόρμα περιεχομένου</h1>
            
            <p class="hero-subtitle">
                Οργανώστε τα αγαπημένα σας βίντεο από το YouTube, δημιουργήστε εκπληκτικές λίστες 
                και μοιραστείτε τις με τον κόσμο. Ανακαλύψτε νέο περιεχόμενο και συνδεθείτε με άλλους δημιουργούς.
            </p>
            
            <div class="hero-cta">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="cta-button cta-primary">
                        <span>👤</span> Το Προφίλ μου
                    </a>
                    <a href="create_playlist.php" class="cta-button cta-secondary">
                        <span>➕</span> Νέα Λίστα
                    </a>
                <?php else: ?>
                    <a href="register.php" class="cta-button cta-primary">
                        <span>🚀</span> Ξεκινήστε Δωρεάν
                    </a>
                    <a href="search_content.php?show_public=true" class="cta-button cta-secondary">
                        <span>🔍</span> Εξερευνήστε
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($stats['total_users'] > 0): ?>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['total_users']); ?></span>
                        <div class="stat-label">Εγγεγραμμένοι Χρήστες</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['public_playlists']); ?></span>
                        <div class="stat-label">Δημόσιες Λίστες</div>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($stats['total_videos']); ?></span>
                        <div class="stat-label">Βίντεο Συλλογής</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Success Messages -->
    <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
        <div class="success-message fade-in-up">
            ✅ Έχετε αποσυνδεθεί με επιτυχία. Ευχαριστούμε που χρησιμοποιήσατε την πλατφόρμα μας!
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] == 'profile_deleted_successfully'): ?>
        <div class="success-message fade-in-up">
            ✅ Το προφίλ σας διαγράφηκε επιτυχώς. Ελπίζουμε να σας δούμε ξανά στο μέλλον!
        </div>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="features-section">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title">Γιατί να επιλέξετε το Ροή μου;</h2>
                <p class="section-subtitle">
                    Μια πλατφόρμα που σας δίνει τον πλήρη έλεγχο του περιεχομένου σας
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <span class="feature-icon">🎵</span>
                    <h3 class="feature-title">Οργανώστε το Περιεχόμενό σας</h3>
                    <p class="feature-description">
                        Δημιουργήστε προσωπικές λίστες με τα αγαπημένα σας βίντεο από το YouTube. 
                        Κατηγοριοποιήστε, ταξινομήστε και διαχειριστείτε το περιεχόμενό σας όπως εσείς θέλετε.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">👥</span>
                    <h3 class="feature-title">Συνδεθείτε με Άλλους</h3>
                    <p class="feature-description">
                        Ακολουθήστε άλλους δημιουργούς, ανακαλύψτε νέο περιεχόμενο και μοιραστείτε 
                        τις δημόσιες λίστες σας με την κοινότητα.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🔒</span>
                    <h3 class="feature-title">Πλήρης Έλεγχος Ιδιωτικότητας</h3>
                    <p class="feature-description">
                        Επιλέξτε ποιες λίστες θα είναι δημόσιες και ποιες ιδιωτικές. 
                        Ο έλεγχος είναι στα χέρια σας.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">📱</span>
                    <h3 class="feature-title">Τέλεια σε Όλες τις Συσκευές</h3>
                    <p class="feature-description">
                        Αποκτήστε πρόσβαση στις λίστες σας από οπουδήποτε. 
                        Responsive design που λειτουργεί άψογα σε desktop, tablet και mobile.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">🚀</span>
                    <h3 class="feature-title">Γρήγορο & Εύκολο</h3>
                    <p class="feature-description">
                        Απλή εγγραφή, διαισθητικό interface και άμεση αναπαραγωγή. 
                        Ξεκινήστε να δημιουργείτε σε λίγα δευτερόλεπτα.
                    </p>
                </div>
                
                <div class="feature-card">
                    <span class="feature-icon">📊</span>
                    <h3 class="feature-title">Ανοιχτά Δεδομένα</h3>
                    <p class="feature-description">
                        Εξάγετε τα δεδομένα σας σε μορφή YAML. 
                        Διαφάνεια και ελευθερία στη διαχείριση του περιεχομένου σας.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Playlists Section -->
    <?php if (!empty($featured_playlists)): ?>
        <section class="featured-section">
            <div class="featured-container">
                <div class="section-header">
                    <h2 class="section-title">Δημοφιλείς Λίστες</h2>
                    <p class="section-subtitle">
                        Ανακαλύψτε τις πιο δημοφιλείς λίστες από την κοινότητά μας
                    </p>
                </div>
                
                <div class="playlists-grid">
                    <?php foreach ($featured_playlists as $playlist): ?>
                        <div class="playlist-card">
                            <div class="playlist-thumbnail">
                                📺
                                <div class="playlist-overlay">
                                    <div class="play-icon" onclick="window.location.href='view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>&autoplay=1'">
                                        ▶️
                                    </div>
                                </div>
                            </div>
                            <div class="playlist-info">
                                <h3 class="playlist-title">
                                    <a href="view_playlist_items.php?playlist_id=<?php echo $playlist['playlist_id']; ?>">
                                        <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                                    </a>
                                </h3>
                                <div class="playlist-creator">
                                    από <?php echo htmlspecialchars($playlist['first_name'] . ' ' . $playlist['last_name']); ?>
                                </div>
                                <div class="playlist-stats">
                                    <?php echo $playlist['video_count']; ?> βίντεο • 
                                    <?php 
                                        $date = new DateTime($playlist['creation_date']);
                                        echo $date->format('M Y'); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 40px;">
                    <a href="search_content.php?show_public=true" class="cta-button cta-primary">
                        <span>🔍</span> Δείτε Όλες τις Λίστες
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2 class="cta-title">Έτοιμοι να Ξεκινήσετε;</h2>
            <p class="cta-description">
                Μπείτε στην κοινότητά μας και δημιουργήστε εκπληκτικές λίστες περιεχομένου σήμερα!
            </p>
            
            <div class="hero-cta">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="cta-button cta-primary">
                        <span>📝</span> Εγγραφή Δωρεάν
                    </a>
                    <a href="login.php" class="cta-button cta-secondary">
                        <span>🔑</span> Σύνδεση
                    </a>
                <?php else: ?>
                    <a href="create_playlist.php" class="cta-button cta-primary">
                        <span>➕</span> Δημιουργία Πρώτης Λίστας
                    </a>
                    <a href="search_content.php" class="cta-button cta-secondary">
                        <span>🔍</span> Εξερεύνηση Περιεχομένου
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'php/partials/footer.php'; ?>
    
    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.feature-card, .playlist-card, .section-header');
            animatedElements.forEach(el => {
                observer.observe(el);
            });

            // Stagger animation for feature cards
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });

            // Stagger animation for playlist cards
            const playlistCards = document.querySelectorAll('.playlist-card');
            playlistCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Enhanced CTA button interactions
        document.querySelectorAll('.cta-button').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>