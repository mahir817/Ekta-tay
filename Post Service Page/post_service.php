<?php
session_start();
require_once "../backend/db.php";
@include __DIR__ . "/../backend/session.php";

// If form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serviceType = $_POST['service_type'];
    $user_id = $_SESSION['user_id']; // assuming session keeps user_id

    if ($serviceType == "housing") {
        $sql = "INSERT INTO housing 
                (service_id, property_type, size_sqft, floor_no, total_floors, furnished_status, 
                 parking_spaces, bedrooms, bathrooms, balconies, rent, service_charge, advance_deposit, 
                 available_from, available_for, negotiable, property_condition, status, furnished) 
                VALUES 
                ('$user_id','{$_POST['property_type']}','{$_POST['size_sqft']}','{$_POST['floor_no']}',
                 '{$_POST['total_floors']}','{$_POST['furnished_status']}','{$_POST['parking_spaces']}',
                 '{$_POST['bedrooms']}','{$_POST['bathrooms']}','{$_POST['balconies']}',
                 '{$_POST['rent']}','{$_POST['service_charge']}','{$_POST['advance_deposit']}',
                 '{$_POST['available_from']}','{$_POST['available_for']}','{$_POST['negotiable']}',
                 '{$_POST['property_condition']}','available','{$_POST['furnished']}')";
        mysqli_query($conn, $sql);

    } elseif ($serviceType == "job") {
        $sql = "INSERT INTO jobs 
                (service_id, title, job_type, description, salary, location, requirements) 
                VALUES 
                ('$user_id','{$_POST['title']}','{$_POST['job_type']}','{$_POST['description']}',
                 '{$_POST['salary']}','{$_POST['location']}','{$_POST['requirements']}')";
        mysqli_query($conn, $sql);

    } elseif ($serviceType == "food") {
        $sql = "INSERT INTO food_services 
                (service_id, food_type, provider_name, location, available_date, price, description) 
                VALUES 
                ('$user_id','{$_POST['food_type']}','{$_POST['provider_name']}','{$_POST['location']}',
                 '{$_POST['available_date']}','{$_POST['price']}','{$_POST['description']}')";
        mysqli_query($conn, $sql);
    }

    header("Location: dashboard.php?msg=Service Posted Successfully");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post a Service</title>
    <link rel="stylesheet" href="../styles.css">
    <script>
        function toggleForm() {
            let type = document.getElementById("service_type").value;
            document.getElementById("housingForm").style.display = (type === "housing") ? "block" : "none";
            document.getElementById("jobForm").style.display = (type === "job") ? "block" : "none";
            document.getElementById("foodForm").style.display = (type === "food") ? "block" : "none";
        }
    </script>
</head>
<body>
    <h2>Post Your Service</h2>
    <form method="POST" action="">
        <label>Select Service Type:</label>
        <select name="service_type" id="service_type" onchange="toggleForm()" required>
            <option value="">-- Choose --</option>
            <option value="housing">Housing</option>
            <option value="job">Job</option>
            <option value="food">Food Service</option>
        </select>

        <!-- Housing Form -->
        <div id="housingForm" style="display:none;">
            <h3>Housing Details</h3>
            Property Type: <input type="text" name="property_type"><br>
            Size (sqft): <input type="number" name="size_sqft"><br>
            Floor No: <input type="text" name="floor_no"><br>
            Total Floors: <input type="number" name="total_floors"><br>
            Rent: <input type="number" name="rent"><br>
            Advance Deposit: <input type="number" name="advance_deposit"><br>
            Available From: <input type="date" name="available_from"><br>
            Condition: <input type="text" name="property_condition"><br>
        </div>

        <!-- Job Form -->
        <div id="jobForm" style="display:none;">
            <h3>Job Details</h3>
            Title: <input type="text" name="title"><br>
            Type: <select name="job_type">
                <option value="tuition">Tuition</option>
                <option value="part-time">Part-time</option>
                <option value="full-time">Full-time</option>
                <option value="internship">Internship</option>
            </select><br>
            Description: <textarea name="description"></textarea><br>
            Salary: <input type="number" name="salary"><br>
            Location: <input type="text" name="location"><br>
            Requirements: <textarea name="requirements"></textarea><br>
        </div>

        <!-- Food Form -->
        <div id="foodForm" style="display:none;">
            <h3>Food Service Details</h3>
            Provider Name: <input type="text" name="provider_name"><br>
            Type: <select name="food_type">
                <option value="meal">Meal</option>
                <option value="tiffin">Tiffin</option>
                <option value="laundry">Laundry</option>
                <option value="others">Others</option>
            </select><br>
            Location: <input type="text" name="location"><br>
            Available Date: <input type="date" name="available_date"><br>
            Price: <input type="number" name="price"><br>
            Description: <textarea name="description"></textarea><br>
        </div>

        <button type="submit">Post Service</button>
    </form>
</body>
</html>