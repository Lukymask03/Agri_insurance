USE agri_insurance_system;
GO

-- SUBQUERY: Above-average total coverage
SELECT f.full_name, farmer_coverage.total_coverage
FROM farmers f
JOIN (
    SELECT cl.farmer_id, SUM(ip.coverage_amount) AS total_coverage
    FROM crops_livestock cl
    JOIN insurance_policies ip ON cl.id = ip.item_id
    WHERE ip.status = 'active'
    GROUP BY cl.farmer_id
) AS farmer_coverage ON f.id = farmer_coverage.farmer_id
WHERE total_coverage > (
    SELECT AVG(total_cov) FROM (
        SELECT SUM(coverage_amount) as total_cov 
        FROM insurance_policies WHERE status = 'active' GROUP BY item_id
    ) AS avg_data
);
GO

-- SUBQUERY: Expiring soon (Within 30 days)
SELECT ip.id, f.full_name, DATEDIFF(day, GETDATE(), ip.end_date) AS days_until_expiry
FROM insurance_policies ip
JOIN crops_livestock cl ON ip.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
WHERE ip.status = 'active'
AND ip.end_date BETWEEN GETDATE() AND DATEADD(day, 30, GETDATE());
GO