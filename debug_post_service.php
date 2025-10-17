<?php
session_start();
require_once "backend/db.php";

// Set test user for debugging
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Change to your user ID
}

echo "<h2>Post Service Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div style='background: green; color: white; padding: 10px; margin: 10px;'>";
    echo "<strong>✅ FORM SUBMITTED SUCCESSFULLY!</strong><br>";
    echo "POST data received:<br>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
    
    // Test database insertion
    try {
        $serviceType = $_POST['service_type'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $rent = isset($_POST['rent']) && $_POST['rent'] !== '' ? (float)$_POST['rent'] : 0;
        
        echo "<div style='background: blue; color: white; padding: 10px; margin: 10px;'>";
        echo "<strong>Processing data:</strong><br>";
        echo "Service Type: $serviceType<br>";
        echo "Title: $title<br>";
        echo "Description: $description<br>";
        echo "Rent: $rent<br>";
        echo "</div>";
        
        if ($serviceType === 'housing' && $title && $description && $rent > 0) {
            $pdo->beginTransaction();
            
            // Insert service
            $stmt = $pdo->prepare("INSERT INTO services (user_id, title, description, type, price, location) VALUES (?, ?, ?, 'housing', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $rent, $_POST['location'] ?? '']);
            $serviceId = $pdo->lastInsertId();
            
            // Insert housing
            $hStmt = $pdo->prepare("INSERT INTO housing (service_id, rent, property_type, bedrooms, bathrooms, furnished_status) VALUES (?, ?, ?, ?, ?, ?)");
            $hStmt->execute([
                $serviceId, 
                $rent, 
                $_POST['property_type'] ?? 'apartment',
                $_POST['bedrooms'] ?? 0,
                $_POST['bathrooms'] ?? 0,
                $_POST['furnished_status'] ?? 'unfurnished'
            ]);
            
            $pdo->commit();
            
            echo "<div style='background: darkgreen; color: white; padding: 10px; margin: 10px;'>";
            echo "<strong>✅ DATABASE INSERT SUCCESSFUL!</strong><br>";
            echo "Service ID: $serviceId<br>";
            echo "</div>";
        } else {
            echo "<div style='background: orange; color: white; padding: 10px; margin: 10px;'>";
            echo "<strong>⚠️ VALIDATION FAILED</strong><br>";
            echo "Missing required fields or invalid data<br>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<div style='background: red; color: white; padding: 10px; margin: 10px;'>";
        echo "<strong>❌ DATABASE ERROR:</strong><br>";
        echo $e->getMessage();
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Post Service</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-container { background: #f5f5f5; padding: 20px; border-radius: 8px; max-width: 600px; }
        input, textarea, select { width: 100%; padding: 8px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #005a87; }
        .housing-section { background: #e8f4f8; padding: 15px; margin: 15px 0; border-radius: 4px; }
    </style>
</head>
<body>

<h3>Simple Housing Post Test Form</h3>

<div class="form-container">
    <form method="POST" action="" id="testForm">
        <label>Service Type:</label>
        <select name="service_type" id="service_type" required>
            <option value="">-- Choose --</option>
            <option value="housing">Housing</option>
        </select>

        <label>Title:</label>
        <input type="text" name="title" placeholder="Enter title" required>

        <label>Description:</label>
        <textarea name="description" placeholder="Enter description" required></textarea>

        <label>Location:</label>
        <input type="text" name="location" placeholder="Enter location">

        <div class="housing-section" id="housingSection" style="display: none;">
            <h4>Housing Details</h4>
            
            <label>Property Type:</label>
            <select name="property_type">
                <option value="apartment">Apartment</option>
                <option value="room">Room</option>
            </select>

            <label>Rent (BDT):</label>
            <input type="number" name="rent" step="0.01" placeholder="Enter rent amount" required>

            <label>Bedrooms:</label>
            <input type="number" name="bedrooms" value="1">

            <label>Bathrooms:</label>
            <input type="number" name="bathrooms" value="1">

            <label>Furnished Status:</label>
            <select name="furnished_status">
                <option value="unfurnished">Unfurnished</option>
                <option value="furnished">Furnished</option>
            </select>
        </div>

        <button type="submit" id="submitBtn">Post Housing Service</button>
    </form>
</div>

<script>
console.log("Debug script loaded");

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded");
    
    const serviceType = document.getElementById('service_type');
    const housingSection = document.getElementById('housingSection');
    const form = document.getElementById('testForm');
    const submitBtn = document.getElementById('submitBtn');
    
    console.log("Elements found:", {
        serviceType: !!serviceType,
        housingSection: !!housingSection,
        form: !!form,
        submitBtn: !!submitBtn
    });
    
    // Show housing section when housing is selected
    serviceType.addEventListener('change', function() {
        console.log("Service type changed to:", this.value);
        if (this.value === 'housing') {
            housingSection.style.display = 'block';
            console.log("Housing section shown");
        } else {
            housingSection.style.display = 'none';
            console.log("Housing section hidden");
        }
    });
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        console.log("Form submit event triggered");
        
        const formData = new FormData(this);
        console.log("Form data:");
        for (let [key, value] of formData.entries()) {
            console.log(key + ": " + value);
        }
        
        // Let the form submit normally (don't prevent default)
        console.log("Allowing form to submit normally");
    });
    
    // Button click handler for additional debugging
    submitBtn.addEventListener('click', function(e) {
        console.log("Submit button clicked");
    });
});
</script>

</body>
</html>
