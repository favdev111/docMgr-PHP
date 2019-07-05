<?php
/*****************************************************************************************
  Fileame: ldap-config.php
  Purpose: Contains all settings for ldap connectiving and attribute mapping
******************************************************************************************/

/************************************************************
	LDAP Connectivity
************************************************************/

//your ldap server uri
define("LDAP_SERVER","ldap://localhost");

//your ldap server port
define("LDAP_PORT","389");

//the dn to bind to your server with
define("BIND_DN","cn=root,dc=mydomain,dc=com");

//the password of the above specified dn
define("BIND_PASSWORD","secret");

//your search attribute base for accounts
define("LDAP_BASE","dc=mydomain,dc=com");

//default base for creating accounts
define("LDAP_CREATE_BASE",LDAP_BASE);

//a search filter to limit valid accounts to
define("LDAP_FILTER","(uid=*)");

//password-hash setting in your slapd.conf file.  
//options are {SHA},{SSHA},{MD5},{SMD5},{CLEARTEXT},{CRYPT}
//CRYPT uses the highest supported system encryption and is recommended
//if your LDAP database is configured to use it
define("LDAP_CRYPT","{CRYPT}");

//password-crypt-salt-format set in your slapd.conf (if not set, leave commented out)
//the "$6$%.86s" is for SHA512
define("LDAP_CRYPT_SALT","$6$%.86s");

//ldap protocol
define("LDAP_PROTOCOL","3");

//default group id for a new account
define("DEFAULT_GID","100");

//base of our tree
define("LDAP_ROOT","dc=mydomain,dc=com");

//uncomment to make ldap read-only.  will be used for authentication but no 
//writes back to your ldap database will occur
//define("LDAP_READ_ONLY","1");

/***********************************************************
	Attribute Mapping
***********************************************************/
define("LDAP_UID","uid");
define("LDAP_UIDNUMBER","uidNumber");
define("LDAP_GIDNUMBER","gidNumber");
define("LDAP_USERPASSWORD","userPassword");
define("LDAP_CN","cn");
define("LDAP_SN","sn");
define("LDAP_GECOS","gecos");
define("LDAP_TELEPHONENUMBER","telephoneNumber");
define("LDAP_GIVENNAME","givenName");
define("LDAP_MAIL","mail");

//your dn in your directory should look like this:
//<UID>=<login>,<LDAP_BASE>
//ex: uid=mylogin,ou=people,dc=mydomain,dc=com
