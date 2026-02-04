<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('agent'))) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$success = '';
$error = '';

// 1. Handle Create Policy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $item_id = $_POST['item_id'];
    $policy_type = $_POST['policy_type'];
    $premium_rate = $_POST['premium_rate'];
    $coverage_amount = $_POST['coverage_amount'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $sql = "INSERT INTO insurance_policies (item_id, policy_type, premium_rate, coverage_amount, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $params = array($item_id, $policy_type, $premium_rate, $coverage_amount, $start_date, $end_date);
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) {
        $success = "✅ Policy successfully issued!";
    } else {
        $error = "❌ Error: " . print_r(sqlsrv_errors(), true);
    }
}

// 2. Fetch Policies
$policies = [];
$query = "SELECT p.*, f.full_name, cl.type as item_name 
          FROM insurance_policies p
          JOIN crops_livestock cl ON p.item_id = cl.id
          JOIN farmers f ON cl.farmer_id = f.id
          ORDER BY p.id DESC";

$res = sqlsrv_query($conn, $query);
if ($res) {
    while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        $policies[] = $row;
    }
}

// 3. Stats
$total_policies = count($policies);
$active_val = 0;
$total_coverage = 0;
foreach ($policies as $p) {
    if (($p['status'] ?? '') === 'active') $active_val++;
    $total_coverage += ($p['coverage_amount'] ?? 0);
}

// Fetch items for dropdown
$items = [];
$item_query = "SELECT cl.id, cl.type, f.full_name FROM crops_livestock cl JOIN farmers f ON cl.farmer_id = f.id";
$item_res = sqlsrv_query($conn, $item_query);
if ($item_res) {
    while ($row = sqlsrv_fetch_array($item_res, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Policy Management - Agri Insurance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 2rem; max-width: 1200px; margin: auto; }
        .stats-row { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-box { background: white; padding: 20px; border-radius: 12px; flex: 1; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #eef2f6; }
        .stat-box span { font-size: 24px; font-weight: 800; color: #7c3aed; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .table th { text-align: left; padding: 14px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.8rem; text-transform: uppercase; }
        .table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; cursor: pointer; border: none; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #7c3aed; color: white; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-active { background: #dcfce7; color: #166534; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index: 1000; backdrop-filter: blur(4px); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 35px; border-radius: 16px; width: 480px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #475569; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="header">
    <div style="display: flex; align-items: center; gap: 12px;"><span>📄</span><h2 style="margin: 0;">Policy Management</h2></div>
    <a href="dashboard.php" style="color:white; text-decoration:none; border:1px solid white; padding:8px 15px; border-radius:8px;">Dashboard</a>
</div>

<div class="container">
    <?php if ($success): ?> <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:8px; margin-bottom:20px; border-left: 5px solid #22c55e;"><?php echo $success; ?></div> <?php endif; ?>

    <div class="stats-row">
        <div class="stat-box"><span><?php echo $total_policies; ?></span><br><small>Total Policies</small></div>
        <div class="stat-box"><span><?php echo $active_val; ?></span><br><small>Active</small></div>
        <div class="stat-box"><span>₱<?php echo number_format($total_coverage, 2); ?></span><br><small>Value Insured</small></div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
            <h3 style="margin: 0; color: #1e293b;">Active Policies</h3>
            <button class="btn btn-primary" onclick="showCreateModal()">+ New Policy</button>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Policy ID</th>
                    <th>Farmer</th>
                    <th>Item</th>
                    <th>Coverage (₱)</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($policies as $p): ?>
                <tr>
                    <td style="font-family: monospace;">POL-<?php echo $p['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($p['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['item_name']); ?></td>
                    <td>₱<?php echo number_format($p['coverage_amount'], 2); ?></td>
                    <td><span class="badge badge-active"><?php echo $p['status']; ?></span></td>
                    <td><?php echo ($p['end_date'] instanceof DateTime) ? $p['end_date']->format('M d, Y') : date('M d, Y', strtotime($p['end_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="createModal" class="modal">
    <div class="modal-content">
        <h3 style="color:#7c3aed; margin-top:0;">Issue New Policy</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Registered Item (Farmer)</label>
                <select name="item_id" required>
                    <?php foreach($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo $item['type'] . " - " . $item['full_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Policy Name</label><input type="text" name="policy_type" required></div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex:1;"><label>Coverage (₱)</label><input type="number" name="coverage_amount" required></div>
                <div class="form-group" style="flex:1;"><label>Premium (₱)</label><input type="number" name="premium_rate" required></div>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex:1;"><label>Start Date</label><input type="date" name="start_date" required></div>
                <div class="form-group" style="flex:1;"><label>End Date</label><input type="date" name="end_date" required></div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Confirm & Save</button>
            <button type="button" class="btn" style="width:100%; margin-top:10px;" onclick="closeModal('createModal')">Cancel</button>
        </form>
    </div>
</div>

<script>
    function showCreateModal() { document.getElementById('createModal').classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }
</script>
</body>
</html>