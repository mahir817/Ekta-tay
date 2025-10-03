-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 03, 2025 at 05:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ekta_tay`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `action_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` enum('ban_user','resolve_dispute','generate_report') NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `capabilities`
--

CREATE TABLE `capabilities` (
  `id` int(11) NOT NULL,
  `capability_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `capabilities`
--

INSERT INTO `capabilities` (`id`, `capability_name`) VALUES
(7, 'expense_tracking'),
(1, 'find_job'),
(5, 'find_room'),
(4, 'find_tutor'),
(8, 'food_service'),
(6, 'offer_room'),
(3, 'offer_tuition'),
(2, 'post_job');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `housing_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housing`
--

CREATE TABLE `housing` (
  `housing_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `property_type` enum('apartment','room','commercial','mixed') DEFAULT 'apartment',
  `size_sqft` int(11) DEFAULT NULL,
  `floor_no` varchar(20) DEFAULT NULL,
  `total_floors` int(11) DEFAULT NULL,
  `furnished_status` enum('furnished','semi-furnished','unfurnished') DEFAULT 'unfurnished',
  `parking_spaces` int(11) DEFAULT 0,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `balconies` int(11) DEFAULT 0,
  `rent` decimal(12,2) NOT NULL,
  `service_charge` decimal(12,2) DEFAULT 0.00,
  `advance_deposit` decimal(12,2) DEFAULT 0.00,
  `available_from` date DEFAULT NULL,
  `available_for` enum('family','bachelor','any') DEFAULT 'any',
  `negotiable` tinyint(1) DEFAULT 0,
  `property_condition` varchar(255) DEFAULT 'N/A',
  `verification_doc` varchar(100) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `khotiyan` varchar(100) DEFAULT NULL,
  `status` enum('available','pending','occupied') DEFAULT 'available',
  `furnished` enum('furnished','unfurnished') DEFAULT 'unfurnished'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housing_applications`
--

CREATE TABLE `housing_applications` (
  `application_id` int(11) NOT NULL,
  `housing_id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected','cancelled') DEFAULT 'pending',
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housing_features`
--

CREATE TABLE `housing_features` (
  `feature_id` int(11) NOT NULL,
  `housing_id` int(11) NOT NULL,
  `feature_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housing_images`
--

CREATE TABLE `housing_images` (
  `image_id` int(11) NOT NULL,
  `housing_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housing_tenants`
--

CREATE TABLE `housing_tenants` (
  `tenant_id` int(11) NOT NULL,
  `housing_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `housing_terms`
--

CREATE TABLE `housing_terms` (
  `term_id` int(11) NOT NULL,
  `housing_id` int(11) NOT NULL,
  `term_name` varchar(100) NOT NULL,
  `term_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentors`
--

CREATE TABLE `mentors` (
  `mentor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expertise` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentorship_requests`
--

CREATE TABLE `mentorship_requests` (
  `request_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rent_split`
--

CREATE TABLE `rent_split` (
  `id` int(11) NOT NULL,
  `housing_id` int(11) DEFAULT NULL,
  `roommate_id` int(11) DEFAULT NULL,
  `share_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('tuition','job','housing','food') NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(15) NOT NULL,
  `location` varchar(50) NOT NULL,
  `gender` enum('male','female') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `phone`, `location`, `gender`) VALUES
(1, 'Demo User', 'demo@example.com', '$2y$10$wHhG6p1xZ1V6lF7lA4L4ZOqf9D3HTxYyXQ9x9xZ6D9Q4yZ7u5o6e.', '2025-09-13 14:36:32', '', '', 'male'),
(2, 'Mahir Ahmed', 'mahir101748@gmail.com', '$2y$10$cSzWLtXFkYN8YkcRU4F/GeJr61XM7PNf9NsyeqvEP2oFVKTzFPXBO', '2025-09-13 15:03:48', '01999373432', 'Dhaka North', 'male'),
(3, 'random xyz', 'randomxyz@mail.com', '$2y$10$u2//P1cE4PvITtDC7NpaQO89VX/A3rLYhEUaT7b/9NbCPYOeHL2pG', '2025-09-13 15:38:01', '01999372222', 'Dhaka South', 'female'),
(4, 'Rayhan Ahmed', 'rayhan01748@gmail.com', '$2y$10$tTIb7T822Q4ERsdoA/f0T.Y7p7n8rdHdjopw37pDoCcuevpnYm.fe', '2025-09-14 05:51:50', '01999373435', 'Dhaka South', 'male'),
(5, 'John cina', 'johncena@gmail.com', '$2y$10$NoHgA.F.N3B.FcvtS5VBmeWn6ajXiEDFodFEboYQG1YTLFYpMa.u.', '2025-09-17 17:57:02', '01238924723', 'Dhaka South', 'male'),
(6, 'asdf jkl', 'asdf@gmail.com', '$2y$10$aL1Xuey/hW4MwSJEnPJ/N.FfKKXbZpSwqkhv9cLOHH7tW8C2smFdO', '2025-09-17 18:02:26', '1234567891011', 'Dhaka East', 'male'),
(7, 'new user', 'newuser@gmail.com', '$2y$10$sJvzj7aXKCV7y90qPjMo8.Pp0Jte16dPEzBEhaseb5DxKEv6AmX4q', '2025-09-17 18:10:13', '01345435432', 'Dhaka West', 'male'),
(8, 'new user2', 'newuser2@gmail.com', '$2y$10$LtkrjeWWh4eLIYfLdMvBUOwQ2pnVLdDd.GV9p5sWcLjD3M31YtLcy', '2025-09-17 18:12:40', '01346435432', 'Dhaka South', 'male'),
(9, 'new user3', 'newuser3@gmail.com', '$2y$10$LNqtpPPSo7zA4nV52dtpS.00Kk8Cb38Wti7mU9pOaQK2b59AD13T2', '2025-09-17 18:28:41', '0199937445982', 'Dhaka South', 'female'),
(10, 'new user3', 'user3@gmail.com', '$2y$10$X8VpPf4atF9DCUGHA4kgauVHL16Oy.BZpTHoaPcPJA/RgsijBtKqi', '2025-09-17 18:29:29', '0199932948793', 'Dhaka South', 'male'),
(11, 'new user4', 'newuser4@gmail.com', '$2y$10$QF55iS9LT.Wp8vnxdjzhAOPnXyL8xOFIGNGbVBQ8mZLpwiEJcv6MK', '2025-09-17 18:33:09', '01923232323', 'Dhaka North', 'male'),
(12, 'abcd efgh', 'abcd@gmail.com', '$2y$10$2iWU49d019i04BbDo9XPyOi9v/.sMGPgFuq1GnpYObKgRJGnhmq5G', '2025-09-19 14:11:00', '017118394722', 'Dhaka South', 'male'),
(13, 'jon snow', 'snow@mail.com', '$2y$10$nRDKLpcyAcABBy24ovSzsec5CDiPFQBHiZjIjFaVfecIUE3FmAQei', '2025-09-19 14:19:07', '01293745289', 'Dhaka North', 'male'),
(14, 'Walter White', 'white@mail.com', '$2y$10$XPpC262XlhnbJ4.ZuZydKOsS3ikjTZ/GLMv0ualHEBqKMKpnVLzTK', '2025-09-19 15:08:09', '12345678900', 'Dhaka North', 'male'),
(15, 'new user7', 'user7@mail.com', '$2y$10$peHMDI49xqi.N2Frg6L5H.U00BmKC1pfHCIrsJVwv8SPyjOc/1cei', '2025-09-19 15:19:06', '12345678901', 'Dhaka North', 'female'),
(16, 'new user8', 'user8@mail.com', '$2y$10$/6.DWV/UTUeS2eV/Bj47V.Tz4OCZkmknpRGpdkVJleM9LdDzaOxdS', '2025-09-19 15:21:13', '12345689638', 'Dhaka North', 'female'),
(17, 'new user9', 'new9@mail.com', '$2y$10$EnARDqLr6RhvcxC5v62JBu1L4lfXSl30EC9cfQsaddwJjEk9zNdpW', '2025-09-19 15:44:38', '23638738298', 'Dhaka West', 'female'),
(18, 'new user11', 'user11@mail.com', '$2y$10$7FRLtkNCwBVFFXqQIxu0XuJKjjiBrSbrqPFZonpyVkB6J2jdJ9Y.i', '2025-09-19 18:04:39', '127138928739', 'Dhaka East', 'female'),
(19, 'test id', 'test@gmail.com', '$2y$10$VN4joOgEbEZtz9Sir8Qqv.wv8TonOxds9x32DY6ksf.0ceazzMeiK', '2025-10-03 14:18:46', '01277383773', 'Dhaka East', 'male');

-- --------------------------------------------------------

--
-- Table structure for table `user_capabilities`
--

CREATE TABLE `user_capabilities` (
  `user_id` int(11) NOT NULL,
  `capability_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_capabilities`
--

INSERT INTO `user_capabilities` (`user_id`, `capability_id`) VALUES
(2, 1),
(2, 4),
(2, 5),
(3, 2),
(3, 3),
(3, 6),
(4, 1),
(4, 4),
(4, 5),
(5, 1),
(5, 4),
(5, 5),
(6, 1),
(6, 4),
(6, 5),
(7, 1),
(7, 4),
(7, 5),
(8, 1),
(8, 4),
(8, 5),
(14, 1),
(14, 4),
(14, 8),
(15, 1),
(15, 3),
(15, 4),
(17, 1),
(17, 4),
(17, 8),
(18, 1),
(18, 2),
(18, 3),
(18, 4),
(18, 5),
(18, 6),
(18, 7),
(18, 8),
(19, 1),
(19, 2),
(19, 3),
(19, 4),
(19, 5),
(19, 6),
(19, 7),
(19, 8);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `capabilities`
--
ALTER TABLE `capabilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `capability_name` (`capability_name`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `housing_id` (`housing_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `housing`
--
ALTER TABLE `housing`
  ADD PRIMARY KEY (`housing_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `housing_applications`
--
ALTER TABLE `housing_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `housing_id` (`housing_id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `housing_features`
--
ALTER TABLE `housing_features`
  ADD PRIMARY KEY (`feature_id`),
  ADD KEY `housing_id` (`housing_id`);

--
-- Indexes for table `housing_images`
--
ALTER TABLE `housing_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `housing_id` (`housing_id`);

--
-- Indexes for table `housing_tenants`
--
ALTER TABLE `housing_tenants`
  ADD PRIMARY KEY (`tenant_id`),
  ADD KEY `housing_id` (`housing_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `housing_terms`
--
ALTER TABLE `housing_terms`
  ADD PRIMARY KEY (`term_id`),
  ADD KEY `housing_id` (`housing_id`);

--
-- Indexes for table `mentors`
--
ALTER TABLE `mentors`
  ADD PRIMARY KEY (`mentor_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `rent_split`
--
ALTER TABLE `rent_split`
  ADD PRIMARY KEY (`id`),
  ADD KEY `housing_id` (`housing_id`),
  ADD KEY `roommate_id` (`roommate_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_capabilities`
--
ALTER TABLE `user_capabilities`
  ADD PRIMARY KEY (`user_id`,`capability_id`),
  ADD KEY `capability_id` (`capability_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `capabilities`
--
ALTER TABLE `capabilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housing`
--
ALTER TABLE `housing`
  MODIFY `housing_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housing_applications`
--
ALTER TABLE `housing_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housing_features`
--
ALTER TABLE `housing_features`
  MODIFY `feature_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housing_images`
--
ALTER TABLE `housing_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housing_tenants`
--
ALTER TABLE `housing_tenants`
  MODIFY `tenant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housing_terms`
--
ALTER TABLE `housing_terms`
  MODIFY `term_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `mentor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rent_split`
--
ALTER TABLE `rent_split`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `housing`
--
ALTER TABLE `housing`
  ADD CONSTRAINT `housing_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `housing_applications`
--
ALTER TABLE `housing_applications`
  ADD CONSTRAINT `housing_applications_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `housing_applications_ibfk_2` FOREIGN KEY (`applicant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `housing_features`
--
ALTER TABLE `housing_features`
  ADD CONSTRAINT `housing_features_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`) ON DELETE CASCADE;

--
-- Constraints for table `housing_images`
--
ALTER TABLE `housing_images`
  ADD CONSTRAINT `housing_images_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`) ON DELETE CASCADE;

--
-- Constraints for table `housing_tenants`
--
ALTER TABLE `housing_tenants`
  ADD CONSTRAINT `housing_tenants_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `housing_tenants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `housing_terms`
--
ALTER TABLE `housing_terms`
  ADD CONSTRAINT `housing_terms_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`) ON DELETE CASCADE;

--
-- Constraints for table `mentors`
--
ALTER TABLE `mentors`
  ADD CONSTRAINT `mentors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorship_requests`
--
ALTER TABLE `mentorship_requests`
  ADD CONSTRAINT `mentorship_requests_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`mentor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentorship_requests_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE CASCADE;

--
-- Constraints for table `rent_split`
--
ALTER TABLE `rent_split`
  ADD CONSTRAINT `rent_split_ibfk_1` FOREIGN KEY (`housing_id`) REFERENCES `housing` (`housing_id`),
  ADD CONSTRAINT `rent_split_ibfk_2` FOREIGN KEY (`roommate_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_capabilities`
--
ALTER TABLE `user_capabilities`
  ADD CONSTRAINT `user_capabilities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_capabilities_ibfk_2` FOREIGN KEY (`capability_id`) REFERENCES `capabilities` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
