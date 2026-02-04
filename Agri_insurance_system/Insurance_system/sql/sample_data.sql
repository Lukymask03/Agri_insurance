-- Sample Data Generation Script for Agricultural Insurance System
-- This script generates 1000-2000 records per table

-- STEP 1: Create Admin User
INSERT INTO users (username, password, role, email, phone) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@agriinsure.com', '09171234567'),
('agent1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'agent1@agriinsure.com', '09181234567'),
('adjuster1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'adjuster', 'adjuster1@agriinsure.com', '09191234567');

-- STEP 2: Generate 1500 Farmer Users
DELIMITER $$
CREATE PROCEDURE generate_farmer_users()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE random_phone VARCHAR(20);
    
    WHILE i <= 1500 DO
        SET random_phone = CONCAT('09', LPAD(FLOOR(RAND() * 1000000000), 9, '0'));
        
        INSERT INTO users (username, password, role, email, phone)
        VALUES (
            CONCAT('farmer', i),
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
            'farmer',
            CONCAT('farmer', i, '@gmail.com'),
            random_phone
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL generate_farmer_users();

-- STEP 3: Generate Farmer Profiles
DELIMITER $$
CREATE PROCEDURE generate_farmer_profiles()
BEGIN
    DECLARE i INT DEFAULT 4;
    DECLARE provinces VARCHAR(1000) DEFAULT 'Nueva Ecija,Pangasinan,Isabela,Cagayan,Iloilo,Negros Occidental,South Cotabato,Sultan Kudarat,Bukidnon,Davao del Sur';
    DECLARE first_names VARCHAR(1000) DEFAULT 'Juan,Maria,Jose,Ana,Pedro,Rosa,Miguel,Carmen,Antonio,Isabel,Ramon,Elena,Carlos,Sofia,Luis,Teresa,Diego,Patricia,Fernando,Linda';
    DECLARE last_names VARCHAR(1000) DEFAULT 'Reyes,Santos,Cruz,Bautista,Garcia,Gonzales,Ramos,Mendoza,Torres,Flores,Rivera,Lopez,Gomez,Castillo,Morales,Aquino,Salazar,Villanueva,Hernandez,Santiago';
    
    DECLARE rand_first VARCHAR(50);
    DECLARE rand_last VARCHAR(50);
    DECLARE rand_province VARCHAR(50);
    DECLARE rand_barangay VARCHAR(50);
    
    WHILE i <= 1503 DO
        SET rand_first = SUBSTRING_INDEX(SUBSTRING_INDEX(first_names, ',', FLOOR(1 + RAND() * 20)), ',', -1);
        SET rand_last = SUBSTRING_INDEX(SUBSTRING_INDEX(last_names, ',', FLOOR(1 + RAND() * 20)), ',', -1);
        SET rand_province = SUBSTRING_INDEX(SUBSTRING_INDEX(provinces, ',', FLOOR(1 + RAND() * 10)), ',', -1);
        SET rand_barangay = CONCAT('Barangay ', FLOOR(1 + RAND() * 50));
        
        INSERT INTO farmers (user_id, full_name, address, contact_number)
        VALUES (
            i,
            CONCAT(rand_first, ' ', rand_last),
            CONCAT(rand_barangay, ', ', rand_province, ', Philippines'),
            CONCAT('09', LPAD(FLOOR(RAND() * 1000000000), 9, '0'))
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL generate_farmer_profiles();

-- STEP 4: Generate Crops and Livestock (2000 records)
DELIMITER $$
CREATE PROCEDURE generate_crops_livestock()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE farmer_count INT;
    DECLARE rand_farmer_id INT;
    DECLARE rand_category VARCHAR(10);
    DECLARE crops VARCHAR(500) DEFAULT 'Rice,Corn,Sugarcane,Coconut,Banana,Mango,Pineapple,Coffee,Cacao,Cassava,Sweet Potato,Tobacco,Abaca';
    DECLARE livestock VARCHAR(500) DEFAULT 'Cattle,Carabao,Goat,Pig,Chicken,Duck,Sheep,Horse,Turkey,Rabbit';
    DECLARE rand_type VARCHAR(50);
    DECLARE rand_quantity INT;
    
    SELECT COUNT(*) INTO farmer_count FROM farmers;
    
    WHILE i <= 2000 DO
        SET rand_farmer_id = FLOOR(1 + RAND() * farmer_count);
        SET rand_category = IF(RAND() > 0.5, 'crop', 'livestock');
        
        IF rand_category = 'crop' THEN
            SET rand_type = SUBSTRING_INDEX(SUBSTRING_INDEX(crops, ',', FLOOR(1 + RAND() * 13)), ',', -1);
            SET rand_quantity = FLOOR(100 + RAND() * 9900); -- 100 to 10000
        ELSE
            SET rand_type = SUBSTRING_INDEX(SUBSTRING_INDEX(livestock, ',', FLOOR(1 + RAND() * 10)), ',', -1);
            SET rand_quantity = FLOOR(5 + RAND() * 495); -- 5 to 500
        END IF;
        
        INSERT INTO crops_livestock (farmer_id, category, type, quantity, location)
        VALUES (
            rand_farmer_id,
            rand_category,
            rand_type,
            rand_quantity,
            (SELECT address FROM farmers WHERE id = rand_farmer_id)
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL generate_crops_livestock();

-- STEP 5: Generate Insurance Policies (1500 records)
DELIMITER $$
CREATE PROCEDURE generate_insurance_policies()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE item_count INT;
    DECLARE rand_item_id INT;
    DECLARE policy_types VARCHAR(500) DEFAULT 'Crop Failure Insurance,Multi-Peril Crop Insurance,Livestock Mortality Insurance,Weather-Based Insurance,Revenue Protection';
    DECLARE rand_policy VARCHAR(100);
    DECLARE rand_premium DECIMAL(10,2);
    DECLARE rand_coverage DECIMAL(10,2);
    DECLARE rand_start_date DATE;
    DECLARE rand_end_date DATE;
    
    SELECT COUNT(*) INTO item_count FROM crops_livestock;
    
    WHILE i <= 1500 DO
        SET rand_item_id = FLOOR(1 + RAND() * item_count);
        SET rand_policy = SUBSTRING_INDEX(SUBSTRING_INDEX(policy_types, ',', FLOOR(1 + RAND() * 5)), ',', -1);
        SET rand_premium = ROUND(5000 + RAND() * 45000, 2); -- 5000 to 50000
        SET rand_coverage = ROUND(rand_premium * (10 + RAND() * 20), 2); -- 10x to 30x premium
        SET rand_start_date = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 365) DAY);
        SET rand_end_date = DATE_ADD(rand_start_date, INTERVAL 365 DAY);
        
        INSERT INTO insurance_policies (item_id, policy_type, premium_rate, coverage_amount, start_date, end_date, status)
        VALUES (
            rand_item_id,
            rand_policy,
            rand_premium,
            rand_coverage,
            rand_start_date,
            rand_end_date,
            IF(rand_end_date < CURDATE(), 'expired', 'active')
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL generate_insurance_policies();

-- STEP 6: Generate Premium Payments (2000 records)
DELIMITER $$
CREATE PROCEDURE generate_premium_payments()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE policy_count INT;
    DECLARE rand_policy_id INT;
    DECLARE rand_amount DECIMAL(10,2);
    DECLARE rand_date DATETIME;
    
    SELECT COUNT(*) INTO policy_count FROM insurance_policies;
    
    WHILE i <= 2000 DO
        SET rand_policy_id = FLOOR(1 + RAND() * policy_count);
        
        SELECT premium_rate INTO rand_amount 
        FROM insurance_policies 
        WHERE id = rand_policy_id;
        
        SET rand_date = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY);
        
        INSERT INTO premium_payments (policy_id, payment_date, amount, payment_method, received_by)
        VALUES (
            rand_policy_id,
            rand_date,
            rand_amount,
            'cash',
            2 -- agent1
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL generate_premium_payments();

-- STEP 7: Generate Claims (1000 records)
DELIMITER $$
CREATE PROCEDURE generate_claims()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE policy_count INT;
    DECLARE rand_policy_id INT;
    DECLARE claim_types VARCHAR(500) DEFAULT 'Drought Damage,Flood Damage,Pest Infestation,Disease Outbreak,Natural Disaster,Fire Damage,Theft,Accidental Death';
    DECLARE statuses VARCHAR(100) DEFAULT 'pending,approved,rejected,paid';
    DECLARE rand_claim_type VARCHAR(100);
    DECLARE rand_status VARCHAR(20);
    DECLARE rand_settlement DECIMAL(10,2);
    DECLARE rand_date DATETIME;
    
    SELECT COUNT(*) INTO policy_count FROM insurance_policies;
    
    WHILE i <= 1000 DO
        SET rand_policy_id = FLOOR(1 + RAND() * policy_count);
        SET rand_claim_type = SUBSTRING_INDEX(SUBSTRING_INDEX(claim_types, ',', FLOOR(1 + RAND() * 8)), ',', -1);
        SET rand_status = SUBSTRING_INDEX(SUBSTRING_INDEX(statuses, ',', FLOOR(1 + RAND() * 4)), ',', -1);
        SET rand_date = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY);
        
        SELECT coverage_amount * (0.3 + RAND() * 0.7) INTO rand_settlement
        FROM insurance_policies
        WHERE id = rand_policy_id;
        
        INSERT INTO claims (policy_id, claim_type, claim_description, date_filed, status, processed_by, decision_date, settlement_amount)
        VALUES (
            rand_policy_id,
            rand_claim_type,
            CONCAT('Claim for ', rand_claim_type, ' affecting insured property.'),
            rand_date,
            rand_status,
            IF(rand_status != 'pending', 3, NULL), -- adjuster1
            IF(rand_status != 'pending', DATE_ADD(rand_date, INTERVAL FLOOR(7 + RAND() * 30) DAY), NULL),
            IF(rand_status IN ('approved', 'paid'), rand_settlement, NULL)
        );
        
        SET i = i + 1;
    END WHILE;
END$$
DELIMITER ;

CALL generate_claims();

-- Clean up stored procedures
DROP PROCEDURE IF EXISTS generate_farmer_users;
DROP PROCEDURE IF EXISTS generate_farmer_profiles;
DROP PROCEDURE IF EXISTS generate_crops_livestock;
DROP PROCEDURE IF EXISTS generate_insurance_policies;
DROP PROCEDURE IF EXISTS generate_premium_payments;
DROP PROCEDURE IF EXISTS generate_claims;

-- Verify data
SELECT 'Users' as TableName, COUNT(*) as RecordCount FROM users
UNION ALL
SELECT 'Farmers', COUNT(*) FROM farmers
UNION ALL
SELECT 'Crops/Livestock', COUNT(*) FROM crops_livestock
UNION ALL
SELECT 'Insurance Policies', COUNT(*) FROM insurance_policies
UNION ALL
SELECT 'Premium Payments', COUNT(*) FROM premium_payments
UNION ALL
SELECT 'Claims', COUNT(*) FROM claims;