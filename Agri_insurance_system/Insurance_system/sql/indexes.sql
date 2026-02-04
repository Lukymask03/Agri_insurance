USE agri_insurance_system;
GO

-- USERS & FARMERS INDEXES
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_farmers_full_name ON farmers(full_name);

-- CROPS & POLICIES INDEXES
CREATE INDEX idx_crops_category_type ON crops_livestock(category, type);
CREATE INDEX idx_policies_status ON insurance_policies(status);

-- REQUIREMENT: Indexing Efficiency
CREATE NONCLUSTERED INDEX idx_active_farmer_policies 
ON insurance_policies(item_id, status) 
INCLUDE (coverage_amount);
GO