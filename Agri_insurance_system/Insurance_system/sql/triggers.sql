USE agri_insurance_system;
GO

-- TRIGGER 1 & 2: Auto-update policy status
CREATE OR ALTER TRIGGER trg_UpdatePolicyStatus
ON insurance_policies
AFTER INSERT, UPDATE
AS
BEGIN
    UPDATE ip
    SET ip.status = CASE WHEN i.end_date < GETDATE() THEN 'expired' ELSE 'active' END
    FROM insurance_policies ip
    JOIN inserted i ON ip.id = i.id;
END;
GO

-- TRIGGER 3: Log claim status changes to Audit Table
CREATE OR ALTER TRIGGER trg_log_claim_change
ON claims
AFTER UPDATE
AS
BEGIN
    IF UPDATE(status)
    BEGIN
        INSERT INTO claim_audit_log (claim_id, old_status, new_status, changed_by)
        SELECT i.id, d.status, i.status, i.processed_by
        FROM inserted i 
        JOIN deleted d ON i.id = d.id;
    END
END;
GO

-- TRIGGER 4: Prevent deletion of policies with active claims
CREATE OR ALTER TRIGGER prevent_policy_deletion_with_claims
ON insurance_policies
INSTEAD OF DELETE
AS
BEGIN
    IF EXISTS (
        SELECT 1 FROM claims c 
        JOIN deleted d ON c.policy_id = d.id 
        WHERE c.status IN ('pending', 'approved')
    )
    BEGIN
        RAISERROR('Cannot delete policy with active or pending claims', 16, 1);
    END
    ELSE
    BEGIN
        DELETE FROM insurance_policies WHERE id IN (SELECT id FROM deleted);
    END
END;
GO