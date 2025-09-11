/**
 * Heritage Christian University Online Voting System
 * Landing Page JavaScript
 * 
 * Features:
 * - Election Countdown Timer with live updates
 * - Smooth scrolling navigation
 * - Scroll-based animations
 * - Responsive navigation behavior
 * - Accessibility enhancements
 */

// ===== CONFIGURATION =====
const CONFIG = {
    // Election date and time (EASILY ADJUSTABLE)
    // Format: 'YYYY-MM-DD HH:MM:SS' (24-hour format)
    electionDateTime: '2025-08-27 08:00:00',
    
    // Animation settings
    animationDelay: 100,
    scrollOffset: 100,
    
    // Countdown update interval (milliseconds)
    countdownInterval: 1000
};

// ===== GLOBAL VARIABLES =====
let countdownTimer;
let isCountdownActive = true;

// ===== UTILITY FUNCTIONS =====

/**
 * Safely query DOM elements
 * @param {string} selector - CSS selector
 * @returns {Element|null} - DOM element or null
 */
function safeQuerySelector(selector) {
    try {
        return document.querySelector(selector);
    } catch (error) {
        console.warn(`Invalid selector: ${selector}`);
        return null;
    }
}

/**
 * Safely query multiple DOM elements
 * @param {string} selector - CSS selector
 * @returns {NodeList} - NodeList of elements
 */
function safeQuerySelectorAll(selector) {
    try {
        return document.querySelectorAll(selector);
    } catch (error) {
        console.warn(`Invalid selector: ${selector}`);
        return [];
    }
}

/**
 * Add event listener with error handling
 * @param {Element} element - DOM element
 * @param {string} event - Event type
 * @param {Function} handler - Event handler
 */
function safeAddEventListener(element, event, handler) {
    if (element && typeof handler === 'function') {
        element.addEventListener(event, handler);
    }
}

/**
 * Debounce function to limit function calls
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===== COUNTDOWN TIMER FUNCTIONS =====

/**
 * Parse election date from configuration
 * @returns {Date} - Election date object
 */
function getElectionDate() {
    try {
        // Parse the date string (assuming local timezone)
        const [datePart, timePart] = CONFIG.electionDateTime.split(' ');
        const [year, month, day] = datePart.split('-').map(Number);
        const [hours, minutes, seconds] = timePart.split(':').map(Number);
        
        return new Date(year, month - 1, day, hours, minutes, seconds);
    } catch (error) {
        console.error('Invalid election date format:', error);
        // Fallback to 7 days from now
        const fallbackDate = new Date();
        fallbackDate.setDate(fallbackDate.getDate() + 7);
        return fallbackDate;
    }
}

/**
 * Calculate time remaining until election
 * @returns {Object} - Object with days, hours, minutes, seconds, and total milliseconds
 */
function calculateTimeRemaining() {
    const now = new Date().getTime();
    const electionTime = getElectionDate().getTime();
    const difference = electionTime - now;
    
    if (difference <= 0) {
        return {
            total: 0,
            days: 0,
            hours: 0,
            minutes: 0,
            seconds: 0
        };
    }
    
    return {
        total: difference,
        days: Math.floor(difference / (1000 * 60 * 60 * 24)),
        hours: Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
        minutes: Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60)),
        seconds: Math.floor((difference % (1000 * 60)) / 1000)
    };
}

/**
 * Update countdown display with animation
 * @param {Object} timeRemaining - Time remaining object
 */
function updateCountdownDisplay(timeRemaining) {
    const elements = {
        days: safeQuerySelector('#days'),
        hours: safeQuerySelector('#hours'),
        minutes: safeQuerySelector('#minutes'),
        seconds: safeQuerySelector('#seconds')
    };
    
    // Update each time unit with fade animation
    Object.keys(elements).forEach(unit => {
        const element = elements[unit];
        if (element) {
            const newValue = String(timeRemaining[unit]).padStart(2, '0');
            
            // Only animate if value changed
            if (element.textContent !== newValue) {
                element.style.opacity = '0.5';
                element.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                    element.textContent = newValue;
                    element.style.opacity = '1';
                    element.style.transform = 'scale(1)';
                }, 150);
            }
        }
    });
}

/**
 * Show voting open message
 */
function showVotingOpenMessage() {
    const timerElement = safeQuerySelector('#countdown-timer');
    const messageElement = safeQuerySelector('#countdown-message');
    const titleElement = safeQuerySelector('.countdown-title');
    
    if (timerElement) {
        timerElement.style.display = 'none';
    }
    
    if (messageElement) {
        messageElement.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>Voting is now open!</strong>
        `;
        messageElement.style.display = 'block';
        messageElement.classList.add('animate-fade-in');
    }
    
    if (titleElement) {
        titleElement.innerHTML = `
            <i class="fas fa-vote-yea me-3"></i>Election Day is Here!
        `;
        titleElement.classList.add('animate-fade-in');
    }
}

/**
 * Initialize and start countdown timer
 */
function initializeCountdown() {
    // Clear any existing timer
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
    
    // Function to update countdown
    function updateCountdown() {
        if (!isCountdownActive) return;
        
        const timeRemaining = calculateTimeRemaining();
        
        if (timeRemaining.total <= 0) {
            // Election time reached
            clearInterval(countdownTimer);
            showVotingOpenMessage();
            isCountdownActive = false;
        } else {
            // Update display
            updateCountdownDisplay(timeRemaining);
        }
    }
    
    // Initial update
    updateCountdown();
    
    // Set up interval for live updates
    countdownTimer = setInterval(updateCountdown, CONFIG.countdownInterval);
}

// ===== NAVIGATION FUNCTIONS =====

/**
 * Handle navigation scroll effects
 */
function handleNavigationScroll() {
    const navbar = safeQuerySelector('#mainNav');
    if (!navbar) return;
    
    const scrolled = window.scrollY > 50;
    
    if (scrolled) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
}

/**
 * Initialize smooth scrolling for navigation links
 */
function initializeSmoothScrolling() {
    const navLinks = safeQuerySelectorAll('a[href^="#"]');
    
    navLinks.forEach(link => {
        safeAddEventListener(link, 'click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip if it's just '#'
            if (href === '#') return;
            
            const targetElement = safeQuerySelector(href);
            
            if (targetElement) {
                e.preventDefault();
                
                const navbar = safeQuerySelector('#mainNav');
                const navbarHeight = navbar ? navbar.offsetHeight : 0;
                const targetPosition = targetElement.offsetTop - navbarHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                const navbarCollapse = safeQuerySelector('#navbarNav');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            }
        });
    });
}

// ===== ANIMATION FUNCTIONS =====

/**
 * Check if element is in viewport
 * @param {Element} element - DOM element to check
 * @returns {boolean} - True if element is in viewport
 */
function isElementInViewport(element) {
    if (!element) return false;
    
    const rect = element.getBoundingClientRect();
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;
    
    return (
        rect.top <= windowHeight - CONFIG.scrollOffset &&
        rect.bottom >= CONFIG.scrollOffset
    );
}

/**
 * Handle scroll-based animations
 */
function handleScrollAnimations() {
    const animatedElements = safeQuerySelectorAll('[class*="animate-"]');
    
    animatedElements.forEach((element, index) => {
        if (isElementInViewport(element) && !element.classList.contains('animated')) {
            setTimeout(() => {
                element.classList.add('animated');
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * CONFIG.animationDelay);
        }
    });
}

/**
 * Initialize scroll animations
 */
function initializeScrollAnimations() {
    // Set initial state for animated elements
    const animatedElements = safeQuerySelectorAll('[class*="animate-"]');
    
    animatedElements.forEach(element => {
        if (!element.classList.contains('animated')) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'all 0.6s ease-out';
        }
    });
    
    // Handle scroll events
    const debouncedScrollHandler = debounce(() => {
        handleScrollAnimations();
        handleNavigationScroll();
    }, 10);
    
    safeAddEventListener(window, 'scroll', debouncedScrollHandler);
    
    // Initial check
    handleScrollAnimations();
}

// ===== ACCESSIBILITY FUNCTIONS =====

/**
 * Initialize accessibility enhancements
 */
function initializeAccessibility() {
    // Add skip link for keyboard navigation
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.textContent = 'Skip to main content';
    skipLink.className = 'sr-only sr-only-focusable';
    skipLink.style.cssText = `
        position: absolute;
        top: -40px;
        left: 6px;
        z-index: 1000;
        color: white;
        background: var(--primary-blue);
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        transition: top 0.3s;
    `;
    
    safeAddEventListener(skipLink, 'focus', () => {
        skipLink.style.top = '6px';
    });
    
    safeAddEventListener(skipLink, 'blur', () => {
        skipLink.style.top = '-40px';
    });
    
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Add main content landmark
    const heroSection = safeQuerySelector('.hero-section');
    if (heroSection) {
        heroSection.id = 'main-content';
        heroSection.setAttribute('role', 'main');
    }
    
    // Enhance button accessibility
    const buttons = safeQuerySelectorAll('.btn');
    buttons.forEach(button => {
        if (!button.getAttribute('aria-label') && !button.textContent.trim()) {
            const icon = button.querySelector('i');
            if (icon) {
                const iconClass = icon.className;
                let ariaLabel = 'Button';
                
                if (iconClass.includes('fa-vote')) ariaLabel = 'Vote';
                else if (iconClass.includes('fa-user')) ariaLabel = 'User Login';
                else if (iconClass.includes('fa-cog')) ariaLabel = 'Admin Login';
                
                button.setAttribute('aria-label', ariaLabel);
            }
        }
    });
}

// ===== PERFORMANCE FUNCTIONS =====

/**
 * Initialize performance optimizations
 */
function initializePerformanceOptimizations() {
    // Lazy load images when they come into view
    const images = safeQuerySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for browsers without IntersectionObserver
        images.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
    
    // Preload critical resources
    const criticalResources = [
        'assets/css/landing.css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
    ];
    
    criticalResources.forEach(resource => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = resource;
        document.head.appendChild(link);
    });
}

// ===== ERROR HANDLING =====

/**
 * Global error handler
 * @param {Error} error - Error object
 * @param {string} context - Context where error occurred
 */
function handleError(error, context = 'Unknown') {
    console.error(`Error in ${context}:`, error);
    
    // You could send errors to a logging service here
    // Example: sendErrorToLoggingService(error, context);
}

/**
 * Initialize error handling
 */
function initializeErrorHandling() {
    window.addEventListener('error', (event) => {
        handleError(event.error, 'Global');
    });
    
    window.addEventListener('unhandledrejection', (event) => {
        handleError(event.reason, 'Promise Rejection');
    });
}

// ===== MAIN INITIALIZATION =====

/**
 * Initialize all landing page functionality
 */
function initializeLandingPage() {
    try {
        // Core functionality
        initializeCountdown();
        initializeSmoothScrolling();
        initializeScrollAnimations();
        
        // Enhancements
        initializeAccessibility();
        initializePerformanceOptimizations();
        initializeErrorHandling();
        
        // Initial navigation state
        handleNavigationScroll();
        
        console.log('Landing page initialized successfully');
    } catch (error) {
        handleError(error, 'Initialization');
    }
}

// ===== EVENT LISTENERS =====

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', initializeLandingPage);

// Window Load (for complete resource loading)
window.addEventListener('load', () => {
    // Remove any loading states
    document.body.classList.add('loaded');
    
    // Final animation trigger
    setTimeout(() => {
        handleScrollAnimations();
    }, 100);
});

// Window Resize (debounced)
window.addEventListener('resize', debounce(() => {
    handleScrollAnimations();
}, 250));

// Visibility Change (pause/resume countdown when tab is not visible)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        isCountdownActive = false;
        if (countdownTimer) {
            clearInterval(countdownTimer);
        }
    } else {
        isCountdownActive = true;
        initializeCountdown();
    }
});

// ===== EXPORT FOR TESTING (if needed) =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        CONFIG,
        calculateTimeRemaining,
        getElectionDate,
        initializeCountdown,
        handleScrollAnimations,
        initializeLandingPage
    };
}

// ===== EXACT COUNTDOWN TIMER CODE (as requested) =====
/*
Here's the exact JavaScript countdown timer code that can be plugged straight into any landing page:

// COUNTDOWN TIMER - STANDALONE VERSION
function createCountdownTimer(targetDate, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    function updateTimer() {
        const now = new Date().getTime();
        const distance = new Date(targetDate).getTime() - now;
        
        if (distance < 0) {
            container.innerHTML = "<h2>Voting is now open!</h2>";
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        container.innerHTML = `
            <div style="display: flex; gap: 20px; justify-content: center; font-family: Arial, sans-serif;">
                <div style="text-align: center; padding: 20px; background: #1e3a8a; color: white; border-radius: 10px; min-width: 80px;">
                    <div style="font-size: 2rem; font-weight: bold;">${days.toString().padStart(2, '0')}</div>
                    <div style="font-size: 0.9rem;">Days</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #1e3a8a; color: white; border-radius: 10px; min-width: 80px;">
                    <div style="font-size: 2rem; font-weight: bold;">${hours.toString().padStart(2, '0')}</div>
                    <div style="font-size: 0.9rem;">Hours</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #1e3a8a; color: white; border-radius: 10px; min-width: 80px;">
                    <div style="font-size: 2rem; font-weight: bold;">${minutes.toString().padStart(2, '0')}</div>
                    <div style="font-size: 0.9rem;">Minutes</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #1e3a8a; color: white; border-radius: 10px; min-width: 80px;">
                    <div style="font-size: 2rem; font-weight: bold;">${seconds.toString().padStart(2, '0')}</div>
                    <div style="font-size: 0.9rem;">Seconds</div>
                </div>
            </div>
        `;
    }
    
    updateTimer();
    setInterval(updateTimer, 1000);
}

// Usage:
// createCountdownTimer('2024-12-15 09:00:00', 'countdown-container');
*/

console.log('Heritage Christian University Online Voting System - Landing Page JavaScript Loaded');