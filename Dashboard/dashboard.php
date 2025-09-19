<?php
session_start();
require_once("../backend/session.php");
require_once("../backend/db.php");

// Fetch capabilities for logged-in user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT capability FROM user_capabilities WHERE user_id = ?");
$stmt->execute([$user_id]);
$capabilities = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekta-Tay Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <h2 class="logo">Ektate</h2>
        <ul>
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <?php if (in_array("find_room", $capabilities) || in_array("offer_room", $capabilities)) { ?>
                <li><a href="../Modules/Housing/housing.php"><i class="fa-solid fa-house"></i> Housing</a></li>
            <?php } ?>
            <?php if (in_array("find_job", $capabilities) || in_array("post_job", $capabilities)) { ?>
                <li><a href="../Modules/Jobs/jobs.php"><i class="fa-solid fa-briefcase"></i> Jobs</a></li>
            <?php } ?>
            <?php if (in_array("find_tutor", $capabilities) || in_array("offer_tuition", $capabilities)) { ?>
                <li><a href="../Modules/Jobs/jobs.php?type=tuition"><i class="fa-solid fa-graduation-cap"></i> Tuition</a></li>
            <?php } ?>
            <?php if (in_array("food_service", $capabilities)) { ?>
                <li><a href="../Modules/Food & services/services.php"><i class="fa-solid fa-utensils"></i> Services</a></li>
            <?php } ?>
            <?php if (in_array("expense_tracking", $capabilities)) { ?>
                <li><a href="../Modules/Housing/expenses.php"><i class="fa-solid fa-wallet"></i> Expenses</a></li>
            <?php } ?>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <h1>Dashboard</h1>
            <div class="profile">
                <img src="../images/user.png" alt="Profile">
                <span><?php echo $_SESSION['user_name']; ?></span>
            </div>
        </header>

        <section class="cards">
            <?php if (in_array("find_room", $capabilities) || in_array("offer_room", $capabilities)) { ?>
                <div class="glass-card">
                    <i class="fa-solid fa-house"></i>
                    <h3>Housing</h3>
                    <p>Find or offer rooms easily</p>
                </div>
            <?php } ?>
            <?php if (in_array("find_job", $capabilities) || in_array("post_job", $capabilities)) { ?>
                <div class="glass-card">
                    <i class="fa-solid fa-briefcase"></i>
                    <h3>Jobs</h3>
                    <p>Search or post part-time jobs</p>
                </div>
            <?php } ?>
            <?php if (in_array("find_tutor", $capabilities) || in_array("offer_tuition", $capabilities)) { ?>
                <div class="glass-card">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <h3>Tuition</h3>
                    <p>Hire or offer tutoring</p>
                </div>
            <?php } ?>
            <?php if (in_array("food_service", $capabilities)) { ?>
                <div class="glass-card">
                    <i class="fa-solid fa-utensils"></i>
                    <h3>Food & Services</h3>
                    <p>Order meals, laundry & more</p>
                </div>
            <?php } ?>
            <?php if (in_array("expense_tracking", $capabilities)) { ?>
                <div class="glass-card">
                    <i class="fa-solid fa-wallet"></i>
                    <h3>Expense Tracking</h3>
                    <p>Track your spending</p>
                </div>
            <?php } ?>
        </section>
    </div>
</body>
</html>
