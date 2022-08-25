
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";



CREATE TABLE IF NOT EXISTS `hgauth` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(8) CHARACTER SET utf8 NOT NULL,
  `uuid` char(36) CHARACTER SET utf8,
  `avatarname` varchar(64) CHARACTER SET utf8,
  `createtime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmtime` datetime,
  PRIMARY KEY (`id`),
  UNIQUE KEY uuid (`uuid`),
  UNIQUE KEY avatarname (`avatarname`)
) DEFAULT CHARSET=utf8;
COMMIT;

