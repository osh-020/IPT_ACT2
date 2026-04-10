-- =====================================================
-- COMPLETE DATABASE SETUP - IPT_ACT2
-- =====================================================
-- This file combines all SQL files into one
-- Includes: Tables, Orders, Users, and Products
-- =====================================================

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Mar 27, 2026 at 06:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- DATABASE: `ipt_act2`
-- =====================================================

-- =====================================================
-- TABLE STRUCTURES
-- =====================================================

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` varchar(15) NOT NULL,
  `civil_status` varchar(50) NOT NULL,
  `mobile_number` varchar(11) NOT NULL,
  `address` text NOT NULL,
  `zip_code` char(4) NOT NULL,
  `username` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `orders`
-- --------------------------------------------------------

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'COD',
  `order_status` varchar(50) NOT NULL DEFAULT 'Pending',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `delivery_address` text NOT NULL,
  `zip_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `order_items`
-- --------------------------------------------------------

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `order_ratings`
-- --------------------------------------------------------

CREATE TABLE `order_ratings` (
  `rating_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- INDEXES FOR DUMPED TABLES
-- =====================================================

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD CONSTRAINT `order_ratings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

-- =====================================================
-- AUTO_INCREMENT FOR DUMPED TABLES
-- =====================================================

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_ratings`
--
ALTER TABLE `order_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

-- =====================================================
-- TABLE STRUCTURE FOR NOTIFICATIONS
-- =====================================================

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

-- =====================================================
-- TABLE STRUCTURE FOR ADMIN NOTIFICATIONS
-- =====================================================

CREATE TABLE `admin_notifications` (
  `notification_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'order',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `is_read` (`is_read`);

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

-- =====================================================
-- SAMPLE DATA - USERS
-- =====================================================
-- Sample User Accounts for Testing
-- Passwords are hashed using bcrypt

-- User 1: John Doe
-- Password: Test1234
INSERT INTO `users` (`full_name`, `email`, `username`, `password`, `age`, `gender`, `civil_status`, `mobile_number`, `address`, `zip_code`) 
VALUES ('John Doe', 'john@example.com', 'johndoe', '$2y$10$mZLa8Pys1QsGPv.D4N0vPeI4KXR/BHLbXb9d5Z8y7q5k4K3J2K9Jm', 28, 'Male', 'Single', '09123456789', '123 Main Street, Lingayen, Pangasinan', '2401');

-- User 2: Maria Santos
-- Password: Password123
INSERT INTO `users` (`full_name`, `email`, `username`, `password`, `age`, `gender`, `civil_status`, `mobile_number`, `address`, `zip_code`) 
VALUES ('Maria Santos', 'maria@example.com', 'mariasantos', '$2y$10$d5PYk8XvZqR2B3n7L9mXxOK4j5L6K8Q2W9E5R7T2U4V6W8X9Y0Z1b', 32, 'Female', 'Married', '09198765432', '456 Oak Avenue, Dagupan, Pangasinan', '2400');
-- =====================================================
-- SAMPLE DATA - PRODUCTS
-- =====================================================
-- Insert 15 Sample PC Components for Testing

INSERT INTO `products` (`name`, `category`, `brand`, `price`, `stock`, `description`, `keywords`, `image`) VALUES
('Intel Core i5-13600K', 'CPU', 'Intel', 365.99, 25, 'High-performance CPU with 14 cores. Perfect for gaming and content creation. Base clock 3.5 GHz, boost up to 5.1 GHz.', 'processor, cpu, intel, gaming, core i5', 'cpu_intel_i5.jpg'),
('AMD Ryzen 7 5700X', 'CPU', 'AMD', 299.99, 18, 'Excellent multi-core performance with 8 cores and 16 threads. Great for streaming and video editing.', 'processor, cpu, amd, ryzen, multithreading', 'cpu_amd_ryzen.jpg'),
('Kingston Fury DDR4 32GB', 'RAM', 'Kingston', 129.99, 40, '32GB (2x16GB) DDR4 3600MHz memory kit. High-speed performance with excellent stability.', 'memory, ram, ddr4, 32gb, kingston', 'ram_kingston_32gb.jpg'),
('Corsair Vengeance RGB DDR4 16GB', 'RAM', 'Corsair', 69.99, 55, '16GB (2x8GB) DDR4 3200MHz with RGB lighting. Great gaming RAM with dynamic color effects.', 'memory, ram, ddr4, rgb, corsair', 'ram_corsair_rgb.jpg'),
('Samsung 870 EVO 1TB SSD', 'Storage', 'Samsung', 89.99, 30, '1TB SATA SSD with 560MB/s read speed. Reliable and fast solid-state drive for boot and games.', 'ssd, storage, 1tb, samsung, sata', 'ssd_samsung_870.jpg'),
('WD Blue 2TB 3.5" HDD', 'Storage', 'Western Digital', 49.99, 35, '2TB mechanical hard drive. Affordable storage for backup and large file libraries.', 'hard drive, hdd, 2tb, western digital, mechanical', 'hdd_wd_blue.jpg'),
('RTX 4060 Ti 8GB', 'Graphics Card', 'NVIDIA', 449.99, 12, 'NVIDIA RTX 4060 Ti with 8GB GDDR6 memory. Excellent 1440p gaming performance with ray tracing.', 'graphics card, gpu, nvidia, rtx, gaming', 'gpu_rtx_4060ti.jpg'),
('Gigabyte B760 Aorus Elite', 'Motherboard', 'Gigabyte', 189.99, 20, 'ATX motherboard for Intel 13th gen. Features PCIe 5.0, DDR5 support, and robust power delivery.', 'motherboard, b760, gigabyte, intel, atx', 'mobo_gigabyte_b760.jpg'),
('ASUS ROG Strix B550-F', 'Motherboard', 'ASUS', 179.99, 22, 'Premium B550 motherboard for AMD Ryzen. WiFi 6E, PCIe 4.0, and excellent VRM design.', 'motherboard, b550, asus, amd, rog', 'mobo_asus_b550.jpg'),
('Corsair RM750x 750W', 'Power Supply', 'Corsair', 89.99, 28, '750W 80+ Gold certified power supply. Fully modular with silent operation and 10-year warranty.', 'power supply, psu, 750w, corsair, modular', 'psu_corsair_750w.jpg'),
('NZXT Kraken X63 280mm', 'CPU Cooler', 'NZXT', 139.99, 16, '280mm liquid CPU cooler with RGB pump and case lighting. Excellent cooling performance for high-end CPUs.', 'cooler, aio, liquid, 280mm, nzxt', 'cooler_nzxt_kraken.jpg'),
('Noctua NH-D15 Chromax', 'CPU Cooler', 'Noctua', 99.99, 19, 'Dual-tower air cooler with excellent performance. Quiet operation with high-quality fans.', 'cooler, air, dual tower, noctua, quiet', 'cooler_noctua_nh_d15.jpg'),
('Lian Li LANCOOL 205 Mesh', 'Case', 'Lian Li', 49.99, 33, 'Compact micro-ATX case with mesh front panel. Good airflow and cable management for small builds.', 'case, micro atx, lian li, mesh, compact', 'case_lian_li_205.jpg'),
('NZXT H510 Flow', 'Case', 'NZXT', 99.99, 24, 'Mid-tower ATX case with excellent cable management and airflow. Tempered glass side panel.', 'case, atx, nzxt, tempered glass, airflow', 'case_nzxt_h510.jpg'),
('Crucial P3 1TB NVMe SSD', 'Storage', 'Crucial', 59.99, 42, '1TB NVMe M.2 SSD with 5100MB/s read speed. Budget-friendly fast storage for gaming and OS.', 'ssd, nvme, m.2, 1tb, crucial, fast', 'ssd_crucial_p3.jpg');

-- =====================================================
-- END OF COMPLETE DATABASE SETUP
-- =====================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
