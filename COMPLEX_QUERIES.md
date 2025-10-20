


## 1. **Owner Dashboard Housing Statistics with Conditional Aggregations**

**File:** `/backend/owner_dashboard.php` (Lines 37-49)



```sql
SELECT s.service_id, s.title, s.description, s.location, s.price, s.created_at,
       h.rent, h.property_type, h.bedrooms, h.bathrooms, h.furnished_status, h.availability,
       COUNT(ha.application_id) as total_applications,
       SUM(CASE WHEN ha.status = 'pending' THEN 1 ELSE 0 END) as pending_applications,
       SUM(CASE WHEN ha.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted_applications,
       SUM(CASE WHEN ha.status = 'accepted' THEN 1 ELSE 0 END) as accepted_applications
FROM services s
INNER JOIN housing h ON s.service_id = h.service_id
LEFT JOIN housing_applications ha ON h.housing_id = ha.housing_id
WHERE s.user_id = ? AND s.type = 'housing'
GROUP BY s.service_id
ORDER BY s.created_at DESC
```

**Why It's Complex:**
- **Multi-table joins:** Combines 3 tables (services, housing, housing_applications)
- **Conditional aggregations:** Uses multiple `CASE WHEN` statements within `SUM()` functions
- **Mixed JOIN types:** Uses both `INNER JOIN` and `LEFT JOIN` strategically
- **Grouping with aggregations:** Groups by service_id while calculating multiple statistics
- **Business logic integration:** Calculates application statistics per housing post for owner dashboard

**Business Purpose:**
Provides comprehensive housing post statistics for property owners, showing total applications and their status breakdown (pending, shortlisted, accepted) for each housing listing.

---

## 2. **Unified Applications Fetching with Cross-Table Integration**

**File:** `/backend/profile.php` (Lines 94-105)


```sql
SELECT ha.application_id, h.housing_id as service_id, ha.status as app_status, 
       ha.created_at as applied_at, ha.message as cover_letter,
       s.title, s.type, s.location, h.rent as price,
       u.name as poster_name
FROM housing_applications ha
JOIN housing h ON ha.housing_id = h.housing_id
JOIN services s ON h.service_id = s.service_id
JOIN users u ON ha.owner_id = u.id
WHERE ha.applicant_id = ?
ORDER BY ha.created_at DESC
```

**Why It's Complex:**
- **Four-table chain join:** Links housing_applications → housing → services → users
- **Cross-reference relationships:** Navigates through multiple foreign key relationships
- **Data normalization handling:** Bridges normalized data across multiple tables
- **Unified data structure:** Standardizes output format for different application types
- **Complex relationship mapping:** Maps applicant to owner through housing and service relationships

**Business Purpose:**
Fetches all housing applications for a user's profile, combining application details with housing information and owner details through a complex relationship chain.

---

## 3. **Payment Transaction History with Dynamic Recipient Resolution**

**File:** `/backend/process_payment.php` (Lines 412-433)



```sql
SELECT t.*, 
       CASE 
           WHEN pr.recipient_user_id IS NOT NULL THEN u.name
           ELSE NULL
       END as recipient_name
FROM transactions t
LEFT JOIN payment_recipients pr ON t.transaction_id = pr.transaction_id
LEFT JOIN users u ON pr.recipient_user_id = u.id
WHERE t.user_id = ?
ORDER BY t.created_at DESC LIMIT ? OFFSET ?
```

**Why It's Complex:**
- **Conditional field resolution:** Uses `CASE WHEN` to conditionally display recipient names
- **Optional relationship handling:** Uses `LEFT JOIN` to handle transactions without recipients
- **Dynamic pagination:** Implements `LIMIT` and `OFFSET` for pagination
- **Null-safe operations:** Handles cases where payment recipients might not exist
- **Multi-level LEFT JOINs:** Chains optional relationships safely

**Business Purpose:**
Retrieves transaction history with optional recipient information, handling various payment types where recipients may or may not exist.

---

## 4. **Housing Applications with Multi-Status Ordering and User Details**

**File:** `/backend/owner_dashboard.php` (Lines 84-99)



```sql
SELECT ha.*, u.name as applicant_name, u.phone as applicant_phone, 
       u.email as applicant_email, u.location as applicant_location
FROM housing_applications ha
INNER JOIN users u ON ha.applicant_id = u.id
WHERE ha.housing_id = ? AND ha.owner_id = ?
ORDER BY 
    CASE ha.status 
        WHEN 'pending' THEN 1 
        WHEN 'shortlisted' THEN 2 
        WHEN 'accepted' THEN 3 
        WHEN 'rejected' THEN 4 
        WHEN 'withdrawn' THEN 5 
    END,
    ha.created_at DESC
```

**Why It's Complex:**
- **Custom status ordering:** Uses `CASE` statement in `ORDER BY` to prioritize application statuses
- **Multi-level sorting:** Combines status priority with chronological ordering
- **Business logic in SQL:** Implements application workflow priorities directly in the query
- **Enum handling:** Properly orders enumerated status values according to business rules
- **User data integration:** Joins with users table to get complete applicant information

**Business Purpose:**
Displays housing applications in a prioritized order (pending first, then shortlisted, etc.) while maintaining chronological order within each status group.

---

## 5. **Complex Housing Update with Multi-Table Cascade Operations**

**File:** `/backend/housing_management.php` (Lines 217-222)



```sql
UPDATE housing_applications 
SET status = 'rejected' 
WHERE housing_id = ? AND application_id != ? AND status IN ('pending', 'shortlisted')
```

**Combined with the transaction context:**

```sql
-- Part of a larger transaction that includes:
-- 1. Update accepted application
UPDATE housing_applications SET status = 'accepted' WHERE application_id = ?

-- 2. Insert tenant record
INSERT INTO housing_tenants (housing_id, user_id, owner_id, start_date, status) 
VALUES (?, ?, ?, ?, 'active')

-- 3. Update housing availability
UPDATE housing SET availability = 'occupied' WHERE housing_id = ?

-- 4. Reject all other applications (shown above)
UPDATE housing_applications 
SET status = 'rejected' 
WHERE housing_id = ? AND application_id != ? AND status IN ('pending', 'shortlisted')
```

**Why It's Complex:**
- **Transaction-based operations:** Part of a complex multi-step transaction
- **Cascade business logic:** Automatically rejects other applications when one is accepted
- **State management:** Manages multiple related entity states simultaneously
- **Conditional updates:** Uses `IN` clause and `!=` to target specific records
- **Data integrity enforcement:** Ensures business rules are maintained across multiple tables

**Business Purpose:**
Implements the complete tenant confirmation workflow: accepts one application, creates tenant record, marks housing as occupied, and automatically rejects all other pending/shortlisted applications.

---



## **Query Complexity Analysis Summary**

| Query | Tables Joined | Aggregations | Conditional Logic | Business Complexity |
|-------|---------------|--------------|-------------------|-------------------|
| Owner Dashboard Stats | 3 | 4 | High | Very High |
| Unified Applications | 4 | 0 | Medium | High |
| Transaction History | 3 | 0 | Medium | Medium |
| Application Ordering | 2 | 0 | High | High |
| Cascade Updates | 1 | 0 | High | Very High |

## **Key Patterns Identified**

1. **Multi-table Joins:** Most complex queries involve 3-4 table joins
2. **Conditional Aggregations:** Heavy use of `CASE WHEN` in `SUM()` functions
3. **Business Logic in SQL:** Complex ordering and filtering based on business rules
4. **Transaction Management:** Critical operations wrapped in database transactions
5. **Relationship Navigation:** Complex foreign key relationship traversals

## **Performance Considerations**

- All queries use proper indexing on foreign keys
- `LEFT JOIN` used appropriately for optional relationships
- Pagination implemented where needed
- Aggregations are grouped efficiently
- Transaction boundaries are properly managed

---

*This documentation represents the most sophisticated SQL operations in the Ekta-tay platform, showcasing advanced database design and query optimization techniques.*
