CREATE DATABASE  IF NOT EXISTS `cst8257project` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `cst8257project`;
-- MySQL dump 10.13  Distrib 8.0.33, for Win64 (x86_64)
--
-- Host: localhost    Database: cst8257project
-- ------------------------------------------------------
-- Server version	8.0.33

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accessibility`
--

DROP TABLE IF EXISTS `accessibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accessibility` (
  `Accessibility_Code` varchar(16) NOT NULL,
  `Description` varchar(128) NOT NULL,
  PRIMARY KEY (`Accessibility_Code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accessibility`
--

LOCK TABLES `accessibility` WRITE;
/*!40000 ALTER TABLE `accessibility` DISABLE KEYS */;
INSERT INTO `accessibility` VALUES ('private','Accessible only by the owner '),('shared','Accessible by the owner and friends');
/*!40000 ALTER TABLE `accessibility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `album`
--

DROP TABLE IF EXISTS `album`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `album` (
  `Album_Id` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(256) NOT NULL,
  `Description` varchar(3000) DEFAULT NULL,
  `Owner_Id` varchar(16) NOT NULL,
  `Accessibility_Code` varchar(16) NOT NULL,
  PRIMARY KEY (`Album_Id`),
  KEY `Owner` (`Owner_Id`),
  KEY `Accessibility` (`Accessibility_Code`),
  CONSTRAINT `Album_Accessibility_FK` FOREIGN KEY (`Accessibility_Code`) REFERENCES `accessibility` (`Accessibility_Code`),
  CONSTRAINT `Album_User_FK` FOREIGN KEY (`Owner_Id`) REFERENCES `user` (`UserId`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comment` (
  `Comment_Id` int NOT NULL AUTO_INCREMENT,
  `Author_Id` varchar(16) NOT NULL,
  `Picture_Id` int NOT NULL,
  `Comment_Text` varchar(3000) NOT NULL,
  PRIMARY KEY (`Comment_Id`),
  KEY `Author_Index` (`Author_Id`),
  KEY `Comment_Picture_Index` (`Picture_Id`),
  CONSTRAINT `Comment_Picture_FK` FOREIGN KEY (`Picture_Id`) REFERENCES `picture` (`Picture_Id`),
  CONSTRAINT `Comment_User_FK` FOREIGN KEY (`Author_Id`) REFERENCES `user` (`UserId`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `friendship`
--

DROP TABLE IF EXISTS `friendship`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `friendship` (
  `Friend_RequesterId` varchar(16) NOT NULL,
  `Friend_RequesteeId` varchar(16) NOT NULL,
  `Status` varchar(16) NOT NULL,
  PRIMARY KEY (`Friend_RequesterId`,`Friend_RequesteeId`),
  KEY `FriendShip_Student_FK2` (`Friend_RequesteeId`),
  KEY `Status` (`Status`),
  CONSTRAINT `Friendship_Status_FK` FOREIGN KEY (`Status`) REFERENCES `friendshipstatus` (`Status_Code`),
  CONSTRAINT `FriendShip_User_FK1` FOREIGN KEY (`Friend_RequesterId`) REFERENCES `user` (`UserId`),
  CONSTRAINT `FriendShip_User_FK2` FOREIGN KEY (`Friend_RequesteeId`) REFERENCES `user` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `friendshipstatus`
--

DROP TABLE IF EXISTS `friendshipstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `friendshipstatus` (
  `Status_Code` varchar(16) NOT NULL,
  `Description` varchar(120) NOT NULL,
  PRIMARY KEY (`Status_Code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `friendshipstatus`
--

LOCK TABLES `friendshipstatus` WRITE;
/*!40000 ALTER TABLE `friendshipstatus` DISABLE KEYS */;
INSERT INTO `friendshipstatus` VALUES ('accepted','The request to become a friend has been accepted'),('request','A request has been sent to become a friend');
/*!40000 ALTER TABLE `friendshipstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `picture`
--

DROP TABLE IF EXISTS `picture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `picture` (
  `Picture_Id` int NOT NULL AUTO_INCREMENT,
  `Album_Id` int NOT NULL,
  `File_Name` varchar(256) NOT NULL,
  `Title` varchar(256) NOT NULL,
  `Description` varchar(3000) DEFAULT NULL,
  PRIMARY KEY (`Picture_Id`),
  KEY `Album_Id_Index` (`Album_Id`),
  CONSTRAINT `Picture_Album_FK` FOREIGN KEY (`Album_Id`) REFERENCES `album` (`Album_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `UserId` varchar(16) NOT NULL,
  `Name` varchar(256) NOT NULL,
  `Phone` varchar(16) NOT NULL,
  `Password` varchar(256) NOT NULL,
  PRIMARY KEY (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

