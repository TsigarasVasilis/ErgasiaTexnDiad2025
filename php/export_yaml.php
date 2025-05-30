<?php
// export_yaml.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'php/db_connect.php';

$export_data = [];
$error_message = '';
$download_requested = isset($_GET['download']) && $_GET['download'] == '1';

try {
    // Λήψη όλων των δημόσιων λιστών με τα περιεχόμενά τους
    $stmt = $pdo->query("
        SELECT 
            p.playlist_id,
            p.playlist_name,
            p.creation_date,
            u.username,
            u.first_name,
            u.last_name,
            u.user_id
        FROM playlists p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.is_public = 1 
        ORDER BY p.creation_date DESC
    ");
    
    $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($playlists as $playlist) {
        // Δημιουργία μοναδικού αναγνωριστικού (hash) για privacy
        $user_hash = hash('sha256', $playlist['user_id'] . $playlist['username']);
        
        // Λήψη περιεχομένων λίστας
        $items_stmt = $pdo->prepare("
            SELECT 
                video_id,
                video_title,
                added_date
            FROM playlist_items 
            WHERE playlist_id = ? 
            ORDER BY added_date ASC
        ");
        $items_stmt->execute([$playlist['playlist_id']]);
        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Προετοιμασία δεδομένων για YAML
        $playlist_data = [
            'id' => $playlist['playlist_id'],
            'name' => $playlist['playlist_name'],
            'created' => $playlist['creation_date'],
            'creator' => [
                'hash_id' => $user_hash,
                'display_name' => $playlist['first_name'] . ' ' . $playlist['last_name']
            ],
            'video_count' => count($items),
            'videos' => []
        ];
        
        foreach ($items as $item) {
            $playlist_data['videos'][] = [
                'youtube_id' => $item['video_id'],
                'title' => $item['video_title'],
                'added_date' => $item['added_date'],
                'youtube_url' => 'https://www.youtube.com/watch?v=' . $item['video_id']
            ];
        }
        
        $export_data[] = $playlist_data;
    }
    
} catch (PDOException $e) {
    $error_message = "Σφάλμα κατά την εξαγωγή δεδομένων: " . $e->getMessage();
}

// Δημιουργία YAML content
function arrayToYaml($array, $indent = 0) {
    $yaml = '';
    $spaces = str_repeat('  ', $indent);
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (array_keys($value) === array_keys(array_keys($value))) {
                // Indexed array (list)
                $yaml .= $spaces . $key . ":\n";
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $yaml .= $spaces . "  -\n";
                        $yaml .= arrayToYaml($item, $indent + 2);
                    } else {
                        $yaml .= $spaces . "  - " . yamlEscape($item) . "\n";
                    }
                }
            } else {
                // Associative array (object)
                $yaml .= $spaces . $key . ":\n";
                $yaml .= arrayToYaml($value, $indent + 1);
            }
        } else {
            $yaml .= $spaces . $key . ": " . yamlEscape($value) . "\n";
        }
    }
    
    return $yaml;
}

function yamlEscape($value) {
    if (is_null($value)) {
        return 'null';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_numeric($value)) {
        return $value;
    }
    
    // Escape special characters and wrap in quotes if necessary
    $str = (string)$value;
    if (preg_match('/[:\-\[\]{}|>]|^\s|\s$/', $str) || empty($str)) {
        return '"' . str_replace('"', '\"', $str) . '"';
    }
    
    return $str;
}

// Αν ζητήθηκε download, στείλε το αρχείο
if ($download_requested && !$error_message) {
    $yaml_content = "# Ροή μου - Open Data Export\n";
    $yaml_content .= "# Εξαγωγή δημόσιων λιστών περιεχομένου\n";
    $yaml_content .= "# Ημερομηνία εξαγωγής: " . date('Y-m-d H:i:s') . "\n";
    $yaml_content .= "# Συνολικές λίστες: " . count($export_data) . "\n\n";
    $yaml_content .= "playlists:\n";
    
    foreach ($export_data as $playlist) {
        $yaml_content .= "  -\n";
        $yaml_content .= arrayToYaml($playlist, 2);
        $yaml_content .= "\n";
    }
    
    // Set headers for download
    header('Content-Type: application/x-yaml');
    header('Content-Disposition: attachment; filename="roimi_open_data_' . date('Y-m-d') . '.yaml"');
    header('Content-Length: ' . strlen($yaml_content));
    
    echo $yaml_content;
    exit;
}

// Στατιστικά για την preview
$total_playlists = count($export_data);
$total_videos = array_sum(array_column($export_data, 'video_count'));
$creators_count = count(array_unique(array_column(array_column($export_data, 'creator'), 'hash_id')));
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Εξαγωγή Open Data - Ροή μου</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        .export-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .info-box {
            background-color: var(--current-accordion-header-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .info-box h3 {
            margin-top: 0;
            color: var(--current-accordion-header-text);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--nav-link);
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: var(--text-color);
            opacity: 0.8;
        }
        
        .privacy-notice {
            background-color: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .privacy-notice h4 {
            margin-top: 0;
            color: #0066cc;
        }
        
        .download-section {
            text-align: center;
            padding: 30px;
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 8px;
            margin: 25px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--button-bg);
            color: var(--button-text);
            text-decoration: none;
            border-radius: 4px;
            font-size: 1.1em;
            font-weight: bold;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: var(--button-hover-bg);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #545b62;
        }
        
        .preview-section {
            margin-top: 30px;
        }
        
        .yaml-preview {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .sample-data {
            margin-top: 20px;
        }
        
        .sample-item {
            background-color: var(--current-accordion-content-bg);
            border: 1px solid var(--current-border-color);
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include 'php/partials/header.php'; ?>

    <main>
        <div class="export-container">
            <h2>📊 Εξαγωγή Open Data</h2>

            <div class="info-box">
                <h3>🌐 Τι είναι τα Open Data;</h3>
                <p>Τα Open Data είναι δεδομένα που είναι διαθέσιμα για χρήση από οποιονδήποτε, χωρίς περιορισμούς από πνευματικά δικαιώματα, διπλώματα ευρεσιτεχνίας ή άλλους μηχανισμούς ελέγχου.</p>
                <p>Εδώ μπορείτε να κατεβάσετε όλες τις δημόσιες λίστες περιεχομένου σε μορφή YAML για περαιτέρω ανάλυση και επεξεργασία.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $total_playlists; ?></span>
                        <div class="stat-label">Δημόσιες Λίστες</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $total_videos; ?></span>
                        <div class="stat-label">Συνολικά Βίντεο</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $creators_count; ?></span>
                        <div class="stat-label">Δημιουργοί</div>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number">YAML</span>
                        <div class="stat-label">Μορφή Αρχείου</div>
                    </div>
                </div>

                <div class="privacy-notice">
                    <h4>🔒 Προστασία Ιδιωτικότητας</h4>
                    <p>Για την προστασία της ιδιωτικότητας των χρηστών:</p>
                    <ul>
                        <li>Αντί για πραγματικά ονόματα χρηστών, χρησιμοποιούνται μοναδικά hash identifiers</li>
                        <li>Εξάγονται μόνο δημόσιες λίστες περιεχομένου</li>
                        <li>Δεν περιλαμβάνονται email addresses ή άλλα προσωπικά στοιχεία</li>
                        <li>Εμφανίζονται μόνο display names (πρώτο όνομα + επώνυμο)</li>
                    </ul>
                </div>

                <div class="download-section">
                    <h3>💾 Κατέβασμα Δεδομένων</h3>
                    <p>Κατεβάστε όλα τα δημόσια δεδομένα σε μορφή YAML</p>
                    <a href="export_yaml.php?download=1" class="btn">📥 Κατέβασμα YAML Αρχείου</a>
                    <p style="margin-top: 15px; font-size: 0.9em; opacity: 0.8;">
                        Αρχείο: roimi_open_data_<?php echo date('Y-m-d'); ?>.yaml
                    </p>
                </div>

                <?php if (!empty($export_data)): ?>
                    <div class="preview-section">
                        <h3>👁️ Προεπισκόπηση Δεδομένων</h3>
                        
                        <div class="sample-data">
                            <h4>Δείγμα από τις πρώτες 3 λίστες:</h4>
                            <?php for ($i = 0; $i < min(3, count($export_data)); $i++): ?>
                                <div class="sample-item">
                                    <strong><?php echo htmlspecialchars($export_data[$i]['name']); ?></strong><br>
                                    <small>
                                        Από: <?php echo htmlspecialchars($export_data[$i]['creator']['display_name']); ?> | 
                                        Βίντεο: <?php echo $export_data[$i]['video_count']; ?> | 
                                        Δημιουργήθηκε: <?php echo htmlspecialchars($export_data[$i]['created']); ?>
                                    </small>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <h4>Δείγμα YAML Structure:</h4>
                        <div class="yaml-preview"># Ροή μου - Open Data Export
# Εξαγωγή δημόσιων λιστών περιεχομένου  
# Ημερομηνία εξαγωγής: <?php echo date('Y-m-d H:i:s'); ?>

playlists:
  - id: 1
    name: "Τα Καλύτερα Τραγούδια 2024"
    created: "2024-01-15 10:30:00"
    creator:
      hash_id: "a1b2c3d4e5f6..."
      display_name: "Γιάννης Παπαδόπουλος"
    video_count: 15
    videos:
      - youtube_id: "dQw4w9WgXcQ"
        title: "Amazing Song Title"
        added_date: "2024-01-16 14:20:00"
        youtube_url: "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
      # ... περισσότερα βίντεο
  # ... περισσότερες λίστες</div>
                    </div>
                <?php else: ?>
                    <div class="info-box">
                        <h3>📭 Δεν υπάρχουν δεδομένα</h3>
                        <p>Δεν υπάρχουν δημόσιες λίστες για εξαγωγή αυτή τη στιγμή.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div style="margin-top: 30px; text-align: center;">
                <a href="search_content.php" class="btn btn-secondary">🔍 Περιήγηση στις Λίστες</a>
            </div>
        </div>
    </main>

    <?php include 'php/partials/footer.php'; ?>

    <script src="js/theme_switcher.js"></script>
    <script src="js/main.js"></script>
</body>
</html>