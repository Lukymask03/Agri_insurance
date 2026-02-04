USE [agri_insurance_system]
GO
/****** Object:  User [NT AUTHORITY\SYSTEM]    Script Date: 2/4/2026 11:47:51 PM ******/
CREATE USER [NT AUTHORITY\SYSTEM] FOR LOGIN [NT AUTHORITY\SYSTEM] WITH DEFAULT_SCHEMA=[dbo]
GO
ALTER ROLE [db_owner] ADD MEMBER [NT AUTHORITY\SYSTEM]
GO
/****** Object:  UserDefinedFunction [dbo].[calculate_risk_factor]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- Function to calculate Risk Factor (mirrored from your logic)
CREATE   FUNCTION [dbo].[calculate_risk_factor](@farmer_id INT)
RETURNS DECIMAL(5,2)
AS
BEGIN
    DECLARE @total_claims INT, @approved_claims INT, @risk_factor DECIMAL(5,2);
    SELECT @total_claims = COUNT(id) FROM claims WHERE policy_id IN 
        (SELECT id FROM insurance_policies WHERE item_id IN 
            (SELECT id FROM crops_livestock WHERE farmer_id = @farmer_id));
    
    SET @risk_factor = CASE WHEN @total_claims = 0 THEN 1.0 ELSE 1.5 END; -- Simplified logic for MSSQL
    RETURN @risk_factor;
END;
GO
/****** Object:  Table [dbo].[farmers]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[farmers](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[user_id] [int] NOT NULL,
	[full_name] [varchar](100) NOT NULL,
	[address] [nvarchar](max) NOT NULL,
	[contact_number] [varchar](20) NOT NULL,
	[date_joined] [datetime] NULL,
	[phone_number] [varchar](15) NULL,
	[email] [varchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[crops_livestock]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[crops_livestock](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[farmer_id] [int] NOT NULL,
	[category] [varchar](10) NOT NULL,
	[type] [varchar](100) NOT NULL,
	[quantity] [int] NOT NULL,
	[location] [nvarchar](max) NOT NULL,
	[registered_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  Table [dbo].[insurance_policies]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[insurance_policies](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[item_id] [int] NOT NULL,
	[policy_type] [varchar](100) NOT NULL,
	[premium_rate] [decimal](10, 2) NOT NULL,
	[coverage_amount] [decimal](10, 2) NOT NULL,
	[start_date] [date] NOT NULL,
	[end_date] [date] NOT NULL,
	[status] [varchar](10) NULL,
	[farmer_id] [int] NULL,
	[premium_amount] [decimal](18, 2) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[premium_payments]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[premium_payments](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[policy_id] [int] NOT NULL,
	[payment_date] [datetime] NULL,
	[amount] [decimal](10, 2) NOT NULL,
	[payment_method] [varchar](10) NULL,
	[received_by] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[claims]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[claims](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[policy_id] [int] NOT NULL,
	[claim_type] [varchar](100) NOT NULL,
	[claim_description] [nvarchar](max) NOT NULL,
	[claim_date] [datetime] NULL,
	[status] [varchar](10) NULL,
	[evidence_file] [varchar](255) NULL,
	[processed_by] [int] NULL,
	[decision_date] [datetime] NULL,
	[settlement_amount] [decimal](10, 2) NULL,
	[damage_type] [nvarchar](255) NULL,
	[estimated_loss] [decimal](18, 2) NULL,
	[description] [nvarchar](max) NULL,
	[approved_payout] [decimal](18, 2) NULL,
	[processed_date] [datetime] NULL,
	[disbursement_date] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Object:  View [dbo].[view_dashboard_stats]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   VIEW [dbo].[view_dashboard_stats] AS
SELECT 
    (SELECT COUNT(*) FROM farmers) AS total_farmers,
    (SELECT COUNT(*) FROM crops_livestock) AS total_items,
    (SELECT COUNT(*) FROM insurance_policies WHERE status = 'active') AS active_policies,
    (SELECT ISNULL(SUM(coverage_amount), 0) FROM insurance_policies WHERE status = 'active') AS total_coverage,
    (SELECT COUNT(*) FROM claims WHERE status = 'pending') AS pending_claims,
    (SELECT ISNULL(SUM(amount), 0) FROM premium_payments) AS total_revenue;
GO
/****** Object:  View [dbo].[view_active_policies]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- View for Active Policies
CREATE VIEW [dbo].[view_active_policies] AS
SELECT p.id AS policy_id, p.policy_type, p.premium_rate, p.coverage_amount, p.start_date, p.end_date,
       f.id AS farmer_id, f.full_name AS farmer_name, cl.type AS item_type, cl.category,
       DATEDIFF(day, GETDATE(), p.end_date) AS days_until_expiry
FROM insurance_policies p
JOIN crops_livestock cl ON p.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id
WHERE p.status = 'active';
GO
/****** Object:  View [dbo].[view_claims_summary]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- View for Claims Summary
CREATE VIEW [dbo].[view_claims_summary] AS
SELECT c.id AS claim_id, c.claim_type, c.claim_description, c.date_filed, c.status, c.settlement_amount,
       f.id AS farmer_id, f.full_name AS farmer_name, p.policy_type, cl.type AS item_type,
       DATEDIFF(day, c.date_filed, ISNULL(c.decision_date, GETDATE())) AS processing_days
FROM claims c
JOIN insurance_policies p ON c.policy_id = p.id
JOIN crops_livestock cl ON p.item_id = cl.id
JOIN farmers f ON cl.farmer_id = f.id;
GO
/****** Object:  View [dbo].[vw_ActivePolicySummary]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- 4. ADVANCED FEATURE: VIEW (Requirement #3)
-- This is perfect for your Dashboard visualization
CREATE   VIEW [dbo].[vw_ActivePolicySummary] AS
SELECT 
    f.full_name, 
    cl.type AS Asset, 
    p.coverage_amount, 
    p.status AS PolicyStatus
FROM farmers f
JOIN crops_livestock cl ON f.id = cl.farmer_id
JOIN insurance_policies p ON cl.id = p.item_id;
GO
/****** Object:  Table [dbo].[claim_audit_log]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[claim_audit_log](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[claim_id] [int] NOT NULL,
	[old_status] [varchar](20) NULL,
	[new_status] [varchar](20) NULL,
	[changed_by] [int] NULL,
	[changed_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[payments]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[payments](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[claim_id] [int] NOT NULL,
	[amount] [decimal](15, 2) NOT NULL,
	[payment_method] [varchar](50) NULL,
	[payment_date] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[policies]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[policies](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[farmer_id] [int] NOT NULL,
	[policy_type] [nvarchar](50) NULL,
	[coverage_amount] [decimal](18, 2) NULL,
	[status] [nvarchar](20) NULL,
	[item_id] [int] NULL,
	[premium_rate] [decimal](10, 2) NULL,
	[start_date] [date] NULL,
	[end_date] [date] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Object:  Table [dbo].[users]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[users](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[username] [varchar](50) NOT NULL,
	[password] [varchar](255) NOT NULL,
	[role] [varchar](20) NOT NULL,
	[email] [nvarchar](255) NULL,
	[phone] [nvarchar](20) NULL,
	[status] [varchar](10) NULL,
	[created_at] [datetime] NULL,
	[full_name] [nvarchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[username] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
ALTER TABLE [dbo].[claim_audit_log] ADD  DEFAULT (getdate()) FOR [changed_at]
GO
ALTER TABLE [dbo].[claims] ADD  DEFAULT (getdate()) FOR [claim_date]
GO
ALTER TABLE [dbo].[claims] ADD  DEFAULT ('pending') FOR [status]
GO
ALTER TABLE [dbo].[claims] ADD  DEFAULT ((0.00)) FOR [approved_payout]
GO
ALTER TABLE [dbo].[crops_livestock] ADD  DEFAULT (getdate()) FOR [registered_at]
GO
ALTER TABLE [dbo].[insurance_policies] ADD  DEFAULT ('active') FOR [status]
GO
ALTER TABLE [dbo].[payments] ADD  DEFAULT ('Bank Transfer') FOR [payment_method]
GO
ALTER TABLE [dbo].[payments] ADD  DEFAULT (getdate()) FOR [payment_date]
GO
ALTER TABLE [dbo].[policies] ADD  DEFAULT ('active') FOR [status]
GO
ALTER TABLE [dbo].[premium_payments] ADD  DEFAULT (getdate()) FOR [payment_date]
GO
ALTER TABLE [dbo].[premium_payments] ADD  DEFAULT ('cash') FOR [payment_method]
GO
ALTER TABLE [dbo].[users] ADD  DEFAULT ('active') FOR [status]
GO
ALTER TABLE [dbo].[users] ADD  DEFAULT (getdate()) FOR [created_at]
GO
ALTER TABLE [dbo].[claim_audit_log]  WITH NOCHECK ADD  CONSTRAINT [FK_Audit_Claims] FOREIGN KEY([claim_id])
REFERENCES [dbo].[claims] ([id])
GO
ALTER TABLE [dbo].[claim_audit_log] CHECK CONSTRAINT [FK_Audit_Claims]
GO
ALTER TABLE [dbo].[claims]  WITH NOCHECK ADD  CONSTRAINT [FK_Claims_Policies] FOREIGN KEY([policy_id])
REFERENCES [dbo].[insurance_policies] ([id])
GO
ALTER TABLE [dbo].[claims] CHECK CONSTRAINT [FK_Claims_Policies]
GO
ALTER TABLE [dbo].[claims]  WITH NOCHECK ADD  CONSTRAINT [FK_Claims_Users] FOREIGN KEY([processed_by])
REFERENCES [dbo].[users] ([id])
GO
ALTER TABLE [dbo].[claims] CHECK CONSTRAINT [FK_Claims_Users]
GO
ALTER TABLE [dbo].[crops_livestock]  WITH NOCHECK ADD  CONSTRAINT [FK_Items_Farmers] FOREIGN KEY([farmer_id])
REFERENCES [dbo].[farmers] ([id])
GO
ALTER TABLE [dbo].[crops_livestock] CHECK CONSTRAINT [FK_Items_Farmers]
GO
ALTER TABLE [dbo].[farmers]  WITH NOCHECK ADD  CONSTRAINT [FK_Farmers_Users] FOREIGN KEY([user_id])
REFERENCES [dbo].[users] ([id])
GO
ALTER TABLE [dbo].[farmers] CHECK CONSTRAINT [FK_Farmers_Users]
GO
ALTER TABLE [dbo].[insurance_policies]  WITH CHECK ADD  CONSTRAINT [FK_Policies_Farmers] FOREIGN KEY([farmer_id])
REFERENCES [dbo].[farmers] ([id])
GO
ALTER TABLE [dbo].[insurance_policies] CHECK CONSTRAINT [FK_Policies_Farmers]
GO
ALTER TABLE [dbo].[insurance_policies]  WITH NOCHECK ADD  CONSTRAINT [FK_Policies_Items] FOREIGN KEY([item_id])
REFERENCES [dbo].[crops_livestock] ([id])
GO
ALTER TABLE [dbo].[insurance_policies] CHECK CONSTRAINT [FK_Policies_Items]
GO
ALTER TABLE [dbo].[payments]  WITH NOCHECK ADD  CONSTRAINT [FK_Payments_Claims] FOREIGN KEY([claim_id])
REFERENCES [dbo].[claims] ([id])
GO
ALTER TABLE [dbo].[payments] CHECK CONSTRAINT [FK_Payments_Claims]
GO
ALTER TABLE [dbo].[policies]  WITH NOCHECK ADD FOREIGN KEY([farmer_id])
REFERENCES [dbo].[farmers] ([id])
GO
ALTER TABLE [dbo].[premium_payments]  WITH NOCHECK ADD  CONSTRAINT [FK_Premiums_Policies] FOREIGN KEY([policy_id])
REFERENCES [dbo].[insurance_policies] ([id])
GO
ALTER TABLE [dbo].[premium_payments] CHECK CONSTRAINT [FK_Premiums_Policies]
GO
ALTER TABLE [dbo].[premium_payments]  WITH NOCHECK ADD  CONSTRAINT [FK_Premiums_Users] FOREIGN KEY([received_by])
REFERENCES [dbo].[users] ([id])
GO
ALTER TABLE [dbo].[premium_payments] CHECK CONSTRAINT [FK_Premiums_Users]
GO
ALTER TABLE [dbo].[claims]  WITH NOCHECK ADD CHECK  (([status]='paid' OR [status]='rejected' OR [status]='approved' OR [status]='pending'))
GO
ALTER TABLE [dbo].[crops_livestock]  WITH NOCHECK ADD CHECK  (([category]='livestock' OR [category]='crop'))
GO
ALTER TABLE [dbo].[insurance_policies]  WITH NOCHECK ADD  CONSTRAINT [CK_policy_status] CHECK  (([status]='expired' OR [status]='pending' OR [status]='active'))
GO
ALTER TABLE [dbo].[insurance_policies] CHECK CONSTRAINT [CK_policy_status]
GO
ALTER TABLE [dbo].[premium_payments]  WITH NOCHECK ADD CHECK  (([payment_method]='cash'))
GO
ALTER TABLE [dbo].[users]  WITH NOCHECK ADD CHECK  (([role]='adjuster' OR [role]='agent' OR [role]='farmer' OR [role]='admin'))
GO
ALTER TABLE [dbo].[users]  WITH NOCHECK ADD CHECK  (([status]='inactive' OR [status]='active'))
GO
/****** Object:  StoredProcedure [dbo].[expire_old_policies]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- PROCEDURE 4: Expire old policies (run daily)
CREATE   PROCEDURE [dbo].[expire_old_policies]
AS
BEGIN
    UPDATE insurance_policies
    SET status = 'expired'
    WHERE end_date < GETDATE()
    AND status = 'active';
    
    SELECT CAST(@@ROWCOUNT AS VARCHAR) + ' policies expired' AS Result;
END;
GO
/****** Object:  StoredProcedure [dbo].[get_farmer_report]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- PROCEDURE 3: Get farmer comprehensive report
CREATE   PROCEDURE [dbo].[get_farmer_report]
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
/****** Object:  StoredProcedure [dbo].[get_monthly_revenue_report]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- PROCEDURE 2: Generate monthly revenue report
CREATE   PROCEDURE [dbo].[get_monthly_revenue_report]
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
/****** Object:  StoredProcedure [dbo].[get_top_farmers_by_coverage]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- PROCEDURE 5: Get top farmers (MSSQL uses TOP instead of LIMIT)
CREATE   PROCEDURE [dbo].[get_top_farmers_by_coverage]
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
/****** Object:  StoredProcedure [dbo].[process_claim]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- PROCEDURE 1: Process claim and auto-approve based on criteria
CREATE   PROCEDURE [dbo].[process_claim]
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
/****** Object:  StoredProcedure [dbo].[sp_GenerateBulkData]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- 2. ADVANCED FEATURE: STORED PROCEDURE (Requirement #3)
-- This procedure handles bulk insertion for your 2,000 records
CREATE   PROCEDURE [dbo].[sp_GenerateBulkData] 
    @TargetCount INT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @i INT = 1;
    WHILE @i <= @TargetCount
    BEGIN
        INSERT INTO users (username, password, role, email, status)
        VALUES ('user_'+CAST(@i AS VARCHAR), 'pass123', 'farmer', 'farm'+CAST(@i AS VARCHAR)+'@agri.com', 'active');
        
        DECLARE @u_id INT = SCOPE_IDENTITY();
        
        INSERT INTO farmers (user_id, full_name, address, contact_number)
        VALUES (@u_id, 'Farmer_'+CAST(@i AS VARCHAR), 'Location_'+CAST(@i AS VARCHAR), '09'+CAST(1000000+@i AS VARCHAR));
        
        DECLARE @f_id INT = SCOPE_IDENTITY();
        
        INSERT INTO crops_livestock (farmer_id, category, type, quantity, location)
        VALUES (@f_id, 'crop', 'Rice', 100, 'Field_'+CAST(@i AS VARCHAR));
        
        DECLARE @item_id INT = SCOPE_IDENTITY();
        
        INSERT INTO insurance_policies (item_id, policy_type, premium_rate, coverage_amount, start_date, end_date, status)
        VALUES (@item_id, 'Rice Insurance', 1500.00, 50000.00, GETDATE(), DATEADD(year, 1, GETDATE()), 'active');
        
        SET @i = @i + 1;
    END
END;
GO
/****** Object:  StoredProcedure [dbo].[sp_GenerateRealisticData]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- =============================================
-- 3. CREATE THE MASTER DATA GENERATOR
-- =============================================
CREATE   PROCEDURE [dbo].[sp_GenerateRealisticData] 
    @TargetCount INT
AS
BEGIN
    SET NOCOUNT ON;

    -- A. INSERT SYSTEM TEST CREDENTIALS
    INSERT INTO users (username, password, role, email, status) VALUES 
    ('admin_user', 'password', 'admin', 'admin@agri.com', 'active'),
    ('test_agent', 'password', 'agent', 'agent@agri.com', 'active'),
    ('test_adjuster', 'password', 'adjuster', 'adjuster@agri.com', 'active');

    -- B. INTEGRATE MANALO ACCOUNT (Linked correctly)
    DECLARE @m_uid INT;
    INSERT INTO users (username, password, role, email, status)
    VALUES ('Manalo', 'password', 'farmer', 'manalo@agri.com', 'active');
    SET @m_uid = SCOPE_IDENTITY(); -- Captures ID #4
    
    INSERT INTO farmers (user_id, full_name, address, contact_number, date_joined)
    VALUES (@m_uid, 'Ramses Manalo', 'Poblacion, Nueva Ecija', '09123456789', GETDATE());

    -- C. GENERATE 2,000 FARMERS
    DECLARE @i INT = 1;
    DECLARE @status VARCHAR(10);
    DECLARE @random_days INT;
    DECLARE @unique_phone VARCHAR(11);
    DECLARE @unique_join_date DATETIME;

    WHILE @i <= @TargetCount
    BEGIN
        SET @unique_phone = '09' + RIGHT('000000000' + CAST(@i AS VARCHAR), 9);
        SET @unique_join_date = DATEADD(MINUTE, -@i, GETDATE());

        -- Insert User and immediately capture ID to prevent NULL error in farmers table
        INSERT INTO users (username, password, role, email, status)
        VALUES ('user_'+CAST(@i AS VARCHAR), 'password', 'farmer', 'farm'+CAST(@i AS VARCHAR)+'@agri.com', 'active');
        
        DECLARE @current_u_id INT = SCOPE_IDENTITY(); -- THE FIX: Local scope ID capture
        
        INSERT INTO farmers (user_id, full_name, address, contact_number, date_joined)
        VALUES (@current_u_id, 'Farmer Name '+CAST(@i AS VARCHAR), 'Barangay '+CAST(@i AS VARCHAR), @unique_phone, @unique_join_date);
        
        DECLARE @f_id INT = SCOPE_IDENTITY();
        
        INSERT INTO crops_livestock (farmer_id, category, type, quantity, location)
        VALUES (@f_id, (CASE WHEN @i % 2 = 0 THEN 'crop' ELSE 'livestock' END), 
                (CASE WHEN @i % 2 = 0 THEN 'Rice' ELSE 'Cattle' END), 100, 'Sector '+CAST(@i AS VARCHAR));
        
        DECLARE @item_id INT = SCOPE_IDENTITY();
        
        SET @random_days = ABS(CHECKSUM(NEWID()) % 365);
        IF @i <= 250 SET @status = 'pending';
        ELSE SET @status = (CASE WHEN @random_days < 30 THEN 'expired' ELSE 'active' END);

        INSERT INTO insurance_policies (item_id, policy_type, premium_rate, coverage_amount, start_date, end_date, status)
        VALUES (@item_id, 'Agri-Guard Plus', 1500.00, 50000.00, 
                DATEADD(day, -@random_days, GETDATE()), 
                DATEADD(day, (365 - @random_days), GETDATE()), @status);

        SET @i = @i + 1;
    END
END;
GO
/****** Object:  StoredProcedure [dbo].[sp_ProcessClaim]    Script Date: 2/4/2026 11:47:51 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- Procedure to process claims through your API/UI
CREATE   PROCEDURE [dbo].[sp_ProcessClaim]
    @ClaimID INT,
    @NewStatus VARCHAR(20),
    @Amount DECIMAL(18,2),
    @ProcessedBy INT
AS
BEGIN
    UPDATE claims
    SET status = @NewStatus, settlement_amount = @Amount, 
        processed_by = @ProcessedBy, decision_date = GETDATE()
    WHERE id = @ClaimID;
END;
GO
