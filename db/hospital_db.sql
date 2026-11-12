-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 07:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospital_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(100) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `type` enum('in-person','video-call') DEFAULT 'in-person',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `consultation_type` varchar(50) DEFAULT 'Video Call',
  `time_slot` varchar(50) DEFAULT '10:00 AM - 10:30 AM',
  `symptoms` text DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `disease` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_name`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `type`, `notes`, `created_at`, `consultation_type`, `time_slot`, `symptoms`, `doctor_name`, `disease`) VALUES
(3, 'Ali Khan', 1, 1, '2026-10-23', '00:00:00', 'completed', 'in-person', NULL, '2026-10-23 16:05:23', 'Video Call', '10:00 AM - 10:30 AM', 'Phnemonia', NULL, NULL),
(28, NULL, 2, 20, '2026-11-02', '14:00:00', 'completed', 'video-call', 'PRP Session', '2026-11-02 10:07:57', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL),
(29, NULL, 2, 20, '2026-11-02', '11:00:00', 'completed', 'video-call', 'Hydrafacial', '2026-11-02 10:36:33', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL),
(30, NULL, 2, 20, '2026-11-03', '10:30:00', 'completed', 'video-call', 'Hydrafacial', '2026-11-02 10:45:30', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL),
(31, NULL, 2, 21, '2026-11-02', '12:00:00', 'confirmed', 'video-call', 'Phenoumonia', '2026-11-02 13:49:48', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL),
(32, NULL, 2, 20, '2026-11-02', '16:00:00', 'completed', 'video-call', 'PRP', '2026-11-02 14:14:59', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL),
(33, NULL, 2, 20, '2026-11-03', '10:00:00', 'confirmed', 'in-person', '', '2026-11-02 14:20:11', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL),
(34, NULL, 2, 20, '2026-11-02', '16:30:00', 'confirmed', 'video-call', 'PRGF', '2026-11-02 14:21:01', 'Video Call', '10:00 AM - 10:30 AM', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `status` enum('active','pending') DEFAULT 'pending',
  `fee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `user_id`, `specialization`, `qualification`, `experience`, `status`, `fee`) VALUES
(8, 20, 'Dermatologist', 'MBBS', 5, 'pending', 1500.00),
(9, 21, 'Pediatrician', 'MBBS', NULL, 'pending', 500.00),
(10, 22, 'General Physician', 'MBBS', NULL, 'pending', 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `appointment_id`, `patient_id`, `amount`, `payment_method`, `transaction_id`, `status`, `created_at`) VALUES
(1, 3, 1, 1000.00, 'JazzCash', '', 'completed', '2026-10-27 13:47:58'),
(2, 14, 2, 1500.00, 'JazzCash / EasyPaisa', 'TXN-31F0BF2A58', 'completed', '2026-10-27 17:09:47'),
(3, 15, 2, 1500.00, 'JazzCash / EasyPaisa', 'TXN-F2713217FA', 'completed', '2026-10-27 17:10:50'),
(4, 16, 2, 1500.00, 'JazzCash / EasyPaisa', 'TXN-857AFD6BC2', 'completed', '2026-10-27 17:11:15'),
(5, 17, 2, 1500.00, 'JazzCash / EasyPaisa', 'TXN-9CB2F1E721', 'completed', '2026-10-27 17:18:31'),
(6, 18, 2, 1500.00, 'JazzCash / EasyPaisa', 'TXN-B4FA9A0E05', 'completed', '2026-10-27 17:22:46'),
(7, 19, 2, 1500.00, 'JazzCash / EasyPaisa', 'TXN-413D597FAF', 'completed', '2026-10-29 13:31:15'),
(10, 28, 2, 500.00, 'easypaisa', 'MC-20261102-DA1579EF', 'completed', '2026-11-02 10:10:36'),
(11, 30, 2, 500.00, 'jazzcash', 'MC-20261102-01A08C10', 'completed', '2026-11-02 13:12:23'),
(12, 29, 2, 500.00, 'easypaisa', 'MC-20261102-57D2A6CE', 'completed', '2026-11-02 13:13:20'),
(13, 31, 2, 500.00, 'easypaisa', 'MC-20261102-EDAFCB42', 'completed', '2026-11-02 13:53:27'),
(14, 32, 2, 500.00, 'easypaisa', 'MC-20261102-194DE798', 'completed', '2026-11-02 14:16:16'),
(15, 34, 2, 500.00, 'easypaisa', 'MC-20261102-543E41DA', 'completed', '2026-11-02 14:22:49');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `medicines` text NOT NULL,
  `instructions` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `appointment_id`, `doctor_id`, `patient_id`, `diagnosis`, `medicines`, `instructions`, `follow_up_date`, `created_at`) VALUES
(1, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"},{\"name\":\"Cap. Omeprazole 20mg\"},{\"dosage\":\"1-0-0 (Before breakfast)\"},{\"name\":\"Tab. Panadol 500mg\"},{\"dosage\":\"1-1-1 (As needed for fever\\/pain)\"}]', 'Standard Care & Diet: Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.\r\n\r\nAlternative Option: Complete the full course of prescribed antibiotics, avoid strenuous physical activity, apply a light diet containing soups and fresh juices, and return for a follow-up review if symptoms persist.', '2026-10-30', '2026-10-27 14:45:08'),
(2, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"},{\"name\":\"Cap. Omeprazole 20mg\"},{\"dosage\":\"1-0-0 (Before breakfast)\"},{\"name\":\"Tab. Panadol 500mg\"},{\"dosage\":\"1-1-1 (As needed for fever\\/pain)\"}]', 'Standard Care & Diet: Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.\r\n\r\nAlternative Option: Complete the full course of prescribed antibiotics, avoid strenuous physical activity, apply a light diet containing soups and fresh juices, and return for a follow-up review if symptoms persist.', '2026-10-30', '2026-10-27 14:48:39'),
(3, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"},{\"name\":\"Cap. Omeprazole 20mg\"},{\"dosage\":\"1-0-0 (Before breakfast)\"},{\"name\":\"Tab. Panadol 500mg\"},{\"dosage\":\"1-1-1 (As needed for fever\\/pain)\"}]', 'Standard Care & Diet: Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.\r\n\r\nAlternative Option: Complete the full course of prescribed antibiotics, avoid strenuous physical activity, apply a light diet containing soups and fresh juices, and return for a follow-up review if symptoms persist.', '2026-10-30', '2026-10-27 14:48:53'),
(4, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"}]', 'Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.', '2026-11-01', '2026-10-27 14:49:01'),
(5, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"}]', 'Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.', '2026-11-01', '2026-10-27 14:49:14'),
(6, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"}]', 'Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.', '2026-11-01', '2026-10-27 14:53:10'),
(7, 3, 1, 1, 'Acute Upper Respiratory Tract Infection presenting with mild fever, persistent dry cough, sore throat, and generalized body fatigue.', '[{\"name\":\"Tab. Augmentin 625mg\"},{\"dosage\":\"1-0-1 (After meal for 5 days)\"}]', 'Take plenty of warm fluids, maintain proper hydration, take complete physical rest for 3 to 5 days, and avoid oily, spicy, or cold food items.', '2026-11-01', '2026-10-27 14:54:30'),
(10, 28, 20, 2, 'Androgenetic Alopecia (Hair Thinning) - PRP Session #1 Completed', '[{\"name\":\"Cap. Biotin 10mg\",\"dose\":\"1 Capsule\",\"timing\":\"1x Daily After Meals\",\"days\":\"30\"},{\"name\":\"Tab. Paracetamol 500mg\",\"dose\":\"1 Tablet\",\"timing\":\"If pain occurs (SOS)\",\"days\":\"3\"}]', '1. Do not wash hair or use shampoo for 24 hours post-PRP treatment.\r\n\r\n2. Avoid heavy exercise, sweating, and direct sunlight exposure for 48 hours.\r\n\r\n3. Do not apply hair dye, oil, or chemical products on scalp for 3 days.', '2026-12-02', '2026-11-02 10:16:20'),
(11, 30, 20, 2, 'Post-HydraFacial skin exfoliation, deep pore purification, and mild post-procedure transient erythema.', '[{\"name\":\"Gentle Sulfate-Free Cleanser\",\"dose\":\"Pea-sized amount\",\"timing\":\"2x Daily (Morning & Night)\",\"days\":\"14\"},{\"name\":\"Hyaluronic Acid Serum 2%\",\"dose\":\"3-4 Drops\",\"timing\":\"2x Daily After Cleansing\",\"days\":\"30\"}]', '', NULL, '2026-11-02 14:02:51'),
(12, 29, 20, 2, 'Post-HydraFacial skin exfoliation, deep pore purification, and mild post-procedure transient erythema.', '[{\"name\":\"Gentle Sulfate-Free Cleanser\",\"dose\":\"Pea-sized amount\",\"timing\":\"2x Daily (Morning & Night)\",\"days\":\"14\"},{\"name\":\"Hyaluronic Acid Serum 2%\",\"dose\":\"3-4 Drops\",\"timing\":\"2x Daily After Cleansing\",\"days\":\"30\"},{\"name\":\"Broad-Spectrum Mineral Sunscreen SPF 50+\",\"dose\":\"Generous Amount\",\"timing\":\"Every Morning & Reapply 3x Daily\",\"days\":\"30\"}]', '1. Discontinue all active topicals (Retinoids, Tretinoin, Salicylic Acid, Glycolic Acid, Vitamin C) for 3 to 5 days.\r\n2. Avoid heavy workouts, saunas, hot baths, and direct sun exposure for 24-48 hours.\r\n3. Refrain from applying heavy facial makeup for at least 12 to 24 hours.\r\n4. Ensure adequate fluid intake (2.5 to 3 Liters of water daily) for optimal skin barrier hydration.', '2026-11-16', '2026-11-02 14:05:54');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `doctor_id`, `patient_id`, `appointment_id`, `rating`, `review`, `created_at`) VALUES
(1, 20, 2, 28, 5, 'Very professional and polite. Explained the medical condition clearly. Highly recommended doctor. Consultation started right on time.', '2026-11-02 10:19:38'),
(2, 20, 2, 30, 5, 'Explained the medical condition clearly.', '2026-11-02 14:11:59'),
(3, 20, 2, 29, 5, 'Very professional and polite.', '2026-11-02 14:12:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL,
  `status` enum('active','pending','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `specialization` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `role`, `status`, `created_at`, `specialization`) VALUES
(1, 'Admin', 'admin@medicare.com', '0300-0000000', '0192023a7bbd73250516f069df18b500', 'admin', 'active', '2026-10-22 06:12:41', NULL),
(2, 'Maizzy Yousafzai', 'maazyousafzai576@gmail.com', '03149661858', '876258db5df001e3f943dc9c98a0e635', 'patient', 'active', '2026-10-22 06:15:42', NULL),
(20, 'Maaz Ahmad', 'maazahmadyousafzai557@gmail.com', '03361576134', '876258db5df001e3f943dc9c98a0e635', 'doctor', 'active', '2026-11-02 09:51:43', NULL),
(21, 'Arsalan Khan', 'arsalankhan@gmail.com', '03338765212', '$2y$10$IDeBO1uKEwj57TnyHTxClu3ym4z3xiR7xX4vLCohkZ0IgHqlHsgs6', 'doctor', 'active', '2026-11-02 13:43:27', NULL),
(22, 'Mansoor Khan', 'mansoorkhan@gmail.com', '03169575712', '$2y$10$pxnj3V9y.GDV09XsyEUUkeSqBSegi0zbbsNbOYREWgwk3aFRQ7LIi', 'doctor', 'active', '2026-11-02 13:44:33', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `prescriptions_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
