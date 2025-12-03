/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.0.2-MariaDB, for osx12.00 (arm64)
--
-- Host: localhost    Database: film_rentals
-- ------------------------------------------------------
-- Server version	12.0.2-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `filmy`
--

DROP TABLE IF EXISTS `filmy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `filmy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tytul` varchar(100) DEFAULT NULL,
  `id_gatunku` int(11) DEFAULT NULL,
  `data_dodania` date DEFAULT NULL,
  `status` enum('dostepny','wypozyczony') DEFAULT NULL,
  `ilosc_ocen` int(11) DEFAULT NULL,
  `srednia_ocen` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_gatunek` (`id_gatunku`),
  CONSTRAINT `fk_gatunek` FOREIGN KEY (`id_gatunku`) REFERENCES `gatunki` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filmy`
--

LOCK TABLES `filmy` WRITE;
/*!40000 ALTER TABLE `filmy` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `filmy` VALUES
(2,'Interstellar',3,'2016-10-10','wypozyczony',2000,8.4),
(3,'Oppenheimer',3,'2024-09-13','dostepny',1376,8.1),
(4,'Incepcja',3,'2018-03-25','dostepny',863,7.4),
(5,'Barbie',3,'2024-10-04','wypozyczony',423,7.7),
(6,'Kler',2,'2019-02-16','wypozyczony',509,7.3),
(7,'Obecność 2',1,'2019-12-09','dostepny',423,6.1),
(8,'To',1,'2017-06-01','wypozyczony',327,6.9),
(9,'Gru, Dru i miniionki',4,'2016-11-22','wypozyczony',972,7.3),
(10,'Jak ukraść ksiezyc',4,'2019-06-01','dostepny',633,7.2);
/*!40000 ALTER TABLE `filmy` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `gatunki`
--

DROP TABLE IF EXISTS `gatunki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gatunki` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazwa` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gatunki`
--

LOCK TABLES `gatunki` WRITE;
/*!40000 ALTER TABLE `gatunki` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `gatunki` VALUES
(1,'horror'),
(2,'obyczajowy'),
(3,'sci-fi'),
(4,'animacja');
/*!40000 ALTER TABLE `gatunki` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-11-19  9:44:24
