# DriveFlow – Vehicle Rental & Fleet Management System

## Overview

DriveFlow is a web-based Vehicle Rental and Fleet Management System developed using PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap.

The system allows customers to browse available vehicles, rent vehicles online, manage their rentals, and provide feedback. Administrators can manage vehicles, customers, rentals, payments, notifications, and view business analytics through an interactive dashboard.

---

## Features

### Customer Features

* User Registration
* User Login & Logout
* Browse Available Vehicles
* View Vehicle Details
* Rent Vehicles
* View Rental History
* Submit Feedback & Ratings
* Manage Profile

### Admin Features

* Secure Admin Login
* Dashboard Overview
* Add New Vehicles
* Edit Vehicle Information
* Delete Vehicles
* Manage Vehicle Types
* View and Manage Customers
* Manage Rentals
* Manage Payments
* View Customer Feedback
* Activity Logs
* Notifications System
* Business Analytics Dashboard

---

## Business Analytics

The analytics module provides:

* Total Revenue
* Total Customers
* Total Rentals
* Available Vehicles
* Rental Trends
* Vehicle Utilization Statistics
* Customer Activity Reports

---

## Technologies Used

### Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap 5
* Bootstrap Icons

### Backend

* PHP 8

### Database

* MySQL

### Development Environment

* XAMPP
* Visual Studio Code
* Git & GitHub

---

## Database Structure

The project contains the following tables:

1. users
2. vehicles
3. vehicle_types
4. rentals
5. payments
6. feedback
7. activity_logs
8. notifications

---

## Project Structure

```text
driveflow/
│
├── admin/
├── customer/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── database/
│   └── driveflow.sql
│
├── includes/
│   ├── config.php
│   ├── admin_header.php
│   └── admin_sidebar.php
│
├── index.php
├── login.php
├── register.php
├── logout.php
└── README.md
```

---

## Installation Guide

### Step 1

Install XAMPP and start:

* Apache
* MySQL

### Step 2

Copy the project folder into:

```text
xampp/htdocs/
```

### Step 3

Create a database named:

```sql
driveflow
```

### Step 4

Import:

```text
database/driveflow.sql
```

using phpMyAdmin.

### Step 5

Configure database settings in:

```text
includes/config.php
```

### Step 6

Open the browser and run:

```text
http://localhost/driveflow
```

---

## Sample Accounts

### Admin

Email:

```text
admin@driveflow.com
```

Password:

```text
admin123
```

### Customer

Email:

```text
ali@example.com
```

Password:

```text
customer123
```

---

## Future Improvements

* Online Payment Gateway Integration
* Email Notifications
* Vehicle Booking Calendar
* Vehicle Tracking System
* PDF Invoice Generation
* Mobile Application
* AI-Based Rental Recommendations

---

## Author

Muneeb Ahmed

BS Computer Science

University of Central Punjab (UCP)

---

## License

This project was developed for educational and academic purposes.
