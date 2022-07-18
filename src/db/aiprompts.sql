-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.7.33 - MySQL Community Server (GPL)
-- Server OS:                    Win64
-- HeidiSQL Version:             11.2.0.6213
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for aiprompts
CREATE DATABASE IF NOT EXISTS `aiprompts` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `aiprompts`;

-- Dumping structure for table aiprompts.editcode
CREATE TABLE IF NOT EXISTS `editcode` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PromptID` int(11) NOT NULL,
  `CodeEdit` longtext NOT NULL,
  `SearchCode` longtext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table aiprompts.editcode: ~0 rows (approximately)
/*!40000 ALTER TABLE `editcode` DISABLE KEYS */;
/*!40000 ALTER TABLE `editcode` ENABLE KEYS */;

-- Dumping structure for table aiprompts.news
CREATE TABLE IF NOT EXISTS `news` (
  `Date` date NOT NULL,
  `Title` text NOT NULL,
  `Content` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table aiprompts.news: ~1 rows (approximately)
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT IGNORE INTO `news` (`Date`, `Title`, `Content`) VALUES
	('2022-07-02', 'Release 1.0', 'Initial Release\r\n                                                       	Edit and Search Code System');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;

-- Dumping structure for table aiprompts.prompts
CREATE TABLE IF NOT EXISTS `prompts` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `AuthorsNote` longtext,
  `Description` longtext,
  `Memory` longtext,
  `Nsfw` int(11) NOT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `PromptContent` longtext,
  `PublishDate` text,
  `Tags` text NOT NULL,
  `Title` text NOT NULL,
  `ScriptZip` blob,
  `NovelAIScenario` longtext,
  `HoloAIScenario` longtext,
  `CorrelationID` int(11) NOT NULL,
  `DateCreated` text NOT NULL,
  `DateEdited` text,
  `Quests` text,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table aiprompts.prompts: ~0 rows (approximately)
/*!40000 ALTER TABLE `prompts` DISABLE KEYS */;
/*!40000 ALTER TABLE `prompts` ENABLE KEYS */;

-- Dumping structure for table aiprompts.worldinfos
CREATE TABLE IF NOT EXISTS `worldinfos` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Entry` longtext NOT NULL,
  `WKeys` longtext NOT NULL,
  `PromptId` int(11) NOT NULL,
  `CorrelationId` int(11) NOT NULL,
  `DateCreated` longtext NOT NULL,
  `DateEdited` longtext,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table aiprompts.worldinfos: ~0 rows (approximately)
/*!40000 ALTER TABLE `worldinfos` DISABLE KEYS */;
/*!40000 ALTER TABLE `worldinfos` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
