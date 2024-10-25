-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2024 at 07:28 PM
-- Server version: 10.5.25-MariaDB-cll-lve
-- PHP Version: 8.1.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evef9533_TodoList`
--

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` varchar(5) NOT NULL COMMENT 'Auto increment\r\n',
  `list_id` varchar(5) NOT NULL COMMENT 'Foreign key to todo_lists',
  `title` varchar(100) NOT NULL COMMENT 'Task tile\r\n',
  `description` varchar(200) DEFAULT NULL COMMENT 'Task description',
  `due_date` date DEFAULT NULL COMMENT 'Task due date',
  `completed` tinyint(1) NOT NULL COMMENT 'Task status (completed or not)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '	Date and time of creation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `list_id`, `title`, `description`, `due_date`, `completed`, `created_at`) VALUES
('A0001', 'D0007', 'Makanan', 'daging, buah, sayur', '2024-10-23', 0, '2024-10-25 12:18:23'),
('A0002', 'D0007', 'Keperluan kuliah', 'Pensil Pen', '2024-10-29', 1, '2024-10-25 12:18:42'),
('A0003', 'D0008', 'Tugas 1', 'Membuat Website', '2024-10-22', 0, '2024-10-25 12:18:58');

-- --------------------------------------------------------

--
-- Table structure for table `todo`
--

CREATE TABLE `todo` (
  `id` varchar(5) NOT NULL COMMENT 'Auto increment',
  `user_id` varchar(5) NOT NULL COMMENT 'Foreign key to users table',
  `title` varchar(100) NOT NULL COMMENT 'Title of the todo list'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `todo`
--

INSERT INTO `todo` (`id`, `user_id`, `title`) VALUES
('D0003', 'U0001', 'testing123'),
('D0004', 'U0001', '123'),
('D0005', 'U0001', '123'),
('D0007', 'U0002', 'Groceries'),
('D0008', 'U0002', 'Work'),
('D0009', 'U0002', 'Education');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
('U0001', '1234', '1234@123.com', '$2y$10$4LFBytydfw3T3KcA2bdo4uVF1e4ldeawi4NG9IFIbr9R8jktSpfzS'),
('U0002', 'El Kecepatan', 'raden@watkins.com', '$2y$10$5jaoBGotTdbEe.eD6zr9xeHZ.E3zmVM9BuhrR/m/ni7Yg8Bjd1Kx6');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `list_id` (`list_id`);

--
-- Indexes for table `todo`
--
ALTER TABLE `todo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`list_id`) REFERENCES `todo` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
