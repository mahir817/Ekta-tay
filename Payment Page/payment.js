// Payment Page JavaScript
let paymentContext = {};

document.addEventListener('DOMContentLoaded', function() {
    initializePaymentPage();
});

function initializePaymentPage() {
    // Load payment context
    loadPaymentContext();
    
    // Initialize payment method switching
    initializePaymentMethods();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize card number formatting
    initializeCardFormatting();
    
    // Initialize dropdown
    initializeDropdown();
    
    // Initialize order calculation
    updateOrderSummary();
    
    // Load transaction history
    loadRecentTransactions();
}

// Load Payment Context
function loadPaymentContext() {
    const contextElement = document.getElementById('paymentContext');
    if (contextElement) {
        try {
            paymentContext = JSON.parse(contextElement.value);
            console.log('Payment Context:', paymentContext);
        } catch (e) {
            console.error('Error parsing payment context:', e);
            paymentContext = {
                type: 'Service Payment',
                title: 'Payment',
                amount: 1000,
                transaction_type: 'service_fee'
            };
        }
    }
}

// Payment Method Switching
function initializePaymentMethods() {
    const paymentMethodOptions = document.querySelectorAll('input[name="payment_method"]');
    const cardForm = document.getElementById('cardPaymentForm');
    const mobileForm = document.getElementById('mobilePaymentForm');
    
    paymentMethodOptions.forEach(option => {
        option.addEventListener('change', function() {
            if (this.value === 'card') {
                cardForm.style.display = 'block';
                mobileForm.style.display = 'none';
                
                // Make card fields required
                document.getElementById('cardName').required = true;
                document.getElementById('cardNumber').required = true;
                document.getElementById('expiryDate').required = true;
                document.getElementById('cvv').required = true;
                
                // Make mobile fields optional
                document.getElementById('mobileNumber').required = false;
            } else {
                cardForm.style.display = 'none';
                mobileForm.style.display = 'block';
                
                // Make card fields optional
                document.getElementById('cardName').required = false;
                document.getElementById('cardNumber').required = false;
                document.getElementById('expiryDate').required = false;
                document.getElementById('cvv').required = false;
                
                // Make mobile fields required
                document.getElementById('mobileNumber').required = true;
            }
        });
    });
}

// Card Number Formatting
function initializeCardFormatting() {
    const cardNumberInput = document.getElementById('cardNumber');
    const expiryDateInput = document.getElementById('expiryDate');
    const cvvInput = document.getElementById('cvv');
    const mobileNumberInput = document.getElementById('mobileNumber');
    
    // Format card number (add spaces every 4 digits)
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            value = value.replace(/\D/g, '');
            value = value.substring(0, 16);
            
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Format expiry date (MM/YY)
    if (expiryDateInput) {
        expiryDateInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            
            e.target.value = value;
        });
    }
    
    // CVV - numbers only
    if (cvvInput) {
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        });
    }
    
    // Mobile number - numbers only
    if (mobileNumberInput) {
        mobileNumberInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 11);
        });
    }
}

// Form Validation
function initializeFormValidation() {
    const paymentForm = document.getElementById('paymentForm');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                processPayment();
            }
        });
    }
}

function validateForm() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const termsCheckbox = document.getElementById('terms');
    
    // Check terms and conditions
    if (!termsCheckbox.checked) {
        showNotification('Please accept the terms and conditions', 'error');
        return false;
    }
    
    // Validate based on payment method
    if (paymentMethod === 'card') {
        return validateCardPayment();
    } else {
        return validateMobilePayment();
    }
}

function validateCardPayment() {
    const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
    const expiryDate = document.getElementById('expiryDate').value;
    const cvv = document.getElementById('cvv').value;
    
    // Validate card number (should be 16 digits)
    if (cardNumber.length !== 16) {
        showNotification('Please enter a valid 16-digit card number', 'error');
        return false;
    }
    
    // Validate expiry date format
    const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
    if (!expiryRegex.test(expiryDate)) {
        showNotification('Please enter a valid expiry date (MM/YY)', 'error');
        return false;
    }
    
    // Check if card is expired
    const [month, year] = expiryDate.split('/');
    const expiryDateObj = new Date(2000 + parseInt(year), parseInt(month) - 1);
    const currentDate = new Date();
    
    if (expiryDateObj < currentDate) {
        showNotification('Card has expired', 'error');
        return false;
    }
    
    // Validate CVV
    if (cvv.length !== 3) {
        showNotification('Please enter a valid 3-digit CVV', 'error');
        return false;
    }
    
    return true;
}

function validateMobilePayment() {
    const mobileNumber = document.getElementById('mobileNumber').value;
    
    // Validate mobile number (should be 11 digits starting with 01)
    if (mobileNumber.length !== 11 || !mobileNumber.startsWith('01')) {
        showNotification('Please enter a valid 11-digit mobile number', 'error');
        return false;
    }
    
    return true;
}

// Process Payment
function processPayment() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    // Prepare payment data
    const paymentData = {
        transaction_type: paymentContext.transaction_type || 'service_fee',
        reference_id: paymentContext.reference_id || null,
        reference_type: paymentContext.reference_type || null,
        amount: paymentContext.amount || 0,
        payment_method: paymentMethod
    };
    
    // Add payment method specific data
    if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        paymentData.card_data = {
            card_number: cardNumber,
            last_four: cardNumber.slice(-4),
            cardholder_name: document.getElementById('cardName').value,
            expiry_date: document.getElementById('expiryDate').value,
            cvv: document.getElementById('cvv').value
        };
    } else {
        paymentData.mobile_data = {
            number: document.getElementById('mobileNumber').value,
            transaction_id: document.getElementById('transactionId').value || null
        };
    }
    
    // Add billing address
    paymentData.billing_address = {
        street: document.getElementById('address').value,
        city: document.getElementById('city').value,
        postal_code: document.getElementById('postalCode').value
    };
    
    // Show loading state
    const submitBtn = document.querySelector('.btn-submit');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
    submitBtn.disabled = true;
    
    // Send payment request
    fetch('../backend/process_payment.php?action=process_payment', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(paymentData)
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            // Show success message
            showNotification('Payment processed successfully!', 'success');
            
            // Store transaction reference
            const transactionRef = data.transaction_ref;
            const transactionId = data.transaction_id;
            
            // Redirect to confirmation page after 2 seconds
            setTimeout(function() {
                // Redirect based on payment context
                const returnUrl = getReturnUrl(paymentContext.transaction_type);
                window.location.href = returnUrl + '?transaction_ref=' + transactionRef + '&status=success';
            }, 2000);
        } else {
            // Show error message
            showNotification(data.message || 'Payment failed. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Payment error:', error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        showNotification('An error occurred while processing your payment. Please try again.', 'error');
    });
}

// Get return URL based on transaction type
function getReturnUrl(transactionType) {
    const urls = {
        'expense_payment': '../Expenses Page/expenses.php',
        'shared_expense_payment': '../Expenses Page/expenses.php',
        'housing_deposit': '../Modules/Housing/housing.php',
        'housing_rent': '../Modules/Housing/housing.php',
        'tuition_payment': '../Modules/Jobs/jobs.php',
        'food_order': '../Dashboard/dashboard.php',
        'general_order': '../Dashboard/dashboard.php',
        'service_fee': '../Dashboard/dashboard.php'
    };
    
    return urls[transactionType] || '../Dashboard/dashboard.php';
}

// Order Summary Calculation
function updateOrderSummary() {
    // Get base amount from context
    const baseAmount = paymentContext.amount || 1000;
    
    // Get selected payment method
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'card';
    
    // Calculate fees based on transaction type and payment method
    const serviceFee = calculateServiceFee(baseAmount, paymentContext.transaction_type);
    const processingFee = calculateProcessingFee(baseAmount, paymentMethod);
    const total = baseAmount + serviceFee + processingFee;
    
    // Update display
    document.getElementById('subtotal').textContent = `৳${baseAmount.toFixed(2)}`;
    document.getElementById('serviceFee').textContent = `৳${serviceFee.toFixed(2)}`;
    document.getElementById('processingFee').textContent = `৳${processingFee.toFixed(2)}`;
    document.getElementById('totalAmount').textContent = `৳${total.toFixed(2)}`;
}

// Calculate service fee
function calculateServiceFee(amount, transactionType) {
    const rates = {
        'housing_rent': 0.02,  // 2%
        'housing_deposit': 0.01, // 1%
        'job_application_fee': 0,
        'tuition_payment': 0.03, // 3%
        'expense_payment': 0,
        'shared_expense_payment': 0,
        'food_order': 0.05,  // 5%
        'general_order': 0.03, // 3%
        'service_fee': 0.02
    };
    
    const rate = rates[transactionType] || 0.02;
    return Math.round(amount * rate * 100) / 100;
}

// Calculate processing fee
function calculateProcessingFee(amount, paymentMethod) {
    const fees = {
        'card': 25.00,  // Fixed fee
        'bkash': 0.015, // 1.5%
        'nagad': 0.015, // 1.5%
        'rocket': 0.015, // 1.5%
        'bank_transfer': 0
    };
    
    const fee = fees[paymentMethod] || 25.00;
    
    // If percentage, calculate
    if (fee < 1) {
        return Math.round(amount * fee * 100) / 100;
    }
    
    // If fixed fee
    return fee;
}

// Update summary when payment method changes
document.addEventListener('change', function(e) {
    if (e.target.name === 'payment_method') {
        updateOrderSummary();
    }
});

// User Dropdown Toggle
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userProfile = document.querySelector('.user-profile');
    const dropdown = document.getElementById('userDropdown');
    
    if (dropdown && !userProfile.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'rgba(97, 197, 125, 0.9)' : type === 'error' ? 'rgba(255, 107, 107, 0.9)' : 'rgba(120, 219, 255, 0.9)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 10000;
        animation: slideInFromRight 0.3s ease;
        backdrop-filter: blur(10px);
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
    `;
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInFromRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutToRight 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Load recent transactions (fetch from backend)
function loadRecentTransactions() {
    fetch('../backend/process_payment.php?action=get_transaction_history&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions && data.transactions.length > 0) {
                displayTransactions(data.transactions);
            } else {
                displayEmptyState();
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            displayEmptyState();
        });
}

function displayTransactions(transactions) {
    const historyList = document.querySelector('.payment-history-list');
    
    historyList.innerHTML = transactions.map(transaction => {
        const statusClass = transaction.payment_status === 'completed' ? 'success' : 
                           transaction.payment_status === 'failed' ? 'failed' : 'pending';
        const statusIcon = transaction.payment_status === 'completed' ? 'check-circle' : 
                          transaction.payment_status === 'failed' ? 'times-circle' : 'clock';
        
        return `
            <div class="transaction-item" onclick="viewTransactionDetails(${transaction.transaction_id})">
                <div class="transaction-info">
                    <div class="transaction-header">
                        <h4>${formatTransactionType(transaction.transaction_type)}</h4>
                        <span class="status-badge status-${statusClass}">
                            <i class="fas fa-${statusIcon}"></i>
                            ${transaction.payment_status}
                        </span>
                    </div>
                    <p class="transaction-date">
                        <i class="fas fa-calendar"></i>
                        ${formatDate(transaction.created_at)}
                    </p>
                    <p class="transaction-method">
                        <i class="fas fa-${getPaymentMethodIcon(transaction.payment_method)}"></i>
                        ${transaction.payment_method.toUpperCase()}
                    </p>
                </div>
                <div class="transaction-amount">
                    <span class="amount">৳${parseFloat(transaction.total_amount).toFixed(2)}</span>
                </div>
            </div>
        `;
    }).join('');
}

function displayEmptyState() {
    const historyList = document.querySelector('.payment-history-list');
    historyList.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-file-invoice"></i>
            <p>No recent transactions</p>
        </div>
    `;
}

function formatTransactionType(type) {
    const types = {
        'housing_rent': 'Housing Rent',
        'housing_deposit': 'Housing Deposit',
        'job_application_fee': 'Job Application Fee',
        'tuition_payment': 'Tuition Payment',
        'service_fee': 'Service Fee',
        'expense_payment': 'Expense Payment',
        'shared_expense_payment': 'Shared Expense',
        'food_order': 'Food Order',
        'general_order': 'Service Order'
    };
    return types[type] || type;
}

function getPaymentMethodIcon(method) {
    const icons = {
        'card': 'credit-card',
        'bkash': 'mobile-alt',
        'nagad': 'wallet',
        'rocket': 'rocket',
        'bank_transfer': 'university'
    };
    return icons[method] || 'money-bill';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return 'Today at ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return diffDays + ' days ago';
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
}

function viewTransactionDetails(transactionId) {
    // Fetch and display transaction details in a modal
    fetch(`../backend/process_payment.php?action=get_transaction_details&transaction_id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTransactionModal(data.transaction);
            }
        })
        .catch(error => {
            console.error('Error fetching transaction details:', error);
            showNotification('Failed to load transaction details', 'error');
        });
}

function showTransactionModal(transaction) {
    const modal = document.createElement('div');
    modal.className = 'modal transaction-modal';
    modal.innerHTML = `
        <div class="modal-content glass-card">
            <div class="modal-header">
                <h3>Transaction Details</h3>
                <button class="close-btn" onclick="this.closest('.modal').remove()">&times;</button>
            </div>
            <div class="modal-body transaction-details">
                <div class="detail-row">
                    <span class="label">Transaction ID:</span>
                    <span class="value">${transaction.transaction_ref}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Type:</span>
                    <span class="value">${formatTransactionType(transaction.transaction_type)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Amount:</span>
                    <span class="value">৳${parseFloat(transaction.amount).toFixed(2)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Service Fee:</span>
                    <span class="value">৳${parseFloat(transaction.service_charge).toFixed(2)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Processing Fee:</span>
                    <span class="value">৳${parseFloat(transaction.processing_fee).toFixed(2)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Total Amount:</span>
                    <span class="value amount-highlight">৳${parseFloat(transaction.total_amount).toFixed(2)}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Payment Method:</span>
                    <span class="value">${transaction.payment_method.toUpperCase()}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Status:</span>
                    <span class="value status-${transaction.payment_status}">${transaction.payment_status}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span>
                    <span class="value">${new Date(transaction.created_at).toLocaleString()}</span>
                </div>
                ${transaction.recipient_name ? `
                    <div class="detail-row">
                        <span class="label">Recipient:</span>
                        <span class="value">${transaction.recipient_name}</span>
                    </div>
                ` : ''}
            </div>
            ${transaction.payment_status === 'completed' ? `
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="requestRefund(${transaction.transaction_id})">
                        Request Refund
                    </button>
                </div>
            ` : ''}
        </div>
    `;
    document.body.appendChild(modal);
}

function requestRefund(transactionId) {
    const reason = prompt('Please enter the reason for refund:');
    if (!reason) return;
    
    const refundData = {
        transaction_id: transactionId,
        refund_amount: null, // Will use full amount
        refund_reason: reason
    };
    
    fetch('../backend/process_payment.php?action=request_refund', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(refundData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            document.querySelector('.transaction-modal')?.remove();
            loadRecentTransactions();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error requesting refund:', error);
        showNotification('Failed to request refund', 'error');
    });
}

// Initialize on page load
loadRecentTransactions();
