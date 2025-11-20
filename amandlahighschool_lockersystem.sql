-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 01:42 AM
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
-- Database: `amandlahighschool_lockersystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrator`
--

CREATE TABLE `administrator` (
  `AdminID` int(11) NOT NULL,
  `AdminName` varchar(100) DEFAULT NULL,
  `AdminSurname` varchar(100) DEFAULT NULL,
  `AdminEmail` varchar(100) DEFAULT NULL,
  `AdminPassword` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrator`
--

INSERT INTO `administrator` (`AdminID`, `AdminName`, `AdminSurname`, `AdminEmail`, `AdminPassword`) VALUES
(1, 'Amanda', 'Ford', 'amandlahighschoollockersystem1@gmail.com', '801549#'),
(2, 'Sarah', 'Gibson', 'amandlahighschoollockersystem2@gmail.com', '903157}');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` varchar(100) NOT NULL,
  `StudentSchoolNumber` int(11) DEFAULT NULL,
  `ParentID` bigint(20) DEFAULT NULL,
  `BookedOnDate` date NOT NULL DEFAULT curdate(),
  `BookedForDate` date NOT NULL,
  `Status` varchar(100) NOT NULL,
  `CreatedByAdminID` int(11) DEFAULT NULL,
  `LockerID` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`BookingID`, `StudentSchoolNumber`, `ParentID`, `BookedOnDate`, `BookedForDate`, `Status`, `CreatedByAdminID`, `LockerID`) VALUES
('B1', 321908, 7608153221084, '2025-11-06', '2026-01-19', 'Allocated', NULL, 'L11-ROW B'),
('B10', 652908, 8804185209089, '2025-11-10', '2026-03-11', 'Allocated', NULL, 'L52-ROW F'),
('B11', 239170, 6507115698088, '2025-11-14', '2026-02-09', 'Allocated', NULL, 'L13-ROW B'),
('B12', 621908, 6703197213084, '2025-11-14', '2026-03-02', 'Allocated', NULL, 'L31-ROW D'),
('B13', 712908, 8906185498082, '2025-11-15', '2026-05-04', 'Allocated', NULL, 'L15-ROW B'),
('B14', 139078, 9012185498087, '2025-11-15', '2026-06-02', 'Allocated', NULL, 'L1-ROW A'),
('B15', 619873, 6903173216088, '2025-11-16', '2026-05-11', 'Allocated', NULL, 'L54-ROW F'),
('B16', 710964, 8212158396087, '2025-11-16', '2026-02-24', 'Allocated', NULL, 'L3-ROW A'),
('B17', 419652, 9710116298083, '2025-11-17', '2026-06-01', 'Allocated', 2, 'L76-ROW H'),
('B18', 372162, 8806113921085, '2025-11-17', '2026-06-01', 'Allocated', NULL, 'L32-ROW D'),
('B19', 421639, 9409168497083, '2025-11-17', '2026-05-11', 'Allocated', NULL, 'L53-ROW F'),
('B2', 490128, 8612051392084, '2025-11-06', '2026-01-19', 'Allocated', NULL, NULL),
('B20', 192317, 2309183217084, '2025-11-17', '2026-03-23', 'Allocated', 2, 'L55-ROW F'),
('B21', 603268, 6304187924088, '2025-11-17', '2026-04-13', 'Allocated', 2, 'L33-ROW D'),
('B22', 612729, 7609273569089, '2025-11-18', '2026-05-11', 'Allocated', NULL, 'L14-ROW B'),
('B23', 503146, 9103216894086, '2025-11-19', '2026-02-23', 'Allocated', 2, 'L56-ROW F'),
('B24', 102349, 9203185496084, '2025-11-19', '2026-01-26', 'Allocated', NULL, 'L10-ROW A\r\n'),
('B25', 213789, 7405114908087, '2025-11-19', '2026-03-03', 'Waiting', NULL, NULL),
('B26', 302681, 5911123976088, '2025-11-19', '2026-02-16', 'Waiting', NULL, NULL),
('B27', 102350, 9203185496084, '2025-11-19', '2026-01-12', 'Allocated', 2, 'L16-ROW B\r\n\r\n'),
('B28', 416532, 5911123976088, '2025-11-19', '2026-02-16', 'Allocated', 2, 'L3-ROW A\r\n'),
('B29', 962513, 7405114908087, '2025-11-19', '2026-02-24', 'Allocated', 2, 'L57-ROW F'),
('B3', 796234, 8509165792086, '2025-11-06', '2026-01-28', 'Allocated', NULL, NULL),
('B30', 407682, 8910285473086, '2025-11-19', '2026-04-21', 'Allocated', NULL, 'L77-ROW H'),
('B31', 407683, 8910285473086, '2025-11-20', '2026-03-02', 'Allocated', 2, 'L34-ROW D'),
('B4', 321789, 9905152039080, '2025-11-07', '2026-01-28', 'Allocated', NULL, NULL),
('B5', 910983, 4109137427178, '2025-11-07', '2026-01-27', 'Allocated', NULL, NULL),
('B6', 589721, 7908165489089, '2025-11-08', '2026-02-09', 'Allocated', NULL, NULL),
('B7', 852907, 8105145069088, '2025-11-08', '2026-03-02', 'Allocated', NULL, 'L75-ROW H'),
('B9', 811764, 8805123427084, '2025-11-10', '2026-03-23', 'Allocated', NULL, 'L12-ROW B');

-- --------------------------------------------------------

--
-- Table structure for table `lockers`
--

CREATE TABLE `lockers` (
  `LockerID` varchar(100) NOT NULL,
  `LockerGrade` tinyint(3) UNSIGNED DEFAULT NULL,
  `Status` enum('Available','Allocated','Maintenance') DEFAULT 'Available',
  `StudentSchoolNumber` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lockers`
--

INSERT INTO `lockers` (`LockerID`, `LockerGrade`, `Status`, `StudentSchoolNumber`) VALUES
('L1 - ROW A', 8, 'Allocated', 139078),
('L10-ROW A\r\n', 8, 'Allocated', 102349),
('L11-ROW B\r\n', 9, 'Allocated', 321908),
('L12-ROW B\r\n', 9, 'Allocated', 811764),
('L13-ROW B\r\n', 9, 'Allocated', 239170),
('L14-ROW B', 9, 'Allocated', 612729),
('L15-ROW B\r\n', 9, 'Allocated', 712908),
('L16-ROW B\r\n\r\n', 9, 'Allocated', 102350),
('L17-ROW B', 9, 'Available', NULL),
('L18-ROW B\r\n', 9, 'Available', NULL),
('L19-ROW B\r\n', 9, 'Available', NULL),
('L2-ROW A', 8, 'Allocated', 416532),
('L20-ROW B', 9, 'Available', NULL),
('L21-ROW C', 9, 'Available', NULL),
('L22-ROW C', 9, 'Available', NULL),
('L23-ROW C', 9, 'Available', NULL),
('L24-ROW C', 9, 'Available', NULL),
('L25-ROW C\r\n', 9, 'Available', NULL),
('L26-ROW C', 9, 'Available', NULL),
('L27-ROW C', 9, 'Available', NULL),
('L28-ROW C', 9, 'Available', NULL),
('L29-ROW C', 9, 'Available', NULL),
('L3-ROW A\r\n', 8, 'Allocated', 416532),
('L30-ROW C', 9, 'Available', NULL),
('L31-ROW D', 10, 'Allocated', 621908),
('L32-ROW D', 10, 'Allocated', 372162),
('L33-ROW D', 10, 'Allocated', 603268),
('L34-ROW D', 10, 'Allocated', 407683),
('L35-ROW D', 10, 'Available', NULL),
('L36-ROW D', 10, 'Available', NULL),
('L37-ROW D', 10, 'Available', NULL),
('L38-ROW D', 10, 'Available', NULL),
('L39-ROW D', 10, 'Available', NULL),
('L4-ROW A\r\n', 8, 'Available', NULL),
('L40-ROW D', 10, 'Available', NULL),
('L41-ROW E', 10, 'Available', NULL),
('L42-ROW E\r\n', 10, 'Available', NULL),
('L43-ROW E', 10, 'Available', NULL),
('L44-ROW E', 10, 'Available', NULL),
('L45-ROW E', 10, 'Available', NULL),
('L46-ROW E', 10, 'Available', NULL),
('L47-ROW E', 10, 'Available', NULL),
('L48-ROW E', 10, 'Available', NULL),
('L49-ROW E', 10, 'Available', NULL),
('L5-ROW A\r\n', 8, 'Available', NULL),
('L50-ROW E', 10, 'Available', NULL),
('L51-ROW E', 10, 'Available', NULL),
('L52-ROW F', 11, 'Allocated', 652908),
('L53-ROW F', 11, 'Allocated', 421639),
('L54-ROW F', 11, 'Allocated', 619873),
('L55-ROW F', 11, 'Allocated', 192317),
('L56-ROW F', 11, 'Allocated', 503146),
('L57-ROW F', 11, 'Allocated', 962513),
('L58-ROW F', 11, 'Available', NULL),
('L59-ROW F', 11, 'Available', NULL),
('L6- ROW A\r\n', 8, 'Available', NULL),
('L60-ROW F', 11, 'Available', NULL),
('L61-ROW F', 11, 'Available', NULL),
('L62-ROW F', 10, 'Available', NULL),
('L63-ROW G', 11, 'Available', NULL),
('L64-ROW G', 11, 'Available', NULL),
('L65-ROW G', 11, 'Available', NULL),
('L66-ROW G', 11, 'Available', NULL),
('L67-ROW G', 11, 'Available', NULL),
('L68-ROW G', 11, 'Available', NULL),
('L69-ROW G', 11, 'Available', NULL),
('L7-ROW A\r\n', 8, 'Available', NULL),
('L70-ROW G', 11, 'Available', NULL),
('L71-ROW G', 11, 'Available', NULL),
('L72-ROW G', 11, 'Available', NULL),
('L73-ROW G', 11, 'Available', NULL),
('L74-ROW G', 11, 'Available', NULL),
('L75-ROW H', 12, 'Allocated', 852907),
('L76-ROW H', 12, 'Allocated', 419652),
('L77-ROW H', 12, 'Allocated', 407682),
('L78-ROW H', 12, 'Available', NULL),
('L79-ROW H', 12, 'Available', NULL),
('L8-ROW A\r\n', 8, 'Available', NULL),
('L9-ROW A\r\n', 8, 'Available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `ParentID` bigint(20) NOT NULL,
  `ParentTitle` varchar(100) DEFAULT NULL,
  `ParentName` varchar(100) DEFAULT NULL,
  `ParentSurname` varchar(100) DEFAULT NULL,
  `ParentEmail` varchar(100) DEFAULT NULL,
  `ParentHomeAddress` varchar(100) DEFAULT NULL,
  `ParentPhoneNumber` int(11) DEFAULT NULL,
  `Password` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`ParentID`, `ParentTitle`, `ParentName`, `ParentSurname`, `ParentEmail`, `ParentHomeAddress`, `ParentPhoneNumber`, `Password`) VALUES
(1008113111123, 'Mr', 'Ethan', 'Wilson', 'ethan.wilson@gmail.com', '636 Cedar Street', 737248373, 'Pass@1'),
(2020195031968, 'Ms', 'Jane', 'Taylor', 'jane.taylor@gmail.com', '911 Ash Street', 781998910, 'Pass@2'),
(2309183217084, 'Mrs', 'Emery', 'Daniels', 'abigailmichaels350@gmail.com', '675 Hildagaardt Drive,Norwood', 786541289, 'Pass@32'),
(3018701182456, 'Mrs', 'Mary', 'Johnson', 'mary.johnson@gmail.com', '456 Oak Ave', 832345678, 'Pass@3'),
(3060525411423, 'Dr', 'Emma', 'Anderson', 'emma.anderson@gmail.com', '704 Willow Street', 721206388, 'Pass@4'),
(4108192658003, 'Mrs', 'Olivia', 'Anderson', 'olivia.anderson@gmail.com', '404 Cedar Street', 714962168, 'Pass@5'),
(4109137427178, 'Mr', 'James', 'Thomas', 'abigailmichaels350@gmail.com', '246 Redwood Court', 789012345, 'Pass@6'),
(5004038523192, 'Ms', 'Linda', 'Anderson', 'abigailmichaels350@gmail.com', '135 Spruce Street', 778901234, 'Pass@7'),
(5612226663035, 'Mr', 'Robert', 'Wilson', 'abigailmichaels350@gmail.com', '189 Pine Street', 735460310, 'Pass@8'),
(5911123976088, 'Mr', 'Duresh', 'Reddy', 'abigailmichaels350@gmail.com', '67 Green Street, Norwood', 786541230, 'Pass@39'),
(6304187924088, 'Mr', 'Jonathan', 'Kramer', 'abigailmichaels350@gmail.com', '789 Spring Street,Broadacres', 786543009, 'Pass@33'),
(6507115698088, 'Mr', 'Lester', 'James', 'abigailmichaels350@gmail.com', '78 Silverstone Road, Broadacres', 786541290, 'Pass@23'),
(6703197213084, 'Mr', 'Graham', 'Seale', 'abigailmichaels350@gmail.com', '923 Marine Drive, Summerstrand', 675431290, 'Pass@24'),
(6711189204048, 'Mr', 'Ethan', 'Nkosi', 'mrethan.nkosi@gmail.com', '382 Elm Street', 738510987, 'P91287%'),
(6806020888197, 'Mr', 'Michael', 'Nkosi', 'michael.nkosi@gmail.com', '897 Cedar Street', 798801632, 'P64137%'),
(6903173216088, 'Dr', 'Berenice', 'Philips', 'abigailmichaels350@gmail.com', '78 Mountview Drive, Malabar', 786551290, 'Pass@27'),
(7009119979038, 'Mrs', 'Ava', 'Wilson', 'ava.wilson@gmail.com', '911 Birch Street', 764281373, 'P97634^'),
(7110115033116, 'Mr', 'John', 'Smith', 'john.smith@gmail.com', '123 Maple Street', 821234567, 'P23456@'),
(7303154126003, 'Dr', 'Ethan', 'Taylor', 'ethan.taylor@gmail.com', '404 Oak Street', 799314784, 'P92365*'),
(7405114908087, 'Mr', 'Tshepo', 'Majola', 'abigailmichaels350@gmail.com', '569 Worracker Road, Norwood', 785664312, 'Pass@37'),
(7501236921081, 'Mrs', 'Dianne', 'Beaton', 'abigailmichaels350@gmail.com', '78 Cessna Road, Eldorado Park', 876541230, 'Pass@9'),
(7608153221084, 'Ms', 'Sameerah', 'Solomons', 'abigailmichaels350@gmail.com', '78 Glenningdale Avenue , Parktown', 786541290, 'Pass@10'),
(7609273569089, 'Mrs', 'Celine', 'Hart', 'abigailmichaels350@gmail.com', '567 Rhode Drive, Sunninghill', 786541289, 'Pass@34'),
(7807275046086, 'Dr', 'Liam', 'Brown', 'liam.brown@gmail.com', '492 Spruce Street', 786828544, 'P27854='),
(7908165489089, 'Mrs', 'Deidre', 'Klate', 'abigailmichaels350@gmail.com', '678 Strand Street, Rosebank', 789651234, 'Pass@17'),
(8090359780981, 'Mr', 'John', 'Smith', 'john.smith134@gmail.com', '140 Maple Street', 775223322, 'P60421£'),
(8105145069088, 'Mr', 'Dean', 'VanWyk', 'abigailmichaels350@gmail.com', '908 Cape Road, Norwood', 781235672, 'Pass@18'),
(8212158396087, 'Mr', 'Daniel', 'Jacobs', 'abigailmichaels350@gmail.com', '890 Marine Drive, Summerstrand', 786751234, 'Pass@28'),
(8509165792086, 'Mr', 'Faizel ', 'Agherdien', 'abigailmichaels350@gmail.com', '65 Jewel Street, Sandown', 786542319, 'Pass@11'),
(8612051392084, 'Ms', 'Jerusha', 'Pillay', 'abigailmichaels350@gmail.com', '321 Spine Road , Norwood', 783225678, 'Pass@12'),
(8804185209089, 'Mr', 'Quinton', 'Brooks', 'abigailmichaels350@gmail.com', '89 Brown Street, Ormonde', 786543210, 'Pass@21'),
(8805123427084, 'Ms', 'Leigh', 'Claasen', 'abigailmichaels350@gmail.com', '78 Marine Drive, Summerstrand', 762348965, 'Pass@20'),
(8806113921085, 'Ms', 'Lynn', 'Green', 'abigailmichaels350@gmail.com', '678 Bloom Drive , Kilarney', 789065432, 'Pass@30'),
(8906185498082, 'Mrs', 'Jillene', 'Steele', 'abigailmichaels350@gmail.com', '786 Pratt Street, Broadacres', 765429087, 'Pass@25'),
(8910285473086, 'Mrs', 'Taahira', 'Abrahams', 'abigailmichaels350@gmail.com', '2010 Marine Drive,Summerstrand', 678907654, 'Pass@40'),
(9012185498087, 'Mrs', 'Verushka', 'Patel', 'abigailmichaels350@gmail.com', '89 Smythe Avenue,Linden', 765438907, 'Pass@26'),
(9103216894086, 'Mr', 'Johan', 'Van Wyk', 'abigailmichaels350@gmail.com', '789 Marine Drive,Summerstrand', 675431290, 'Pass@35'),
(9108286769008, 'Mr', 'Noah', 'Taylor', 'noah.taylor@gmail.com', '566 Ash Street', 787273081, 'P16590%'),
(9111216984084, 'Mr', 'Lionel', 'Jones', 'lioneljones@gmail.com', '68 Bird Street', 675332890, 'Pass@13'),
(9122667840156, 'Mr', 'Noah', 'Nkosi', 'noah.nkosi@gmail.com', '353 Oak Street', 763407066, 'P90546&'),
(9203185496084, 'Mrs', 'Anna', 'Joubert', 'abigailmichaels350@gmail.com', '780 Peck Road,Ormonde', 786528907, 'Pass@36'),
(9409168497083, 'Mr', 'Ronald', 'Brown', 'abigailmichaels350@gmail.com', '678 Voortrekker Road, Roodepoort', 786541290, 'Pass@31'),
(9503192107085, 'Mr', 'Areeb', 'Braaf', 'abigailmichaels350@gmail.com', '56 Brown Street,Sandton', 786531468, 'Pass@14'),
(9503207652085, 'Mrs', 'Fatima', 'Monsoor', 'apadayachy630@gmail.com', '38 Adams Drive', 788229946, 'Pass@15'),
(9710116298083, 'Mr', 'Shaheem', 'Dolley', 'abigailmichaels350@gmail.com', '67 Missouri Drive,Eldorado Park', 765437219, 'Pass@29'),
(9804175689084, 'Mrs', 'Sandra', 'Loggenberg', 'abigailmichaels350@gmail.com', '89 Greenstone Road, Roodepoort', 786541239, 'Pass@19'),
(9905152039080, 'Dr', 'Denzil', 'Petrus', 'abigailmichaels350@gmail.com', '78 Green Street, Sandown', 78650311, 'Pass@16');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentID` varchar(50) NOT NULL,
  `StudentSchoolNumber` int(11) NOT NULL,
  `BookingID` varchar(100) DEFAULT NULL,
  `PaymentAmount` decimal(10,2) DEFAULT NULL,
  `PaymentDate` date DEFAULT NULL,
  `PaymentStatus` varchar(20) NOT NULL,
  `ProofOfPaymentFile` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`PaymentID`, `StudentSchoolNumber`, `BookingID`, `PaymentAmount`, `PaymentDate`, `PaymentStatus`, `ProofOfPaymentFile`) VALUES
('P1', 321908, 'B1', 100.00, '2025-11-07', 'Verified', 'uploads/payments/payment_B1_1762421803.pdf'),
('P10', 419652, 'B17', 100.00, '2025-11-17', 'Verified', 'proof_B17.pdf'),
('P11', 0, 'B17', 100.00, '2025-11-17', 'Rejected', 'proof_B17.pdf'),
('P12', 372162, 'B18', 100.00, '2025-11-17', 'Verified', 'proof_B18.pdf'),
('P13', 421639, 'B19', 100.00, '2025-11-17', 'Verified', 'proof_B19.pdf'),
('P14', 192317, 'B20', 100.00, '2025-11-17', 'Verified', 'proof_B20.pdf'),
('P15', 603268, 'B21', 100.00, '2025-11-17', 'Verified', 'proof_B21.pdf'),
('P16', 612729, 'B22', 100.00, '2025-11-18', 'Verified', 'proof_B22.pdf'),
('P17', 503146, 'B23', 100.00, '2025-11-19', 'Verified', 'proof_B23.pdf'),
('P18', 416532, 'B28', 100.00, '2025-11-19', 'Verified', 'proof_B28.pdf'),
('P19', 962513, 'B29', 100.00, '2025-11-19', 'Verified', 'proof_B29.pdf'),
('P2', 852907, 'B7', 100.00, '2025-11-08', 'Verified', '1762637095_PROOF OF PAYMENT.pdf'),
('P20', 407682, 'B30', 100.00, '2025-11-19', 'Verified', 'proof_B30.pdf'),
('P21', 407682, 'B30', 100.00, '2025-11-19', 'Rejected', 'proof_B30.pdf'),
('P22', 407683, 'B31', 100.00, '2025-11-20', 'Verified', 'proof_B31.pdf'),
('P3', 811764, 'B9', 100.00, '2025-11-14', 'Verified', 'proof_691119fdf2f867.03821743.pdf'),
('P4', 239170, 'B11', 100.00, '2025-11-14', 'Verified', 'pay_6916f425a9de37.24149842'),
('P5', 621908, 'B12', 100.00, '2025-11-14', 'Verified', 'proof_B12.pdf'),
('P6', 712908, 'B13', 100.00, '2025-11-15', 'Verified', 'proof_B13.pdf'),
('P7', 139078, 'B14', 100.00, '2025-11-15', 'Verified', 'proof_B14.pdf'),
('P8', 619873, 'B15', 100.00, '2025-11-16', 'Verified', 'proof_B15.pdf'),
('P9', 710964, 'B16', 100.00, '2025-11-16', 'Verified', 'proof_B16.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `StudentSchoolNumber` int(11) NOT NULL,
  `StudentName` varchar(100) DEFAULT NULL,
  `StudentSurname` varchar(100) DEFAULT NULL,
  `StudentGrade` varchar(100) DEFAULT NULL,
  `ParentID` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`StudentSchoolNumber`, `StudentName`, `StudentSurname`, `StudentGrade`, `ParentID`) VALUES
(102349, 'Minke', 'Joubert', 'Grade 8', 9203185496084),
(102350, 'Kobus', 'Joubert', 'Grade 9', 9203185496084),
(123456, 'Emma', 'Smith', 'Grade 9', 7110115033116),
(139078, 'Jehdene', 'Patel', 'Grade 8', 9012185498087),
(192317, 'Saskia', 'Daniels', 'Grade 11', 2309183217084),
(200443, 'Ava', 'Anderson', 'Grade 11', 5004038523192),
(205637, 'Caleb', 'Beaton', 'Grade 9', 7501236921081),
(207541, 'Ameerah', 'Monsoor', 'Grade 9', 9503207652085),
(213789, 'Asanda', 'Majola', 'Grade 9', 7405114908087),
(239170, 'Judah', 'James', 'Grade 9', 6507115698088),
(248256, 'Liam', 'Nkosi', 'Grade 10', 6711189204048),
(277788, 'Olivia', 'Nkosi', 'Grade 10', 9122667840156),
(287868, 'David', 'Nkosi', 'Grade 8', 6806020888197),
(302681, 'Rajesh', 'Reddy', 'Grade 11', 5911123976088),
(321789, 'Grayson', 'Petrus', 'Grade 9', 9905152039080),
(321908, 'Natheerah', 'Solomons', 'Grade 9', 7608153221084),
(357874, 'Liam', 'Johnson', 'Grade 8', 3018701182456),
(366289, 'Liam', 'Anderson', 'Grade 9', 4108192658003),
(372162, 'Mia', 'Green', 'Grade 10', 8806113921085),
(390412, 'James', 'Wilson', 'Grade 9', 5612226663035),
(407682, 'Maleekah', 'Abrahams', 'Grade  12', 8910285473086),
(407683, 'Abdul', 'Abrahams', 'Grade 10', 8910285473086),
(416532, 'Priyanka', 'Reddy', 'Grade 8', 5911123976088),
(419652, 'Zahra', 'Dolley', 'Grade 12', 9710116298083),
(421639, 'Luke', 'Brown', 'Grade 11', 9409168497083),
(435784, 'Kaylee', 'Jones', 'Grade 9', 9111216984084),
(490128, 'Jecoliah', 'Pillay', 'Grade 9', 8612051392084),
(503146, 'Tiaan', 'Van Wyk', 'Grade 11', 9103216894086),
(508534, 'Emma', 'Smith', 'Grade 10', 8090359780981),
(509342, 'Olivia', 'Taylor', 'Grade 8', 7303154126003),
(528034, 'Jane', 'Anderson', 'Grade 11', 3060525411423),
(540787, 'Sophia', 'Taylor', 'Grade 8', 9108286769008),
(589721, 'Bianca', 'Klate', 'Grade 11', 7908165489089),
(603268, 'Brayden', 'Kramer', 'Grade 10', 6304187924088),
(612729, 'Melody', 'Hart', 'Grade 9', 7609273569089),
(619873, 'Talitha', 'Philips', 'Grade 11', 6903173216088),
(621908, 'Tiffany', 'Seale', 'Grade 10', 6703197213084),
(647927, 'Alice', 'Wilson', 'Grade 12', 1008113111123),
(652308, 'Michael', 'Wilson', 'Grade 10', 7009119979038),
(652908, 'Rowan', 'Brooks', 'Grade 11', 8804185209089),
(652909, 'Ronan', 'Brooks', 'Grade 10', 8804185209089),
(710964, 'Leighton', 'Jacobs', 'Grade 8', 8212158396087),
(712908, 'Joshua', 'Steele', 'Grade 9', 8906185498082),
(796234, 'Farouk', 'Agherdien', 'Grade 10', 8509165792086),
(811764, 'Jordan', 'Classen', 'Grade 9', 8805123427084),
(852907, 'Jean-Pierre', 'Van Wyk', ' Grade 12', 8105145069088),
(895067, 'Alice', 'Taylor', 'Grade 9', 2020195031968),
(908741, 'Lemize', 'Braaf', 'Grade 8', 9503192107085),
(908979, 'Emily', 'Brown', 'Grade 10', 7807275046086),
(910983, 'Ethan', 'Thomas', 'Grade 10', 4109137427178),
(962513, 'Sipho', 'Majola', '11', 7405114908087);

-- --------------------------------------------------------

--
-- Table structure for table `waitinglist`
--

CREATE TABLE `waitinglist` (
  `WaitingListID` varchar(100) NOT NULL,
  `BookingID` varchar(100) NOT NULL,
  `ParentID` bigint(20) DEFAULT NULL,
  `LockerID` varchar(100) DEFAULT NULL,
  `AdminID` int(11) DEFAULT NULL,
  `StudentSchoolNumber` int(11) NOT NULL,
  `RequestedOn` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` enum('Waiting','Allocated','Cancelled') DEFAULT 'Waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `waitinglist`
--

INSERT INTO `waitinglist` (`WaitingListID`, `BookingID`, `ParentID`, `LockerID`, `AdminID`, `StudentSchoolNumber`, `RequestedOn`, `Status`) VALUES
('WL1', 'B5', 4109137427178, NULL, 2, 910983, '2025-11-07 16:38:16', 'Allocated'),
('WL10', 'B16', 8212158396087, 'L3-ROW A\r\n', 2, 710964, '2025-11-16 20:11:46', 'Allocated'),
('WL11', 'B17', 9710116298083, 'L76-ROW H', 2, 419652, '2025-11-17 11:31:59', 'Allocated'),
('WL12', 'B18', 8806113921085, 'L32-ROW D', 2, 372162, '2025-11-17 13:07:02', 'Allocated'),
('WL13', 'B19', 9409168497083, 'L53-ROW F', 2, 421639, '2025-11-17 14:46:32', 'Allocated'),
('WL14', 'B20', 2309183217084, 'L55-ROW F', 2, 192317, '2025-11-17 15:24:48', 'Allocated'),
('WL15', 'B21', 6304187924088, 'L33-ROW D', 2, 603268, '2025-11-17 17:03:53', 'Allocated'),
('WL16', 'B22', 7609273569089, 'L14-ROW B', 2, 612729, '2025-11-18 20:00:37', 'Allocated'),
('WL17', 'B23', 9103216894086, 'L56-ROW F', 2, 503146, '2025-11-18 22:23:37', 'Allocated'),
('WL18', 'B24', 9203185496084, 'L10-ROW A\r\n', 2, 102349, '2025-11-19 07:09:37', 'Allocated'),
('WL19', 'B27', 9203185496084, 'L16-ROW B\r\n\r\n', 2, 102350, '2025-11-19 11:21:28', 'Allocated'),
('WL2', 'B6', 7908165489089, NULL, 2, 589721, '2025-11-08 11:26:47', 'Allocated'),
('WL20', 'B28', 5911123976088, 'L3-ROW A\r\n', 2, 416532, '2025-11-19 15:42:24', 'Allocated'),
('WL21', 'B29', 7405114908087, 'L57-ROW F', 2, 962513, '2025-11-19 15:43:00', 'Allocated'),
('WL22', 'B30', 8910285473086, 'L77-ROW H', 2, 407682, '2025-11-19 21:05:05', 'Allocated'),
('WL23', 'B31', 8910285473086, 'L34-ROW D', 2, 407683, '2025-11-19 22:59:27', 'Allocated'),
('WL3', 'B7', 8105145069088, 'L75-ROW H', 2, 852907, '2025-11-08 18:54:30', 'Allocated'),
('WL4', 'B10', 8804185209089, 'L52-ROW F', 2, 652908, '2025-11-10 02:52:56', 'Allocated'),
('WL5', 'B11', 6507115698088, 'L13-ROW B\r\n', 2, 239170, '2025-11-14 09:17:40', 'Allocated'),
('WL6', 'B12', 6703197213084, 'L31-ROW D', 2, 621908, '2025-11-14 12:10:15', 'Allocated'),
('WL7', 'B13', 8906185498082, 'L15-ROW B\r\n', 2, 712908, '2025-11-15 06:16:10', 'Allocated'),
('WL8', 'B14', 9012185498087, 'L1 - ROW A', 2, 139078, '2025-11-15 13:14:20', 'Allocated'),
('WL9', 'B15', 6903173216088, 'L54-ROW F', 2, 619873, '2025-11-16 19:35:28', 'Allocated');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrator`
--
ALTER TABLE `administrator`
  ADD PRIMARY KEY (`AdminID`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD UNIQUE KEY `BookingID` (`BookingID`),
  ADD KEY `fk_createdbyadmin` (`CreatedByAdminID`),
  ADD KEY `fk_bookings_students` (`StudentSchoolNumber`),
  ADD KEY `fk_bookings_parents` (`ParentID`);

--
-- Indexes for table `lockers`
--
ALTER TABLE `lockers`
  ADD PRIMARY KEY (`LockerID`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`ParentID`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `StudentSchoolNumber` (`StudentSchoolNumber`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`StudentSchoolNumber`),
  ADD KEY `ParentID` (`ParentID`);

--
-- Indexes for table `waitinglist`
--
ALTER TABLE `waitinglist`
  ADD PRIMARY KEY (`WaitingListID`),
  ADD UNIQUE KEY `StudentSchoolNumber` (`StudentSchoolNumber`),
  ADD UNIQUE KEY `BookingID` (`BookingID`),
  ADD UNIQUE KEY `BookingID_2` (`BookingID`),
  ADD UNIQUE KEY `BookingID_3` (`BookingID`),
  ADD KEY `ParentID` (`ParentID`),
  ADD KEY `LockerID` (`LockerID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_parents` FOREIGN KEY (`ParentID`) REFERENCES `parents` (`ParentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_students` FOREIGN KEY (`StudentSchoolNumber`) REFERENCES `students` (`StudentSchoolNumber`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_createdbyadmin` FOREIGN KEY (`CreatedByAdminID`) REFERENCES `administrator` (`AdminID`),
  ADD CONSTRAINT `fk_parent` FOREIGN KEY (`ParentID`) REFERENCES `parents` (`ParentID`),
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`StudentSchoolNumber`) REFERENCES `students` (`StudentSchoolNumber`);

--
-- Constraints for table `lockers`
--
ALTER TABLE `lockers`
  ADD CONSTRAINT `fk_lockers_student` FOREIGN KEY (`StudentSchoolNumber`) REFERENCES `students` (`StudentSchoolNumber`),
  ADD CONSTRAINT `fk_lockers_students` FOREIGN KEY (`StudentSchoolNumber`) REFERENCES `students` (`StudentSchoolNumber`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`StudentSchoolNumber`) REFERENCES `students` (`StudentSchoolNumber`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_parent` FOREIGN KEY (`ParentID`) REFERENCES `parents` (`ParentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_students_parents` FOREIGN KEY (`ParentID`) REFERENCES `parents` (`ParentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`ParentID`) REFERENCES `parents` (`ParentID`);

--
-- Constraints for table `waitinglist`
--
ALTER TABLE `waitinglist`
  ADD CONSTRAINT `fk_waitinglist_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_waitinglist_bookings` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `waitinglist_ibfk_1` FOREIGN KEY (`ParentID`) REFERENCES `parents` (`ParentID`),
  ADD CONSTRAINT `waitinglist_ibfk_2` FOREIGN KEY (`LockerID`) REFERENCES `lockers` (`LockerID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
