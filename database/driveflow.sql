
CREATE DATABASE IF NOT EXISTS driveflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE driveflow;


CREATE TABLE vehicle_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','customer') DEFAULT 'customer',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_name VARCHAR(150) NOT NULL,
    vehicle_number VARCHAR(50) NOT NULL UNIQUE,
    type_id INT,
    brand VARCHAR(100),
    price_per_day DECIMAL(10,2) NOT NULL,
    status ENUM('Available','Rented','Maintenance') DEFAULT 'Available',
    image VARCHAR(255) DEFAULT 'default.jpg',
    description TEXT,
    seats INT DEFAULT 5,
    fuel_type VARCHAR(50) DEFAULT 'Petrol',
    transmission VARCHAR(50) DEFAULT 'Manual',
    year INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES vehicle_types(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE rentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    rental_status ENUM('Pending','Approved','Active','Completed','Cancelled') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash','Card','Online','Bank Transfer') DEFAULT 'Cash',
    payment_status ENUM('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_id INT,
    message TEXT NOT NULL,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Vehicle Types
INSERT INTO vehicle_types (type_name, description) VALUES
('Sedan', 'Comfortable 4-door passenger cars for city and highway travel'),
('SUV', 'Spacious Sport Utility Vehicles for family trips and off-road'),
('Pickup Truck', 'Heavy-duty trucks for cargo and adventure'),
('Luxury', 'Premium class vehicles with top-tier amenities'),
('Electric', 'Eco-friendly electric vehicles with zero emissions'),
('Van', 'Large capacity vans for groups and cargo transport'),
('Motorcycle', 'Fuel-efficient two-wheelers for quick city commutes');


INSERT INTO users (full_name, email, password, role, phone, address) VALUES
('Admin User', 'admin@driveflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+92-300-0000001', 'DriveFlow HQ, Karachi'),
('Ali Hassan', 'ali@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+92-300-1234567', '123 Garden Road, Karachi'),
('Sara Khan', 'sara@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+92-321-9876543', '456 Clifton Block 5, Karachi'),
('Usman Raza', 'usman@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+92-333-5556667', '789 DHA Phase 6, Lahore'),
('Fatima Malik', 'fatima@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+92-345-1112223', '321 Bahria Town, Islamabad'),
('Zain Ahmed', 'zain@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '+92-312-7778889', '654 Model Town, Lahore');

-- Vehicles
INSERT INTO vehicles (vehicle_name, vehicle_number, type_id, brand, price_per_day, status, description, seats, fuel_type, transmission, year) VALUES
('Corolla GLI', 'KHI-2021-001', 1, 'Toyota', 4500.00, 'Available', 'Well-maintained sedan with AC and premium sound system.', 5, 'Petrol', 'Automatic', 2021),
('Civic Oriel', 'LHR-2022-002', 1, 'Honda', 5500.00, 'Rented', 'Sporty sedan with leather seats and sunroof.', 5, 'Petrol', 'Automatic', 2022),
('Fortuner Sigma 4', 'ISB-2020-003', 2, 'Toyota', 9500.00, 'Available', 'Premium SUV with 4x4 capability and 7 seats.', 7, 'Diesel', 'Automatic', 2020),
('Sportage Alpha', 'KHI-2022-004', 2, 'Kia', 8000.00, 'Available', 'Stylish SUV with panoramic roof and smart features.', 5, 'Petrol', 'Automatic', 2022),
('Hilux Revo', 'LHR-2019-005', 3, 'Toyota', 7500.00, 'Maintenance', 'Robust pickup truck for tough terrain and heavy loads.', 5, 'Diesel', 'Manual', 2019),
('Prado TX', 'KHI-2021-006', 4, 'Toyota', 15000.00, 'Available', 'Luxury SUV with full leather interior and premium audio.', 7, 'Diesel', 'Automatic', 2021),
('S-Class', 'ISB-2023-007', 4, 'Mercedes-Benz', 25000.00, 'Available', 'Ultra-luxury executive sedan with chauffeur option.', 5, 'Petrol', 'Automatic', 2023),
('Model 3', 'KHI-2023-008', 5, 'Tesla', 12000.00, 'Rented', 'Full electric sedan with autopilot and 500km range.', 5, 'Electric', 'Automatic', 2023),
('H-1 Grand', 'LHR-2020-009', 6, 'Hyundai', 6000.00, 'Available', '12-seater van perfect for family and group tours.', 12, 'Diesel', 'Manual', 2020),
('Ninja 400', 'KHI-2022-010', 7, 'Kawasaki', 2500.00, 'Available', 'High-performance sport motorcycle for city and highway.', 2, 'Petrol', 'Manual', 2022);

-- Rentals (with various statuses and dates for chart data)
INSERT INTO rentals (user_id, vehicle_id, start_date, end_date, total_price, rental_status, created_at) VALUES
(2, 1, '2025-01-05', '2025-01-08', 13500.00, 'Completed', '2025-01-04 10:00:00'),
(3, 3, '2025-01-15', '2025-01-20', 47500.00, 'Completed', '2025-01-14 11:00:00'),
(4, 6, '2025-02-01', '2025-02-05', 60000.00, 'Completed', '2025-01-31 09:00:00'),
(5, 7, '2025-02-10', '2025-02-12', 50000.00, 'Completed', '2025-02-09 14:00:00'),
(6, 4, '2025-02-20', '2025-02-25', 40000.00, 'Completed', '2025-02-19 10:00:00'),
(2, 8, '2025-03-01', '2025-03-05', 48000.00, 'Completed', '2025-02-28 12:00:00'),
(3, 10, '2025-03-10', '2025-03-12', 5000.00, 'Completed', '2025-03-09 09:00:00'),
(4, 1, '2025-03-20', '2025-03-25', 22500.00, 'Completed', '2025-03-19 11:00:00'),
(5, 3, '2025-04-01', '2025-04-07', 66500.00, 'Completed', '2025-03-31 10:00:00'),
(6, 6, '2025-04-10', '2025-04-15', 75000.00, 'Completed', '2025-04-09 13:00:00'),
(2, 7, '2025-04-20', '2025-04-22', 50000.00, 'Completed', '2025-04-19 10:00:00'),
(3, 4, '2025-05-01', '2025-05-05', 32000.00, 'Completed', '2025-04-30 09:00:00'),
(4, 8, '2025-05-10', '2025-05-15', 60000.00, 'Completed', '2025-05-09 10:00:00'),
(5, 1, '2025-06-01', '2025-06-04', 13500.00, 'Completed', '2025-05-31 11:00:00'),
(6, 3, '2025-06-10', '2025-06-15', 47500.00, 'Completed', '2025-06-09 10:00:00'),
(2, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 16500.00, 'Active', NOW()),
(3, 8, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 60000.00, 'Active', NOW()),
(4, 9, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 24000.00, 'Approved', NOW()),
(5, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 45000.00, 'Pending', NOW()),
(6, 7, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 50000.00, 'Pending', NOW());

-- Payments
INSERT INTO payments (rental_id, amount, payment_method, payment_status, transaction_id) VALUES
(1, 13500.00, 'Card', 'Completed', 'TXN-2025-001'),
(2, 47500.00, 'Online', 'Completed', 'TXN-2025-002'),
(3, 60000.00, 'Bank Transfer', 'Completed', 'TXN-2025-003'),
(4, 50000.00, 'Card', 'Completed', 'TXN-2025-004'),
(5, 40000.00, 'Cash', 'Completed', 'TXN-2025-005'),
(6, 48000.00, 'Online', 'Completed', 'TXN-2025-006'),
(7, 5000.00, 'Cash', 'Completed', 'TXN-2025-007'),
(8, 22500.00, 'Card', 'Completed', 'TXN-2025-008'),
(9, 66500.00, 'Bank Transfer', 'Completed', 'TXN-2025-009'),
(10, 75000.00, 'Online', 'Completed', 'TXN-2025-010'),
(11, 50000.00, 'Card', 'Completed', 'TXN-2025-011'),
(12, 32000.00, 'Cash', 'Completed', 'TXN-2025-012'),
(13, 60000.00, 'Online', 'Completed', 'TXN-2025-013'),
(14, 13500.00, 'Card', 'Completed', 'TXN-2025-014'),
(15, 47500.00, 'Bank Transfer', 'Completed', 'TXN-2025-015'),
(16, 16500.00, 'Card', 'Pending', NULL),
(17, 60000.00, 'Online', 'Pending', NULL);

-- Feedback
INSERT INTO feedback (user_id, vehicle_id, message, rating) VALUES
(2, 1, 'Great car! Very clean and comfortable. Will rent again.', 5),
(3, 3, 'Amazing SUV, perfect for our family trip to Murree.', 5),
(4, 6, 'The Prado was incredible. Smooth drive and luxurious.', 5),
(5, 7, 'S-Class was absolutely top-notch. Worth every rupee!', 5),
(6, 4, 'Sportage was fun to drive. Good value for money.', 4),
(2, 8, 'Tesla experience was mind-blowing. Silent and fast!', 5),
(3, 10, 'Ninja was perfect for weekend rides. Great condition.', 4),
(4, 1, 'Corolla is always reliable. Good AC and comfortable.', 4),
(5, 3, 'Fortuner handled the mountain roads perfectly!', 5),
(6, 6, 'Prado again! Could not resist booking it twice.', 5);

-- Activity Logs
INSERT INTO activity_logs (user_id, activity) VALUES
(1, 'Admin logged in'),
(2, 'Customer Ali Hassan registered'),
(2, 'Ali Hassan rented Corolla GLI'),
(3, 'Sara Khan rented Fortuner Sigma 4'),
(1, 'Admin approved rental #3'),
(4, 'Usman Raza rented Prado TX'),
(5, 'Fatima Malik rented S-Class'),
(1, 'Admin added new vehicle: Tesla Model 3'),
(6, 'Zain Ahmed rented Sportage Alpha'),
(1, 'Admin updated vehicle status: Hilux Revo to Maintenance');

-- Notifications
INSERT INTO notifications (user_id, title, message, status) VALUES
(2, 'Rental Confirmed', 'Your rental for Corolla GLI has been confirmed. Enjoy your drive!', 'read'),
(3, 'New Vehicle Available', 'A new Tesla Model 3 has been added to our fleet. Book now!', 'unread'),
(4, 'Payment Received', 'Your payment of PKR 60,000 for Prado TX rental has been received.', 'read'),
(5, 'Rental Reminder', 'Your S-Class rental starts tomorrow. Please ensure timely pickup.', 'unread'),
(NULL, 'System Update', 'DriveFlow system maintenance scheduled for Sunday 2 AM - 4 AM.', 'unread'),
(2, 'Special Offer', '20% discount on weekend rentals this month! Book now.', 'unread'),
(3, 'Rental Complete', 'Your Fortuner rental has been completed. Leave a review!', 'read'),
(NULL, 'Fleet Expansion', '3 new luxury vehicles added to our fleet this week!', 'unread');