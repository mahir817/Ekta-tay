-- Housing Module Database Updates
-- This file contains SQL updates to implement the complete housing workflow

-- 1. Update housing_applications table to match workflow requirements
ALTER TABLE `housing_applications` 
ADD COLUMN `owner_id` int(11) NOT NULL AFTER `housing_id`,
MODIFY COLUMN `status` enum('pending','shortlisted','accepted','rejected','withdrawn') DEFAULT 'pending',
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

-- Add foreign key constraint for owner_id
ALTER TABLE `housing_applications`
ADD CONSTRAINT `housing_applications_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- 2. Update housing_tenants table to match workflow requirements  
ALTER TABLE `housing_tenants`
ADD COLUMN `owner_id` int(11) NOT NULL AFTER `housing_id`,
MODIFY COLUMN `active` tinyint(1) DEFAULT 1,
ADD COLUMN `status` enum('active','inactive','terminated') DEFAULT 'active',
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp();

-- Add foreign key constraint for owner_id in housing_tenants
ALTER TABLE `housing_tenants`
ADD CONSTRAINT `housing_tenants_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- 3. Update housing table to add availability status
ALTER TABLE `housing`
ADD COLUMN `availability` enum('available','pending','occupied') DEFAULT 'available';

-- Update existing records to set availability based on current status
UPDATE `housing` SET `availability` = `status` WHERE `status` IN ('available', 'pending', 'occupied');

-- 4. Create indexes for better performance
CREATE INDEX `idx_housing_applications_status` ON `housing_applications` (`status`);
CREATE INDEX `idx_housing_applications_owner` ON `housing_applications` (`owner_id`);
CREATE INDEX `idx_housing_applications_applicant` ON `housing_applications` (`applicant_id`);
CREATE INDEX `idx_housing_tenants_status` ON `housing_tenants` (`status`);
CREATE INDEX `idx_housing_availability` ON `housing` (`availability`);

-- 5. Add sample data for testing (optional - remove in production)
-- This will be handled by the backend PHP files

COMMIT;
