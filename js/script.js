// ============================
// Dark Mode Toggle
// ============================

function initDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const savedMode = localStorage.getItem('darkMode');
    
    // Check if dark mode was previously enabled
    if (savedMode === 'enabled') {
        document.body.classList.add('dark-mode');
    }
    
    // Add click listener to toggle button
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('dark-mode');
            
            // Save preference
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                this.textContent = '☀️';
                this.title = 'Light Mode';
            } else {
                localStorage.setItem('darkMode', 'disabled');
                this.textContent = '🌙';
                this.title = 'Dark Mode';
            }
        });
    }
}

// ============================
// Form Validation
// ============================

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

function validateUsername(username) {
    return username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        }
    });

    return isValid;
}

function showFieldError(input, message) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    input.classList.add('error');
}

function clearFieldError(input) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
    }
    input.classList.remove('error');
}

// ============================
// Notifications with Animations
// ============================

function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '1000';
    alertDiv.style.maxWidth = '400px';
    alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    alertDiv.style.animation = 'slideInDown 0.5s ease';

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.style.animation = 'slideInDown 0.5s ease reverse';
        setTimeout(() => {
            alertDiv.remove();
        }, 500);
    }, 4000);
}

function showAlert(message, type = 'info') {
    showNotification(message, type);
}

function confirmAction(message) {
    return confirm(message);
}

// ============================
// Time Slot Selection
// ============================

function initTimeSlots() {
    const timeSlots = document.querySelectorAll('.time-slot');
    
    timeSlots.forEach(slot => {
        slot.addEventListener('click', function(e) {
            e.preventDefault();
            if (!this.classList.contains('booked')) {
                timeSlots.forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
                
                const timeInput = document.getElementById('time_slot');
                if (timeInput) {
                    timeInput.value = this.textContent;
                }
            }
        });
    });
}

function selectTimeSlot(slotElement, time) {
    if (slotElement.classList.contains('booked')) {
        showNotification('This time slot is not available', 'warning');
        return false;
    }
    
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    
    slotElement.classList.add('selected');
    document.getElementById('time_slot').value = time;
    return true;
}

// ============================
// Booking Functions
// ============================

function checkSlotAvailability(facilityId, bookingDate, callback) {
    fetch('api/check_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `facility_id=${facilityId}&booking_date=${bookingDate}`
    })
    .then(response => response.json())
    .then(data => {
        callback(data.available);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error checking availability', 'error');
    });
}

function submitBookingForm() {
    const form = document.getElementById('bookingForm');
    if (!form) return false;

    const facilityId = document.getElementById('facility_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const timeSlot = document.getElementById('time_slot').value;
    const purpose = document.getElementById('purpose').value;

    if (!facilityId || !bookingDate || !timeSlot) {
        showNotification('Please fill all required fields', 'warning');
        return false;
    }

    form.submit();
    return true;
}

function cancelBooking(bookingId) {
    if (confirmAction('Are you sure you want to cancel this booking?')) {
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('action', 'cancel');

        fetch('booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            showNotification('Booking cancelled successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            showNotification('Error cancelling booking', 'error');
        });
    }
}

function rescheduleBooking(bookingId) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (newDate) {
        const newTime = prompt('Enter new time (HH:MM):');
        if (newTime) {
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('action', 'reschedule');
            formData.append('new_date', newDate);
            formData.append('new_time', newTime);

            fetch('booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showNotification('Booking rescheduled successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                showNotification('Error rescheduling booking', 'error');
            });
        }
    }
}

// ============================
// Admin Functions
// ============================

function approveBooking(bookingId) {
    if (confirmAction('Approve this booking?')) {
        updateBookingStatus(bookingId, 'Approved');
    }
}

function rejectBooking(bookingId) {
    if (confirmAction('Reject this booking?')) {
        updateBookingStatus(bookingId, 'Rejected');
    }
}

function updateBookingStatus(bookingId, status) {
    const formData = new FormData();
    formData.append('booking_id', bookingId);
    formData.append('action', 'update_status');
    formData.append('status', status);

    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        showNotification(`Booking ${status.toLowerCase()} successfully`, 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        showNotification('Error updating booking', 'error');
    });
}

// ============================
// Date Utilities
// ============================

function getMinDate() {
    const today = new Date();
    today.setDate(today.getDate() + 1);
    return today.toISOString().split('T')[0];
}

function setMinDate(inputId) {
    const input = document.getElementById(inputId);
    if (input && input.type === 'date') {
        input.min = getMinDate();
    }
}

function isValidDate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
}

// ============================
// Table Functions
// ============================

function filterTable(tableId, searchInput) {
    const input = document.getElementById(searchInput);
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');

    input.addEventListener('keyup', () => {
        const filter = input.value.toUpperCase();

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < td.length; j++) {
                if (td[j].textContent.toUpperCase().includes(filter)) {
                    found = true;
                    break;
                }
            }

            tr[i].style.display = found ? '' : 'none';
        }
    });
}

function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    let rows = Array.from(table.querySelectorAll('tbody tr'));
    let ascending = true;

    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent;
        const bVal = b.cells[columnIndex].textContent;

        if (!isNaN(aVal) && !isNaN(bVal)) {
            return ascending ? aVal - bVal : bVal - aVal;
        }

        return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });

    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
}

// ============================
// Print & Export
// ============================

function printTable(tableId) {
    const table = document.getElementById(tableId);
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Print Report</title>');
    printWindow.document.write('<link rel="stylesheet" href="css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(table.outerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function exportTableToCSV(tableId, filename = 'report.csv') {
    const table = document.getElementById(tableId);
    let csv = [];
    
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));
    
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// ============================
// Smooth Page Load
// ============================

function addPageLoadAnimation() {
    document.body.style.opacity = '0';
    window.addEventListener('load', () => {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    });
}

// ============================
// Document Ready
// ============================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dark mode
    initDarkMode();
    
    // Initialize date inputs
    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (input.hasAttribute('min-today')) {
            input.min = getMinDate();
        }
    });

    // Initialize time slots
    if (document.querySelectorAll('.time-slot').length > 0) {
        initTimeSlots();
    }

    // Clear field errors on input
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('focus', function() {
            clearFieldError(this);
        });
    });
    
    // Add smooth animations to page elements
    const elements = document.querySelectorAll('.card, .form-container, .facility-card, .stat-card');
    elements.forEach((el, index) => {
        el.style.animationDelay = (index * 0.1) + 's';
    });
});

function validatePassword(password) {
    return password.length >= 6;
}

function validateUsername(username) {
    return username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        }
    });

    return isValid;
}

function showFieldError(input, message) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    input.classList.add('error');
}

function clearFieldError(input) {
    const errorDiv = input.parentElement.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
    }
    input.classList.remove('error');
}

// ============================
// Notifications
// ============================

function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '1000';
    alertDiv.style.maxWidth = '400px';
    alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}

function showAlert(message, type = 'info') {
    showNotification(message, type);
}

function confirmAction(message) {
    return confirm(message);
}

// ============================
// Time Slot Selection
// ============================

function initTimeSlots() {
    const timeSlots = document.querySelectorAll('.time-slot');
    
    timeSlots.forEach(slot => {
        slot.addEventListener('click', function() {
            if (!this.classList.contains('booked')) {
                timeSlots.forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
                
                const timeInput = document.getElementById('time_slot');
                if (timeInput) {
                    timeInput.value = this.textContent;
                }
            }
        });
    });
}

function selectTimeSlot(slotElement, time) {
    if (slotElement.classList.contains('booked')) {
        showNotification('This time slot is not available', 'warning');
        return false;
    }
    
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    
    slotElement.classList.add('selected');
    document.getElementById('time_slot').value = time;
    return true;
}

// ============================
// Booking Functions
// ============================

function checkSlotAvailability(facilityId, bookingDate, callback) {
    fetch('api/check_availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `facility_id=${facilityId}&booking_date=${bookingDate}`
    })
    .then(response => response.json())
    .then(data => {
        callback(data.available);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error checking availability', 'error');
    });
}

function submitBookingForm() {
    const form = document.getElementById('bookingForm');
    if (!form) return false;

    const facilityId = document.getElementById('facility_id').value;
    const bookingDate = document.getElementById('booking_date').value;
    const timeSlot = document.getElementById('time_slot').value;
    const purpose = document.getElementById('purpose').value;

    if (!facilityId || !bookingDate || !timeSlot) {
        showNotification('Please fill all required fields', 'warning');
        return false;
    }

    // Submit form
    form.submit();
    return true;
}

function cancelBooking(bookingId) {
    if (confirmAction('Are you sure you want to cancel this booking?')) {
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('action', 'cancel');

        fetch('booking.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            showNotification('Booking cancelled successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            showNotification('Error cancelling booking', 'error');
        });
    }
}

function rescheduleBooking(bookingId) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (newDate) {
        const newTime = prompt('Enter new time (HH:MM):');
        if (newTime) {
            const formData = new FormData();
            formData.append('booking_id', bookingId);
            formData.append('action', 'reschedule');
            formData.append('new_date', newDate);
            formData.append('new_time', newTime);

            fetch('booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                showNotification('Booking rescheduled successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                showNotification('Error rescheduling booking', 'error');
            });
        }
    }
}

// ============================
// Admin Functions
// ============================

function approveBooking(bookingId) {
    if (confirmAction('Approve this booking?')) {
        updateBookingStatus(bookingId, 'Approved');
    }
}

function rejectBooking(bookingId) {
    if (confirmAction('Reject this booking?')) {
        updateBookingStatus(bookingId, 'Rejected');
    }
}

function updateBookingStatus(bookingId, status) {
    const formData = new FormData();
    formData.append('booking_id', bookingId);
    formData.append('action', 'update_status');
    formData.append('status', status);

    fetch('admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        showNotification(`Booking ${status.toLowerCase()} successfully`, 'success');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        showNotification('Error updating booking', 'error');
    });
}

// ============================
// Date Utilities
// ============================

function getMinDate() {
    const today = new Date();
    today.setDate(today.getDate() + 1);
    return today.toISOString().split('T')[0];
}

function setMinDate(inputId) {
    const input = document.getElementById(inputId);
    if (input && input.type === 'date') {
        input.min = getMinDate();
    }
}

function isValidDate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
}

// ============================
// Table Functions
// ============================

function filterTable(tableId, searchInput) {
    const input = document.getElementById(searchInput);
    const table = document.getElementById(tableId);
    const tr = table.getElementsByTagName('tr');

    input.addEventListener('keyup', () => {
        const filter = input.value.toUpperCase();

        for (let i = 1; i < tr.length; i++) {
            const td = tr[i].getElementsByTagName('td');
            let found = false;

            for (let j = 0; j < td.length; j++) {
                if (td[j].textContent.toUpperCase().includes(filter)) {
                    found = true;
                    break;
                }
            }

            tr[i].style.display = found ? '' : 'none';
        }
    });
}

function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    let rows = Array.from(table.querySelectorAll('tbody tr'));
    let ascending = true;

    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent;
        const bVal = b.cells[columnIndex].textContent;

        if (!isNaN(aVal) && !isNaN(bVal)) {
            return ascending ? aVal - bVal : bVal - aVal;
        }

        return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });

    const tbody = table.querySelector('tbody');
    rows.forEach(row => tbody.appendChild(row));
}

// ============================
// Print & Export
// ============================

function printTable(tableId) {
    const table = document.getElementById(tableId);
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Print Report</title>');
    printWindow.document.write('<link rel="stylesheet" href="css/style.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(table.outerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function exportTableToCSV(tableId, filename = 'report.csv') {
    const table = document.getElementById(tableId);
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// ============================
// Document Ready
// ============================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date inputs
    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (input.hasAttribute('min-today')) {
            input.min = getMinDate();
        }
    });

    // Initialize time slots
    if (document.querySelectorAll('.time-slot').length > 0) {
        initTimeSlots();
    }

    // Clear field errors on input
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('focus', function() {
            clearFieldError(this);
        });
    });
});
