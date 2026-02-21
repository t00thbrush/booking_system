# 🏫 Online Booking System for School Facilities

## 📖 Project Description
The **Online Booking System** is a web-based application designed to allow internal and external users to book school facilities, such as the Indoor Stadium and Main Hall, efficiently and accurately.  

Currently, the booking process is manual, error-prone, and inefficient. This system automates reservations, prevents double bookings, keeps digital records, and provides administrators with tools to manage all bookings.

---

## 🎯 Project Objectives
- Enable users to book school facilities online.  
- Prevent double bookings.  
- Maintain digital records of all reservations.  
- Provide booking confirmations to users.  
- Allow admins to monitor and manage all bookings efficiently.

---

## 🛠️ Technologies Used
- HTML, CSS, JavaScript  
- PHP (Backend)  
- MySQL (Database)  
- Local Development Environment: Linux Mint XFCE, VSCode  

---

## ⚙️ System Features

### User Management
- User Registration & Login  
- Roles: Admin, Staff, External User  

### Booking Management
- Select Facility, Date, and Time Slot  
- Book, Cancel, Reschedule  
- Prevent double bookings automatically  

### Admin Dashboard
- Approve or Reject Bookings  
- Manage Users  
- View Facility Pages  

### Notifications
- Simple confirmation messages on booking or cancellation (JS alerts for MVP)  

### Reports
- Daily, Weekly, Monthly booking summaries (table view)  

---

## 💻 System Requirements

### Hardware
- Laptop or Desktop Computer  
- Minimum 4GB RAM  
- 2GB Free Storage  

### Software
- Linux Mint / Windows / macOS  
- Web Browser (Chrome / Firefox)  
- VSCode or any Code Editor  
- PHP 7.x or higher  
- MySQL / MariaDB  

---

## 🗄️ Database Structure

**Users Table**
- `user_id` (Primary Key)  
- `name`  
- `role` (Admin/Staff/External)  
- `username`  
- `password`  

**Facilities Table**
- `facility_id` (Primary Key)  
- `facility_name`  
- `description`  
- `capacity`  

**Bookings Table**
- `booking_id` (Primary Key)  
- `user_id` (Foreign Key → Users)  
- `facility_id` (Foreign Key → Facilities)  
- `booking_date`  
- `time_slot`  
- `status` (Pending / Approved / Rejected)  

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3+
- XAMPP / LAMP / LEMP Stack
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Step-by-Step Setup

#### 1. **Prepare the Environment**
   - Install XAMPP or LAMP Stack with PHP and MySQL
   - Start Apache and MySQL services

#### 2. **Clone/Copy Project**
   ```
   cp -r booking_system /var/www/html/
   cd /var/www/html/booking_system
   ```

#### 3. **Initialize Database**
   - Open MySQL terminal or phpMyAdmin
   - Run the initialization script: `http://localhost/booking_system/init.php`
   - This will automatically:
     - Create the database `booking_system`
     - Create all required tables (users, facilities, bookings)
     - Insert sample facilities (5 facilities)
     - Insert demo users and bookings

#### 4. **Access the System**
   - Open browser and navigate to: `http://localhost/booking_system/`
   - Login with demo credentials below

### Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| User | `user1` | `user123` |
| Staff | `staff1` | `user123` |

### File Structure
```
booking_system/
├── index.php              # Login page
├── register.php           # Registration page
├── booking.php            # Booking management
├── admin.php              # Admin dashboard
├── db.php                 # Database connection
├── session.php            # Session & utility functions
├── init.php               # Database initialization
├── readme.md              # Documentation
├── css/
│   └── style.css          # Responsive styling
├── js/
│   └── script.js          # Client-side JavaScript
└── api/
    └── check_availability.php  # Availability API
```

### Features Implemented

✅ **User Authentication**
- User Registration
- Login/Logout
- Password Hashing (bcrypt)
- Role-based access control

✅ **Facility Booking**
- Select from 5 available facilities
- Choose booking date (minimum next day)
- Select from 10 time slots (9 AM - 6 PM)
- Cancel bookings
- View booking history
- Prevent double bookings

✅ **Admin Dashboard**
- View all bookings with filters
- Approve/Reject pending bookings
- Real-time statistics
- Facility booking summary
- User management overview

✅ **Responsive Design**
- Mobile-friendly interface
- Works on desktop, tablet, and mobile
- Adaptive layouts using CSS Grid and Flexbox

✅ **Database**
- MySQL with proper relationships
- Foreign key constraints
- Unique booking constraints (prevents double-booking)
- Proper indexing

✅ **UI/UX**
- Clean, modern design
- Color-coded status badges
- Intuitive navigation
- Form validation
- Alert notifications

### Database Schema

**Users Table**
```sql
- user_id (int, Primary Key)
- name (varchar)
- role (enum: Admin/Staff/External)
- username (varchar, Unique)
- password (varchar, hashed)
- email (varchar, Unique)
- created_at (timestamp)
```

**Facilities Table**
```sql
- facility_id (int, Primary Key)
- facility_name (varchar)
- description (text)
- capacity (int)
- created_at (timestamp)
```

**Bookings Table**
```sql
- booking_id (int, Primary Key)
- user_id (int, Foreign Key)
- facility_id (int, Foreign Key)
- booking_date (date)
- time_slot (varchar)
- status (enum: Pending/Approved/Rejected/Cancelled)
- purpose (varchar)
- notes (text)
- created_at (timestamp)
```

### Available Time Slots
- 09:00 AM
- 10:00 AM
- 11:00 AM
- 12:00 PM
- 01:00 PM
- 02:00 PM
- 03:00 PM
- 04:00 PM
- 05:00 PM
- 06:00 PM

### Available Facilities
1. **Indoor Stadium** (Capacity: 500)
   - Large indoor sports facility with basketball and volleyball courts

2. **Main Hall** (Capacity: 300)
   - Main auditorium for events, presentations, and gatherings

3. **Library** (Capacity: 100)
   - Study center with reading rooms and meeting spaces

4. **Conference Room A** (Capacity: 50)
   - Professional conference room with AV equipment

5. **Conference Room B** (Capacity: 30)
   - Smaller conference room for team meetings

### Troubleshooting

**Database Connection Error**
- Ensure MySQL is running
- Check DB_SERVER, DB_USER, DB_PASSWORD in `db.php`
- Verify user has database creation permissions

**Page Not Found (404)**
- Ensure files are in `/var/www/html/booking_system/`
- Verify Apache is serving the correct directory
- Check file permissions

**Login Failed**
- Ensure `init.php` has been run to create demo users
- Check that MySQL is running
- Clear browser cache and try again

### Security Notes
- Passwords are hashed using bcrypt
- SQL queries use prepared statements to prevent injection
- Sessions are used for user authentication
- All user input is validated and sanitized  

---

## 👨‍💻 Developed By
1. Menula Geeneth [Grade 12C - Technology Section]  
2. Pranitha Salini [Grade 12D - Maths Section]  
3. Kavithma Thathsarani [Grade 12D - Commerce Section]  
4. Shalomi Joseph [Grade 12D - Commerce Section]  
5. Mohomad Shameeth [Grade 12D - Commerce Section]  

---

## 📅 Project Duration
1 Month Development Period

---

## 📝 Notes
This project is fully developed from scratch by the team without using pre-built systems.  
All features are implemented to demonstrate practical software development skills.