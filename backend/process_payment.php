<?php
/**
 * Payment Processing Backend for Ekta-tay Platform
 * Handles all payment transactions including:
 * - Housing payments (rent, deposits)
 * - Job application fees
 * - Tuition payments
 * - Expense payments
 * - Shared expense payments
 * - Food orders
 * - General service orders
 */

session_start();
require_once "db.php";

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login to process payments"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'process_payment';

// Route to appropriate handler
switch ($action) {
    case 'process_payment':
        processPayment();
        break;
    case 'get_transaction_history':
        getTransactionHistory();
        break;
    case 'get_transaction_details':
        getTransactionDetails();
        break;
    case 'request_refund':
        requestRefund();
        break;
    case 'verify_payment':
        verifyPayment();
        break;
    case 'get_payment_summary':
        getPaymentSummary();
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
}

/**
 * Main payment processing function
 */
function processPayment() {
    global $pdo, $user_id;
    
    try {
        // Get payment data
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required = ['amount', 'payment_method', 'transaction_type'];
        foreach ($required as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Extract payment data
        $transaction_type = $input['transaction_type'];
        $reference_id = $input['reference_id'] ?? null;
        $reference_type = $input['reference_type'] ?? null;
        $amount = floatval($input['amount']);
        $payment_method = $input['payment_method'];
        
        // Calculate fees
        $service_charge = calculateServiceCharge($amount, $transaction_type);
        $processing_fee = calculateProcessingFee($amount, $payment_method);
        $total_amount = $amount + $service_charge + $processing_fee;
        
        // Payment method specific data
        $card_data = $input['card_data'] ?? null;
        $mobile_data = $input['mobile_data'] ?? null;
        $billing_address = $input['billing_address'] ?? null;
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Create transaction record
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                user_id, transaction_type, reference_id, reference_type,
                amount, service_charge, processing_fee, total_amount,
                payment_method, payment_status, transaction_ref,
                card_last_four, mobile_number,
                billing_address, billing_city, billing_postal_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $transaction_ref = generateTransactionRef();
        
        $stmt->execute([
            $user_id,
            $transaction_type,
            $reference_id,
            $reference_type,
            $amount,
            $service_charge,
            $processing_fee,
            $total_amount,
            $payment_method,
            'processing',
            $transaction_ref,
            $card_data['last_four'] ?? null,
            $mobile_data['number'] ?? null,
            $billing_address['street'] ?? null,
            $billing_address['city'] ?? null,
            $billing_address['postal_code'] ?? null
        ]);
        
        $transaction_id = $pdo->lastInsertId();
        
        // Process payment with gateway (simulated for now)
        $payment_result = processPaymentGateway($payment_method, $total_amount, $card_data, $mobile_data, $transaction_ref);
        
        if ($payment_result['success']) {
            // Update transaction as completed
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET payment_status = 'completed', payment_date = NOW(), updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$transaction_id]);
            
            // Handle specific transaction type updates
            handleTransactionTypeUpdate($transaction_type, $reference_id, $transaction_id, $amount);
            
            // Create payment recipient record
            createPaymentRecipient($transaction_id, $transaction_type, $reference_id, $amount);
            
            // Log activity
            require_once "log_activity.php";
            logActivity($user_id, 'payment_completed', 'Payment Successful', 
                "Paid ৳" . number_format($total_amount, 2) . " via " . strtoupper($payment_method), 
                $transaction_id);
            
            $pdo->commit();
            
            echo json_encode([
                "success" => true,
                "message" => "Payment processed successfully",
                "transaction_id" => $transaction_id,
                "transaction_ref" => $transaction_ref,
                "amount" => $total_amount
            ]);
        } else {
            // Payment failed
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET payment_status = 'failed', updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$transaction_id]);
            
            $pdo->commit();
            
            throw new Exception($payment_result['message'] ?? 'Payment processing failed');
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Handle updates based on transaction type
 */
function handleTransactionTypeUpdate($transaction_type, $reference_id, $transaction_id, $amount) {
    global $pdo;
    
    switch ($transaction_type) {
        case 'expense_payment':
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    UPDATE expenses 
                    SET status = 'paid', transaction_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$transaction_id, $reference_id]);
            }
            break;
            
        case 'shared_expense_payment':
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    UPDATE shared_expense_shares 
                    SET status = 'paid', transaction_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$transaction_id, $reference_id]);
            }
            break;
            
        case 'housing_deposit':
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    UPDATE housing_applications 
                    SET deposit_paid = 1, deposit_paid_at = NOW(), deposit_transaction_id = ? 
                    WHERE application_id = ?
                ");
                $stmt->execute([$transaction_id, $reference_id]);
            }
            break;
            
        case 'housing_rent':
            if ($reference_id) {
                // Update rental_payments table
                $stmt = $pdo->prepare("
                    UPDATE rental_payments 
                    SET status = 'paid', paid_date = NOW(), transaction_id = ? 
                    WHERE payment_id = ?
                ");
                $stmt->execute([$transaction_id, $reference_id]);
            }
            break;
            
        case 'tuition_payment':
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    UPDATE tuition_payments 
                    SET status = 'paid', payment_date = NOW(), transaction_id = ? 
                    WHERE payment_id = ?
                ");
                $stmt->execute([$transaction_id, $reference_id]);
            }
            break;
            
        case 'food_order':
        case 'general_order':
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'confirmed', payment_status = 'paid', transaction_id = ? 
                    WHERE order_id = ?
                ");
                $stmt->execute([$transaction_id, $reference_id]);
            }
            break;
    }
}

/**
 * Create payment recipient record
 */
function createPaymentRecipient($transaction_id, $transaction_type, $reference_id, $amount) {
    global $pdo;
    
    $recipient_id = null;
    
    // Determine recipient based on transaction type
    switch ($transaction_type) {
        case 'housing_rent':
        case 'housing_deposit':
            // Get housing owner
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    SELECT ha.owner_id 
                    FROM housing_applications ha 
                    WHERE ha.application_id = ?
                ");
                $stmt->execute([$reference_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $recipient_id = $result['owner_id'] ?? null;
            }
            break;
            
        case 'tuition_payment':
            // Get tutor
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    SELECT tutor_id 
                    FROM tuition_payments 
                    WHERE payment_id = ?
                ");
                $stmt->execute([$reference_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $recipient_id = $result['tutor_id'] ?? null;
            }
            break;
            
        case 'food_order':
        case 'general_order':
            // Get service provider
            if ($reference_id) {
                $stmt = $pdo->prepare("
                    SELECT s.user_id 
                    FROM orders o 
                    JOIN services s ON o.service_id = s.service_id 
                    WHERE o.order_id = ?
                ");
                $stmt->execute([$reference_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $recipient_id = $result['user_id'] ?? null;
            }
            break;
    }
    
    // Create recipient record if recipient found
    if ($recipient_id) {
        $stmt = $pdo->prepare("
            INSERT INTO payment_recipients (transaction_id, recipient_user_id, amount, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$transaction_id, $recipient_id, $amount]);
    }
}

/**
 * Calculate service charge based on amount and type
 */
function calculateServiceCharge($amount, $transaction_type) {
    // Different service charges for different transaction types
    $rates = [
        'housing_rent' => 0.02,  // 2%
        'housing_deposit' => 0.01, // 1%
        'job_application_fee' => 0,
        'tuition_payment' => 0.03, // 3%
        'expense_payment' => 0,
        'shared_expense_payment' => 0,
        'food_order' => 0.05,  // 5%
        'general_order' => 0.03, // 3%
        'service_fee' => 0
    ];
    
    $rate = $rates[$transaction_type] ?? 0.02;
    return round($amount * $rate, 2);
}

/**
 * Calculate processing fee based on payment method
 */
function calculateProcessingFee($amount, $payment_method) {
    $fees = [
        'card' => 25.00,  // Fixed fee
        'bkash' => 0.015, // 1.5%
        'nagad' => 0.015, // 1.5%
        'rocket' => 0.015, // 1.5%
        'bank_transfer' => 0
    ];
    
    $fee = $fees[$payment_method] ?? 0;
    
    // If percentage, calculate
    if ($fee < 1) {
        return round($amount * $fee, 2);
    }
    
    // If fixed fee
    return $fee;
}

/**
 * Simulate payment gateway processing
 * In production, integrate with real payment gateways
 */
function processPaymentGateway($payment_method, $amount, $card_data, $mobile_data, $transaction_ref) {
    // Simulate processing delay
    usleep(500000); // 0.5 seconds
    
    // Simulate success rate (95% success for demo)
    $success = (rand(1, 100) <= 95);
    
    if ($success) {
        return [
            'success' => true,
            'gateway_ref' => $transaction_ref,
            'message' => 'Payment processed successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Payment declined. Please try again or use a different payment method.'
        ];
    }
}

/**
 * Generate unique transaction reference
 */
function generateTransactionRef() {
    return 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -8));
}

/**
 * Get transaction history for user
 */
function getTransactionHistory() {
    global $pdo, $user_id;
    
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);
    $status = $_GET['status'] ?? null;
    
    try {
        $sql = "
            SELECT t.*, 
                   CASE 
                       WHEN pr.recipient_user_id IS NOT NULL THEN u.name
                       ELSE NULL
                   END as recipient_name
            FROM transactions t
            LEFT JOIN payment_recipients pr ON t.transaction_id = pr.transaction_id
            LEFT JOIN users u ON pr.recipient_user_id = u.id
            WHERE t.user_id = ?
        ";
        
        $params = [$user_id];
        
        if ($status) {
            $sql .= " AND t.payment_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "transactions" => $transactions
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Get detailed transaction information
 */
function getTransactionDetails() {
    global $pdo, $user_id;
    
    $transaction_id = intval($_GET['transaction_id'] ?? 0);
    
    if (!$transaction_id) {
        echo json_encode(["success" => false, "message" => "Transaction ID required"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, 
                   pr.recipient_user_id, pr.amount as recipient_amount, pr.status as recipient_status,
                   u.name as recipient_name, u.email as recipient_email
            FROM transactions t
            LEFT JOIN payment_recipients pr ON t.transaction_id = pr.transaction_id
            LEFT JOIN users u ON pr.recipient_user_id = u.id
            WHERE t.transaction_id = ? AND t.user_id = ?
        ");
        $stmt->execute([$transaction_id, $user_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found");
        }
        
        echo json_encode([
            "success" => true,
            "transaction" => $transaction
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Request a refund
 */
function requestRefund() {
    global $pdo, $user_id;
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $transaction_id = intval($input['transaction_id'] ?? 0);
        $refund_amount = floatval($input['refund_amount'] ?? 0);
        $refund_reason = $input['refund_reason'] ?? '';
        
        if (!$transaction_id || $refund_amount <= 0) {
            throw new Exception("Invalid refund request");
        }
        
        // Verify transaction ownership
        $stmt = $pdo->prepare("
            SELECT * FROM transactions 
            WHERE transaction_id = ? AND user_id = ? AND payment_status = 'completed'
        ");
        $stmt->execute([$transaction_id, $user_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found or not eligible for refund");
        }
        
        if ($refund_amount > $transaction['total_amount']) {
            throw new Exception("Refund amount cannot exceed transaction amount");
        }
        
        // Check if refund already requested
        $stmt = $pdo->prepare("SELECT refund_id FROM refunds WHERE transaction_id = ?");
        $stmt->execute([$transaction_id]);
        if ($stmt->fetch()) {
            throw new Exception("Refund already requested for this transaction");
        }
        
        // Create refund request
        $stmt = $pdo->prepare("
            INSERT INTO refunds (transaction_id, refund_amount, refund_reason, requested_by) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$transaction_id, $refund_amount, $refund_reason, $user_id]);
        
        echo json_encode([
            "success" => true,
            "message" => "Refund requested successfully. It will be processed within 7 business days."
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Verify payment status
 */
function verifyPayment() {
    global $pdo, $user_id;
    
    $transaction_ref = $_GET['transaction_ref'] ?? '';
    
    if (!$transaction_ref) {
        echo json_encode(["success" => false, "message" => "Transaction reference required"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM transactions 
            WHERE transaction_ref = ? AND user_id = ?
        ");
        $stmt->execute([$transaction_ref, $user_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found");
        }
        
        echo json_encode([
            "success" => true,
            "status" => $transaction['payment_status'],
            "transaction" => $transaction
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}

/**
 * Get payment summary/statistics
 */
function getPaymentSummary() {
    global $pdo, $user_id;
    
    try {
        // Total spent
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(total_amount) as total_spent,
                SUM(CASE WHEN payment_status = 'completed' THEN total_amount ELSE 0 END) as completed_amount,
                SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END) as pending_amount
            FROM transactions 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total received (as recipient)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_received_count,
                SUM(pr.amount) as total_received
            FROM payment_recipients pr
            JOIN transactions t ON pr.transaction_id = t.transaction_id
            WHERE pr.recipient_user_id = ? AND t.payment_status = 'completed'
        ");
        $stmt->execute([$user_id]);
        $received = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "summary" => array_merge($summary, $received)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}
?>
