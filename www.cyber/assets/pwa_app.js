// assets/pwa_app.js - PWA functionality

document.addEventListener('DOMContentLoaded', () => {
    initPWA();
});

function initPWA() {
    // Check if app is running in standalone mode
    checkStandaloneMode();

    // Register service worker updates
    registerServiceWorker();

    // Check for app updates
    checkForUpdates();

    // Add to home screen prompt
    setupAddToHomeScreen();

    // Install prompt
    setupInstallPrompt();

    // Push notifications
    setupPushNotifications();

    // Periodic sync registration
    setupPeriodicSync();
}

function checkStandaloneMode() {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App is running in standalone mode');
        document.body.classList.add('pwa-installed');

        // Hide install prompts
        const installPrompt = document.querySelector('.pwa-install-prompt');
        if (installPrompt) {
            installPrompt.style.display = 'none';
        }
    }
}

function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('Service Worker registered with scope:', registration.scope);

                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                showUpdateNotification();
                            }
                        });
                    }
                });

                // Check for waiting worker
                if (registration.waiting) {
                    showUpdateNotification();
                }
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    }
}

function checkForUpdates() {
    // Check for app updates periodically
    setInterval(() => {
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            // Send message to service worker to check for updates
            navigator.serviceWorker.controller.postMessage({
                type: 'CHECK_FOR_UPDATES'
            });
        }
    }, 60 * 60 * 1000); // Check every hour
}

function setupInstallPrompt() {
    // Show install prompt after 5 seconds
    setTimeout(() => {
        if (!window.matchMedia('(display-mode: standalone)').matches) {
            showInstallPrompt();
        }
    }, 5000);
}

function showInstallPrompt() {
    const existingPrompt = document.querySelector('.pwa-install-prompt');
    if (existingPrompt) return;

    const installPrompt = document.createElement('div');
    installPrompt.className = 'pwa-install-prompt';
    installPrompt.innerHTML = `
        <div class="install-content">
            <span class="install-icon">📱</span>
            <span class="install-text">Install this app for better experience!</span>
            <button class="install-button" onclick="installApp()">Install</button>
            <button class="install-close" onclick="dismissInstallPrompt()">×</button>
        </div>
    `;

    document.body.appendChild(installPrompt);

    // Animate in
    setTimeout(() => {
        installPrompt.classList.add('show');
    }, 100);

    // Auto-dismiss after 10 seconds
    setTimeout(() => {
        dismissInstallPrompt();
    }, 10000);
}

function dismissInstallPrompt() {
    const installPrompt = document.querySelector('.pwa-install-prompt');
    if (installPrompt) {
        installPrompt.classList.remove('show');
        setTimeout(() => {
            installPrompt.remove();
        }, 300);
    }
}

function installApp() {
    // Check if install prompt is available
    if (window.deferredPrompt) {
        window.deferredPrompt.prompt()
            .then(result => {
                if (result.outcome === 'accepted') {
                    console.log('App installed');
                    dismissInstallPrompt();
                    showNotification('App installed successfully!', 'success');

                    // Track installation
                    trackEvent('pwa_install', 'accepted');
                } else {
                    trackEvent('pwa_install', 'dismissed');
                }
            });

        // Clear the prompt
        window.deferredPrompt = null;
    } else {
        // Fallback for browsers that don't support install prompt
        showNotification('Use your browser\'s menu to add this app to your home screen', 'info');
    }
}

function setupAddToHomeScreen() {
    // Listen for beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the mini-infobar from appearing
        e.preventDefault();

        // Save the prompt for later use
        window.deferredPrompt = e;

        // Show custom install prompt
        if (!window.matchMedia('(display-mode: standalone)').matches) {
            showInstallPrompt();
        }
    });
}

function showUpdateNotification() {
    if ('Notification' in navigator && Notification.permission === 'granted') {
        const notification = new Notification('App Update Available', {
            body: 'A new version of CyberGuard is available. Click to update.',
            icon: '/assets/icons/icon-192.png',
            badge: '/assets/icons/icon-96.png',
            tag: 'app-update',
            requireInteraction: true,
            actions: [
                {
                    action: 'update',
                    title: 'Update Now'
                },
                {
                    action: 'later',
                    title: 'Later'
                }
            ]
        });

        notification.onclick = (event) => {
            if (event.action === 'update') {
                // Reload the page to get the latest version
                window.location.reload();
            } else if (event.action === 'later') {
                notification.close();
            } else {
                // Click on notification body
                window.focus();
                notification.close();
            }
        };
    } else {
        // Show in-app notification
        showNotification('A new version is available. Refresh to update.', 'info');
    }
}

function setupPushNotifications() {
    // Request notification permission
    if ('Notification' in navigator && Notification.permission === 'default') {
        // Create a button to request permission
        const requestPermissionBtn = document.createElement('button');
        requestPermissionBtn.textContent = 'Enable Notifications';
        requestPermissionBtn.className = 'btn-cyber';
        requestPermissionBtn.style.marginTop = '1rem';

        requestPermissionBtn.addEventListener('click', () => {
            Notification.requestPermission()
                .then(permission => {
                    if (permission === 'granted') {
                        showNotification('Notifications enabled!', 'success');
                        requestPermissionBtn.textContent = 'Notifications Enabled';
                        requestPermissionBtn.disabled = true;

                        // Subscribe to push
                        subscribeToPush();
                    } else {
                        showNotification('Notifications permission denied', 'error');
                    }
                });
        });

        // Add to a relevant location
        const profileHeader = document.querySelector('.profile-header');
        if (profileHeader) {
            profileHeader.appendChild(requestPermissionBtn);
        }
    }
}

function subscribeToPush() {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.ready.then(registration => {
            registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array('BN4PqVzg3I2JNRgn8U2yfCm_uKx3lG43KlTFGVMqqlA')
            })
            .then(subscription => {
                console.log('Push subscription successful:', subscription);

                // Send subscription to server
                sendSubscriptionToServer(subscription);
            })
            .catch(error => {
                console.error('Push subscription failed:', error);
            });
        });
    }
}

function sendSubscriptionToServer(subscription) {
    fetch('/api/save_push_subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(subscription)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Push subscription saved to server');
        } else {
            console.error('Error saving push subscription:', data.error);
        }
    })
    .catch(error => {
        console.error('Error sending push subscription:', error);
    });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

function setupPeriodicSync() {
    if ('serviceWorker' in navigator && 'periodicSync' in window.PeriodicSyncManager) {
        navigator.serviceWorker.ready.then(registration => {
            // Register for daily challenge sync
            return registration.periodicSync.register('daily-challenge-sync', {
                minInterval: 24 * 60 * 60 * 1000, // 24 hours
                networkState: 'online'
            });
        })
        .then(syncRegistration => {
            console.log('Daily challenge sync registered:', syncRegistration);
        })
        .catch(error => {
            console.error('Periodic sync registration failed:', error);
        });
    }
}

function trackEvent(eventName, action) {
    // Send analytics data to server
    fetch('/api/track_analytics.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            event: eventName,
            action: action,
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent
        })
    })
    .catch(error => {
        console.error('Analytics tracking error:', error);
    });
}

// Handle background sync events
window.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SYNC_OFFLINE_ACTIONS') {
        // Handle offline actions
        handleOfflineActions();
    }
});

function handleOfflineActions() {
    // Get stored offline actions
    const offlineActions = JSON.parse(localStorage.getItem('offlineActions') || '[]');

    if (offlineActions.length > 0) {
        showNotification(`Syncing ${offlineActions.length} offline actions...`, 'info');

        // Process each action
        offlineActions.forEach(action => {
            // Resend to server
            fetch(action.endpoint, {
                method: action.method,
                headers: action.headers || {},
                body: action.body || null
            })
            .then(response => {
                if (response.ok) {
                    // Remove from offline queue
                    const updatedActions = offlineActions.filter(a => a.id !== action.id);
                    localStorage.setItem('offlineActions', JSON.stringify(updatedActions));
                }
            })
            .catch(error => {
                console.error('Error syncing offline action:', error);
            });
        });
    }
}

// Cache cleanup
function cleanupCache() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(registration => {
            registration.active.postMessage({
                type: 'CACHE_CLEANUP'
            });
        });
    }
}

// Run cleanup weekly
setInterval(cleanupCache, 7 * 24 * 60 * 60 * 1000);

// Handle online/offline events
window.addEventListener('online', () => {
    showNotification('Back online!', 'success');
    document.body.classList.remove('offline');
});

window.addEventListener('offline', () => {
    showNotification('You are offline. Some features may be unavailable.', 'warning');
    document.body.classList.add('offline');
});

// Initialize PWA features when DOM is ready
document.addEventListener('DOMContentLoaded', initPWA);
