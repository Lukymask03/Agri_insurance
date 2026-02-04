<?php
session_start();
require_once '../backend/db.php';
$conn = getConn();

// 1. Get Claim ID from URL
$claim_id = isset($_GET['id']) ? $_GET['id'] : 0;

// 2. USE LEFT JOIN and insurance_policies (the table name used in your files)
$tsql = "SELECT c.*, f.full_name, p.policy_type 
         FROM claims c
         LEFT JOIN insurance_policies p ON c.policy_id = p.id
         LEFT JOIN farmers f ON p.farmer_id = f.id
         WHERE c.id = ?";

$stmt = sqlsrv_query($conn, $tsql, array($claim_id));
$claim = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// 3. Check if claim exists
if (!$claim) {
    die("<div style='text-align:center; padding:50px;'><h2>❌ Claim Not Found</h2><p>Claim ID #$claim_id was not found in the system.</p></div>");
}

$status = strtolower($claim['status']);

// 4. FIX THE AMOUNT LOGIC: Use the names from your actual files
$requested = $claim['estimated_loss'] ?? 0; // From your submit_claim.php
$approved  = $claim['approved_payout'] ?? 0; // From your approve_amount.php

if ($status == 'paid' || $status == 'approved') {
    // If Admin has approved a payout, show that. If it's still 0, show what the farmer requested.
    $display_amount = ($approved > 0) ? $approved : $requested;
} else {
    $display_amount = $requested;
}

$title = ($status == 'paid') ? "OFFICIAL RECEIPT" : "CLAIM SUMMARY";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .receipt-card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 600px; border: 1px solid #ddd; }
        .header { text-align: center; border-bottom: 2px solid #673ab7; padding-bottom: 20px; margin-bottom: 20px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 5px; }
        .label { font-weight: bold; color: #555; }
        .amount-box { text-align: center; margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        .amount { font-size: 24px; color: #673ab7; font-weight: bold; }
        .status-paid { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="header">
            <h2><?php echo $title; ?></h2>
            <p>Agricultural Insurance System</p>
        </div>

        <div class="row">
            <span class="label">Claim ID:</span>
            <span>#<?php echo $claim['id']; ?></span>
        </div>
        <div class="row">
            <span class="label">Payee:</span>
            <span><?php echo htmlspecialchars($claim['full_name']); ?></span>
        </div>
        <div class="row">
            <span class="label">Policy Type:</span>
            <span><?php echo htmlspecialchars($claim['policy_type']); ?></span>
        </div>
        <div class="row">
            <span class="label">Status:</span>
            <span class="status-<?php echo $status; ?>"><?php echo strtoupper($claim['status']); ?></span>
        </div>

        <div class="amount-box">
            <div class="label"><?php echo ($status == 'paid') ? "Total Amount Paid" : "Requested Claim Amount"; ?></div>
            <div class="amount">₱<?php echo number_format($display_amount, 2); ?></div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #999;">
            <?php if($status == 'paid'): ?>
                <p>This document serves as proof of disbursement.</p>
            <?php else: ?>
                <p>Note: This claim is still undergoing review. The final amount may change.</p>
            <?php endif; ?>
            <button onclick="window.print()" style="margin-top: 15px; padding: 8px 20px; cursor: pointer;">Print Document</button>
        </div>
    </div>
</body>
</html>