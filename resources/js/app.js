import './bootstrap';

// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registered:', registration);
            
            // Listen for updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                newWorker?.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        if (confirm('New content available, reload?')) {
                            window.location.reload();
                        }
                    }
                });
            });
        } catch (error) {
            console.error('Service Worker registration failed:', error);
        }
    });
}

// SPA Navigation Enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Add page transition animations
    const mainContent = document.querySelector('main');
    
    // Loading state management
    let loadingTimeout;
    
    // Show loading indicator for longer requests
    document.addEventListener('livewire:navigating', () => {
        document.body.classList.add('navigation-loading');
        
        // Show loading spinner after 200ms if still loading
        loadingTimeout = setTimeout(() => {
            showLoadingIndicator();
        }, 200);
    });
    
    // Hide loading indicator when navigation completes
    document.addEventListener('livewire:navigated', () => {
        document.body.classList.remove('navigation-loading');
        clearTimeout(loadingTimeout);
        hideLoadingIndicator();
        
        // Page transition animation
        if (mainContent) {
            mainContent.style.opacity = '0';
            mainContent.style.transform = 'translateY(10px)';
            
            requestAnimationFrame(() => {
                mainContent.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                mainContent.style.opacity = '1';
                mainContent.style.transform = 'translateY(0)';
            });
        }
        
        // Update active tab states
        updateActiveTabStates();
    });
    
    // Function to show loading indicator
    function showLoadingIndicator() {
        let loader = document.getElementById('spa-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'spa-loader';
            loader.className = 'fixed top-0 left-0 right-0 z-50 h-1 bg-orange-500/20';
            loader.innerHTML = '<div class="h-full bg-orange-500 animate-pulse" style="width: 30%; animation: loading-bar 2s ease-in-out infinite;"></div>';
            document.body.appendChild(loader);
        }
        loader.style.display = 'block';
    }
    
    // Function to hide loading indicator
    function hideLoadingIndicator() {
        const loader = document.getElementById('spa-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }
    
    // Update active tab states without full page reload
    function updateActiveTabStates() {
        const currentPath = window.location.pathname;
        const tabLinks = document.querySelectorAll('nav a[wire\\:navigate]');
        
        tabLinks.forEach(link => {
            const href = new URL(link.href).pathname;
            const isActive = href === currentPath || 
                           (currentPath === '/' && href.includes('starter'));
            
            // Remove existing active classes
            link.classList.remove('text-orange-600', 'dark:text-orange-400');
            link.classList.add('text-gray-600', 'dark:text-gray-400');
            
            // Add active classes if current page
            if (isActive) {
                link.classList.remove('text-gray-600', 'dark:text-gray-400');
                link.classList.add('text-orange-600', 'dark:text-orange-400');
            }
        });
    }
    
    // Initial tab state update
    updateActiveTabStates();
    
    // Prefetch likely navigation targets
    prefetchLikelyPages();
});

// Performance optimizations
function prefetchLikelyPages() {
    const currentPath = window.location.pathname;
    const prefetchUrls = [];
    
    // Define likely navigation paths based on current page
    switch (currentPath) {
        case '/':
        case '/starter':
            prefetchUrls.push('/feeding', '/recipe');
            break;
        case '/feeding':
            prefetchUrls.push('/starter', '/history');
            break;
        case '/recipe':
            prefetchUrls.push('/starter');
            break;
        case '/history':
            prefetchUrls.push('/starter', '/feeding');
            break;
    }
    
    // Prefetch with a delay to not impact current page performance
    setTimeout(() => {
        prefetchUrls.forEach(url => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = url;
            document.head.appendChild(link);
        });
    }, 1000);
}

// Lazy load images when they come into view
function setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Pull to refresh functionality
let startY = 0;
let currentY = 0;
let pullThreshold = 150; // Increased from 80 to 150 to reduce sensitivity
let isPulling = false;

document.addEventListener('touchstart', (e) => {
    if (window.scrollY === 0) {
        startY = e.touches[0].clientY;
        isPulling = true;
    }
});

document.addEventListener('touchmove', (e) => {
    if (!isPulling) return;
    
    currentY = e.touches[0].clientY;
    const pullDistance = currentY - startY;
    
    if (pullDistance > 0 && window.scrollY === 0) {
        e.preventDefault();
        
        if (pullDistance > pullThreshold) {
            document.body.classList.add('pull-to-refresh-active');
        }
        
        // Visual feedback
        const pullIndicator = document.getElementById('pull-indicator') || createPullIndicator();
        pullIndicator.style.transform = `translateY(${Math.min(pullDistance, pullThreshold + 20)}px)`;
        pullIndicator.style.opacity = Math.min(pullDistance / pullThreshold, 1);
    }
});

document.addEventListener('touchend', () => {
    if (!isPulling) return;
    isPulling = false;
    
    const pullDistance = currentY - startY;
    
    if (pullDistance > pullThreshold) {
        // Trigger refresh
        window.location.reload();
    }
    
    // Reset UI
    document.body.classList.remove('pull-to-refresh-active');
    const pullIndicator = document.getElementById('pull-indicator');
    if (pullIndicator) {
        pullIndicator.style.transform = 'translateY(-100%)';
        pullIndicator.style.opacity = '0';
    }
});

function createPullIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'pull-indicator';
    indicator.className = 'fixed top-0 left-0 right-0 bg-orange-500 text-white text-center py-2 text-sm font-medium transform -translate-y-full transition-all duration-300 z-40';
    indicator.innerHTML = '� Pull to refresh';
    document.body.appendChild(indicator);
    return indicator;
}

// Offline detection and status
window.addEventListener('online', () => {
    document.body.classList.remove('app-offline');
    showToast('Back online', 'success');
});

window.addEventListener('offline', () => {
    document.body.classList.add('app-offline');
    showToast('You are offline', 'warning');
});

// Simple toast notification system
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm font-medium z-50 transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'warning' ? 'bg-yellow-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Slide in
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
    });
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(full)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Photo Modal Functions (globally available)
window.openPhotoModal = function(photoUrl, date) {
    document.getElementById('modalPhoto').src = photoUrl;
    document.getElementById('modalDate').textContent = date;
    document.getElementById('photoModal').classList.remove('hidden');
}

window.closePhotoModal = function() {
    document.getElementById('photoModal').classList.add('hidden');
}

// Initialize photo modal after DOM loads
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside the photo
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePhotoModal();
            }
        });
    }
});

// Add CSS animations via JavaScript
const style = document.createElement('style');
style.textContent = `
    @keyframes loading-bar {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(0%); }
        100% { transform: translateX(100%); }
    }
    
    .navigation-loading {
        cursor: progress;
    }
    
    .app-offline::before {
        content: "Offline";
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #ef4444;
        color: white;
        text-align: center;
        padding: 4px;
        font-size: 12px;
        z-index: 100;
    }
    
    .pull-to-refresh-active #pull-indicator {
        color: #fff;
    }
`;
document.head.appendChild(style);