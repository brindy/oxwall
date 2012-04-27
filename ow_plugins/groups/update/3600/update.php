<?php

$query = array();

$P = OW_DB_PREFIX;

$query[] ="ALTER TABLE `{$P}groups_group_user` ADD UNIQUE (`groupId` ,`userId`);";
$query[] ="ALTER TABLE `{$P}groups_group_user` ADD INDEX ( `timeStamp` );";
$query[] ="ALTER TABLE `{$P}groups_group` ADD INDEX ( `timeStamp` );";
$query[] ="ALTER TABLE `{$P}groups_group` ADD INDEX ( `userId` );";
$query[] ="ALTER TABLE `{$P}groups_group_user` ADD `privacy` VARCHAR( 100 ) NOT NULL DEFAULT 'everybody';";

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

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'groups');