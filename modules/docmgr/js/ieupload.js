
var IEUPLOAD = new APP_IEUPLOAD();

function APP_IEUPLOAD()
{

	this.keywords;
	this.timer;

  //pass the path we are dumping files to, and the
  //function to run when an upload is complete
  this.load = function()
  {

    //load our logs
    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","docmgr_keyword_search");  
    p.add("object_id",BROWSE.id);
    p.post(API_URL,"IEUPLOAD.writeLoad");

  };

  this.writeLoad = function(data)
  {

    clearSiteStatus();

    if (data.error) alert(data.error);
    else if (data.record) IEUPLOAD.keywords = data.record;
    else IEUPLOAD.keywords = new Array();

		MODAL.open(640,480,_I18N_UPLOAD_FILES);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"IEUPLOAD.upload()");

		var fileform = createForm("file","uploadfile");
		var descform = createTextarea("summary");

		MODAL.add(EFORM.template(_I18N_SELECT_FILE,fileform));
		MODAL.add(EFORM.template(_I18N_DESCRIPTION,descform));
	
		IEUPLOAD.showKeywords();

	};

	/**
		shows all available keywords for the current collection
		*/
	this.showKeywords = function()
	{

		var keycont = ce("div","keywordContainer");

		if (IEUPLOAD.keywords.length > 0)
		{
	
			for (var i=0;i<IEUPLOAD.keywords.length;i++)
			{
		
				var curkey = IEUPLOAD.keywords[i];
				var keyform;
		
				//display the options
				if (curkey.type=="select") 
				{
		
					keyform = createSelect("keyword_value[]");

					if (curkey.option)
					{
						for (var c=0;c<curkey.option.length;c++)
						{
							keyform[c] = new Option(curkey.option[c].name,curkey.option[c].id);
						}
					}
				} 
				else 
				{
					keyform = createTextbox("keyword_value[]");
				}

				var row = EFORM.template(curkey.name,keyform);
				row.appendChild(createHidden("keyword_id[]",curkey.id));

				MODAL.add(row);
		
			}
		
		}
	
		return keycont;
	
	};

	/**
		*/
	this.upload = function()
	{
	
		if (!IEUPLOAD.checkRequiredKeywords())
		{
			alert(_I18N_REQUIRED_KEYWORD_ERROR);
			return false;
		}
	
		updateSiteStatus(_I18N_PLEASEWAIT);
	
		window.frames["uploadframe"].document.open();
		window.frames["uploadframe"].document.write("");
		window.frames["uploadframe"].document.close();
	
		var fileval = ge("uploadfile").value;
	
		//how is the directory structure
		if (fileval.indexOf("/")!=-1) var arr = fileval.split("/");
		else var arr = fileval.split("\\");
	
		var fn = arr.pop();
	
		//this will happen n several stages.  First, we send the object info to the server, then when send the file itself
		//setup the xml
		var p = new PROTO();
		p.add("command","docmgr_file_save");
		p.add("ie_hack","1");
		p.add("parent_path",BROWSE.path);
		p.add("name",fn);
		p.add("summary",ge("summary").value);
		if (ge("keywordContainer")) p.addDOM(ge("keywordContainer"));
	
		var d = new Date();
		var url = API_URL + "?" + p.encodeData() + "&timestamp=" + d.getTime();

		//copy our file form into the actual form container so it can be submitted
		clearElement(document.pageForm);
		var ufarr = MODAL.container.getElementsByTagName("input");
		document.pageForm.appendChild(ufarr[0]);

		MODAL.hide();
	
		document.pageForm.action = url;
		document.pageForm.target = "uploadframe";	
		document.pageForm.submit();
	
		IEUPLOAD.timer = setInterval("IEUPLOAD.checkUpload()","500");
	
	}
	
	/**
		*/
	this.checkRequiredKeywords = function()
	{
	
		var ret = true;
		var tbl = ge("keywordContainer");
	
		if (tbl)
		{
	
		var arr = tbl.getElementsByTagName("input");
		
			for (var i=0;i<arr.length;i++)
			{
		
				var kid = arr[i].getAttribute("keyword_id");
				var req = arr[i].getAttribute("required");
				
				if (kid && req && req=="t" && arr[i].value.length=="0")
				{
					ret = false;
					break;
				}
		
			}
		
		}
	
		return ret;
	
	};

	/**
		*/
	this.checkUpload1 = function()
	{

		var doc = window.frames["uploadframe"].document;
		var success = doc.getElementsByTagName("success");
		var err = doc.getElementsByTagName("error");

		if (success.length > 0 || error.length > 0)
		{
			clearInterval(IEUPLOAD.timer);
			clearSiteStatus();

			if (error.length > 0) alert(error[0].length);
			else BROWSE.refresh();

		}

	};

 /**
    */
  this.checkUpload = function()
  {
  
    var tmp = window.frames["uploadframe"].document;
    var success = tmp.getElementsByTagName("success");
    var err = tmp.getElementsByTagName("error");

    if (err.length > 0 || success.length > 0)
    {

      clearSiteStatus();
      clearInterval(IEUPLOAD.timer);
  
      //if success just refresh, otherwise show the error
      if (success.length > 0)
      {
        BROWSE.refresh();
      }
      else
      {
        alert(tmp.body.innerText);
      }

      window.frames["uploadframe"].document.open();
      window.frames["uploadframe"].document.write("");
      window.frames["uploadframe"].document.close();

    }
  
  };

	

}


	
