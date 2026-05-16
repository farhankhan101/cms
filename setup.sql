-- Courier Management System Database Schema (Complete)
CREATE DATABASE IF NOT EXISTS courier_cms;
USE courier_cms;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'agent', 'user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cities table
CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    state VARCHAR(100)
);

-- Courier Types table
CREATE TABLE IF NOT EXISTS courier_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    base_rate DECIMAL(10, 2) NOT NULL
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    phone VARCHAR(20),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Agents table
CREATE TABLE IF NOT EXISTS agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    city_id INT,
    branch_code VARCHAR(50) UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(id)
);

-- Shipments table
CREATE TABLE IF NOT EXISTS shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_no VARCHAR(50) UNIQUE NOT NULL,
    sender_id INT, -- Link to customers or users (optional)
    sender_name VARCHAR(100), -- Direct sender name
    receiver_name VARCHAR(100),
    receiver_phone VARCHAR(20),
    receiver_address TEXT,
    agent_id INT,
    courier_type_id INT,
    from_city_id INT,
    to_city_id INT,
    weight DECIMAL(10, 2),
    amount DECIMAL(10, 2),
    status ENUM('booked', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'booked',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_delivery DATE,
    FOREIGN KEY (from_city_id) REFERENCES cities(id),
    FOREIGN KEY (to_city_id) REFERENCES cities(id),
    FOREIGN KEY (courier_type_id) REFERENCES courier_types(id),
    FOREIGN KEY (agent_id) REFERENCES agents(id)
);

-- Shipment Status History (Audit Log)
CREATE TABLE IF NOT EXISTS shipment_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT,
    status VARCHAR(50),
    note TEXT,
    updated_by INT, -- user_id
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Bills table
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT,
    amount DECIMAL(10, 2),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- SMS Logs
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT,
    phone VARCHAR(20),
    message TEXT,
    status VARCHAR(50),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
);

-- Insert Initial Admin (Password: admin123)
INSERT IGNORE INTO users (name, email, password, role) 
VALUES ('System Admin', 'admin@cms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample Data
INSERT IGNORE INTO cities (city_name, state) VALUES 
('Karachi', 'Sindh'), 
('Lahore', 'Punjab'), 
('Faisalabad', 'Punjab'), 
('Rawalpindi', 'Punjab'), 
('Gujranwala', 'Punjab'), 
('Peshawar', 'KPK'), 
('Multan', 'Punjab'), 
('Hyderabad', 'Sindh'), 
('Islamabad', 'ICT'), 
('Quetta', 'Balochistan'), 
('Bahawalpur', 'Punjab'), 
('Sargodha', 'Punjab'), 
('Sialkot', 'Punjab'), 
('Sukkur', 'Sindh'), 
('Larkana', 'Sindh'), 
('Sheikhupura', 'Punjab'), 
('Rahim Yar Khan', 'Punjab'), 
('Jhang', 'Punjab'), 
('Dera Ghazi Khan', 'Punjab'), 
('Gujrat', 'Punjab'), 
('Sahiwal', 'Punjab'), 
('Wah Cantonment', 'Punjab'), 
('Mardan', 'KPK'), 
('Kasur', 'Punjab'), 
('Okara', 'Punjab'), 
('Mingora', 'KPK'), 
('Nawabshah', 'Sindh'), 
('Chiniot', 'Punjab'), 
('Kotri', 'Sindh'), 
('Kāmoke', 'Punjab'), 
('Hafizabad', 'Punjab'), 
('Sadiqabad', 'Punjab'), 
('Mirpur Khas', 'Sindh'), 
('Burewala', 'Punjab'), 
('Kohat', 'KPK'), 
('Khanewal', 'Punjab'), 
('Dera Ismail Khan', 'KPK'), 
('Turbat', 'Balochistan'), 
('Muzaffargarh', 'Punjab'), 
('Abbottabad', 'KPK');
INSERT IGNORE INTO courier_types (type_name, base_rate) VALUES ('Standard', 10.00), ('Express', 25.00), ('Overnight', 50.00);
