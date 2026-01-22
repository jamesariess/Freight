-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2025 at 03:02 PM
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
-- Database: `financial`
--

-- --------------------------------------------------------

--
-- Table structure for table `adjustment`
--

CREATE TABLE `adjustment` (
  `adjustID` int(11) NOT NULL,
  `budgetID` int(11) NOT NULL,
  `adjustedBy` varchar(500) NOT NULL,
  `adjustmentDate` date NOT NULL,
  `reason` text NOT NULL,
  `newAmount` int(11) NOT NULL,
  `status` varchar(500) NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allocationadjustment`
--

CREATE TABLE `allocationadjustment` (
  `allocateadjustmentID` int(11) NOT NULL,
  `allocationID` int(11) DEFAULT NULL,
  `oldpercent` int(11) NOT NULL,
  `oldamount` int(11) NOT NULL,
  `Reason` varchar(500) NOT NULL,
  `Archine` enum('YES','NO','','') NOT NULL DEFAULT 'NO',
  `ChaneDate` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approver`
--

CREATE TABLE `approver` (
  `approver_id` int(11) NOT NULL,
  `approver_name` varchar(255) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approver`
--

INSERT INTO `approver` (`approver_id`, `approver_name`, `title`, `Archive`) VALUES
(1, 'daniel', 'ceo', 'NO'),
(3, 'maray', 'dasd', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `ap_adjustments`
--

CREATE TABLE `ap_adjustments` (
  `adjustment_id` bigint(20) UNSIGNED NOT NULL,
  `bill_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('Credit','Debit') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ap_bills`
--

CREATE TABLE `ap_bills` (
  `bill_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `bill_date` date NOT NULL,
  `due_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PHP',
  `reference_no` varchar(80) DEFAULT NULL,
  `created_at` date NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ap_payments`
--

CREATE TABLE `ap_payments` (
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `LoanID` int(11) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `requestID` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` enum('Bank Transfer','Check','Cash') NOT NULL DEFAULT 'Bank Transfer',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ap_payments`
--

INSERT INTO `ap_payments` (`payment_id`, `LoanID`, `payment_date`, `requestID`, `amount`, `method`, `remarks`, `created_at`, `Archive`) VALUES
(88, 43, '2025-09-21', NULL, 21712461.00, 'Check', 'Downs', '2025-09-21 05:54:16', 'NO'),
(89, 43, '2025-09-21', NULL, 21712461.00, 'Cash', 'bawas', '2025-09-21 10:51:52', 'NO'),
(90, 43, '2025-10-02', NULL, 21712461.00, 'Bank Transfer', 'hahabeb', '2025-10-02 10:59:57', 'NO'),
(91, 43, '2025-10-02', NULL, 21712461.00, 'Bank Transfer', 'bawas', '2025-10-02 11:04:16', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `ar_adjustments`
--

CREATE TABLE `ar_adjustments` (
  `adjustment_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('Credit','Debit') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Archive` enum('YES','NO','','') NOT NULL,
  `status` enum('Resolved','Unresolved','Pending','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ar_collections`
--

CREATE TABLE `ar_collections` (
  `collection_id` bigint(20) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `method` enum('Bank Transfer','Check','Cash','Mobile Payment','Other','','','','') NOT NULL DEFAULT 'Bank Transfer',
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `Archive` enum('YES','NO','','') DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ar_collections`
--

INSERT INTO `ar_collections` (`collection_id`, `invoice_id`, `payment_date`, `amount`, `method`, `remarks`, `created_at`, `Archive`) VALUES
(17, 5, '2025-09-21', 45000.00, 'Cash', 'Partial Payment', '2025-09-21 06:37:42', 'YES'),
(18, 5, '2025-09-21', 45000.00, 'Cash', 'Full Payment', '2025-09-21 06:43:53', 'NO'),
(19, 7, '2025-09-21', 50000.00, 'Cash', 'Full Payment', '2025-09-21 07:11:09', 'NO'),
(20, 10, '2025-10-02', 50000.00, 'Cash', 'Full Payment', '2025-10-02 12:43:13', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `ar_invoices`
--

CREATE TABLE `ar_invoices` (
  `invoice_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PHP',
  `reference_no` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stat` enum('Paid','Partially Paid','UnPaid','Overdue','Draft') NOT NULL DEFAULT 'Draft',
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ar_invoices`
--

INSERT INTO `ar_invoices` (`invoice_id`, `customer_id`, `invoice_date`, `due_date`, `description`, `amount`, `currency`, `reference_no`, `created_at`, `updated_at`, `stat`, `Archive`) VALUES
(5, 1, '2025-09-15', '2025-09-27', 'oig', 450000.00, 'PHP', 'INV-20250921-GHGK', '2025-09-21 06:36:31', '2025-09-21 06:43:53', 'Paid', 'NO'),
(7, 1, '2025-09-21', '2025-09-26', 'sgagsss', 50000.00, 'PHP', 'INV-20250921-Q6FX', '2025-09-21 07:08:02', '2025-10-02 12:41:22', 'Paid', 'NO'),
(8, 1, '2025-09-21', '2025-09-23', 'bukas need to', 50000.00, 'PHP', 'INV-20250921-Z9PC', '2025-09-21 07:28:32', '2025-09-21 07:28:32', 'Draft', 'NO'),
(9, 1, '2025-09-22', '2025-09-24', 'ghhhhh', 500.00, 'PHP', 'INV-20250922-VHF0', '2025-09-22 08:22:26', '2025-09-22 08:22:26', 'Draft', 'NO'),
(10, 1, '2025-09-22', '2025-09-30', 'sgag', 50000.00, 'PHP', 'INV-20250922-N4OK', '2025-09-22 08:33:56', '2025-10-02 12:43:13', 'Paid', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `bankID` int(11) NOT NULL,
  `bankName` varchar(500) NOT NULL,
  `status` enum('Inactive','Active','','') NOT NULL DEFAULT 'Active',
  `archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank`
--

INSERT INTO `bank` (`bankID`, `bankName`, `status`, `archive`) VALUES
(5, 'Bdo', 'Active', 'NO'),
(6, 'Bpi', 'Active', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `budgetplanning`
--

CREATE TABLE `budgetplanning` (
  `bugdetID` int(11) NOT NULL,
  `BudgetName` varchar(500) NOT NULL,
  `DepartmentID` int(11) NOT NULL,
  `Period Start` date NOT NULL,
  `Period End` date NOT NULL,
  `PlannedAmount` int(11) NOT NULL,
  `stats` enum('Draft','Closed','Approved','') NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chartofaccount`
--

CREATE TABLE `chartofaccount` (
  `accountID` int(11) NOT NULL,
  `accountCode` varchar(500) NOT NULL,
  `accountName` varchar(500) NOT NULL,
  `accounType` enum('Assets','Equity','Liabilities','Revenue','Expenses') NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL,
  `status` enum('Active','Inactive','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chartofaccount`
--

INSERT INTO `chartofaccount` (`accountID`, `accountCode`, `accountName`, `accounType`, `Archive`, `status`) VALUES
(1, 'AS-001', 'Cash On Hand', 'Assets', 'YES', 'Active'),
(2, 'AS-002', 'Cash On Bank', 'Assets', 'YES', 'Active'),
(3, 'AS-003', 'Account Receivable', 'Assets', 'NO', 'Active'),
(4, 'AS-004', 'Prepaid Expenses', 'Expenses', 'NO', 'Active'),
(5, 'AS-005', 'Trucks & Vehicles', 'Assets', 'NO', 'Active'),
(6, 'AS-006', 'Fuel Inventory', 'Assets', 'NO', 'Active'),
(7, 'AS-007', 'Office Equipment', 'Assets', 'NO', 'Active'),
(8, 'LI-001', 'Account Payables', 'Liabilities', 'NO', 'Active'),
(9, 'LI-002', 'Salaries Payable', 'Liabilities', 'NO', 'Active'),
(10, 'LI-003', 'Fuel Supplier Payable', 'Liabilities', 'NO', 'Active'),
(11, 'LI-004', 'Loan Payable', 'Liabilities', 'NO', 'Active'),
(12, 'LI-005', 'Taxes Payable', 'Liabilities', 'NO', 'Active'),
(13, 'EQ-001', 'Owner Capital', 'Equity', 'NO', 'Active'),
(14, 'EQ-002', 'Retained Earnings', 'Equity', 'NO', 'Active'),
(15, 'RE-001', 'Freight Revenue', 'Revenue', 'NO', 'Active'),
(16, 'RE-002', 'Fuel Surcharge Revenue', 'Revenue', 'NO', 'Active'),
(17, 'RE-003', 'Other Services', 'Revenue', 'NO', 'Active'),
(18, 'EX-001', 'Fuel Expenses', 'Expenses', 'NO', 'Active'),
(19, 'EX-002', 'Wages', 'Expenses', 'NO', 'Active'),
(20, 'EX-003', 'Truck Maintenance', 'Expenses', 'NO', 'Active'),
(21, 'EX-004', 'Office Supplies', 'Expenses', 'NO', 'Active'),
(22, 'EX-005', 'Facility Maintenance ', 'Expenses', 'NO', 'Active'),
(23, 'EX-006', 'Tools & Supplies', 'Expenses', 'NO', 'Active'),
(24, 'EX-007', 'Interest ', 'Expenses', 'NO', 'Active'),
(25, 'EX-008', 'Insurance ', 'Expenses', 'NO', 'Active'),
(26, 'EX-009', 'Toll & Port Fees ', 'Expenses', 'NO', 'Active'),
(27, 'EX-010', 'Travel Allowances Expense', 'Expenses', 'NO', 'Active'),
(28, 'EX-011', 'Benefits & Allowances', 'Expenses', 'NO', 'Active'),
(29, 'EX-012', 'Recruitment Expense', 'Expenses', 'NO', 'Active'),
(30, 'EX-013', 'Training ', 'Expenses', 'NO', 'Active'),
(31, 'EX-014', 'Rent & Utilities', 'Expenses', 'NO', 'Active'),
(32, 'EX-015', 'Taxes & Licenses ', 'Expenses', 'YES', 'Active'),
(33, 'EX-016', 'Employee Reimbursements', 'Expenses', 'NO', 'Active'),
(34, 'EX-017', 'Preventive Maintenance ', 'Expenses', 'NO', 'Active'),
(35, 'EX-018', 'Packaging & Materials ', 'Expenses', 'NO', 'Active'),
(36, 'EX-019', 'Dispatch & Routing ', 'Expenses', 'NO', 'Active'),
(37, 'EX-020', 'Service Contracts ', 'Expenses', 'NO', 'Active'),
(38, 'EX-021', 'Audit & Reporting ', 'Expenses', 'NO', 'Active'),
(39, 'EX-022', 'IT & Software', 'Expenses', 'NO', 'Active'),
(40, 'EX-023', 'Courier & Document', 'Expenses', 'NO', 'Active'),
(41, 'AS-008', 'Warehouse Stock', 'Assets', 'NO', 'Active'),
(42, 'EX-024', 'Depreciation ', 'Assets', 'NO', 'Active'),
(43, 'AS-025', 'Cash On Bank', 'Assets', 'NO', 'Active'),
(44, 'AS-025', 'Cash On Hand', 'Assets', 'NO', 'Active'),
(45, 'EQ-003', 'Petty Cash Funding', 'Equity', 'NO', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `collection_plan`
--

CREATE TABLE `collection_plan` (
  `planID` int(11) NOT NULL,
  `plan` varchar(500) NOT NULL,
  `remaining_days` int(11) NOT NULL,
  `plan_type` enum('Automated','Manual','Mixed / Hybrid') NOT NULL,
  `status` enum('Active','Inactive') NOT NULL,
  `Date` datetime NOT NULL,
  `Archive` enum('YES','NO') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collection_plan`
--

INSERT INTO `collection_plan` (`planID`, `plan`, `remaining_days`, `plan_type`, `status`, `Date`, `Archive`) VALUES
(11, 'Due Notice', 3, 'Manual', 'Active', '2025-09-19 07:59:23', 'NO'),
(12, 'Notice before Due', 7, 'Mixed / Hybrid', 'Active', '2025-09-19 08:00:22', 'YES'),
(13, 'tomorrow', 1, 'Automated', 'Active', '2025-09-21 15:27:54', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `costallocation`
--

CREATE TABLE `costallocation` (
  `allocationID` int(11) NOT NULL,
  `Deptbudget` int(11) NOT NULL,
  `accountID` int(11) DEFAULT NULL,
  `Amount` int(11) NOT NULL,
  `percentage` int(11) NOT NULL,
  `yearlybudget` year(4) NOT NULL,
  `AllocationCreate` date NOT NULL,
  `Status` enum('Activate','Deactivate') NOT NULL DEFAULT 'Activate',
  `usedAllocation` int(11) DEFAULT NULL,
  `addedbudget` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `costallocation`
--

INSERT INTO `costallocation` (`allocationID`, `Deptbudget`, `accountID`, `Amount`, `percentage`, `yearlybudget`, `AllocationCreate`, `Status`, `usedAllocation`, `addedbudget`) VALUES
(58, 19, 11, 100000000, 10, '2025', '2025-09-19', 'Activate', 86854844, NULL),
(59, 19, 12, 2500000, 25, '2025', '2025-09-19', 'Activate', NULL, NULL),
(60, 19, 24, 1000000, 10, '2025', '2025-09-19', 'Activate', NULL, NULL),
(61, 19, 25, 500000, 5, '2025', '2025-09-19', 'Activate', NULL, NULL),
(62, 20, 31, 200000000, 40, '2025', '2025-09-19', 'Activate', NULL, NULL),
(63, 20, 21, 50000000, 10, '2025', '2025-09-19', 'Activate', NULL, NULL),
(64, 20, 22, 50000000, 10, '2025', '2025-09-19', 'Activate', NULL, NULL),
(65, 20, 37, 50000000, 10, '2025', '2025-09-19', 'Activate', NULL, NULL),
(66, 20, 39, 100000000, 20, '2025', '2025-09-19', 'Activate', NULL, NULL),
(73, 22, 34, 100000000, 25, '2025', '2025-09-19', 'Activate', NULL, NULL),
(74, 22, 20, 160000000, 40, '2025', '2025-09-19', 'Activate', NULL, NULL),
(75, 22, 23, 104000000, 26, '2025', '2025-09-19', 'Activate', NULL, NULL),
(76, 23, 18, 9000000, 20, '2025', '2025-09-19', 'Activate', NULL, NULL),
(77, 23, 26, 4500000, 10, '2025', '2025-09-19', 'Activate', NULL, NULL),
(78, 23, 27, 2250000, 5, '2025', '2025-09-19', 'Activate', NULL, NULL),
(79, 23, 35, 450000, 1, '2025', '2025-09-19', 'Activate', 53535, NULL),
(80, 23, 36, 7200000, 16, '2025', '2025-09-19', 'Activate', NULL, NULL),
(81, 23, 40, 1800000, 4, '2025', '2025-09-19', 'Activate', 42141, NULL),
(82, 20, 38, 1500000, 3, '2025', '2025-09-19', 'Activate', NULL, NULL),
(83, 21, 28, 40000000, 20, '2025', '2025-09-19', 'Activate', NULL, NULL),
(84, 21, 29, 10000000, 5, '2025', '2025-09-19', 'Activate', 2400100, NULL),
(85, 21, 30, 10000000, 5, '2025', '2025-09-19', 'Activate', NULL, NULL),
(86, 21, 33, 40000000, 20, '2025', '2025-09-19', 'Activate', NULL, NULL),
(87, 21, 19, 40000000, 40, '2025', '2025-09-19', 'Activate', NULL, NULL),
(88, 22, 42, 14400000, 40, '2025', '2025-09-21', 'Activate', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(40) DEFAULT '30 days',
  `is_active` enum('Acive','Inavtive','','') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Archive` enum('YES','NO','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `contact_person`, `email`, `phone`, `address_line`, `payment_terms`, `is_active`, `created_at`, `updated_at`, `Archive`) VALUES
(1, 'ABC Logistics', 'Juan Dela Cruz', 'jamesconcepcion122@gmail.com', '917123456', '123 Port Area, Manila', '30 days', 'Acive', '2025-08-28 13:15:02', '2025-08-28 13:15:02', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `departmentbudget`
--

CREATE TABLE `departmentbudget` (
  `Deptbudget` int(11) NOT NULL,
  `Name` varchar(500) NOT NULL,
  `Amount` int(11) NOT NULL,
  `DateValid` year(4) NOT NULL,
  `Details` varchar(500) NOT NULL,
  `status` enum('Cancel','Proceed') NOT NULL DEFAULT 'Proceed',
  `UsedBudget` int(11) DEFAULT NULL,
  `addbudget` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departmentbudget`
--

INSERT INTO `departmentbudget` (`Deptbudget`, `Name`, `Amount`, `DateValid`, `Details`, `status`, `UsedBudget`, `addbudget`) VALUES
(19, 'Finance ', 10000000, '2025', 'Loans, AP, AR, GL, taxes, reporting', 'Cancel', 10000000, NULL),
(20, 'General Services', 500000000, '2025', 'Office rent, utilities, supplies, IT systems, janitorial, security', 'Cancel', 451500000, NULL),
(21, 'HR', 200000000, '2025', 'Salaries, benefits, recruitment, training, reimbursements', 'Cancel', 140000000, NULL),
(22, 'Maintenance', 400000000, '2025', 'Repairs, spare parts, preventive maintenance, tools', 'Cancel', 378400000, NULL),
(23, 'Operations', 45000000, '2025', 'Logistics, scheduling, production, service delivery', 'Cancel', 25200000, NULL),
(24, 'Finance ', 1000000000, '2026', 'Loans, AP, AR, GL, taxes, reporting', 'Cancel', NULL, NULL),
(25, 'Finance ', 600000, '2025', 'Loans, AP, AR, GL, taxes, reporting', 'Cancel', NULL, NULL),
(26, 'Finance ', 60000000, '2025', 'daum', 'Cancel', NULL, NULL),
(27, 'Finance ', 60000000, '2025', 'Account Payable, taxes and Interest', 'Proceed', NULL, NULL),
(28, 'General Services', 20000000, '2025', 'Office rent, utilities, supplies, IT systems, janitorial, security', 'Proceed', NULL, NULL),
(29, 'HR', 80353000, '2025', 'Salaries, benefits, recruitment, training, reimbursements ', 'Proceed', NULL, NULL),
(30, 'Maintenance', 100000582, '2025', 'Repairs, spare parts, preventive maintenance, tools and warehousing', 'Proceed', NULL, NULL),
(31, 'Operations', 150000000, '2025', 'Logistics, scheduling, production, service delivery', 'Proceed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `details`
--

CREATE TABLE `details` (
  `entriesID` int(11) NOT NULL,
  `journalID` int(11) DEFAULT NULL,
  `accountID` int(11) DEFAULT NULL,
  `debit` int(11) DEFAULT NULL,
  `credit` int(11) DEFAULT NULL,
  `Archive` enum('YES','NO','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `details`
--

INSERT INTO `details` (`entriesID`, `journalID`, `accountID`, `debit`, `credit`, `Archive`) VALUES
(117, 48, 1, 520000000, 0, 'NO'),
(118, 48, 11, 0, 520000000, 'NO'),
(119, 49, 24, 1594292, 0, 'NO'),
(120, 49, 11, 20118169, 0, 'NO'),
(121, 49, 2, 0, 21712461, 'NO'),
(122, 50, 3, 450000, 0, 'NO'),
(123, 50, 15, 0, 450000, 'NO'),
(124, 51, 1, 4500, 0, 'NO'),
(125, 51, 3, 0, 4500, 'NO'),
(126, 52, 1, 450000, 0, 'NO'),
(127, 52, 3, 0, 450000, 'NO'),
(128, 53, 3, 5000000, 0, 'NO'),
(129, 53, 15, 0, 5000000, 'NO'),
(130, 54, 3, 50000, 0, 'NO'),
(131, 54, 15, 0, 50000, 'NO'),
(132, 55, 1, 50000, 0, 'NO'),
(133, 55, 3, 0, 50000, 'NO'),
(134, 56, 3, 50000, 0, 'NO'),
(135, 56, 15, 0, 50000, 'NO'),
(136, 57, 29, 100000, 0, 'NO'),
(137, 57, 1, 0, 100000, 'NO'),
(138, 58, 24, 1454856, 0, 'NO'),
(139, 58, 11, 20257605, 0, 'NO'),
(140, 58, 2, 0, 21712461, 'NO'),
(141, 59, 24, 1387331, 0, 'NO'),
(142, 59, 11, 20325130, 0, 'NO'),
(143, 59, 2, 0, 21712461, 'NO'),
(144, 60, 3, 500, 0, 'NO'),
(145, 60, 15, 0, 500, 'NO'),
(146, 61, 3, 50000, 0, 'NO'),
(147, 61, 15, 0, 50000, 'NO'),
(148, 62, 1, 5500, 0, 'NO'),
(149, 62, 11, 0, 5500, 'NO'),
(150, 63, 2, 29, 0, 'NO'),
(151, 63, 1, 0, 29, 'NO'),
(152, 64, 2, 971, 0, 'NO'),
(153, 64, 1, 0, 971, 'NO'),
(154, 65, 43, 409000, 0, 'NO'),
(155, 65, 44, 0, 409000, 'NO'),
(156, 66, 43, 1, 0, 'NO'),
(157, 66, 44, 0, 1, 'NO'),
(158, 67, 44, 1, 0, 'NO'),
(159, 67, 45, 0, 1, 'NO'),
(160, 68, 43, 29, 0, 'NO'),
(161, 68, 13, 0, 29, 'NO'),
(162, 69, 43, 29, 0, 'NO'),
(163, 69, 13, 0, 29, 'NO'),
(164, 70, 43, 20, 0, 'NO'),
(165, 70, 44, 0, 20, 'NO'),
(166, 71, 43, 2, 0, 'NO'),
(167, 71, 44, 0, 2, 'NO'),
(168, 72, 43, 2, 0, 'NO'),
(169, 72, 13, 0, 2, 'NO'),
(170, 76, 43, 29, 0, 'NO'),
(171, 76, 13, 0, 29, 'NO'),
(172, 77, 43, 1, 0, 'NO'),
(173, 77, 13, 0, 1, 'NO'),
(174, 78, 43, 1, 0, 'NO'),
(175, 78, 13, 0, 1, 'NO'),
(176, 79, 43, 1, 0, 'NO'),
(177, 79, 13, 0, 1, 'NO'),
(178, 80, 43, 1, 0, 'NO'),
(179, 80, 13, 0, 1, 'NO'),
(180, 81, 43, 1, 0, 'NO'),
(181, 81, 13, 0, 1, 'NO'),
(182, 82, 44, 22, 0, 'NO'),
(183, 82, 45, 0, 22, 'NO'),
(184, 83, 43, 29, 0, 'NO'),
(185, 83, 44, 0, 29, 'NO'),
(186, 84, 44, 1, 0, 'NO'),
(187, 84, 45, 0, 1, 'NO'),
(188, 85, 44, 1, 0, 'NO'),
(189, 85, 45, 0, 1, 'NO'),
(190, 86, 44, 1, 0, 'NO'),
(191, 86, 45, 0, 1, 'NO'),
(192, 87, 44, 1, 0, 'NO'),
(193, 87, 45, 0, 1, 'NO'),
(194, 88, 43, 1, 0, 'NO'),
(195, 88, 44, 0, 1, 'NO'),
(196, 89, 13, 2, 0, 'NO'),
(197, 89, 43, 0, 2, 'NO'),
(198, 90, 13, 9, 0, 'NO'),
(199, 90, 43, 0, 9, 'NO'),
(200, 91, 13, 9, 0, 'NO'),
(201, 91, 43, 0, 9, 'NO'),
(202, 92, 43, 1, 0, 'NO'),
(203, 92, 13, 0, 1, 'NO'),
(204, 93, 43, 1, 0, 'NO'),
(205, 93, 13, 0, 1, 'NO'),
(206, 94, 44, 29, 0, 'NO'),
(207, 94, 45, 0, 29, 'NO'),
(208, 95, 43, 29, 0, 'NO'),
(209, 95, 13, 0, 29, 'NO'),
(210, 96, 43, 11, 0, 'NO'),
(211, 96, 44, 0, 11, 'NO'),
(212, 97, 44, 8, 0, 'NO'),
(213, 97, 45, 0, 8, 'NO'),
(214, 98, 43, 29, 0, 'NO'),
(215, 98, 13, 0, 29, 'NO'),
(216, 99, 43, 455646545, 0, 'NO'),
(217, 99, 13, 0, 455646545, 'NO'),
(218, 100, 43, 44, 0, 'NO'),
(219, 100, 13, 0, 44, 'NO'),
(220, 101, 44, 4, 0, 'NO'),
(221, 101, 45, 0, 4, 'NO'),
(222, 102, 43, 2, 0, 'NO'),
(223, 102, 13, 0, 2, 'NO'),
(224, 103, 44, 1, 0, 'NO'),
(225, 103, 45, 0, 1, 'NO'),
(226, 105, 43, 0, 646725, 'NO'),
(227, 105, 13, 646725, 0, 'NO'),
(228, 106, 43, 5000000, 0, 'NO'),
(229, 106, 44, 0, 5000000, 'NO'),
(230, 107, 43, 4646521, 0, 'NO'),
(231, 107, 44, 0, 4646521, 'NO'),
(232, 108, 43, 0, 5000002, 'NO'),
(233, 108, 13, 5000002, 0, 'NO'),
(234, 109, 43, 0, 4000000, 'NO'),
(235, 109, 13, 4000000, 0, 'NO'),
(236, 110, 43, 510353484, 0, 'NO'),
(237, 110, 44, 0, 510353484, 'NO'),
(238, 111, 43, 29, 0, 'NO'),
(239, 111, 13, 0, 29, 'NO'),
(240, 112, 43, 1, 0, 'NO'),
(241, 112, 13, 0, 1, 'NO'),
(242, 113, 43, 3, 0, 'NO'),
(243, 113, 13, 0, 3, 'NO'),
(244, 114, 43, 0, 3, 'NO'),
(245, 114, 13, 3, 0, 'NO'),
(246, 115, 44, 50, 0, 'NO'),
(247, 115, 45, 0, 50, 'NO'),
(248, 117, 43, 150, 0, 'NO'),
(249, 117, 44, 0, 150, 'NO'),
(250, 118, 43, 0, 646701, 'NO'),
(251, 118, 13, 646701, 0, 'NO'),
(252, 119, 43, 0, 50000000, 'NO'),
(253, 119, 13, 50000000, 0, 'NO'),
(254, 120, 29, 100000, 0, 'NO'),
(255, 120, 1, 0, 100000, 'NO'),
(256, 121, 29, 100000, 0, 'NO'),
(257, 121, 1, 0, 100000, 'NO'),
(258, 122, 29, 100000, 0, 'NO'),
(259, 122, 2, 0, 100000, 'NO'),
(260, 123, 29, 100000, 0, 'NO'),
(261, 123, 2, 0, 100000, 'NO'),
(262, 124, 24, 1247206, 0, 'NO'),
(263, 124, 11, 20465255, 0, 'NO'),
(264, 124, 2, 0, 21712461, 'NO'),
(265, 125, 43, 0, 438441020, 'NO'),
(266, 125, 13, 438441020, 0, 'NO'),
(267, 126, 44, 21712461, 0, 'NO'),
(268, 126, 45, 0, 21712461, 'NO'),
(269, 127, 44, 250200100, 0, 'NO'),
(270, 127, 45, 0, 250200100, 'NO'),
(271, 128, 24, 1106613, 0, 'NO'),
(272, 128, 11, 20605848, 0, 'NO'),
(273, 128, 1, 0, 21712461, 'NO'),
(274, 129, 1, 50000, 0, 'NO'),
(275, 129, 3, 0, 50000, 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

CREATE TABLE `entries` (
  `journalID` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(1000) NOT NULL,
  `referenceType` text NOT NULL,
  `createdBy` text NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL,
  `periodID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `entries`
--

INSERT INTO `entries` (`journalID`, `date`, `description`, `referenceType`, `createdBy`, `Archive`, `periodID`) VALUES
(48, '2025-09-21', 'Loan Approved: LAnd', 'Loan Approval', 'system', 'NO', 2),
(49, '2025-09-21', 'Loan Payment Request #52', 'Loan Payment', 'System', 'NO', 2),
(50, '2025-09-21', 'oig', 'INV-20250921-GHGK', 'System', 'NO', 2),
(51, '2025-09-21', 'Collection for Invoice #INV-20250921-GHGK', 'INV-INV-20250921-GHGK', 'Admin', 'NO', 2),
(52, '2025-09-21', 'Collection for Invoice #INV-20250921-GHGK', 'INV-INV-20250921-GHGK', 'Admin', 'NO', 2),
(53, '2025-09-21', 'need', 'INV-20250921-UF57', 'System', 'NO', 2),
(54, '2025-09-21', 'sgag', 'INV-20250921-Q6FX', 'System', 'NO', 2),
(55, '2025-09-21', 'Collection for Invoice #INV-20250921-Q6FX', 'INV-INV-20250921-Q6FX', 'Admin', 'NO', 2),
(56, '2025-09-21', 'bukas need to', 'INV-20250921-Z9PC', 'System', 'NO', 2),
(57, '2025-09-21', 'Expense Request #53', 'General Expense', 'System', 'NO', 2),
(58, '2025-09-21', 'Loan Payment Request #54', 'Loan Payment', 'System', 'NO', NULL),
(59, '2025-09-22', 'Loan Payment Request #54', 'Loan Payments', 'System', 'NO', NULL),
(60, '2025-09-22', 'ghhhhh', 'INV-20250922-VHF0', 'System', 'NO', NULL),
(61, '2025-09-22', 'sgag', 'INV-20250922-N4OK', 'System', 'NO', NULL),
(62, '2025-09-22', 'Loan Approved: Buying Truck', 'Loan Approval', 'system', 'NO', NULL),
(63, '2025-09-30', 'Bank Deposit (12566814)', 'Deposit', 'System', 'NO', NULL),
(64, '2025-09-30', 'Bank Deposit (GDHTBSAGDWG)', 'Deposit', 'System', 'NO', NULL),
(65, '2025-09-30', 'Bank Deposit (GDHTBSAGDWG)', 'Deposit', 'System', 'NO', NULL),
(66, '2025-09-30', 'Bank Deposit (GDHTBSAGDWG)', 'Deposit', 'System', 'NO', NULL),
(67, '2025-09-30', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(68, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(69, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(70, '2025-09-30', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(71, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(72, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(73, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(74, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(75, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(76, '2025-09-30', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(77, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(78, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(79, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(80, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(81, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(82, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(83, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(84, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(85, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(86, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(87, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(88, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(89, '2025-10-01', 'Withdrawal from BDO', 'Withdrawal - BDO', 'System', 'NO', NULL),
(90, '2025-10-01', 'Withdrawal from BDO', 'Withdrawal - BDO', 'System', 'NO', NULL),
(91, '2025-10-01', 'Withdrawal from BDO', 'Withdrawal - BDO', 'System', 'NO', NULL),
(92, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(93, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(94, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(95, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(96, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(97, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(98, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(99, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(100, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(101, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(102, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(103, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(104, '2025-10-01', 'Withdrawal from Bank Fund', 'Withdrawal', 'System', 'NO', NULL),
(105, '2025-10-01', 'Withdrawal from Bank Fund', 'Withdrawal', 'System', 'NO', NULL),
(106, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(107, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(108, '2025-10-01', 'Withdrawal from Bank', 'Withdrawal', 'System', 'NO', NULL),
(109, '2025-10-01', 'Withdrawal from Bank', 'Withdrawal', 'System', 'NO', NULL),
(110, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(111, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(112, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(113, '2025-10-01', 'Deposit (Owner)', 'Owner', 'System', 'NO', NULL),
(114, '2025-10-01', 'Withdrawal from Bank', 'Withdrawal', 'System', 'NO', NULL),
(115, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(116, '2025-10-01', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(117, '2025-10-01', 'Deposit (Bank)', 'Bank', 'System', 'NO', NULL),
(118, '2025-10-01', 'Withdrawal from Bank', 'Withdrawal', 'System', 'NO', NULL),
(119, '2025-10-01', 'Withdrawal from Bank', 'Withdrawal', 'System', 'NO', NULL),
(120, '2025-10-02', 'Expense Request #53', 'General Expense', 'System', 'NO', NULL),
(121, '2025-10-02', 'Expense Request #53', 'General Expense', 'System', 'NO', NULL),
(122, '2025-10-02', 'Expense Request #53', 'General Expense', 'System', 'NO', NULL),
(123, '2025-10-02', 'Expense Request #53', 'General Expense', 'System', 'NO', NULL),
(124, '2025-10-02', 'Loan Payment Request #55', 'Loan Payment', 'System', 'NO', NULL),
(125, '2025-10-02', 'Withdrawal from Bank', 'Withdrawal', 'System', 'NO', NULL),
(126, '2025-10-02', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(127, '2025-10-02', 'Deposit (Petty Cash)', 'Petty Cash', 'System', 'NO', NULL),
(128, '2025-10-02', 'Loan Payment Request #56', 'Loan Payment', 'System', 'NO', NULL),
(129, '2025-10-02', 'Collection for Invoice #INV-20250922-N4OK', 'INV-INV-20250922-N4OK', 'Admin', 'NO', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `follow`
--

CREATE TABLE `follow` (
  `reminderID` int(11) NOT NULL,
  `planID` int(11) DEFAULT NULL,
  `InvoiceID` int(11) DEFAULT NULL,
  `FollowUpDate` datetime NOT NULL,
  `Contactinfo` enum('Email','CellNumber','In Person','') NOT NULL,
  `Remarks` enum('Reminder Sent','Failed To Sent','Email not Working','To Be Sent','Emailed Sent') NOT NULL,
  `paymentstatus` enum('Paid','Not Paid','Partially Paid') NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follow`
--

INSERT INTO `follow` (`reminderID`, `planID`, `InvoiceID`, `FollowUpDate`, `Contactinfo`, `Remarks`, `paymentstatus`, `Archive`) VALUES
(15, 11, 5, '2025-09-24 00:00:00', 'Email', 'To Be Sent', 'Paid', 'YES'),
(16, 11, 7, '2025-09-23 00:00:00', 'Email', 'To Be Sent', 'Paid', 'YES'),
(17, 13, 8, '2025-09-22 00:00:00', 'Email', 'Emailed Sent', 'Not Paid', 'NO'),
(18, 13, 9, '2025-09-22 16:27:00', 'Email', 'Emailed Sent', 'Not Paid', 'NO'),
(19, 12, 10, '2025-09-22 16:38:00', 'Email', 'Emailed Sent', 'Paid', 'NO'),
(20, 11, 7, '2025-09-23 00:00:00', 'Email', 'Emailed Sent', 'Not Paid', 'NO'),
(21, 11, 5, '2025-09-24 00:00:00', 'Email', 'To Be Sent', 'Not Paid', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `funds`
--

CREATE TABLE `funds` (
  `fundsID` int(11) NOT NULL,
  `bankID` int(11) DEFAULT NULL,
  `Amount` int(11) NOT NULL,
  `UsedAmount` int(11) DEFAULT NULL,
  `Date` datetime NOT NULL DEFAULT current_timestamp(),
  `reference` varchar(500) DEFAULT NULL,
  `Notes` text NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO',
  `fundType` enum('Bank','Owner','PettyCash') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `funds`
--

INSERT INTO `funds` (`fundsID`, `bankID`, `Amount`, `UsedAmount`, `Date`, `reference`, `Notes`, `Archive`, `fundType`) VALUES
(41, 5, 5000000, 5000000, '2025-10-01 13:07:25', 'H46641D', 'Bank', 'NO', 'Bank'),
(42, 5, 4646521, 4646521, '2025-10-01 13:07:40', 'GDHTBSAGDWG', 'Bank', 'NO', 'Bank'),
(43, 6, 510353484, 510353484, '2025-10-01 13:14:21', '12566814', 'Bank', 'NO', 'Bank'),
(44, 5, 29, 29, '2025-10-01 16:01:33', 'Owner Deposit', 'Owner', 'NO', 'Owner'),
(45, 6, 1, 0, '2025-10-01 16:02:00', 'Owner Deposit', 'Owner', 'NO', 'Owner'),
(46, 5, 3, 3, '2025-10-01 16:10:22', 'Owner Deposit', 'Owner', 'NO', 'Owner'),
(47, NULL, 50, 0, '2025-10-01 21:00:32', 'Add Money to Petty Cash', 'Petty Cash', 'NO', 'PettyCash'),
(48, NULL, 150, 0, '2025-10-01 21:02:37', 'Add Money to Petty Cash', 'Petty Cash', 'NO', 'PettyCash'),
(49, 5, 150, 150, '2025-10-01 21:06:58', 'GDHTBSAGDWG', 'Bank', 'NO', 'Bank'),
(50, NULL, 21712461, 0, '2025-10-02 19:05:03', 'Add Money to Petty Cash', 'Petty Cash', 'NO', 'PettyCash'),
(51, NULL, 250200100, 0, '2025-10-02 19:07:03', 'Add Money to Petty Cash', 'Petty Cash', 'NO', 'PettyCash');

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `LoanID` int(11) NOT NULL,
  `LoanTitle` varchar(500) NOT NULL,
  `loanAmount` int(11) NOT NULL,
  `interestRate` int(11) NOT NULL,
  `paidAmount` int(11) NOT NULL,
  `startDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `PaymentTerms` int(11) DEFAULT NULL,
  `Notes` varchar(500) NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO',
  `VendorID` int(11) DEFAULT NULL,
  `Status` enum('Overdue','Pending','Partially Paid','Paid') NOT NULL DEFAULT 'Pending',
  `dateReceived` datetime DEFAULT NULL,
  `paymentMethod` enum('Cash','Check','Bank Transfer') DEFAULT NULL,
  `installment` int(11) DEFAULT NULL,
  `AmountperMonth` int(11) DEFAULT NULL,
  `dueday` int(11) DEFAULT NULL,
  `collateral` varchar(500) DEFAULT NULL,
  `PenaltyInterest` int(11) NOT NULL,
  `PenaltyDetails` varchar(1000) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `pdf_filename` blob DEFAULT NULL,
  `Remarks` enum('Draft','Approved','Rejected','') DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan`
--

INSERT INTO `loan` (`LoanID`, `LoanTitle`, `loanAmount`, `interestRate`, `paidAmount`, `startDate`, `EndDate`, `PaymentTerms`, `Notes`, `Archive`, `VendorID`, `Status`, `dateReceived`, `paymentMethod`, `installment`, `AmountperMonth`, `dueday`, `collateral`, `PenaltyInterest`, `PenaltyDetails`, `created_at`, `pdf_filename`, `Remarks`) VALUES
(43, 'LAnd', 500000000, 4, 188621851, '2025-10-04', '2027-09-04', NULL, 'Expanding', 'NO', 2, 'Partially Paid', '2025-09-22 00:00:00', 'Cash', 24, 21712461, 4, 'AR', 5, 'increase interest', '2025-09-21 13:51:48', 0x706466732f6c6f616e5f34335f313735383433333934352e706466, 'Approved'),
(44, 'Buying Truck', 5000, 10, 0, '2025-10-25', '2025-10-25', NULL, 'need money', 'NO', 1, 'Pending', '2025-09-23 00:00:00', 'Cash', 1, 5042, 25, 'AR', 5, 'increase interest', '2025-09-22 22:34:54', 0x706466732f6c6f616e5f34345f313735383535313732372e706466, 'Approved'),
(45, 'daymn', 35, 4, 0, '2025-11-25', '2026-02-25', NULL, 'neeed truck', 'NO', 1, 'Pending', '2025-10-08 00:00:00', 'Cash', 4, 9, 25, 'AR', 5, 'increase interest', '2025-10-01 14:48:15', 0x706466732f6c6f616e5f6461796d6e5f313735393330313239352e706466, 'Draft');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `paymentID` int(11) NOT NULL,
  `Invoice` varchar(500) NOT NULL,
  `paymentDate` datetime NOT NULL,
  `amount` int(11) NOT NULL,
  `paymethod` varchar(500) NOT NULL,
  `remarks` varchar(500) NOT NULL,
  `Archive` enum('YES','NO','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`paymentID`, `Invoice`, `paymentDate`, `amount`, `paymethod`, `remarks`, `Archive`) VALUES
(1, 'HT1264', '2025-08-22 15:22:00', 500, 'Online Bangking', 'nakatulog', 'YES'),
(2, 'ggaag', '2025-08-22 15:28:11', 500, 'gahah', 'Maglalaba', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `paymentmethod`
--

CREATE TABLE `paymentmethod` (
  `payment_method_id` int(11) NOT NULL,
  `method_name` varchar(50) DEFAULT NULL,
  `account_details` varchar(255) DEFAULT NULL,
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paymentmethod`
--

INSERT INTO `paymentmethod` (`payment_method_id`, `method_name`, `account_details`, `Archive`) VALUES
(1, 'gcashs', 'sdaas', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `periods`
--

CREATE TABLE `periods` (
  `period_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `status` enum('Open','Closed','Locked') DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periods`
--

INSERT INTO `periods` (`period_id`, `year`, `month`, `status`) VALUES
(2, 2025, 9, 'Open');

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `receiptID` int(11) NOT NULL,
  `paymentID` bigint(20) DEFAULT NULL,
  `receiptsdate` datetime NOT NULL,
  `receiptNumber` varchar(500) NOT NULL,
  `issueBy` varchar(500) NOT NULL,
  `receiptImage` blob NOT NULL,
  `Archive` enum('YES','NO') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`receiptID`, `paymentID`, `receiptsdate`, `receiptNumber`, `issueBy`, `receiptImage`, `Archive`) VALUES
(16, 17, '2025-09-21 14:37:42', 'RCP-2025-00017', 'Admin', 0x75706c6f6164732f726563656970742f726563656970745f31362e706e67, 'NO'),
(17, 18, '2025-09-21 14:43:53', 'RCP-2025-00018', 'Admin', 0x75706c6f6164732f726563656970742f726563656970745f31372e706e67, 'YES'),
(18, 19, '2025-09-21 15:11:10', 'RCP-2025-00019', 'Admin', 0x75706c6f6164732f726563656970742f726563656970745f31382e706e67, 'NO'),
(19, 20, '2025-10-02 20:43:13', 'RCP-2025-00020', 'Admin', 0x75706c6f6164732f726563656970742f726563656970745f31392e706e67, 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

CREATE TABLE `request` (
  `requestID` int(11) NOT NULL,
  `allocationID` int(11) DEFAULT NULL,
  `requestTiTle` varchar(500) DEFAULT NULL,
  `Amount` int(11) DEFAULT NULL,
  `Requested_by` varchar(500) DEFAULT NULL,
  `Due` date DEFAULT NULL,
  `status` enum('Pending','Paid','Approved','Rejected','Release') NOT NULL DEFAULT 'Pending',
  `date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Archive` enum('YES','NO','','') NOT NULL DEFAULT 'NO',
  `customer_id` int(11) DEFAULT NULL,
  `employeeID` int(11) DEFAULT NULL,
  `Purpuse` text DEFAULT NULL,
  `ApprovedAmount` int(11) DEFAULT NULL,
  `LoanID` int(11) DEFAULT NULL,
  `Remarks` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request`
--

INSERT INTO `request` (`requestID`, `allocationID`, `requestTiTle`, `Amount`, `Requested_by`, `Due`, `status`, `date`, `Archive`, `customer_id`, `employeeID`, `Purpuse`, `ApprovedAmount`, `LoanID`, `Remarks`) VALUES
(52, 58, 'LAnd', 21712461, 'admin', '2025-10-04', 'Paid', '2025-09-21 05:55:09', 'NO', NULL, NULL, 'Loan Payment', 21712461, 43, NULL),
(53, 84, 'Need Allowance', 100000, 'me', '2025-09-21', 'Approved', '2025-10-02 11:43:38', 'NO', NULL, NULL, 'gamit', 100000, NULL, 'laka'),
(54, 58, 'LAnd', 21712461, 'admin', '2025-10-04', 'Pending', '2025-10-02 11:44:11', 'NO', NULL, NULL, 'Loan Payment', 21712461, 43, 'bawal ngani'),
(55, 58, 'LAnd', 21712461, 'admin', '2025-10-04', 'Paid', '2025-10-02 11:51:04', 'YES', NULL, NULL, 'Loan Payment', 21712461, 43, NULL),
(56, 58, 'LAnd', 21712461, 'admin', '2025-10-04', 'Paid', '2025-10-02 11:49:17', 'YES', NULL, NULL, 'Loan Payment', 21712461, 43, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(500) NOT NULL,
  `password` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$F7Ln8BImZ7RCYFNnY2t2B.WB0PjWIpVfEj7Z8FRbbJlQmn0m3kSPS');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `failed_attempts`, `lockout_until`) VALUES
(1, 'daniel', '$2y$10$oZAJ3htAnQ0ncSUF5x8VcuPofPY4I/akrEqH9L0RPUG7hpwlP9DFS', '', 0, NULL),
(3, 'danielpogi', '$2y$10$x6QAhjCDwor9A9PgoIJdM./ALbtKQAaDzoMgV6A/zu94U0uy1i7sK', 'arkosios86@gmail.com', 3, '2025-09-21 18:25:24'),
(4, 'bawal_lumabase', '$2y$10$AbbaRwdeScQJxKMic4l3AeMq/rVJKZva5WZ4sG0HW1EBRBingND.u', 'jamesconcepcion122@gmail.com', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

CREATE TABLE `vendor` (
  `vendor_id` int(11) NOT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `Email` varchar(500) NOT NULL,
  `Contact_person` varchar(500) NOT NULL,
  `Status` enum('Active','Inactive','','') NOT NULL DEFAULT 'Active',
  `Archive` enum('YES','NO') NOT NULL DEFAULT 'NO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor`
--

INSERT INTO `vendor` (`vendor_id`, `vendor_name`, `contact_info`, `address`, `Email`, `Contact_person`, `Status`, `Archive`) VALUES
(1, 'BDO', '09126238701', 'adgagvass', 'at@gmail', 'Sarah', 'Active', 'NO'),
(2, 'China Bank', '0912345678', '123 Tondo Manila , fund Street', 'chinaBank@gmail.com', 'Joseph Yis', 'Active', 'NO'),
(3, '', '', '', '', '', 'Active', 'NO'),
(4, '', '', '', '', '', 'Active', 'NO');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `email` varchar(160) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(40) DEFAULT '30 days',
  `is_active` varchar(500) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Archive` enum('YES','NO','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`vendor_id`, `name`, `contact_person`, `email`, `phone`, `address_line`, `payment_terms`, `is_active`, `created_at`, `updated_at`, `Archive`) VALUES
(1, 'lpg', 'Marikit', 'at@gmail', '05621314', 'adgagva', '45', 'Active', '2025-08-27 02:01:24', '2025-08-27 02:04:55', 'NO');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adjustment`
--
ALTER TABLE `adjustment`
  ADD PRIMARY KEY (`adjustID`);

--
-- Indexes for table `allocationadjustment`
--
ALTER TABLE `allocationadjustment`
  ADD PRIMARY KEY (`allocateadjustmentID`),
  ADD KEY `allocationID` (`allocationID`);

--
-- Indexes for table `approver`
--
ALTER TABLE `approver`
  ADD PRIMARY KEY (`approver_id`);

--
-- Indexes for table `ap_adjustments`
--
ALTER TABLE `ap_adjustments`
  ADD PRIMARY KEY (`adjustment_id`),
  ADD KEY `idx_ap_adj_bill` (`bill_id`);

--
-- Indexes for table `ap_bills`
--
ALTER TABLE `ap_bills`
  ADD PRIMARY KEY (`bill_id`),
  ADD KEY `idx_ap_bill_vendor` (`vendor_id`),
  ADD KEY `idx_ap_bill_due` (`due_date`);

--
-- Indexes for table `ap_payments`
--
ALTER TABLE `ap_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_ap_pay_bill` (`LoanID`),
  ADD KEY `idx_ap_pay_date` (`payment_date`),
  ADD KEY `requestID` (`requestID`);

--
-- Indexes for table `ar_adjustments`
--
ALTER TABLE `ar_adjustments`
  ADD PRIMARY KEY (`adjustment_id`);

--
-- Indexes for table `ar_collections`
--
ALTER TABLE `ar_collections`
  ADD PRIMARY KEY (`collection_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `ar_invoices`
--
ALTER TABLE `ar_invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`bankID`);

--
-- Indexes for table `budgetplanning`
--
ALTER TABLE `budgetplanning`
  ADD PRIMARY KEY (`bugdetID`);

--
-- Indexes for table `chartofaccount`
--
ALTER TABLE `chartofaccount`
  ADD PRIMARY KEY (`accountID`);

--
-- Indexes for table `collection_plan`
--
ALTER TABLE `collection_plan`
  ADD PRIMARY KEY (`planID`);

--
-- Indexes for table `costallocation`
--
ALTER TABLE `costallocation`
  ADD PRIMARY KEY (`allocationID`),
  ADD KEY `Deptbudget` (`Deptbudget`),
  ADD KEY `accountID` (`accountID`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `uq_customers_name` (`name`);

--
-- Indexes for table `departmentbudget`
--
ALTER TABLE `departmentbudget`
  ADD PRIMARY KEY (`Deptbudget`);

--
-- Indexes for table `details`
--
ALTER TABLE `details`
  ADD PRIMARY KEY (`entriesID`);

--
-- Indexes for table `entries`
--
ALTER TABLE `entries`
  ADD PRIMARY KEY (`journalID`),
  ADD KEY `periodID` (`periodID`);

--
-- Indexes for table `follow`
--
ALTER TABLE `follow`
  ADD PRIMARY KEY (`reminderID`),
  ADD KEY `InvoiceID` (`InvoiceID`),
  ADD KEY `planID` (`planID`);

--
-- Indexes for table `funds`
--
ALTER TABLE `funds`
  ADD PRIMARY KEY (`fundsID`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`LoanID`),
  ADD KEY `VendorID` (`VendorID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`paymentID`);

--
-- Indexes for table `paymentmethod`
--
ALTER TABLE `paymentmethod`
  ADD PRIMARY KEY (`payment_method_id`);

--
-- Indexes for table `periods`
--
ALTER TABLE `periods`
  ADD PRIMARY KEY (`period_id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`receiptID`),
  ADD KEY `paymentID` (`paymentID`);

--
-- Indexes for table `request`
--
ALTER TABLE `request`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `employeeID` (`employeeID`),
  ADD KEY `allocationID` (`allocationID`),
  ADD KEY `LoanID` (`LoanID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`vendor_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`),
  ADD UNIQUE KEY `uq_vendors_name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allocationadjustment`
--
ALTER TABLE `allocationadjustment`
  MODIFY `allocateadjustmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `approver`
--
ALTER TABLE `approver`
  MODIFY `approver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ap_adjustments`
--
ALTER TABLE `ap_adjustments`
  MODIFY `adjustment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ap_bills`
--
ALTER TABLE `ap_bills`
  MODIFY `bill_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ap_payments`
--
ALTER TABLE `ap_payments`
  MODIFY `payment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `ar_adjustments`
--
ALTER TABLE `ar_adjustments`
  MODIFY `adjustment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ar_collections`
--
ALTER TABLE `ar_collections`
  MODIFY `collection_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `ar_invoices`
--
ALTER TABLE `ar_invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `bankID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `budgetplanning`
--
ALTER TABLE `budgetplanning`
  MODIFY `bugdetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chartofaccount`
--
ALTER TABLE `chartofaccount`
  MODIFY `accountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `collection_plan`
--
ALTER TABLE `collection_plan`
  MODIFY `planID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `costallocation`
--
ALTER TABLE `costallocation`
  MODIFY `allocationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departmentbudget`
--
ALTER TABLE `departmentbudget`
  MODIFY `Deptbudget` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `details`
--
ALTER TABLE `details`
  MODIFY `entriesID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT for table `entries`
--
ALTER TABLE `entries`
  MODIFY `journalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `follow`
--
ALTER TABLE `follow`
  MODIFY `reminderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `funds`
--
ALTER TABLE `funds`
  MODIFY `fundsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `LoanID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `paymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `paymentmethod`
--
ALTER TABLE `paymentmethod`
  MODIFY `payment_method_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `periods`
--
ALTER TABLE `periods`
  MODIFY `period_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `receipt`
--
ALTER TABLE `receipt`
  MODIFY `receiptID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `request`
--
ALTER TABLE `request`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `allocationadjustment`
--
ALTER TABLE `allocationadjustment`
  ADD CONSTRAINT `allocationadjustment_ibfk_1` FOREIGN KEY (`allocationID`) REFERENCES `costallocation` (`allocationID`);

--
-- Constraints for table `ap_adjustments`
--
ALTER TABLE `ap_adjustments`
  ADD CONSTRAINT `fk_ap_adj_bill` FOREIGN KEY (`bill_id`) REFERENCES `ap_bills` (`bill_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ap_bills`
--
ALTER TABLE `ap_bills`
  ADD CONSTRAINT `fk_ap_bill_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON UPDATE CASCADE;

--
-- Constraints for table `ap_payments`
--
ALTER TABLE `ap_payments`
  ADD CONSTRAINT `ap_payments_ibfk_1` FOREIGN KEY (`LoanID`) REFERENCES `loan` (`LoanID`),
  ADD CONSTRAINT `ap_payments_ibfk_2` FOREIGN KEY (`requestID`) REFERENCES `request` (`requestID`);

--
-- Constraints for table `ar_collections`
--
ALTER TABLE `ar_collections`
  ADD CONSTRAINT `ar_collections_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `ar_invoices` (`invoice_id`);

--
-- Constraints for table `ar_invoices`
--
ALTER TABLE `ar_invoices`
  ADD CONSTRAINT `ar_invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `costallocation`
--
ALTER TABLE `costallocation`
  ADD CONSTRAINT `costallocation_ibfk_1` FOREIGN KEY (`Deptbudget`) REFERENCES `departmentbudget` (`Deptbudget`),
  ADD CONSTRAINT `costallocation_ibfk_2` FOREIGN KEY (`accountID`) REFERENCES `chartofaccount` (`accountID`);

--
-- Constraints for table `entries`
--
ALTER TABLE `entries`
  ADD CONSTRAINT `entries_ibfk_1` FOREIGN KEY (`periodID`) REFERENCES `periods` (`period_id`);

--
-- Constraints for table `follow`
--
ALTER TABLE `follow`
  ADD CONSTRAINT `follow_ibfk_1` FOREIGN KEY (`planID`) REFERENCES `collection_plan` (`planID`),
  ADD CONSTRAINT `follow_ibfk_2` FOREIGN KEY (`InvoiceID`) REFERENCES `ar_invoices` (`invoice_id`);

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`VendorID`) REFERENCES `vendor` (`vendor_id`);

--
-- Constraints for table `receipt`
--
ALTER TABLE `receipt`
  ADD CONSTRAINT `receipt_ibfk_1` FOREIGN KEY (`paymentID`) REFERENCES `ar_collections` (`collection_id`);

--
-- Constraints for table `request`
--
ALTER TABLE `request`
  ADD CONSTRAINT `request_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `core3`.`custumer` (`customer_id`),
  ADD CONSTRAINT `request_ibfk_2` FOREIGN KEY (`employeeID`) REFERENCES `hr`.`employee` (`EmployeeID`),
  ADD CONSTRAINT `request_ibfk_3` FOREIGN KEY (`allocationID`) REFERENCES `costallocation` (`allocationID`),
  ADD CONSTRAINT `request_ibfk_4` FOREIGN KEY (`LoanID`) REFERENCES `loan` (`LoanID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
