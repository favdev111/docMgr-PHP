#!/usr/bin/php
<?php

include("../config/config.php");
include("../lib/pgsql.php");

$userId = $argv[1];

if (!$userId)
{

  echo "You must pass the user id of the account you want to add as an administrator\n";
  exit(0);


}


$DB = new POSTGRESQL(DBHOST,DBUSER,DBPASSWORD,DBPORT,DBNAME);

$sql = "SELECT account_id FROM auth.account_permissions WHERE account_id='".$userId."'";
$info = $DB->single($sql);

if ($info)
{
  $sql = "UPDATE auth.account_permissions SET bitmask='00000000000000000000000000000001' WHERE account_id='".$userId."'";
  $DB->query($sql);
}
else
{
  $sql = "INSERT INTO auth.account_permissions (account_id,enable,bitmask) VALUES ('".$userId."','t','00000000000000000000000000000001');";
  $DB->query($sql);
}

if (!$DB->error()) echo "Process complete\n";