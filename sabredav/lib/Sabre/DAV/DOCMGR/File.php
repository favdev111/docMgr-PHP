<?php

namespace Sabre\DAV\DOCMGR;
use Sabre\DAV;

/**
 * File class
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class File extends Node implements DAV\PartialUpdate\IFile {

    /**
     * Updates the data
     *
     * data is a readable stream resource.
     *
     * @param resource|string $data
     * @return string
     */
    public function put($data) 
    {

      $name = getFileName($this->path);
      
      //write the file to a temp directory
      $tmpdir = TMP_DIR."/".USER_LOGIN;
      recurmkdir($tmpdir);

      $tmpfile = $tmpdir."/".md5(rand()).".dat";

      file_put_contents($tmpfile,$data);

      $opt = null;
      $opt["name"] = $name;
      $opt["filepath"] = $tmpfile;
      $opt["path"] = $this->path;
      $opt["token"] = $this->getLockToken();

      $d = new \EDAV_OBJECT($opt);
      $d->put();

      @unlink($tmpfile);

    }

    /**
     * Updates the data at a given offset
     *
     * The data argument is a readable stream resource.
     * The offset argument is a 0-based offset where the data should be
     * written.
     *
     * param resource|string $data
     * @return void
     */
    public function putRange($data, $offset) {

        $f = fopen($this->path, 'c');
        fseek($f,$offset-1);
        if (is_string($data)) {
            fwrite($f, $data);
        } else {
            stream_copy_to_stream($data,$f);
        }
        fclose($f);
        return '"' . md5_file($this->path) . '"';

    }

    /**
     * Returns the data
     *
     * @return resource
     */
    public function get() 
    {
      //return a file stream to the file
      $d = new \EDAV_OBJECT($this->path);
      return $d->get();
    }

    /**
     * Delete the current file
     *
     * @return bool
     */
    public function delete() 
    {
      $d = new \EDAV_OBJECT($this->path);
      $d->delete();
    }

    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * Return null if the ETag can not effectively be determined
     *
     * @return string|null
     */
    public function getETag() 
    {
      return null;
      //return '"' . md5_file($this->path). '"';
    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return string|null
     */
    public function getContentType() 
    {
      $obj = Directory::getObject($this->path);

      if ($obj["object_type"]=="document") $type = "text/html";
      else
      {
        $fn = getFileName($this->path);
        $type =  return_file_mime($fn);
      }
      
      return $type;


    }

    /**
     * Returns the size of the file, in bytes
     *
     * @return int
     */
    public function getSize() 
    {
      $obj = Directory::getObject($this->path);
      return $obj["size"];
    }

    function getLockToken()
    {

      $token = null;
    
      foreach ($_SERVER as $name => $value) 
      {
         if (substr($name, 0, 5) == 'HTTP_') 
         {

           $key = substr($name,5);

          //windows uses this
          if ($key=="IF")
          {

            $prefix = "<opaquelocktoken:";

            $pos = strpos($value,$prefix);
            if ($pos!==FALSE) 
            {
            
              $token = substr($value,strlen($prefix)+1);
              $token = substr($token,0,strpos($token,">"));
              break;  
            }

          }
          //Finder uses this
          else if ($key=="LOCK_TOKEN")
          {

            $prefix = "<opaquelocktoken:";

            $pos = strpos($value,$prefix);
            if ($pos!==FALSE) 
            {
            
              $token = substr($value,strlen($prefix)+1);
              $token = substr($token,0,strpos($token,">"));
              break;  
            }

          }

        }

      }

      return $token;

    }

}

