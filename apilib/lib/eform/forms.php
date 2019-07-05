<?php

class EFORM_FORMS extends EFORM
{

  private $file;
  private $deffile;
  private $definitions;
	private $DOM;
	    
  function ___construct()
  {

    $this->file = $this->apidata["file"];
    $this->deffile = $this->apidata["definition_file"];
    
    if (!$this->file)
    {
      $this->throwError("No xml definition file specified");
      return false;
    }

    if (!file_exists($this->file))
    {
      $this->throwError("Could not find xml defintion file");
      return false;
    }

  }


  function load()
  {
  
    $str = file_get_contents($this->file);

    //load our master file
    if ($this->deffile) $this->processDefinitionFile();

    //convert simple to dom so we can work with it
    $this->DOM = new DOMDocument();
    $this->DOM->loadXML($str);

    //get all forms
    $forms = $this->DOM->getElementsByTagName("form");

    //loop through and see if we have to add any options
    foreach ($forms AS $form) 
    {

      $children = $form -> childNodes;
  
      //reset our element fields
      $arr = array();
 
      //store in an array for later access
      foreach ($children AS $child) 
      {

        //if we have a nodetype of 3 and a value, it's a single form
        if ($this->deffile && $child->nodeType==3 && $child->textContent) 
        {
          $arr["form"] = trim($child->textContent);			//the name of our form
          $arr["child"] = $child;									//child ref so we can delete it later
          break;
        }

        //process regular nodes here
        if ($child->nodeType!=1) continue;
        $arr[$child->nodeName] = $child->textContent;
  
      }

      //if form is set, this means it's defined in our def file.  pull the form info from there
      if ($this->deffile && $arr["form"])
      {
      	 $arr = $this->mergeDefinitionForm($form,$arr);
			}
			
			//if passed an api_command, call the command to populate options for the form
		  if ($arr["api_command"]) $form = $this->mergeAPIData($form,$arr);

		}

		//clear any data put into proto by our api calls
		$this->PROTO->clearData();

		if ($this->getError())
		{
			return false;
		}
		else
		{
			header("Content-Type: text/xml");

			//output directly to the client
			print $this->DOM->saveXML();
		
			die;
		
		}

		

	}


  private function processDefinitionFile()
  {
  
      $defstr = file_get_contents($this->deffile);

      //create defforms
      $defdom = new DOMDocument();
      $defdom->DOM->loadXML($defstr);

      $defforms = $defdom -> getElementsByTagName("form");
      $this->definitions = array();

      //populate our defform array
      foreach ($defforms AS $defform) 
      {

        $defchildren = $defform -> childNodes;
        $arr = array();

        //store in an array for later access
        foreach ($defchildren AS $defchild) 
        {
        
          if ($defchild->nodeType!=1) continue;

          //handle manual option tags      
          if ($defchild->nodeName=="option") 
          {

            $tmp = array();
            $chittlens = $defchild->childNodes;

            foreach ($chittlens AS $chittle) 
            {
              if ($chittle->nodeType!=1) continue;
              $tmp[$chittle->nodeName] = $chittle->textContent;
            }
        
            $arr["option"] = $tmp;

          } 
          else 
          {
            $arr[$defchild->nodeName] = $defchild->textContent;
          }

        }

        //the key for looking this up later
        $key = &$arr["name"];
        $this->definitions[$key] = $arr;
  
      }  

  }

  private function mergeDefinitionForm($form,$arr)
  {
  
        //form doesn't appear to be defined, try to find it in our def array
        $key = $arr["form"];
        $tempform = $this->definitions[$key];

        //stop here, form couldn't be found
        if (!$tempform) die("Form ".$key." not found in definition file");

		    //merge our defarray data back into the main dom
		    if ($tempform["type"]) 
		    {
		
		      //remove the text only child from the form
		      $form->removeChild($arr["child"]);
		
		      //add all our keys from the definition into the dom
		      $keys = array_keys($tempform);
		      foreach ($keys AS $formkey) 
		      {
		
		        //if there's a subarray, add it as a new child element
		        if (is_array($tempform[$formkey])) 
		        {
		
		          //get key sof the cild
		          $subkeys = array_keys($tempform[$formkey]);        
		
		          //create new element
		          $e = $this->DOM->createElement($formkey);
		
		          //add array values to new element          
		          foreach ($subkeys AS $sub) 
		          {
		            $e->appendChild($this->DOM->createElement($sub,$tempform[$formkey][$sub]));            
		          }
		          //add back to the main form
		          $form->appendChild($e);
		
		        //otherwise just add an element with text content        
		        } 
		        else 
		        {
		          $form->appendChild($this->DOM->createElement($formkey,$tempform[$formkey]));
		        }
		      }
		    //we didn't find a valid definition in the def file, skip this one
		    } 
		    else 
		    {
		      die("Form ".$key." does not appear to be defined properly in the def file");
		    }

		    //reset arr to our temp form for further data extraction if necessary
		    return $tempform;
  
  }

  private function mergeAPIData($form,$arr)
  {
  
		  //get our class parameters
		  $api_command = &$arr["api_command"];
		  $title_field = &$arr["title_field"];
		  $data_field = &$arr["data_field"];

			if ($this->apidata["api_parameters"]) $api_parameters = $this->apidata["api_parameters"][0];
			else $api_parameters = null;
					  	
		  //split the api into it's parts
		  $tmp = explode("_",$api_command);

		  if (count($tmp)!=3)
		  {
		  	$this->throwError("API command must have 3 segments");
		  	return false;
			}
  
			//fetch the parts
			$api_method = array_pop($tmp);
			$api_class = implode("_",$tmp);

			//run the query, call the method with any parameters passed to us by the client
			$API = new $api_class($api_parameters);
			$list = $API->$api_method();
			unset($list["count"]);

			//if there was an error
			if ($API->getError())
			{
				$this->throwError($API->getError());
				return false;
			}
	                                                                                  
		  //fallback
		  if (!$title_field) $title_field = "name";
		  if (!$data_field) $data_field = "id";
		
		  $num = count($list);
		  
		  for ($i=0;$i<$num;$i++)
		  {
		  
		    $option = $this->DOM->createElement("option");
		    $title = $this->DOM->createElement("title");
		    $data = $this->DOM->createElement("data");
		
		    //append the data to the nodes
		    $title->appendChild($this->DOM->createTextNode($list[$i][$title_field]));
		    $data->appendChild($this->DOM->createTextNode($list[$i][$data_field]));
		
		    //add to the option, and add the option to the dom
		    $option -> appendChild($title);
		    $option -> appendChild($data);
		    $form -> appendChild($option);
		
		  } 
  
		  return $form;
		  
  }
	
}

