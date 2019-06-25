SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `accessTokens` (
  `characterID` bigint(16) NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `refreshToken` varchar(255) NOT NULL,
  `accessToken` varchar(4096) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `failcount` int(11) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `authTokens` (
  `id` int(11) NOT NULL,
  `selector` char(32) DEFAULT NULL,
  `token` char(64) DEFAULT NULL,
  `characterID` bigint(16) NOT NULL,
  `expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `deleted_mails` (
  `mailID` bigint(20) NOT NULL,
  `deleted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `characterID` bigint(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `esisso` (
  `id` int(11) NOT NULL,
  `characterID` bigint(16) NOT NULL,
  `characterName` varchar(255) DEFAULT NULL,
  `ownerHash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `accessTokens`
  ADD UNIQUE KEY `characterID` (`characterID`,`scope`);

ALTER TABLE `authTokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `characterID` (`characterID`);

ALTER TABLE `deleted_mails`
  ADD PRIMARY KEY (`mailID`),
  ADD UNIQUE KEY `mailid` (`mailID`);

ALTER TABLE `esisso`
  ADD PRIMARY KEY (`characterID`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `characterID` (`characterID`);

ALTER TABLE `authTokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `esisso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;
