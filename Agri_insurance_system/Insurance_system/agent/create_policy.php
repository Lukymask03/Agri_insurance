<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; 

if (!isLoggedIn() || !hasRole('agent')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$username = $_SESSION['username'] ?? 'agent';
$full_name = $_SESSION['full_name'] ?? 'Insurance Agent';

$success = '';
$error = '';

// Define Policy Types with Rates
$policy_types = [
    'crop_basic' => ['name' => 'Crop Insurance - Basic', 'rate' => 5.0],
    'crop_premium' => ['name' => 'Crop Insurance - Premium', 'rate' => 7.5],
    'livestock_basic' => ['name' => 'Livestock Insurance - Basic', 'rate' => 6.0],
    'livestock_premium' => ['name' => 'Livestock Insurance - Premium', 'rate' => 8.5]
];

// 1. Fetch ALL Farmers for the dropdown
$farmers = [];
$tsql_farmers = "SELECT id, full_name, address FROM farmers ORDER BY full_name ASC";
$stmt_farmers = sqlsrv_query($conn, $tsql_farmers);
if ($stmt_farmers) {
    while ($row = sqlsrv_fetch_array($stmt_farmers, SQLSRV_FETCH_ASSOC)) {
        $farmers[] = $row;
    }
}

// 2. Handle Policy Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_policy'])) {
    $selected_farmer_id = $_POST['farmer_id'];
    $item_id = $_POST['item_id'];
    $policy_type_key = $_POST['policy_type'];
    $coverage_amount = $_POST['coverage_amount'];
    $start_date = $_POST['start_date'];
    $duration_months = (int)$_POST['policy_duration'];
    $premium_amount = $_POST['premium_amount'];
    
    $premium_rate = $policy_types[$policy_type_key]['rate'] ?? 5.0;

    if (!empty($item_id) && !empty($selected_farmer_id)) {
        // We use 'active' and ensure column names match your SSMS schema
        $tsql_insert = "INSERT INTO insurance_policies 
                        (item_id, farmer_id, policy_type, premium_rate, coverage_amount, premium_amount, start_date, end_date, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, DATEADD(month, ?, ?), 'active')";
        
        $params = array($item_id, $selected_farmer_id, $policy_type_key, $premium_rate, $coverage_amount, $premium_amount, $start_date, $duration_months, $start_date);
        $stmt_insert = sqlsrv_query($conn, $tsql_insert, $params);
        
        if ($stmt_insert) {
            $success = "✅ Policy created successfully!";
        } else {
            $error = "Database Error: " . print_r(sqlsrv_errors(), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Insurance Policy - Agent Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .navbar { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 2rem; }
        .form-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-section { margin-bottom: 2rem; }
        .form-section h2 { color: #1e3a8a; border-bottom: 2px solid #e5e7eb; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group select, .form-group input { width: 100%; padding: 0.8rem; border: 2px solid #ddd; border-radius: 5px; }
        .calculation-box { background: #e0e7ff; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .calc-item { display: flex; justify-content: space-between; padding: 0.3rem 0; }
        .calc-item.total { border-top: 2px solid #3b82f6; margin-top: 0.5rem; padding-top: 1rem; font-weight: bold; font-size: 1.2rem; }
        .btn { padding: 1rem 2rem; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: bold; border: none; }
        .btn-primary { background: #1e3a8a; color: white; width: 100%; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .required { color: red; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">👔 Agent Portal</div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($full_name); ?></span>
        </div>
    </nav>

    <div class="container">
        <a href="dashboard.php" style="text-decoration:none; color:#1e3a8a;">← Back to Dashboard</a>

        <div class="page-header" style="margin-top:1rem;">
            <h1>📋 Create Insurance Policy</h1>
            <p>Select a farmer to load their registered items instantly.</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <div class="form-container">
            <form method="POST" action="" id="policyForm">
                
                <div class="form-section">
                    <h2>1. Select Farmer</h2>
                    <div class="form-group">
                        <label>Choose Farmer <span class="required">*</span></label>
                        <select name="farmer_id" id="farmerSelect" required>
                            <option value="">-- Select a farmer --</option>
                            <?php foreach($farmers as $farmer): ?>
                                <option value="<?php echo $farmer['id']; ?>">
                                    <?php echo htmlspecialchars($farmer['id'] . ' - ' . $farmer['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h2>2. Select Item to Insure</h2>
                    <div class="form-group">
                        <label>Crop/Livestock <span class="required">*</span></label>
                        <select name="item_id" id="itemSelect" required disabled>
                            <option value="">-- Select Farmer First --</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h2>3. Policy Details</h2>
                    <div class="form-group">
                        <label>Policy Type <span class="required">*</span></label>
                        <select name="policy_type" id="policyType" required>
                            <option value="">-- Select policy type --</option>
                            <?php foreach($policy_types as $key => $type): ?>
                                <option value="<?php echo $key; ?>" data-rate="<?php echo $type['rate']; ?>">
                                    <?php echo htmlspecialchars($type['name'] . ' (' . $type['rate'] . '% rate)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex; gap:1rem;">
                        <div class="form-group" style="flex:1;">
                            <label>Coverage Amount (₱) <span class="required">*</span></label>
                            <input type="number" name="coverage_amount" id="coverageAmount" required step="0.01">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Duration <span class="required">*</span></label>
                            <select name="policy_duration" id="policyDuration" required>
                                <option value="6">6 Months</option>
                                <option value="12" selected>12 Months</option>
                                <option value="24">24 Months</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Start Date <span class="required">*</span></label>
                        <input type="date" name="start_date" id="startDate" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-section">
                    <div class="calculation-box">
                        <h3>💰 Premium Summary</h3>
                        <div class="calc-item"><span>Coverage:</span> <span id="displayCoverage">₱0.00</span></div>
                        <div class="calc-item"><span>Rate:</span> <span id="displayRate">0%</span></div>
                        <div class="calc-item total"><span>Total Premium:</span> <span id="displayPremium">₱0.00</span></div>
                        <input type="hidden" name="premium_amount" id="premiumAmount" value="0">
                    </div>
                </div>

                <button type="submit" name="submit_policy" class="btn btn-primary">Create Policy</button>
            </form>
        </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem;">
                <a href="create_policy.php" class="btn btn-primary">Create Another Policy</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const farmerSelect = document.getElementById('farmerSelect');
            const itemSelect = document.getElementById('itemSelect');
            const coverageInput = document.getElementById('coverageAmount');
            const policyTypeSelect = document.getElementById('policyType');
            const durationSelect = document.getElementById('policyDuration');
            
            const displayCoverage = document.getElementById('displayCoverage');
            const displayRate = document.getElementById('displayRate');
            const displayPremium = document.getElementById('displayPremium');
            const hiddenPremium = document.getElementById('premiumAmount');

            // --- STEP 2 INTEGRATION: AJAX FETCH ---
            farmerSelect.addEventListener('change', function() {
                const farmerId = this.value;
                itemSelect.disabled = true;
                itemSelect.innerHTML = '<option value="">-- Loading items... --</option>';

                if (!farmerId) {
                    itemSelect.innerHTML = '<option value="">-- Select Farmer First --</option>';
                    return;
                }

                // Fetch items from the script we created earlier
                fetch(`get_farmer_items.php?farmer_id=${farmerId}`)
                    .then(response => response.json())
                    .then(data => {
                        itemSelect.innerHTML = '<option value="">-- Select item --</option>';
                        if (data.length === 0) {
                            itemSelect.innerHTML = '<option value="">No registered items found</option>';
                        } else {
                            data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.id;
                                option.setAttribute('data-value', item.estimated_value || 0);
                                option.textContent = `${item.category.toUpperCase()}: ${item.type} (Qty: ${item.quantity})`;
                                itemSelect.appendChild(option);
                            });
                            itemSelect.disabled = false;
                        }
                    })
                    .catch(err => {
                        itemSelect.innerHTML = '<option value="">Error loading items</option>';
                        console.error(err);
                    });
            });

            function calculate() {
                const coverage = parseFloat(coverageInput.value) || 0;
                const selectedPolicy = policyTypeSelect.options[policyTypeSelect.selectedIndex];
                const rate = selectedPolicy ? parseFloat(selectedPolicy.getAttribute('data-rate')) : 0;
                const total = (coverage * (rate / 100));

                displayCoverage.innerText = '₱' + coverage.toLocaleString();
                displayRate.innerText = rate + '%';
                displayPremium.innerText = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
                hiddenPremium.value = total.toFixed(2);
            }

            itemSelect.addEventListener('change', function() {
                const selectedItem = this.options[this.selectedIndex];
                const val = selectedItem.getAttribute('data-value');
                if(val && val > 0) {
                    coverageInput.value = val;
                    calculate();
                }
            });

            [coverageInput, policyTypeSelect, durationSelect].forEach(el => {
                el.addEventListener('input', calculate);
                el.addEventListener('change', calculate);
            });
        });
    </script>
</body>
</html>