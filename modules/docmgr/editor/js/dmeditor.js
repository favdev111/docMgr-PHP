
function DMEDITOR()
{

	this.ckeditor;

	/*******************************************************************
		FUNCTION: loadPage
		PURPOSE:	initializes and loads the editor
	*******************************************************************/
	this.load = function()
	{

		if (!EDITOR.objectId) 
		{
			clearSiteStatus();	
			EDITOR.remotesaved = 0;
			this.loadEditor();
		} 
		else 
		{
			//snag the document converted to html for us
			if (EDITOR.obj.object_type=="file") this.getFile();
			else this.getDocument();
		}
	
	};

	
	/*******************************************************************
		FUNCTION: loadEditor
		PURPOSE:	load the actual editor.  
		INPUTS:		curval -> html we'll populate the editor with
	*******************************************************************/
	this.loadEditor = function(curval)
	{

		if (this.ckeditor) this.cleanup();

    var ta = createTextarea("editor_content");
    content.appendChild(ta);

		if (EDITOR.objectId) var ip = EDITOR.obj.object_path + "/.object" + EDITOR.objectId + "_storage";
		else var ip = "/Users/" + USER_LOGIN + "/.temp_storage";

		//default to english
		var lang = "en";	

		//try to pull a code out of settings
		var arr = JSON.decode(sessionStorage.accountSettings);
		if (arr && arr.language) lang = arr.language;

	  //create a new one
	  this.ckeditor = CKEDITOR.replace('editor_content',
									{
										image_mode: 'docmgr',
										image_path: ip,
										toolbar: 'Docmgr',
										fullPage: true,
										language: lang,
										on: 
										{
											pluginsLoaded: function (ev) { addDialogs(this); },	
											instanceReady: function (ev) { EDITOR.setFrameSize();}
										}
	
									});

	    if (curval) 
			{

				//couldn't load.  Probably a mobile browser that doesn't support contenteditable
				if (!this.ckeditor)
				{

					ta.value = curval;

					if (confirm(_I18N_EDITOR_LOAD_FAIL_PROMPT))
					{

						var display = ce("div");
						display.innerHTML = curval;
						
						ta.style.display = "none";

						content.appendChild(display);

					}
			
				} else this.ckeditor.setData(curval);
			
			}

			clearSiteStatus();

	};

	this.cleanup = function()
	{

		if (this.ckeditor)
		{
			this.ckeditor.destroy();
			this.ckeditor = null;
		}

		clearElement(content);

	};
	
	/*******************************************************************
		FUNCTION: getDocumentContent
		PURPOSE:	get the html for the specified document
	*******************************************************************/
	this.getDocument = function()
	{
		updateSiteStatus(_I18N_LOADING);
		var p = new PROTO();
		p.add("command","docmgr_document_get");
		p.add("object_id",EDITOR.objectId);
		p.add("lock","1");
		p.post(API_URL,createMethodReference(this,"writeDocumentContent"));
	};
	
	/*******************************************************************
		FUNCTION: writeDocumentContent
		PURPOSE:	response handler for getDocumentContent.  Populates
							the editor with the html returned from docmgr
		INPUTS:		resp -> ajax response
	*******************************************************************/
	this.writeDocumentContent = function(data) 
	{

		clearSiteStatus();
	
		//set filesaved so we automatically overwrite
		EDITOR.remotesaved = 1;
	
		if (!data.content) data.content = "";
	
		this.loadEditor(data.content);
		this.markDirty(false);
	
	};
	
	/*******************************************************************
		FUNCTION: getFileContent
		PURPOSE:	get the html for the specified file.  The file is
							called by docmgr and convert to html.  that html is
							returned to here
	*******************************************************************/
	this.getFile = function()
	{
		updateSiteStatus(_I18N_LOADING);
		var p = new PROTO();
		p.add("command","docmgr_file_getashtml");
		p.add("object_id",EDITOR.objectId);
		p.add("lock","1");
		p.post(API_URL,createMethodReference(this,"writeDocumentContent"));
	};
	
	/*********************************************
	  FUNCTION: checkState
	  PURPOSE:  checks if file needs to be saved
	*********************************************/
	this.checkState = function()
	{

		if (!this.ckeditor) return false;

		if (this.ckeditor.checkDirty())
		{
			return _I18N_DOCUMENT_ISDIRTY;
		}
	
	};
	
	
	/*******************************************************************
		FUNCTION: runServerSave
		PURPOSE:	posts the document content to docmgr for saving, 
							along with the path to save it to
		INPUTS:		none
	*******************************************************************/
	this.saveServer = function() 
	{
	
		updateSiteStatus(_I18N_SAVING);
	
		//setup xml
		var p = new PROTO();
	
		if (EDITOR.obj.savetype=="docmgr")
		{
			p.add("command","docmgr_document_save");
		}
		else
		{
			p.add("command","docmgr_file_savefromhtml");
		}
	
		p.add("parent_path",EDITOR.obj.parent);			//pass the path too so we know where to put it (parent folder)
		p.add("name",EDITOR.obj.name);
		if (EDITOR.objectId) p.add("object_id",EDITOR.objectId);
		p.add("lock","1");
		p.add("revision_notes",EDITOR.revision_notes);
		p.add("editor_content",this.ckeditor.getData());
		p.post(API_URL,"EDITOR.writeServerSave");

	};
	

	this.markDirty = function(dirty)
  {
    if (dirty==false) this.ckeditor.resetDirty();
  };  

	this.mbOpenObject = function(res)
	{

			updateSiteStatus(_I18N_LOADING);
	
	  	//if it is a document, get the html from docmgr
	  	if (EDITOR.obj.object_type=="document") 
			{
				this.getDocument();
	  	} 
			else if (EDITOR.obj.object_type=="file") 
			{
				this.getFile();
	  	} 	

	};

	this.mbSaveObject = function(res)
	{

		if (EDITOR.obj.savetype!="docmgr")
		{
			//make sure the file extension is in the name
			if (EDITOR.obj.name.indexOf("." + res.savetype)==-1) EDITOR.obj.name += "." + res.savetype;
		}

		updateSiteStatus(_I18N_SAVING);
		this.saveServer();

	};
	
	/*******************************************************************
		FUNCTION: print
		PURPOSE:	prints the document to a pdf file
		INPUTS:		none
	*******************************************************************/
	this.print = function()
	{
      updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_object_converthtml");
			p.add("editor_content",this.ckeditor.getData());
			p.add("to","pdf");
			p.post(API_URL,"EDITOR.editor.writePrintHandler");
	};

	this.writePrintHandler = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else if (!data.url) alert("Error converting document");
		else window.open(data.url);

	}
	
	/*******************************************************************
		FUNCTION: setFrameSize
		PURPOSE:	sets the fthis.ckeditor to fill the window when resized
		INPUTS:		none
	*******************************************************************/
	this.setFrameSize = function()
	{
	
		if (!this.ckeditor) return false;

		var base = 189;
		var width = "100%";
		var height = (getWinHeight() - base);

		this.ckeditor.resize(width,height,true);
	
	};
	 
	
	this.insertMergeField = function(val) 
	{
	
	  val += " ";
	
		this.ckeditor.insertHtml(val);
	
	};
	
}
