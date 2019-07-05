<?php

$show_login_form = 1;

//logout out.  If there is a login set to show it
if (isset($_REQUEST["logout"])) 
{

	$a = new AUTH();
	$a->logout();

	//go back to the main page
	header("Location: index.php");

} 
else if (isset($_SESSION["api"]["authorize"]))
{

	//sets some account related constants for us
	$a = new AUTH();
	$a->authorize();

	//set our permission defines
	$show_login_form = null;

} 
else 
{
	//sets some account related constants for us
	$a = new AUTH();
	$a->authorize();
	echo $a->getError()."\n";
	$module = "login";
} 
