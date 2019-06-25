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

ALTER TABLE `esisso`
  DROP COLUMN `refreshToken`,
  DROP COLUMN `accessToken`,
  DROP COLUMN `expires`,
  DROP COLUMN `failcount`,
  DROP COLUMN`enabled`
;

ALTER TABLE `accessTokens`
  ADD UNIQUE KEY `characterID` (`characterID`,`scope`);

COMMIT;
