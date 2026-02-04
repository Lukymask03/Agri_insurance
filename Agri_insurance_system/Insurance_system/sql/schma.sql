-- ========================================
-- AGRICULTURAL INSURANCE SYSTEM - DATABASE SCHEMA
-- ========================================

-- Drop existing tables if they exist (use with caution!)
-- DROP TABLE IF EXISTS claim_audit_log;
-- DROP TABLE IF EXISTS claims;
-- DROP TABLE IF EXISTS premium_payments;
-- DROP TABLE IF EXISTS insurance_policies;
-- DROP TABLE IF EXISTS crops_livestock;
-- DROP TABLE IF EXISTS farmers;
-- DROP TABLE IF EXISTS users;

-- ========================================
-- TABLE 1: USERS
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','farmer','agent','adjuster') NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TABLE 2: FARMERS
-- ========================================
CREATE TABLE IF NOT EXISTS farmers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_full_name (full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TABLE 3: CROPS AND LIVESTOCK
-- ========================================
CREATE TABLE IF NOT EXISTS crops_livestock (
    id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT NOT NULL,
    category ENUM('crop','livestock') NOT NULL,
    type VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    location TEXT NOT NULL,
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    INDEX idx_farmer_id (farmer_id),
    INDEX idx_category (category),
    INDEX idx_type (type),
    INDEX idx_category_type (category, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TABLE 4: INSURANCE POLICIES
-- ========================================
CREATE TABLE IF NOT EXISTS insurance_policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    policy_type VARCHAR(100) NOT NULL,
    premium_rate DECIMAL(10,2) NOT NULL,
    coverage_amount DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired') DEFAULT 'active',
    FOREIGN KEY (item_id) REFERENCES crops_livestock(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date),
    INDEX idx_status_end_date (status, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TABLE 5: PREMIUM PAYMENTS
-- ========================================
CREATE TABLE IF NOT EXISTS premium_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    policy_id INT NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','bank_transfer','check','online') DEFAULT 'cash',
    received_by INT,
    FOREIGN KEY (policy_id) REFERENCES insurance_policies(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_policy_id (policy_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_received_by (received_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TABLE 6: CLAIMS
-- ========================================
CREATE TABLE IF NOT EXISTS claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    policy_id INT NOT NULL,
    claim_type VARCHAR(100) NOT NULL,
    claim_description TEXT NOT NULL,
    date_filed DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
    evidence_file VARCHAR(255),
    processed_by INT,
    decision_date DATETIME,
    settlement_amount DECIMAL(10,2),
    FOREIGN KEY (policy_id) REFERENCES insurance_policies(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_policy_id (policy_id),
    INDEX idx_status (status),
    INDEX idx_date_filed (date_filed),
    INDEX idx_processed_by (processed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TABLE 7: CLAIM AUDIT LOG (for triggers)
-- ========================================
CREATE TABLE IF NOT EXISTS claim_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    old_status VARCHAR(20),
    new_status VARCHAR(20),
    changed_by INT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE,
    INDEX idx_claim_id (claim_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- VERIFY SCHEMA CREATION
-- ========================================
SHOW TABLES;

-- Show table structures
DESCRIBE users;
DESCRIBE farmers;
DESCRIBE crops_livestock;
DESCRIBE insurance_policies;
DESCRIBE premium_payments;
DESCRIBE claims;
DESCRIBE claim_audit_log;