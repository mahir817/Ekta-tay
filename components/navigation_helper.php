<?php
// Navigation helper functions for universal header

// Function to get current page name for active navigation
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

// Function to get current directory for navigation
function getCurrentDir() {
    return basename(dirname($_SERVER['PHP_SELF']));
}

// Function to check if user has specific capability
function hasCapability($capability, $userCapabilities) {
    return in_array($capability, $userCapabilities);
}

// Function to get navigation items based on user capabilities
function getNavigationItems($capabilities) {
    $capabilityMap = [
        'find_room' => ['name' => 'Housing', 'icon' => 'fas fa-home', 'url' => '../Modules/Housing/housing_listings.php'],
        'offer_room' => ['name' => 'Housing', 'icon' => 'fas fa-home', 'url' => '../Modules/Housing/housing_listings.php'],
        'find_job' => ['name' => 'Jobs', 'icon' => 'fas fa-briefcase', 'url' => '../Modules/Jobs/jobs_listings.php'],
        'post_job' => ['name' => 'Jobs', 'icon' => 'fas fa-briefcase', 'url' => '../Modules/Jobs/jobs_listings.php'],
        'find_tutor' => ['name' => 'Tuition', 'icon' => 'fas fa-graduation-cap', 'url' => '../Modules/Tuitions/tuitions_listings.php'],
        'offer_tuition' => ['name' => 'Tuition', 'icon' => 'fas fa-graduation-cap', 'url' => '../Modules/Tuitions/tuitions_listings.php'],
        'food_service' => ['name' => 'Services', 'icon' => 'fas fa-wrench', 'url' => '../Modules/Services/services_listings.php']
    ];
    
    $navigationItems = [];
    $addedItems = [];
    
    foreach ($capabilities as $capability) {
        if (isset($capabilityMap[$capability])) {
            $item = $capabilityMap[$capability];
            if (!in_array($item['name'], $addedItems)) {
                $navigationItems[] = $item;
                $addedItems[] = $item['name'];
            }
        }
    }
    
    return $navigationItems;
}
?>
