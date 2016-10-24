-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Värd: 127.0.0.1
-- Tid vid skapande: 24 okt 2016 kl 18:58
-- Serverversion: 10.1.9-MariaDB
-- PHP-version: 5.6.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databas: `wgtotw`
--

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_answer`
--

CREATE TABLE `lf_answer` (
  `id` int(11) NOT NULL,
  `content` varchar(255) DEFAULT NULL,
  `score` int(11) NOT NULL,
  `accepted` tinyint(4) NOT NULL,
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_answer2comment`
--

CREATE TABLE `lf_answer2comment` (
  `idAnswer` int(11) NOT NULL,
  `idComment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_answervote`
--

CREATE TABLE `lf_answervote` (
  `id` int(11) NOT NULL,
  `idAnswer` int(11) NOT NULL,
  `idUser` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_comment`
--

CREATE TABLE `lf_comment` (
  `id` int(11) NOT NULL,
  `content` varchar(255) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_commentvote`
--

CREATE TABLE `lf_commentvote` (
  `id` int(11) NOT NULL,
  `idComment` int(11) NOT NULL,
  `idUser` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_question`
--

CREATE TABLE `lf_question` (
  `id` int(11) NOT NULL,
  `title` varchar(80) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `answers` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_question2answer`
--

CREATE TABLE `lf_question2answer` (
  `idQuestion` int(11) NOT NULL,
  `idAnswer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_question2comment`
--

CREATE TABLE `lf_question2comment` (
  `idQuestion` int(11) NOT NULL,
  `idComment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_question2tag`
--

CREATE TABLE `lf_question2tag` (
  `idQuestion` int(11) NOT NULL,
  `idTag` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_questionvote`
--

CREATE TABLE `lf_questionvote` (
  `id` int(11) NOT NULL,
  `idQuestion` int(11) NOT NULL,
  `idUser` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_tag`
--

CREATE TABLE `lf_tag` (
  `id` int(11) NOT NULL,
  `label` char(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `numQuestions` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumpning av Data i tabell `lf_tag`
--

INSERT INTO `lf_tag` (`id`, `label`, `description`, `numQuestions`) VALUES
(1, 'Landskap', 'En tag för landskap där vi ser horisonten som t ex öppna landskap eller bilder från hav och sjö.', 0),
(2, 'Intima-landskap', 'En tag för landskap där vi inte har någon horisont som t ex skogslandskap eller närbilder av det lilla landskapet.', 0),
(3, 'Platser', 'En tag för platser runt om vår värld, t ex en plats som man vill åka till eller där man redan har varit.', 0),
(4, 'Fototeknik', 'En tag för fototeknik där vi lär oss av varandra för att bli en bättre landskapsfotograf.', 0),
(5, 'Utrustning', 'En tag för utrustning där vi kan diskutera allt som rör vår utrustning som vi använder oss av när vi fotograferar.', 0),
(6, 'Kamera', 'En tag för kamera där vi kan diskutera allt som rör kameror.', 0),
(7, 'Objektiv', 'En tag för objektiv där vi kan diskutera allt som rör objektiv till våra kameror.', 0),
(8, 'Kläder', 'En tag för kläder där vi kan diskutera allt som rör våra kläder som vi använder oss av när vi är ute och fotograferar.', 0),
(9, 'Övrigt', 'En tag för övriga saker som vi kan använda oss av om vi vill disktuera något som inte passar in på de övriga taggarna.', 0);

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_user`
--

CREATE TABLE `lf_user` (
  `id` int(11) NOT NULL,
  `acronym` char(20) NOT NULL,
  `firstName` varchar(80) DEFAULT NULL,
  `lastName` varchar(80) DEFAULT NULL,
  `town` varchar(80) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `gravatar` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `activityScore` int(11) DEFAULT NULL,
  `numVotes` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumpning av Data i tabell `lf_user`
--

INSERT INTO `lf_user` (`id`, `acronym`, `firstName`, `lastName`, `town`, `email`, `gravatar`, `password`, `activityScore`, `numVotes`, `created`) VALUES
(1, 'admin', 'Administrator', 'Administrator', 'Staden', 'wgtotw@mail.se', 'http://www.gravatar.com/avatar/4af1d7ebcf0b456d6b4e85ae64523539.jpg', '$2y$10$pbDZHhkpHhlHdzfrfBfleOSZAPsbhSQGRtRpZkmyqeke8iiT5Wa2K', 0, 0, '2016-10-24 18:54:24');

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_user2answer`
--

CREATE TABLE `lf_user2answer` (
  `idUser` int(11) NOT NULL,
  `idAnswer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_user2comment`
--

CREATE TABLE `lf_user2comment` (
  `idUser` int(11) NOT NULL,
  `idComment` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellstruktur `lf_user2question`
--

CREATE TABLE `lf_user2question` (
  `idUser` int(11) NOT NULL,
  `idQuestion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Index för dumpade tabeller
--

--
-- Index för tabell `lf_answer`
--
ALTER TABLE `lf_answer`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_answer2comment`
--
ALTER TABLE `lf_answer2comment`
  ADD PRIMARY KEY (`idAnswer`,`idComment`),
  ADD KEY `idComment` (`idComment`);

--
-- Index för tabell `lf_answervote`
--
ALTER TABLE `lf_answervote`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_comment`
--
ALTER TABLE `lf_comment`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_commentvote`
--
ALTER TABLE `lf_commentvote`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_question`
--
ALTER TABLE `lf_question`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_question2answer`
--
ALTER TABLE `lf_question2answer`
  ADD PRIMARY KEY (`idQuestion`,`idAnswer`),
  ADD KEY `idAnswer` (`idAnswer`);

--
-- Index för tabell `lf_question2comment`
--
ALTER TABLE `lf_question2comment`
  ADD PRIMARY KEY (`idQuestion`,`idComment`),
  ADD KEY `idComment` (`idComment`);

--
-- Index för tabell `lf_question2tag`
--
ALTER TABLE `lf_question2tag`
  ADD PRIMARY KEY (`idQuestion`,`idTag`),
  ADD KEY `idTag` (`idTag`);

--
-- Index för tabell `lf_questionvote`
--
ALTER TABLE `lf_questionvote`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_tag`
--
ALTER TABLE `lf_tag`
  ADD PRIMARY KEY (`id`);

--
-- Index för tabell `lf_user`
--
ALTER TABLE `lf_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `acronym` (`acronym`);

--
-- Index för tabell `lf_user2answer`
--
ALTER TABLE `lf_user2answer`
  ADD PRIMARY KEY (`idUser`,`idAnswer`),
  ADD KEY `idAnswer` (`idAnswer`);

--
-- Index för tabell `lf_user2comment`
--
ALTER TABLE `lf_user2comment`
  ADD PRIMARY KEY (`idUser`,`idComment`),
  ADD KEY `idComment` (`idComment`);

--
-- Index för tabell `lf_user2question`
--
ALTER TABLE `lf_user2question`
  ADD PRIMARY KEY (`idUser`,`idQuestion`),
  ADD KEY `idQuestion` (`idQuestion`);

--
-- AUTO_INCREMENT för dumpade tabeller
--

--
-- AUTO_INCREMENT för tabell `lf_answer`
--
ALTER TABLE `lf_answer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT för tabell `lf_answervote`
--
ALTER TABLE `lf_answervote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT för tabell `lf_comment`
--
ALTER TABLE `lf_comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT för tabell `lf_commentvote`
--
ALTER TABLE `lf_commentvote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT för tabell `lf_question`
--
ALTER TABLE `lf_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT för tabell `lf_questionvote`
--
ALTER TABLE `lf_questionvote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT för tabell `lf_tag`
--
ALTER TABLE `lf_tag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT för tabell `lf_user`
--
ALTER TABLE `lf_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- Restriktioner för dumpade tabeller
--

--
-- Restriktioner för tabell `lf_answer2comment`
--
ALTER TABLE `lf_answer2comment`
  ADD CONSTRAINT `lf_answer2comment_ibfk_1` FOREIGN KEY (`idAnswer`) REFERENCES `lf_answer` (`id`),
  ADD CONSTRAINT `lf_answer2comment_ibfk_2` FOREIGN KEY (`idComment`) REFERENCES `lf_comment` (`id`);

--
-- Restriktioner för tabell `lf_question2answer`
--
ALTER TABLE `lf_question2answer`
  ADD CONSTRAINT `lf_question2answer_ibfk_1` FOREIGN KEY (`idQuestion`) REFERENCES `lf_question` (`id`),
  ADD CONSTRAINT `lf_question2answer_ibfk_2` FOREIGN KEY (`idAnswer`) REFERENCES `lf_answer` (`id`);

--
-- Restriktioner för tabell `lf_question2comment`
--
ALTER TABLE `lf_question2comment`
  ADD CONSTRAINT `lf_question2comment_ibfk_1` FOREIGN KEY (`idQuestion`) REFERENCES `lf_question` (`id`),
  ADD CONSTRAINT `lf_question2comment_ibfk_2` FOREIGN KEY (`idComment`) REFERENCES `lf_comment` (`id`);

--
-- Restriktioner för tabell `lf_question2tag`
--
ALTER TABLE `lf_question2tag`
  ADD CONSTRAINT `lf_question2tag_ibfk_1` FOREIGN KEY (`idQuestion`) REFERENCES `lf_question` (`id`),
  ADD CONSTRAINT `lf_question2tag_ibfk_2` FOREIGN KEY (`idTag`) REFERENCES `lf_tag` (`id`);

--
-- Restriktioner för tabell `lf_user2answer`
--
ALTER TABLE `lf_user2answer`
  ADD CONSTRAINT `lf_user2answer_ibfk_1` FOREIGN KEY (`idUser`) REFERENCES `lf_user` (`id`),
  ADD CONSTRAINT `lf_user2answer_ibfk_2` FOREIGN KEY (`idAnswer`) REFERENCES `lf_answer` (`id`);

--
-- Restriktioner för tabell `lf_user2comment`
--
ALTER TABLE `lf_user2comment`
  ADD CONSTRAINT `lf_user2comment_ibfk_1` FOREIGN KEY (`idUser`) REFERENCES `lf_user` (`id`),
  ADD CONSTRAINT `lf_user2comment_ibfk_2` FOREIGN KEY (`idComment`) REFERENCES `lf_comment` (`id`);

--
-- Restriktioner för tabell `lf_user2question`
--
ALTER TABLE `lf_user2question`
  ADD CONSTRAINT `lf_user2question_ibfk_1` FOREIGN KEY (`idUser`) REFERENCES `lf_user` (`id`),
  ADD CONSTRAINT `lf_user2question_ibfk_2` FOREIGN KEY (`idQuestion`) REFERENCES `lf_question` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
