-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 12:33 PM
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
-- Database: `furriendly_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `original_event_id` int(11) DEFAULT NULL,
  `host_username` varchar(50) NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) NOT NULL,
  `services` text NOT NULL,
  `description` text NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `position` enum('Government Official','Veterinarian','Furr Parent') NOT NULL,
  `id_upload` varchar(255) DEFAULT NULL,
  `valid_id` varchar(255) NOT NULL,
  `permit` varchar(255) NOT NULL,
  `veterinarians_list` varchar(255) NOT NULL,
  `safety_plan` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `appeal_message` text DEFAULT NULL,
  `appealed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `original_event_id`, `host_username`, `event_title`, `event_date`, `start_time`, `end_time`, `location`, `services`, `description`, `full_name`, `contact_number`, `position`, `id_upload`, `valid_id`, `permit`, `veterinarians_list`, `safety_plan`, `status`, `created_at`, `completed_at`, `rejection_reason`, `appeal_message`, `appealed_at`) VALUES
(1, NULL, 'richh87', 'asdasdasd', '2025-11-01', '05:34:00', '22:30:00', 'Sigma Boy House', '[\"Grooming\"]', 'What the sigma', 'Omsim Skibidi', '090909090909', 'Furr Parent', NULL, 'valid_id_1761323431_68fba9a7a1b88.jpg', 'permit_1761323431_68fba9a7a1e4f.jpg', 'vets_1761323431_68fba9a7a2e6c.pdf', 'safety_1761323431_68fba9a7a3139.jpg', 'completed', '2025-10-24 16:30:31', NULL, NULL, NULL, NULL),
(2, NULL, 'richh69', 'Sigma Boy', '2025-10-31', '11:56:00', '13:58:00', 'Sigma Boy House', '[\"Grooming\"]', 'Sigma', 'Omsim Skibidi', '090909090909', 'Furr Parent', NULL, 'valid_id_1761710267_690190bb2a122.jpg', 'permit_1761710267_690190bb2a3d0.jpg', 'vets_1761710267_690190bb2a671.pdf', 'safety_1761710267_690190bb2a8e3.jpg', 'completed', '2025-10-29 03:57:47', '2025-10-29 15:53:52', NULL, NULL, NULL),
(3, NULL, 'richh87', 'Brr Brr Patapim', '2025-10-30', '20:31:00', '21:32:00', 'Lirili Larila', '[\"Grooming\"]', 'asd', 'Omsim Skibidi', '090909090909', 'Furr Parent', NULL, 'valid_id_1761741145_6902095957fc6.jpg', 'permit_1761741145_6902095958264.jpg', 'vets_1761741145_690209595850d.pdf', 'safety_1761741145_690209595877b.jpg', 'completed', '2025-10-29 12:32:25', NULL, NULL, NULL, NULL),
(4, NULL, 'richh69', 'asd asd', '2025-10-31', '20:57:00', '22:58:00', 'Lirili Larila', '[\"GROOMING\"]', 'asd', 'Omsim Skibidi', '090909090909', 'Furr Parent', NULL, 'valid_id_1761753514_690239aaa9c28.jpg', 'permit_1761753514_690239aaaa027.jpg', 'vets_1761753514_690239aaaa328.pdf', 'safety_1761753514_690239aaaa6e7.jpg', 'completed', '2025-10-29 15:58:34', '2025-10-29 16:00:15', NULL, NULL, NULL),
(5, NULL, 'richh69', 'sleeping glass', '2025-10-31', '01:07:00', '03:09:00', 'Sigma Boy House', '[\"Grooming\"]', 'asd', 'Omsim Skibidi', '090909090909', 'Furr Parent', NULL, 'valid_id_1761753994_69023b8a9ac45.jpg', 'permit_1761753994_69023b8a9af0b.jpg', 'vets_1761753994_69023b8a9b8da.pdf', 'safety_1761753994_69023b8aa32ae.jpg', 'completed', '2025-10-29 16:06:34', '2025-10-29 16:15:50', NULL, NULL, NULL),
(7, NULL, 'richh87', '123123', '2025-11-06', '09:17:00', '10:19:00', 'Lirili Larila', '[\"Grooming\"]', 'dddd', 'Omsim Skibidi', '090909090909', 'Furr Parent', NULL, 'valid_id_1762132719_690802efea525.jpg', 'permit_1762132719_690802efeb0ad.jpg', 'vets_1762132719_690802efeb388.pdf', 'safety_1762132719_690802efeb9c5.jpg', 'completed', '2025-11-03 01:18:39', '2025-11-03 01:59:02', NULL, NULL, NULL),
(8, NULL, 'richh87', 'Grooming and Dental', '2025-11-13', '05:00:00', '15:00:00', 'Balanga', '[\"Grooming\",\"Dental\"]', 'Real', 'Omsim Skibidi', '961 266 3496', 'Furr Parent', NULL, 'valid_id_1762895898_6913a81a15b55.jpg', 'permit_1762895898_6913a81a15ea4.jpg', 'vets_1762895898_6913a81a1612c.pdf', 'safety_1762895898_6913a81a16391.jpg', 'completed', '2025-11-11 12:58:40', '2025-11-13 07:15:18', NULL, 'asd', '2025-11-11 21:18:18'),
(9, NULL, 'richh87', 'diddyblud', '2025-11-28', '13:36:00', '14:37:00', 'island', '[\"Grooming\",\"Feeding\",\"Sigma\"]', 'what is he doing', 'Omsim Skibidi', '961 266 3496', 'Furr Parent', NULL, 'valid_id_1762925848_69141d180a3b6.jpg', 'permit_1762925848_69141d180a8d6.jpg', 'vets_1762925848_69141d180abaa.pdf', 'safety_1762925848_69141d180b5e3.jpg', 'rejected', '2025-11-12 05:37:28', NULL, 'Sigma', NULL, NULL),
(10, NULL, 'richh87', 'RAA', '2025-11-27', '16:00:00', '17:00:00', 'Doon', '[\"Grooming\",\"Vaccination\"]', 'Sigma', 'Dog Guy', '961 266 3496', 'Furr Parent', NULL, 'valid_id_1763020795_69158ffbb85c0.jpg', 'permit_1763020795_69158ffbb88b3.jpg', 'vets_1763020795_69158ffbb8bfc.pdf', 'safety_1763020795_69158ffbb8ff7.jpg', 'approved', '2025-11-13 07:59:55', NULL, NULL, NULL, NULL),
(11, NULL, 'richh87', 'Gorming and Vaxin', '2025-11-15', '08:19:00', '09:20:00', 'Dyan', '[\"Grooming\",\"Vaccination\"]', 'Lol', 'Dog Guy', '961 266 3496', 'Veterinarian', NULL, 'valid_id_1763079627_691675cb6d350.jpg', 'permit_1763079627_691675cb6e9a8.jpg', 'vets_1763079627_691675cb6ef79.pdf', 'safety_1763079627_691675cb6f25d.jpg', 'completed', '2025-11-14 00:20:27', '2025-11-14 00:22:19', NULL, NULL, NULL),
(12, NULL, 'richh69', 'AAAAAAAAA', '2025-11-15', '08:43:00', '09:41:00', 'Balanga', '[\"Grooming\",\"Vaccination\"]', 'asd', 'asd', '090 909 0909', 'Veterinarian', NULL, 'valid_id_1763080842_69167a8a7c1b2.jpg', 'permit_1763080842_69167a8a7c588.jpg', 'vets_1763080842_69167a8a7c8c4.pdf', 'safety_1763080842_69167a8a7e709.jpg', 'completed', '2025-11-14 00:40:42', '2025-11-14 01:45:41', NULL, NULL, NULL),
(13, NULL, 'richh69', 'Notif', '2025-11-15', '09:50:00', '10:50:00', 'Lirili Larila', '[\"Grooming\",\"Vaccination\"]', 'asd', 'asd', '090 909 0909', 'Veterinarian', NULL, 'valid_id_1763084799_691689ffa0d58.jpg', 'permit_1763084799_691689ffa1045.jpg', 'vets_1763084799_691689ffa1303.pdf', 'safety_1763084799_691689ffa165e.jpg', 'approved', '2025-11-14 01:46:39', NULL, NULL, NULL, NULL),
(14, NULL, 'sigmaboy', 'Training', '2025-11-15', '08:30:00', '14:34:00', 'Lirili Larila', '[\"Training\",\"Feeding\"]', 'asd', 'asd', '961 266 3496', 'Furr Parent', NULL, 'valid_id_1763094776_6916b0f8da0b7.jpg', 'permit_1763094776_6916b0f8da3a2.jpg', 'vets_1763094776_6916b0f8da690.pdf', 'safety_1763094776_6916b0f8da98a.jpg', 'approved', '2025-11-14 04:32:56', NULL, NULL, NULL, NULL),
(15, NULL, 'Riki3', 'Dog Training', '2025-11-15', '16:48:00', '18:47:00', 'Balanga City', '[\"Training\",\"Feeding\"]', 'Training Dogs', 'Ricky II Mangalindan', '956 632 5004', 'Furr Parent', NULL, 'valid_id_1763106640_6916df50863c9.png', 'permit_1763106640_6916df5086c3b.jpg', 'vets_1763106640_6916df508760f.pdf', 'safety_1763106640_6916df5087f1c.png', 'completed', '2025-11-14 07:50:40', '2025-11-14 07:55:59', NULL, NULL, NULL),
(16, NULL, 'richh87', 'REject', '2025-11-15', '06:01:00', '17:00:00', 'Doon', '[\"Grooming\",\"Vaccination\"]', 'asd', 'Ricky II Mangalindan', '612 663 4966', 'Furr Parent', NULL, 'valid_id_1763107281_6916e1d1befd9.png', 'permit_1763107281_6916e1d1bf726.png', 'vets_1763107281_6916e1d1bfd28.pdf', 'safety_1763107281_6916e1d1c0288.png', 'rejected', '2025-11-14 08:01:21', NULL, 'Rejected', NULL, NULL),
(17, NULL, 'richh87', 'REject', '2025-11-15', '06:01:00', '17:00:00', 'Doon', '[\"Grooming\",\"Vaccination\"]', 'asd', 'Ricky II Mangalindan', '612 663 4966', 'Furr Parent', NULL, 'valid_id_1763107370_6916e22abbcdd.png', 'permit_1763107370_6916e22abc110.png', 'vets_1763107370_6916e22abc2d5.pdf', 'safety_1763107370_6916e22abc43e.png', 'approved', '2025-11-14 08:02:50', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event_joins`
--

CREATE TABLE `event_joins` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `join_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('joined','completed','canceled') DEFAULT 'joined'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('joined','completed','canceled') DEFAULT 'joined',
  `canceled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_participants`
--

INSERT INTO `event_participants` (`id`, `event_id`, `username`, `pet_id`, `joined_at`, `status`, `canceled_at`, `completed_at`) VALUES
(1, 1, 'richh69', 3, '2025-10-29 10:19:16', 'completed', '2025-10-29 10:19:16', NULL),
(2, 1, 'admin123', 4, '2025-10-29 12:21:20', 'completed', NULL, NULL),
(3, 3, 'richh69', 3, '2025-10-29 12:33:31', 'completed', NULL, NULL),
(4, 3, 'admin123', 4, '2025-10-29 12:33:41', 'completed', NULL, NULL),
(5, 2, 'richh69', 3, '2025-10-29 13:25:27', 'completed', NULL, '2025-10-29 15:53:52'),
(6, 2, 'admin123', 4, '2025-10-29 13:25:59', 'completed', NULL, '2025-10-29 15:53:52'),
(7, 2, 'richh87', NULL, '2025-10-29 15:17:10', 'completed', NULL, '2025-10-29 15:53:52'),
(8, 4, 'richh87', NULL, '2025-10-29 15:59:30', 'completed', NULL, '2025-10-29 16:00:15'),
(9, 4, 'admin123', 4, '2025-10-29 15:59:48', 'completed', NULL, '2025-10-29 16:00:15'),
(10, 5, 'admin123', 4, '2025-10-29 16:07:01', 'completed', NULL, '2025-10-29 16:15:50'),
(11, 5, 'richh87', NULL, '2025-10-29 16:09:47', 'completed', NULL, '2025-10-29 16:15:50'),
(14, 7, 'richh69', 8, '2025-11-03 01:50:25', 'completed', NULL, '2025-11-03 01:59:02'),
(15, 8, 'richh69', 8, '2025-11-13 05:41:28', 'completed', NULL, '2025-11-13 07:15:18'),
(16, 8, 'richh69', 3, '2025-11-13 06:33:20', 'completed', NULL, '2025-11-13 07:15:18'),
(17, 10, 'richh69', 8, '2025-11-13 09:13:25', 'joined', NULL, NULL),
(18, 10, 'richh69', 3, '2025-11-13 09:13:32', 'joined', NULL, NULL),
(19, 11, 'richh69', 3, '2025-11-14 00:20:58', 'completed', NULL, '2025-11-14 00:22:19'),
(20, 12, 'admin123', 4, '2025-11-14 00:41:08', 'completed', NULL, '2025-11-14 01:45:41'),
(21, 12, 'richh87', 9, '2025-11-14 00:44:54', 'completed', NULL, '2025-11-14 01:45:41'),
(22, 12, 'richh69', 3, '2025-11-14 01:33:02', 'completed', NULL, '2025-11-14 01:45:41'),
(23, 13, 'richh87', 9, '2025-11-14 01:47:12', 'joined', NULL, NULL),
(24, 13, 'richh87', 10, '2025-11-14 02:03:06', 'joined', NULL, NULL),
(25, 13, 'richh69', 3, '2025-11-14 02:07:27', 'joined', NULL, NULL),
(26, 13, 'richh69', 8, '2025-11-14 02:09:25', 'joined', NULL, NULL),
(27, 13, 'admin123', 4, '2025-11-14 02:14:21', 'joined', NULL, NULL),
(28, 13, 'sigmaboy', 11, '2025-11-14 02:26:17', 'joined', NULL, NULL),
(29, 13, 'richh87', 12, '2025-11-14 02:30:39', 'joined', NULL, NULL),
(30, 13, 'sigmaboy', 13, '2025-11-14 04:10:34', 'joined', NULL, NULL),
(31, 14, 'admin123', 4, '2025-11-14 04:34:39', 'joined', NULL, NULL),
(32, 15, 'richh87', 10, '2025-11-14 07:52:53', 'completed', NULL, '2025-11-14 07:55:59'),
(33, 13, 'richh87', 15, '2025-11-14 07:57:48', 'joined', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medical_documents`
--

CREATE TABLE `medical_documents` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `uploaded_by` varchar(50) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `record_type` enum('vaccination','medication','treatment','checkup','other') DEFAULT 'other',
  `is_host_upload` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_documents`
--

INSERT INTO `medical_documents` (`id`, `pet_id`, `event_id`, `uploaded_by`, `original_name`, `file_path`, `file_size`, `description`, `uploaded_at`, `record_type`, `is_host_upload`) VALUES
(4, 9, NULL, 'richh87', 'print oms.pdf', 'uploads/medical_documents/med_doc_1763010365_6915673d77271.pdf', 2216804, 'Sheesh', '2025-11-13 05:06:05', 'other', 0),
(5, 9, NULL, 'richh87', 'print oms.pdf', 'uploads/medical_documents/med_doc_1763073859_69165f43ba792.pdf', 2216804, 'you would not believe ur eyes', '2025-11-13 22:44:19', 'other', 0),
(6, 8, 8, 'richh87', 'print oms.pdf', 'uploads/pet_records/record_1763075830_691666f6617a0.pdf', 2216804, '', '2025-11-13 23:17:10', 'vaccination', 1),
(7, 3, 11, 'richh87', 'print oms.pdf', 'uploads/pet_records/record_1763079762_691676524f6d5.pdf', 2216804, 'asdasd', '2025-11-14 00:22:42', 'vaccination', 1),
(8, 11, NULL, 'sigmaboy', 'print oms.pdf', 'uploads/medical_documents/med_doc_1763087161_691693391c137.pdf', 2216804, '', '2025-11-14 02:26:01', 'other', 0),
(9, 3, 11, 'richh87', 'ACTIVE-ISKO-FOR-TDP.pdf', 'uploads/pet_records/record_1763088935_69169a27a0ae4.pdf', 165142, '', '2025-11-14 02:55:35', 'treatment', 1),
(11, 10, 15, 'Riki3', 'Final Peer Evaluation.pdf', 'uploads/pet_records/record_1763106999_6916e0b761d49.pdf', 90106, 'asd', '2025-11-14 07:56:39', 'vaccination', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `notification_type` enum('event_reminder','event_update','event_canceled') DEFAULT 'event_reminder',
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `event_id`, `username`, `notification_type`, `message`, `sent_at`, `is_read`) VALUES
(1, 13, 'sigmaboy', '', 'You have successfully joined the event \'Notif\' scheduled for 2025-11-15 at 09:50:00 in Lirili Larila.', '2025-11-14 02:26:17', 0),
(2, 13, 'richh87', '', 'You have added another pet to the event \'\' scheduled for  at  in . You now have 3 pets attending this event.', '2025-11-14 02:30:39', 0),
(3, 13, 'richh87', '', 'You have successfully joined the event \'Notif\' scheduled for 2025-11-15 at 09:50:00 in Lirili Larila.', '2025-11-14 02:30:39', 0),
(4, 13, 'sigmaboy', '', 'You have added another pet to the event \'\' scheduled for  at  in . You now have 2 pets attending this event.', '2025-11-14 04:10:34', 0),
(5, 13, 'sigmaboy', '', 'You have successfully joined the event \'Notif\' scheduled for 2025-11-15 at 09:50:00 in Lirili Larila.', '2025-11-14 04:10:34', 0),
(6, 14, 'admin123', '', 'You have successfully joined the event \'Training\' scheduled for 2025-11-15 at 08:30:00 in Lirili Larila.', '2025-11-14 04:34:39', 0),
(7, 15, 'richh87', '', 'You have successfully joined the event \'Dog Training\' scheduled for 2025-11-15 at 16:48:00 in Balanga City.', '2025-11-14 07:52:53', 0),
(8, 13, 'richh87', '', 'You have added another pet to the event \'\' scheduled for  at  in . You now have 4 pets attending this event.', '2025-11-14 07:57:48', 0),
(9, 13, 'richh87', '', 'You have successfully joined the event \'Notif\' scheduled for 2025-11-15 at 09:50:00 in Lirili Larila.', '2025-11-14 07:57:48', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `pet_species` varchar(100) DEFAULT NULL,
  `pet_breed` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `pet_gender` enum('Male','Female') DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `vaccines` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`vaccines`)),
  `medical_condition` varchar(255) DEFAULT NULL,
  `pet_profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pet_age` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `username`, `pet_name`, `pet_species`, `pet_breed`, `birthdate`, `pet_gender`, `medical_history`, `vaccines`, `medical_condition`, `pet_profile_pic`, `created_at`, `pet_age`) VALUES
(1, 'maru123', '67 DOG', '67', 'Six Seven', '2023-02-16', 'Male', '', NULL, '', NULL, '2025-10-29 18:12:27', 2),
(2, 'kakai', '67 DOG', '67', 'Six Seven', '2023-02-16', 'Male', '', NULL, '', NULL, '2025-10-29 18:12:27', 2),
(3, 'richh69', '67 DOG', '67', 'Six Seven', '2023-02-16', 'Male', '\nMedical record uploaded by event host (richh87) for event #11: treatment on 2025-11-14 03:55:35', NULL, '', NULL, '2025-10-29 18:12:27', 2),
(4, 'admin123', '67 DOG', '67', 'Six Seven', '2023-02-16', 'Male', '', NULL, '', '../uploads/pets/pet_1763094823_24875ce6b00928ca.png', '2025-10-29 18:12:27', 2),
(8, 'richh69', '67 DOG', '67', 'Six Seven', '2023-02-16', 'Male', '', NULL, '', NULL, '2025-11-03 01:20:42', 2),
(9, 'richh87', 'Sigma Boy Dog', '67', 'Six Seven', '2023-01-18', 'Male', '', NULL, '', '../uploads/pets/pet_1763088695_54eff51b564d08bc.webp', '2025-11-11 21:49:54', 2),
(10, 'richh87', '67 DOG', '67', 'Six Seven', '2023-02-16', 'Male', '\nMedical record uploaded by event host (Riki3) for event #15: vaccination - asd on 2025-11-14 08:56:39', NULL, '', NULL, '2025-11-14 01:59:34', 2),
(11, 'sigmaboy', 'Madam', 'Gem', 'Peerless', '2023-02-16', 'Female', '', NULL, '', '../uploads/pets/pet_1763093402_452162e9806ea564.webp', '2025-11-14 02:21:10', 2),
(12, 'richh87', 'Hater', 'Hater Dog', 'Hate', '2020-02-01', 'Male', '', NULL, '', '../uploads/pets/pet_1763088571_a7236d88a79bac26.webp', '2025-11-14 02:30:30', 5),
(13, 'sigmaboy', 'Cutie', 'Cute', 'Lamao', '2022-03-18', 'Female', '', NULL, '', '../uploads/pets/pet_1763093428_d4cf76b069a16d1a.jpg', '2025-11-14 04:10:28', 3),
(15, 'richh87', 'Ricky', 'Dog', 'Shih Tzu', '2021-02-17', 'Male', '- Vaccine: Anti-Rabies (Date: 2025-11-07)', '[{\"type\":\"Anti-Rabies\",\"date\":\"2025-11-07\"}]', '', '../uploads/pets/pet_1763106922_832a4dc432d58a8f.png', '2025-11-14 07:54:35', 4);

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `username`, `content`, `image_path`, `event_id`, `created_at`, `updated_at`) VALUES
(1, 'richh69', 'I love dogs', '/furriendly-main/uploads/posts/post_1763078833_3638216ebd45e1ab.jpg', 10, '2025-11-14 00:07:13', '2025-11-14 00:07:13'),
(2, 'richh87', 'I love sigmsa', '/furriendly-main/uploads/posts/post_1763088749_3e8ac36086c4eca5.webp', 13, '2025-11-14 02:52:29', '2025-11-14 02:52:29'),
(3, 'Riki3', 'I love my dog', '/furriendly-main/uploads/posts/post_1763106423_b845a02074f7a967.jpg', NULL, '2025-11-14 07:47:03', '2025-11-14 07:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `name`, `email`, `phone`, `bio`, `profile_pic`, `is_admin`) VALUES
(1, 'admin', '$2y$10$SK5OAU5lU4tNYJTqQvuWP.sBHgnH9VMN3IKQ3qVhROiK7tIXztCOa', '2025-10-09 13:54:49', NULL, NULL, NULL, NULL, NULL, 1),
(2, 'rikitu123', '$2y$10$V7oAinCqtgRfP4ZYTfhqye03r1MXvk2mGw5fWkygbfrK2dZ0lkIhy', '2025-10-09 14:02:13', NULL, NULL, NULL, NULL, NULL, 0),
(3, 'baby kakai', '$2y$10$tjEvhlOx8AzWbV.xQzGW.OtAgSycA.UliDJFr8bF9P.E94Pw/yiki', '2025-10-09 14:18:44', NULL, NULL, NULL, NULL, NULL, 0),
(4, 'maru123', '$2y$10$KwQ41sTSYox02R99Sm02wOvLBL68vEvPj.XZGIhliWq7vE1qi3/kO', '2025-10-09 14:22:38', 'Baby Kakai', 'rikitumangalindan@gmail.com', NULL, 'kyut', '../uploads/1760021544_Screenshot 2025-10-09 224157.png', 0),
(5, 'rikitukokiii', '$2y$10$GDhHHYkt80Z6wBXIP0NabOsBktfozJ4iXuuWFQOf694KQhjOzmyyC', '2025-10-10 02:26:36', NULL, NULL, NULL, NULL, NULL, 0),
(6, 'kakai', '$2y$10$JBjNRuW1F3D7on6Q89wDk.947TlQMYFCcOjjlYnis3KbwAIEQuMp.', '2025-10-10 02:31:25', 'Baby ni Kakai', 'moymoymangalindan13@gmail.com', NULL, 'meowmeowmeow', NULL, 0),
(9, 'richh87', '$2y$10$oHxGCF..tAn5RwRN68.L9eWz6Mw./Lv0PHS1OaWVIKWJWEiPVU1/m', '2025-10-24 15:00:34', '67richh', 'nakalimutanpassword@gmail.com', '', 'BRUH', '/furriendly-main/uploads/profile_1763092911_0368aabc62481964.png', 0),
(10, 'richh69', '$2y$10$/7wfO.mozfQ5ShonLXB5sOGUM0ve8d2FRKqbzOQPbZeTE26od1mA2', '2025-10-29 03:55:32', 'richh69', '', NULL, '', '../uploads/1761738738_2x2.jpg', 0),
(11, 'admin123', '$2y$10$oCSg1R4FgrFz1UkIPPRcVumJk/qiytoMkdVzr56/cYbDoBVHQBpmC', '2025-10-29 03:59:14', 'admin123', 'bruhlmao1273@gmail.com', '', 'bro.....', '/furriendly-main/uploads/profile_1763092889_202e90eb14808286.jpg', 1),
(12, 'sigmaboy', '$2y$10$iV8QHrbkHJgeZHBYKX5lK.zE2jJ7BiSfXTzcwNF5AgcFcHKCoDLNu', '2025-11-14 02:18:23', 'SigmaBoy', 'lol@gmail.com', '', 'yeah\r\n', '/furriendly-main/uploads/profile_1763093474_19b925a3623a5007.png', 0),
(13, 'riki', '$2y$10$M4C39gp2sWVsc1YQ9F2xPedffltSleTO866NVMmA.cWqcvV/1GFQ6', '2025-11-14 06:23:00', 'Rikitu', 'rikitumangalindan@gmail.com', '', 'sigmaboy', '/furriendly-main/uploads/profile_1763101426_6b5fe54d0a9402ed.png', 0),
(14, 'rikitu', '$2y$10$Z9JiPx3GNCVebBBAgjRd6uVXOPs1LXcj9dfWVvDORfuATcpWk8Dnm', '2025-11-14 06:27:10', 'Rikitu', 'rikitumangalindan@gmail.com', '', '', '/furriendly-main/uploads/profile_1763101781_2db374b9573b967b.png', 0),
(15, 'Riki3', '$2y$10$6QlgOw8mWKSq8pl8hZiRV.pbHqwtRxJjlBRfrt4HO23Fj6s0LrVY2', '2025-11-14 07:45:56', 'Riki', 'moymoymangalindan13@gmail.com', '', 'I love dogs', '/furriendly-main/uploads/profile_1763106391_57396131203a2c2a.png', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `host_username` (`host_username`);

--
-- Indexes for table `event_joins`
--
ALTER TABLE `event_joins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `username` (`username`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Indexes for table `medical_documents`
--
ALTER TABLE `medical_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `event_joins`
--
ALTER TABLE `event_joins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `medical_documents`
--
ALTER TABLE `medical_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`host_username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `event_joins`
--
ALTER TABLE `event_joins`
  ADD CONSTRAINT `event_joins_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_joins_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_joins_ibfk_3` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participants_ibfk_3` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_participants_ibfk_4` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medical_documents`
--
ALTER TABLE `medical_documents`
  ADD CONSTRAINT `medical_documents_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_documents_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
