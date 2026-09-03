-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2026 at 10:05 AM
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
-- Database: `sound_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'Ali', 'admin@sound.com', '$2y$10$PneFcaUtp28Uh0O6JvedheYF42MoqBWHwWS7z5UVvQFw6rHqMI07C', 'profile_6a8fd4976140c.jpg', '2026-08-17 18:57:39', '2026-08-27 06:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `id` int(11) NOT NULL,
  `album_name` varchar(150) NOT NULL,
  `artist_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `albums`
--

INSERT INTO `albums` (`id`, `album_name`, `artist_id`, `created_at`, `image`) VALUES
(1, 'Khuda Aur Mohabbat (Original Score) - Single', 1, '2026-08-18 15:53:09', '__none__'),
(2, '÷ (Deluxe)', 2, '2026-08-18 15:58:19', 'https://cdn-images.dzcdn.net/images/cover/ff64b988f85900881ab7e289d6726baf/250x250-000000-80-0-0.jpg'),
(3, 'Blinding Lights - Single', 3, '2026-08-18 16:07:15', 'https://cdn-images.dzcdn.net/images/cover/dd7c8f64ea151fbe4de06174444ddf6b/250x250-000000-80-0-0.jpg'),
(4, 'Evolve', 4, '2026-08-18 16:08:16', 'https://cdn-images.dzcdn.net/images/cover/247b228179aea3b083eef43522b78b45/250x250-000000-80-0-0.jpg'),
(5, 'Despacito (Versión Urbana/Sky) - Single', 5, '2026-08-18 16:10:26', '__none__'),
(6, 'Faded - EP', 6, '2026-08-18 16:10:58', 'https://cdn-images.dzcdn.net/images/cover/bcc4e8a0e710b89e047bf77519210192/250x250-000000-80-0-0.jpg'),
(7, 'Dreamland', 7, '2026-08-18 16:11:31', 'https://cdn-images.dzcdn.net/images/cover/04ea51c6eb90a6208f2e47da861cf1a5/250x250-000000-80-0-0.jpg'),
(8, 'High Expectations', 8, '2026-08-18 16:14:18', 'https://cdn-images.dzcdn.net/images/cover/fd409078ed61d52b9d1d01d92e72998a/250x250-000000-80-0-0.jpg'),
(9, '21', 9, '2026-08-18 16:15:26', 'https://cdn-images.dzcdn.net/images/cover/dc1ce848d830ecc93521be5a78350364/250x250-000000-80-0-0.jpg'),
(10, 'Here\'s to the Good Times', 10, '2026-08-18 16:16:12', 'https://cdn-images.dzcdn.net/images/cover/f566c4c0a81527eb128f5976eec475b2/250x250-000000-80-0-0.jpg'),
(11, 'Milk & Honey', 11, '2026-08-18 16:16:45', 'https://cdn-images.dzcdn.net/images/cover/8d7fe7e0d95b7f5393cd5d66e5fee434/250x250-000000-80-0-0.jpg'),
(12, 'Punk Goes Pop, Vol. 7', 12, '2026-08-18 16:18:28', 'https://cdn-images.dzcdn.net/images/cover/f6ce1e255165145612b5c95fb9e4be54/250x250-000000-80-0-0.jpg'),
(13, 'Kidz Bop 40', 13, '2026-08-18 16:19:11', 'https://cdn-images.dzcdn.net/images/cover/040f97dd3a837a65f8ebf935d568289d/250x250-000000-80-0-0.jpg'),
(14, 'Lovely - Single', 14, '2026-08-18 16:20:18', '__none__'),
(15, 'Wasteland, Baby!', 15, '2026-08-18 16:21:20', 'https://cdn-images.dzcdn.net/images/cover/ced08a09eb93982eb30ae9ae44a1c5ba/250x250-000000-80-0-0.jpg'),
(16, 'Counting Stars (2023 Version) - Single', 16, '2026-08-18 16:22:16', '__none__'),
(17, 'Turning Point', 17, '2026-08-18 16:22:43', 'https://cdn-images.dzcdn.net/images/cover/4b0c634703838fa3e7caac51fcf1940d/250x250-000000-80-0-0.jpg'),
(18, 'Bad Guy - Single', 18, '2026-08-18 16:23:16', '__none__'),
(19, 'Sugar', 19, '2026-08-18 16:23:37', 'https://cdn-images.dzcdn.net/images/cover/ec4d1bb1b4c28c04e592b32f133b40cc/250x250-000000-80-0-0.jpg'),
(20, 'PRISM (Deluxe Version)', 20, '2026-08-18 16:23:57', '__none__'),
(21, 'Until I Found You - Single', 21, '2026-08-18 16:24:24', '__none__'),
(22, 'Tera Mera Hai Pyar Amar (From \"Ishq Murshid\") - Single', 22, '2026-08-18 16:36:59', '__none__'),
(23, 'Khaani (Original Score) - Single', 23, '2026-08-18 16:37:58', '__none__'),
(24, 'Pasoori - Single', 24, '2026-08-18 16:38:31', '__none__'),
(25, 'Meri Zindagi Hai Tu - Single', 25, '2026-08-18 16:40:06', '__none__'),
(26, 'Some Girls', 29, '2026-08-23 16:49:46', 'https://cdn-images.dzcdn.net/images/cover/4daeaee9a15329dc861f62976d0cbb7c/250x250-000000-80-0-0.jpg'),
(27, 'Satyameva Jayate 2 (Original Motion Picture Soundtrack)', 36, '2026-08-27 17:33:45', '__none__'),
(28, 'Tere Bin (Original Score) - Single', 37, '2026-08-27 18:00:27', '__none__');

-- --------------------------------------------------------

--
-- Table structure for table `artists`
--

CREATE TABLE `artists` (
  `id` int(11) NOT NULL,
  `artist_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artists`
--

INSERT INTO `artists` (`id`, `artist_name`, `created_at`, `image`) VALUES
(1, 'Rahat Fateh Ali Khan & Nish Asher', '2026-08-18 15:53:09', '__none__'),
(2, 'Ed Sheeran', '2026-08-18 15:58:19', 'https://cdn-images.dzcdn.net/images/artist/d41d8cd98f00b204e9800998ecf8427e/250x250-000000-80-0-0.jpg'),
(3, 'The Weeknd', '2026-08-18 16:07:15', 'https://cdn-images.dzcdn.net/images/artist/581693b4724a7fcfa754455101e13a44/250x250-000000-80-0-0.jpg'),
(4, 'Imagine Dragons', '2026-08-18 16:08:16', 'https://cdn-images.dzcdn.net/images/artist/1ba025c23cae3dee14b51152990285fc/250x250-000000-80-0-0.jpg'),
(5, 'Luis Fonsi & Daddy Yankee', '2026-08-18 16:10:26', '__none__'),
(6, 'Alan Walker', '2026-08-18 16:10:58', 'https://cdn-images.dzcdn.net/images/artist/79d384488d390e65a6c27a95c431627e/250x250-000000-80-0-0.jpg'),
(7, 'Glass Animals', '2026-08-18 16:11:31', 'https://cdn-images.dzcdn.net/images/artist/d41d8cd98f00b204e9800998ecf8427e/250x250-000000-80-0-0.jpg'),
(8, 'Atlus', '2026-08-18 16:14:18', 'https://cdn-images.dzcdn.net/images/artist/09073251c6a5cb97def13435833dcc15/250x250-000000-80-0-0.jpg'),
(9, 'Adele', '2026-08-18 16:15:26', 'https://cdn-images.dzcdn.net/images/artist/60cf720ba6f2a96820d8b69bcea61f36/250x250-000000-80-0-0.jpg'),
(10, 'Florida Georgia Line', '2026-08-18 16:16:12', 'https://cdn-images.dzcdn.net/images/artist/f42c37d3065cf700cea975aa2e708506/250x250-000000-80-0-0.jpg'),
(11, 'Selah Soul', '2026-08-18 16:16:45', 'https://cdn-images.dzcdn.net/images/artist/2a3aed6d9d96335a3dc81b8477bead8d/250x250-000000-80-0-0.jpg'),
(12, 'Grayscale', '2026-08-18 16:18:28', 'https://cdn-images.dzcdn.net/images/artist/93bd73e2e92ed912df209b0983fd9de6/250x250-000000-80-0-0.jpg'),
(13, 'KIDZ BOP Kids', '2026-08-18 16:19:11', 'https://cdn-images.dzcdn.net/images/artist/5fba57b995f40383fdcfef6ee406ac74/250x250-000000-80-0-0.jpg'),
(14, 'Lauren Babic & Seraphim', '2026-08-18 16:20:18', '__none__'),
(15, 'Hozier', '2026-08-18 16:21:20', '__none__'),
(16, 'OneRepublic', '2026-08-18 16:22:16', 'https://cdn-images.dzcdn.net/images/artist/36556d769dc4052d915eb78c8daf98fb/250x250-000000-80-0-0.jpg'),
(17, 'Mario', '2026-08-18 16:22:43', 'https://cdn-images.dzcdn.net/images/artist/d3a4b7dff134dd1f2ac09640bc4b1bef/250x250-000000-80-0-0.jpg'),
(18, 'Casey Donahew', '2026-08-18 16:23:16', 'https://cdn-images.dzcdn.net/images/artist/98f9d06765d1bc7d1148cc1bdb2f3ce3/250x250-000000-80-0-0.jpg'),
(19, 'Robin Schulz', '2026-08-18 16:23:37', 'https://cdn-images.dzcdn.net/images/artist/1486b240b4987ab8de5bca19b14b3260/250x250-000000-80-0-0.jpg'),
(20, 'Katy Perry', '2026-08-18 16:23:57', 'https://cdn-images.dzcdn.net/images/artist/9c144bd1ed91b2a59c826cf1d1cde633/250x250-000000-80-0-0.jpg'),
(21, 'Stephen Sanchez', '2026-08-18 16:24:24', 'https://cdn-images.dzcdn.net/images/artist/6f9911aab77f6b9836e3fb9bf4b46335/250x250-000000-80-0-0.jpg'),
(22, 'Ahmed Jahanzeb', '2026-08-18 16:36:59', 'https://cdn-images.dzcdn.net/images/artist/74e74d4ad72548289e6564efa36b700b/250x250-000000-80-0-0.jpg'),
(23, 'Rahat Fateh Ali Khan', '2026-08-18 16:37:58', 'https://cdn-images.dzcdn.net/images/artist/d41d8cd98f00b204e9800998ecf8427e/250x250-000000-80-0-0.jpg'),
(24, 'Shae Gill & Ali Sethi', '2026-08-18 16:38:31', '__none__'),
(25, 'Asim Azhar & Sabri Sisters', '2026-08-18 16:40:06', '__none__'),
(26, 'Coldplay', '2026-08-23 05:59:27', 'https://cdn-images.dzcdn.net/images/artist/d41d8cd98f00b204e9800998ecf8427e/250x250-000000-80-0-0.jpg'),
(27, 'ASH ISLAND', '2026-08-23 06:02:55', 'https://cdn-images.dzcdn.net/images/artist/c279e84434198f1d3eb4ff1599ab4724/250x250-000000-80-0-0.jpg'),
(28, 'Tebey', '2026-08-23 16:44:49', 'https://cdn-images.dzcdn.net/images/artist/990d5763b0d040480b5426197e0c00c6/250x250-000000-80-0-0.jpg'),
(29, 'The Rolling Stones', '2026-08-23 16:49:46', 'https://cdn-images.dzcdn.net/images/artist/cac467bef484959b4ad503c4cb7ef83d/250x250-000000-80-0-0.jpg'),
(30, 'Acoustic Remedies by Kamran', '2026-08-23 16:52:36', 'https://cdn-images.dzcdn.net/images/artist//250x250-000000-80-0-0.jpg'),
(31, 'Pritam, Arijit Singh & Amitabh Bhattacharya', '2026-08-23 16:54:31', 'https://cdn-images.dzcdn.net/images/artist//250x250-000000-80-0-0.jpg'),
(32, 'RajTailorSoundz', '2026-08-23 16:57:36', 'https://cdn-images.dzcdn.net/images/artist//250x250-000000-80-0-0.jpg'),
(33, 'Bohemia & Punjabi Dump', '2026-08-25 04:14:10', '__none__'),
(34, 'DARSHANLALVALECHA', '2026-08-27 06:11:18', 'https://cdn-images.dzcdn.net/images/artist/0ee59d440de18d5af8a278af09d0c044/250x250-000000-80-0-0.jpg'),
(35, 'MARKPAIN', '2026-08-27 07:35:48', 'https://cdn-images.dzcdn.net/images/artist/d5dbd9875eab390a81a35d3f10f0a8f5/250x250-000000-80-0-0.jpg'),
(36, 'Jubin Nautiyal & Neeti Mohan', '2026-08-27 17:33:45', '__none__'),
(37, 'Shani Arshad', '2026-08-27 18:00:27', 'https://cdn-images.dzcdn.net/images/artist/069278086e9b1e4ad6f2fb86512e1b8c/250x250-000000-80-0-0.jpg'),
(38, 'Marina and The Diamonds', '2026-08-27 19:06:15', 'https://cdn-images.dzcdn.net/images/artist/a2d0e12d5e8b9cf9f489db3b64c20533/250x250-000000-80-0-0.jpg'),
(39, 'HOOP_MUSIC', '2026-08-28 19:26:44', 'https://cdn-images.dzcdn.net/images/artist/030f857536985a8bc8ac3429a0b13824/250x250-000000-80-0-0.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `genre_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `genre_name`) VALUES
(4, 'Alternative'),
(15, 'Bollywood'),
(9, 'Children\'s Music'),
(7, 'Country'),
(6, 'Dance'),
(17, 'Hip-Hop/Rap'),
(14, 'K-Pop'),
(2, 'Pop'),
(8, 'Pop Punk'),
(3, 'R&B/Soul'),
(10, 'Rock'),
(11, 'Singer/Songwriter'),
(1, 'Soundtrack'),
(5, 'Urbano latino'),
(12, 'Urdu'),
(16, 'World'),
(13, 'Worldwide');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `language_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `language_name`) VALUES
(1, 'English'),
(3, 'Hindi'),
(2, 'Urdu');

-- --------------------------------------------------------

--
-- Table structure for table `music`
--

CREATE TABLE `music` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `artist_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `year_id` int(11) DEFAULT NULL,
  `genre_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `music_file` varchar(255) DEFAULT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source_track_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `music`
--

INSERT INTO `music` (`id`, `title`, `artist_id`, `album_id`, `year_id`, `genre_id`, `language_id`, `description`, `image`, `music_file`, `is_new`, `created_at`, `source_track_id`) VALUES
(1, 'Khuda Aur Mohabbat (Original Score)', 1, 1, 1, 1, 2, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music124/v4/77/7c/4d/777c4de6-df23-b412-10aa-ea9fcd79e811/859745934947_cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview125/v4/05/ab/be/05abbeba-73d1-7866-f088-d0982aa9ffa1/mzaf_18437729690723784573.plus.aac.p.m4a', 1, '2026-08-18 15:53:09', '1554984464'),
(2, 'Shape of You', 2, 2, 2, 2, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/15/e6/e8/15e6e8a4-4190-6a8b-86c3-ab4a51b88288/190295851286.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/44/c7/4f/44c74f0d-72dc-6143-d4d0-ba14d661ca0d/mzaf_9566898362556366703.plus.aac.p.m4a', 1, '2026-08-18 15:58:19', '1193701392'),
(3, 'Blinding Lights', 3, 3, 3, 3, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music125/v4/a6/6e/bf/a66ebf79-5008-8948-b352-a790fc87446b/19UM1IM04638.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/17/b4/8f/17b48f9a-0b93-6bb8-fe1d-3a16623c2cfb/mzaf_9560252727299052414.plus.aac.p.m4a', 1, '2026-08-18 16:07:15', '1488408568'),
(4, 'Believer', 4, 4, 2, 4, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music126/v4/11/7a/b8/117ab805-6811-8929-18b9-0fad7baf0c25/17UMGIM98210.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/c0/3f/36/c03f367a-b66b-fd0a-a54c-30f8250c4410/mzaf_12768434238801682952.plus.aac.p.m4a', 1, '2026-08-18 16:08:16', '1411628233'),
(5, 'Despacito (Versión Urbana/Sky)', 5, 5, 2, 5, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/11/d6/58/11d658ed-2ee0-31bb-da65-3377b879f7fe/00602557543537.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview115/v4/0b/4b/39/0b4b3997-0a64-48b3-4975-1d17177171fd/mzaf_15227190121552250586.plus.aac.p.m4a', 1, '2026-08-18 16:10:26', '1445011795'),
(6, 'Faded', 6, 6, 4, 6, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music114/v4/0d/a3/1a/0da31af7-d0ff-9bee-c427-1b6d0336f6fc/886446321981.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview125/v4/f4/32/01/f43201b9-4bba-7654-2e43-d59e2d907e9f/mzaf_2440137894989713967.plus.aac.p.m4a', 1, '2026-08-18 16:10:58', '1196294581'),
(7, 'Heat Waves', 7, 7, 5, 4, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/da/8b/77/da8b7731-6f4f-eacf-5e74-8b23389eefa1/20UMGIM03371.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/a3/4c/b9/a34cb911-40fc-5f0c-e862-14bd171a77aa/mzaf_384792072030970151.plus.aac.p.m4a', 1, '2026-08-18 16:11:31', '1508562516'),
(8, 'Perfect', 8, 8, 6, 2, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music116/v4/9d/b9/11/9db9116b-4ad2-cd59-3081-ed5e6e8b702e/197746106612_HIGHEXPECTATIONSALBUM.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/ff/b8/13/ffb8131f-11de-eed1-aea2-030e0867427f/mzaf_85260234243207090.plus.aac.p.m4a', 1, '2026-08-18 16:14:18', '1701686177'),
(9, 'Someone Like You', 9, 9, 7, 2, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music221/v4/eb/ca/25/ebca2596-cd1e-b295-91a3-771c868d0a79/191404113868.png/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview125/v4/ef/18/7b/ef187b7d-f487-e935-4ca1-af5748313710/mzaf_8455263230305249048.plus.aac.p.m4a', 1, '2026-08-18 16:15:26', '1544491998'),
(10, 'Stay', 10, 10, 8, 7, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music221/v4/18/37/69/18376949-c4f9-18bd-0adf-7a473df55a7b/12UMGIM58533.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/f8/27/b7/f827b762-d86d-1d6e-c2dd-ba98fa8b574c/mzaf_4020411232386122765.plus.aac.p.m4a', 1, '2026-08-18 16:16:12', '1440812215'),
(11, 'Love Yourself', 11, 11, 9, 3, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music221/v4/34/77/dc/3477dc72-2ccc-379b-8add-6b157f03bc3d/4blaVeJ2ORpZN-milk-honey-original-copy-0150e75d.png/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/49/cd/1a/49cd1ac1-8179-e5e7-bb5f-5c08462796a8/mzaf_1897365263772777589.plus.aac.p.m4a', 1, '2026-08-18 16:16:45', '1639456638'),
(12, 'Love Yourself', 12, 12, 2, 8, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/86/ec/c3/86ecc317-1f4c-9764-8e2c-d04b8b512e75/00888072027411.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview115/v4/64/9a/6c/649a6c30-26f8-1741-f6e6-357affb99b4a/mzaf_3988125454515280293.plus.aac.p.m4a', 1, '2026-08-18 16:18:28', '1440946508'),
(13, 'Señorita', 13, 13, 3, 9, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music116/v4/e8/a8/b8/e8a8b807-6e32-c673-53f0-ea14f12e4d4e/19CRGIM16368.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/c2/0f/35/c20f35c7-9e12-6606-1781-c35bf9fa9fb7/mzaf_3639993648857320932.plus.aac.p.m4a', 1, '2026-08-18 16:19:11', '1486659261'),
(14, 'Lovely', 14, 14, 5, 10, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music211/v4/c2/e6/02/c2e602e1-22d1-ffdb-0036-23618db041ad/841254.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/36/d2/fa/36d2fad5-f447-b5b4-8666-31a437bb31c2/mzaf_7333479079227153596.plus.aac.p.m4a', 1, '2026-08-18 16:20:18', '1830673973'),
(15, 'As It Was', 15, 15, 3, 4, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/fc/73/03/fc73032d-d67d-ade3-8b9a-6f403fd9491b/886447495391.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/3c/cf/34/3ccf3416-c06e-589d-d439-2aaeb9792fdb/mzaf_199727677040658683.plus.aac.p.m4a', 1, '2026-08-18 16:21:20', '1448967245'),
(16, 'Counting Stars (2023 Version)', 16, 16, 6, 2, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music116/v4/57/fc/33/57fc33f8-d4ed-c333-711f-4ab73fd62a8c/23UM1IM00422.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/d1/5c/aa/d15caa4f-7e69-1bed-4a0a-20ed0b5dcc10/mzaf_10669816201875799471.plus.aac.p.m4a', 1, '2026-08-18 16:22:16', '1706433971'),
(17, 'Let Me Love You', 17, 17, 10, 3, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music112/v4/6b/77/72/6b777276-1f3d-8ecf-d9d2-06733e3d7dd3/828766188523.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/f6/94/30/f694308c-af1b-144b-ec68-6d97982e3a03/mzaf_14747022170193841577.plus.aac.p.m4a', 1, '2026-08-18 16:22:43', '257524515'),
(18, 'Bad Guy', 18, 18, 3, 7, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music124/v4/bf/27/b3/bf27b3d3-501f-7011-956b-7aa0072c2d13/859731587492_cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview115/v4/88/5e/ae/885eaec7-bd61-b8ce-a007-22ebcc72f8e7/mzaf_14362857026056701422.plus.aac.p.m4a', 1, '2026-08-18 16:23:16', '1457243723'),
(19, 'Sugar (feat. Francesco Yates)', 19, 19, 4, 6, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music7/v4/ff/0f/cf/ff0fcfac-848b-70b8-0da2-b16c133d18da/dj.yqavvtmw.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview112/v4/9e/f2/54/9ef254ba-c045-dce0-a435-7e9e46259f6d/mzaf_16865285240503046740.plus.aac.p.m4a', 1, '2026-08-18 16:23:37', '1017003801'),
(20, 'Dark Horse (feat. Juicy J)', 20, 20, 11, 2, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music221/v4/36/21/81/36218129-51b4-df22-cafb-8e9503b53147/13UAAIM70445.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/c7/80/b7/c780b757-f2e8-9e40-c5b9-dd28b5fe1296/mzaf_9911645771464051186.plus.aac.p.m4a', 1, '2026-08-18 16:23:57', '1440819170'),
(21, 'Until I Found You', 21, 21, 9, 11, 1, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/64/d2/c5/64d2c511-67f4-ae09-5153-d39c3da413a3/21UMGIM75467.rgb.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/53/82/c1/5382c1d4-ddba-aa2b-90df-57268895fac9/mzaf_8926201202931541051.plus.aac.p.m4a', 1, '2026-08-18 16:24:24', '1581702085'),
(22, 'Tera Mera Hai Pyar Amar (From \"Ishq Murshid\")', 22, 22, 6, 1, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music221/v4/39/52/37/395237e1-13d9-dc2c-5414-63662b33fcc7/859780676659.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview211/v4/2c/49/c2/2c49c25b-aaf9-a101-7d4e-c66d96c9db0d/mzaf_3964578430677614062.plus.aac.p.m4a', 1, '2026-08-18 16:36:59', '1854323162'),
(23, 'Khaani (Original Score)', 23, 23, 2, 12, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music115/v4/51/1b/b2/511bb224-440c-ea26-104b-24005c324748/859748685624_cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview125/v4/16/74/1f/16741f28-2e92-932f-ed9b-1c80469cea10/mzaf_9034585569761508058.plus.aac.p.m4a', 1, '2026-08-18 16:37:58', '1574289673'),
(24, 'Pasoori', 24, 24, 9, 2, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music116/v4/f3/f9/06/f3f906c3-79d5-ac9a-5fdd-262048f955f9/cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview126/v4/62/33/1e/62331ea8-d1df-027d-fe75-ac16a519323d/mzaf_14381883946572745360.plus.aac.p.m4a', 1, '2026-08-18 16:38:31', '1608356084'),
(25, 'Meri Zindagi Hai Tu', 25, 25, 12, 13, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music211/v4/39/49/d4/3949d4b2-63b0-e6f4-4fc1-ea122ef390db/859722614695_cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/48/f5/33/48f533db-8557-62c6-9ad6-012ebacd3234/mzaf_2439508553999966059.plus.aac.p.m4a', 1, '2026-08-18 16:40:06', '1852517390'),
(26, 'Without You I\'m Lost', 35, NULL, 12, 3, NULL, NULL, 'https://v.monophonic.digital/content/01KABW1Y7Z69YGWMQ13JH93GHH/1000x1000.jpg', '', 1, '2026-08-27 07:35:48', 'v69g1b8'),
(27, 'Meri Zindagi Hai Tu', 36, 27, 1, 15, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music126/v4/e7/d2/63/e7d26367-b835-68ac-7e1f-9701f5dad5bd/8902894361422_cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/70/16/31/7016311f-71ee-dbea-7fd6-331f8e053cf9/mzaf_18364273274420978024.plus.aac.p.m4a', 1, '2026-08-27 17:33:45', '1596876213');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`id`, `user_id`, `title`, `description`, `cover_image`, `is_public`, `created_at`) VALUES
(4, 1, 'third playlist', 'third playlist by me', '1787205306_images (1).png', 0, '2026-08-20 05:07:39'),
(5, 2, 'Mateen\\\'s Playlist', 'Mateen Playlist songs', '1787633594_phone-flat-black-color-rounded-vector-icon-symbol-drawn-light-gray-background-57876651.webp', 0, '2026-08-25 04:50:43'),
(6, 5, 'Rehman\\\'s Playlist', '', '1788025992_company 3 sample 2.jpg', 0, '2026-08-29 17:52:03');

-- --------------------------------------------------------

--
-- Table structure for table `playlist_items`
--

CREATE TABLE `playlist_items` (
  `id` int(11) NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `music_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlist_items`
--

INSERT INTO `playlist_items` (`id`, `playlist_id`, `music_id`, `added_at`) VALUES
(2, 4, 24, '2026-08-20 05:34:27'),
(3, 4, 23, '2026-08-20 05:34:41'),
(5, 4, 21, '2026-08-20 05:44:20'),
(6, 4, 22, '2026-08-20 06:10:01'),
(7, 4, 9, '2026-08-20 06:21:47'),
(8, 5, 15, '2026-08-25 04:51:29'),
(9, 5, 17, '2026-08-25 04:51:40'),
(10, 5, 14, '2026-08-25 04:52:13'),
(11, 4, 13, '2026-08-29 17:47:37'),
(12, 6, 24, '2026-08-29 17:52:13'),
(13, 6, 25, '2026-08-29 17:52:22'),
(14, 6, 23, '2026-08-29 17:52:23'),
(15, 6, 3, '2026-08-29 17:52:53'),
(16, 4, 26, '2026-09-01 07:19:50'),
(17, 4, 25, '2026-09-01 07:35:02'),
(18, 4, 6, '2026-09-01 07:41:15');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `music_id` int(11) DEFAULT NULL,
  `video_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `user_id`, `music_id`, `video_id`, `rating`) VALUES
(3, 1, 24, NULL, 4),
(4, 1, 19, NULL, 4),
(5, 1, NULL, 2, 1),
(6, 1, 16, NULL, 4),
(7, 1, NULL, 7, 2),
(8, 1, NULL, 5, 4),
(9, 1, 23, NULL, 2),
(10, 1, 25, NULL, 3),
(11, 1, 3, NULL, 3),
(12, 1, NULL, 10, 1),
(13, 1, 21, NULL, 3),
(14, 4, 19, NULL, 3),
(15, 1, 17, NULL, 3),
(16, 7, 25, NULL, 4);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `music_id` int(11) DEFAULT NULL,
  `video_id` int(11) DEFAULT NULL,
  `review` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `music_id`, `video_id`, `review`, `created_at`, `updated_at`) VALUES
(1, 1, 24, NULL, 'good', '2026-08-18 17:56:05', '2026-08-18 17:56:05'),
(2, 1, 19, NULL, 'goog', '2026-08-23 05:15:12', '2026-08-23 05:15:12'),
(3, 1, NULL, 2, 'nice', '2026-08-23 06:10:20', '2026-08-23 06:10:20'),
(4, 1, 16, NULL, 'Asim is good boy', '2026-08-24 04:13:49', '2026-08-24 04:13:49'),
(5, 1, NULL, 7, 'nice', '2026-08-24 04:15:11', '2026-08-24 04:15:11'),
(6, 1, NULL, 5, 'good vidios', '2026-08-25 03:22:10', '2026-08-27 19:09:00'),
(7, 1, 23, NULL, 'good', '2026-08-26 04:33:34', '2026-08-26 04:33:34'),
(8, 1, 25, NULL, 'nice song', '2026-08-26 17:04:07', '2026-08-26 17:04:07'),
(9, 1, 3, NULL, 'Nice Songs by weekend', '2026-08-26 17:14:15', '2026-08-26 17:14:15'),
(10, 1, NULL, 10, 'Seedho Mosa wala', '2026-08-26 17:15:16', '2026-08-26 17:15:16'),
(11, 1, 21, NULL, 'Excellent goodddd', '2026-08-28 18:52:41', '2026-08-29 17:17:40'),
(12, 4, 19, NULL, 'nice', '2026-08-28 19:00:01', '2026-08-28 19:00:01'),
(13, 1, 17, NULL, 'nice stay', '2026-08-29 18:16:27', '2026-08-29 18:17:23'),
(14, 7, 25, NULL, 'good', '2026-08-31 06:39:34', '2026-08-31 06:39:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `name`, `address`, `phone`, `email`, `password`, `role`, `created_at`, `profile_image`) VALUES
(1, '1', 'Muhammad Ali', 'Malir cantt Karachi', '03132002769', 'alinarejo18@gmail.com', '$2y$10$MzPpY7E52bSIlTCT7M9ub.7qxzGehn0Fivr0E/K/c2Dmf20uhQtqS', 'user', '2026-08-18 17:55:30', '1787764589_Ali20.jpg'),
(2, '2', 'Mateen', 'Malir cantt Karachi', '03132098778', 'mateen@gmail.com', '$2y$10$vmTv0yBop8TnY4OHXILKLuueUp6octByoOsQiWJc9K98WZYn.IVb.', 'user', '2026-08-25 04:06:42', '1787630865_ali-img.jfif'),
(3, '3', 'Asim', 'Malir cantt Karachi', '031328899', 'asim@gmail.com', '$2y$10$kimF9.DKs.EkbSd04LzHWOzqMQ6W0wG3AMUz8R1VZu7gtt0zEBVby', 'user', '2026-08-28 17:33:16', NULL),
(4, '465721', 'Babar Azam', 'Karachi Sindh', '034566723498', 'babarazam@gmail.com', '$2y$10$uLezbM.riOUU/VcNNOAINO0GWsMwTEnz/.J2QTZWhrc/cSk2iK8F.', 'user', '2026-08-28 17:38:45', NULL),
(5, 'USER_6a931b9975473', 'Abdul Rehman', 'Sanghar', '031612244537', 'abdulrehman1521596@gmail.com', '$2y$10$8u.GLBxkn2eZbCuRXM8TJO6DYOrAe64NXMz/19LrZ7IahRV1ih5he', 'user', '2026-08-29 17:49:13', '1788025866_company 3 sample 1.jpg'),
(6, 'USER_6a931ce4ec458', 'Amna', 'Karchi', '0987765434', 'amna@gmail.com', '$2y$10$iSaVn.mP2fq4T75q76jCpegtPCkAdIAuwbSCoWhRdBhHWYG2mPAMC', 'user', '2026-08-29 17:54:44', NULL),
(7, 'USER_6a95217fa2c08', 'fahad', 'Karachi', '03435637838', 'fahad@gmail.com', '$2y$10$Rh.sW7XrtxrdI6NxK6ph5ODVEv.YpVYb1OkgVdfMiPozkZafotGim', 'user', '2026-08-31 06:38:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `artist_id` int(11) DEFAULT NULL,
  `album_id` int(11) DEFAULT NULL,
  `year_id` int(11) DEFAULT NULL,
  `genre_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `artist_id`, `album_id`, `year_id`, `genre_id`, `language_id`, `description`, `image`, `video_file`, `is_new`, `created_at`) VALUES
(2, 'OST (feat. Chanmina)', 27, NULL, 13, 14, NULL, NULL, 'import_1757355738.jpg', 'import_1757355738.m4v', 1, '2026-08-23 06:02:55'),
(3, 'Shape of You', 2, NULL, 2, 2, NULL, NULL, 'import_1201244847.jpg', 'import_1201244847.m4v', 1, '2026-08-23 16:42:28'),
(4, 'Blinding Lights', 28, NULL, 13, 7, NULL, NULL, 'import_1743852044.jpg', 'import_1743852044.m4v', 1, '2026-08-23 16:44:49'),
(5, 'Believer', 4, NULL, 2, 4, NULL, NULL, 'import_1445059746.jpg', 'import_1445059746.m4v', 1, '2026-08-23 16:47:56'),
(6, 'Miss You', 29, 26, 14, 10, NULL, NULL, 'import_1441151781.jpg', 'import_1441151781.m4v', 1, '2026-08-23 16:49:46'),
(7, 'Aadat (Junoon)', 30, NULL, 12, 2, NULL, NULL, 'import_1847744163.jpg', 'import_1847744163.m4v', 1, '2026-08-23 16:52:36'),
(8, 'Channa Mereya (From \"Ae Dil Hai Mushkil\") [Lyric Video]', 31, NULL, 15, 15, NULL, NULL, 'import_1564639021.jpg', 'import_1564639021.m4v', 1, '2026-08-23 16:54:31'),
(9, 'Dil Diyan Gallan  (Lyric Video)', 32, NULL, 12, 16, NULL, NULL, 'import_1829532738.jpg', 'import_1829532738.m4v', 1, '2026-08-23 16:57:36'),
(10, 'Same Beef', 33, NULL, 3, 17, NULL, 'talk about its genre, vibe, instruments, rhythm, and lyrics, while sharing how it makes you feel.', 'import_1476968505.jpg', 'import_1476968505.m4v', 1, '2026-08-25 04:14:10'),
(11, 'Ek Dil Ek Arman Pakistan  (feat. Wania Tazeen) (Visualizer)', 34, NULL, 16, 4, NULL, NULL, 'import_6801823424.jpg', 'import_6801823424.m4v', 1, '2026-08-27 06:11:18'),
(12, 'Meri Zindagi Hai Tu', 25, 25, 12, 13, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music211/v4/39/49/d4/3949d4b2-63b0-e6f4-4fc1-ea122ef390db/859722614695_cover.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview221/v4/48/f5/33/48f533db-8557-62c6-9ad6-012ebacd3234/mzaf_2439508553999966059.plus.aac.p.m4a', 1, '2026-08-27 17:52:03'),
(13, 'Tere Bin (Original Score)', 37, 28, 9, 2, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Music116/v4/c1/d6/f3/c1d6f3a4-39ae-5e52-c3ea-034f7f32eef2/artwork.jpg/600x600bb.jpg', 'https://audio-ssl.itunes.apple.com/itunes-assets/AudioPreview116/v4/7b/d7/8c/7bd78c40-d0e3-ab67-7933-97760ccf294d/mzaf_8785189303504585034.plus.aac.p.m4a', 1, '2026-08-27 18:00:27'),
(15, 'Happy', 38, NULL, 4, 2, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Video3/v4/a5/be/0d/a5be0d40-16c3-e34e-2eb0-ef378c1f79d0/GB1301500002.sca1.jpg/600x600bb.jpg', 'itunes-video:https://video-ssl.itunes.apple.com/itunes-assets/Video114/v4/93/fe/15/93fe15c9-828d-8905-05ab-86653406bc44/mzvf_2520379025727613509.1920w.h264lc.U.p.m4v', 1, '2026-08-27 19:06:15'),
(16, 'Teri Galiyan (Official Romantic Video Song)  Heart-touching Hindi Love Story  4K', 39, NULL, 12, 6, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Video221/v4/92/e9/c0/92e9c08b-2f29-a62d-7afc-d7cb6326f9e4/Jobbb1ba85c-a86e-4921-bc08-c45b6e1a1ccb-200822300-PreviewImage_preview_image_45000_video_sdr-Time1755801512310.png/600x600bb.jpg', 'itunes-video:https://video-ssl.itunes.apple.com/itunes-assets/Video211/v4/0e/6f/ec/0e6fec91-9adf-cc82-06ba-5df04935361c/mzvf_15115610457098467426.1920w.h264lc.U.p.m4v', 1, '2026-08-28 19:26:44'),
(17, 'Mere Mehboob Qayamat Hogi  Romantic Hindi Love Song', 39, NULL, 12, 6, NULL, NULL, 'https://is1-ssl.mzstatic.com/image/thumb/Video211/v4/8a/a8/ed/8aa8ed55-39ae-2d19-639d-45ebc8640b67/Job4d5457d2-238b-4d61-a787-f6fa6990e6ef-201720379-PreviewImage_preview_image_45000_video_sdr-Time1756679623865.png/600x600bb.jpg', 'itunes-video:https://video-ssl.itunes.apple.com/itunes-assets/Video211/v4/94/cd/d1/94cdd124-0200-8c6d-dd70-d7afc3c9eba4/mzvf_5152289262097057937.1920w.h264lc.U.p.m4v', 1, '2026-08-28 19:26:44');

-- --------------------------------------------------------

--
-- Table structure for table `website_info`
--

CREATE TABLE `website_info` (
  `id` int(11) NOT NULL,
  `site_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `years`
--

CREATE TABLE `years` (
  `id` int(11) NOT NULL,
  `year_value` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `years`
--

INSERT INTO `years` (`id`, `year_value`) VALUES
(14, '1978'),
(10, '2004'),
(7, '2011'),
(8, '2012'),
(11, '2013'),
(4, '2015'),
(15, '2016'),
(2, '2017'),
(3, '2019'),
(5, '2020'),
(1, '2021'),
(9, '2022'),
(6, '2023'),
(13, '2024'),
(12, '2025'),
(16, '2026');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist_id` (`artist_id`);

--
-- Indexes for table `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `artist_name` (`artist_name`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `genre_name` (`genre_name`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `language_name` (`language_name`);

--
-- Indexes for table `music`
--
ALTER TABLE `music`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `source_track_id` (`source_track_id`),
  ADD KEY `artist_id` (`artist_id`),
  ADD KEY `album_id` (`album_id`),
  ADD KEY `year_id` (`year_id`),
  ADD KEY `genre_id` (`genre_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_playlist_song` (`playlist_id`,`music_id`),
  ADD KEY `idx_playlist_items_music_id` (`music_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rating_user` (`user_id`),
  ADD KEY `fk_rating_music` (`music_id`),
  ADD KEY `fk_rating_video` (`video_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `music_id` (`music_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist_id` (`artist_id`),
  ADD KEY `album_id` (`album_id`),
  ADD KEY `year_id` (`year_id`),
  ADD KEY `genre_id` (`genre_id`),
  ADD KEY `language_id` (`language_id`);

--
-- Indexes for table `website_info`
--
ALTER TABLE `website_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `years`
--
ALTER TABLE `years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_value` (`year_value`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `artists`
--
ALTER TABLE `artists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `music`
--
ALTER TABLE `music`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `playlist_items`
--
ALTER TABLE `playlist_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `website_info`
--
ALTER TABLE `website_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `years`
--
ALTER TABLE `years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `music`
--
ALTER TABLE `music`
  ADD CONSTRAINT `music_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `music_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `music_ibfk_3` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `music_ibfk_4` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `music_ibfk_5` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_rating_music` FOREIGN KEY (`music_id`) REFERENCES `music` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rating_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rating_video` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`music_id`) REFERENCES `music` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `videos_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `videos_ibfk_3` FOREIGN KEY (`year_id`) REFERENCES `years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `videos_ibfk_4` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `videos_ibfk_5` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
