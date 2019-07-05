<?php

namespace Sabre\DAV\DOCMGR;

use Sabre\DAV;

/**
 * Directory class
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Directory extends Node implements DAV\ICollection, DAV\IQuota {
  
  protected $objects;
  protected $object;
        
    /**
     * Creates a new file in the directory
     *
     * Data will either be supplied as a stream resource, or in certain cases
     * as a string. Keep in mind that you may have to support either.
     *
     * After successful creation of the file, you may choose to return the ETag
     * of the new file here.
     *
     * The returned ETag must be surrounded by double-quotes (The quotes should
     * be part of the actual string).
     *
     * If you cannot accurately determine the ETag, you should not return it.
     * If you don't store the file exactly as-is (you're transforming it
     * somehow) you should also not return an ETag.
     *
     * This means that if a subsequent GET to this new file does not exactly
     * return the same contents of what was submitted here, you are strongly
     * recommended to omit the ETag.
     *
     * @param string $name Name of the file
     * @param resource|string $data Initial payload
     * @return null|string
     */
    public function createFile($name, $data = null) 
    {

      // We're not allowing dots
      if ($name=='.' || $name=='..') throw new DAV\Exception\Forbidden('Permission denied to . and ..');

      //write the file to a temp directory
      $tmpdir = TMP_DIR."/".USER_LOGIN;
      recurmkdir($tmpdir);

      $tmpfile = $tmpdir."/".md5(rand()).".dat";
      file_put_contents($tmpfile,$data);

      $opt = null;
      $opt["name"] = $name;
      $opt["filepath"] = $tmpfile;
      $opt["path"] = $this->path;

      $d = new \EDAV_OBJECT($opt);
      $d->put();

      return null;
      //return '"' . md5_file($tmpfile) . '"';

    }

    /**
     * Creates a new subdirectory
     *
     * @param string $name
     * @return void
     */
    public function createDirectory($name) 
    {
        // We're not allowing dots
        if ($name=='.' || $name=='..') throw new DAV\Exception\Forbidden('Permission denied to . and ..');

        $opt = null;
        $opt["name"] = $name;
        $opt["path"] = $this->path;
        $opt["object_type"] = "collection";

        $d = new \EDAV_OBJECT($opt);
        $d->mkdir();

    }

    /**
     * Returns a specific child node, referenced by its name
     *
     * This method must throw Sabre\DAV\Exception\NotFound if the node does not
     * exist.
     *
     * @param string $name
     * @throws DAV\Exception\NotFound
     * @return DAV\INode
     */
    public function getChild($name,$objinfo = null) 
    {

        if ($name=='.' || $name=='..') throw new DAV\Exception\Forbidden('Permission denied to . and ..');

        $path = $this->path . '/' . $name;
        $path = str_replace("/webdav.php","/",$path);
        $path = str_replace("/DavWWWRoot","/",$path);
        $path = str_replace("//","/",$path);

        if ($path=="/")
        {
          return new Directory($path);
        }
        else
        { 

          if ($objinfo)
          {
            $this->object = $objinfo;
          }
          else
          { 
            
            //get the info for this object
            $d = new \EDAV_OBJECT($path);
            $this->object = $d->objectInfo;

            if ($d->getError())
            {
              throw new DAV\Exception\Forbidden($d->getError());
            }
          
          }

          if ($this->object)
          {	

            if ($this->object["object_type"]=="collection")
            {
              return new Directory($path);
            }
            else
            { 
              return new File($path);
            }

          }
          else
          { 
            throw new DAV\Exception\FileNotFound('File with name ' . $path . ' could not be located');
          }

        }

    }

    /**
     * Checks if a child exists.
     *
     * @param string $name
     * @return bool
     */
    public function childExists($name) 
    {

        if ($name=='.' || $name=='..')
            throw new DAV\Exception\Forbidden('Permission denied to . and ..');

        $path = $this->path . '/' . $name;
        $path = str_replace("/webdav.php","/",$path);
        $path = str_replace("/DavWWWRoot","/",$path);
        $path = str_replace("//","/",$path);
                        
        $d = new \EDAV_OBJECT($path);


        if ($d->objectInfo) return true;
        else return false;
                                                                    
        $path = $this->path . '/' . $name;
        return file_exists($path);

    }

    /**
     * Returns an array with all the child nodes
     *
     * @return DAV\INode[]
     */
    public function getChildren() 
    {

      $e = new \EDAV_QUERY($this->path);

      //we'll use this later
      $e->objectInfo["object_path"] = $this->path;
      $_SESSION["current_object"] = $e->objectInfo;
      
      $this->objects = $e->browse();

      $nodes = array();

      if ($e->getError()) 
      {
        throw new DAV\Exception\Forbidden($e->getError());
      }

      $_SESSION["docmgr_objects"] = $this->objects;

      $num = count($this->objects);

      for ($i=0;$i<$num;$i++) 
      {
        $nodes[] = $this->getChild($this->objects[$i]["name"],$this->objects[$i]);
      }

      return $nodes;
                                                                                                         
    }

    /**
     * Deletes all files in this directory, and then itself
     *
     * @return bool
     */
    public function delete() 
    {

      $d = new \EDAV_OBJECT($this->path);
      $d->delete();

      if ($d->getError())
      {
        throw new DAV\Exception\Forbidden($d->getError());
      }
                              
    }

    /**
     * Returns available diskspace information
     *
     * @return array
     */
    public function getQuotaInfo() {

        return array(
            @disk_total_space($this->path)-disk_free_space($this->path),
            @disk_free_space($this->path)
            );

    }

    public function getObject($path)
    {

      $objects = $_SESSION["docmgr_objects"];
      $num = count($objects);
      $obj = null;

      //we may be asking for the current object's children.  we already have that
      for ($i=0;$i<$num;$i++)
      {
        if ($objects[$i]["object_path"]==$path)
        {
          $obj = $objects[$i];
          break;
        }
      }

      //we may be asking for the current object, and we already have that info too
      if (!$obj && $path==$_SESSION["current_object"]["object_path"])
      {
        $obj = $_SESSION["current_object"];    
      }

      //path error, hit the api directly
      if (!$obj)
      {
        $d = new \EDAV_OBJECT($path);
        $obj = $d->objectInfo;

        if ($d->getError())
        {
          throw new DAV\Exception\Forbidden($d->getError());
        }

      }
 
      return $obj;
 
    } 
 
}

