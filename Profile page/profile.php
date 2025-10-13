<?php
session_start();
require_once "../backend/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login Page/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get basic user info for header
$userStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Ekta Tay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
</head>
<body class="bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <nav class="w-64 bg-white/10 backdrop-blur-md border-r border-white/20 min-h-screen fixed left-0 top-0 z-40">
            <div class="p-6">
                <!-- Logo -->
                <div class="flex items-center mb-8">
                    <img src="../images/logo.png" alt="Ekta-tay Logo" class="w-8 h-8 mr-3" />
                    <span class="text-white text-xl font-bold">Ekta-tay</span>
                </div>

                <!-- Navigation Links -->
                <ul class="space-y-2">
                    <li>
                        <a href="../Dashboard/dashboard.php" class="flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-200">
                            <i class="fas fa-home mr-3"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="../Modules/Housing/housing.php" class="flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-200">
                            <i class="fas fa-home mr-3"></i>
                            Housing
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-200">
                            <i class="fas fa-briefcase mr-3"></i>
                            Jobs
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-200">
                            <i class="fas fa-graduation-cap mr-3"></i>
                            Tuition
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-white/80 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-200">
                            <i class="fas fa-utensils mr-3"></i>
                            Food
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center px-4 py-3 text-white bg-white/20 rounded-xl">
                            <i class="fas fa-user mr-3"></i>
                            Profile
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Profile Summary in Sidebar -->
            <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-white/20">
                <div class="flex items-center" id="sidebarProfile">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-bold mr-3">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="text-white font-medium text-sm"><?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?></div>
                        <div class="text-white/60 text-xs">View Profile</div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-8">
            <!-- Header -->
            <header class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">My Profile</h1>
                        <p class="text-white/70">Manage your account settings and preferences</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button onclick="logout()" class="px-4 py-2 bg-red-500/20 text-red-300 rounded-xl hover:bg-red-500/30 transition-all duration-200">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <!-- Profile Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Profile Info -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Profile Header Card -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20" id="profileHeaderCard">
                        <div class="text-center">
                            <div class="relative inline-block mb-4">
                                <div class="w-24 h-24 bg-gradient-to-r from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto" id="profileAvatar">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </div>
                                <button class="absolute bottom-0 right-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm hover:bg-blue-600 transition-colors" onclick="openEditProfileModal()">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <h2 class="text-xl font-bold text-white mb-1" id="profileName">Loading...</h2>
                            <p class="text-white/70 mb-2" id="profileTagline">Loading...</p>
                            <p class="text-white/60 text-sm mb-4" id="profileLocation">Loading...</p>
                            
                            <!-- Capabilities Badges -->
                            <div class="flex flex-wrap gap-2 justify-center mb-4" id="capabilitiesBadges">
                                <!-- Badges will be loaded dynamically -->
                            </div>
                            
                            <button onclick="openEditProfileModal()" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl transition-colors">
                                <i class="fas fa-edit mr-2"></i>
                                Edit Profile
                            </button>
                        </div>
                    </div>

                    <!-- About Section -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">About</h3>
                        <div class="space-y-3" id="aboutSection">
                            <!-- About info will be loaded dynamically -->
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                        <h3 class="text-lg font-semibold text-white mb-4">Quick Stats</h3>
                        <div class="grid grid-cols-2 gap-4" id="quickStats">
                            <!-- Stats will be loaded dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Right Column - Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Navigation Tabs -->
                    <div class="bg-white/10 backdrop-blur-md rounded-xl border border-white/20">
                        <div class="flex overflow-x-auto">
                            <button class="tab-btn active px-6 py-4 text-white border-b-2 border-blue-400 whitespace-nowrap" onclick="showTab('posts')">
                                <i class="fas fa-file-alt mr-2"></i>My Posts
                            </button>
                            <button class="tab-btn px-6 py-4 text-white/70 hover:text-white border-b-2 border-transparent hover:border-white/20 whitespace-nowrap" onclick="showTab('applications')">
                                <i class="fas fa-paper-plane mr-2"></i>Applications
                            </button>
                            <button class="tab-btn px-6 py-4 text-white/70 hover:text-white border-b-2 border-transparent hover:border-white/20 whitespace-nowrap" onclick="showTab('expenses')">
                                <i class="fas fa-wallet mr-2"></i>Expenses
                            </button>
                            <button class="tab-btn px-6 py-4 text-white/70 hover:text-white border-b-2 border-transparent hover:border-white/20 whitespace-nowrap" onclick="showTab('capabilities')">
                                <i class="fas fa-cogs mr-2"></i>Capabilities
                            </button>
                            <button class="tab-btn px-6 py-4 text-white/70 hover:text-white border-b-2 border-transparent hover:border-white/20 whitespace-nowrap" onclick="showTab('settings')">
                                <i class="fas fa-cog mr-2"></i>Settings
                            </button>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div id="tabContent">
                        <!-- My Posts Tab -->
                        <div id="postsTab" class="tab-content active">
                            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-white">My Posts</h3>
                                    <a href="../Post Service Page/post_service.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl transition-colors">
                                        <i class="fas fa-plus mr-2"></i>New Post
                                    </a>
                                </div>
                                
                                <!-- Post Type Tabs -->
                                <div class="flex space-x-4 mb-6">
                                    <button class="post-type-btn active px-4 py-2 bg-blue-500/20 text-blue-300 rounded-lg" onclick="showPostType('all')">All</button>
                                    <button class="post-type-btn px-4 py-2 bg-white/10 text-white/70 rounded-lg hover:bg-white/20" onclick="showPostType('tuition')">Tuition</button>
                                    <button class="post-type-btn px-4 py-2 bg-white/10 text-white/70 rounded-lg hover:bg-white/20" onclick="showPostType('job')">Jobs</button>
                                    <button class="post-type-btn px-4 py-2 bg-white/10 text-white/70 rounded-lg hover:bg-white/20" onclick="showPostType('housing')">Housing</button>
                                    <button class="post-type-btn px-4 py-2 bg-white/10 text-white/70 rounded-lg hover:bg-white/20" onclick="showPostType('food')">Food</button>
                                </div>
                                
                                <div id="postsContainer" class="space-y-4">
                                    <!-- Posts will be loaded dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Applications Tab -->
                        <div id="applicationsTab" class="tab-content hidden">
                            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                                <h3 class="text-xl font-semibold text-white mb-6">My Applications</h3>
                                
                                <!-- Application Status Tabs -->
                                <div class="flex space-x-4 mb-6">
                                    <button class="app-status-btn active px-4 py-2 bg-blue-500/20 text-blue-300 rounded-lg" onclick="showApplicationStatus('all')">All</button>
                                    <button class="app-status-btn px-4 py-2 bg-yellow-500/20 text-yellow-300 rounded-lg" onclick="showApplicationStatus('pending')">Pending</button>
                                    <button class="app-status-btn px-4 py-2 bg-green-500/20 text-green-300 rounded-lg" onclick="showApplicationStatus('accepted')">Accepted</button>
                                    <button class="app-status-btn px-4 py-2 bg-red-500/20 text-red-300 rounded-lg" onclick="showApplicationStatus('rejected')">Rejected</button>
                                </div>
                                
                                <div id="applicationsContainer" class="space-y-4">
                                    <!-- Applications will be loaded dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Expenses Tab -->
                        <div id="expensesTab" class="tab-content hidden">
                            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-semibold text-white">Expense Tracking</h3>
                                    <button onclick="openAddExpenseModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl transition-colors">
                                        <i class="fas fa-plus mr-2"></i>Add Expense
                                    </button>
                                </div>
                                
                                <!-- Expense Summary Cards -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" id="expenseSummary">
                                    <!-- Summary cards will be loaded dynamically -->
                                </div>
                                
                                <!-- Expense Chart and List -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="bg-white/5 rounded-xl p-4">
                                        <h4 class="text-white font-medium mb-4">Expense Categories</h4>
                                        <div id="expenseChart" class="h-64 flex items-center justify-center text-white/60">
                                            <i class="fas fa-chart-pie text-4xl"></i>
                                        </div>
                                    </div>
                                    <div class="bg-white/5 rounded-xl p-4">
                                        <h4 class="text-white font-medium mb-4">Recent Expenses</h4>
                                        <div id="expensesList" class="space-y-2 max-h-64 overflow-y-auto">
                                            <!-- Expenses list will be loaded dynamically -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Capabilities Tab -->
                        <div id="capabilitiesTab" class="tab-content hidden">
                            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                                <h3 class="text-xl font-semibold text-white mb-6">Manage Capabilities</h3>
                                <p class="text-white/70 mb-6">Select the services you want to access on Ekta-tay</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="capabilitiesGrid">
                                    <!-- Capabilities checkboxes will be loaded dynamically -->
                                </div>
                                
                                <div class="mt-6 pt-6 border-t border-white/20">
                                    <button onclick="updateCapabilities()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-xl transition-colors">
                                        <i class="fas fa-save mr-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div id="settingsTab" class="tab-content hidden">
                            <div class="space-y-6">
                                <!-- Account Settings -->
                                <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20">
                                    <h3 class="text-xl font-semibold text-white mb-6">Account Settings</h3>
                                    <div class="space-y-4">
                                        <button class="w-full text-left p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-white font-medium">Change Password</h4>
                                                    <p class="text-white/60 text-sm">Update your account password</p>
                                                </div>
                                                <i class="fas fa-chevron-right text-white/40"></i>
                                            </div>
                                        </button>
                                        <button class="w-full text-left p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-white font-medium">Privacy Settings</h4>
                                                    <p class="text-white/60 text-sm">Manage your privacy preferences</p>
                                                </div>
                                                <i class="fas fa-chevron-right text-white/40"></i>
                                            </div>
                                        </button>
                                        <button class="w-full text-left p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h4 class="text-white font-medium">Notification Settings</h4>
                                                    <p class="text-white/60 text-sm">Configure your notifications</p>
                                                </div>
                                                <i class="fas fa-chevron-right text-white/40"></i>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Danger Zone -->
                                <div class="bg-red-500/10 backdrop-blur-md rounded-xl p-6 border border-red-500/20">
                                    <h3 class="text-xl font-semibold text-red-300 mb-6">Danger Zone</h3>
                                    <button onclick="confirmDeleteAccount()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-xl transition-colors">
                                        <i class="fas fa-trash mr-2"></i>Delete Account
                                    </button>
                                    <p class="text-red-300/70 text-sm mt-2">This action cannot be undone</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 w-full max-w-md">
                <h3 class="text-xl font-semibold text-white mb-6">Edit Profile</h3>
                <form id="editProfileForm" enctype="multipart/form-data">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Profile Picture</label>
                            <input type="file" name="profile_img" accept="image/*" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Name</label>
                            <input type="text" name="name" id="editName" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50" placeholder="Your name">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Phone</label>
                            <input type="text" name="phone" id="editPhone" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50" placeholder="Phone number">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Location</label>
                            <input type="text" name="location" id="editLocation" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50" placeholder="Your location">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Generalized Location</label>
                            <select name="generalized_location" id="editGeneralizedLocation" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                                <option value="">Select area</option>
                                <option value="Dhaka North">Dhaka North</option>
                                <option value="Dhaka South">Dhaka South</option>
                                <option value="Dhaka East">Dhaka East</option>
                                <option value="Dhaka West">Dhaka West</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Gender</label>
                            <select name="gender" id="editGender" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Tagline</label>
                            <input type="text" name="tagline" id="editTagline" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50" placeholder="A short tagline about yourself">
                        </div>
                    </div>
                    <div class="flex space-x-4 mt-6">
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-xl transition-colors">
                            Save Changes
                        </button>
                        <button type="button" onclick="closeEditProfileModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-xl transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 w-full max-w-md">
                <h3 class="text-xl font-semibold text-white mb-6">Add Expense</h3>
                <form id="addExpenseForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Expense Name</label>
                            <input type="text" name="name" required class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50" placeholder="e.g., Rent, Food, Transportation">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Amount (BDT)</label>
                            <input type="number" name="amount" required min="0" step="0.01" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Due Date</label>
                            <input type="date" name="due_date" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                        </div>
                        <div>
                            <label class="block text-white/70 text-sm mb-2">Status</label>
                            <select name="status" class="w-full p-3 bg-white/10 border border-white/20 rounded-xl text-white">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex space-x-4 mt-6">
                        <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded-xl transition-colors">
                            Add Expense
                        </button>
                        <button type="button" onclick="closeAddExpenseModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-xl transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="profile.js"></script>
</body>
</html>
