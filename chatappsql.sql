-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 05:21 PM
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
-- Database: `chatappsql`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `Id` int(11) NOT NULL,
  `BlockerId` int(11) NOT NULL COMMENT 'ID của người đi chặn',
  `BlockedId` int(11) NOT NULL COMMENT 'ID của người bị chặn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commentemotes`
--

CREATE TABLE `commentemotes` (
  `CommentEmoteId` int(11) NOT NULL,
  `CommentId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `EmoteId` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `CommentId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Content` text NOT NULL,
  `ParentCommentId` int(11) DEFAULT NULL,
  `CommentedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`CommentId`, `PostId`, `UserId`, `Content`, `ParentCommentId`, `CommentedAt`) VALUES
(3, 24, 10, 'quá hay', NULL, '2025-11-14 21:31:32'),
(6, 28, 33, 'Ai chả mệc :))', NULL, '2025-11-15 23:10:13');

-- --------------------------------------------------------

--
-- Table structure for table `emojiusage`
--

CREATE TABLE `emojiusage` (
  `Emoji` varchar(10) NOT NULL,
  `UsageCount` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emotes`
--

CREATE TABLE `emotes` (
  `EmoteId` int(11) NOT NULL,
  `EmoteName` varchar(50) NOT NULL,
  `EmoteUnicode` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `emotes`
--

INSERT INTO `emotes` (`EmoteId`, `EmoteName`, `EmoteUnicode`) VALUES
(1, 'Like', '👍'),
(2, 'Haha', '😂'),
(3, 'Wow', '😮'),
(4, 'Sad', '😢'),
(5, 'Angry', '😡');

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `FriendId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `FriendUserId` int(11) NOT NULL,
  `IsConfirmed` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`FriendId`, `UserId`, `FriendUserId`, `IsConfirmed`) VALUES
(2, 11, 10, 1),
(3, 20, 14, 0),
(4, 20, 11, 1),
(5, 20, 10, 1),
(6, 24, 10, 1),
(9, 13, 25, 1),
(50, 13, 10, 1),
(51, 10, 25, 1),
(52, 11, 25, 1),
(56, 27, 29, 1),
(57, 27, 25, 1),
(58, 27, 9, 0),
(59, 27, 14, 0),
(60, 27, 18, 0),
(61, 27, 20, 0),
(64, 29, 28, 1),
(65, 27, 28, 1),
(68, 10, 28, 1),
(69, 32, 25, 1),
(70, 33, 32, 1),
(71, 33, 25, 1),
(74, 34, 33, 0),
(75, 34, 32, 1),
(76, 34, 31, 0),
(77, 34, 25, 1),
(78, 34, 10, 0),
(79, 37, 25, 1),
(80, 37, 35, 1),
(81, 37, 34, 0),
(82, 35, 33, 0),
(83, 35, 25, 1),
(84, 32, 35, 1),
(85, 32, 37, 1);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `GroupId` int(11) NOT NULL,
  `GroupName` varchar(100) NOT NULL,
  `AvatarPath` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện của nhóm',
  `CreatedBy` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`GroupId`, `GroupName`, `AvatarPath`, `CreatedBy`, `CreatedAt`) VALUES
(1, 'Friends', NULL, 9, '2025-11-16 02:21:49'),
(3, 'Test2', 'uploads/group_avatars/group_avatar_3_1763313968.jpg', 32, '2025-11-16 02:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `GroupMemberId` int(11) NOT NULL,
  `GroupId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `JoinedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `Role` enum('Admin','Member') NOT NULL DEFAULT 'Member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`GroupMemberId`, `GroupId`, `UserId`, `JoinedAt`, `Role`) VALUES
(4, 3, 32, '2025-11-16 09:27:55', 'Admin'),
(13, 3, 33, '2025-11-16 23:07:13', 'Member'),
(18, 3, 34, '2025-11-17 00:15:09', 'Member'),
(19, 3, 25, '2025-11-17 00:25:52', 'Member');

-- --------------------------------------------------------

--
-- Table structure for table `hidden_feeds`
--

CREATE TABLE `hidden_feeds` (
  `Id` int(11) NOT NULL,
  `HiderId` int(11) NOT NULL COMMENT 'ID của người đi ẩn',
  `HiddenId` int(11) NOT NULL COMMENT 'ID của người bị ẩn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messageemotes`
--

CREATE TABLE `messageemotes` (
  `MessageEmoteId` int(11) NOT NULL,
  `MessageId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `EmoteId` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `MessageId` int(11) NOT NULL,
  `SenderId` int(11) NOT NULL,
  `ReceiverId` int(11) DEFAULT NULL,
  `GroupId` int(11) DEFAULT NULL,
  `Content` text NOT NULL,
  `SentAt` datetime NOT NULL DEFAULT current_timestamp(),
  `MessageType` varchar(10) NOT NULL DEFAULT 'text',
  `IsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `IsRead` tinyint(1) NOT NULL DEFAULT 0,
  `IsPinned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`MessageId`, `SenderId`, `ReceiverId`, `GroupId`, `Content`, `SentAt`, `MessageType`, `IsDeleted`, `IsRead`, `IsPinned`) VALUES
(9, 11, 13, NULL, '[IMG]uploads/messages/u_11/img_6915806a1c60b.jpg', '2025-11-13 13:53:30', 'text', 0, 0, 0),
(10, 11, 13, NULL, 'hello', '2025-11-13 13:53:45', 'text', 0, 0, 0),
(11, 11, 13, NULL, '[IMG]uploads/messages/u_11/img_691594bc8d44f.jpg', '2025-11-13 15:20:12', 'text', 0, 0, 0),
(12, 11, 13, NULL, '[IMG]uploads/messages/u_11/img_6915954867eac.jpg', '2025-11-13 15:22:32', 'text', 0, 0, 0),
(13, 24, 13, NULL, 'alo', '2025-11-14 11:22:52', 'text', 0, 0, 0),
(14, 24, 13, NULL, 'call me a king', '2025-11-14 11:22:55', 'text', 0, 0, 0),
(15, 24, 13, NULL, 'call me a demon', '2025-11-14 11:23:00', 'text', 0, 0, 0),
(16, 13, 24, NULL, '[IMG]uploads/messages/u_13/img_6916aee687c03.jpg', '2025-11-14 11:24:06', 'text', 0, 0, 0),
(17, 13, 24, NULL, 'whoareu', '2025-11-14 11:24:21', 'text', 0, 0, 0),
(18, 25, 24, NULL, 'Gà', '2025-11-14 12:11:53', 'text', 0, 0, 0),
(19, 13, 25, NULL, '😎', '2025-11-14 14:32:19', 'text', 0, 1, 0),
(20, 13, 25, NULL, '🤯', '2025-11-14 14:32:22', 'text', 0, 1, 0),
(21, 13, 25, NULL, '❤️', '2025-11-14 14:32:27', 'text', 0, 1, 0),
(22, 13, 25, NULL, '[IMG]uploads/messages/u_13/img_6916db18bb3ce.jpg', '2025-11-14 14:32:40', 'text', 0, 1, 0),
(23, 25, 13, NULL, 'ayo gay', '2025-11-14 14:41:09', 'text', 0, 0, 0),
(24, 25, 32, NULL, 'Làm cái đường phân cách trên tin nhắn mới nhất đề là tin nhắn chưa đọc', '2025-11-15 22:44:05', 'text', 0, 1, 0),
(25, 32, 25, NULL, 'Oke', '2025-11-15 22:57:33', 'text', 0, 1, 0),
(26, 32, 25, NULL, 'Đang làm', '2025-11-15 22:57:36', 'text', 0, 1, 0),
(27, 32, 25, NULL, 'để qua lại bên kia', '2025-11-15 22:57:40', 'text', 0, 1, 0),
(28, 32, 25, NULL, 'Test thử', '2025-11-15 22:57:44', 'text', 0, 1, 0),
(29, 32, 25, NULL, 'rồi xem sao', '2025-11-15 22:57:47', 'text', 0, 1, 0),
(30, 32, 25, NULL, 'oke', '2025-11-15 22:57:52', 'text', 0, 1, 0),
(31, 25, 32, NULL, 'Test', '2025-11-15 23:02:00', 'text', 0, 1, 0),
(32, 25, 32, NULL, 'Test', '2025-11-15 23:02:01', 'text', 0, 1, 0),
(33, 25, 32, NULL, 'Test', '2025-11-15 23:02:01', 'text', 0, 1, 0),
(34, 25, 32, NULL, 'Test', '2025-11-15 23:02:02', 'text', 0, 1, 0),
(35, 25, 32, NULL, 'Test', '2025-11-15 23:02:02', 'text', 0, 1, 0),
(36, 25, 32, NULL, 'Test', '2025-11-15 23:02:03', 'text', 0, 1, 0),
(37, 25, 32, NULL, 'Test', '2025-11-15 23:02:04', 'text', 0, 1, 0),
(38, 25, 32, NULL, 'Test', '2025-11-15 23:02:04', 'text', 0, 1, 0),
(39, 25, 32, NULL, 'Test', '2025-11-15 23:02:05', 'text', 0, 1, 0),
(40, 32, 25, NULL, 'Test', '2025-11-15 23:05:30', 'text', 0, 1, 0),
(41, 32, 25, NULL, 'Test', '2025-11-15 23:05:31', 'text', 0, 1, 0),
(42, 32, 25, NULL, 'Test', '2025-11-15 23:05:32', 'text', 0, 1, 0),
(43, 25, 32, NULL, 'Hehe', '2025-11-15 23:06:33', 'text', 0, 1, 0),
(44, 25, 32, NULL, 'he', '2025-11-15 23:06:34', 'text', 0, 1, 0),
(45, 32, 25, NULL, 'Heiz', '2025-11-15 23:06:49', 'text', 0, 1, 0),
(46, 32, 25, NULL, 'Heiz', '2025-11-15 23:06:51', 'text', 0, 1, 0),
(47, 32, 33, NULL, 'Hi', '2025-11-15 23:08:20', 'text', 0, 1, 0),
(48, 32, 33, NULL, 'Hello', '2025-11-15 23:08:22', 'text', 0, 1, 0),
(49, 32, 33, NULL, 'Bye', '2025-11-15 23:08:23', 'text', 0, 1, 0),
(50, 25, 33, NULL, 'Wuh', '2025-11-15 23:08:37', 'text', 0, 1, 0),
(51, 33, 32, NULL, 'Hi', '2025-11-15 23:09:04', 'text', 0, 1, 0),
(52, 33, 25, NULL, 'Sup', '2025-11-15 23:09:08', 'text', 0, 1, 0),
(53, 25, 32, NULL, 'W', '2025-11-15 23:10:46', 'text', 0, 1, 0),
(54, 25, 33, NULL, 'hezi', '2025-11-15 23:10:51', 'text', 0, 1, 0),
(55, 33, 25, NULL, 'Hmm', '2025-11-15 23:11:15', 'text', 0, 1, 0),
(56, 33, 32, NULL, 'Hmm', '2025-11-15 23:11:19', 'text', 0, 1, 0),
(57, 32, 33, NULL, 'WE', '2025-11-15 23:11:55', 'text', 0, 1, 0),
(58, 32, 33, NULL, 'R', '2025-11-15 23:11:56', 'text', 0, 1, 0),
(59, 32, 33, NULL, 'Venom', '2025-11-15 23:11:59', 'text', 0, 1, 0),
(60, 32, 25, NULL, 'Kul', '2025-11-15 23:12:02', 'text', 0, 1, 0),
(61, 33, 25, NULL, 'UE', '2025-11-15 23:12:29', 'text', 0, 1, 0),
(62, 33, 32, NULL, 'Hey', '2025-11-15 23:12:32', 'text', 0, 1, 0),
(63, 32, 25, NULL, 'wẻwẻ', '2025-11-15 23:12:53', 'text', 0, 1, 0),
(64, 32, 25, NULL, '[IMG]uploads/messages/u_32/img_6918ab6968583.jpg', '2025-11-15 23:33:45', 'text', 0, 1, 0),
(65, 32, 33, NULL, '[IMG]uploads/messages/u_32/img_6918ab7982ebb.jpg', '2025-11-15 23:34:01', 'text', 0, 1, 0),
(66, 32, 25, NULL, 'Hello', '2025-11-16 00:10:51', 'text', 0, 1, 0),
(67, 32, 33, NULL, 's', '2025-11-16 00:10:55', 'text', 0, 1, 0),
(68, 32, 33, NULL, '[IMG]uploads/messages/u_32/img_6918b422b49cd.jpg', '2025-11-16 00:10:58', 'text', 0, 1, 0),
(69, 32, 33, NULL, '😍', '2025-11-16 00:11:01', 'text', 0, 1, 0),
(70, 32, 25, NULL, 'Hi', '2025-11-16 00:17:37', 'text', 0, 1, 0),
(71, 32, 33, NULL, 'ÁD', '2025-11-16 00:17:43', 'text', 0, 1, 0),
(72, 32, NULL, 1, 's', '2025-11-16 00:17:45', 'text', 0, 0, 0),
(73, 32, 25, NULL, 'Hi', '2025-11-16 00:23:49', 'text', 0, 1, 0),
(74, 32, NULL, 1, 'Hi', '2025-11-16 00:23:56', 'text', 0, 0, 0),
(75, 32, NULL, 1, 'Hello', '2025-11-16 00:31:30', 'text', 0, 0, 0),
(76, 32, NULL, 1, 'Hi', '2025-11-16 00:36:30', 'text', 0, 0, 0),
(77, 32, NULL, 1, '/', '2025-11-16 00:37:38', 'text', 0, 0, 0),
(78, 33, NULL, 1, '[IMG]uploads/messages/u_33/img_6918bd19e01ab.jpg', '2025-11-16 00:49:13', 'text', 0, 0, 0),
(84, 32, 25, NULL, 'Heh', '2025-11-16 09:43:39', 'text', 0, 1, 0),
(85, 32, 33, NULL, '😎', '2025-11-16 09:43:48', 'text', 0, 0, 0),
(87, 32, NULL, 3, 'hi', '2025-11-16 16:35:11', 'text', 0, 0, 0),
(92, 25, NULL, 3, '😂', '2025-11-16 16:37:22', 'text', 0, 0, 0),
(100, 32, NULL, 3, 'Heiz', '2025-11-16 16:46:37', 'text', 0, 0, 0),
(114, 32, NULL, 3, '[IMG]uploads/messages/u_32/img_691a070997dd5.jpg', '2025-11-17 00:16:57', 'text', 0, 0, 0),
(119, 34, NULL, 3, 'Hello', '2025-11-17 00:26:41', 'text', 0, 0, 0),
(120, 32, 25, NULL, 'duma', '2025-11-19 22:51:36', 'text', 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_otp`
--

CREATE TABLE `password_reset_otp` (
  `OtpId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Otp` varchar(6) NOT NULL,
  `IsUsed` tinyint(1) NOT NULL DEFAULT 0,
  `ExpiresAt` datetime NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_otp`
--

INSERT INTO `password_reset_otp` (`OtpId`, `UserId`, `Email`, `Otp`, `IsUsed`, `ExpiresAt`, `CreatedAt`) VALUES
(5, 10, 'deadordie159@gmail.com', '242668', 1, '2025-11-15 10:35:37', '2025-11-15 16:20:37');

-- --------------------------------------------------------

--
-- Table structure for table `postemotes`
--

CREATE TABLE `postemotes` (
  `PostEmoteId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `EmoteId` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `postemotes`
--

INSERT INTO `postemotes` (`PostEmoteId`, `PostId`, `UserId`, `EmoteId`, `CreatedAt`) VALUES
(3, 24, 10, 1, '2025-11-14 21:31:28'),
(4, 25, 10, 5, '2025-11-14 21:32:06'),
(5, 28, 32, 5, '2025-11-15 22:40:16'),
(6, 28, 33, 2, '2025-11-15 23:10:17'),
(7, 29, 25, 1, '2025-11-16 13:43:55'),
(11, 29, 32, 1, '2025-11-17 16:36:40');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `PostId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `PostType` enum('status','album') NOT NULL DEFAULT 'status',
  `Content` text DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `ImagePath` varchar(200) DEFAULT NULL,
  `Privacy` enum('public','friends') NOT NULL DEFAULT 'friends',
  `PostedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`PostId`, `UserId`, `PostType`, `Content`, `Title`, `ImagePath`, `Privacy`, `PostedAt`) VALUES
(19, 10, 'album', 'xcv', 'sdad', NULL, 'friends', '2025-11-14 21:12:04'),
(20, 10, 'album', 'á', 'ád', NULL, 'friends', '2025-11-14 21:12:28'),
(21, 10, 'status', 'ád', NULL, NULL, 'friends', '2025-11-14 21:22:10'),
(22, 10, 'status', 'zfsdf', NULL, NULL, 'public', '2025-11-14 21:22:31'),
(24, 28, 'status', 'dfdsaf', NULL, NULL, 'friends', '2025-11-14 21:31:01'),
(25, 10, 'album', 'áda', 'dá', NULL, 'public', '2025-11-14 21:31:55'),
(28, 32, 'status', 'Mệc', NULL, NULL, 'friends', '2025-11-15 22:40:03'),
(29, 25, 'status', 'Chào', NULL, NULL, 'public', '2025-11-16 13:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

CREATE TABLE `post_images` (
  `ImageId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL,
  `ImagePath` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`ImageId`, `PostId`, `ImagePath`) VALUES
(1, 19, 'uploads/posts/post_691738b4464ac_1763129524_0.png'),
(2, 19, 'uploads/posts/post_691738b44681c_1763129524_1.png'),
(3, 19, 'uploads/posts/post_691738b446a58_1763129524_2.png'),
(4, 19, 'uploads/posts/post_691738b447ada_1763129524_3.png'),
(5, 20, 'uploads/posts/post_691738cc51728_1763129548_0.png'),
(6, 21, 'uploads/posts/post_69173b1223c00_1763130130.png'),
(7, 22, 'uploads/posts/post_69173b2702f8b_1763130151.png'),
(12, 24, 'uploads/posts/post_69173d25de310_1763130661.png'),
(13, 25, 'uploads/posts/post_69173d5b39ece_1763130715_0.png'),
(14, 25, 'uploads/posts/post_69173d5b3ac90_1763130715_1.png'),
(15, 25, 'uploads/posts/post_69173d5b3baf3_1763130715_2.png'),
(16, 25, 'uploads/posts/post_69173d5b3be95_1763130715_3.png'),
(17, 25, 'uploads/posts/post_69173d5b3c195_1763130715_4.png'),
(20, 28, 'uploads/posts/post_69189ed3bfaf4_1763221203.jpg'),
(21, 29, 'uploads/posts/post_6919725736926_1763275351.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `ReportId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL COMMENT 'Bài đăng bị báo xấu',
  `ReporterId` int(11) NOT NULL COMMENT 'Người báo xấu',
  `ReportedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('pending','resolved') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái: chờ xử lý, đã xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`ReportId`, `PostId`, `ReporterId`, `ReportedAt`, `Status`) VALUES
(3, 22, 28, '2025-11-14 21:27:31', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserId` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `IsOnline` tinyint(1) NOT NULL DEFAULT 0,
  `LastSeen` datetime DEFAULT current_timestamp(),
  `Role` varchar(20) NOT NULL DEFAULT 'User',
  `FullName` varchar(100) DEFAULT NULL,
  `PhoneNumber` varchar(15) DEFAULT NULL,
  `Address` varchar(200) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `AvatarPath` varchar(255) NOT NULL DEFAULT '/uploads/default-avatar.jpg',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserId`, `Username`, `Password`, `Email`, `IsOnline`, `LastSeen`, `Role`, `FullName`, `PhoneNumber`, `Address`, `DateOfBirth`, `Gender`, `AvatarPath`, `CreatedAt`) VALUES
(9, 'admin', '$2y$10$pWGlzpGyj2cW15NBExjNpORdGqgxchX.nC81BcoGlAZhrJ2bGqF5a', 'admin@gmail.com', 0, '2025-11-13 10:07:48', 'Admin', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-11 20:17:38'),
(10, 'khoa', '$2y$10$2WFFz9RrKAwmwqR4H8Iwjuc.qflWtl4SJc/yt.rpIcv7dk723pWiO', 'deadordie159@gmail.com', 0, '2025-11-15 16:38:03', 'User', '', NULL, NULL, NULL, NULL, 'uploads/avatars/u_10/avatar_01.jpg', '2025-11-11 20:17:38'),
(11, 'Khoa1234', '$2y$10$IplhDQCsA859gdWupvBjnuZzJdGVIMxOTHRpZ3WEKPmcLxGtpgF4C', 'deadordie1204@gmail.com', 0, '2025-11-15 16:40:10', 'Admin', 'Đặng Nguyễn Đăng Khoa', NULL, NULL, NULL, 'Khác', 'uploads/avatars/u_11/avatar_07.jpg', '2025-11-11 20:17:38'),
(13, '64131003', '$2y$10$bSmsuAINZRyOfwbSGMcwcuSTZE8/n4HQXTQUptql4oLcNa2.jwaba', 'a@gmail.com', 0, '2025-11-13 10:07:48', 'User', 'HieKoa', '0973318260', 'nha trang', '2004-04-12', 'Nam', 'uploads/avatars/u_13/avatar_01.jpg', '2025-11-11 20:17:38'),
(14, 'phannhuthao', '$2y$10$3tBJeNsp6R1DcllLaINrd.kS6dPRRInt/1GypSc3lwfB/nMHQ9hR2', 'hao@gmail.com', 0, '2025-11-13 17:18:13', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-13 17:18:13'),
(16, 'yasuo', '$2y$10$BEjIVvCsWfyifRT81wthYuxem8AusqQmMIZLFz60wES.vd4.XS2KK', 'ys@gmail.com', 0, '2025-11-13 17:22:28', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-13 17:22:28'),
(18, 'hao544', '$2y$10$k4wqCjZ9uXwaMb2AqtFipOC509v2ZH6V.TO.UgAq7nkYw/zKIDs2S', 'hao544@gmail.com', 0, '2025-11-13 17:27:30', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-13 17:27:30'),
(20, 'pnhao1234', '$2y$10$a0Noy30P2R1ypWuwxDcPWeWdg93J0l9W7UbAGlKygRvt0gqT.Rt8C', 'pnhao@gmail.com', 0, '2025-11-13 18:54:23', 'Admin', '', NULL, NULL, NULL, NULL, 'uploads/avatars/u_20/avatar_01.jpg', '2025-11-13 18:54:23'),
(21, 'pnhao123', '$2y$10$ZAp82wJkNniJuOQ5uKXZPua397FphNyfZ6a7ATgi1O4PcuRC0mSp6', 'hao090@gmail.com', 0, '2025-11-13 20:15:02', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-13 20:15:02'),
(24, 'accmoicuatne', '$2y$10$SPY/SCJR8sJipb5hAm2WLujLS27cbk4wXxjD3lhqSoMbUpSfqFTqm', '123456@gmail.com', 1, '2025-11-14 11:18:47', 'Admin', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-14 11:18:47'),
(25, 'AizuRen', '$2y$10$KhY.KAabqxb5Iw1cGQ2CAOrWqcAwqt.zwS4G6TnpdcmNE2vVhUJHC', 'luan120604@gmail.com', 0, '2025-11-17 00:37:15', 'User', 'Nguyễn Luân Thiên Đỗ', '0329988967', '10 Sinh Trung', '2004-06-12', 'Nam', 'uploads/avatars/u_25/avatar_01.jpg', '2025-11-14 12:11:26'),
(27, 'huhuhu', '$2y$10$uDkt.M1blusQNursx1QaAO0HyVK2y34OgLP3KQl0vfvt73GlG6Oby', 'ajhsdajshdb@gmail.com', 0, '2025-11-14 21:26:53', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-14 18:49:43'),
(28, 'hihihi', '$2y$10$28bUQRpUjyQGZTUEAJdxNe2XpZ02AhI3TwMg9lJPamblg5skpDvsC', 'hdafhabfuia@gmail.com', 1, '2025-11-14 21:27:11', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-14 18:49:59'),
(29, 'hehehe', '$2y$10$ZmxFJZ5/BRrmlKp3zKvH6OsxivGIDHIh3wIvppuRCiuPADD1vzw5.', 'ahfbdasihbdiua@gmail.com', 0, '2025-11-14 21:27:06', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-14 18:50:17'),
(30, 'ccc', '$2y$10$VnEQMqzhFClDbZpV4ZUFPuN1V7oUhtQEZewkJT9US8mzqzOtg0Xvm', 'fsgdfs@gmail.com', 0, '2025-11-14 21:29:48', 'Admin', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-14 21:27:59'),
(31, 'SilGee', '$2y$10$epjWKbnHJWhAVRhEBp9em.k1KlfqPjD76pvep6Lt5jIVdpfmHxNE2', 'luan.ndt.64cntt@edu.ntu.vn', 0, '2025-11-15 22:38:55', 'Admin', '', NULL, NULL, NULL, NULL, 'uploads/avatars/u_31/avatar_01.jpg', '2025-11-15 16:40:29'),
(32, 'JaneDoe', '$2y$10$zHyCmNbv7hG6HFvo4Jibmu/XvBWrMDrNigbQ3zhyOmxEoWlXDRETu', 'luanthien1206@gmail.com', 1, '2025-11-19 16:15:34', 'Admin', 'Jane Doe', '0328007535', '31 Tố Hữu', '2004-06-12', 'Nữ', 'uploads/avatars/u_32/avatar_01.jpg', '2025-11-15 22:39:18'),
(33, 'JohnDoe', '$2y$10$uKvezFySiaVEWLAyJM68huFqlod6vt1rs.Ma68gfASu1Eoj.iVbDC', 'lukeeinsworth@gmail.com', 0, '2025-11-16 09:17:59', 'Admin', 'John Doe', '0329988967', '10B Đường 23 tháng 10', '2004-06-12', 'Nam', 'uploads/avatars/u_33/avatar_01.jpg', '2025-11-15 23:07:38'),
(34, 'Vekrom', '$2y$10$3whuyAi1S5v/zENGKKwHiuJVp/lGxiIvRQfYCVr/V/zNLq07ml3mC', 'Vekrom@gmail.com', 0, '2025-11-17 00:27:52', 'User', '', NULL, NULL, NULL, NULL, 'uploads/avatars/u_34/avatar_01.jpg', '2025-11-16 23:23:25'),
(35, 'Vermillion', '$2y$10$PZRW.7esx1t4ykPcfU7Qb.gy1HTbywDd9.17mMAjwYyRpFXMTFDZu', 'Vermillion@gmail.com', 0, '2025-11-18 16:08:45', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-17 00:35:03'),
(37, 'LukeEinsworth', '$2y$10$hNwU7THuMVyAYVVm6VxYXeXQ7cA6FQDQR6Wgp2Wxm/4kHxehFdfk2', 'Lukey@gmail.com', 0, '2025-11-19 16:15:27', 'User', '', NULL, NULL, NULL, NULL, 'uploads/avatars/u_37/avatar_01.jpg', '2025-11-17 00:35:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `UQ_Block_Pair` (`BlockerId`,`BlockedId`),
  ADD KEY `FK_BlockedUsers_Blocked` (`BlockedId`);

--
-- Indexes for table `commentemotes`
--
ALTER TABLE `commentemotes`
  ADD PRIMARY KEY (`CommentEmoteId`),
  ADD UNIQUE KEY `UQ_User_Comment_Emote` (`CommentId`,`UserId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `EmoteId` (`EmoteId`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`CommentId`),
  ADD KEY `PostId` (`PostId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `fk_parent_comment` (`ParentCommentId`);

--
-- Indexes for table `emojiusage`
--
ALTER TABLE `emojiusage`
  ADD PRIMARY KEY (`Emoji`);

--
-- Indexes for table `emotes`
--
ALTER TABLE `emotes`
  ADD PRIMARY KEY (`EmoteId`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`FriendId`),
  ADD UNIQUE KEY `UQ_Friendship` (`UserId`,`FriendUserId`),
  ADD KEY `FriendUserId` (`FriendUserId`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`GroupId`),
  ADD KEY `FK_Groups_CreatedBy` (`CreatedBy`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`GroupMemberId`),
  ADD UNIQUE KEY `UQ_Group_User` (`GroupId`,`UserId`),
  ADD KEY `FK_GroupMembers_User` (`UserId`);

--
-- Indexes for table `hidden_feeds`
--
ALTER TABLE `hidden_feeds`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `UQ_Hide_Pair` (`HiderId`,`HiddenId`),
  ADD KEY `FK_HiddenFeeds_Hidden` (`HiddenId`);

--
-- Indexes for table `messageemotes`
--
ALTER TABLE `messageemotes`
  ADD PRIMARY KEY (`MessageEmoteId`),
  ADD UNIQUE KEY `UQ_User_Message_Emote` (`MessageId`,`UserId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `EmoteId` (`EmoteId`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`MessageId`),
  ADD KEY `SenderId` (`SenderId`),
  ADD KEY `ReceiverId` (`ReceiverId`),
  ADD KEY `GroupId` (`GroupId`);

--
-- Indexes for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD PRIMARY KEY (`OtpId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `Email` (`Email`);

--
-- Indexes for table `postemotes`
--
ALTER TABLE `postemotes`
  ADD PRIMARY KEY (`PostEmoteId`),
  ADD UNIQUE KEY `UQ_User_Post_Emote` (`PostId`,`UserId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `EmoteId` (`EmoteId`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`PostId`),
  ADD KEY `UserId` (`UserId`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD PRIMARY KEY (`ImageId`),
  ADD KEY `FK_PostImages_Post` (`PostId`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportId`),
  ADD KEY `FK_Reports_Post` (`PostId`),
  ADD KEY `FK_Reports_Reporter` (`ReporterId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserId`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blocked_users`
--
ALTER TABLE `blocked_users`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `commentemotes`
--
ALTER TABLE `commentemotes`
  MODIFY `CommentEmoteId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `CommentId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `emotes`
--
ALTER TABLE `emotes`
  MODIFY `EmoteId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `FriendId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `GroupId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `group_members`
--
ALTER TABLE `group_members`
  MODIFY `GroupMemberId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `hidden_feeds`
--
ALTER TABLE `hidden_feeds`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messageemotes`
--
ALTER TABLE `messageemotes`
  MODIFY `MessageEmoteId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `MessageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  MODIFY `OtpId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `postemotes`
--
ALTER TABLE `postemotes`
  MODIFY `PostEmoteId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `PostId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `post_images`
--
ALTER TABLE `post_images`
  MODIFY `ImageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `ReportId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `FK_BlockedUsers_Blocked` FOREIGN KEY (`BlockedId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_BlockedUsers_Blocker` FOREIGN KEY (`BlockerId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- Constraints for table `commentemotes`
--
ALTER TABLE `commentemotes`
  ADD CONSTRAINT `commentemotes_ibfk_1` FOREIGN KEY (`CommentId`) REFERENCES `comments` (`CommentId`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentemotes_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentemotes_ibfk_3` FOREIGN KEY (`EmoteId`) REFERENCES `emotes` (`EmoteId`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`ParentCommentId`) REFERENCES `comments` (`CommentId`) ON DELETE CASCADE;

--
-- Constraints for table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`FriendUserId`) REFERENCES `users` (`UserId`);

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `FK_Groups_CreatedBy` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `FK_GroupMembers_Group` FOREIGN KEY (`GroupId`) REFERENCES `groups` (`GroupId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_GroupMembers_User` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- Constraints for table `hidden_feeds`
--
ALTER TABLE `hidden_feeds`
  ADD CONSTRAINT `FK_HiddenFeeds_Hidden` FOREIGN KEY (`HiddenId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_HiddenFeeds_Hider` FOREIGN KEY (`HiderId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- Constraints for table `messageemotes`
--
ALTER TABLE `messageemotes`
  ADD CONSTRAINT `messageemotes_ibfk_1` FOREIGN KEY (`MessageId`) REFERENCES `messages` (`MessageId`) ON DELETE CASCADE,
  ADD CONSTRAINT `messageemotes_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `messageemotes_ibfk_3` FOREIGN KEY (`EmoteId`) REFERENCES `emotes` (`EmoteId`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`SenderId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`ReceiverId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`GroupId`) REFERENCES `groups` (`GroupId`);

--
-- Constraints for table `password_reset_otp`
--
ALTER TABLE `password_reset_otp`
  ADD CONSTRAINT `fk_otp_user` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

--
-- Constraints for table `postemotes`
--
ALTER TABLE `postemotes`
  ADD CONSTRAINT `postemotes_ibfk_1` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE,
  ADD CONSTRAINT `postemotes_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `postemotes_ibfk_3` FOREIGN KEY (`EmoteId`) REFERENCES `emotes` (`EmoteId`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- Constraints for table `post_images`
--
ALTER TABLE `post_images`
  ADD CONSTRAINT `FK_PostImages_Post` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `FK_Reports_Post` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_Reports_Reporter` FOREIGN KEY (`ReporterId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
