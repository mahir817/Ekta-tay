// Dashboard functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeDashboard();
});

function initializeDashboard() {
    // Add interactive effects
    addInteractiveEffects();

    // Initialize animations
    initializeAnimations();

    // Setup form interactions
    setupFormInteractions();

    // Setup navigation
    setupNavigation();

    // Setup mobile menu
    setupMobileMenu();
}

function addInteractiveEffects() {
    // Add hover effects to cards
    const cards = document.querySelectorAll('.stat-card, .glass-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-5px)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add hover effects to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px)';
        });

        button.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add hover effects to navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function () {
            this.style.transform = 'translateX(4px)';
        });

        link.addEventListener('mouseleave', function () {
            this.style.transform = 'translateX(0)';
        });
    });
}

function initializeAnimations() {
    // Add staggered animation to cards
    const cards = document.querySelectorAll('.fade-in-up');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    // Add loading animation to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.classList.add('loading');
        setTimeout(() => {
            card.classList.remove('loading');
        }, 1000 + Math.random() * 500);
    });
}

function setupFormInteractions() {
    // Radio button interactions
    const radioItems = document.querySelectorAll('.radio-item');
    radioItems.forEach(item => {
        item.addEventListener('click', function () {
            // Remove checked class from all radio inputs in the same group
            const radioGroup = this.closest('.radio-group');
            const allInputs = radioGroup.querySelectorAll('.radio-input');
            allInputs.forEach(input => input.classList.remove('checked'));

            // Add checked class to clicked input
            const input = this.querySelector('.radio-input');
            input.classList.add('checked');
        });
    });

    // Form input focus effects
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.style.transform = 'scale(1.02)';
        });

        input.addEventListener('blur', function () {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // Payment form submission
    const paymentForm = document.querySelector('.payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            handlePaymentSubmission();
        });
    }
}

function handlePaymentSubmission() {
    const amount = document.querySelector('input[placeholder="Enter amount"]').value;
    const paymentMethod = document.querySelector('.radio-input.checked').nextElementSibling.textContent;
    const notes = document.querySelector('input[placeholder="Payment and notes"]').value;

    if (!amount) {
        showNotification('Please enter an amount', 'error');
        return;
    }

    // Show loading state
    const submitBtn = document.querySelector('.payment-form .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;

    // Simulate payment processing
    setTimeout(() => {
        showNotification(`Payment of $${amount} via ${paymentMethod} processed successfully!`, 'success');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;

        // Reset form
        document.querySelector('.payment-form').reset();
        document.querySelector('.radio-input').classList.add('checked');
    }, 2000);
}

function setupNavigation() {
    // Navigation link clicks
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));

            // Add active class to clicked link
            this.classList.add('active');

            // Handle navigation based on link text
            const linkText = this.textContent.trim();
            handleNavigation(linkText);
        });
    });
}

function handleNavigation(linkText) {
    switch (linkText) {
        case 'Dashboard':
            // Already on dashboard
            break;
        case 'Housing':
            showNotification('Housing module coming soon!', 'info');
            break;
        case 'Jobs':
            showNotification('Jobs module coming soon!', 'info');
            break;
        case 'Tuition':
            showNotification('Tuition module coming soon!', 'info');
            break;
        case 'Services':
            showNotification('Services module coming soon!', 'info');
            break;
        case 'Manage':
            showNotification('Management panel coming soon!', 'info');
            break;
        case 'Help':
            showNotification('Help section coming soon!', 'info');
            break;
    }
}

function setupMobileMenu() {
    // Create mobile menu toggle button
    const mobileToggle = document.createElement('button');
    mobileToggle.className = 'mobile-menu-toggle';
    mobileToggle.innerHTML = '<i class="fas fa-bars"></i>';
    mobileToggle.style.cssText = `
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 12px;
        color: white;
        cursor: pointer;
        font-size: 1.2rem;
    `;

    document.body.appendChild(mobileToggle);

    // Toggle sidebar on mobile
    mobileToggle.addEventListener('click', function () {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('open');
    });

    // Show/hide mobile toggle based on screen size
    function handleResize() {
        if (window.innerWidth <= 768) {
            mobileToggle.style.display = 'block';
        } else {
            mobileToggle.style.display = 'none';
            document.querySelector('.sidebar').classList.remove('open');
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial check
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1002;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        color: white;
        font-weight: 500;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    `;

    // Add type-specific styling
    if (type === 'success') {
        notification.style.borderLeft = '4px solid #4ade80';
    } else if (type === 'error') {
        notification.style.borderLeft = '4px solid #f87171';
    } else if (type === 'info') {
        notification.style.borderLeft = '4px solid #60a5fa';
    }

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Add some celebration effects for first visit
function addWelcomeEffect() {
    // Check if this is the first visit today
    const lastVisit = localStorage.getItem('lastVisit');
    const today = new Date().toDateString();

    if (lastVisit !== today) {
        // Add welcome animation
        const welcomeCard = document.querySelector('.dashboard-title');
        if (welcomeCard) {
            welcomeCard.style.animation = 'pulse 2s ease-in-out';
        }

        // Update last visit
        localStorage.setItem('lastVisit', today);
    }
}

// Initialize welcome effect
addWelcomeEffect();

// Add pulse animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
