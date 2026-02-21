/* ============================
   GLASSMORPHISM THEME - JavaScript
   ============================ */

document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initTimeSlots();
});

// ============================
// Dark Mode Toggle
// ============================

function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    
    const darkModeEnabled = localStorage.getItem('darkMode') === 'enabled';
    
    if (darkModeEnabled) {
        body.classList.add('dark-mode');
        updateDarkModeIcon(true);
    }
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            const isEnabled = body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isEnabled ? 'enabled' : 'disabled');
            updateDarkModeIcon(isEnabled);
        });
    }
}

function updateDarkModeIcon(isDarkMode) {
    const toggle = document.getElementById('darkModeToggle');
    if (toggle) {
        toggle.textContent = isDarkMode ? '☀️' : '🌙';
    }
}

// ============================
// Time Slots
// ============================

function initTimeSlots() {
    const timeSlots = document.querySelectorAll('.time-slots label');
    timeSlots.forEach(label => {
        label.addEventListener('click', function() {
            timeSlots.forEach(l => l.style.borderColor = '');
            this.style.borderColor = '#6366f1';
            this.style.background = 'rgba(99, 102, 241, 0.1)';
        });
    });
}

// ============================
// Form Validation
// ============================

function validateForm() {
    const inputs = document.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#ef4444';
            valid = false;
        }
    });
    
    return valid;
}

// ============================
// Table Row Hover
// ============================

document.querySelectorAll('.table tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.background = 'rgba(99, 102, 241, 0.05)';
    });
    row.addEventListener('mouseleave', function() {
        this.style.background = '';
    });
});

// ============================
// Button Click Effects
// ============================

document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            left: ${x}px;
            top: ${y}px;
            animation: expandRipple 0.6s ease-out;
        `;
        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    });
});

// ============================
// Form Input Focus
// ============================

document.querySelectorAll('input, select, textarea').forEach(input => {
    input.addEventListener('focus', function() {
        this.style.borderColor = '#6366f1';
    });
    
    input.addEventListener('blur', function() {
        this.style.borderColor = '';
    });
});

// ============================
// Page Animations
// ============================

window.addEventListener('load', function() {
    const elements = document.querySelectorAll('.card, .booking-form, .facility-card, .alert');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.animation = `slideInUp 0.6s ease ${index * 0.1}s forwards`;
    });
});
