<?php

/**************************************************************************

	app-config.php

	This file was automatically created by the installer.  You may 
	edit it at any time.  The installer will migrate your settings 
	into the new config file at upgrade time.  Be sure to backup   
	this file before attempting upgrades.  Non-standard config     
	options should go in the custom-config.php file.               

**************************************************************************/


/**********************************************************
	Apps
***********************************************************/

//Path to python binary with UNO bindings (usually in OpenOffice program directory)
define("APP_PYTHON","/opt/openoffice4/program/");

//OCR Program.  All content should be output to stdout
define("APP_OCR","ocrad --format=utf8");

//WGET for url objects.  Outputs to file
define("APP_WGET","wget -O");

//Do not remove the -nopgbrk option
define("APP_PDFTOTEXT","pdftotext -nopgbrk -q");

//PDF Images processing
define("APP_PDFIMAGES","pdfimages -q");

//Path to sendmail
define("APP_SENDMAIL","sendmail");

//PHP CLI binary
define("APP_PHP","php");

//Virus scanner
define("APP_CLAMAV","clamscan");

//Tiff Info
define("APP_TIFFINFO","tiffinfo");

//Tiff Split
define("APP_TIFFSPLIT","tiffsplit");

//Imagemagick convert
define("APP_CONVERT","convert");

//Imagemagick mogrify
define("APP_MOGRIFY","mogrify");

//Imagemagick montage
define("APP_MONTAGE","montage");

//Imagemagick identify
define("APP_IDENTIFY","identify");

//Ghostscript
define("APP_GS","gs");

//pdftoppm
define("APP_PDFTOPPM","pdftoppm");

