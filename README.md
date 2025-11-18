# Smart Cab Service - Backend Setup Guide

## Overview
This project includes a complete MySQL and PHP backend for the Smart Cab Service booking system.

## Database Setup

### 1. Create the Database
1. Open phpMyAdmin or MySQL command line
2. Import the `database.sql` file:
   ```sql
   mysql -u root -p < database.sql
   ```
   Or use phpMyAdmin to import the file directly.

### 2. Configure Database Connection
Edit `config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'smart_cab_db');
```

## File Structure

```
suhaib/
├── config.php              # Database configuration
├── db.php                  # Database connection handler
├── database.sql            # Database schema
├── api/
│   ├── save_booking.php    # Save new booking
│   ├── get_bookings.php    # Retrieve bookings
│   ├── search_booking.php  # Search bookings
│   ├── update_booking.php  # Update booking
│   └── delete_booking.php  # Delete booking
└── booking-details.html    # Frontend (updated to use API)
```

## API Endpoints

### 1. Save Booking
**POST** `/api/save_booking.php`
- Saves a new booking to the database
- Returns booking ID and details

**Request Body:**
```json
{
  "pickupLocation": "123 Main St",
  "dropoffLocation": "456 Oak Ave",
  "rideDate": "2024-12-25",
  "rideTime": "14:30",
  "carType": "Maruti Suzuki Dzire",
  "additionalNotes": "Please call on arrival",
  "customerName": "John Doe",
  "customerPhone": "1234567890"
}
```

### 2. Get Bookings
**GET** `/api/get_bookings.php`
- Retrieves bookings from database
- Query parameters:
  - `user_id` (optional): Filter by user ID
  - `booking_id` (optional): Get specific booking
  - `status` (optional): Filter by status
  - `limit` (optional): Limit results (default: 100)
  - `offset` (optional): Pagination offset

### 3. Search Booking
**GET/POST** `/api/search_booking.php`
- Searches bookings by pickup and dropoff locations
- Parameters:
  - `pickup`: Pickup location
  - `dropoff`: Dropoff location

### 4. Update Booking
**PUT/POST** `/api/update_booking.php`
- Updates booking information
- Request body must include `booking_id` and fields to update

### 5. Delete Booking
**DELETE/POST** `/api/delete_booking.php`
- Deletes a booking
- Request body: `{"booking_id": "BK123456"}`

## Database Tables

1. **users** - User accounts
2. **bookings** - Booking records
3. **feedbacks** - Customer feedback
4. **payments** - Payment transactions
5. **ride_history** - Completed ride history

## Testing

1. Start your local server (XAMPP, WAMP, or similar)
2. Ensure MySQL is running
3. Import the database schema
4. Update `config.php` with your database credentials
5. Open `booking-details.html` in your browser
6. Submit a booking to test the API

## Features

- ✅ Secure database connection
- ✅ Prepared statements (SQL injection protection)
- ✅ Input sanitization
- ✅ JSON API responses
- ✅ Error handling
- ✅ CORS support
- ✅ Booking ID generation
- ✅ Search functionality
- ✅ CRUD operations

## Notes

- Make sure PHP version is 7.4 or higher
- MySQL version 5.7 or higher recommended
- Enable mysqli extension in PHP
- For production, set `display_errors` to 0 in `config.php`
