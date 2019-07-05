
var VIEW = new OBJECT_VIEW();

function OBJECT_VIEW()
{

	/**
		hands off viewing to appropriate method
		*/
	this.load = function(obj)
	{

    //now handle accordingly
    if (obj.object_type=="document")
    {
			this.document(obj);
    }
    else if (obj.object_type=="url")
    {
			this.url(obj);
    }
    else if (obj.object_type=="search")
    {
			this.search(obj);
    }
		else
		{
			this.file(obj);
		}

	};

	/**
		opens a DocMGR document in the inline editor
		*/
	this.document = function(obj)
	{

    var url = "index.php?module=editor&objectId=" + obj.id;
    var parms = centerParms(800,600,1) + ",resizable=1";  
    var popupref = window.open(url,"_editor",parms);

    //if no popup, they probably have a popup blocker
    if (!popupref)
    {
      alert(_I18N_POPUP_BLOCKER_ERROR);
    }
    else
    {   
      popupref.focus();
    }

	};

	/**
		views or downloads object file
		*/
	this.file = function(obj)
	{

		//try to open in our editor if possible
		if (VIEW.getEditor(obj))
		{

		    var url = "index.php?module=editor&objectId=" + obj.id;
		    var parms = centerParms(800,600,1) + ",resizable=1";  
		    var popupref = window.open(url,"_editor",parms);
		
		    //if no popup, they probably have a popup blocker
		    if (!popupref)
		    {
		      alert(_I18N_POPUP_BLOCKER_ERROR);
		    }
		    else
		    {   
		      popupref.focus();
		    }

		}
		//prep for download
		else
		{

      var p = new PROTO();
      p.add("command","docmgr_file_get");
      p.add("object_id",obj.id);

			if (obj.inline==1)
			{
				p.redirect(API_URL,1);
			}
			else
			{
				p.redirect(API_URL);
			}

		}

	};

	/**
		determines if the object can be opened by dmeditor or text
		*/
	this.getEditor = function(obj)
	{
	 
	  var ext = fileExtension(obj.name);
	  var editor;
	    
	  //obj.object_type,ext
	  if (obj.object_type=="document") 
		{
			editor = "dmeditor";
	  }
		else
	  {   
	   
			//call up our xml file to figure out what we are allowed to open this with   
	    var p = new PROTO();
	    p.setAsync(false);  
	    p.setProtocol("XML");
	    var extensions = p.post("config/extensions.xml");
	   
	    var ow = new Array();
	
	    for (var i=0;i<extensions.object.length;i++)
	    {
	
	      var e = extensions.object[i];
	
	      //we have a match, get the handler
	      if (e.extension==ext)
	      {
	        if (isData(e.open_with)) editor = e.open_with.toString().split(",");
	        break;
	      }
	
	    }
	
	  }
	
	  return editor;
	
	};

	/**
		open saved url object in new window
		*/
	this.url = function(obj)
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

		//get the url to load
		var p = new PROTO();
		p.setAsync(false);
		p.add("command","docmgr_url_get");
		p.add("object_id",obj.id);
		p.post(API_URL,"VIEW.writeURL");

	};

	this.writeURL = function(data)
	{
		clearSiteStatus();

    var parms = centerParms(800,600,1) + ",scrollbars=1,menubar=1,resizable=1,titlebar=1,toolbar=1,status=1,location=1";
    var ref = window.open(data.url,"_blank",parms);

    if (!ref) 
		{
    	alert(_I18N_POPUP_BLOCKER_ERROR);
    } 
		else 
		{
    	ref.focus();
    }

	};

}


