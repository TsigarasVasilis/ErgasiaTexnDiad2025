<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Λήψη notifications count αν είναι συνδεδεμένος
$notifications_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../db_connect.php';
        
        // Μετράει νέους followers (τελευταίες 7 ημέρες)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM follows 
            WHERE followed_user_id = ? 
            AND follow_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications_count = $stmt->fetch()['count'];
    } catch (PDOException $e) {
        // Non-critical error
        $notifications_count = 0;
    }
}
?>
<header>
    <div class="header-container">
        <div class="header-left">
            <h1 class="logo">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'profile.php' : 'index.php'; ?>">
                    🎵 Ροή μου
                </a>
            </h1>
        </div>
        
        <nav class="main-nav">
            <ul>
                <li><a href="index.php" class="nav-link">🏠 Αρχική</a></li>
                <li><a href="search_content.php" class="nav-link">🔍 Αναζήτηση</a></li>
                <li><a href="about.php" class="nav-link">ℹ️ Σχετικά</a></li>
                <li><a href="help.php" class="nav-link">❓ Βοήθεια</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            📋 Οι Λίστες μου
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="my_playlists.php" class="dropdown-item">📄 Όλες οι Λίστες</a>
                            <a href="create_playlist.php" class="dropdown-item">➕ Νέα Λίστα</a>
                        </div>
                    </li>
                    
                    <li class="nav-dropdown">
                        <a href="#" class="nav-link dropdown-toggle">
                            👤 <?php echo htmlspecialchars($_SESSION['first_name'] ?? $_SESSION['username']); ?>
                            
                            <span class="dropdown-arrow">▼</span>
                        </a>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">👤 Το Προφίλ μου</a>
                            <a href="follow_management.php" class="dropdown-item">
                                👥 Follows & Followers                          
                            <a href="edit_profile.php" class="dropdown-item">⚙️ Ρυθμίσεις</a>
                            <div class="dropdown-divider"></div>
                            <a href="export_yaml.php" class="dropdown-item">📊 Εξαγωγή YAML</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item logout">🚪 Αποσύνδεση</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="register.php" class="nav-link register-btn">📝 Εγγραφή</a></li>
                    <li><a href="login.php" class="nav-link login-btn">🔑 Σύνδεση</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="header-right">
            <button id="theme-toggle-button" class="theme-toggle">
                <span class="theme-icon">🌙</span>
                <span class="theme-text">Θέμα</span>
            </button>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <nav class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-content">
            <a href="index.php" class="mobile-nav-item">🏠 Αρχική</a>
            <a href="search_content.php" class="mobile-nav-item">🔍 Αναζήτηση</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="mobile-nav-section">
                    <div class="mobile-nav-title">Οι Λίστες μου</div>
                    <a href="my_playlists.php" class="mobile-nav-item">📄 Όλες οι Λίστες</a>
                    <a href="create_playlist.php" class="mobile-nav-item">➕ Νέα Λίστα</a>
                </div>
                
                <div class="mobile-nav-section">
                    <div class="mobile-nav-title">
                        Προφίλ

                    </div>
                    <a href="profile.php" class="mobile-nav-item">👤 Το Προφίλ μου</a>
                    <a href="follow_management.php" class="mobile-nav-item">
                        👥 Follows & Followers
                
                    <a href="edit_profile.php" class="mobile-nav-item">⚙️ Ρυθμίσεις</a>
                    <a href="export_yaml.php" class="mobile-nav-item">📊 Εξαγωγή YAML</a>
                </div>
            <?php else: ?>
                <a href="register.php" class="mobile-nav-item register">📝 Εγγραφή</a>
                <a href="login.php" class="mobile-nav-item login">🔑 Σύνδεση</a>
            <?php endif; ?>
            
            <div class="mobile-nav-section">
                <a href="about.php" class="mobile-nav-item">ℹ️ Σχετικά</a>
                <a href="help.php" class="mobile-nav-item">❓ Βοήθεια</a>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="mobile-nav-item logout">🚪 Αποσύνδεση</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<style>
header {
    background: linear-gradient(135deg, var(--header-bg) 0%, var(--current-accordion-header-bg) 100%);
    color: var(--header-text);
    padding: 0;
    border-bottom: 1px solid var(--current-border-color);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
}

.header-left {
    flex-shrink: 0;
}

.logo {
    margin: 0;
    font-size: 1.8em;
    font-weight: bold;
}

.logo a {
    color: var(--header-text);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.3s ease;
}

.logo a:hover {
    color: var(--nav-link);
}

.main-nav {
    flex-grow: 1;
    display: flex;
    justify-content: center;
}

.main-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.main-nav li {
    position: relative;
}

.nav-link {
    color: var(--nav-link);
    text-decoration: none;
    font-weight: 500;
    padding: 10px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.nav-link:hover {
    color: var(--nav-link-hover);
    background-color: var(--current-accordion-header-bg);
    transform: translateY(-1px);
}

.register-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white !important;
    margin-left: 5px;
}

.register-btn:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    color: white !important;
}

.login-btn {
    background: linear-gradient(135deg, var(--button-bg) 0%, var(--nav-link) 100%);
    color: var(--button-text) !important;
}

.login-btn:hover {
    background: linear-gradient(135deg, var(--button-hover-bg) 0%, var(--nav-link-hover) 100%);
    color: var(--button-text) !important;
}

.nav-dropdown {
    position: relative;
}

.dropdown-toggle {
    cursor: pointer;
}

.dropdown-arrow {
    margin-left: 4px;
    font-size: 0.8em;
    transition: transform 0.3s ease;
}

.nav-dropdown:hover .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--current-accordion-content-bg);
    border: 1px solid var(--current-border-color);
    border-radius: 10px;
    min-width: 200px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
    overflow: hidden;
}

.nav-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    color: var(--text-color);
    text-decoration: none;
    transition: background-color 0.3s ease;
    font-size: 0.9em;
}

.dropdown-item:hover {
    background-color: var(--current-accordion-header-bg);
    color: var(--nav-link);
}

.dropdown-item.logout {
    color: #dc3545;
}

.dropdown-item.logout:hover {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.dropdown-divider {
    height: 1px;
    background-color: var(--current-border-color);
    margin: 8px 0;
}


@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.header-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.theme-toggle {
    background-color: var(--current-accordion-header-bg);
    color: var(--text-color);
    border: 1px solid var(--current-border-color);
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    font-size: 0.9em;
}

.theme-toggle:hover {
    background-color: var(--current-border-color);
    transform: translateY(-1px);
}

.theme-icon {
    font-size: 1.1em;
}

.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    gap: 3px;
}

.mobile-menu-toggle span {
    width: 20px;
    height: 2px;
    background-color: var(--text-color);
    transition: all 0.3s ease;
}

.mobile-nav {
    display: none;
    background-color: var(--current-accordion-content-bg);
    border-top: 1px solid var(--current-border-color);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.mobile-nav-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.mobile-nav-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    color: var(--text-color);
    text-decoration: none;
    border-radius: 8px;
    margin-bottom: 4px;
    transition: background-color 0.3s ease;
}

.mobile-nav-item:hover {
    background-color: var(--current-accordion-header-bg);
    color: var(--nav-link);
}

.mobile-nav-item.register {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    margin-top: 10px;
}

.mobile-nav-item.login {
    background: linear-gradient(135deg, var(--button-bg) 0%, var(--nav-link) 100%);
    color: var(--button-text);
}

.mobile-nav-item.logout {
    color: #dc3545;
    margin-top: 10px;
}

.mobile-nav-section {
    margin: 20px 0;
    padding-top: 15px;
    border-top: 1px solid var(--current-border-color);
}

.mobile-nav-title {
    font-weight: bold;
    color: var(--current-accordion-header-text);
    margin-bottom: 10px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Mobile Responsiveness */
@media (max-width: 1024px) {
    .header-container {
        padding: 12px 15px;
    }
    
    .main-nav ul {
        gap: 2px;
    }
    
    .nav-link {
        padding: 8px 10px;
        font-size: 0.9em;
    }
    
    .theme-text {
        display: none;
    }
}

@media (max-width: 768px) {
    .main-nav {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .mobile-nav.active {
        display: block;
    }
    
    .logo {
        font-size: 1.5em;
    }
    
    .header-container {
        padding: 10px 15px;
    }
}

/* Dark theme specific adjustments */
body.dark-theme .theme-icon::before {
    content: '☀️';
}

body.light-theme .theme-icon::before {
    content: '🌙';
}
</style>

<script>
function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    mobileNav.classList.toggle('active');
    toggle.classList.toggle('active');
    
    // Animate hamburger menu
    const spans = toggle.querySelectorAll('span');
    if (toggle.classList.contains('active')) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
    } else {
        spans[0].style.transform = 'none';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'none';
    }
}

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const mobileNav = document.getElementById('mobileNav');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (!toggle.contains(e.target) && !mobileNav.contains(e.target)) {
        mobileNav.classList.remove('active');
        toggle.classList.remove('active');
        
        const spans = toggle.querySelectorAll('span');
        spans[0].style.transform = 'none';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'none';
    }
});

// Update theme icon
document.addEventListener('DOMContentLoaded', function() {
    const themeIcon = document.querySelector('.theme-icon');
    const isDark = document.body.classList.contains('dark-theme');
    themeIcon.textContent = isDark ? '☀️' : '🌙';
});
</script>