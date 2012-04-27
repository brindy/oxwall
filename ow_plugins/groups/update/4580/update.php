<?php

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'groups');

$query = array();

$P = OW_DB_PREFIX;

$query[] ="CREATE TABLE IF NOT EXISTS `{$P}groups_invite` (
  `id` int(11) NOT NULL auto_increment,
  `groupId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `inviterId` int(11) NOT NULL,
  `timeStamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `inviteUniq` (`groupId`,`userId`,`inviterId`),
  KEY `timeStamp` (`timeStamp`),
  KEY `userId` (`userId`),
  KEY `groupId` (`groupId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$query[] = "ALTER TABLE `{$P}groups_group` ADD `whoCanView` VARCHAR( 100 ) NOT NULL DEFAULT 'anyone', ADD `whoCanInvite` VARCHAR( 100 ) NOT NULL DEFAULT 'participant';";
$query[] = "ALTER TABLE `{$P}groups_group` ADD INDEX ( `whoCanView` );";

$query[] = "UPDATE `{$P}base_comment_entity` SET `pluginKey`='groups' WHERE `entityType`='groups_wal'";

foreach ( $query as $q )
{
    try
    {
        Updater::getDbo()->query($q);
    }
    catch ( Exception $e )
    {
        //TODO log exception
    }
}


