<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('farmer')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Farmer'; // Added to fix the display error

// 1. Get the Farmer ID first
$farmer_query = "SELECT id FROM farmers WHERE user_id = ?";
$farmer_stmt = sqlsrv_query($conn, $farmer_query, array($user_id));
$farmer_row = sqlsrv_fetch_array($farmer_stmt, SQLSRV_FETCH_ASSOC);
$farmer_id = $farmer_row['id'] ?? 0;

// 2. Fetch Items using the confirmed 'farmer_id' column
// We renamed the array to $items to match your JavaScript code below
$items = [];
$tsql = "SELECT * FROM crops_livestock WHERE farmer_id = ?";
$stmt = sqlsrv_query($conn, $tsql, array($farmer_id));

if ($stmt === false) {
    // This helps debug if the query fails due to database connection or schema issues
    die(print_r(sqlsrv_errors(), true)); 
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Ensure 'insured' key exists for the JS filter, even if NULL in DB
    $row['insured'] = isset($row['insured']) ? (bool)$row['insured'] : false;
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Crops & Livestock - Agricultural Insurance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar h1 { font-size: 1.5em; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; transition: background 0.3s; }
        .nav-links a:hover { background: rgba(255,255,255,0.2); }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .page-header { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { color: #333; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: transform 0.3s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; border-top: 4px solid #667eea; }
        .stat-box .number { font-size: 2.5em; font-weight: bold; color: #667eea; margin-bottom: 10px; }
        .stat-box .label { color: #666; font-size: 1em; }
        .filters { background: white; padding: 20px 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-group label { font-weight: 600; color: #666; }
        .filter-group select, .filter-group input { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1em; }
        .items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .item-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; transition: transform 0.3s; }
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .item-header { padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .item-body { padding: 20px; }
        .item-detail { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; }
        .badge-insured { background: #d4edda; color: #155724; }
        .badge-uninsured { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 60px; color: #999; grid-column: 1/-1; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🌾 Agricultural Insurance System</h1>
        <div class="nav-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <span>👤 <?php echo htmlspecialchars($username); ?></span>
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <div>
                <h2>🌾 My Crops & Livestock</h2>
                <p>Manage your farm inventory and insurance coverage</p>
            </div>
            <button class="btn-primary" onclick="window.location.href='register_crop.php'">+ Add New Item</button>
        </div>

        <div class="stats-row">
            <div class="stat-box">
                <div class="number" id="totalCrops">0</div>
                <div class="label">Total Crops</div>
            </div>
            <div class="stat-box">
                <div class="number" id="totalLivestock">0</div>
                <div class="label">Total Livestock</div>
            </div>
            <div class="stat-box">
                <div class="number" id="insuredItems">0</div>
                <div class="label">Insured Items</div>
            </div>
            <div class="stat-box">
                <div class="number" id="totalQuantity">0</div>
                <div class="label">Total Quantity</div>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label>Category:</label>
                <select id="filterCategory" onchange="filterItems()">
                    <option value="all">All</option>
                    <option value="crop">Crops</option>
                    <option value="livestock">Livestock</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" id="searchInput" placeholder="Search by type..." oninput="filterItems()">
            </div>
        </div>

        <div id="itemsContainer" class="items-grid">
            <div class="loading">Loading your items...</div>
        </div>
    </div>

    <script>
        // Use the $items variable we defined in PHP above
        const allItems = <?php echo json_encode($items); ?>;
        const farmerId = <?php echo $farmer_id; ?>;

        function loadItems() {
            updateStats();
            displayItems(allItems);
        }

        function updateStats() {
            const crops = allItems.filter(i => i.category.toLowerCase() === 'crop');
            const livestock = allItems.filter(i => i.category.toLowerCase() === 'livestock');
            const insured = allItems.filter(i => i.insured === true);
            const totalQty = allItems.reduce((sum, i) => sum + parseFloat(i.quantity || 0), 0);

            document.getElementById('totalCrops').textContent = crops.length;
            document.getElementById('totalLivestock').textContent = livestock.length;
            document.getElementById('insuredItems').textContent = insured.length;
            document.getElementById('totalQuantity').textContent = totalQty.toLocaleString();
        }

        function displayItems(items) {
            const container = document.getElementById('itemsContainer');
            if (items.length === 0) {
                container.innerHTML = `<div class="empty-state"><h3>No items found</h3><p>Start by adding your crops or livestock</p></div>`;
                return;
            }

            container.innerHTML = items.map(item => `
                <div class="item-card">
                    <div class="item-header" style="background: ${item.category.toLowerCase() === 'crop' ? 'linear-gradient(135deg, #27ae60, #2ecc71)' : 'linear-gradient(135deg, #e67e22, #f39c12)'}">
                        <h3>${item.category.toLowerCase() === 'crop' ? '🌾' : '🐄'} ${item.type}</h3>
                        <div class="category">${item.category.toUpperCase()}</div>
                    </div>
                    <div class="item-body">
                        <div class="item-detail"><label>Quantity:</label><span>${item.quantity}</span></div>
                        <div class="item-detail"><label>Location:</label><span>${item.location}</span></div>
                        <div class="item-detail">
                            <label>Status:</label>
                            <span class="badge badge-${item.insured ? 'insured' : 'uninsured'}">
                                ${item.insured ? '✓ Insured' : '✗ Not Insured'}
                            </span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function filterItems() {
            const category = document.getElementById('filterCategory').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            let filtered = allItems;
            if (category !== 'all') filtered = filtered.filter(i => i.category.toLowerCase() === category);
            if (search) filtered = filtered.filter(i => i.type.toLowerCase().includes(search));
            displayItems(filtered);
        }

        function logout() { if (confirm('Logout?')) window.location.href = '../views/login.php'; }

        window.addEventListener('DOMContentLoaded', loadItems);
    </script>
</body>
</html>