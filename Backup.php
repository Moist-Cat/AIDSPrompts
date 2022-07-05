<?php 
//Class to backup database an give file to user
require_once "class/DatabaseBackup.php";
$databaseBackup = new DatabaseBackup();
//We just use the function to backup the database in a .sql that the user can download.
$databaseBackup->backupDatabase(array("prompts","worldinfos"));
?>
