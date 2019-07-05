<?php

namespace Sabre\DAV\DOCMGR;

use Sabre\DAV;

/**
 * Base node-class
 *
 * The node class implements the method used by both the File and the Directory classes
 *
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Node extends DAV\FS\Node implements DAV\IProperties {

    /**
     * Returns the name of the node 
     * 
     * @return string 
     */
    public function getName() {

        return basename($this->path);

    }

    /**
     * Updates properties on this node,
     *
     * The mutations array, contains arrays with mutation information, with the following 3 elements:
     *   * 0 = mutationtype (1 for set, 2 for remove)
     *   * 1 = nodename (encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     *   * 2 = value, can either be a string or a DOMElement
     * 
     * This method should return a similar array, with information about every mutation:
     *   * 0 - nodename, encoded as in the $mutations argument
     *   * 1 - statuscode, encoded as http status code, for example
     *      200 for an updated property or succesful delete
     *      201 for a new property
     *      403 for permission denied
     *      etc..
     *
     * @param array $mutations 
     * @return void
     */
    function updateProperties($mutations) {

        $resourceData = $this->getProperties(array());
        
        $result = array();

        foreach($mutations as $mutation) {

            switch($mutation[0]){ 
                case Sabre_DAV_Server::PROP_SET :
                   if (isset($resourceData[$mutation[1]])) {
                       $result[] = array($mutation[1],200);
                   } else {
                       $result[] = array($mutation[1],201);
                   }
                   $resourceData[$mutation[1]] = $mutation[2];
                   break;
                case Sabre_DAV_Server::PROP_REMOVE :
                   if (isset($resourceData[$mutation[1]])) {
                       unset($resourceData[$mutation[1]]);
                   }
                   // Specifying the deletion of a property that does not exist, is _not_ an error
                   $result[] = array($mutation[1],200);
                   break;

            }

        }

        $opt = null;
        $opt["properties"] = $resourceData;
        $opt["path"] = $this->path;

        $d = new \EDAV_OBJECT($opt);
        $d->saveProperties();

        if ($d->getError()) 
        {
          throw new DAV\Exception\Forbidden($d->getError());
        }

        return $result;

    }

    /**
     * Returns a list of properties for this nodes.
     *
     * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     * If the array is empty, all properties should be returned
     *
     * @param array $properties 
     * @return void
     */
    function getProperties($properties) {

      $d = new \EDAV_OBJECT($this->path);
      $propData = $d->getProperties();

      if ($d->getError()) 
      {
        throw new DAV\Exception\Forbidden($d->getError());
      }
                                  
      if (!$propData) $propData = array();
       
      // if the array was empty, we need to return everything
      if (!$properties || count($properties)=="0") return $propData;

      $props = array();
      foreach($properties as $property) {
          if (isset($propData[$property])) $props[$property] = $propData[$property];
      }

      return $props;

    }

    public function delete() {

        //return $this->deleteResourceData();

    }

    /**
     * Returns the last modification time, as a unix timestamp 
     * 
     * @return int 
     */
    public function getLastModified() 
    {
      $obj = Directory::getObject($this->path);
      return strtotime($obj["last_modified"]);
    }

}


