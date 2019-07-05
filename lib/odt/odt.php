<?php

//get our includes
include("lib/odt/odt_table.php");

class ODT
{

  protected $content;			//domdocument ref for content.xml
  protected $ce;			//documentelement ref for content.xml
  protected $se;
  protected $officeNS;			//office namespace
  protected $styleNS;
  protected $body;			//reference to body of content.xml
  protected $styles;
  protected $masterpage;
  protected $automaticstyles;
  protected $tableCount;
  protected $templateDir;
  protected $tempDir;
  protected $outputFormat;
  protected $outputLogo;
  protected $images;
  protected $manifest;
  protected $me;
        
  //can be set by calling interface
  public $tableCellStyle;
        
  /********************************************************
    FUNCTION:	construct
    PURPOSE:	inits instance, sets up our xml files
              for dom access
  ********************************************************/
  function ___construct()
  {

    $this->setupNamespaces();

    //report template directory
    $this->templateDir = SITE_PATH."/config/openoffice/templates/asterisk-billing";
    
    //setup our temp directory
    $this->tmpDir = TMP_DIR."/".USER_LOGIN."/reports";
    recurmkdir($this->tmpDir);

    //load the content.xml file into DOM
    $this->content = new DOMDocument();
    $this->content->preserveWhitespace = true;
    $this->content->formatOutput = true;
    $this->content->substituteEntities = true;
    $this->content->load($this->templateDir."/content.xml");
    $this->ce = $this->content->documentElement;
  
    //get the main place for storing content
    $b = $this->ce->getElementsByTagNameNS($this->officeNS,"text");
    $this->body = $b->item(0);

    $b = $this->ce->getElementsByTagNameNS($this->officeNS,"automatic-styles");
    $this->automaticstyles = $b->item(0);

    //our file manifest
    $this->manifest = new DOMDocument();
    $this->manifest->preserveWhitespace = true;
    $this->manifest->formatOutput = true;
    $this->manifest->substituteEntities = true;
    $this->manifest->load($this->templateDir."/META-INF/manifest.xml");
    $this->me = $this->manifest->documentElement;

    //keep track of how many tables we have made
    $this->tableCount = 0;

    //keep track of images added
    $this->images = array();

    //load the styles.xml file into DOM
    $this->styles = new DOMDocument();
    $this->styles->preserveWhitespace = true;
    $this->styles->formatOutput = true;
    $this->styles->substituteEntities = true;
    $this->styles->load($this->templateDir."/styles.xml");
    $this->se = $this->styles->documentElement;

    //get the main place for storing content
    $b = $this->se->getElementsByTagNameNS($this->styleNS,"master-page");
    $this->masterpage = $b->item(0);


  }    

  /********************************************************
    FUNCTION:	setupNamespaces
    PURPOSE:	gets xml namespaces for accessing certain
              dom objects
  ********************************************************/
  function setupNamespaces()
  {
  
    //namespace URIs
    $this->officeNS = "urn:oasis:names:tc:opendocument:xmlns:office:1.0";
    $this->styleNS = "urn:oasis:names:tc:opendocument:xmlns:style:1.0";  

  }

  function setOrientation($name)
  {
  
    //default to letter 8.5x11
    if (!$name) $name = "LetterPortraitMode";
    
    $this->masterpage->setAttribute("style:page-layout-name",$name);
  
  }  

  function setOutputFormat($format)
  {
    $this->outputFormat = $format;
  }

  /********************************************************
    FUNCTION:	addHeader
    PURPOSE:	adds a report header, stored as a table
    INPUTS:		text -> header title
              count -> total group count for header
              percent -> percent of total for header
              level -> header level
  ********************************************************/
  function addHeader($text,$level)
  {

    $styleName = "HeaderLevel".$level;
    
    //first we need a container
    $header = $this->content->createElement("text:p",$text);  
    $header->setAttribute("text:style-name",$styleName);

    $this->body->appendChild($header); 
  
  }

  /********************************************************
    FUNCTION:	addPargraph
    PURPOSE:	adds a paragraph
    INPUTS:		text -> header title
              styleName -> style name to apply
  ********************************************************/
  function addParagraph($text,$styleName=null)
  {

    //first we need a container
    $paragraph = $this->content->createElement("text:p",$text);  

    if ($styleName) $paragraph->setAttribute("text:style-name",$styleName);

    $this->body->appendChild($paragraph); 
  
  }

  /********************************************************
    FUNCTION:	addPageBreak
    PURPOSE:	adds a report header, stored as a table
    INPUTS:		text -> header title
              count -> total group count for header
              percent -> percent of total for header
              level -> header level
  ********************************************************/
  function addPageBreak()
  {

    //first we need a container
    $header = $this->content->createElement("text:p");  
    $header->setAttribute("text:style-name","PageBreak");

    $this->body->appendChild($header); 
  
  }

  function addTableStyle($name,$border=null,$bgcolor=null)
  {
  
    //add a style for it
    $s = $this->content->createElement("style:style");
    $s->setAttribute("style:name",$name);
    $s->setAttribute("style:family","table-cell");
  
    $props = $this->content->createElement("style:table-cell-properties");
    
    if ($border) $props->setAttribute("fo:border",$border);
    if ($bgcolor) $props->setAttribute("fo:background-color",$bgcolor);
    
    $s->appendChild($props);
    $this->automaticstyles->appendChild($s);    
  
  }

  /********************************************************
    FUNCTION:	addTable
    PURPOSE:	adds a table for storing core report data
    INPUTS:		arr -> array of table row data to display
              level -> table level
              header -> header column names
              footer -> column footer data
  ********************************************************/
  function addTable($arr,$level,$header=null,$footer=null,$widthArr=null,$alignArr=null,$headerAlignArr=null)
  {

    $this->tableCount++;
    $tableName = "Table".$this->tableCount;
    $styleName = "Table".$level;

    //first we need a container
    $cont = $this->content->createElement("text:p");
    $cont->setAttribute("text:style-name","Text_20_body");

    $table = $this->content->createElement("table:table");  
    $table->setAttribute("table:style-name",$styleName);
    $table->setAttribute("table:name",$tableName);
    $table->setAttribute("table:number-columns-repeated",count($arr[0]));

    //create our columns
    for ($i=0;$i<count($arr[0]);$i++)
    {

      $col = $this->content->createElement("table:table-column");

      if ($widthArr[$i]) 
      {

         $colStyleName = $tableName."Column".$i;

         //add a style for it
         $s = $this->content->createElement("style:style");
         $s->setAttribute("style:name",$colStyleName);
         $s->setAttribute("style:family","table-column");
         
         //the col property
         $colprop = $this->content->createElement("style:table-column-properties");
         $colprop->setAttribute("style:column-width",$widthArr[$i]);

         //add to main styles section
         $s->appendChild($colprop);
         $this->automaticstyles->appendChild($s);

          //set the style name for the column
         $col->setAttribute("table:style-name",$colStyleName);
                  
      }

      $table->appendChild($col);

    }

    if ($header && count($header) > 0) 	$this->addRows(&$table,"TableHeader",$header,$headerAlignArr);
    if ($arr && count($arr) > 0) 				$this->addRows(&$table,"TableCell",$arr,$alignArr);
    if ($footer && count($footer) > 0) 	$this->addRows(&$table,"TableFooter",$footer);
     
    $this->body->appendChild($table); 
  
  }

  /********************************************************
    FUNCTION:	addRows
    PURPOSE:	adds a row to a table
    INPUTS:		table -> reference to table to add row to
              styleName -> row style name
              data -> data for row
  ********************************************************/
  function addRows($table,$styleName,$data,$alignData=null)
  {

    if ($styleName=="TableHeader") $hr = $this->content->createElement("table:table-header-rows");
    else $hr = null;
    
      //add the header
    foreach ($data AS $rowData)
    {

      $row = $this->content->createElement("table:table-row");
      $i = 0;

      foreach ($rowData AS $cellData)
      {

        if ($alignData)
        {

          if ($alignData[$i]=="center") 
          {
            if ($styleName == "TableHeader") $useStyleName = "TableHeaderCellCenter";
            else $useStyleName = "TableCellCenter";
          }
          else if ($alignData[$i]=="right")
          {
            if ($styleName == "TableHeader") $useStyleName = "TableHeaderCellRight";
            else $useStyleName = "TableCellRight";
          }
          else
          {
            $useStyleName = $styleName;
          }
                    
        }
        else $useStyleName = $styleName;
                
        //keeper of the text
        $p = $this->content->createElement("text:p");
        $p->setAttribute("text:style-name",$useStyleName);
        $p->appendChild($this->content->createCDATASection($cellData));
        
        //create the table cell              
        $cell = $this->content->createElement("table:table-cell");
        $cell->setAttribute("office:value-type","string");

        if ($this->tableCellStyle) $cell->setAttribute("table:style-name",$this->tableCellStyle);
                
        //throw it together
        $cell->appendChild($p);
        $row->appendChild($cell);  

        $i++;
        
      }

      //add the row to the main table      
      if ($hr) $hr->appendChild($row);
      else $table->appendChild($row);
      
    }

    if ($hr) $table->appendChild($hr);
  
  
  }

  function createImage($imgsrc)
  {

    if (!file_exists($imgsrc)) return false;

    //get the file name
    $fileName = array_pop(explode("/",$imgsrc));
    $ext = fileExtension($fileName);

    //how it will be stored    
    $imgdest = "Pictures/Image".count($this->images).".".$ext;
    
    $this->images[] = array($imgsrc,$imgdest);
   
    //get size of image
    $arr = getImageSize($imgsrc);
	
	  //assume 72dpi
	  $width = floatvalue($arr[0]/72,2);
	  $height = floatvalue($arr[1]/72,2);
	
	  //setup our image container
	  $imgcont = $this->styles->createElement("draw:frame");
	  $imgcont->setAttribute("draw:style-name","PageHeaderTableImg");
	  $imgcont->setAttribute("svg:width",$width."in");
	  $imgcont->setAttribute("svg:height",$height."in");
	  $imgcont->setAttribute("text:anchor-type","paragraph");
	  $imgcont->setAttribute("svg:x","0.0in");
	  $imgcont->setAttribute("svg:y","0.0in");
	
	  $img = $this->styles->createElement("draw:image");
	  $img->setAttribute("xlink:href",$imgdest);
	  $img->setAttribute("xlink:type","simple"); 
	  $img->setAttribute("xlink:show","embed");
	  $img->setAttribute("xlink:actuate","onLoad");

    $imgcont->appendChild($img);
    	
	  return $imgcont;
	  
  }

  /********************************************************
    FUNCTION:	addPageHeader
    PURPOSE:	adds a header to the page
    INPUTS:		data -> array of data to display on 
                      right column
  ********************************************************/

  function addPageHeader($data,$logo=null)
  {

     $header = $this->styles->createElement("style:header");
     
     //create our table
     $table = $this->styles->createElement("table:table");
     $table->setAttribute("table:style-name","PageHeaderTable");
     
     //col1
     $col1 = $this->styles->createElement("table:table-column");
     $col1->setAttribute("table:style-name","PageHeaderTableCol1"); 

     //col 2
     $col2 = $this->styles->createElement("table:table-column");
     $col2->setAttribute("table:style-name","PageHeaderTableCol2"); 
  
     //put together
     $table->appendChild($col1);
     $table->appendChild($col2);

     //make our two cells and our row
     $row = $this->styles->createElement("table:table-row");
     $row->setAttribute("table:style-name","PageHeaderTableRow");

     //cell 1     
     $cell1 = $this->styles->createElement("table:table-cell");
     $cell1->setAttribute("office:value-type","string");
  
     //cell 2
     $cell2 = $this->styles->createElement("table:table-cell");
     $cell2->setAttribute("office:value-type","string");

     //add a logo if passed
     if ($logo) 
     {

       $p = $this->styles->createElement("text:p");
       $p->setAttribute("text:style-name","Table_20_Contents");

       $p->appendChild($this->createImage($logo));
       
       $cell1->appendChild($p);
       
     }
      
     //now for cell 2
     foreach ($data AS $line)
     {
 
       $p = $this->styles->createElement("text:p");  
       $p->appendChild($this->styles->createCDATASection($line));    
       $p->setAttribute("text:style-name","PageHeaderTableContent");  
                             
       $cell2->appendChild($p);       
     
     }

     //put it all together
     $row->appendChild($cell1);
     $row->appendChild($cell2);
     $table->appendChild($row);

     //spacer row because margin-bottom on the table doesn't work
     $row = $this->styles->createElement("table:table-row");
     $row->setAttribute("table:style-name","PageHeaderTableRowSpacer");
     $table->appendChild($row);
     
     $header->appendChild($table);   
     $this->masterpage->appendChild($header);
          
  }

  /********************************************************
    FUNCTION:	addPageFooter
    PURPOSE:	adds a footer to the page
    INPUTS:		line -> raw text to display
  ********************************************************/
  function addPageFooter($line)
  {

     $footer = $this->styles->createElement("style:footer");
     
     $p = $this->styles->createElement("text:p",$line);
     $p->setAttribute("text:style-name","PageFooterContent");    

     $footer->appendChild($p);   
     $this->masterpage->appendChild($footer);
          
  }

  /********************************************************
    FUNCTION:	output
    PURPOSE:	saves changes to xml back to file and
              creates new ODT
  ********************************************************/
  function output()
  {

    $dir = SITE_PATH."/modules/center/reports/editreport/runreport/odt";
 
    //clear out the temp folder
    emptyDir($this->tmpDir);

    //copy the template over  
    $cmd = "cp -R ".$this->templateDir."/* ".$this->tmpDir."/";
    `$cmd`;

    //copy all images
    for ($i=0;$i<count($this->images);$i++)
    {

      $img = &$this->images[$i];
      copy($img[0],$this->tmpDir."/".$img[1]);

      //add to the manifest
      $m = $this->manifest->createElement("manifest:file-entry");
      $m->setAttribute("manifest:media-type",return_file_mime($img[0]));
      $m->setAttribute("manifest:full-path",$img[1]);
      
      $this->me->appendChild($m);
      
    }

    //write our update xml to their respective files 
    $this->saveXMLFile(&$this->content,"content.xml");
    $this->saveXMLFile(&$this->styles,"styles.xml");
    $this->saveXMLFile(&$this->manifest,"META-INF/manifest.xml");
    
    //where we write the report
    $outputfile = TMP_DIR."/".USER_LOGIN."/output.odt";

    //remove old output file
    @unlink($outputfile);

    //zip it all up            
    $cmd = "cd ".$this->tmpDir."; zip -r ".$outputfile." *";
    `$cmd`;

    //reformat if necessary
    if ($this->outputFormat=="pdf")
    {
    
      $oo = new OPENOFFICE($outputfile);
      $outputfile = $oo->convert("pdf");
          
    }

    //return path to our new file
    return $outputfile;
    
  }

  /********************************************************
    FUNCTION:	saveXMLFile
    PURPOSE:	saves dom changes to actual xml file
    INPUTS:		ref -> dom reference w/ changes
              file -> name of file to write to
  ********************************************************/
  function saveXMLFile($ref,$file)
  {

    //content file
    $content = trim($ref->saveXML());

    /*
    //fit entities
    $XmlEntities = array(
      '&apos;' => '\'',
      );

    foreach ($XmlEntities AS $key=>$val)
    {
      $content = str_replace($val,$key,$content);
    }                          
    */
    file_put_contents($this->tmpDir."/".$file,$content);
  
  }

}          

