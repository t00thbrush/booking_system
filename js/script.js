/* ============================
   GLASSMORPHISM THEME - JavaScript
   ============================ */

document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initTimeSlots();
    initSelects();
});

// ============================
// Dark Mode Toggle
// ============================

function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    const savedMode = localStorage.getItem('darkMode');
    
    // Apply saved preference
    if (savedMode === 'enabled') {
        body.classList.add('dark-mode');
        updateDarkModeIcon(true);
    } else if (savedMode === 'disabled') {
        body.classList.remove('dark-mode');
        updateDarkModeIcon(false);
    }
    
    // Add click listener
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function(e) {
            e.preventDefault();
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
        toggle.innerHTML = isDarkMode ? '☀️' : '🌙';
    }
}

// ============================
// Time Slots
// ============================

function initTimeSlots() {
    const timeSlots = document.querySelectorAll('.time-slots input[type="radio"]');
    timeSlots.forEach(radio => {
        radio.addEventListener('change', function() {
            const label = document.querySelector(`label[for="${this.id}"]`);
            if (label) {
                timeSlots.forEach(r => {
                    const l = document.querySelector(`label[for="${r.id}"]`);
                    if (l) {
                        l.style.borderColor = '';
                        l.style.background = '';
                    }
                });
                label.style.borderColor = '#6366f1';
                label.style.background = 'rgba(99, 102, 241, 0.2)';
            }
        });
    });
}

// ============================
// Select & Date Styling
// ============================

function initSelects() {
    const selects = document.querySelectorAll('select');
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    selects.forEach(select => {
        select.addEventListener('focus', function() {
            this.style.borderColor = '#6366f1';
            this.style.background = 'rgba(255, 255, 255, 0.9)';
        });
        select.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.background = '';
        });
    });
    
    dateInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#6366f1';
            this.style.background = 'rgba(255, 255, 255, 0.95)';
        });
        input.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.background = '';
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
// ============================
// Modal Functions
// ============================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Close modal when clicking outside content
document.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
    }
});

// ============================
// Comments Section - Collapse/Expand
// ============================

function toggleCommentsSection() {
    const content = document.getElementById('commentsSectionContent');
    const toggle = document.getElementById('commentsSectionToggle');
    
    if (content) {
        const isVisible = content.style.display !== 'none';
        content.style.display = isVisible ? 'none' : 'block';
        toggle.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(90deg)';
        
        // Save preference
        localStorage.setItem('commentsExpanded', !isVisible ? 'true' : 'false');
    }
}

// Initialize comments section state
document.addEventListener('DOMContentLoaded', function() {
    const commentsExpanded = localStorage.getItem('commentsExpanded') === 'true';
    if (commentsExpanded) {
        const content = document.getElementById('commentsSectionContent');
        const toggle = document.getElementById('commentsSectionToggle');
        if (content) {
            content.style.display = 'block';
            toggle.style.transform = 'rotate(90deg)';
        }
    }
});

// ============================
// Filter Comments by Facility
// ============================

function filterCommentsByFacility() {
    const facilitySelect = document.getElementById('commentsFilterFacility');
    const selectedFacilityId = facilitySelect.value;
    const commentItems = document.querySelectorAll('.comment-item[data-facility-id]');
    
    if (selectedFacilityId === '') {
        // Show all comments
        commentItems.forEach(item => {
            item.style.display = 'block';
        });
    } else {
        // Show only comments for selected facility
        commentItems.forEach(item => {
            const itemFacilityId = item.getAttribute('data-facility-id');
            item.style.display = itemFacilityId === selectedFacilityId ? 'block' : 'none';
        });
    }
}