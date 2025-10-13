-- Add generalized_location field to users table for nearby housing functionality
-- This field will store the user's generalized location (e.g., 'Dhaka North', 'Dhaka South', 'Dhaka East', 'Dhaka West')

ALTER TABLE `users` 
ADD COLUMN `generalized_location` ENUM('Dhaka North', 'Dhaka South', 'Dhaka East', 'Dhaka West') DEFAULT NULL 
AFTER `location`;

-- Create index for better performance when filtering nearby housing
CREATE INDEX IF NOT EXISTS `idx_users_generalized_location` ON `users` (`generalized_location`);

-- Optional: Update existing users with sample generalized locations based on their current location
-- You can run these manually or modify as needed:

-- UPDATE users SET generalized_location = 'Dhaka North' WHERE location LIKE '%Mirpur%' OR location LIKE '%Uttara%' OR location LIKE '%Gulshan%';
-- UPDATE users SET generalized_location = 'Dhaka South' WHERE location LIKE '%Dhanmondi%' OR location LIKE '%Wari%' OR location LIKE '%Old Dhaka%';
-- UPDATE users SET generalized_location = 'Dhaka East' WHERE location LIKE '%Bashundhara%' OR location LIKE '%Badda%' OR location LIKE '%Rampura%';
-- UPDATE users SET generalized_location = 'Dhaka West' WHERE location LIKE '%Mohammadpur%' OR location LIKE '%Shyamoli%' OR location LIKE '%Adabor%';
