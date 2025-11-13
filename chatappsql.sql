-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 12:30 PM
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
-- Database: `chatappsql`
--
CREATE DATABASE IF NOT EXISTS `chatappsql` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `chatappsql`;

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
-- [GỘP] Thêm 5 Emojis
--
INSERT INTO `emotes` (`EmoteId`, `EmoteName`, `EmoteUnicode`) VALUES
(1, 'Like', '👍'),
(2, 'Haha', '😂'),
(3, 'Wow', '😮'),
(4, 'Sad', '😢'),
(5, 'Angry', '😡');

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- (Giữ nguyên cấu trúc đầy đủ của bạn)
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
  `AvatarPath` varchar(255) NOT NULL DEFAULT '/images/default-avatar.jpg',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- [GỘP] Giữ nguyên dữ liệu người dùng của bạn
--
INSERT INTO `users` (`UserId`, `Username`, `Password`, `Email`, `IsOnline`, `LastSeen`, `Role`, `FullName`, `PhoneNumber`, `Address`, `DateOfBirth`, `Gender`, `AvatarPath`, `CreatedAt`) VALUES
(9, 'admin', '$2y$10$pWGlzpGyj2cW15NBExjNpORdGqgxchX.nC81BcoGlAZhrJ2bGqF5a', 'admin@gmail.com', 0, '2025-11-13 10:07:48', 'Admin', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-11 20:17:38'),
(10, 'khoa', '$2y$10$2qjB9c1d9l5a/vwYjy.eVuhQXX/nQ3huUqYhAsNgMIAn8tHFL.xB6', 'deadordie159@gmail.com', 0, '2025-11-13 10:07:48', 'User', NULL, NULL, NULL, NULL, NULL, 'uploads/default-avatar.jpg', '2025-11-11 20:17:38'),
(11, 'Khoa1234', '$2y$10$IplhDQCsA859gdWupvBjnuZzJdGVIMxOTHRpZ3WEKPmcLxGtpgF4C', 'deadordie1204@gmail.com', 1, '2025-11-13 10:07:48', 'Admin', 'Đặng Nguyễn Đăng Khoa', NULL, NULL, NULL, 'Khác', 'uploads/avatars/u_11/avatar_06.jpg', '2025-11-11 20:17:38'),
(13, '64131003', '$2y$10$bSmsuAINZRyOfwbSGMcwcuSTZE8/n4HQXTQUptql4oLcNa2.jwaba', 'a@gmail.com', 1, '2025-11-13 10:07:48', 'User', '', NULL, NULL, NULL, NULL, 'uploads/avatars/u_13/avatar_02.jpg', '2025-11-11 20:17:38');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `PostId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Content` text DEFAULT NULL,
  `ImagePath` varchar(200) DEFAULT NULL,
  `PostedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--
-- [GỘP] Đã thêm cột `ParentCommentId`
--
CREATE TABLE `comments` (
  `CommentId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `Content` text NOT NULL,
  `ParentCommentId` int(11) DEFAULT NULL,
  `CommentedAt` datetime NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `emojiusage`
--

CREATE TABLE `emojiusage` (
  `Emoji` varchar(10) NOT NULL,
  `UsageCount` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- [GỘP] Giữ nguyên dữ liệu bạn bè của bạn
--
INSERT INTO `friends` (`FriendId`, `UserId`, `FriendUserId`, `IsConfirmed`) VALUES
(1, 13, 11, 1),
(2, 11, 10, 0);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `GroupId` int(11) NOT NULL,
  `GroupName` varchar(100) NOT NULL
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
  `SentAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- [GỘP] Giữ nguyên dữ liệu tin nhắn của bạn
--
INSERT INTO `messages` (`MessageId`, `SenderId`, `ReceiverId`, `GroupId`, `Content`, `SentAt`) VALUES
(1, 10, 9, NULL, 'alo', '2025-11-11 18:53:11'),
(2, 11, 10, NULL, 'alu', '2025-11-11 19:09:42'),
(3, 10, 11, NULL, 'hi', '2025-11-11 19:10:06'),
(5, 11, 9, NULL, 'alo', '2025-11-11 20:41:56'),
(6, 11, 10, NULL, 'alo', '2025-11-13 10:58:09'),
(7, 11, 13, NULL, 'ê', '2025-11-13 11:23:12'),
(8, 11, 13, NULL, 'làm gì đó', '2025-11-13 11:23:15');

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
-- Table structure for table `postemotes`
--

CREATE TABLE `postemotes` (
  `PostEmoteId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `EmoteId` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- [GỘP] Bảng cho chức năng "Ẩn nhật ký"
--
CREATE TABLE `hidden_feeds` (
  `Id` int(11) NOT NULL,
  `HiderId` int(11) NOT NULL COMMENT 'ID của người đi ẩn',
  `HiddenId` int(11) NOT NULL COMMENT 'ID của người bị ẩn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- [GỘP] Bảng cho chức năng "Chặn xem nhật ký"
--
CREATE TABLE `blocked_users` (
  `Id` int(11) NOT NULL,
  `BlockerId` int(11) NOT NULL COMMENT 'ID của người đi chặn',
  `BlockedId` int(11) NOT NULL COMMENT 'ID của người bị chặn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- [GỘP] Bảng cho chức năng "Báo xấu"
--
CREATE TABLE `reports` (
  `ReportId` int(11) NOT NULL,
  `PostId` int(11) NOT NULL COMMENT 'Bài đăng bị báo xấu',
  `ReporterId` int(11) NOT NULL COMMENT 'Người báo xấu',
  `ReportedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` enum('pending','resolved') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái: chờ xử lý, đã xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

ALTER TABLE `commentemotes`
  ADD PRIMARY KEY (`CommentEmoteId`),
  ADD UNIQUE KEY `UQ_User_Comment_Emote` (`CommentId`,`UserId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `EmoteId` (`EmoteId`);

--
-- [GỘP] Sửa Indexes cho `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`CommentId`),
  ADD KEY `PostId` (`PostId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `fk_parent_comment` (`ParentCommentId`);

ALTER TABLE `emojiusage`
  ADD PRIMARY KEY (`Emoji`);

ALTER TABLE `emotes`
  ADD PRIMARY KEY (`EmoteId`);

ALTER TABLE `friends`
  ADD PRIMARY KEY (`FriendId`),
  ADD UNIQUE KEY `UQ_Friendship` (`UserId`,`FriendUserId`),
  ADD KEY `FriendUserId` (`FriendUserId`);

ALTER TABLE `groups`
  ADD PRIMARY KEY (`GroupId`);

ALTER TABLE `messageemotes`
  ADD PRIMARY KEY (`MessageEmoteId`),
  ADD UNIQUE KEY `UQ_User_Message_Emote` (`MessageId`,`UserId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `EmoteId` (`EmoteId`);

ALTER TABLE `messages`
  ADD PRIMARY KEY (`MessageId`),
  ADD KEY `SenderId` (`SenderId`),
  ADD KEY `ReceiverId` (`ReceiverId`),
  ADD KEY `GroupId` (`GroupId`);

ALTER TABLE `postemotes`
  ADD PRIMARY KEY (`PostEmoteId`),
  ADD UNIQUE KEY `UQ_User_Post_Emote` (`PostId`,`UserId`),
  ADD KEY `UserId` (`UserId`),
  ADD KEY `EmoteId` (`EmoteId`);

ALTER TABLE `posts`
  ADD PRIMARY KEY (`PostId`),
  ADD KEY `UserId` (`UserId`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`UserId`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- [GỘP] Indexes cho 3 bảng mới
--
ALTER TABLE `hidden_feeds`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `UQ_Hide_Pair` (`HiderId`,`HiddenId`),
  ADD KEY `FK_HiddenFeeds_Hidden` (`HiddenId`);

ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`Id`),
  ADD UNIQUE KEY `UQ_Block_Pair` (`BlockerId`,`BlockedId`),
  ADD KEY `FK_BlockedUsers_Blocked` (`BlockedId`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportId`),
  ADD KEY `FK_Reports_Post` (`PostId`),
  ADD KEY `FK_Reports_Reporter` (`ReporterId`);

--
-- AUTO_INCREMENT for dumped tables
--
-- [GỘP] Cập nhật AUTO_INCREMENT theo dữ liệu của bạn
--
ALTER TABLE `commentemotes`
  MODIFY `CommentEmoteId` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `comments`
  MODIFY `CommentId` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `emotes`
  MODIFY `EmoteId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `friends`
  MODIFY `FriendId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `groups`
  MODIFY `GroupId` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `messageemotes`
  MODIFY `MessageEmoteId` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `messages`
  MODIFY `MessageId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `postemotes`
  MODIFY `PostEmoteId` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `posts`
  MODIFY `PostId` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- [GỘP] AUTO_INCREMENT cho 3 bảng mới
--
ALTER TABLE `hidden_feeds`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `blocked_users`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `reports`
  MODIFY `ReportId` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE `commentemotes`
  ADD CONSTRAINT `commentemotes_ibfk_1` FOREIGN KEY (`CommentId`) REFERENCES `comments` (`CommentId`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentemotes_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentemotes_ibfk_3` FOREIGN KEY (`EmoteId`) REFERENCES `emotes` (`EmoteId`) ON DELETE CASCADE;

--
-- [GỘP] Sửa Constraints cho `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`ParentCommentId`) REFERENCES `comments` (`CommentId`) ON DELETE CASCADE;

ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`FriendUserId`) REFERENCES `users` (`UserId`);

ALTER TABLE `messageemotes`
  ADD CONSTRAINT `messageemotes_ibfk_1` FOREIGN KEY (`MessageId`) REFERENCES `messages` (`MessageId`) ON DELETE CASCADE,
  ADD CONSTRAINT `messageemotes_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `messageemotes_ibfk_3` FOREIGN KEY (`EmoteId`) REFERENCES `emotes` (`EmoteId`) ON DELETE CASCADE;

ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`SenderId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`ReceiverId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`GroupId`) REFERENCES `groups` (`GroupId`);

ALTER TABLE `postemotes`
  ADD CONSTRAINT `postemotes_ibfk_1` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE,
  ADD CONSTRAINT `postemotes_ibfk_2` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `postemotes_ibfk_3` FOREIGN KEY (`EmoteId`) REFERENCES `emotes` (`EmoteId`) ON DELETE CASCADE;

ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- [GỘP] Constraints cho 3 bảng mới
--
ALTER TABLE `hidden_feeds`
  ADD CONSTRAINT `FK_HiddenFeeds_Hider` FOREIGN KEY (`HiderId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_HiddenFeeds_Hidden` FOREIGN KEY (`HiddenId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

ALTER TABLE `blocked_users`
  ADD CONSTRAINT `FK_BlockedUsers_Blocker` FOREIGN KEY (`BlockerId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_BlockedUsers_Blocked` FOREIGN KEY (`BlockedId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;

ALTER TABLE `reports`
  ADD CONSTRAINT `FK_Reports_Post` FOREIGN KEY (`PostId`) REFERENCES `posts` (`PostId`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_Reports_Reporter` FOREIGN KEY (`ReporterId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
--cap nhat