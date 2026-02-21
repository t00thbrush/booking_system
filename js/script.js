/* ============================
   CYBERPUNK NEON THEME - JavaScript
   ============================ */

// Initialize dark mode on page load
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
    
    // Check if dark mode preference is saved
    const darkModeEnabled = localStorage.getItem('darkMode') === 'enabled';
    
    if (darkModeEnabled) {
        body.classList.add('dark-mode');
        updateDarkModeIcon(true);
    }
    
    // Add click listener
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            body.classList.toggle('dark-mode');
            const isEnabled = body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isEnabled ? 'enabled' : 'disabled');
            updateDarkModeIcon(isEnabled);
            
            // Add pulse animation
            this.style.animation = 'none';
            setTimeout(() => {
                this.style.animation = '';
            }, 10);
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
// Time Slots Selection
// ============================

function initTimeSlots() {
    const timeSlots = document.querySelectorAll('.time-slots label');
    timeSlots.forEach(label => {
        const input = label.querySelector('input[type="radio"]');
        if (input) {
            input.addEventListener('change', function() {
                timeSlots.forEach(l => l.style.borderColor = '');
                if (this.checked) {
                    label.style.borderColor = 'var(--neon-pink)';
                    label.style.background = 'rgba(255, 0, 110, 0.2)';
                    showNeonPulse(label);
                }
            });
        }
    });
}

function selectTimeSlot(element) {
    const allSlots = document.querySelectorAll('.time-slots label');
    allSlots.forEach(slot => {
        slot.style.borderColor = '';
        slot.style.background = '';
    });
    element.style.borderColor = 'var(--neon-pink)';
    element.style.background = 'rgba(255, 0, 110, 0.2)';
    showNeonPulse(element);
}

// ============================
// Neon Effects
// ============================

function showNeonPulse(element) {
    element.style.animation = 'none';
    setTimeout(() => {
        element.style.animation = 'glow 0.6s ease';
    }, 10);
}

// Add hover glow effect to all cards
function initCardGlows() {
    const cards = document.querySelectorAll('.booking-form, .facility-card, .stats-card, .card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.borderColor = 'var(--neon-pink)';
            this.style.boxShadow = '0 0 30px var(--neon-pink), 0 0 40px var(--neon-blue)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    });
}

// ============================
// Form Validation
// ============================

function validateBookingForm() {
    const facilityId = document.getElementById('facility_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const timeSlot = document.querySelector('input[name="time_slot"]:checked');
    
    if (!facilityId || !bookingDate || !timeSlot) {
        showNeonNotification('Please fill all required fields', 'error');
        return false;
    }
    
    return true;
}

// ============================
// Notifications
// ============================

function showNeonNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.animation = 'slideInDown 0.5s ease';
    notification.innerHTML = `
        ${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'} ${message}
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(notification, container.firstChild);
    }
    
    // Auto-dismiss after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideInUp 0.5s ease';
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 4000);
}

// ============================
// Cancel Booking
// ============================

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="booking_id" value="${bookingId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============================
// Table Interactions
// ============================

function initTableHovers() {
    const rows = document.querySelectorAll('.table tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.background = 'rgba(0, 217, 255, 0.1)';
            this.style.boxShadow = 'inset 0 0 20px rgba(0, 217, 255, 0.1)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.background = '';
            this.style.boxShadow = '';
        });
    });
}

// ============================
// Button Ripple Effect
// ============================

document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    });
});

// ============================
// Page Load Animations
// ============================

window.addEventListener('load', function() {
    // Fade in page elements
    const elements = document.querySelectorAll('.page-header, .booking-form, .facility-card, .alert');
    elements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.animation = `slideInUp 0.6s ease ${index * 0.1}s forwards`;
    });
});

// ============================
// Filter Bookings (Admin)
// ============================

function filterBookingsButton(status) {
    const currentUrl = new URL(window.location);
    if (status === 'all') {
        currentUrl.searchParams.delete('status');
    } else {
        currentUrl.searchParams.set('status', status);
    }
    window.location.href = currentUrl.toString();
}

// ============================
// Export Table to CSV
// ============================

function exportTableToCSV(filename = 'bookings.csv') {
    const csv = [];
    const rows = document.querySelectorAll('.table tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = Array.from(cols).map(col => {
            let text = col.textContent.trim();
            text = text.replace(/"/g, '""');
            return `"${text}"`;
        }).join(',');
        csv.push(csvRow);
    });
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename;
    downloadLink.click();
}

// ============================
// Sort Table
// ============================

function sortTable(n) {
    const table = document.querySelector('.table');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    
    rows.sort((a, b) => {
        const aVal = a.cells[n].textContent.trim();
        const bVal = b.cells[n].textContent.trim();
        return aVal.localeCompare(bVal);
    });
    
    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
}

// ============================
// Input Focus Glow
// ============================

document.querySelectorAll('input, select, textarea').forEach(input => {
    input.addEventListener('focus', function() {
        this.style.borderColor = 'var(--neon-pink)';
        this.style.boxShadow = '0 0 15px var(--neon-pink), inset 0 0 10px rgba(0, 217, 255, 0.1)';
    });
    
    input.addEventListener('blur', function() {
        this.style.borderColor = '';
        this.style.boxShadow = '';
    });
});

// ============================
// Scroll Animations
// ============================

const observerOptions = {
    threshold: 0.1,
    rootMargin: '50px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'slideInUp 0.6s ease forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll('.booking-form, .facility-card, .stats-card').forEach(el => {
    observer.observe(el);
});
