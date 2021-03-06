<?php

OW::getNavigation()->addMenuItem(OW_Navigation::MAIN, 'spodpublic.main', 'spodpublic', 'main', OW_Navigation::VISIBLE_FOR_MEMBER);

$sql = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'spod_public_room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ownerId` int(11) NOT NULL,
  `subject` text,
  `body` text,
  `views` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `opendata` int(11) DEFAULT 0,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `post` varchar(512),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'spod_public_room_suggestion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ownerId` int(11) NOT NULL,
  `publicRoomId` int(11) NOT NULL,
  `dataset` text,
  `comment` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'spod_public_room_comment_sentiment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publicRoomId` int(11) NOT NULL,
  `sentiment` smallint,
  `commentId` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'spod_public_room_user_notification` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `userId` INT NOT NULL,
  `roomId` INT NOT NULL,
  PRIMARY KEY (`id`)
)  ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;';

OW::getDbo()->query($sql);

$authorization = OW::getAuthorization();
$groupName = 'spodpublic';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'view', true);
$authorization->addAction($groupName, 'add_comment');

$path = OW::getPluginManager()->getPlugin('spodpublic')->getRootDir() . 'langs.zip';
BOL_LanguageService::getInstance()->importPrefixFromZip($path, 'spodpublic');

OW::getPluginManager()->addPluginSettingsRouteName('spodpublic', 'public-room-settings');