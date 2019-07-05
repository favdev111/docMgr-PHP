
var UPLOAD = new OBJECT_UPLOAD();

function OBJECT_UPLOAD()
{

	this.uploadArray = new Array();
	this.uploadList = "";
	this.formref;
	this.btnref;
	this.detailref;
	this.isRunning;
	this.keywords;

	//pass the path we are dumping files to, and the 
	//function to run when an upload is complete
	this.load = function()
	{

		//fallback on a pre html5 uploader 
		if (!supportsFileAPI())
		{
			IEUPLOAD.load();
			return false;
		}

    //load our keywords
		updateSiteStatus(_I18N_PLEASEWAIT); 
   	var p = new PROTO();
    p.add("command","docmgr_keyword_search");
    p.add("object_id",BROWSE.id);
    p.post(API_URL,"UPLOAD.writeLoad");

	};

	this.writeLoad = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else if (data.record) UPLOAD.keywords = data.record;  
		else UPLOAD.keywords = new Array();

		//clear out our upload array
		UPLOAD.uploadArray = new Array();
		UPLOAD.isRunning = false;
	
		MODAL.open(640,480,_I18N_UPLOAD_FILES);

		//our button for sending the files to the server
		MODAL.addToolbarButtonRight(_I18N_UPLOAD_FILES,"UPLOAD.sendFiles()");

		//upload select and options
		var cont = ce("div","uploadSelectContainer");

		//our checkbox for showing additional info during upload
		var cell = ce("div","uploadOptions");
		UPLOAD.detailref = createCheckbox("enterInfo","1");
		setClick(UPLOAD.detailref,"UPLOAD.cycleAddInfo()");
		cell.appendChild(UPLOAD.detailref);
		cell.appendChild(ctnode(_I18N_ENTER_ADDINFO_FILE));
		cont.appendChild(cell);

		//are select files button
		UPLOAD.btnref = ce("div","uploadSelect");
		UPLOAD.btnref.appendChild(createImg(THEME_PATH + "/images/icons/select.png"));
		UPLOAD.btnref.appendChild(ctnode("Select Files"));
		cont.appendChild(UPLOAD.btnref);
		MODAL.add(cont);

		UPLOAD.setupForm();
	
		UPLOAD.uploadList = ce("div","","uploadListDiv");
		MODAL.add(UPLOAD.uploadList);	
	
	};

	this.setupForm = function()
	{

    //add the file form to the toolbar
    UPLOAD.formref = createForm("file","uploadForm");
    UPLOAD.formref.setAttribute("multiple","true");  
    UPLOAD.formref.style.position = "absolute";
    UPLOAD.formref.style.top = "14px";
    UPLOAD.formref.setAttribute("size","1");

    UPLOAD.formref.addEventListener("change",UPLOAD.addFiles,false);

    //if it's later than ie8,it most like supports the HTML 5 file api
    if (document.addEventListener)
    {
      setClick(UPLOAD.btnref,"UPLOAD.clicked()");
    }
    //provide a link to a modal uploader for older browsers
    else
    {   
      setClick(UPLOAD.btnref,"UPLOAD.legacyUpload()");
    }
     
    UPLOAD.formref.style.visibility = "hidden";

    //insert the form ref before the button
    UPLOAD.btnref.parentNode.insertBefore(UPLOAD.formref,UPLOAD.btnref);

	};	

  /** 
    called when a user clicks on the registered DOM element
    */
  this.clicked = function()
  {

    if (BROWSERMOBILE==true)
    {
      alert("File uploading does not work for mobile devices");
    }
    else
    {
      UPLOAD.formref.click();
    }

  };
	
	this.cycleAddInfo = function(e)
	{

		var arr = UPLOAD.uploadList.getElementsByClassName("uploadListSubRow");

		if (UPLOAD.detailref.checked==true) var mode = "";
		else var mode = "none";

		for (var i=0;i<arr.length;i++)
		{
			arr[i].style.display = mode;
		}
	
	};

	this.addFiles = function()
	{
	
		clearElement(UPLOAD.uploadList);

		var files = UPLOAD.formref.files;

		for (var i=0;i<files.length;i++) 
		{
	
			UPLOAD.uploadArray[i] = new Array();
			UPLOAD.uploadArray[i]["file"] = files[i];
			UPLOAD.uploadArray[i]["name"] = files[i].name;
			UPLOAD.uploadArray[i]["loaded"] = "";
			UPLOAD.uploadArray[i]["total"] = files[i].size;
	
			//add an entry to the dropdown list
			UPLOAD.uploadList.appendChild(UPLOAD.createUploadSection(i));
	
		}

	};
	
	this.createUploadSection = function(idx)
	{
	
		var fn = UPLOAD.uploadArray[idx]["name"];
		var total = UPLOAD.uploadArray[idx]["total"];
	
		var row = ce("div","uploadListRow");

		var cell = ce("div");
		var progdiv = ce("div","","progressContainer");
		
		var statdiv = ce("div","","progressStatus");
		progdiv.appendChild(statdiv);
		cell.appendChild(progdiv);
	
		var detail = ce("div","","progressText");
		var dtlsp = ce("span","","currentUploadTotal");
		dtlsp.appendChild(ctnode(size_format(total)));
		detail.appendChild(dtlsp);
		progdiv.appendChild(detail);
	
		//store a reference to the meter and text
		UPLOAD.uploadArray[idx]["upload_meter"] = statdiv;
		UPLOAD.uploadArray[idx]["upload_text"] = dtlsp;
		UPLOAD.uploadArray[idx]["container"] = row;
	
		row.appendChild(cell);
	
		var namediv = ce("div","uploadListName","",fn);
		row.appendChild(namediv);
	
		row.appendChild(createCleaner());
	
		var sub = ce("div","uploadListSubRow");

		var lc = ce("div","uploadListSubColumn");
		var ta = createTextarea("summary",_I18N_ENTER_FILE_DESC);
		setClass(ta,"enterDescriptionFaded");
		ta.setAttribute("onFocus","UPLOAD.clearDescription(event)");
		lc.appendChild(ta);
	
		var rc = ce("div","uploadListSubColumn","",UPLOAD.showKeywordOptions());
	
		//status bar
		sub.appendChild(lc);
		sub.appendChild(rc);
		sub.appendChild(createCleaner());

		if (UPLOAD.detailref.checked==true) sub.style.display = "";
		else sub.style.display = "none";

		row.appendChild(sub);
	
		return row;
	
	};

	this.clearDescription = function(e)
	{
	
		var ta = getEventSrc(e);
		if (ta.value==_I18N_ENTER_FILE_DESC)
		{
			ta.value = "";
			setClass(ta,"enterDescription");
		}
	
	};
	
	this.sendFiles = function()
	{

		if (UPLOAD.uploadArray.length==0)
		{
			alert(_I18N_FILE_UPLOAD_SELECT_ERROR);
			return false;
		}

		//bail if it's already running
		if (UPLOAD.isRunning==true) return false;

		UPLOAD.isRunning = true;
	
		updateSiteStatus(_I18N_PLEASEWAIT + "...");
	
		//help safari out
		closeKeepAlive();
	
		for (var i=0;i<UPLOAD.uploadArray.length;i++)
		{
			//run in a separate function to prevent PROTO overlapping
			UPLOAD.runSendFile(i);
		}
	
	};
	
	this.runSendFile = function(idx)
	{
	
		var file = UPLOAD.uploadArray[idx]["file"];
		var name = UPLOAD.uploadArray[idx]["name"];
		var cont = UPLOAD.uploadArray[idx]["container"];

		var p = new PROTO();
		p.add("command","docmgr_file_saveinputstream");
		p.add("name",name);
		p.add("parent_id",BROWSE.id);
		p.addDOM(cont);

		//check our summary value
		var data = p.getData();
		if (data.summary==_I18N_ENTER_FILE_DESC) data.summary = "";
		p.setData(data);
	
		p.upload(API_URL,file,"UPLOAD.uploadComplete","UPLOAD.uploadProgress");
	
	};
	
	
	this.uploadProgress = function(evt,file) 
	{
	
		if (evt.lengthComputable) 
		{
	
			for (var i=0;i<UPLOAD.uploadArray.length;i++)
			{
	
				if (UPLOAD.uploadArray[i]["file"]==file) 
				{
	
					var loaded = evt.loaded;
					var total = UPLOAD.uploadArray[i]["total"];
	
					var percentComplete = Math.round(loaded/total * 100);
	
					UPLOAD.uploadArray[i]["loaded"] = loaded;
					UPLOAD.uploadArray[i]["upload_meter"].style.width = percentComplete + "%";
					UPLOAD.uploadArray[i]["upload_text"].innerHTML = _I18N_UPLOADED + " " + size_format(loaded) + " " + _I18N_OF + " " + size_format(total);
	
				}
	
			}
	
		}
	
	};
	
	this.uploadComplete = function(data,file) 
	{

		//update loaded for the passed file.  I have to do this because firefox doesn't always show the
		//last progress indicator that shows a matched loaded w/ total
		for (var i=0;i<UPLOAD.uploadArray.length;i++)
		{
	
			if (UPLOAD.uploadArray[i]["file"]==file)
			{
				UPLOAD.uploadArray[i]["loaded"] = UPLOAD.uploadArray[i]["total"];
				UPLOAD.uploadArray[i]["upload_text"].innerHTML = "Uploaded " + size_format(UPLOAD.uploadArray[i]["loaded"]) + " of " + size_format(UPLOAD.uploadArray[i]["total"]);
				UPLOAD.uploadArray[i]["upload_meter"].style.width = "100%";
				break;
			}
	
		}
	
		/* This event is raised when the server send back a response */
		if (data.error) 
		{
			clearSiteStatus();
			alert(data.error);
		}
		else
		{
	
			var done = true;
	
			//make sure all uploads are finished
			for (var i=0;i<UPLOAD.uploadArray.length;i++)
			{
	
				if (UPLOAD.uploadArray[i]["loaded"]!=UPLOAD.uploadArray[i]["total"])
				{
					done = false;
					break;
				}
	
			}
	
			if (done==true)
			{
				UPLOAD.isRunning = false;

				clearSiteStatus();
				MODAL.hide();
				BROWSE.refresh();

			}
	
		}
	
	};

	/***************************************************************
	  FUNCTION: showKeywordOptions
	  PURPOSE:  show keyword data if there is any
	***************************************************************/
	this.showKeywordOptions = function() 
	{

	  var keycont = ce("div","keywordList");

	  if (UPLOAD.keywords.length > 0)
	  {
	   
	    //keycont.appendChild(ce("div","formHeader","",_I18N_KEYWORDS));
	  
	    var tbl = createTable("uploadKeywordTable");
	    var tbd = ce("tbody");
	    tbl.appendChild(tbd); 
	    keycont.appendChild(tbl);
	
	    for (var i=0;i<UPLOAD.keywords.length;i++)
	    {
	     
	      var curkey = UPLOAD.keywords[i];
	  
	      //the row and the options
	      var row = ce("tr","uploadKeywordRow");
	      row.appendChild(createHidden("keyword_id[]",curkey.id));
	  
	      //display the name
	      row.appendChild(ce("td","uploadKeywordLabel","",curkey.name));
	  
	      //display the options
	      if (curkey.type=="select")
	      {
	  
	        var tb = createSelect("keyword_value[]");
	        if (curkey.option)
	        {
	  
	          for (var c=0;c<curkey.option.length;c++)
	          {
	            tb[c] = new Option(curkey.option[c].name,curkey.option[c].id);
	          }
	  
	        }
	  
	      } 
				else 
				{
	        //search string
	        var tb = createTextbox("keyword_value[]");
	  
	      }
	
	      tb.setAttribute("keyword_id",curkey.id);
	      tb.setAttribute("required",curkey.required);
	          
	      row.appendChild(ce("td","uploadKeywordEntry","",tb));
	  
	      tbd.appendChild(row);
	  
	    }
	     
	  }  
	     
	  return keycont;
	
	};
	 
		
}
