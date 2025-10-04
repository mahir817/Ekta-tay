<?php
require_once "db.php";

header('Content-Type: application/json');

try {
    // Insert sample services
    $services = [
        [
            'user_id' => 1,
            'title' => 'Modern Apartment in Dhanmondi',
            'description' => 'Beautiful 3-bedroom apartment in a prime location with modern amenities.',
            'type' => 'housing',
            'price' => 25000,
            'location' => 'Dhanmondi, Dhaka'
        ],
        [
            'user_id' => 1,
            'title' => 'Shared Room in Uttara',
            'description' => 'Comfortable shared room with all basic facilities included.',
            'type' => 'housing',
            'price' => 15000,
            'location' => 'Uttara, Dhaka'
        ],
        [
            'user_id' => 1,
            'title' => 'Studio Apartment in Gulshan',
            'description' => 'Luxury studio apartment with premium location and facilities.',
            'type' => 'housing',
            'price' => 30000,
            'location' => 'Gulshan, Dhaka'
        ]
    ];

    $insertedServices = [];
    
    foreach ($services as $service) {
        $stmt = $pdo->prepare("INSERT INTO services (user_id, title, description, type, price, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $service['user_id'],
            $service['title'],
            $service['description'],
            $service['type'],
            $service['price'],
            $service['location']
        ]);
        
        $serviceId = $pdo->lastInsertId();
        $insertedServices[] = $serviceId;
    }

    // Insert corresponding housing details
    $housingDetails = [
        [
            'service_id' => $insertedServices[0],
            'property_type' => 'apartment',
            'size_sqft' => 1200,
            'floor_no' => '5th',
            'total_floors' => 8,
            'furnished_status' => 'furnished',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'balconies' => 2,
            'rent' => 25000,
            'service_charge' => 2000,
            'advance_deposit' => 50000,
            'available_from' => '2024-02-01',
            'available_for' => 'family',
            'negotiable' => 1,
            'khotiyan' => 'DH-12345',
            'verification_status' => 'verified',
            'status' => 'available'
        ],
        [
            'service_id' => $insertedServices[1],
            'property_type' => 'room',
            'size_sqft' => 200,
            'floor_no' => '2nd',
            'total_floors' => 4,
            'furnished_status' => 'semi-furnished',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'balconies' => 0,
            'rent' => 15000,
            'service_charge' => 1000,
            'advance_deposit' => 30000,
            'available_from' => '2024-01-15',
            'available_for' => 'bachelor',
            'negotiable' => 1,
            'khotiyan' => null,
            'verification_status' => 'pending',
            'status' => 'available'
        ],
        [
            'service_id' => $insertedServices[2],
            'property_type' => 'apartment',
            'size_sqft' => 800,
            'floor_no' => '12th',
            'total_floors' => 15,
            'furnished_status' => 'furnished',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'balconies' => 1,
            'rent' => 30000,
            'service_charge' => 3000,
            'advance_deposit' => 60000,
            'available_from' => '2024-02-15',
            'available_for' => 'any',
            'negotiable' => 0,
            'khotiyan' => 'DH-67890',
            'verification_status' => 'verified',
            'status' => 'available'
        ]
    ];

    foreach ($housingDetails as $housing) {
        $stmt = $pdo->prepare("
            INSERT INTO housing (
                service_id, property_type, size_sqft, floor_no, total_floors, 
                furnished_status, bedrooms, bathrooms, balconies, rent, 
                service_charge, advance_deposit, available_from, available_for, 
                negotiable, khotiyan, verification_status, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $housing['service_id'],
            $housing['property_type'],
            $housing['size_sqft'],
            $housing['floor_no'],
            $housing['total_floors'],
            $housing['furnished_status'],
            $housing['bedrooms'],
            $housing['bathrooms'],
            $housing['balconies'],
            $housing['rent'],
            $housing['service_charge'],
            $housing['advance_deposit'],
            $housing['available_from'],
            $housing['available_for'],
            $housing['negotiable'],
            $housing['khotiyan'],
            $housing['verification_status'],
            $housing['status']
        ]);
    }

    echo json_encode([
        "success" => true, 
        "message" => "Sample housing data inserted successfully",
        "inserted_services" => $insertedServices
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>
