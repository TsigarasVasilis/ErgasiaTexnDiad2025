<?php
?>
<footer class="modern-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <div class="footer-logo-icon"></div>
                    <span>Ροή μου</span>
                </div>
                <p class="footer-description">
                    Η πλατφόρμα που σας δίνει τον πλήρη έλεγχο του περιεχομένου σας. 
                    Δημιουργήστε, οργανώστε και μοιραστείτε τις αγαπημένες σας λίστες.
                </p>
                <div class="footer-stats">
                    <?php
                    try {
                        require_once __DIR__ . '/../db_connect.php';
                        $stmt = $pdo->query("SELECT COUNT(*) as users FROM users");
                        $users_count = $stmt->fetch()['users'];
                        
                        $stmt = $pdo->query("SELECT COUNT(*) as playlists FROM playlists WHERE is_public = 1");
                        $playlists_count = $stmt->fetch()['playlists'];
                        
                        echo "<span>{$users_count}+ χρήστες</span>";
                        echo "<span>{$playlists_count}+ δημόσιες λίστες</span>";
                    } catch (Exception $e) {
                        echo "<span>Powered by PHP & MySQL</span>";
                    }
                    ?>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Πλατφόρμα</h4>
                <ul class="footer-links">
                    <li><a href="search_content.php?show_public=true">🔍 Εξερεύνηση</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="my_playlists.php">📋 Οι Λίστες μου</a></li>
                        <li><a href="create_playlist.php">➕ Νέα Λίστα</a></li>
                        <li><a href="follow_management.php">👥 Follows</a></li>
                    <?php else: ?>
                        <li><a href="register.php">📝 Εγγραφή</a></li>
                        <li><a href="login.php">🔑 Σύνδεση</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Υποστήριξη</h4>
                <ul class="footer-links">
                    <li><a href="about.php">ℹ️ Σχετικά με εμάς</a></li>
                    <li><a href="help.php">❓ Βοήθεια</a></li>
                    <li><a href="export_yaml.php">📊 Open Data</a></li>
                    <li><a href="#" onclick="showContact()">📧 Επικοινωνία</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Τεχνολογίες</h4>
                <div class="tech-badges">
                    <span class="tech-badge">PHP</span>
                    <span class="tech-badge">MySQL</span>
                    <span class="tech-badge">JavaScript</span>
                    <span class="tech-badge">CSS3</span>
                    <span class="tech-badge">HTML5</span>
                    <span class="tech-badge">YouTube API</span>
                </div>
                <div class="footer-features">
                    <span>🌓 Dark/Light Theme</span>
                    <span>📱 Responsive Design</span>
                    <span>⚡ Fast & Secure</span>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-copyright">
                <p>&copy; <span id="year"></span> Ροή μου - Εργασία Εξαμήνου</p>
                <p class="footer-subtitle">Δημιουργήθηκε με ❤️ για την οργάνωση περιεχομένου</p>
            </div>
            
            <div class="footer-actions">
                <button onclick="scrollToTop()" class="scroll-top-btn" title="Επιστροφή στην κορυφή">
                    ↑
                </button>
                
                <div class="theme-toggle-footer">
                    <button onclick="toggleThemeFromFooter()" class="footer-theme-btn" title="Εναλλαγή θέματος">
                        <span class="theme-icon-footer">🌙</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Contact Modal -->
<div id="contactModal" class="contact-modal" style="display: none;">
    <div class="contact-modal-content">
        <div class="contact-header">
            <h3>📧 Επικοινωνία</h3>
            <button class="close-modal" onclick="hideContact()">×</button>
        </div>
        <div class="contact-body">
            <p>Για οποιαδήποτε ερώτηση ή υποστήριξη:</p>
            <div class="contact-info">
                <div class="contact-item">
                    <span class="contact-icon">📧</span>
                    <span>support@roimi.local</span>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">🌐</span>
                    <span>www.roimi.local</span>
                </div>
                <div class="contact-item">
                    <span class="contact-icon">💬</span>
                    <span>Χρησιμοποιήστε τη σελίδα Βοήθειας</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modern-footer {
    background: linear-gradient(135deg, var(--current-accordion-header-bg) 0%, var(--footer-bg) 100%);
    color: var(--footer-text);
    border-top: 1px solid var(--current-border-color);
    margin-top: auto;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 50px 20px 20px 20px;
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-section h4 {
    color: var(--current-accordion-header-text);
    font-size: 1.1em;
    font-weight: 600;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--nav-link);
    padding-bottom: 8px;
    display: inline-block;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    font-size: 1.5em;
    font-weight: bold;
    color: var(--current-accordion-header-text);
}

.footer-logo-icon {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 2px 8px rgba(255, 0, 0, 0.3);
}

.footer-logo-icon::after {
    content: '';
    width: 0;
    height: 0;
    border-left: 10px solid white;
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
    margin-left: 2px;
}

.footer-description {
    line-height: 1.6;
    margin-bottom: 20px;
    opacity: 0.9;
}

.footer-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.footer-stats span {
    background-color: var(--current-accordion-content-bg);
    padding: 6px 12px;
    border-radius: 15px;
    font-size: 0.85em;
    color: var(--nav-link);
    font-weight: 500;
    display: inline-block;
    width: fit-content;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: var(--footer-text);
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    opacity: 0.8;
}

.footer-links a:hover {
    color: var(--nav-link);
    opacity: 1;
    transform: translateX(5px);
}

.tech-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 15px;
}

.tech-badge {
    background: linear-gradient(135deg, var(--nav-link) 0%, var(--button-bg) 100%);
    color: white;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.75em;
    font-weight: 500;
}

.footer-features {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.footer-features span {
    font-size: 0.85em;
    opacity: 0.8;
}

.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 30px;
    border-top: 1px solid var(--current-border-color);
    flex-wrap: wrap;
    gap: 20px;
}

.footer-copyright p {
    margin: 0;
    font-size: 0.9em;
}

.footer-subtitle {
    opacity: 0.7;
    font-size: 0.8em !important;
    margin-top: 5px !important;
}

.footer-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.scroll-top-btn,
.footer-theme-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, var(--button-bg) 0%, var(--nav-link) 100%);
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2em;
}

.scroll-top-btn:hover,
.footer-theme-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,123,255,0.4);
}

/* Contact Modal */
.contact-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

.contact-modal-content {
    background: var(--current-accordion-content-bg);
    border: 1px solid var(--current-border-color);
    border-radius: 15px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.contact-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--current-border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.contact-header h3 {
    margin: 0;
    color: var(--current-accordion-header-text);
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-color);
    opacity: 0.7;
}

.close-modal:hover {
    opacity: 1;
}

.contact-body {
    padding: 25px;
}

.contact-info {
    margin-top: 20px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    padding: 10px;
    background-color: var(--current-accordion-header-bg);
    border-radius: 8px;
}

.contact-icon {
    font-size: 1.2em;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .footer-content {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .footer-container {
        padding: 40px 15px 15px 15px;
    }
    
    .tech-badges {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .footer-container {
        padding: 30px 10px 10px 10px;
    }
    
    .contact-modal-content {
        width: 95%;
        margin: 10px;
    }
}

/* Dark theme adjustments */
body.dark-theme .footer-theme-btn .theme-icon-footer::before {
    content: '☀️';
}

body.light-theme .footer-theme-btn .theme-icon-footer::before {
    content: '🌙';
}
</style>

<script>
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function toggleThemeFromFooter() {
    // Use the existing theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle-button');
    if (themeToggle) {
        themeToggle.click();
    }
    
    // Update footer theme icon
    const footerIcon = document.querySelector('.theme-icon-footer');
    const isDark = document.body.classList.contains('dark-theme');
    footerIcon.textContent = isDark ? '☀️' : '🌙';
}

function showContact() {
    document.getElementById('contactModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideContact() {
    document.getElementById('contactModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal on outside click
document.getElementById('contactModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideContact();
    }
});

// Show/hide scroll to top button
window.addEventListener('scroll', function() {
    const scrollBtn = document.querySelector('.scroll-top-btn');
    if (scrollBtn) {
        if (window.pageYOffset > 300) {
            scrollBtn.style.opacity = '1';
            scrollBtn.style.pointerEvents = 'auto';
        } else {
            scrollBtn.style.opacity = '0';
            scrollBtn.style.pointerEvents = 'none';
        }
    }
});

// Initialize footer
document.addEventListener('DOMContentLoaded', function() {
    // Update footer theme icon
    const footerIcon = document.querySelector('.theme-icon-footer');
    const isDark = document.body.classList.contains('dark-theme');
    if (footerIcon) {
        footerIcon.textContent = isDark ? '☀️' : '🌙';
    }
    
    // Initialize scroll button
    const scrollBtn = document.querySelector('.scroll-top-btn');
    if (scrollBtn) {
        scrollBtn.style.opacity = '0';
        scrollBtn.style.pointerEvents = 'none';
        scrollBtn.style.transition = 'all 0.3s ease';
    }
});
</script>