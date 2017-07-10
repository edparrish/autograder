# Ed Parrish
# phpMyAdmin SQL Dump
# version 2.5.2
# http://www.phpmyadmin.net
#
# Host: localhost
# Generation Time: Mar 02, 2004 at 04:19 PM
# Server version: 4.0.14
# PHP Version: 4.3.4
#
# Database : `artzy`
#

# --------------------------------------------------------

#
# Table structure for table `addresses`
#

DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `Address` varchar(50) NOT NULL default '',
  `City` varchar(20) NOT NULL default '',
  `State` varchar(20) default NULL,
  `Zip` varchar(10) default NULL,
  `Country` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=InnoDB COMMENT='Customer addresses' AUTO_INCREMENT=7 ;

#
# Dumping data for table `addresses`
#

INSERT INTO `addresses` VALUES (1, '221A Baker St.', 'London', NULL, NULL, 'England');
INSERT INTO `addresses` VALUES (2, '123 Lake St', 'St. Paul', 'Minnesota', '12345', 'US');
INSERT INTO `addresses` VALUES (3, '2345 Apple St.', 'London', NULL, NULL, 'England');
INSERT INTO `addresses` VALUES (4, '123 Rocky Road', 'Bedrock', 'CA', '12345', '555-1234');
INSERT INTO `addresses` VALUES (5, '123 Rocky Road', 'Bedrock', 'CA', '12345', '555-1234');
INSERT INTO `addresses` VALUES (6, '', '', '', '', '');

# --------------------------------------------------------

#
# Table structure for table `categories`
#

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `CategoryName` varchar(50) NOT NULL default '',
  `ParentID` int(10) unsigned default NULL,
  PRIMARY KEY  (`ID`),
  KEY `ParentID_idx` (`ParentID`)
) TYPE=InnoDB AUTO_INCREMENT=11 ;

#
# Dumping data for table `categories`
#

INSERT INTO `categories` VALUES (1, 'Index', NULL);
INSERT INTO `categories` VALUES (2, 'Surfaces', 1);
INSERT INTO `categories` VALUES (3, 'Brushes', 1);
INSERT INTO `categories` VALUES (4, 'Paints', 1);
INSERT INTO `categories` VALUES (5, 'Paper', 2);
INSERT INTO `categories` VALUES (6, 'Canvas', 2);
INSERT INTO `categories` VALUES (7, 'Large', 3);
INSERT INTO `categories` VALUES (8, 'Small', 3);
INSERT INTO `categories` VALUES (9, 'Acrylic', 4);
INSERT INTO `categories` VALUES (10, 'Oil', 4);

# --------------------------------------------------------

#
# Table structure for table `customers`
#

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `LName` varchar(50) NOT NULL default '',
  `FName` varchar(50) default NULL,
  `AddressID` int(10) unsigned default NULL,
  `Phone` varchar(16) default NULL,
  `Email` varchar(100) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `LName_idx` (`LName`),
  KEY `FName_idx` (`FName`)
) TYPE=InnoDB AUTO_INCREMENT=8 ;

#
# Dumping data for table `customers`
#

INSERT INTO `customers` VALUES (1, 'Bond', 'James', 1, '123-456', 'jbond@mi7.uk');
INSERT INTO `customers` VALUES (2, 'Jones', 'John', 2, '123-4567', 'jjones@sow.com');
INSERT INTO `customers` VALUES (3, 'Newton', 'Issac', 3, '123-457', 'inewton@cambridge.uk');
INSERT INTO `customers` VALUES (4, 'Bond', 'James', 1, '123-456', 'jbond@mi7.uk');
INSERT INTO `customers` VALUES (5, 'Flintrock', 'Fred', 4, '555-1234', NULL);
INSERT INTO `customers` VALUES (6, 'Flintrock', 'Fred', 5, '555-1234', NULL);
INSERT INTO `customers` VALUES (7, '', '', 6, '', NULL);

# --------------------------------------------------------

#
# Table structure for table `orders`
#

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `CustomerID` int(10) unsigned NOT NULL default '0',
  `OrderDate` date NOT NULL default '0000-00-00',
  `OrderStatus` enum('Initial','Confirmed','Shipped','Backorder') default NULL,
  `ProductTotal` float(8,4) default NULL,
  `ShippingTotal` float(8,4) default NULL,
  `TaxTotal` float(8,4) default NULL,
  `ShipName` varchar(50) NOT NULL default '',
  `AddressID` int(10) unsigned default NULL,
  PRIMARY KEY  (`ID`),
  KEY `CustomerID_idx` (`CustomerID`),
  CONSTRAINT `0_185` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`ID`)
) TYPE=InnoDB AUTO_INCREMENT=4 ;

#
# Dumping data for table `orders`
#

INSERT INTO `orders` VALUES (1, 1, '2002-02-01', 'Shipped', '123.4500', '1.2300', '5.6700', 'James Bond', 1);
INSERT INTO `orders` VALUES (2, 2, '2002-02-01', 'Backorder', '123.4500', '1.2300', '5.6700', 'John Jones', 2);
INSERT INTO `orders` VALUES (3, 1, '2002-02-03', 'Initial', '453.4500', '4.2300', '8.7600', 'James Bond', 3);

# --------------------------------------------------------

#
# Table structure for table `products`
#

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `ProductName` varchar(50) NOT NULL default '',
  `ProductDescription` varchar(255) default NULL,
  `Path2Image` varchar(250) default NULL,
  `SupplierID` int(10) unsigned NOT NULL default '0',
  `Price` float(4,2) default NULL,
  `InStock` int(11) default NULL,
  `Weight` float(4,2) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `SupplierIDIdx` (`SupplierID`)
) TYPE=InnoDB AUTO_INCREMENT=7 ;

#
# Dumping data for table `products`
#

INSERT INTO `products` VALUES (1, 'Canvas', 'Good canvas for quality paint', 'canvas2.gif', 2, '22.50', 30, NULL);
INSERT INTO `products` VALUES (2, 'Brush,Big', 'Big brush for large areas', 'brushlarge.gif', 1, '4.75', 47, NULL);
INSERT INTO `products` VALUES (3, 'Brush,Small', 'Small brush of fine material', 'brush.gif', 1, '3.75', 34, NULL);
INSERT INTO `products` VALUES (4, 'Oil Paint', 'Top quality oil paint', 'oil-tube.gif', 3, '6.25', 24, NULL);
INSERT INTO `products` VALUES (5, 'Acry. Paint', 'Top quality acrylic paint', 'tube2.gif', 3, '6.50', 65, NULL);
INSERT INTO `products` VALUES (6, 'Brusher,Hiliner', 'Fine brush for fine work', 'brush2.gif', 1, '4.50', 10, NULL);

# --------------------------------------------------------

#
# Table structure for table `productcategory`
#

DROP TABLE IF EXISTS `productcategory`;
CREATE TABLE `productcategory` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `ProductID` int(10) unsigned NOT NULL default '0',
  `CategoryID` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `ProductID_idx` (`ProductID`),
  KEY `CategoryID_idx` (`CategoryID`),
  CONSTRAINT `0_178` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`),
  CONSTRAINT `0_179` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`ID`)
) TYPE=InnoDB AUTO_INCREMENT=8 ;

#
# Dumping data for table `productcategory`
#

INSERT INTO `productcategory` VALUES (1, 1, 2);
INSERT INTO `productcategory` VALUES (2, 2, 7);
INSERT INTO `productcategory` VALUES (3, 3, 8);
INSERT INTO `productcategory` VALUES (4, 4, 10);
INSERT INTO `productcategory` VALUES (5, 5, 9);
INSERT INTO `productcategory` VALUES (6, 6, 8);
INSERT INTO `productcategory` VALUES (7, 1, 6);

# --------------------------------------------------------

#
# Table structure for table `purchorders`
#

DROP TABLE IF EXISTS `purchorders`;
CREATE TABLE `purchorders` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `ProductID` int(10) unsigned NOT NULL default '0',
  `Qty` int(10) unsigned NOT NULL default '0',
  `Cost` float(4,2) default NULL,
  `DateOrdered` date default NULL,
  PRIMARY KEY  (`ID`),
  KEY `ProductID_idx` (`ProductID`),
  CONSTRAINT `0_175` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`)
) TYPE=InnoDB AUTO_INCREMENT=6 ;

#
# Dumping data for table `purchorders`
#

INSERT INTO `purchorders` VALUES (1, 2, 25, '1.25', '2001-10-15');
INSERT INTO `purchorders` VALUES (2, 3, 25, '1.10', '2001-10-16');
INSERT INTO `purchorders` VALUES (3, 1, 20, '12.25', '2001-10-02');
INSERT INTO `purchorders` VALUES (4, 4, 25, '3.30', '2001-10-22');
INSERT INTO `purchorders` VALUES (5, 5, 25, '2.47', '2001-10-23');

# --------------------------------------------------------

#
# Table structure for table `shoppingcarts`
#

DROP TABLE IF EXISTS `shoppingcarts`;
CREATE TABLE `shoppingcarts` (
  `ProductID` int(10) unsigned NOT NULL default '0',
  `SessionID` char(16) NOT NULL default '',
  `AddDate` date default NULL,
  `PriceEach` float(6,2) default NULL,
  `Quantity` int(10) unsigned default '1',
  PRIMARY KEY  (`ProductID`,`SessionID`),
  CONSTRAINT `0_181` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`)
) TYPE=InnoDB;

#
# Dumping data for table `shoppingcarts`
#

INSERT INTO `shoppingcarts` VALUES (1, '149577645', '2002-02-28', '22.50', 6);
INSERT INTO `shoppingcarts` VALUES (2, '483220544', '2002-03-01', '4.75', 1);
INSERT INTO `shoppingcarts` VALUES (4, '149577645', '2002-02-28', '6.25', 4);
INSERT INTO `shoppingcarts` VALUES (4, '558912867', '2001-02-27', '6.25', 2);
INSERT INTO `shoppingcarts` VALUES (6, '149577645', '2002-02-28', '4.50', 1);

# --------------------------------------------------------

#
# Table structure for table `suppliers`
#

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `SupplierName` varchar(50) NOT NULL default '',
  `SupplierCode` varchar(10) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `SupplierCode_idx` (`SupplierCode`)
) TYPE=InnoDB AUTO_INCREMENT=6 ;

#
# Dumping data for table `suppliers`
#

INSERT INTO `suppliers` VALUES (2, 'Canvas Co.', 'CAN');
INSERT INTO `suppliers` VALUES (3, 'Painters,LLC', 'PT');
INSERT INTO `suppliers` VALUES (4, 'Mr. Unknown', NULL);
INSERT INTO `suppliers` VALUES (5, 'bonzo', 'abc');

# --------------------------------------------------------

#
# Table structure for table `surveys`
#

DROP TABLE IF EXISTS `surveys`;
CREATE TABLE `surveys` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `LName` varchar(50) default NULL,
  `FName` varchar(50) default NULL,
  `Title` char(3) default NULL,
  `County` varchar(12) default NULL,
  `Comments` varchar(250) default NULL,
  PRIMARY KEY  (`ID`)
) TYPE=InnoDB AUTO_INCREMENT=1 ;

#
# Dumping data for table `surveys`
#


# --------------------------------------------------------

#
# Table structure for table `users`
#

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `UserName` varchar(16) NOT NULL default '',
  `Userpwd` varchar(16) NOT NULL default '',
  `Salt` varchar(4) NOT NULL default '',
  `Email` varchar(100) default NULL,
  `CustomerID` int(10) unsigned default NULL,
  PRIMARY KEY  (`UserName`),
  KEY `UserName_idx` (`UserName`),
  KEY `Userpwd_idx` (`Userpwd`),
  KEY `CustomerID_idx` (`CustomerID`)
) TYPE=InnoDB;

#
# Dumping data for table `users`
#

INSERT INTO `users` VALUES ('inewton', '2bc99e382a35c359', 'KDME', 'inewton@aol.com', 3);
INSERT INTO `users` VALUES ('jbond', '27272e1c23ca3491', 'AEFG', 'jbond@mi7.uk', 1);
INSERT INTO `users` VALUES ('jjones', '278b41cd00f230ab', 'WDPF', 'jjones@aol.com', 2);
INSERT INTO `users` VALUES ('q', '68dbe55c49a557c9', 'DKAS', 'jbond@mi7.uk', NULL);

#
# Table structure for table `orderitems`
#

DROP TABLE IF EXISTS `orderitems`;
CREATE TABLE `orderitems` (
  `OrderID` int(10) unsigned NOT NULL default '0',
  `ItemNbr` smallint(6) NOT NULL default '0',
  `ProductID` int(10) unsigned NOT NULL default '0',
  `PriceEach` float(6,2) NOT NULL default '0.00',
  `Quantity` int(11) NOT NULL default '0',
  PRIMARY KEY  (`OrderID`,`ItemNbr`),
  KEY `OrderID_idx` (`OrderID`),
  KEY `ProductID_idx` (`ProductID`),
  CONSTRAINT `0_187` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`ID`),
  CONSTRAINT `0_188` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ID`)
) TYPE=InnoDB;

#
# Dumping data for table `orderitems`
#

INSERT INTO `orderitems` VALUES (1, 1, 1, '22.50', 1);
INSERT INTO `orderitems` VALUES (2, 1, 4, '6.25', 3);
INSERT INTO `orderitems` VALUES (3, 1, 3, '4.75', 1);
INSERT INTO `orderitems` VALUES (3, 2, 4, '1.00', 10);

# --------------------------------------------------------

