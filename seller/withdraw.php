<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

checkAuth();
if (!isSeller()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Get seller's balance information
try {
    // Total revenue
    $stmt = $pdo->prepare("
        SELECT SUM(oi.subtotal) as total_revenue 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE oi.product_id IN (SELECT id FROM products WHERE seller_id = ?) 
        AND o.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
    // Platform fee (20%)
    $platform_fee = $total_revenue * 0.2;
    
    // Available balance (80% of revenue)
    $available_balance = $total_revenue - $platform_fee;
    
    // Pending withdrawals
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as pending_withdrawals 
        FROM withdrawals 
        WHERE seller_id = ? AND status IN ('pending', 'processing')
    ");
    $stmt->execute([$user_id]);
    $pending_withdrawals = $stmt->fetchColumn() ?: 0;
    
    // Completed withdrawals
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_withdrawn 
        FROM withdrawals 
        WHERE seller_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $total_withdrawn = $stmt->fetchColumn() ?: 0;
    
    // Current balance (available - pending)
    $current_balance = $available_balance - $pending_withdrawals;
    
} catch (PDOException $e) {
    $total_revenue = 0;
    $platform_fee = 0;
    $available_balance = 0;
    $pending_withdrawals = 0;
    $total_withdrawn = 0;
    $current_balance = 0;
}

// Get withdrawal history
try {
    $stmt = $pdo->prepare("
        SELECT * FROM withdrawals 
        WHERE seller_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $withdrawals = $stmt->fetchAll();
} catch (PDOException $e) {
    $withdrawals = [];
}

// Get bank accounts for this seller
try {
    $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC");
    $stmt->execute([$user_id]);
    $bank_accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    $bank_accounts = [];
}

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $amount = str_replace('.', '', $_POST['amount']);
    $amount = floatval($amount);
    $bank_id = $_POST['bank_id'] ?? 0;
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if ($amount < 50000) {
        $error = "Minimum withdrawal amount is Rp 50.000";
    } elseif ($amount > $current_balance) {
        $error = "Insufficient balance. Your available balance is Rp " . number_format($current_balance, 0, ',', '.');
    } elseif ($bank_id == 0) {
        $error = "Please select a bank account";
    } else {
        try {
            // Get bank account details
            $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$bank_id, $user_id]);
            $bank = $stmt->fetch();
            
            if (!$bank) {
                $error = "Bank account not found";
            } else {
                // Insert withdrawal request
                $stmt = $pdo->prepare("
                    INSERT INTO withdrawals (
                        seller_id, amount, bank_name, account_number, 
                        account_holder, notes, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $user_id, 
                    $amount, 
                    $bank['bank_name'], 
                    $bank['account_number'], 
                    $bank['account_holder'],
                    $notes
                ]);
                
                $success = "Withdrawal request submitted successfully! It will be processed within 1-3 business days.";
                
                // Refresh data
                $pending_withdrawals += $amount;
                $current_balance -= $amount;
                
                // Refresh withdrawals list
                $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE seller_id = ? ORDER BY created_at DESC LIMIT 20");
                $stmt->execute([$user_id]);
                $withdrawals = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $error = "Error submitting withdrawal: " . $e->getMessage();
        }
    }
}

// Handle add bank account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bank'])) {
    $bank_name = trim($_POST['bank_name']);
    $account_number = trim($_POST['account_number']);
    $account_holder = trim($_POST['account_holder']);
    
    if (empty($bank_name) || empty($account_number) || empty($account_holder)) {
        $error = "All bank account fields are required";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO bank_accounts (user_id, bank_name, account_number, account_holder, is_default) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            // If this is the first bank account, make it default
            $is_default = empty($bank_accounts) ? 1 : 0;
            
            $stmt->execute([$user_id, $bank_name, $account_number, $account_holder, $is_default]);
            
            $success = "Bank account added successfully!";
            
            // Refresh bank accounts
            $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC");
            $stmt->execute([$user_id]);
            $bank_accounts = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $error = "Error adding bank account: " . $e->getMessage();
        }
    }
}

// Handle set default bank
if (isset($_GET['set_default'])) {
    $bank_id = (int)$_GET['set_default'];
    
    try {
        // Reset all to non-default
        $stmt = $pdo->prepare("UPDATE bank_accounts SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Set selected as default
        $stmt = $pdo->prepare("UPDATE bank_accounts SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$bank_id, $user_id]);
        
        $success = "Default bank account updated!";
        
        // Refresh bank accounts
        $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC");
        $stmt->execute([$user_id]);
        $bank_accounts = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error = "Error setting default bank: " . $e->getMessage();
    }
}

// Handle delete bank account
if (isset($_GET['delete_bank'])) {
    $bank_id = (int)$_GET['delete_bank'];
    
    try {
        // Check if this is the default bank
        $stmt = $pdo->prepare("SELECT is_default FROM bank_accounts WHERE id = ? AND user_id = ?");
        $stmt->execute([$bank_id, $user_id]);
        $bank = $stmt->fetch();
        
        if ($bank && $bank['is_default'] == 1) {
            $error = "Cannot delete default bank account. Please set another account as default first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$bank_id, $user_id]);
            
            $success = "Bank account deleted successfully!";
            
            // Refresh bank accounts
            $stmt = $pdo->prepare("SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC");
            $stmt->execute([$user_id]);
            $bank_accounts = $stmt->fetchAll();
        }
        
    } catch (PDOException $e) {
        $error = "Error deleting bank account: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw Earnings - Kreava Seller</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            color: #1e293b;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            padding: 0.8rem 0;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.6rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.3rem 0;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: #0284c7;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a.active {
            color: #0284c7;
        }

        .nav-links a.active::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem 0.4rem 0.4rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            border-color: #0284c7;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 0.9rem;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0.5rem;
            min-width: 200px;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.6rem 1rem;
            color: #475569;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            color: #0284c7;
        }

        .dropdown-item:last-child {
            color: #ef4444;
        }

        .dropdown-item:last-child:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 0.5rem 0;
        }

        /* Main Content */
        main {
            flex: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .back-link {
            color: #0284c7;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        /* Balance Cards */
        .balance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .balance-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .balance-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.2);
            border-color: #0284c7;
        }

        .balance-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .balance-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .balance-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.3rem;
        }

        .balance-note {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* Withdraw Form */
        .form-section {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: #0284c7;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .form-label i {
            color: #0284c7;
            margin-right: 0.5rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-hint {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        /* Bank Account Cards */
        .bank-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .bank-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.2rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .bank-card:hover {
            border-color: #0284c7;
            box-shadow: 0 5px 15px -5px rgba(2, 132, 199, 0.2);
        }

        .bank-card.default {
            border: 2px solid #0284c7;
            background: #f0f9ff;
        }

        .bank-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .bank-name {
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bank-name i {
            color: #0284c7;
        }

        .default-badge {
            background: #0284c7;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .bank-details {
            margin-bottom: 1rem;
        }

        .bank-number {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }

        .bank-holder {
            color: #64748b;
            font-size: 0.85rem;
        }

        .bank-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .bank-action-btn {
            padding: 0.3rem 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: white;
            color: #475569;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .bank-action-btn:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .bank-action-btn.delete:hover {
            background: #ef4444;
            border-color: #ef4444;
        }

        .add-bank-btn {
            background: white;
            border: 2px dashed #0284c7;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            color: #0284c7;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .add-bank-btn:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        .add-bank-btn i {
            font-size: 2rem;
        }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            border-color: #0284c7;
            color: #0284c7;
        }

        /* Withdrawal History */
        .history-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #e2e8f0;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }

        .amount {
            font-weight: 600;
            color: #0284c7;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }

        .modal-close:hover {
            color: #ef4444;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.8rem;
        }

        .footer-logo i {
            font-size: 1.4rem;
        }

        .footer-about p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .footer-social {
            display: flex;
            gap: 0.5rem;
        }

        .footer-social a {
            width: 32px;
            height: 32px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: #0284c7;
            color: white;
            transform: translateY(-3px);
        }

        .footer-column h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.4rem;
        }

        .footer-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: #0284c7;
            padding-left: 3px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .balance-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }

            .balance-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="../products.php">Products</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="withdraw.php" class="active">Withdraw</a>
            </div>

            <div class="nav-buttons">
                <div class="user-dropdown">
                    <div class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="../assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #94a3b8;"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-store"></i>
                            Dashboard
                        </a>
                        <a href="manage-products.php" class="dropdown-item">
                            <i class="fas fa-cube"></i>
                            My Products
                        </a>
                        <a href="add-product.php" class="dropdown-item">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </a>
                        <a href="sales.php" class="dropdown-item">
                            <i class="fas fa-chart-line"></i>
                            Sales Report
                        </a>
                        <a href="withdraw.php" class="dropdown-item">
                            <i class="fas fa-money-bill-wave"></i>
                            Withdraw
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                        <a href="../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Withdraw Earnings</h1>
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Balance Cards -->
            <div class="balance-grid">
                <div class="balance-card">
                    <div class="balance-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="balance-label">Total Revenue</div>
                    <div class="balance-value">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                    <div class="balance-note">Lifetime earnings</div>
                </div>

                <div class="balance-card">
                    <div class="balance-icon">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="balance-label">Platform Fee (20%)</div>
                    <div class="balance-value">Rp <?php echo number_format($platform_fee, 0, ',', '.'); ?></div>
                    <div class="balance-note">Kreava service fee</div>
                </div>

                <div class="balance-card">
                    <div class="balance-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="balance-label">Available Balance</div>
                    <div class="balance-value">Rp <?php echo number_format($available_balance, 0, ',', '.'); ?></div>
                    <div class="balance-note">Before pending withdrawals</div>
                </div>

                <div class="balance-card">
                    <div class="balance-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="balance-label">Current Balance</div>
                    <div class="balance-value">Rp <?php echo number_format($current_balance, 0, ',', '.'); ?></div>
                    <div class="balance-note">Ready to withdraw</div>
                </div>
            </div>

            <!-- Withdraw Form Section -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Request Withdrawal
                </h2>

                <form method="POST" class="form-grid">
                    <div>
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-dollar-sign"></i>
                                Amount (Rp)
                            </label>
                            <input type="text" name="amount" class="form-input" 
                                   placeholder="Min. 50.000" required
                                   onkeyup="formatRupiah(this)">
                            <div class="input-hint">
                                Minimum withdrawal: Rp 50.000
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-university"></i>
                                Bank Account
                            </label>
                            <?php if (count($bank_accounts) > 0): ?>
                                <select name="bank_id" class="form-select" required>
                                    <option value="">Select Bank Account</option>
                                    <?php foreach ($bank_accounts as $bank): ?>
                                    <option value="<?php echo $bank['id']; ?>" <?php echo $bank['is_default'] ? 'selected' : ''; ?>>
                                        <?php echo $bank['bank_name']; ?> - <?php echo $bank['account_number']; ?> (<?php echo $bank['account_holder']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <p style="color: #ef4444; background: #fee2e2; padding: 1rem; border-radius: 12px;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    You need to add a bank account first.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-sticky-note"></i>
                                Notes (Optional)
                            </label>
                            <textarea name="notes" class="form-textarea" placeholder="Additional notes for your withdrawal request"></textarea>
                        </div>

                        <button type="submit" name="withdraw" class="btn btn-success" style="width: 100%;" 
                                <?php echo (count($bank_accounts) == 0 || $current_balance < 50000) ? 'disabled' : ''; ?>>
                            <i class="fas fa-money-bill-wave"></i>
                            Request Withdrawal
                        </button>
                    </div>

                    <div style="background: #f8fafc; border-radius: 16px; padding: 1.5rem;">
                        <h3 style="margin-bottom: 1rem; color: #0f172a;">Withdrawal Information</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                                <span style="color: #64748b;">Available Balance:</span>
                                <span style="font-weight: 600; color: #0284c7;">Rp <?php echo number_format($current_balance, 0, ',', '.'); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                                <span style="color: #64748b;">Minimum Withdrawal:</span>
                                <span>Rp 50.000</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                                <span style="color: #64748b;">Processing Time:</span>
                                <span>1-3 business days</span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0;">
                                <span style="color: #64748b;">Platform Fee:</span>
                                <span>20% (already deducted)</span>
                            </div>
                            
                            <div style="margin-top: 1rem; color: #64748b; font-size: 0.9rem;">
                                <i class="fas fa-info-circle" style="color: #0284c7;"></i>
                                Withdrawals are processed Monday-Friday. Requests after 2 PM will be processed the next business day.
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Bank Accounts Section -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-university"></i>
                    Bank Accounts
                </h2>

                <div class="bank-grid">
                    <?php foreach ($bank_accounts as $bank): ?>
                    <div class="bank-card <?php echo $bank['is_default'] ? 'default' : ''; ?>">
                        <div class="bank-header">
                            <div class="bank-name">
                                <i class="fas fa-university"></i>
                                <?php echo htmlspecialchars($bank['bank_name']); ?>
                            </div>
                            <?php if ($bank['is_default']): ?>
                            <span class="default-badge">Default</span>
                            <?php endif; ?>
                        </div>
                        <div class="bank-details">
                            <div class="bank-number"><?php echo htmlspecialchars($bank['account_number']); ?></div>
                            <div class="bank-holder"><?php echo htmlspecialchars($bank['account_holder']); ?></div>
                        </div>
                        <div class="bank-actions">
                            <?php if (!$bank['is_default']): ?>
                            <a href="?set_default=<?php echo $bank['id']; ?>" class="bank-action-btn" title="Set as default">
                                <i class="fas fa-check"></i> Set Default
                            </a>
                            <?php endif; ?>
                            <a href="?delete_bank=<?php echo $bank['id']; ?>" class="bank-action-btn delete" 
                               onclick="return confirm('Are you sure you want to delete this bank account?')" title="Delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Add Bank Button -->
                    <div class="add-bank-btn" onclick="openBankModal()">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add New Bank Account</span>
                    </div>
                </div>
            </div>

            <!-- Withdrawal History -->
            <div class="history-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Withdrawal History
                </h2>

                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Bank</th>
                                <th>Account</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($withdrawals) > 0): ?>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?php echo date('d M Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                                    <td class="amount">Rp <?php echo number_format($withdrawal['amount'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['account_number']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                            <?php if ($withdrawal['status'] == 'pending'): ?>
                                                <i class="fas fa-clock"></i> Pending
                                            <?php elseif ($withdrawal['status'] == 'processing'): ?>
                                                <i class="fas fa-spinner"></i> Processing
                                            <?php elseif ($withdrawal['status'] == 'completed'): ?>
                                                <i class="fas fa-check"></i> Completed
                                            <?php elseif ($withdrawal['status'] == 'cancelled'): ?>
                                                <i class="fas fa-times"></i> Cancelled
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($withdrawal['notes'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: #64748b;">
                                        No withdrawal history yet
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Bank Account Modal -->
    <div class="modal" id="bankModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Bank Account</h3>
                <button class="modal-close" onclick="closeBankModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="add_bank" value="1">
                
                <div class="form-group">
                    <label class="form-label">Bank Name</label>
                    <select name="bank_name" class="form-select" required>
                        <option value="">Select Bank</option>
                        <option value="BCA">BCA</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="CIMB Niaga">CIMB Niaga</option>
                        <option value="Danamon">Danamon</option>
                        <option value="Permata">Permata</option>
                        <option value="Maybank">Maybank</option>
                        <option value="BTN">BTN</option>
                        <option value="BJB">BJB</option>
                        <option value="BPD">BPD</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" class="form-input" placeholder="e.g. 1234567890" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" name="account_holder" class="form-input" 
                           value="<?php echo htmlspecialchars($_SESSION['user']['name']); ?>" required>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeBankModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Add Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <i class="fas fa-palette"></i>
                        Kreava
                    </div>
                    <p>Empowering creativity through digital assets. Join our community of creators and innovators.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h4>Products</h4>
                    <ul class="footer-links">
                        <li><a href="../products.php?category=ui-ux">UI/UX Design</a></li>
                        <li><a href="../products.php?category=graphic">Graphic Design</a></li>
                        <li><a href="../products.php?category=web">Web Templates</a></li>
                        <li><a href="../products.php?category=mobile">Mobile Assets</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="../about.php">About</a></li>
                        <li><a href="../blog.php">Blog</a></li>
                        <li><a href="../careers.php">Careers</a></li>
                        <li><a href="../contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="../help.php">Help Center</a></li>
                        <li><a href="../terms.php">Terms</a></li>
                        <li><a href="../privacy.php">Privacy</a></li>
                        <li><a href="../faq.php">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Kreava. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Format Rupiah
        function formatRupiah(input) {
            let value = input.value.replace(/[^\d]/g, '');
            if (value) {
                let formatted = new Intl.NumberFormat('id-ID').format(value);
                input.value = formatted;
            }
        }

        // Bank Modal
        function openBankModal() {
            document.getElementById('bankModal').classList.add('active');
        }

        function closeBankModal() {
            document.getElementById('bankModal').classList.remove('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = '';
                });
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const bankModal = document.getElementById('bankModal');
            if (e.target == bankModal) {
                closeBankModal();
            }
        });
    </script>
</body>
</html>