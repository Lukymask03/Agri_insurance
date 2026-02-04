USE agri_insurance_system;
GO

-- PROCEDURE 1: Process claim and auto-approve based on criteria
CREATE OR ALTER PROCEDURE dbo.process_claim
    @claim_id_param INT,
    @adjuster_id_param INT
AS
BEGIN
    DECLARE @claim_amount DECIMAL(10,2), @coverage_amt DECIMAL(10,2);
    DECLARE @farmer_risk DECIMAL(5,2), @farmer_id_val INT;
    DECLARE @auto_decision VARCHAR(20);
    
    -- Get claim and policy details
    SELECT 
        @claim_amount = c.settlement_amount,
        @coverage_amt = ip.coverage_amount,
        @farmer_id_val = cl.farmer_id
    FROM claims c
    JOIN insurance_policies ip ON c.policy_id = ip.id
    JOIN crops_livestock cl ON ip.item_id = cl.id
    WHERE c.id = @claim_id_param;
    
    -- Calculate risk factor using your stored function
    SET @farmer_risk = dbo.calculate_risk_factor(@farmer_id_val);
    
    -- Auto-decision logic
    IF @claim_amount <= (@coverage_amt * 0.3) AND @farmer_risk < 2.0 
        SET @auto_decision = 'approved';
    ELSE IF @claim_amount > (@coverage_amt * 0.8) OR @farmer_risk > 4.0 
        SET @auto_decision = 'rejected';
    ELSE 
        SET @auto_decision = 'pending';
    
    -- Update claim
    UPDATE claims
    SET status = @auto_decision,
        processed_by = @adjuster_id_param,
        decision_date = GETDATE()
    WHERE id = @claim_id_param;
    
    SELECT 'Claim ' + @auto_decision + ' - Risk Factor: ' + CAST(@farmer_risk AS VARCHAR) AS Result;
END;
GO

-- PROCEDURE 2: Generate monthly revenue report
CREATE OR ALTER PROCEDURE dbo.get_monthly_revenue_report
    @report_month INT,
    @report_year INT
AS
BEGIN
    SELECT 
        FORMAT(pp.payment_date, 'yyyy-MM') AS [month],
        COUNT(DISTINCT pp.policy_id) AS total_policies,
        COUNT(pp.id) AS total_payments,
        SUM(pp.amount) AS total_revenue,
        AVG(pp.amount) AS average_payment
    FROM premium_payments pp
    WHERE MONTH(pp.payment_date) = @report_month
    AND YEAR(pp.payment_date) = @report_year
    GROUP BY FORMAT(pp.payment_date, 'yyyy-MM');
END;
GO

-- PROCEDURE 3: Get farmer comprehensive report
CREATE OR ALTER PROCEDURE dbo.get_farmer_report
    @farmer_id_param INT
AS
BEGIN
    -- Farmer basic info
    SELECT f.id, f.full_name, f.address, f.contact_number, u.email
    FROM farmers f
    JOIN users u ON f.user_id = u.id
    WHERE f.id = @farmer_id_param;
    
    -- Insurance policies summary
    SELECT 
        COUNT(*) AS total_policies,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_policies,
        SUM(coverage_amount) AS total_coverage
    FROM insurance_policies ip
    JOIN crops_livestock cl ON ip.item_id = cl.id
    WHERE cl.farmer_id = @farmer_id_param;
END;
GO

-- PROCEDURE 4: Expire old policies (run daily)
CREATE OR ALTER PROCEDURE dbo.expire_old_policies
AS
BEGIN
    UPDATE insurance_policies
    SET status = 'expired'
    WHERE end_date < GETDATE()
    AND status = 'active';
    
    SELECT CAST(@@ROWCOUNT AS VARCHAR) + ' policies expired' AS Result;
END;
GO

-- PROCEDURE 5: Get top farmers (MSSQL uses TOP instead of LIMIT)
CREATE OR ALTER PROCEDURE dbo.get_top_farmers_by_coverage
    @limit_count INT
AS
BEGIN
    -- Using dynamic SQL for the TOP limit
    DECLARE @SQL NVARCHAR(MAX) = 'SELECT TOP ' + CAST(@limit_count AS NVARCHAR) + ' 
        f.id, f.full_name, SUM(ip.coverage_amount) AS total_coverage
        FROM farmers f
        JOIN crops_livestock cl ON f.id = cl.farmer_id
        JOIN insurance_policies ip ON cl.id = ip.item_id
        WHERE ip.status = ''active''
        GROUP BY f.id, f.full_name
        ORDER BY total_coverage DESC';
    EXEC sp_executesql @SQL;
END;
GO