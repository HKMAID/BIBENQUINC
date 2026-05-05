-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 05 mai 2026 à 21:38
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bibquinc`
--

-- --------------------------------------------------------

--
-- Structure de la table `achat`
--

CREATE TABLE `achat` (
  `idAchat` int(11) NOT NULL,
  `idClient` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `dateAchat` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

CREATE TABLE `article` (
  `idArt` int(11) NOT NULL,
  `idCat` int(11) NOT NULL,
  `intitule` varchar(200) NOT NULL,
  `prix` int(11) NOT NULL,
  `nbDispo` int(11) NOT NULL,
  `marque` varchar(70) NOT NULL,
  `photo` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article_achat`
--

CREATE TABLE `article_achat` (
  `idAchat` int(11) NOT NULL,
  `idArticle` int(11) NOT NULL,
  `quantite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `idCat` int(11) NOT NULL,
  `intituleCat` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `favori`
--

CREATE TABLE `favori` (
  `idClient` int(11) NOT NULL,
  `idArticle` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

CREATE TABLE `message` (
  `idClient` int(11) NOT NULL,
  `theme` varchar(60) NOT NULL,
  `contenu` text NOT NULL,
  `date_msg` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `idUtili` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(60) NOT NULL,
  `numTel` varchar(10) NOT NULL,
  `mdp` varchar(250) NOT NULL,
  `type` enum('client','professionnel') NOT NULL,
  `created_at` datetime NOT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `achat`
--
ALTER TABLE `achat`
  ADD PRIMARY KEY (`idAchat`),
  ADD KEY `fk_achat_client` (`idClient`);

--
-- Index pour la table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`idArt`),
  ADD KEY `fk_cat` (`idCat`);

--
-- Index pour la table `article_achat`
--
ALTER TABLE `article_achat`
  ADD PRIMARY KEY (`idAchat`,`idArticle`),
  ADD KEY `fk_a` (`idArticle`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCat`);

--
-- Index pour la table `favori`
--
ALTER TABLE `favori`
  ADD PRIMARY KEY (`idClient`,`idArticle`),
  ADD KEY `fk_fav_article` (`idArticle`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
  ADD KEY `fk_client_msg` (`idClient`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`idUtili`),
  ADD UNIQUE KEY `created_at` (`created_at`,`email`,`numTel`) USING BTREE;

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `achat`
--
ALTER TABLE `achat`
  MODIFY `idAchat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `article`
--
ALTER TABLE `article`
  MODIFY `idArt` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `idCat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `idUtili` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `achat`
--
ALTER TABLE `achat`
  ADD CONSTRAINT `fk_achat_client` FOREIGN KEY (`idClient`) REFERENCES `utilisateur` (`idUtili`);

--
-- Contraintes pour la table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `fk_cat` FOREIGN KEY (`idCat`) REFERENCES `categorie` (`idCat`);

--
-- Contraintes pour la table `article_achat`
--
ALTER TABLE `article_achat`
  ADD CONSTRAINT `fk_a` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArt`),
  ADD CONSTRAINT `fk_a2` FOREIGN KEY (`idAchat`) REFERENCES `achat` (`idAchat`),
  ADD CONSTRAINT `fk_aa` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArt`),
  ADD CONSTRAINT `fk_aa2` FOREIGN KEY (`idAchat`) REFERENCES `achat` (`idAchat`);

--
-- Contraintes pour la table `favori`
--
ALTER TABLE `favori`
  ADD CONSTRAINT `fk_fav_article` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArt`),
  ADD CONSTRAINT `fk_fav_client` FOREIGN KEY (`idClient`) REFERENCES `utilisateur` (`idUtili`);

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `fk_client_msg` FOREIGN KEY (`idClient`) REFERENCES `utilisateur` (`idUtili`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
