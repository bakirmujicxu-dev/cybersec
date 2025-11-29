// assets/profile_app.js - Profile page functionality

document.addEventListener("DOMContentLoaded", () => {
    initProfilePage();
});

function initProfilePage() {
    // Initialize modals
    initModals();

    // Initialize settings form
    initSettingsForm();

    // Initialize PWA features
    initPWAFeatures();

    // Initialize notifications
    initNotifications();

    // Initialize activity animations
    initActivityAnimations();

    // Check for daily challenge completion
    checkDailyChallenge();
}

function initModals() {
    // Settings button
    document.getElementById('settings-btn').addEventListener('click', () => {
        openModal('settings-modal');
    });

    // Achievements button
    document.getElementById('achievements-btn').addEventListener('click', () => {
        openModal('achievements-modal');
    });

    // Close modals when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

function initSettingsForm() {
    const form = document.getElementById('settings-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        saveSettings();
    });
}

function saveSettings() {
    const formData = new FormData(document.getElementById('settings-form'));
    const settings = {};

    for (const [key, value] of formData.entries()) {
        settings[key] = value;
    }

    // Handle checkbox separately
    settings.notifications = document.getElementById('notifications').checked ? '1' : '0';

    // Send settings to server
    fetch('api/save_user_preferences.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Settings saved successfully!', 'success');
            closeModal('settings-modal');

            // Apply theme immediately
            if (settings.theme) {
                applyTheme(settings.theme);
            }

            // Apply avatar
            if (settings.avatar) {
                updateAvatar(settings.avatar);
            }
        } else {
            showNotification('Error saving settings', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving settings', 'error');
    });
}

function applyTheme(theme) {
    document.body.className = '';

    switch(theme) {
        case 'dark':
            document.body.classList.add('dark-theme');
            break;
        case 'matrix':
            document.body.classList.add('matrix-theme');
            break;
        case 'neon':
            document.body.classList.add('neon-theme');
            break;
        default:
            document.body.classList.add('default-theme');
    }
}

function updateAvatar(avatar) {
    const avatarCircle = document.querySelector('.avatar-circle');
    const avatarIcons = {
        'default': '👤',
        'hacker': '👨‍💻',
        'expert': '👨‍💼',
        'agent': '🕵️',
        'ninja': '🥷',
        'wizard': '🧙'
    };

    if (avatarCircle && avatarIcons[avatar]) {
        avatarCircle.textContent = avatarIcons[avatar];
    }
}

function startDailyChallenge(type, challengeId) {
    switch(type) {
        case 'quiz':
            window.location.href = `quiz.php?daily=${challengeId}`;
            break;
        case 'scenario':
            window.location.href = `scenarios.php?daily=${challengeId}`;
            break;
        case 'interactive':
            window.location.href = `interactive.php?challenge=${challengeId}`;
            break;
    }
}

function initPWAFeatures() {
    // Register service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered with scope:', registration.scope);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    }

    // Check if app is installed
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App is running in standalone mode');
        document.body.classList.add('pwa-installed');
    }

    // Show install prompt if not installed
    setTimeout(() => {
        if (!window.matchMedia('(display-mode: standalone)').matches) {
            showInstallPrompt();
        }
    }, 5000);
}

function showInstallPrompt() {
    const installPrompt = document.createElement('div');
    installPrompt.className = 'pwa-install-prompt show';
    installPrompt.innerHTML = `
        <span>📱 Install this app for better experience!</span>
    `;

    installPrompt.addEventListener('click', () => {
        // Trigger install prompt
        window.installPrompt && window.installPrompt.prompt();
        installPrompt.remove();
    });

    document.body.appendChild(installPrompt);
}

function initNotifications() {
    // Request permission for notifications
    if ('Notification' in navigator && Notification.permission === 'default') {
        // Don't ask immediately, wait for user interaction
        document.addEventListener('click', () => {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    showNotification('Notifications enabled!', 'success');
                }
            });
        }, { once: true });
    }

    // Schedule daily reminders
    scheduleDailyReminders();
}

function scheduleDailyReminders() {
    // Check if notifications are enabled in preferences
    fetch('api/get_user_preferences.php')
        .then(response => response.json())
        .then(data => {
            if (data.notifications === '1') {
                // Schedule reminder for daily challenge
                const now = new Date();
                const tomorrow = new Date(now);
                tomorrow.setDate(now.getDate() + 1);
                tomorrow.setHours(9, 0, 0, 0); // 9 AM

                const timeUntilReminder = tomorrow - now;

                setTimeout(() => {
                    sendPushNotification({
                        title: 'Daily Challenge Available!',
                        body: 'Complete today\'s challenge to maintain your streak!',
                        icon: '/assets/icons/icon-192.png',
                        tag: 'daily-challenge'
                    });

                    // Schedule next day's reminder
                    scheduleDailyReminders();
                }, timeUntilReminder);
            }
        })
        .catch(error => {
            console.error('Error fetching preferences:', error);
        });
}

function sendPushNotification(options) {
    if ('Notification' in navigator && Notification.permission === 'granted') {
        new Notification(options.title, options);
    }
}

function initActivityAnimations() {
    // Add entrance animations to activity items
    const activityItems = document.querySelectorAll('.activity-item');

    activityItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';

        setTimeout(() => {
            item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    // Add hover effects to reward badges
    const rewardBadges = document.querySelectorAll('.reward-badge');

    rewardBadges.forEach(badge => {
        badge.addEventListener('mouseenter', () => {
            badge.style.transform = 'translateY(-5px) scale(1.05)';
        });

        badge.addEventListener('mouseleave', () => {
            badge.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-bar .progress-fill');

    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';

        setTimeout(() => {
            bar.style.transition = 'width 1.5s ease';
            bar.style.width = width;
        }, 300);
    });

    // Animate streak number
    const streakNumber = document.querySelector('.streak-number');
    if (streakNumber) {
        const targetValue = parseInt(streakNumber.textContent);
        let currentValue = 0;
        const increment = targetValue / 50;

        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= targetValue) {
                currentValue = targetValue;
                clearInterval(timer);
            }
            streakNumber.textContent = Math.floor(currentValue);
        }, 30);
    }
}

function checkDailyChallenge() {
    // Check if user has completed today's challenge
    const challengeCompleted = document.querySelector('.completed-badge');
    const dailyChallengeSection = document.querySelector('.daily-challenge-section');

    if (challengeCompleted) {
        // Add celebration effect
        celebrateCompletion(dailyChallengeSection);
    }
}

function celebrateCompletion(element) {
    // Create confetti effect
    for (let i = 0; i < 50; i++) {
        createConfetti(element);
    }

    // Pulse animation
    element.classList.add('glow-animation');
    setTimeout(() => {
        element.classList.remove('glow-animation');
    }, 2000);
}

function createConfetti(container) {
    const confetti = document.createElement('div');
    confetti.className = 'confetti';
    confetti.style.position = 'absolute';
    confetti.style.width = '10px';
    confetti.style.height = '10px';
    confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 60%)`;
    confetti.style.borderRadius = '50%';
    confetti.style.left = `${Math.random() * 100}%`;
    confetti.style.top = '0';
    confetti.style.transform = `translateX(${Math.random() * 40 - 20}px)`;
    confetti.style.transition = `transform ${1 + Math.random() * 2}s ease-out, opacity ${1 + Math.random() * 2}s ease-out`;
    confetti.style.pointerEvents = 'none';
    confetti.style.zIndex = '1000';

    container.appendChild(confetti);

    setTimeout(() => {
        confetti.style.transform = `translateY(${container.offsetHeight}px) translateX(${Math.random() * 40 - 20}px)`;
        confetti.style.opacity = '0';
    }, 100);

    setTimeout(() => {
        confetti.remove();
    }, 3000);
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `cyber-notification ${type}`;
    notification.innerHTML = `
        <span class="notif-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
        <span class="notif-text">${message}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
