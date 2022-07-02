<?php require_once "class\DatabaseBackup.php";

$databaseBackup = new DatabaseBackup();

$databaseBackup->backupDatabase(array("prompts","worldinfos"));
?>