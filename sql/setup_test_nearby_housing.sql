-- Setup test data for nearby housing functionality
-- This will help test the filtering logic

-- First, make sure the users table has the generalized_location field
-- (Run the add_generalized_location_to_users.sql first if not done)

-- Set some test generalized locations for existing users
UPDATE users SET generalized_location = 'Dhaka East' WHERE id = 1;
UPDATE users SET generalized_location = 'Dhaka North' WHERE id = 2;
UPDATE users SET generalized_location = 'Dhaka South' WHERE id = 3;

-- Update existing housing posts with generalized locations to match the sample data
UPDATE housing SET generalized_location = 'Dhaka East' WHERE housing_id IN (1, 3);
UPDATE housing SET generalized_location = 'Dhaka North' WHERE housing_id IN (2, 4);

-- Verify the data
SELECT 'Users with generalized locations:' as info;
SELECT id, name, location, generalized_location FROM users WHERE generalized_location IS NOT NULL;

SELECT 'Housing posts with generalized locations:' as info;
SELECT h.housing_id, s.title, h.location, h.generalized_location, s.user_id
FROM housing h 
JOIN services s ON h.service_id = s.service_id 
WHERE h.generalized_location IS NOT NULL;

-- Check matching logic
SELECT 'Matching housing for user in Dhaka East:' as info;
SELECT h.housing_id, s.title, h.generalized_location, s.user_id
FROM housing h 
JOIN services s ON h.service_id = s.service_id 
WHERE h.generalized_location = 'Dhaka East' AND s.user_id != 1;
