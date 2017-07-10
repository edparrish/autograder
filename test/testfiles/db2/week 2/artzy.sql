-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 10, 2017 at 03:19 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `artzy`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE IF NOT EXISTS `addresses` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Address` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `City` varchar(20) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `State` varchar(20) COLLATE latin1_general_ci DEFAULT NULL,
  `Zip` varchar(10) COLLATE latin1_general_ci DEFAULT NULL,
  `Country` varchar(20) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Customer addresses' AUTO_INCREMENT=8 ;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`ID`, `Address`, `City`, `State`, `Zip`, `Country`) VALUES
(1, '221A Baker St.', 'London', NULL, NULL, 'England'),
(2, '123 Lake St', 'St. Paul', 'Minnesota', '12345', 'US'),
(3, '2345 Apple St.', 'London', NULL, NULL, 'England'),
(4, '123 Rocky Road', 'Bedrock', 'CA', '12345', '555-1234'),
(5, '123 Rocky Road', 'Bedrock', 'CA', '12345', '555-1234'),
(6, '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `ParentID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentID_idx` (`ParentID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=11 ;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`ID`, `CategoryName`, `ParentID`) VALUES
(1, 'Index', NULL),
(2, 'Surfaces', 1),
(3, 'Brushes', 1),
(4, 'Paints', 1),
(5, 'Paper', 2),
(6, 'Canvas', 2),
(7, 'Large', 3),
(8, 'Small', 3),
(9, 'Acrylic', 4),
(10, 'Oil', 4);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LName` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `FName` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `AddressID` int(10) unsigned DEFAULT NULL,
  `Phone` varchar(16) COLLATE latin1_general_ci DEFAULT NULL,
  `Email` varchar(100) COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LName_idx` (`LName`),
  KEY `FName_idx` (`FName`),
  KEY `AddressID` (`AddressID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=8 ;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`ID`, `LName`, `FName`, `AddressID`, `Phone`, `Email`) VALUES
(1, 'Bond', 'James', 1, '123-456', 'jbond@mi7.uk'),
(2, 'Jones', 'John', 2, '123-4567', 'jjones@sow.com'),
(3, 'Newton', 'Issac', 3, '123-457', 'inewton@cambridge.uk'),
(4, 'Bond', 'James', 1, '123-456', 'jbond@mi7.uk'),
(5, 'Flintrock', 'Fred', 4, '555-1234', NULL),
(6, 'Flintrock', 'Fred', 5, '555-1234', NULL),
(7, '', '', 6, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orderitems`
--

DROP TABLE IF EXISTS `orderitems`;
CREATE TABLE IF NOT EXISTS `orderitems` (
  `OrderID` int(10) unsigned NOT NULL DEFAULT '0',
  `ItemNbr` smallint(6) NOT NULL DEFAULT '0',
  `ProductID` int(10) unsigned NOT NULL DEFAULT '0',
  `PriceEach` float(6,2) NOT NULL DEFAULT '0.00',
  `Quantity` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`OrderID`,`ItemNbr`),
  KEY `ProductID_idx` (`ProductID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`OrderID`, `ItemNbr`, `ProductID`, `PriceEach`, `Quantity`) VALUES
(1, 1, 1, 22.50, 1),
(2, 1, 4, 6.25, 3),
(3, 1, 3, 4.75, 1),
(3, 2, 4, 1.00, 10);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CustomerID` int(10) unsigned NOT NULL DEFAULT '0',
  `OrderDate` date NOT NULL DEFAULT '0000-00-00',
  `OrderStatus` enum('Initial','Confirmed','Shipped','Backorder') COLLATE latin1_general_ci DEFAULT NULL,
  `ProductTotal` float(8,4) DEFAULT NULL,
  `ShippingTotal` float(8,4) DEFAULT NULL,
  `TaxTotal` float(8,4) DEFAULT NULL,
  `ShipName` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `AddressID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CustomerID_idx` (`CustomerID`),
  KEY `AddressId_idx` (`AddressID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=4 ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`ID`, `CustomerID`, `OrderDate`, `OrderStatus`, `ProductTotal`, `ShippingTotal`, `TaxTotal`, `ShipName`, `AddressID`) VALUES
(1, 1, '2002-02-01', 'Shipped', 123.4500, 1.2300, 5.6700, 'James Bond', 1),
(2, 2, '2002-02-01', 'Backorder', 123.4500, 1.2300, 5.6700, 'John Jones', 2),
(3, 1, '2002-02-03', 'Initial', 453.4500, 4.2300, 8.7600, 'James Bond', 3);

-- --------------------------------------------------------

--
-- Table structure for table `productcategory`
--

DROP TABLE IF EXISTS `productcategory`;
CREATE TABLE IF NOT EXISTS `productcategory` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ProductID` int(10) unsigned NOT NULL DEFAULT '0',
  `CategoryID` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ProductID_idx` (`ProductID`),
  KEY `CategoryID_idx` (`CategoryID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=8 ;

--
-- Dumping data for table `productcategory`
--

INSERT INTO `productcategory` (`ID`, `ProductID`, `CategoryID`) VALUES
(1, 1, 2),
(2, 2, 7),
(3, 3, 8),
(4, 4, 10),
(5, 5, 9),
(6, 6, 8),
(7, 1, 6);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ProductName` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `ProductDescription` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `Path2Image` varchar(250) COLLATE latin1_general_ci DEFAULT NULL,
  `SupplierID` int(10) unsigned NOT NULL DEFAULT '0',
  `Price` float(4,2) DEFAULT NULL,
  `InStock` int(11) DEFAULT NULL,
  `Weight` float(4,2) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SupplierIDIdx` (`SupplierID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=7 ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ID`, `ProductName`, `ProductDescription`, `Path2Image`, `SupplierID`, `Price`, `InStock`, `Weight`) VALUES
(1, 'Canvas', 'Good canvas for quality paint', 'canvas2.gif', 2, 22.50, 30, NULL),
(2, 'Brush,Big', 'Big brush for large areas', 'brushlarge.gif', 1, 4.75, 47, NULL),
(3, 'Brush,Small', 'Small brush of fine material', 'brush.gif', 1, 3.75, 34, NULL),
(4, 'Oil Paint', 'Top quality oil paint', 'oil-tube.gif', 3, 6.25, 24, NULL),
(5, 'Acry. Paint', 'Top quality acrylic paint', 'tube2.gif', 3, 6.50, 65, NULL),
(6, 'Brusher,Hiliner', 'Fine brush for fine work', 'brush2.gif', 1, 4.50, 10, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchorders`
--

DROP TABLE IF EXISTS `purchorders`;
CREATE TABLE IF NOT EXISTS `purchorders` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ProductID` int(10) unsigned NOT NULL DEFAULT '0',
  `Qty` int(10) unsigned NOT NULL DEFAULT '0',
  `Cost` float(4,2) DEFAULT NULL,
  `DateOrdered` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ProductID_idx` (`ProductID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `purchorders`
--

INSERT INTO `purchorders` (`ID`, `ProductID`, `Qty`, `Cost`, `DateOrdered`) VALUES
(1, 2, 25, 1.25, '2001-10-15'),
(2, 3, 25, 1.10, '2001-10-16'),
(3, 1, 20, 12.25, '2001-10-02'),
(4, 4, 25, 3.30, '2001-10-22'),
(5, 5, 25, 2.47, '2001-10-23');

-- --------------------------------------------------------

--
-- Table structure for table `shoppingcarts`
--

DROP TABLE IF EXISTS `shoppingcarts`;
CREATE TABLE IF NOT EXISTS `shoppingcarts` (
  `CartID` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `ProductID` int(10) unsigned NOT NULL DEFAULT '0',
  `AddDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `PriceEach` float(6,2) DEFAULT NULL,
  `Quantity` int(10) unsigned DEFAULT '1',
  PRIMARY KEY (`ProductID`,`CartID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `shoppingcarts`
--

INSERT INTO `shoppingcarts` (`CartID`, `ProductID`, `AddDate`, `PriceEach`, `Quantity`) VALUES
('1', 1, '2004-05-20 16:31:02', 1.00, 1),
('3690f9bfe0a4b87e729d104cb51299cd', 1, '2004-05-20 16:35:20', 22.50, 1),
('94602cf20d2b3674edadfd559987edcd', 1, '2008-05-09 16:47:22', 22.50, 1);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `SupplierName` varchar(50) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `SupplierCode` varchar(10) COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SupplierCode_idx` (`SupplierCode`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=6 ;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`ID`, `SupplierName`, `SupplierCode`) VALUES
(1, 'Brush Bros.', 'BRSH'),
(2, 'Canvas Co.', 'CAN'),
(3, 'Painters,LLC', 'PT'),
(4, 'Unused Supplier', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
CREATE TABLE IF NOT EXISTS `surveys` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `LName` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `FName` varchar(50) COLLATE latin1_general_ci DEFAULT NULL,
  `Title` char(3) COLLATE latin1_general_ci DEFAULT NULL,
  `County` varchar(12) COLLATE latin1_general_ci DEFAULT NULL,
  `Comments` varchar(250) COLLATE latin1_general_ci DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=1 ;

--
-- Dumping data for table `surveys`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `UserName` varchar(16) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `Userpwd` varchar(16) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `Salt` varchar(4) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `Email` varchar(100) COLLATE latin1_general_ci DEFAULT NULL,
  `CustomerID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`UserName`),
  KEY `Userpwd_idx` (`Userpwd`),
  KEY `CustomerID_idx` (`CustomerID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserName`, `Userpwd`, `Salt`, `Email`, `CustomerID`) VALUES
('inewton', '2bc99e382a35c359', 'KDME', 'inewton@aol.com', 3),
('jbond', '27272e1c23ca3491', 'AEFG', 'jbond@mi7.uk', 1),
('jjones', '278b41cd00f230ab', 'WDPF', 'jjones@aol.com', 2),
('q', '68dbe55c49a557c9', 'DKAS', 'jbond@mi7.uk', NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
