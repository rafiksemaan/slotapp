-- MySQL dump 10.13  Distrib 8.0.31, for Win64 (x86_64)
--
-- Host: localhost    Database: slotapp
-- ------------------------------------------------------
-- Server version	8.0.31

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
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'IGT','International Game Technology','2025-05-26 11:02:35','2025-05-26 11:02:35'),(2,'ARISTOCRAT','Aristocrat Leisure Limited','2025-05-26 11:02:35','2025-06-07 04:49:50'),(8,'EGT','Elite Gaming Technology','2025-05-27 05:46:56','2025-05-29 09:57:08'),(9,'GAMBEE','SUPERIOR GAMING EXPERIENCE','2025-05-27 08:16:51','2025-06-09 10:57:22'),(10,'MD','Magic Dreams','2025-05-27 08:19:03','2025-05-27 08:19:03'),(5,'NOVOMATIC','Novomatic AG','2025-05-26 11:02:35','2025-06-07 04:50:02'),(6,'BALLY','Bally Entertainment','2025-05-26 11:33:15','2025-06-07 04:49:56'),(11,'GOLD CLUB','Gold Club GameStar multi-game','2025-05-27 08:19:37','2025-06-09 11:02:04'),(13,'APEX','APEX pro gaming','2025-06-06 07:39:45','2025-06-09 10:58:20'),(14,'WMS','Williams Interactive','2025-06-07 04:50:48','2025-06-07 04:50:48'),(15,'SCIENTIFIC GAMES','Scientific Games Corporation','2025-06-13 08:11:00','2025-06-14 07:40:52'),(16,'KONAMI','Konami Gaming, Inc.','2025-06-13 08:11:00','2025-06-14 07:41:00');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guest_data`
--

DROP TABLE IF EXISTS `guest_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guest_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guest_code_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `upload_date` date NOT NULL,
  `drop_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `result_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `visits` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_guest_upload` (`guest_code_id`,`upload_date`),
  KEY `idx_upload_date` (`upload_date`),
  KEY `idx_guest_code` (`guest_code_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guest_data`
--

LOCK TABLES `guest_data` WRITE;
/*!40000 ALTER TABLE `guest_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `guest_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guest_uploads`
--

DROP TABLE IF EXISTS `guest_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guest_uploads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `upload_date` date NOT NULL,
  `upload_filename` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_by` int NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `upload_date` (`upload_date`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_upload_date` (`upload_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guest_uploads`
--

LOCK TABLES `guest_uploads` WRITE;
/*!40000 ALTER TABLE `guest_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `guest_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guests`
--

DROP TABLE IF EXISTS `guests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guests` (
  `guest_code_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `guest_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`guest_code_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guests`
--

LOCK TABLES `guests` WRITE;
/*!40000 ALTER TABLE `guests` DISABLE KEYS */;
/*!40000 ALTER TABLE `guests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username_time` (`username`,`attempt_time`),
  KEY `idx_ip_time` (`ip_address`,`attempt_time`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'logout','User logged out','::1','2025-05-26 11:32:35'),(2,1,'create_brand','Created brand: Bally','::1','2025-05-26 11:33:15'),(3,1,'create_machine','Created machine: 359','::1','2025-05-26 11:42:23'),(4,1,'delete_brand','Deleted brand: Konami','::1','2025-05-27 05:11:13'),(5,1,'delete_brand','Deleted brand: Scientific Games','::1','2025-05-27 05:11:23'),(6,1,'create_transaction','Created Handpay transaction for machine 359: $1,323.00','::1','2025-05-27 05:14:28'),(7,1,'create_machine','Created machine: 61','::1','2025-05-27 05:20:09'),(8,1,'delete_machine','Deleted machine: 61','::1','2025-05-27 05:43:46'),(9,1,'create_machine','Created machine: aaa','::1','2025-05-27 05:44:03'),(10,1,'delete_machine','Deleted machine: aaa','::1','2025-05-27 05:44:08'),(11,1,'create_brand','Created brand: sss','::1','2025-05-27 05:44:18'),(12,1,'update_brand','Updated brand: EGT','::1','2025-05-27 05:46:24'),(13,1,'update_brand','Updated brand: Bally','::1','2025-05-27 05:46:31'),(14,1,'delete_brand','Deleted brand: EGT','::1','2025-05-27 05:46:44'),(15,1,'create_brand','Created brand: EGT','::1','2025-05-27 05:46:56'),(16,1,'update_transaction','Updated transaction ID: 1','::1','2025-05-27 06:20:08'),(17,1,'create_machine','Created machine: 327','::1','2025-05-27 08:15:22'),(18,1,'create_machine','Created machine: 54','::1','2025-05-27 08:15:43'),(19,1,'create_brand','Created brand: Gambee','::1','2025-05-27 08:16:51'),(20,1,'update_brand','Updated brand: EGT','::1','2025-05-27 08:18:14'),(21,1,'update_brand','Updated brand: Gambee','::1','2025-05-27 08:18:18'),(22,1,'update_brand','Updated brand: Novomatic','::1','2025-05-27 08:18:28'),(23,1,'create_brand','Created brand: MD','::1','2025-05-27 08:19:03'),(24,1,'create_brand','Created brand: Gold Club','::1','2025-05-27 08:19:37'),(25,1,'create_machine','Created machine: 312','::1','2025-05-27 08:20:36'),(26,1,'create_transaction','Created Cash Drop transaction for machine 312: $1,388.00','::1','2025-05-27 08:21:09'),(27,1,'create_transaction','Created Cash Drop transaction for machine 327: $188.00','::1','2025-05-27 08:21:32'),(28,1,'create_transaction','Created Handpay transaction for machine 312: $1,900.00','::1','2025-05-27 08:22:35'),(29,1,'delete_transaction','Deleted transaction ID: 4','::1','2025-05-27 08:24:04'),(30,1,'create_transaction','Created Handpay transaction for machine 312: $1,999.00','::1','2025-05-27 08:24:23'),(31,1,'create_transaction','Created Refill transaction for machine 54: $125.00','::1','2025-05-27 08:31:46'),(32,1,'logout','User logged out','::1','2025-05-27 09:01:03'),(33,3,'logout','User logged out','::1','2025-05-27 09:03:37'),(34,2,'logout','User logged out','::1','2025-05-27 09:04:37'),(35,1,'create_machine','Created machine: 353','::1','2025-05-27 09:18:51'),(36,1,'update_machine','Updated machine: 353','::1','2025-05-27 09:18:55'),(37,1,'update_machine','Updated machine: 353','::1','2025-05-27 09:18:59'),(38,1,'update_machine','Updated machine: 353','::1','2025-05-27 09:19:03'),(39,1,'create_transaction','Created Cash Drop transaction for machine 353: $660.00','192.168.17.207','2025-05-27 13:01:28'),(40,1,'logout','User logged out','::1','2025-05-27 13:11:50'),(41,3,'logout','User logged out','::1','2025-05-27 17:53:35'),(42,1,'create_machine','Created machine: 61','::1','2025-05-27 17:54:35'),(43,1,'create_transaction','Created Refill transaction for machine 61: $125.00','::1','2025-05-27 17:55:13'),(44,1,'delete_transaction','Deleted transaction ID: 5','::1','2025-05-27 17:58:02'),(45,1,'create_machine','Created machine: 77','::1','2025-05-28 09:19:10'),(46,1,'create_transaction','Created Cash Drop transaction for machine 77: 1,200.00','::1','2025-05-28 09:19:32'),(47,1,'create_transaction','Created Handpay transaction for machine 77: 15.00','::1','2025-05-28 09:19:48'),(48,1,'create_transaction','Created Coins Drop transaction for machine 54: 266.00','::1','2025-05-28 09:22:46'),(49,1,'update_machine','Updated machine: 353','::1','2025-05-28 09:23:30'),(50,1,'create_machine','Created machine: 1','::1','2025-05-28 09:24:49'),(51,1,'create_transaction','Created Cash Drop transaction for machine 1: 144.00','::1','2025-05-28 09:25:04'),(52,1,'create_transaction','Created Ticket transaction for machine 1: 12.00','::1','2025-05-28 09:25:14'),(53,1,'update_transaction','Updated transaction ID: 10','::1','2025-05-28 09:25:34'),(54,1,'update_transaction','Updated transaction ID: 9','::1','2025-05-28 09:26:40'),(55,1,'delete_transaction','Deleted transaction ID: 9','::1','2025-05-28 09:27:50'),(56,1,'create_transaction','Created Cash Drop transaction for machine 77: 1,200.00','::1','2025-05-28 09:28:18'),(57,1,'create_machine','Created machine: 122','::1','2025-05-28 09:42:27'),(58,1,'create_transaction','Created Handpay transaction for machine 122: 10.00','::1','2025-05-28 09:42:43'),(59,1,'delete_transaction','Deleted transaction ID: 10','::1','2025-05-28 09:43:11'),(60,1,'delete_transaction','Deleted transaction ID: 14','::1','2025-05-28 09:43:20'),(61,1,'create_transaction','Created Handpay transaction for machine 77: 10.00','::1','2025-05-28 09:43:35'),(62,1,'create_transaction','Created Cash Drop transaction for machine 77: 100.00','::1','2025-05-28 09:43:43'),(63,1,'update_machine','Updated machine: 770','::1','2025-05-28 09:44:21'),(64,1,'update_machine','Updated machine: 770','::1','2025-05-28 09:44:50'),(65,1,'create_machine','Created machine: 999','::1','2025-05-28 09:45:26'),(66,1,'create_transaction','Created Handpay transaction for machine 999: 10.00','::1','2025-05-28 09:45:34'),(67,1,'create_transaction','Created Cash Drop transaction for machine 999: 88.50','::1','2025-05-28 09:45:46'),(68,1,'update_machine','Updated machine: 1','::1','2025-05-28 09:46:26'),(69,1,'create_brand','Created brand: saleh','::1','2025-05-28 09:47:16'),(70,1,'create_machine','Created machine: 8','::1','2025-05-28 09:47:36'),(71,1,'create_transaction','Created Ticket transaction for machine 8: 120.00','::1','2025-05-28 09:47:49'),(72,1,'create_transaction','Created Cash Drop transaction for machine 8: 500.00','::1','2025-05-28 09:47:59'),(73,1,'create_machine','Created machine: 777','::1','2025-05-28 11:32:05'),(74,1,'create_transaction','Created Cash Drop transaction for machine 777: 100.00','::1','2025-05-28 11:32:16'),(75,1,'create_transaction','Created Ticket transaction for machine 777: 20.00','::1','2025-05-28 11:32:25'),(76,1,'delete_machine','Deleted machine: 999','::1','2025-05-28 11:32:47'),(77,1,'delete_machine','Deleted machine: 8','::1','2025-05-28 11:33:33'),(78,1,'delete_machine','Deleted machine: 777','::1','2025-05-29 06:13:53'),(79,1,'create_transaction','Created Cash Drop transaction for machine 770: 40.00','::1','2025-05-29 07:18:37'),(80,1,'create_machine','Created machine: 97','::1','2025-05-29 07:36:53'),(81,1,'create_transaction','Created Cash Drop transaction for machine 97: 200.00','::1','2025-05-29 07:37:08'),(82,1,'create_transaction','Created Ticket transaction for machine 97: 190.00','::1','2025-05-29 07:37:21'),(83,1,'delete_brand','Deleted brand: saleh','::1','2025-05-29 09:23:25'),(84,1,'update_brand','Updated brand: Bally','::1','2025-05-29 09:56:45'),(85,1,'update_brand','Updated brand: EGT','::1','2025-05-29 09:57:08'),(86,1,'update_brand','Updated brand: Gambee','::1','2025-05-29 09:57:46'),(87,1,'logout','User logged out','::1','2025-05-29 17:37:25'),(88,1,'create_transaction','Created Cash Drop transaction for machine 122: 115.00','::1','2025-05-30 09:52:47'),(89,1,'update_transaction','Updated transaction ID: 27','::1','2025-05-30 09:56:38'),(90,1,'create_transaction','Created Cash Drop transaction for machine 359: 1,250.00','::1','2025-05-30 09:57:08'),(91,1,'logout','User logged out','::1','2025-05-30 11:31:03'),(92,1,'logout','User logged out','::1','2025-05-30 11:43:21'),(93,4,'create_transaction','Created Coins Drop transaction for machine 61: 198.00','::1','2025-05-30 11:54:18'),(94,4,'create_transaction','Created Refill transaction for machine 122: 125.00','::1','2025-05-30 17:16:11'),(95,4,'create_transaction','Created Handpay transaction for machine 1: 200.00','::1','2025-05-30 17:16:36'),(96,4,'logout','User logged out','::1','2025-05-31 08:44:55'),(97,3,'logout','User logged out','::1','2025-05-31 08:45:19'),(98,2,'logout','User logged out','::1','2025-05-31 08:45:36'),(99,3,'logout','User logged out','::1','2025-05-31 08:54:13'),(100,4,'logout','User logged out','::1','2025-05-31 08:55:20'),(101,5,'create_transaction','Created Cash Drop transaction for machine 359: 144.00','::1','2025-05-31 09:03:35'),(102,5,'create_transaction','Created Handpay transaction for machine 327: 122.00','::1','2025-05-31 09:03:44'),(103,5,'create_transaction','Created Cash Drop transaction for machine 1: 1,000.00','::1','2025-05-31 12:09:01'),(104,5,'update_transaction','Updated transaction ID: 32','::1','2025-05-31 12:09:10'),(105,5,'create_transaction','Created Handpay transaction for machine 61: 5,000.00','::1','2025-05-31 12:13:50'),(106,5,'update_transaction','Updated transaction ID: 35','::1','2025-05-31 12:14:07'),(107,5,'delete_transaction','Deleted transaction ID: 35','::1','2025-05-31 12:14:15'),(108,5,'update_transaction','Updated transaction ID: 33','::1','2025-05-31 12:14:34'),(109,5,'update_transaction','Updated transaction ID: 29','::1','2025-05-31 12:14:43'),(110,5,'update_transaction','Updated transaction ID: 30','::1','2025-05-31 12:14:52'),(111,5,'update_transaction','Updated transaction ID: 27','::1','2025-05-31 12:15:00'),(112,5,'update_transaction','Updated transaction ID: 6','::1','2025-05-31 12:15:08'),(113,5,'update_transaction','Updated transaction ID: 28','::1','2025-05-31 12:15:15'),(114,5,'update_transaction','Updated transaction ID: 8','::1','2025-05-31 12:15:23'),(115,5,'update_transaction','Updated transaction ID: 7','::1','2025-05-31 12:15:32'),(116,5,'update_transaction','Updated transaction ID: 15','::1','2025-05-31 12:15:41'),(117,5,'update_transaction','Updated transaction ID: 13','::1','2025-05-31 12:15:46'),(118,5,'update_transaction','Updated transaction ID: 12','::1','2025-05-31 12:15:55'),(119,5,'update_transaction','Updated transaction ID: 16','::1','2025-05-31 12:16:04'),(120,5,'update_transaction','Updated transaction ID: 11','::1','2025-05-31 12:16:10'),(121,5,'update_transaction','Updated transaction ID: 17','::1','2025-05-31 12:16:16'),(122,5,'update_transaction','Updated transaction ID: 26','::1','2025-05-31 12:16:26'),(123,5,'update_transaction','Updated transaction ID: 31','::1','2025-05-31 12:16:35'),(124,5,'update_transaction','Updated transaction ID: 24','::1','2025-05-31 12:16:45'),(125,5,'update_transaction','Updated transaction ID: 25','::1','2025-05-31 12:16:56'),(126,5,'update_transaction','Updated transaction ID: 25','::1','2025-05-31 12:40:17'),(127,1,'create_transaction','Created Cash Drop transaction for machine 97: 100.00','::1','2025-06-01 08:56:02'),(128,1,'create_transaction','Created Handpay transaction for machine 97: 50.00','::1','2025-06-01 08:56:10'),(129,1,'create_machine','Created machine: 325','::1','2025-06-01 09:09:25'),(130,1,'create_machine','Created machine: 354','::1','2025-06-01 09:10:04'),(131,1,'update_machine','Updated machine: 105','::1','2025-06-01 12:25:47'),(132,1,'update_machine','Updated machine: 122','::1','2025-06-01 12:26:04'),(133,1,'create_machine','Created machine: 338','::1','2025-06-01 12:30:20'),(134,1,'create_transaction','Created Cash Drop transaction for machine 338: 190.00','::1','2025-06-01 12:30:43'),(135,1,'update_machine','Updated machine: 276','::1','2025-06-01 12:31:54'),(136,1,'create_transaction','Created Ticket transaction for machine 105: 37.50','::1','2025-06-01 12:38:37'),(137,1,'create_transaction','Created Refill transaction for machine 61: 125.00','::1','2025-06-01 12:38:56'),(138,1,'create_transaction','Created Cash Drop transaction for machine 312: 500.00','::1','2025-06-02 05:21:35'),(139,1,'create_transaction','Created Cash Drop transaction for machine 105: 320.00','::1','2025-06-03 05:21:45'),(140,1,'create_transaction','Created Handpay transaction for machine 105: 36.00','::1','2025-06-03 05:21:55'),(141,1,'create_transaction','Created Refill transaction for machine 61: 125.00','::1','2025-06-03 05:22:02'),(142,1,'create_transaction','Created Handpay transaction for machine 61: 17.00','::1','2025-06-03 05:22:08'),(143,1,'create_transaction','Created Cash Drop transaction for machine 312: 500.00','::1','2025-06-03 05:22:20'),(144,1,'create_transaction','Created Ticket transaction for machine 312: 319.00','::1','2025-06-03 05:22:35'),(145,1,'create_transaction','Created Coins Drop transaction for machine 61: 223.00','::1','2025-06-03 05:31:11'),(146,1,'logout','User logged out','::1','2025-06-03 07:53:41'),(147,4,'logout','User logged out','::1','2025-06-03 07:54:04'),(148,4,'create_transaction','Created Coins Drop transaction for machine 61: 320.00','::1','2025-06-04 04:38:17'),(149,4,'create_transaction','Created Cash Drop transaction for machine 312: 120.00','::1','2025-06-04 04:38:25'),(150,4,'create_transaction','Created Handpay transaction for machine 770: 250.00','::1','2025-06-04 04:38:33'),(151,4,'create_transaction','Created Refill transaction for machine 61: 125.00','::1','2025-06-04 04:58:55'),(152,4,'create_transaction','Created Ticket transaction for machine 122: 103.00','::1','2025-06-04 04:59:49'),(153,4,'update_transaction','Updated transaction ID: 50','::1','2025-06-04 05:00:12'),(154,4,'logout','User logged out','::1','2025-06-04 16:03:55'),(155,4,'update_machine','Updated machine: 105','::1','2025-06-05 06:54:54'),(156,4,'update_machine','Updated machine: 122','::1','2025-06-05 06:55:13'),(157,4,'update_machine','Updated machine: 770','::1','2025-06-05 06:55:23'),(158,4,'update_machine','Updated machine: 61','::1','2025-06-05 06:55:32'),(159,4,'update_machine','Updated machine: 54','::1','2025-06-05 06:55:45'),(160,4,'update_machine','Updated machine: 325','::1','2025-06-05 06:55:51'),(161,4,'update_machine','Updated machine: 359','::1','2025-06-05 06:55:57'),(162,4,'update_machine','Updated machine: 354','::1','2025-06-05 06:56:04'),(163,4,'update_machine','Updated machine: 353','::1','2025-06-05 06:56:11'),(164,4,'update_machine','Updated machine: 338','::1','2025-06-05 06:56:18'),(165,4,'update_machine','Updated machine: 312','::1','2025-06-05 06:56:26'),(166,4,'update_machine','Updated machine: 276','::1','2025-06-05 06:56:33'),(167,4,'update_machine','Updated machine: 327','::1','2025-06-05 06:56:39'),(168,4,'create_machine_type','Created machine type: koskos','::1','2025-06-05 06:57:26'),(169,4,'delete_machine_type','Deleted machine type: koskos','::1','2025-06-05 06:58:25'),(170,4,'create_machine_type','Created machine type: sousou','::1','2025-06-05 07:31:59'),(171,4,'create_machine_type','Created machine type: lawlab','::1','2025-06-05 07:41:49'),(172,4,'create_machine_type','Created machine type: ssss','::1','2025-06-05 07:43:05'),(173,4,'create_machine_type','Created machine type: ssexy','::1','2025-06-05 07:45:52'),(174,4,'delete_machine_type','Deleted machine type: ssss','::1','2025-06-05 07:45:57'),(175,4,'delete_machine_type','Deleted machine type: ssexy','::1','2025-06-05 07:46:03'),(176,4,'delete_machine_type','Deleted machine type: sousou','::1','2025-06-05 07:46:10'),(177,4,'delete_machine_type','Deleted machine type: lawlab','::1','2025-06-05 07:46:20'),(178,4,'create_machine_type','Created machine type: Poker','::1','2025-06-05 10:32:41'),(179,4,'create_machine_type','Created machine type: sexy','::1','2025-06-05 10:44:51'),(180,4,'delete_machine_type','Deleted machine type: sexy','::1','2025-06-05 10:53:31'),(181,4,'delete_machine_type','Deleted machine type: Poker','::1','2025-06-05 11:00:59'),(182,4,'create_machine_type','Created machine type: asdd','::1','2025-06-05 11:01:06'),(183,4,'create_machine','Created machine: zzz','::1','2025-06-05 13:06:07'),(184,4,'delete_machine','Deleted machine: zzz','::1','2025-06-05 13:06:51'),(185,4,'delete_machine_type','Deleted machine type: asdd','::1','2025-06-05 13:07:14'),(186,4,'logout','User logged out','::1','2025-06-05 13:07:28'),(187,4,'create_machine_type','Created machine type: sawsan','::1','2025-06-06 05:24:42'),(188,4,'update_machine_type','Updated machine type: sawsan','::1','2025-06-06 05:27:10'),(189,4,'update_machine_type','Updated machine type: sawsan','::1','2025-06-06 05:27:13'),(190,4,'create_machine_type','Created machine type: loulou','::1','2025-06-06 05:27:26'),(191,4,'create_machine_type','Created machine type: guirgis','::1','2025-06-06 05:28:01'),(192,4,'delete_machine_type','Deleted machine type: loulou','::1','2025-06-06 05:28:05'),(193,4,'delete_machine_type','Deleted machine type: sawsan','::1','2025-06-06 05:28:09'),(194,4,'delete_machine_type','Deleted machine type: guirgis','::1','2025-06-06 05:28:12'),(195,4,'create_machine_type','Created machine type: Poker','::1','2025-06-06 07:39:15'),(196,4,'create_brand','Created brand: Apex','::1','2025-06-06 07:39:45'),(197,4,'create_machine','Created machine: 999','::1','2025-06-06 07:40:09'),(198,4,'create_transaction','Created Cash Drop transaction for machine 999: 1,439.00','::1','2025-06-06 07:40:32'),(199,4,'update_machine_type','Updated machine type: POKER','::1','2025-06-06 07:40:48'),(200,4,'update_brand','Updated brand: APEX','::1','2025-06-07 04:49:37'),(201,4,'update_brand','Updated brand: ARISTOCRAT','::1','2025-06-07 04:49:50'),(202,4,'update_brand','Updated brand: BALLY','::1','2025-06-07 04:49:56'),(203,4,'update_brand','Updated brand: NOVOMATIC','::1','2025-06-07 04:50:02'),(204,4,'update_brand','Updated brand: GOLD CLUB','::1','2025-06-07 04:50:11'),(205,4,'create_brand','Created brand: WMS','::1','2025-06-07 04:50:48'),(206,4,'update_machine','Updated machine: 105','::1','2025-06-07 06:40:25'),(207,4,'update_machine','Updated machine: 353','::1','2025-06-07 06:40:31'),(208,4,'update_machine','Updated machine: 999','::1','2025-06-07 06:40:39'),(209,4,'logout','User logged out','::1','2025-06-07 07:21:40'),(210,3,'logout','User logged out','::1','2025-06-07 07:22:07'),(211,4,'update_machine','Updated machine: 353','::1','2025-06-07 07:23:41'),(212,4,'create_machine_type','Created machine type: zz','::1','2025-06-07 08:16:46'),(213,4,'delete_machine_type','Deleted machine type: zz','::1','2025-06-07 08:16:51'),(214,4,'update_brand','Updated brand: APEX','::1','2025-06-07 08:17:01'),(215,4,'update_brand','Updated brand: APEX','::1','2025-06-07 08:17:06'),(216,4,'update_machine_type','Updated machine type: POKER','::1','2025-06-07 08:17:14'),(217,4,'update_machine','Updated machine: 61','::1','2025-06-07 09:18:01'),(218,4,'update_machine','Updated machine: 327','::1','2025-06-07 09:18:09'),(219,4,'update_machine','Updated machine: 359','::1','2025-06-07 09:18:17'),(220,4,'update_machine','Updated machine: 770','::1','2025-06-07 09:18:22'),(221,4,'update_machine','Updated machine: 54','::1','2025-06-07 09:18:29'),(222,4,'update_machine','Updated machine: 122','::1','2025-06-07 09:18:47'),(223,4,'update_machine','Updated machine: 276','::1','2025-06-07 09:18:51'),(224,4,'update_machine','Updated machine: 312','::1','2025-06-07 09:18:57'),(225,4,'update_machine','Updated machine: 325','::1','2025-06-07 09:19:01'),(226,4,'update_machine','Updated machine: 338','::1','2025-06-07 09:19:10'),(227,4,'update_machine','Updated machine: 354','::1','2025-06-07 09:19:21'),(228,4,'logout','User logged out','::1','2025-06-07 12:36:03'),(229,3,'logout','User logged out','::1','2025-06-07 20:09:33'),(230,1,'create_transaction','Created Handpay transaction for machine 122: 1,000.00','::1','2025-06-07 20:10:31'),(231,1,'logout','User logged out','::1','2025-06-08 05:18:55'),(232,4,'logout','User logged out','::1','2025-06-08 05:19:31'),(233,4,'logout','User logged out','::1','2025-06-08 05:20:16'),(234,5,'logout','User logged out','::1','2025-06-08 05:20:53'),(235,5,'create_transaction','Created Coins Drop transaction for machine 61: 140.00','192.168.17.207','2025-06-08 05:28:29'),(236,4,'create_machine_group','Created machine group: Guirgis with 3 machines','::1','2025-06-08 05:44:58'),(237,4,'create_machine_group','Created machine group: Mix Machines with 3 machines','::1','2025-06-08 05:45:51'),(238,4,'create_machine_group','Created machine group: Favorites with 6 machines','::1','2025-06-08 05:57:04'),(239,4,'create_transaction','Created Cash Drop transaction for machine 122: 259.00','::1','2025-06-08 08:18:15'),(240,4,'create_transaction','Created Cash Drop transaction for machine 312: 125.00','::1','2025-06-08 08:34:19'),(241,4,'create_transaction','Created Cash Drop transaction for machine 359: 547.00','::1','2025-06-08 08:34:30'),(242,4,'create_transaction','Created Handpay transaction for machine 105: 12.50','::1','2025-06-08 08:34:49'),(243,4,'create_transaction','Created Handpay transaction for machine 353: 19.00','::1','2025-06-08 08:34:54'),(244,4,'create_transaction','Created Handpay transaction for machine 770: 121.00','::1','2025-06-08 08:35:00'),(245,4,'create_transaction','Created Cash Drop transaction for machine 276: 12.00','::1','2025-06-08 08:42:43'),(246,4,'create_transaction','Created Handpay transaction for machine 999: 140.00','::1','2025-06-08 08:54:18'),(247,4,'create_transaction','Created Coins Drop transaction for machine 54: 200.00','::1','2025-06-08 08:54:27'),(248,4,'create_transaction','Created Cash Drop transaction for machine 105: 89.00','::1','2025-06-08 08:54:35'),(249,4,'create_transaction','Created Handpay transaction for machine 312: 33.00','::1','2025-06-08 08:55:59'),(250,4,'create_transaction','Created Handpay transaction for machine 276: 15.00','::1','2025-06-08 08:56:07'),(251,4,'create_transaction','Created Handpay transaction for machine 105: 99.00','::1','2025-06-08 08:56:11'),(252,4,'delete_machine_group','Deleted machine group: Guirgis','::1','2025-06-08 10:07:15'),(253,4,'create_machine_group','Created machine group: raf with 9 machines','::1','2025-06-08 10:07:56'),(254,4,'delete_machine_group','Deleted machine group: raf','::1','2025-06-08 10:08:02'),(255,4,'create_machine_group','Created machine group: raf with 6 machines','::1','2025-06-08 10:08:41'),(256,4,'delete_machine_group','Deleted machine group: raf','::1','2025-06-08 10:08:53'),(257,4,'create_machine_group','Created machine group: raf with 5 machines','::1','2025-06-08 10:09:21'),(258,4,'delete_machine_group','Deleted machine group: raf','::1','2025-06-08 10:09:36'),(259,4,'create_transaction','Created Cash Drop transaction for machine 354: 122.00','::1','2025-06-08 11:11:28'),(260,4,'create_transaction','Created Cash Drop transaction for machine 353: 199.00','::1','2025-06-08 11:11:34'),(261,4,'create_transaction','Created Cash Drop transaction for machine 105: 190.00','::1','2025-06-08 11:11:39'),(262,4,'logout','User logged out','::1','2025-06-08 11:12:11'),(263,2,'delete_transaction','Deleted transaction ID: 72','::1','2025-06-08 11:12:29'),(264,2,'logout','User logged out','::1','2025-06-09 05:54:59'),(265,5,'create_transaction','Created Refill transaction for machine 61: 125.00','192.168.17.66','2025-06-09 08:45:57'),(266,4,'update_brand','Updated brand: GAMBEE','::1','2025-06-09 10:57:22'),(267,4,'update_brand','Updated brand: APEX','::1','2025-06-09 10:58:20'),(268,4,'update_brand','Updated brand: GOLD CLUB','::1','2025-06-09 11:01:33'),(269,4,'update_brand','Updated brand: GOLD CLUB','::1','2025-06-09 11:02:04'),(270,5,'create_transaction','Created Handpay transaction for machine 312: 1,000.00','192.168.17.66','2025-06-09 17:42:58'),(271,5,'create_machine_group','Created machine group: new machine with 3 machines','192.168.17.66','2025-06-10 17:52:05'),(272,5,'update_transaction','Updated transaction ID: 69','192.168.17.66','2025-06-10 21:27:22'),(273,5,'update_machine','Updated machine: 325','192.168.17.66','2025-06-10 22:40:45'),(274,5,'update_machine','Updated machine: 325','192.168.17.66','2025-06-10 22:41:27'),(275,5,'logout','User logged out','192.168.17.66','2025-06-11 04:14:11'),(276,7,'logout','User logged out','192.168.17.66','2025-06-11 04:14:40'),(277,3,'update_profile','Updated profile information','::1','2025-06-11 08:49:13'),(278,1,'update_machine_group','Updated machine group: Mix Machines with 5 machines','::1','2025-06-11 09:17:44'),(279,1,'update_transaction','Updated transaction ID: 67','::1','2025-06-11 09:34:38'),(280,1,'logout','User logged out','::1','2025-06-11 12:28:26'),(281,3,'logout','User logged out','::1','2025-06-11 12:37:47'),(282,4,'create_transaction','Created Handpay transaction for machine 999: 5,000.00','::1','2025-06-12 05:18:01'),(283,4,'delete_transaction','Deleted transaction ID: 75','::1','2025-06-12 05:19:26'),(284,4,'create_transaction','Created Handpay transaction for machine 61: 14.00','::1','2025-06-12 09:00:22'),(285,4,'create_transaction','Created Handpay transaction for machine 999: 5,000.00','::1','2025-06-12 09:17:57'),(286,4,'delete_transaction','Deleted transaction ID: 77','::1','2025-06-12 09:18:15'),(287,4,'logout','User logged out','::1','2025-06-12 09:19:07'),(288,3,'logout','User logged out','::1','2025-06-12 11:31:59'),(289,4,'update_transaction','Updated transaction ID: 54','::1','2025-06-12 11:32:25'),(290,4,'logout','User logged out','::1','2025-06-13 08:13:39'),(291,1,'login','User logged in successfully','::1','2025-06-13 08:14:13'),(292,1,'logout','User logged out','::1','2025-06-13 08:14:16'),(293,4,'login','User logged in successfully','::1','2025-06-13 08:14:22'),(294,4,'logout','User logged out','::1','2025-06-13 08:32:35'),(295,4,'login','User logged in successfully','::1','2025-06-13 08:33:03'),(296,4,'login','User logged in successfully','::1','2025-06-13 11:41:41'),(297,4,'logout','User logged out','::1','2025-06-13 12:46:38'),(298,4,'login','User logged in successfully','::1','2025-06-14 06:15:52'),(299,4,'update_brand','Updated brand: SCIENTIFIC GAMES','::1','2025-06-14 07:40:52'),(300,4,'update_brand','Updated brand: KONAMI','::1','2025-06-14 07:41:00'),(301,4,'login','User logged in successfully','::1','2025-06-14 08:30:33'),(302,4,'delete_user','Deleted user: admin','::1','2025-06-14 08:56:35'),(303,4,'delete_user','Deleted user: editor','::1','2025-06-14 08:57:32'),(304,4,'create_machine_type','Created machine type: new fucking type','::1','2025-06-14 09:06:09'),(305,4,'create_machine','Created machine: 222','::1','2025-06-14 09:06:37'),(306,4,'delete_machine','Deleted machine: 222','::1','2025-06-14 09:07:09'),(307,4,'delete_machine_type','Deleted machine type: new fucking type','::1','2025-06-14 09:07:15'),(308,4,'logout','User logged out','::1','2025-06-14 09:08:22'),(309,3,'login','User logged in successfully','::1','2025-06-14 09:08:35'),(310,4,'login','User logged in successfully','192.168.17.37','2025-06-14 11:03:56'),(311,4,'login','User logged in successfully','::1','2025-06-18 07:12:01'),(312,4,'login','User logged in successfully','::1','2025-06-18 09:51:36');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machine_group_members`
--

DROP TABLE IF EXISTS `machine_group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machine_group_members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `machine_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_machine` (`group_id`,`machine_id`),
  KEY `idx_machine_group_members_group_id` (`group_id`),
  KEY `idx_machine_group_members_machine_id` (`machine_id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machine_group_members`
--

LOCK TABLES `machine_group_members` WRITE;
/*!40000 ALTER TABLE `machine_group_members` DISABLE KEYS */;
INSERT INTO `machine_group_members` VALUES (33,7,10,'2025-06-10 17:52:05'),(39,2,17,'2025-06-11 09:17:44'),(37,2,6,'2025-06-11 09:17:44'),(38,2,7,'2025-06-11 09:17:44'),(36,2,15,'2025-06-11 09:17:44'),(7,3,10,'2025-06-08 05:57:04'),(8,3,11,'2025-06-08 05:57:04'),(9,3,6,'2025-06-08 05:57:04'),(10,3,4,'2025-06-08 05:57:04'),(11,3,18,'2025-06-08 05:57:04'),(12,3,20,'2025-06-08 05:57:04'),(34,7,16,'2025-06-10 17:52:05'),(35,7,17,'2025-06-10 17:52:05'),(40,2,8,'2025-06-11 09:17:44');
/*!40000 ALTER TABLE `machine_group_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machine_groups`
--

DROP TABLE IF EXISTS `machine_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machine_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_machine_groups_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machine_groups`
--

LOCK TABLES `machine_groups` WRITE;
/*!40000 ALTER TABLE `machine_groups` DISABLE KEYS */;
INSERT INTO `machine_groups` VALUES (7,'new machine','new machine start 1/5/2025','2025-06-10 17:52:05','2025-06-10 17:52:05'),(2,'Mix Machines','mix of status','2025-06-08 05:45:51','2025-06-08 05:45:51'),(3,'Favorites','best of the best','2025-06-08 05:57:04','2025-06-08 05:57:04');
/*!40000 ALTER TABLE `machine_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machine_types`
--

DROP TABLE IF EXISTS `machine_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machine_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machine_types`
--

LOCK TABLES `machine_types` WRITE;
/*!40000 ALTER TABLE `machine_types` DISABLE KEYS */;
INSERT INTO `machine_types` VALUES (1,'CASH','Cash-based slot machine','2025-06-05 06:52:21','2025-06-05 06:52:21'),(2,'COINS','Coin-operated slot machine','2025-06-05 06:52:21','2025-06-05 06:52:21'),(3,'GAMBEE','Gambee electronic gaming machine','2025-06-05 06:52:21','2025-06-05 06:52:21'),(15,'POKER','All new poker machines','2025-06-06 07:39:15','2025-06-06 07:40:48');
/*!40000 ALTER TABLE `machine_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `machines`
--

DROP TABLE IF EXISTS `machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `brand_id` int DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type_id` int DEFAULT NULL,
  `credit_value` decimal(10,2) NOT NULL,
  `manufacturing_year` int DEFAULT NULL,
  `ip_address` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance','Reserved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `machine_number` (`machine_number`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `brand_id` (`brand_id`),
  KEY `type_id` (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `machines`
--

LOCK TABLES `machines` WRITE;
/*!40000 ALTER TABLE `machines` DISABLE KEYS */;
INSERT INTO `machines` VALUES (1,'359',6,'Top',1,0.01,2019,NULL,NULL,'129478','Active','2025-05-26 11:42:23','2025-06-07 09:18:17'),(4,'327',8,'Ambola',1,0.01,2001,NULL,NULL,'789754','Active','2025-05-27 08:15:22','2025-06-07 09:18:09'),(5,'54',1,'Reels',2,0.25,1995,NULL,NULL,'5214477','Active','2025-05-27 08:15:43','2025-06-07 09:18:29'),(6,'312',9,'GP4 - GP Electronic Roulette',3,0.25,2006,NULL,NULL,'1147444','Active','2025-05-27 08:20:36','2025-06-07 09:18:57'),(7,'353',11,'N/A',1,0.01,2006,NULL,NULL,'4451','Maintenance','2025-05-27 09:18:51','2025-06-07 07:23:41'),(8,'61',1,'Reels',2,0.25,1902,NULL,NULL,'171819','Active','2025-05-27 17:54:35','2025-06-07 09:18:01'),(9,'770',6,'Magic Touch',1,0.25,2001,NULL,NULL,'9987421','Active','2025-05-28 09:19:10','2025-06-07 09:18:22'),(10,'105',10,'Number 1',1,0.25,1910,NULL,NULL,'521547','Active','2025-05-28 09:24:49','2025-06-07 06:40:25'),(11,'122',2,'Multi Game',1,1.00,NULL,NULL,NULL,'4587545','Active','2025-05-28 09:42:27','2025-06-07 09:18:47'),(16,'325',1,'G23',1,0.01,2005,NULL,NULL,'12184','Maintenance','2025-06-01 09:09:25','2025-06-10 22:41:27'),(15,'276',10,'Shamboury',1,1.00,1999,NULL,NULL,'12457','Active','2025-05-29 07:36:53','2025-06-07 09:18:51'),(17,'354',11,'N/A',1,0.05,2009,NULL,NULL,'1217','Inactive','2025-06-01 09:10:04','2025-06-07 09:19:21'),(18,'338',5,'Admiral',1,0.01,2015,NULL,NULL,'234254','Active','2025-06-01 12:30:20','2025-06-07 09:19:10'),(20,'999',13,'Tower',15,0.05,2024,NULL,NULL,'19687','Active','2025-06-06 07:40:09','2025-06-07 06:40:39');
/*!40000 ALTER TABLE `machines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `severity` enum('INFO','WARNING','ERROR','CRITICAL') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'INFO',
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (1,'LOGIN_FAILED','Username: admin, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-13 05:14:00'),(2,'LOGIN_FAILED','Username: admin, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-13 05:14:07'),(3,'LOGIN_SUCCESS','Username: admin','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',1,'2025-06-13 05:14:13'),(4,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-13 05:14:22'),(5,'LOGIN_FAILED','Username: raf, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-13 05:32:57'),(6,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-13 05:33:03'),(7,'SESSION_TIMEOUT','User session expired','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-13 08:08:18'),(8,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-13 08:41:41'),(9,'SESSION_TIMEOUT','User session expired','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 02:57:38'),(10,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-14 03:15:52'),(11,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-14 05:30:33'),(12,'LOGIN_FAILED','Username: viewer, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 06:08:29'),(13,'LOGIN_SUCCESS','Username: viewer','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',3,'2025-06-14 06:08:35'),(14,'LOGIN_SUCCESS','Username: raf','INFO','192.168.17.37','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0',4,'2025-06-14 08:03:56'),(15,'SESSION_TIMEOUT','User session expired','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:42:23'),(16,'LOGIN_FAILED','Username: raf, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:42:48'),(17,'LOGIN_FAILED','Username: raf, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:42:52'),(18,'LOGIN_FAILED','Username: raf, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:42:56'),(19,'LOGIN_FAILED','Username: raf, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:43:00'),(20,'LOGIN_FAILED','Username: viewer, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:43:08'),(21,'LOGIN_LOCKOUT','Username: viewer, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:43:13'),(22,'LOGIN_LOCKOUT','Username: raf, IP: ::1','WARNING','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-14 08:43:22'),(23,'SESSION_TIMEOUT','User session expired','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-17 01:48:05'),(24,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-18 04:12:01'),(25,'SESSION_TIMEOUT','User session expired','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',NULL,'2025-06-18 06:51:32'),(26,'LOGIN_SUCCESS','Username: raf','INFO','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36',4,'2025-06-18 06:51:36');
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_types`
--

DROP TABLE IF EXISTS `transaction_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `category` enum('OUT','DROP') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_types`
--

LOCK TABLES `transaction_types` WRITE;
/*!40000 ALTER TABLE `transaction_types` DISABLE KEYS */;
INSERT INTO `transaction_types` VALUES (1,'Handpay','OUT','Manual payment to player','2025-05-26 11:02:35','2025-05-26 11:02:35'),(2,'Ticket','OUT','Ticket out payment','2025-05-26 11:02:35','2025-05-26 11:02:35'),(3,'Refill','OUT','Machine refill','2025-05-26 11:02:35','2025-05-26 11:02:35'),(4,'Coins Drop','DROP','Coins inserted by players','2025-05-26 11:02:35','2025-05-26 11:02:35'),(5,'Cash Drop','DROP','Cash inserted by players','2025-05-26 11:02:35','2025-05-26 11:02:35');
/*!40000 ALTER TABLE `transaction_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_id` int NOT NULL,
  `transaction_type_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  KEY `transaction_type_id` (`transaction_type_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,1,1500.00,'2025-05-26 02:13:00',1,NULL,'2025-05-27 05:14:28','2025-05-27 06:20:08'),(2,6,5,1388.00,'2025-05-27 05:20:00',1,NULL,'2025-05-27 08:21:09','2025-05-27 08:21:09'),(3,4,5,188.00,'2025-05-27 05:21:00',1,NULL,'2025-05-27 08:21:32','2025-05-27 08:21:32'),(16,9,1,10.00,'2025-05-28 00:41:00',1,NULL,'2025-05-28 09:43:35','2025-05-31 12:16:04'),(6,5,3,125.00,'2025-05-09 09:48:00',1,NULL,'2025-05-27 08:31:46','2025-05-31 12:15:08'),(7,7,5,660.00,'2025-05-27 07:24:00',1,NULL,'2025-05-27 13:01:28','2025-05-31 12:15:32'),(8,8,3,125.00,'2025-05-27 18:06:00',1,NULL,'2025-05-27 17:55:13','2025-05-31 12:15:23'),(17,9,5,100.00,'2025-05-28 08:18:00',1,NULL,'2025-05-28 09:43:43','2025-05-31 12:16:16'),(11,5,4,266.00,'2025-05-28 05:22:00',1,NULL,'2025-05-28 09:22:46','2025-05-31 12:16:10'),(12,10,5,144.00,'2025-05-28 01:33:00',1,NULL,'2025-05-28 09:25:04','2025-05-31 12:15:55'),(13,10,2,12.00,'2025-05-28 19:30:00',1,NULL,'2025-05-28 09:25:14','2025-05-31 12:15:46'),(15,11,1,10.00,'2025-05-28 14:45:00',1,NULL,'2025-05-28 09:42:43','2025-05-31 12:15:41'),(18,12,1,10.00,'2025-05-27 21:00:00',1,NULL,'2025-05-28 09:45:34','2025-05-28 09:45:34'),(19,12,5,88.50,'2025-05-27 21:00:00',1,NULL,'2025-05-28 09:45:46','2025-05-28 09:45:46'),(20,13,2,120.00,'2025-05-27 21:00:00',1,NULL,'2025-05-28 09:47:49','2025-05-28 09:47:49'),(21,13,5,500.00,'2025-05-27 21:00:00',1,NULL,'2025-05-28 09:47:59','2025-05-28 09:47:59'),(22,14,5,100.00,'2025-05-27 21:00:00',1,NULL,'2025-05-28 11:32:16','2025-05-28 11:32:16'),(23,14,2,20.00,'2025-05-27 21:00:00',1,NULL,'2025-05-28 11:32:25','2025-05-28 11:32:25'),(24,9,5,40.00,'2025-05-29 11:59:00',1,NULL,'2025-05-29 07:18:37','2025-05-31 12:16:45'),(25,15,5,200.00,'2025-05-29 07:03:00',1,NULL,'2025-05-29 07:37:08','2025-05-31 12:40:17'),(26,15,2,190.00,'2025-05-29 18:35:00',1,NULL,'2025-05-29 07:37:21','2025-05-31 12:16:26'),(27,11,5,115.00,'2025-05-07 07:30:00',1,NULL,'2025-05-30 09:52:47','2025-05-31 12:15:00'),(28,1,5,1250.00,'2025-05-21 09:15:00',1,NULL,'2025-05-30 09:57:08','2025-05-31 12:15:15'),(29,8,4,198.00,'2025-05-28 18:56:00',4,NULL,'2025-05-30 11:54:18','2025-05-31 12:14:43'),(30,11,3,125.00,'2025-05-30 18:05:00',4,NULL,'2025-05-30 17:16:11','2025-05-31 12:14:52'),(31,10,1,200.00,'2025-05-30 20:23:00',4,NULL,'2025-05-30 17:16:36','2025-05-31 12:16:35'),(32,1,5,144.00,'2025-05-31 12:09:00',5,NULL,'2025-05-31 09:03:35','2025-05-31 12:09:10'),(33,4,1,122.00,'2025-05-27 15:49:00',5,NULL,'2025-05-31 09:03:44','2025-05-31 12:14:34'),(34,10,5,1000.00,'2025-05-31 12:08:00',5,NULL,'2025-05-31 12:09:01','2025-05-31 12:09:01'),(36,15,5,100.00,'2025-06-01 08:55:00',1,NULL,'2025-06-01 08:56:02','2025-06-01 08:56:02'),(37,15,1,50.00,'2025-06-01 08:56:00',1,NULL,'2025-06-01 08:56:10','2025-06-01 08:56:10'),(38,18,5,190.00,'2025-06-01 12:30:00',1,NULL,'2025-06-01 12:30:43','2025-06-01 12:30:43'),(39,10,2,37.50,'2025-06-01 12:38:00',1,NULL,'2025-06-01 12:38:37','2025-06-01 12:38:37'),(40,8,3,125.00,'2025-06-01 12:38:00',1,NULL,'2025-06-01 12:38:56','2025-06-01 12:38:56'),(41,6,5,500.00,'2025-06-02 05:21:00',1,NULL,'2025-06-02 05:21:35','2025-06-02 05:21:35'),(42,10,5,320.00,'2025-06-03 05:21:00',1,NULL,'2025-06-03 05:21:44','2025-06-03 05:21:44'),(43,10,1,36.00,'2025-06-03 05:21:00',1,NULL,'2025-06-03 05:21:55','2025-06-03 05:21:55'),(44,8,3,125.00,'2025-06-03 05:21:00',1,NULL,'2025-06-03 05:22:02','2025-06-03 05:22:02'),(45,8,1,17.00,'2025-06-03 05:22:00',1,NULL,'2025-06-03 05:22:08','2025-06-03 05:22:08'),(46,6,5,500.00,'2025-06-03 05:22:00',1,NULL,'2025-06-03 05:22:20','2025-06-03 05:22:20'),(47,6,2,319.00,'2025-06-03 05:22:00',1,NULL,'2025-06-03 05:22:35','2025-06-03 05:22:35'),(48,8,4,223.00,'2025-06-03 05:31:00',1,NULL,'2025-06-03 05:31:11','2025-06-03 05:31:11'),(49,8,4,320.00,'2025-06-04 04:38:00',4,NULL,'2025-06-04 04:38:17','2025-06-04 04:38:17'),(50,6,5,520.00,'2025-06-04 04:38:00',4,NULL,'2025-06-04 04:38:25','2025-06-04 05:00:12'),(51,9,1,250.00,'2025-06-04 04:38:00',4,NULL,'2025-06-04 04:38:33','2025-06-04 04:38:33'),(52,8,3,125.00,'2025-06-04 04:58:00',4,NULL,'2025-06-04 04:58:55','2025-06-04 04:58:55'),(53,11,2,103.00,'2025-06-04 04:59:00',4,NULL,'2025-06-04 04:59:49','2025-06-04 04:59:49'),(54,20,5,153.00,'2025-06-06 07:40:00',4,NULL,'2025-06-06 07:40:32','2025-06-12 11:32:25'),(55,11,1,1000.00,'2025-06-07 20:09:00',1,NULL,'2025-06-07 20:10:31','2025-06-07 20:10:31'),(56,8,4,140.00,'2025-06-08 05:28:00',5,NULL,'2025-06-08 05:28:29','2025-06-08 05:28:29'),(57,11,5,259.00,'2025-06-08 08:17:00',4,NULL,'2025-06-08 08:18:15','2025-06-08 08:18:15'),(58,6,5,125.00,'2025-06-08 07:34:07',4,NULL,'2025-06-08 08:34:19','2025-06-08 08:34:19'),(59,1,5,547.00,'2025-06-08 07:34:07',4,NULL,'2025-06-08 08:34:30','2025-06-08 08:34:30'),(60,10,1,12.50,'2025-06-08 07:34:36',4,NULL,'2025-06-08 08:34:49','2025-06-08 08:34:49'),(61,7,1,19.00,'2025-06-08 07:34:36',4,NULL,'2025-06-08 08:34:54','2025-06-08 08:34:54'),(62,9,1,121.00,'2025-06-08 07:34:36',4,NULL,'2025-06-08 08:35:00','2025-06-08 08:35:00'),(63,15,5,12.00,'2025-06-08 07:42:36',4,NULL,'2025-06-08 08:42:43','2025-06-08 08:42:43'),(64,20,1,140.00,'2025-06-08 07:54:07',4,NULL,'2025-06-08 08:54:18','2025-06-08 08:54:18'),(65,5,4,200.00,'2025-06-08 07:54:07',4,NULL,'2025-06-08 08:54:27','2025-06-08 08:54:27'),(66,10,5,89.00,'2025-06-08 07:54:07',4,NULL,'2025-06-08 08:54:35','2025-06-08 08:54:35'),(67,6,2,33.00,'2025-06-08 07:55:00',4,NULL,'2025-06-08 08:55:59','2025-06-11 09:34:38'),(68,15,1,15.00,'2025-06-08 07:55:45',4,NULL,'2025-06-08 08:56:07','2025-06-08 08:56:07'),(69,10,1,99.00,'2025-06-08 07:55:00',4,'nn','2025-06-08 08:56:11','2025-06-10 21:27:22'),(70,17,5,122.00,'2025-06-08 10:11:12',4,NULL,'2025-06-08 11:11:28','2025-06-08 11:11:28'),(71,7,5,199.00,'2025-06-08 10:11:12',4,NULL,'2025-06-08 11:11:34','2025-06-08 11:11:34'),(73,8,3,125.00,'2025-06-09 07:45:49',5,NULL,'2025-06-09 08:45:57','2025-06-09 08:45:57'),(74,6,1,1000.00,'2025-06-08 16:42:33',5,NULL,'2025-06-09 17:42:58','2025-06-09 17:42:58'),(76,8,1,14.00,'2025-06-12 09:00:10',4,NULL,'2025-06-12 09:00:22','2025-06-12 09:00:22');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','editor','viewer') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Active','Inactive','Maintenance','Reserved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'viewer','$2y$10$BDqeDErN2x0RZGHEnTPSZe5xx/qtu391JZbHbd7JJB05AjVU3jpau','Just a viewer','viewer@example.com','viewer','Active','2025-05-26 11:02:35','2025-06-11 08:49:13'),(4,'Raf','$2y$10$HXjXy157kPt8KiqpQ6EXX.YIEXGPZt7lacU4mPMBVLQP4q2p0nDPS','Rafik Semaan','rafiksemaan@gmail.com','admin','Active','2025-05-30 11:43:19','2025-05-30 11:43:19'),(5,'Allam','$2y$10$RNrZZ9VT9dlwJbf.jPmpHu504UlF23aKlBgLGl8WMIpXoHkFGMcqS','Muhamad Allam','slot.manager@sinaigrandcasino.com','admin','Active','2025-05-31 08:55:04','2025-06-09 20:17:53'),(6,'Jonathan','$2y$10$3/fwpyRN8o9ZLH.MysRW7uh.LeyQlMLlEzBIBdNAltzsdbWHtuxfq','Jonathan Lahousse','jonathan@sinaigrandcasino.com','viewer','Active','2025-06-11 04:10:48','2025-06-11 04:10:48'),(7,'Remon','$2y$10$a5igm/8XJhjmaeAEbEHD3.G6eNhmKvxs3FnPFApRj/aox654SEIQK','Remon sadek','11111@11111.com','editor','Active','2025-06-11 04:12:18','2025-06-11 04:12:18'),(8,'CCTV','$2y$10$dYPgWwCCDNJmafP.R0pxse./PeRsT/c8BRjtaStODpWgPvwpBFupq','CCTV','11111@111111.com','viewer','Active','2025-06-11 04:13:42','2025-06-11 04:13:42');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-20 14:07:22
