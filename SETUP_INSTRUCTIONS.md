# 🚀 Ekta Tay - Registration Setup Instructions

## Quick Setup Guide

### 1. Database Setup
1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services

2. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "Import" tab
   - Choose file: `sql/schema.sql`
   - Click "Go" to import

3. **Verify Setup**
   - Visit: `http://localhost:8080/Ekta-tay/backend/test_connection.php`
   - You should see: "✅ Database connection successful!"

### 2. Test Registration
1. **Open Registration Page**
   - Visit: `http://localhost:8080/Ekta-tay/Registration Page/register.html`

2. **Fill Registration Form**
   - Enter your details
   - Select role (Student or Recruiter)
   - Click "REGISTER"

3. **Success!**
   - You'll see a success message
   - Redirect to mock dashboard with "Database connection successfully" message

## What's Implemented

✅ **Backend PHP Files:**
- `backend/db.php` - Database connection
- `backend/register.php` - Registration API
- `backend/test_connection.php` - Connection test

✅ **Database Schema:**
- `sql/schema.sql` - Complete database structure
- Users, roles, and user_roles tables

✅ **Frontend Integration:**
- Updated registration form to connect to backend
- Real-time validation
- Success/error handling

✅ **Mock Dashboard:**
- Success message display
- "Database connection successfully" confirmation
- Beautiful UI with animations

## File Structure
```
Ekta-tay2/
├── backend/
│   ├── db.php              # Database connection
│   ├── register.php        # Registration API
│   └── test_connection.php # Connection test
├── Dashboard/
│   ├── dashboard.php       # Success dashboard
│   ├── dashboard.css       # Styling
│   └── dashboard.js        # Functionality
├── Registration Page/
│   ├── register.html       # Registration form
│   ├── script.js          # Updated with backend integration
│   └── styles.css         # Existing styles
└── sql/
    └── schema.sql         # Database schema
```

## Troubleshooting

**If database connection fails:**
1. Check XAMPP is running (Apache + MySQL)
2. Verify database name is `ekta_tay`
3. Check credentials in `backend/db.php`
4. Import `sql/schema.sql` in phpMyAdmin

**If registration fails:**
1. Check browser console for errors
2. Verify all form fields are filled
3. Check email format is valid
4. Ensure password is at least 8 characters

## Next Steps
- Test the complete registration flow
- Verify database entries in phpMyAdmin
- Ready to implement login functionality
