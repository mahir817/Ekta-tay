<?php
session_start();
require_once "../backend/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login Page/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Load user for sidebar profile
$userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'User', 'email' => ''];

// Get payment context from URL parameters
$service_id = $_GET['service_id'] ?? null;
$service_type = $_GET['type'] ?? null;
$payment_type = $_GET['payment_type'] ?? 'general_order';
$reference_id = $_GET['reference_id'] ?? null;
$amount = $_GET['amount'] ?? 0;
$service_data = null;
$payment_context = [];

// Load context based on payment type
if ($payment_type === 'expense_payment' && $reference_id) {
    // Load expense details
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$reference_id, $user_id]);
    $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service_data) {
        $payment_context = [
            'type' => 'Expense Payment',
            'title' => $service_data['name'],
            'amount' => $service_data['amount'],
            'reference_id' => $reference_id,
            'transaction_type' => 'expense_payment'
        ];
    }
} elseif ($payment_type === 'shared_expense_payment' && $reference_id) {
    // Load shared expense share details
    $stmt = $pdo->prepare("
        SELECT ses.*, se.title, se.date 
        FROM shared_expense_shares ses
        JOIN shared_expenses se ON ses.shared_expense_id = se.id
        WHERE ses.id = ? AND ses.user_id = ?
    ");
    $stmt->execute([$reference_id, $user_id]);
    $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service_data) {
        $payment_context = [
            'type' => 'Shared Expense Payment',
            'title' => $service_data['title'],
            'amount' => $service_data['share_amount'],
            'reference_id' => $reference_id,
            'transaction_type' => 'shared_expense_payment'
        ];
    }
} elseif ($payment_type === 'housing_deposit' && $reference_id) {
    // Load housing application details
    $stmt = $pdo->prepare("
        SELECT ha.*, s.title, s.location, h.rent, h.advance_deposit 
        FROM housing_applications ha
        JOIN housing h ON ha.housing_id = h.housing_id
        JOIN services s ON h.service_id = s.service_id
        WHERE ha.application_id = ? AND ha.applicant_id = ?
    ");
    $stmt->execute([$reference_id, $user_id]);
    $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service_data) {
        $payment_context = [
            'type' => 'Housing Deposit',
            'title' => $service_data['title'],
            'location' => $service_data['location'],
            'amount' => $service_data['advance_deposit'],
            'reference_id' => $reference_id,
            'transaction_type' => 'housing_deposit'
        ];
    }
} elseif ($payment_type === 'housing_rent' && $reference_id) {
    // Load rental payment details
    $stmt = $pdo->prepare("
        SELECT rp.*, h.property_type, s.title, s.location
        FROM rental_payments rp
        JOIN housing h ON rp.housing_id = h.housing_id
        JOIN services s ON h.service_id = s.service_id
        WHERE rp.payment_id = ? AND rp.tenant_id = ?
    ");
    $stmt->execute([$reference_id, $user_id]);
    $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service_data) {
        $payment_context = [
            'type' => 'Monthly Rent Payment',
            'title' => $service_data['title'] . ' - ' . $service_data['payment_month'],
            'amount' => $service_data['total_amount'],
            'reference_id' => $reference_id,
            'transaction_type' => 'housing_rent'
        ];
    }
} elseif ($payment_type === 'tuition_payment' && $reference_id) {
    // Load tuition payment details
    $stmt = $pdo->prepare("
        SELECT tp.*, t.subject, s.title
        FROM tuition_payments tp
        JOIN tuitions t ON tp.tuition_id = t.tuition_id
        JOIN services s ON t.service_id = s.service_id
        WHERE tp.payment_id = ? AND tp.student_id = ?
    ");
    $stmt->execute([$reference_id, $user_id]);
    $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service_data) {
        $payment_context = [
            'type' => 'Tuition Payment',
            'title' => $service_data['title'],
            'amount' => $service_data['total_amount'],
            'reference_id' => $reference_id,
            'transaction_type' => 'tuition_payment'
        ];
    }
} elseif ($service_id) {
    // Load general service details
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$service_id]);
    $service_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($service_data) {
        $payment_context = [
            'type' => ucfirst($service_data['type']) . ' Service',
            'title' => $service_data['title'],
            'amount' => $amount ?: ($service_data['price'] ?? 0),
            'reference_id' => $service_id,
            'reference_type' => 'service',
            'transaction_type' => $service_data['type'] === 'food' ? 'food_order' : 'general_order'
        ];
    }
}

// Default context if nothing loaded
if (empty($payment_context)) {
    $payment_context = [
        'type' => 'Service Payment',
        'title' => 'Payment',
        'amount' => $amount ?: 1000,
        'reference_id' => null,
        'transaction_type' => 'service_fee'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Ekta-tay</title>
    <link rel="stylesheet" href="payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../images/logo.png" alt="Ektate Logo" class="logo-img" />
                    <div class="logo-text">Ekta-tay</div>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="../Dashboard/dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../Modules/Housing/housing.php" class="nav-link">
                        <i class="nav-icon fas fa-building"></i>
                        Housing
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../Modules/Jobs/jobs.php" class="nav-link">
                        <i class="nav-icon fas fa-briefcase"></i>
                        Jobs
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../Post Service Page/post_service.php" class="nav-link">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        Post Service
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../Expenses Page/expenses.php" class="nav-link">
                        <i class="nav-icon fas fa-wallet"></i>
                        Expenses
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="payment.php" class="nav-link active">
                        <i class="nav-icon fas fa-credit-card"></i>
                        Payment
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="../Profile page/profile.php" class="nav-link">
                        <i class="nav-icon fas fa-user"></i>
                        Profile
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-question-circle"></i>
                        Help
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">Payment</h1>
                <div class="user-dropdown">
                    <div class="user-profile" onclick="toggleDropdown()">
                        <div class="user-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?></span>
                            <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                        </div>
                    </div>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="../Profile page/profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="../Settings page/settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../backend/logout.php" class="dropdown-item logout-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payment Content -->
            <div class="payment-wrapper">
                <div class="payment-grid">
                    <!-- Payment Form Card -->
                    <div class="glass-card payment-form-card">
                        <div class="card-header">
                            <h2><i class="fas fa-credit-card"></i> Payment Details</h2>
                            <p class="subtitle">Secure payment processing</p>
                        </div>

                        <form id="paymentForm" class="payment-form">
                            <!-- Payment Method Selection -->
                            <div class="form-section">
                                <h3 class="section-title">Payment Method</h3>
                                <div class="payment-methods">
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="card" checked>
                                        <div class="method-card">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Credit/Debit Card</span>
                                        </div>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="bkash">
                                        <div class="method-card">
                                            <i class="fas fa-mobile-alt"></i>
                                            <span>bKash</span>
                                        </div>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="nagad">
                                        <div class="method-card">
                                            <i class="fas fa-wallet"></i>
                                            <span>Nagad</span>
                                        </div>
                                    </label>
                                    <label class="payment-method-option">
                                        <input type="radio" name="payment_method" value="rocket">
                                        <div class="method-card">
                                            <i class="fas fa-rocket"></i>
                                            <span>Rocket</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Card Payment Form -->
                            <div id="cardPaymentForm" class="payment-details-section">
                                <div class="form-group">
                                    <label for="cardName">Cardholder Name</label>
                                    <input type="text" id="cardName" name="card_name" placeholder="John Doe" required>
                                </div>

                                <div class="form-group">
                                    <label for="cardNumber">Card Number</label>
                                    <div class="card-input-wrapper">
                                        <input type="text" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                        <div class="card-icons">
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="expiryDate">Expiry Date</label>
                                        <input type="text" id="expiryDate" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="cvv">CVV</label>
                                        <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile Banking Form -->
                            <div id="mobilePaymentForm" class="payment-details-section" style="display: none;">
                                <div class="form-group">
                                    <label for="mobileNumber">Mobile Number</label>
                                    <input type="text" id="mobileNumber" name="mobile_number" placeholder="01XXXXXXXXX" maxlength="11">
                                </div>

                                <div class="form-group">
                                    <label for="transactionId">Transaction ID (Optional)</label>
                                    <input type="text" id="transactionId" name="transaction_id" placeholder="Enter transaction ID after payment">
                                </div>

                                <div class="info-box">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Please complete the payment to the provided merchant number and enter the transaction ID here.</p>
                                </div>
                            </div>

                            <!-- Billing Address -->
                            <div class="form-section">
                                <h3 class="section-title">Billing Address</h3>
                                <div class="form-group">
                                    <label for="address">Street Address</label>
                                    <input type="text" id="address" name="address" placeholder="123 Main Street" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" name="city" placeholder="Dhaka" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="postalCode">Postal Code</label>
                                        <input type="text" id="postalCode" name="postal_code" placeholder="1200" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="form-group checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <span>I agree to the <a href="#" class="terms-link">Terms and Conditions</a></span>
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-submit">
                                <i class="fas fa-lock"></i>
                                Complete Payment
                            </button>
                        </form>
                    </div>

                    <!-- Order Summary Card -->
                    <div class="glass-card order-summary-card">
                        <div class="card-header">
                            <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                        </div>

                        <div class="order-details">
                            <?php if (!empty($payment_context)): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($payment_context['title']); ?></h4>
                                        <p class="item-type"><?php echo htmlspecialchars($payment_context['type']); ?></p>
                                        <?php if (isset($payment_context['location'])): ?>
                                            <p class="item-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($payment_context['location']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="divider"></div>
                            <?php endif; ?>

                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span class="amount" id="subtotal">৳<?php echo number_format($payment_context['amount'], 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Service Fee</span>
                                <span class="amount" id="serviceFee">৳0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Processing Fee</span>
                                <span class="amount" id="processingFee">৳0.00</span>
                            </div>
                            <div class="divider"></div>
                            <div class="summary-row total-row">
                                <span>Total Amount</span>
                                <span class="amount total-amount" id="totalAmount">৳<?php echo number_format($payment_context['amount'], 2); ?></span>
                            </div>
                            
                            <!-- Hidden fields for payment processing -->
                            <input type="hidden" id="paymentContext" value='<?php echo htmlspecialchars(json_encode($payment_context), ENT_QUOTES); ?>'>
                        </div>

                        <!-- Security Badge -->
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>Secure Payment</strong>
                                <p>Your payment information is encrypted and secure</p>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="payment-info">
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span>Processing Time: Instant</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-undo"></i>
                                <span>Refund Available: 7 Days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Section -->
                <div class="glass-card payment-history-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Recent Transactions</h2>
                    </div>
                    <div class="payment-history-list">
                        <!-- Transactions will be loaded dynamically -->
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <p>No recent transactions</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="payment.js"></script>
</body>
</html>
