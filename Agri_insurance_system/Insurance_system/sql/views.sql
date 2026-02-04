-- ========================================
-- DATABASE VIEWS
-- ========================================

-- VIEW 1: Active policies with farmer details
CREATE OR REPLACE VIEW view_active_policies AS
SELECT 
    ip.id AS policy_id,
    ip.policy_type,
    ip.premium_rate,
    ip.coverage_amount,
    ip.start_date,
    ip.end_date,
    f.id AS farmer_id,
    f.full_name AS farmer_name,
    f.contact_number,
    f.address,
    cl.id AS item_id,
    cl.category,
    cl.type AS item_type,
    cl.quantity,
    cl.location,
    DATEDIFF(ip.end_date, CURDATE()) AS days_until_expiry
FROM insurance_policies ip
JOIN crops_livestock cl ON ip.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
WHERE ip.status = 'active';

-- VIEW 2: Claims summary by status
CREATE OR REPLACE VIEW view_claims_summary AS
SELECT 
    c.id AS claim_id,
    c.claim_type,
    c.claim_description,
    c.date_filed,
    c.status,
    c.settlement_amount,
    f.id AS farmer_id,
    f.full_name AS farmer_name,
    f.contact_number,
    ip.policy_type,
    ip.coverage_amount,
    cl.type AS item_type,
    cl.category,
    CONCAT(u.username, ' - ', u.role) AS processed_by,
    c.decision_date,
    DATEDIFF(COALESCE(c.decision_date, NOW()), c.date_filed) AS processing_days
FROM claims c
JOIN insurance_policies ip ON c.policy_id = ip.id
JOIN crops_livestock cl ON ip.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
LEFT JOIN users u ON c.processed_by = u.id;

-- VIEW 3: Farmer portfolio overview
CREATE OR REPLACE VIEW view_farmer_portfolio AS
SELECT 
    f.id AS farmer_id,
    f.full_name,
    f.address,
    f.contact_number,
    u.email,
    COUNT(DISTINCT cl.id) AS total_items,
    SUM(CASE WHEN cl.category = 'crop' THEN 1 ELSE 0 END) AS total_crops,
    SUM(CASE WHEN cl.category = 'livestock' THEN 1 ELSE 0 END) AS total_livestock,
    COUNT(DISTINCT ip.id) AS total_policies,
    SUM(CASE WHEN ip.status = 'active' THEN 1 ELSE 0 END) AS active_policies,
    COALESCE(SUM(CASE WHEN ip.status = 'active' THEN ip.coverage_amount ELSE 0 END), 0) AS total_coverage,
    COALESCE(SUM(CASE WHEN ip.status = 'active' THEN ip.premium_rate ELSE 0 END), 0) AS total_premium,
    COUNT(DISTINCT claims.id) AS total_claims,
    SUM(CASE WHEN claims.status = 'pending' THEN 1 ELSE 0 END) AS pending_claims
FROM farmers f
JOIN users u ON f.user_id = u.id
LEFT JOIN crops_livestock cl ON f.id = cl.farmer_id
LEFT JOIN insurance_policies ip ON cl.id = ip.item_id
LEFT JOIN claims ON ip.id = claims.policy_id
GROUP BY f.id, f.full_name, f.address, f.contact_number, u.email;

-- VIEW 4: Premium payment history with details
CREATE OR REPLACE VIEW view_payment_history AS
SELECT 
    pp.id AS payment_id,
    pp.payment_date,
    pp.amount,
    pp.payment_method,
    f.id AS farmer_id,
    f.full_name AS farmer_name,
    f.contact_number,
    ip.id AS policy_id,
    ip.policy_type,
    ip.premium_rate,
    cl.type AS item_type,
    cl.category,
    u.username AS received_by_user,
    u.role AS received_by_role
FROM premium_payments pp
JOIN insurance_policies ip ON pp.policy_id = ip.id
JOIN crops_livestock cl ON ip.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
LEFT JOIN users u ON pp.received_by = u.id;

-- VIEW 5: Comprehensive dashboard statistics
CREATE OR REPLACE VIEW view_dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM farmers) AS total_farmers,
    (SELECT COUNT(*) FROM crops_livestock) AS total_items,
    (SELECT COUNT(*) FROM crops_livestock WHERE category = 'crop') AS total_crops,
    (SELECT COUNT(*) FROM crops_livestock WHERE category = 'livestock') AS total_livestock,
    (SELECT COUNT(*) FROM insurance_policies) AS total_policies,
    (SELECT COUNT(*) FROM insurance_policies WHERE status = 'active') AS active_policies,
    (SELECT COUNT(*) FROM insurance_policies WHERE status = 'expired') AS expired_policies,
    (SELECT COALESCE(SUM(coverage_amount), 0) FROM insurance_policies WHERE status = 'active') AS total_coverage,
    (SELECT COALESCE(SUM(premium_rate), 0) FROM insurance_policies WHERE status = 'active') AS total_premiums,
    (SELECT COUNT(*) FROM claims) AS total_claims,
    (SELECT COUNT(*) FROM claims WHERE status = 'pending') AS pending_claims,
    (SELECT COUNT(*) FROM claims WHERE status = 'approved') AS approved_claims,
    (SELECT COUNT(*) FROM claims WHERE status = 'rejected') AS rejected_claims,
    (SELECT COUNT(*) FROM claims WHERE status = 'paid') AS paid_claims,
    (SELECT COALESCE(SUM(settlement_amount), 0) FROM claims WHERE status IN ('approved', 'paid')) AS total_settlements,
    (SELECT COUNT(*) FROM premium_payments) AS total_payments,
    (SELECT COALESCE(SUM(amount), 0) FROM premium_payments) AS total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM premium_payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())) AS monthly_revenue;

-- VIEW 6: Expiring policies (next 30 days)
CREATE OR REPLACE VIEW view_expiring_policies AS
SELECT 
    ip.id AS policy_id,
    ip.policy_type,
    ip.end_date,
    DATEDIFF(ip.end_date, CURDATE()) AS days_remaining,
    f.id AS farmer_id,
    f.full_name AS farmer_name,
    f.contact_number,
    f.address,
    cl.type AS item_type,
    cl.category,
    ip.premium_rate,
    ip.coverage_amount
FROM insurance_policies ip
JOIN crops_livestock cl ON ip.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
WHERE ip.status = 'active'
AND ip.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
ORDER BY ip.end_date ASC;

-- VIEW 7: High-value claims (above average settlement)
CREATE OR REPLACE VIEW view_high_value_claims AS
SELECT 
    c.id AS claim_id,
    c.claim_type,
    c.settlement_amount,
    c.status,
    c.date_filed,
    f.full_name AS farmer_name,
    ip.policy_type,
    ip.coverage_amount,
    (c.settlement_amount / ip.coverage_amount * 100) AS percentage_of_coverage
FROM claims c
JOIN insurance_policies ip ON c.policy_id = ip.id
JOIN crops_livestock cl ON ip.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
WHERE c.settlement_amount > (SELECT AVG(settlement_amount) FROM claims WHERE settlement_amount IS NOT NULL)
ORDER BY c.settlement_amount DESC;

-- Test views:
-- SELECT * FROM view_active_policies LIMIT 10;
-- SELECT * FROM view_claims_summary LIMIT 10;
-- SELECT * FROM view_farmer_portfolio LIMIT 10;
-- SELECT * FROM view_payment_history LIMIT 10;
-- SELECT * FROM view_dashboard_stats;
-- SELECT * FROM view_expiring_policies;
-- SELECT * FROM view_high_value_claims LIMIT 10;