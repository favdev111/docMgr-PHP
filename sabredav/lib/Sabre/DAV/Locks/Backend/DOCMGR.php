<?php

namespace Sabre\DAV\Locks\Backend;

use Sabre\DAV\Locks\LockInfo;

/**
 * The Lock manager allows you to handle all file-locks centrally.
 *
 * This Lock Manager stores all its data in a database. You must pass a PDO
 * connection object in the constructor.
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class DOCMGR extends AbstractBackend 
{
         
    public function __construct() {

    }

    /**
     * Returns a list of Sabre_DAV_Locks_LockInfo objects  
     * 
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * @param string $uri 
     * @return array 
     */
    public function getLocks($uri,$returnChildLocks) 
    {

        echo "Getting locks for ".$uri."\n";

        $opt = null;
        $opt["path"] = "/".$uri;
        $opt["child_locks"] = $returnChildLocks;

        //get our locks
        $d = new \EDAV_OBJECT($opt);
        $locks = $d->getLocks();

        $lockList = array();
        
        for ($i=0;$i<count($locks);$i++)
        {

          $row = &$locks[$i];
 
          $lockInfo = new LockInfo();
          $lockInfo->owner = $row['owner'];
          $lockInfo->token = $row['token'];
          $lockInfo->timeout = $row['timeout'];
          $lockInfo->created = $row['created'];
          $lockInfo->scope = $row['scope'];
          $lockInfo->depth = $row['depth'];
          $lockInfo->uri = $row['uri'];
          $lockList[] = $lockInfo;
 
        }

        return $lockList;

    }

    /**
     * Locks a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    public function lock($uri,LockInfo $lockInfo) {

        // We're making the lock timeout 30 minutes
        $lockInfo->timeout = 1800;
        $lockInfo->created = time();

        $opt = null;
        $opt["owner"] = $lockInfo->owner;
        $opt["token"] = $lockInfo->token;	
        $opt["timeout"] = $lockInfo->timeout;
        $opt["created"] = $lockInfo->created;
        $opt["scope"] = $lockInfo->scope;
        $opt["depth"] = $lockInfo->depth;
        $opt["uri"] = $lockInfo->uri;
        $opt["path"] = "/".$uri;

        $d = new \EDAV_OBJECT($opt);
        $d->lock();

    }

    /**
     * Removes a lock from a uri 
     * 
     * @param string $uri 
     * @param Sabre_DAV_Locks_LockInfo $lockInfo 
     * @return bool 
     */
    public function unlock($uri,LockInfo $lockInfo) 
    {

        $opt = null;
        $opt["token"] = $lockInfo->token;
        $opt["path"] = "/".$uri;

        $d = new \EDAV_OBJECT($opt);
        $d->unlock();

    }


}
