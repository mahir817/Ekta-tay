<?php
// Mock dashboard to show successful registration
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Ekta Tay</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="success-message">
            <div class="success-icon">✅</div>
            <h1>Registration Successful!</h1>
            <p class="success-text">Database connection established successfully!</p>
            <p class="welcome-text">Welcome to Ekta Tay - Your digital ecosystem for student life</p>
            
            <div class="features-preview">
                <h3>What you can do with Ekta Tay:</h3>
                <ul>
                    <li>🏠 Find housing and compatible roommates</li>
                    <li>👨‍🏫 Connect with tutors for your subjects</li>
                    <li>💼 Discover part-time job opportunities</li>
                    <li>💰 Track your expenses and payments</li>
                    <li>🍕 Order food and book essential services</li>
                    <li>🌍 Get study abroad guidance from mentors</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <button onclick="goToLogin()" class="btn btn-primary">Go to Login</button>
                <button onclick="goToHome()" class="btn btn-secondary">Go to Home</button>
            </div>
        </div>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>
