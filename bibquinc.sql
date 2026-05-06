-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 06 mai 2026 à 14:57
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
  `intituleCat` enum('outillage','visserie&boulonnerie','plomberie','peinture','jardinage','serrurerie','ampoules&eclairage','cables&fils','disjoncteurs','prises&interrupteurs','projecteurs','comptage') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`idCat`, `intituleCat`) VALUES
(1, 'outillage'),
(2, 'visserie&boulonnerie'),
(3, 'plomberie'),
(4, 'peinture'),
(5, 'jardinage'),
(6, 'serrurerie'),
(7, 'ampoules&eclairage'),
(8, 'cables&fils'),
(9, 'disjoncteurs'),
(10, 'prises&interrupteurs'),
(11, 'projecteurs'),
(12, 'comptage');

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `idFac` int(11) NOT NULL,
  `idAchat` int(11) NOT NULL,
  `etat` enum('payée','non payée') NOT NULL,
  `dateFac` datetime NOT NULL
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
-- Structure de la table `promo`
--

CREATE TABLE `promo` (
  `idArt` int(11) NOT NULL,
  `oldPrice` int(11) NOT NULL,
  `newPrice` int(11) NOT NULL
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
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`idUtili`, `nom`, `prenom`, `email`, `numTel`, `mdp`, `type`, `created_at`, `last_login`) VALUES
(1, 'Boutal', 'Asma', 'asmabl@gmail.com', '0645678906', '$2y$12$ARVxE3bRokak4FgXReKPBuLoYb7pGo0SIG0hf/uBGpx5Ce2m6/ueS', 'client', '2026-05-05 21:02:15', '2026-05-06 13:56:26'),
(3, 'Brouk', 'Sami', 'samibrouk2@gmail.com', '0783245671', '$2y$12$zvu0mvNR3kHWpa6cIAAY2.CIfkGkJnOyCqxP9YgKieR52Hnpj/zum', 'professionnel', '2026-05-05 21:26:36', '2026-05-05 21:27:36');

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
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`idFac`),
  ADD KEY `fk_fact` (`idAchat`);

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
-- Index pour la table `promo`
--
ALTER TABLE `promo`
  ADD KEY `fk_promo` (`idArt`);

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
  MODIFY `idCat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `idUtili` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Contraintes pour la table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `fk_fact` FOREIGN KEY (`idAchat`) REFERENCES `achat` (`idAchat`);

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

--
-- Contraintes pour la table `promo`
--
ALTER TABLE `promo`
  ADD CONSTRAINT `fk_promo` FOREIGN KEY (`idArt`) REFERENCES `article` (`idArt`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
