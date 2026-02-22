<?php
/* ============================
   EMAIL CONFIGURATION & FUNCTIONS
   ============================ */

// Email configuration
define('SENDER_EMAIL', 'noreply@schoolbooking.local');
define('SENDER_NAME', 'School Booking System');
define('ADMIN_EMAIL', 'admin@schoolbooking.local');

// ============================
// Send Email Function
// ============================

function send_email($to, $subject, $message, $headers = '') {
    $default_headers = "MIME-Version: 1.0" . "\r\n";
    $default_headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $default_headers .= "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">" . "\r\n";
    
    if (!empty($headers)) {
        $default_headers .= $headers;
    }
    
    // In production, use mail() function
    // mail($to, $subject, $message, $default_headers);
    
    // For development, log emails to file
    $log_message = "TO: $to\nSUBJECT: $subject\nDATE: " . date('Y-m-d H:i:s') . "\n\n$message\n\n" . str_repeat("=", 80) . "\n\n";
    file_put_contents('/var/www/html/booking_system/logs/emails.log', $log_message, FILE_APPEND);
    
    return true;
}

// ============================
// Email Templates
// ============================

function get_email_header() {
    return '
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 20px 0;">
        <tr>
            <td align="center" style="padding: 20px;">
                <h1 style="color: white; margin: 0; font-size: 28px;">📅 School Booking System</h1>
            </td>
        </tr>
    </table>';
}

function get_email_footer() {
    return '
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8fafc; padding: 20px 0; border-top: 1px solid #e2e8f0;">
        <tr>
            <td align="center" style="color: #64748b; font-size: 12px; padding: 20px;">
                <p style="margin: 5px 0;">© 2026 School Booking System. All rights reserved.</p>
                <p style="margin: 5px 0;">For support, contact the administration.</p>
            </td>
        </tr>
    </table>';
}

function get_email_body_wrapper($content) {
    return '
    <table width="100%" cellpadding="0" cellspacing="0" style="background: white;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table width="100%" max-width="600px" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding: 20px; background: white; border-radius: 10px;">
                            ' . $content . '
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
}

// ============================
// Booking Confirmation Email
// ============================

function send_booking_confirmation_email($user_email, $user_name, $booking_id, $facility_name, $booking_date, $time_slot, $purpose) {
    $content = '
        <h2 style="color: #1e293b; margin-bottom: 20px;">Booking Confirmation</h2>
        <p style="color: #64748b; line-height: 1.6;">Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        
        <p style="color: #64748b; line-height: 1.6;">Your booking has been successfully created! Here are the details:</p>
        
        <table width="100%" cellpadding="15" cellspacing="0" style="background: #f8fafc; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6366f1;">
            <tr>
                <td>
                    <p style="margin: 0; color: #64748b; font-size: 13px;"><strong>Booking ID:</strong> #' . $booking_id . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Facility:</strong> ' . htmlspecialchars($facility_name) . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Date:</strong> ' . date('F d, Y', strtotime($booking_date)) . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Time Slot:</strong> ' . $time_slot . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Status:</strong> <span style="color: #f59e0b; font-weight: bold;">Pending Approval</span></p>
                    ' . (!empty($purpose) ? '<p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Purpose:</strong> ' . htmlspecialchars($purpose) . '</p>' : '') . '
                </td>
            </tr>
        </table>
        
        <p style="color: #64748b; line-height: 1.6; margin-top: 20px;">Your booking is now pending approval from the administration. You will receive another email once your request has been reviewed.</p>
        
        <p style="color: #64748b; line-height: 1.6; margin-top: 20px;">If you have any questions, please contact the school administration.</p>
        
        <p style="color: #64748b; margin-top: 30px;">Best regards,<br><strong>School Booking System</strong></p>';
    
    $message = get_email_header() . get_email_body_wrapper($content) . get_email_footer();
    $subject = 'Booking Confirmation - ' . htmlspecialchars($facility_name);
    
    return send_email($user_email, $subject, $message);
}

// ============================
// Booking Approval Email
// ============================

function send_booking_approval_email($user_email, $user_name, $booking_id, $facility_name, $booking_date, $time_slot) {
    $content = '
        <h2 style="color: #1e293b; margin-bottom: 20px;">✓ Booking Approved</h2>
        <p style="color: #64748b; line-height: 1.6;">Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        
        <p style="color: #64748b; line-height: 1.6;">Great news! Your booking has been <strong style="color: #10b981;">approved</strong>.</p>
        
        <table width="100%" cellpadding="15" cellspacing="0" style="background: rgba(16, 185, 129, 0.1); border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;">
            <tr>
                <td>
                    <p style="margin: 0; color: #059669; font-size: 13px;"><strong>Booking ID:</strong> #' . $booking_id . '</p>
                    <p style="margin: 8px 0 0 0; color: #059669; font-size: 13px;"><strong>Facility:</strong> ' . htmlspecialchars($facility_name) . '</p>
                    <p style="margin: 8px 0 0 0; color: #059669; font-size: 13px;"><strong>Date:</strong> ' . date('F d, Y', strtotime($booking_date)) . '</p>
                    <p style="margin: 8px 0 0 0; color: #059669; font-size: 13px;"><strong>Time Slot:</strong> ' . $time_slot . '</p>
                    <p style="margin: 8px 0 0 0; color: #059669; font-size: 13px;"><strong>Status:</strong> <span style="color: #10b981; font-weight: bold;">✓ Approved</span></p>
                </td>
            </tr>
        </table>
        
        <p style="color: #64748b; line-height: 1.6; margin-top: 20px;">Your facility reservation is confirmed. Make sure to note the date and time.</p>
        
        <p style="color: #64748b; margin-top: 30px;">Best regards,<br><strong>School Booking System</strong></p>';
    
    $message = get_email_header() . get_email_body_wrapper($content) . get_email_footer();
    $subject = '✓ Booking Approved - ' . htmlspecialchars($facility_name);
    
    return send_email($user_email, $subject, $message);
}

// ============================
// Booking Rejection Email
// ============================

function send_booking_rejection_email($user_email, $user_name, $booking_id, $facility_name, $booking_date, $time_slot) {
    $content = '
        <h2 style="color: #1e293b; margin-bottom: 20px;">✗ Booking Rejected</h2>
        <p style="color: #64748b; line-height: 1.6;">Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
        
        <p style="color: #64748b; line-height: 1.6;">Unfortunately, your booking request has been <strong style="color: #ef4444;">rejected</strong>.</p>
        
        <table width="100%" cellpadding="15" cellspacing="0" style="background: rgba(239, 68, 68, 0.1); border-radius: 8px; margin: 20px 0; border-left: 4px solid #ef4444;">
            <tr>
                <td>
                    <p style="margin: 0; color: #dc2626; font-size: 13px;"><strong>Booking ID:</strong> #' . $booking_id . '</p>
                    <p style="margin: 8px 0 0 0; color: #dc2626; font-size: 13px;"><strong>Facility:</strong> ' . htmlspecialchars($facility_name) . '</p>
                    <p style="margin: 8px 0 0 0; color: #dc2626; font-size: 13px;"><strong>Date:</strong> ' . date('F d, Y', strtotime($booking_date)) . '</p>
                    <p style="margin: 8px 0 0 0; color: #dc2626; font-size: 13px;"><strong>Time Slot:</strong> ' . $time_slot . '</p>
                    <p style="margin: 8px 0 0 0; color: #dc2626; font-size: 13px;"><strong>Status:</strong> <span style="color: #ef4444; font-weight: bold;">✗ Rejected</span></p>
                </td>
            </tr>
        </table>
        
        <p style="color: #64748b; line-height: 1.6; margin-top: 20px;">This facility may not be available at the requested time, or there may be a scheduling conflict. Please try another date or time slot.</p>
        
        <p style="color: #64748b; line-height: 1.6; margin-top: 20px;">If you have questions, please contact the school administration.</p>
        
        <p style="color: #64748b; margin-top: 30px;">Best regards,<br><strong>School Booking System</strong></p>';
    
    $message = get_email_header() . get_email_body_wrapper($content) . get_email_footer();
    $subject = '✗ Booking Rejected - ' . htmlspecialchars($facility_name);
    
    return send_email($user_email, $subject, $message);
}

// ============================
// New Booking Notification (Admin)
// ============================

function send_new_booking_admin_email($user_name, $user_email, $booking_id, $facility_name, $booking_date, $time_slot, $purpose) {
    $content = '
        <h2 style="color: #1e293b; margin-bottom: 20px;">New Booking Submission</h2>
        <p style="color: #64748b; line-height: 1.6;"><strong>A new booking request has been submitted and is pending your approval.</strong></p>
        
        <table width="100%" cellpadding="15" cellspacing="0" style="background: #f8fafc; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6366f1;">
            <tr>
                <td>
                    <p style="margin: 0; color: #64748b; font-size: 13px;"><strong>Booking ID:</strong> #' . $booking_id . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>User:</strong> ' . htmlspecialchars($user_name) . ' (' . htmlspecialchars($user_email) . ')</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Facility:</strong> ' . htmlspecialchars($facility_name) . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Date:</strong> ' . date('F d, Y', strtotime($booking_date)) . '</p>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Time Slot:</strong> ' . $time_slot . '</p>
                    ' . (!empty($purpose) ? '<p style="margin: 8px 0 0 0; color: #64748b; font-size: 13px;"><strong>Purpose:</strong> ' . htmlspecialchars($purpose) . '</p>' : '') . '
                </td>
            </tr>
        </table>
        
        <p style="color: #64748b; line-height: 1.6; margin-top: 20px;">Please log in to the admin dashboard to review and approve or reject this booking.</p>
        
        <p style="color: #64748b; margin-top: 30px;">Best regards,<br><strong>School Booking System</strong></p>';
    
    $message = get_email_header() . get_email_body_wrapper($content) . get_email_footer();
    $subject = '[New Booking] ' . htmlspecialchars($facility_name) . ' - Admin Approval Needed';
    
    return send_email(ADMIN_EMAIL, $subject, $message);
}

?>
