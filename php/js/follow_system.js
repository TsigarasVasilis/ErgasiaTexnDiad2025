class FollowSystem {
    constructor() {
        this.init();
    }

    init() {
        // Bind event listeners
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-follow, .btn-unfollow') || 
                e.target.closest('.btn-follow, .btn-unfollow')) {
                e.preventDefault();
                this.handleFollowClick(e);
            }
        });
    }

    async handleFollowClick(event) {
        const button = event.target.matches('.btn-follow, .btn-unfollow') ? 
                      event.target : 
                      event.target.closest('.btn-follow, .btn-unfollow');
        
        if (!button || !button.href) return;

        // Prevent double-clicking
        if (button.classList.contains('loading')) return;

        const originalHref = button.href;
        const url = new URL(originalHref);
        
        // Add AJAX parameter
        url.searchParams.set('ajax', '1');
        
        const userId = url.searchParams.get('user_id');
        const action = url.searchParams.get('action');
        
        if (!userId || !action) return;

        // Show loading state
        this.setLoadingState(button, true);

        try {
            const response = await fetch(url.toString());
            const data = await response.json();

            if (data.success) {
                this.updateButton(button, data);
                this.updateFollowerCounts(userId, data);
                this.showNotification(data.message, 'success');
            } else {
                this.showNotification(data.message || 'Προέκυψε σφάλμα', 'error');
            }
        } catch (error) {
            console.error('Follow/Unfollow error:', error);
            this.showNotification('Προέκυψε σφάλμα δικτύου', 'error');
        } finally {
            this.setLoadingState(button, false);
        }
    }

    setLoadingState(button, loading) {
        if (loading) {
            button.classList.add('loading');
            button.style.opacity = '0.6';
            button.style.pointerEvents = 'none';
            
            const originalText = button.innerHTML;
            button.setAttribute('data-original-text', originalText);
            button.innerHTML = originalText.includes('Follow') ? 
                              '⏳ Loading...' : 
                              '⏳ Loading...';
        } else {
            button.classList.remove('loading');
            button.style.opacity = '1';
            button.style.pointerEvents = 'auto';
        }
    }

    updateButton(button, data) {
        const url = new URL(button.href);
        
        if (data.new_status === 'following') {
            // Update to unfollow button
            button.className = button.className.replace('btn-follow', 'btn-unfollow');
            button.innerHTML = '❌ Unfollow';
            url.searchParams.set('action', 'unfollow');
        } else {
            // Update to follow button
            button.className = button.className.replace('btn-unfollow', 'btn-follow');
            button.innerHTML = '➕ Follow';
            url.searchParams.set('action', 'follow');
        }
        
        // Remove ajax parameter for normal navigation
        url.searchParams.delete('ajax');
        button.href = url.toString();

        // Add click animation
        button.style.transform = 'scale(0.95)';
        setTimeout(() => {
            button.style.transform = 'scale(1)';
        }, 150);
    }

    updateFollowerCounts(userId, data) {
        // Update follower counts throughout the page
        const followerElements = document.querySelectorAll(`[data-user-id="${userId}"] .followers-count, .follower-count-${userId}`);
        
        followerElements.forEach(element => {
            if (data.followers_count !== undefined) {
                element.textContent = data.followers_count;
                
                // Add animation
                element.style.transform = 'scale(1.1)';
                element.style.color = 'var(--nav-link)';
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                    element.style.color = '';
                }, 300);
            }
        });

        // Update notification badge in header if needed
        this.updateNotificationBadge();
    }

    updateNotificationBadge() {
        // This could be expanded to fetch updated notification count
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            // Add a subtle animation to indicate change
            badge.style.animation = 'none';
            setTimeout(() => {
                badge.style.animation = 'pulse 2s infinite';
            }, 10);
        });
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.ajax-notification');
        existingNotifications.forEach(notification => notification.remove());

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `ajax-notification ajax-notification-${type}`;
        notification.innerHTML = `
            <div class="ajax-notification-content">
                <span class="ajax-notification-icon">
                    ${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}
                </span>
                <span class="ajax-notification-message">${message}</span>
                <button class="ajax-notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: ${type === 'success' ? 
                        'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)' : 
                        type === 'error' ? 
                        'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)' : 
                        'linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%)'};
            color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 350px;
            min-width: 250px;
        `;

        const content = notification.querySelector('.ajax-notification-content');
        content.style.cssText = `
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
        `;

        const closeBtn = notification.querySelector('.ajax-notification-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
            margin-left: auto;
        `;

        // Add to page
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Utility functions for playlist cards
class PlaylistSystem {
    constructor() {
        this.currentPlaylist = null;
        this.currentVideoIndex = 0;
        this.isPlaying = false;
        this.init();
    }

    init() {
        // Initialize playlist-specific functionality
        this.initializeAutoplay();
        this.setupKeyboardShortcuts();
    }

    initializeAutoplay() {
        // Check if autoplay is requested
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('autoplay') === '1') {
            // Handled by PHP, but can add additional JS logic here
        }
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Only work when not in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

            switch(e.key) {
                case 'f':
                case 'F':
                    e.preventDefault();
                    this.openSearchModal();
                    break;
                case 'Escape':
                    this.closeModals();
                    break;
            }
        });
    }

    openSearchModal() {
        // Quick search functionality
        const searchInput = document.querySelector('input[name="q"], input[name="search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    closeModals() {
        // Close any open modals or dropdowns
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
        });
    }
}

// Animation utilities
class AnimationUtils {
    static fadeIn(element, duration = 300) {
        element.style.opacity = '0';
        element.style.transition = `opacity ${duration}ms ease`;
        
        setTimeout(() => {
            element.style.opacity = '1';
        }, 10);
    }

    static slideIn(element, direction = 'up', duration = 300) {
        const transforms = {
            up: 'translateY(20px)',
            down: 'translateY(-20px)',
            left: 'translateX(20px)',
            right: 'translateX(-20px)'
        };

        element.style.opacity = '0';
        element.style.transform = transforms[direction];
        element.style.transition = `all ${duration}ms ease`;

        setTimeout(() => {
            element.style.opacity = '1';
            element.style.transform = 'translate(0)';
        }, 10);
    }

    static staggeredAnimation(elements, delay = 100) {
        elements.forEach((element, index) => {
            setTimeout(() => {
                this.slideIn(element);
            }, index * delay);
        });
    }
}

// Initialize systems when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize follow system
    window.followSystem = new FollowSystem();
    
    // Initialize playlist system
    window.playlistSystem = new PlaylistSystem();
    
    // Animate elements on page load
    const cards = document.querySelectorAll('.playlist-card, .user-card, .video-card');
    if (cards.length > 0) {
        AnimationUtils.staggeredAnimation(Array.from(cards), 100);
    }

    // Initialize theme toggle enhancement
    const themeToggle = document.getElementById('theme-toggle-button');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            // Add ripple effect
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (rect.width / 2) - (size / 2) + 'px';
            ripple.style.top = (rect.height / 2) - (size / 2) + 'px';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }
});

// Add CSS for ripple animation
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .loading {
        pointer-events: none !important;
    }
    
    .ajax-notification-message {
        flex: 1;
        font-weight: 500;
    }
    
    .ajax-notification-icon {
        font-size: 1.2em;
    }
`;
document.head.appendChild(style);