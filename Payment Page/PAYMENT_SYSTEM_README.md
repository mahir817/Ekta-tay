# Ekta-tay Payment Processing System

## Overview
Complete payment processing system for the Ekta-tay platform supporting multiple payment methods and transaction types.

## Features

### Payment Methods Supported
1. **Credit/Debit Card** - Standard card payments with CVV verification
2. **bKash** - Mobile banking integration
3. **Nagad** - Mobile wallet payments
4. **Rocket** - Mobile financial services

### Transaction Types
1. **Housing Payments**
   - Security deposits
   - Monthly rent payments
   - Service charges

2. **Tuition Payments**
   - Hourly rate payments
   - Session-based payments

3. **Job Services**
   - Application fees (future)
   - Service fees

4. **Expense Management**
   - Personal expense payments
   - Shared expense settlements

5. **Orders**
   - Food orders
   - General service orders

## Database Schema

### Main Tables

#### `transactions`
Stores all payment transactions with complete details including payment method, amounts, fees, and status.

#### `payment_recipients`
Tracks who receives payments (landlords, tutors, service providers).

#### `refunds`
Manages refund requests and processing.

#### `rental_payments`
Recurring monthly rent payment tracking.

#### `tuition_payments`
Session-based or hourly tuition payment records.

#### `user_wallets` (Future)
Platform wallet for users to hold balance.

## API Endpoints

### Process Payment
**Endpoint:** `POST /backend/process_payment.php?action=process_payment`

**Request Body:**
```json
{
  "transaction_type": "housing_rent|housing_deposit|tuition_payment|expense_payment|...",
  "reference_id": 123,
  "reference_type": "expense|housing|job|...",
  "amount": 5000.00,
  "payment_method": "card|bkash|nagad|rocket",
  "card_data": {
    "card_number": "1234567890123456",
    "cardholder_name": "John Doe",
    "expiry_date": "12/25",
    "cvv": "123",
    "last_four": "3456"
  },
  "mobile_data": {
    "number": "01712345678",
    "transaction_id": "TXN123456"
  },
  "billing_address": {
    "street": "123 Main St",
    "city": "Dhaka",
    "postal_code": "1200"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "transaction_id": 456,
  "transaction_ref": "TXN20251018ABCD1234",
  "amount": 5075.00
}
```

### Get Transaction History
**Endpoint:** `GET /backend/process_payment.php?action=get_transaction_history&limit=20&offset=0&status=completed`

### Get Transaction Details
**Endpoint:** `GET /backend/process_payment.php?action=get_transaction_details&transaction_id=123`

### Request Refund
**Endpoint:** `POST /backend/process_payment.php?action=request_refund`

### Verify Payment
**Endpoint:** `GET /backend/process_payment.php?action=verify_payment&transaction_ref=TXN123`

### Get Payment Summary
**Endpoint:** `GET /backend/process_payment.php?action=get_payment_summary`

## Payment Flow

### 1. Initiating Payment
User navigates to payment page with context parameters:
```
payment.php?payment_type=expense_payment&reference_id=123&amount=1000
```

### 2. Payment Context Types
- `expense_payment` - Personal expense
- `shared_expense_payment` - Shared expense share
- `housing_deposit` - Security deposit for housing application
- `housing_rent` - Monthly rent payment
- `tuition_payment` - Tuition session payment
- `food_order` - Food service order
- `general_order` - General service order

### 3. Fee Calculation

**Service Charges:**
- Housing Rent: 2%
- Housing Deposit: 1%
- Tuition Payment: 3%
- Food Order: 5%
- General Order: 3%
- Expenses: 0%

**Processing Fees:**
- Card: ৳25 (fixed)
- bKash/Nagad/Rocket: 1.5%

### 4. Payment Processing
1. Validate payment data
2. Calculate fees
3. Create transaction record
4. Process payment with gateway (simulated)
5. Update transaction status
6. Update related records (expenses, applications, etc.)
7. Create payment recipient record
8. Log activity
9. Return response

### 5. Post-Payment Actions
- Update expense status to 'paid'
- Update housing application deposit status
- Update rental payment status
- Create payment recipient record for fund transfer

### 6. Return Flow
After successful payment, user is redirected to:
- Expenses: `../Expenses Page/expenses.php?transaction_ref=XXX&status=success`
- Housing: `../Modules/Housing/housing.php?transaction_ref=XXX&status=success`
- Tuition: `../Modules/Jobs/jobs.php?transaction_ref=XXX&status=success`
- Others: `../Dashboard/dashboard.php?transaction_ref=XXX&status=success`

## Integration Examples

### Pay for Personal Expense
```html
<a href="../Payment Page/payment.php?payment_type=expense_payment&reference_id=<?php echo $expense_id; ?>&amount=<?php echo $amount; ?>">
    Pay Now
</a>
```

### Pay Housing Deposit
```html
<a href="../Payment Page/payment.php?payment_type=housing_deposit&reference_id=<?php echo $application_id; ?>">
    Pay Deposit
</a>
```

### Pay Monthly Rent
```html
<a href="../Payment Page/payment.php?payment_type=housing_rent&reference_id=<?php echo $rental_payment_id; ?>">
    Pay Rent
</a>
```

### Pay for Tuition
```html
<a href="../Payment Page/payment.php?payment_type=tuition_payment&reference_id=<?php echo $tuition_payment_id; ?>">
    Pay Tuition
</a>
```

### Pay Shared Expense Share
```html
<a href="../Payment Page/payment.php?payment_type=shared_expense_payment&reference_id=<?php echo $share_id; ?>&amount=<?php echo $share_amount; ?>">
    Pay Your Share
</a>
```

## Installation

### 1. Run Database Migration
```sql
-- Run the migration file
SOURCE sql/payment_tables_migration.sql;
```

### 2. Ensure Tables Exist
The payment system requires these tables:
- `transactions`
- `payment_recipients`
- `refunds`
- `saved_payment_methods`
- `rental_payments`
- `tuition_payments`
- `user_wallets`
- `wallet_transactions`

### 3. Update Existing Tables
The migration adds payment tracking columns to:
- `expenses` (transaction_id)
- `shared_expense_shares` (transaction_id)
- `orders` (transaction_id, payment_status)
- `housing_applications` (deposit_transaction_id, deposit_paid, deposit_paid_at)

## Security Features

1. **Session Validation** - All requests require valid user session
2. **Transaction Validation** - Ownership and status checks before processing
3. **SQL Injection Protection** - Prepared statements throughout
4. **Transaction Atomicity** - Database transactions for data consistency
5. **Payment Gateway Integration** - Ready for real payment gateway integration

## Future Enhancements

1. **Wallet System** - Users can maintain balance on platform
2. **Recurring Payments** - Automatic monthly rent deductions
3. **Split Payments** - Multiple payment methods for single transaction
4. **Saved Payment Methods** - Store card/mobile numbers securely
5. **Payment Reminders** - Automated notifications for due payments
6. **Escrow Service** - Hold payments until service completion
7. **Multi-Currency Support** - Support for international transactions
8. **Payment Analytics** - Spending insights and reports
9. **Subscription Plans** - Premium features with subscription payments
10. **Real Payment Gateway Integration** - SSL Commerz, Stripe, PayPal

## Testing

### Test Payment Methods
Currently, the system simulates payment processing with 95% success rate.

### Test Scenarios
1. Card payment with valid details
2. Mobile banking payment
3. Failed payment handling
4. Refund request
5. Transaction history retrieval
6. Payment verification

## Support

For payment-related issues:
1. Check transaction history in payment page
2. Request refund within 7 days
3. Contact support with transaction reference

## Notes

- All amounts are in BDT (Bangladeshi Taka)
- Transaction references are unique: `TXN[YYYYMMDD][8-char-unique-id]`
- Payment status: pending → processing → completed/failed
- Refund processing time: 7 business days
- Service charges are platform revenue
- Processing fees go to payment gateway providers

## Changelog

### Version 1.0.0 (October 2025)
- Initial release
- Multi-method payment processing
- Transaction tracking
- Refund system
- Payment history
- Automated fee calculation
- Integration with all platform features
