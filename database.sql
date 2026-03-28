-- Society Maintenance System (provided schema)

-- 1. Create and use the Database
CREATE DATABASE society_maintenance_system;
USE society_maintenance_system;

-- 2. System_Users Table
CREATE TABLE System_Users (
    user_id INT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'User') NOT NULL
);

-- Enforce "only one Admin" at the database level
DELIMITER $$
CREATE TRIGGER trg_system_users_one_admin_ins
BEFORE INSERT ON System_Users
FOR EACH ROW
BEGIN
    IF NEW.role = 'Admin' THEN
        IF (SELECT COUNT(*) FROM System_Users WHERE role = 'Admin') >= 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one Admin account is allowed';
        END IF;
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_system_users_one_admin_upd
BEFORE UPDATE ON System_Users
FOR EACH ROW
BEGIN
    IF NEW.role = 'Admin' AND OLD.role <> 'Admin' THEN
        IF (SELECT COUNT(*) FROM System_Users WHERE role = 'Admin') >= 1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one Admin account is allowed';
        END IF;
    END IF;
END$$
DELIMITER ;

-- 3. Society Table
CREATE TABLE Society (
    society_id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    area VARCHAR(50) NOT NULL,
    pincode VARCHAR(10) NOT NULL
);

-- 4. Building Table
CREATE TABLE Building (
    building_id INT PRIMARY KEY,
    society_id INT NOT NULL,
    building_name VARCHAR(50) NOT NULL,
    total_floors INT NOT NULL,
    FOREIGN KEY (society_id) REFERENCES Society(society_id)
);

-- 5. Flat_Owner Table
CREATE TABLE Flat_Owner (
    owner_id INT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    dob DATE NOT NULL
);

-- 6. Owner_Mobile Table
CREATE TABLE Owner_Mobile (
    owner_id INT NOT NULL,
    mobile_no VARCHAR(15) NOT NULL,
    PRIMARY KEY (owner_id, mobile_no),
    FOREIGN KEY (owner_id) REFERENCES Flat_Owner(owner_id)
);

-- 7. Owner_Email Table
CREATE TABLE Owner_Email (
    owner_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    PRIMARY KEY (owner_id, email),
    FOREIGN KEY (owner_id) REFERENCES Flat_Owner(owner_id)
);

-- 8. Flat Table
CREATE TABLE Flat (
    flat_id INT PRIMARY KEY,
    building_id INT NOT NULL,
    owner_id INT,
    flat_number VARCHAR(10) NOT NULL,
    floor_number INT NOT NULL,
    flat_price DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (building_id) REFERENCES Building(building_id),
    FOREIGN KEY (owner_id) REFERENCES Flat_Owner(owner_id)
);

-- 9. Maintenance Table
CREATE TABLE Maintenance (
    maintenance_id INT PRIMARY KEY,
    m_id INT NOT NULL,
    owner_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    billing_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    status ENUM('Paid', 'Pending', 'Overdue') DEFAULT 'Pending',
    is_monthly BOOLEAN DEFAULT FALSE,
    is_yearly BOOLEAN DEFAULT FALSE,
    is_due BOOLEAN DEFAULT FALSE,
    is_emergency BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (owner_id) REFERENCES Flat_Owner(owner_id),
    UNIQUE (m_id, owner_id)
);
