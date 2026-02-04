USE agri_insurance_system;
GO

-- FUNCTION: Calculate Risk Factor
CREATE OR ALTER FUNCTION dbo.calculate_risk_factor(@farmer_id INT)
RETURNS DECIMAL(5,2)
AS
BEGIN
    DECLARE @total_claims INT, @approved_claims INT, @risk_factor DECIMAL(5,2);
    
    SELECT @total_claims = COUNT(c.id),
           @approved_claims = SUM(CASE WHEN c.status IN ('approved', 'paid') THEN 1 ELSE 0 END)
    FROM claims c
    JOIN insurance_policies ip ON c.policy_id = ip.id
    JOIN crops_livestock cl ON ip.item_id = cl.id
    WHERE cl.farmer_id = @farmer_id;

    SET @risk_factor = CASE WHEN @total_claims = 0 THEN 1.0 ELSE 1.0 + (@approved_claims * 1.0 / @total_claims * 4.0) END;
    RETURN @risk_factor;
END;
GO