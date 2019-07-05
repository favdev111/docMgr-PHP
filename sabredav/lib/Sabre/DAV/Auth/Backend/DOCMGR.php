<?php

namespace Sabre\DAV\Auth\Backend;

use Sabre\HTTP;
use Sabre\DAV;

/**
 * This is an authentication backend that uses a file to manage passwords.
 *
 * The backend file must conform to Apache's htdigest format
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
*/

class DOCMGR extends AbstractDigest {

    /**
     * List of users 
     * 
     * @var array
     */
    protected $users = array();

    public function getDigestHash($realm,$username)
    {

      $a = new \ACCOUNT();
      $info = $a->getInfo($username);

      return $info["digest_hash"];
          
    }

    public function getUsers()
    {

      if ($_SESSION["_DOCMGR_USER_INFO"]) $matches = $_SESSION["_DOCMGR_USER_INFO"];
      else
      {
    
        $a = new \ACCOUNT();
        $matches = $a->search(array("login"=>$username),"login");

        $_SESSION["_DOCMGR_USER_INFO"] = $matches;
        
      }
      
      $ret = array();
      $num = count($matches);
      
      for ($i=0;$i<$num;$i++) $ret[] = array("userId"=>$matches[$i]["login"],
                                              "uri"=>"principals/".$matches[$i]["login"]);
    
      return $ret;
      
    }


    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is succesful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function authenticate(\Sabre\DAV\Server $server, $realm) 
    {

        $digest = new HTTP\DigestAuth();
        //$digest = new \Sabre\HTTP\DigestAuth();

        // Hooking up request and response objects
        $digest->setHTTPRequest($server->httpRequest);
        $digest->setHTTPResponse($server->httpResponse);

        $digest->setRealm($realm);
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) 
        {
            $digest->requireLogin();
            throw new DAV\Exception\NotAuthenticated('No digest authentication headers were found');
        }

        //get our hash
        $hash = $this->getDigestHash($realm, $username); 

        //save for later
        //$hash = $info["digestHash"];
        //$userId = $info["userId"];
        //$password = $info["password"];
        
        // If this was false, the user account didn't exist
        if ($hash===false || is_null($hash)) 
        {
          $digest->requireLogin();
          throw new DAV\Exception\NotAuthenticated('The supplied username was not on file');
        }

        if (!is_string($hash)) 
        {
          throw new DAV\Exception\NotAuthenticated('The returned value from getDigestHash must be a string or null');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($hash)) 
        {
          $digest->requireLogin();
          throw new DAV\Exception\NotAuthenticated('Incorrect username');
        }
 
        $this->currentUser = $username;

        //initialize our authentication to the system.
        $AUTH = new \AUTH($username,null,$hash);
        $AUTH->authorize();
        
        if ($AUTH->getError())
        {
          throw new DAV\Exception\NotAuthenticated($userId.":".$password.":".$AUTH->getError());
        }

        return true;

    }


}


