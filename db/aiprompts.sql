-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 09 juil. 2022 à 19:37
-- Version du serveur : 10.4.24-MariaDB
-- Version de PHP : 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `aiprompts`
--

-- --------------------------------------------------------

--
-- Structure de la table `editcode`
--

CREATE TABLE `editcode` (
  `ID` int(11) NOT NULL,
  `PromptID` int(11) NOT NULL,
  `CodeEdit` longtext NOT NULL,
  `SearchCode` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `news`
--

CREATE TABLE `news` (
  `Date` date NOT NULL,
  `Title` text NOT NULL,
  `Content` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `news`
--

INSERT INTO `news` (`Date`, `Title`, `Content`) VALUES
('2022-07-02', 'Release 1.0', 'Initial Release\r\n                                                       	Edit and Search Code System');

-- --------------------------------------------------------

--
-- Structure de la table `prompts`
--

CREATE TABLE `prompts` (
  `Id` int(11) NOT NULL,
  `AuthorsNote` longtext DEFAULT NULL,
  `Description` longtext DEFAULT NULL,
  `Memory` longtext DEFAULT NULL,
  `Nsfw` int(11) NOT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `PromptContent` longtext DEFAULT NULL,
  `PublishDate` text DEFAULT NULL,
  `Tags` text NOT NULL,
  `Title` text NOT NULL,
  `ScriptZip` blob DEFAULT NULL,
  `NovelAIScenario` longtext DEFAULT NULL,
  `HoloAIScenario` longtext DEFAULT NULL,
  `CorrelationID` int(11) NOT NULL,
  `DateCreated` text NOT NULL,
  `DateEdited` text DEFAULT NULL,
  `Quests` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `worldinfos`
--

CREATE TABLE `worldinfos` (
  `Id` int(11) NOT NULL,
  `Entry` longtext NOT NULL,
  `WKeys` longtext NOT NULL,
  `PromptId` int(11) NOT NULL,
  `CorrelationId` int(11) NOT NULL,
  `DateCreated` longtext NOT NULL,
  `DateEdited` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `editcode`
--
ALTER TABLE `editcode`
  ADD PRIMARY KEY (`ID`);

--
-- Index pour la table `prompts`
--
ALTER TABLE `prompts`
  ADD PRIMARY KEY (`Id`);

--
-- Index pour la table `worldinfos`
--
ALTER TABLE `worldinfos`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `editcode`
--
ALTER TABLE `editcode`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT pour la table `prompts`
--
ALTER TABLE `prompts`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3338;

--
-- AUTO_INCREMENT pour la table `worldinfos`
--
ALTER TABLE `worldinfos`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12315;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
