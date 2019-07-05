
function TEXTEDIT()
{

	this.isDirty;

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
			this.getContent();
		}
	
	};
	
	/*******************************************************************
		FUNCTION: loadEditor
		PURPOSE:	load the actual editor.  
		INPUTS:		curval -> html we'll populate the editor with
	*******************************************************************/
	this.loadEditor = function(curval) 
	{

		var ta = createTextarea("editor_content");
		setChange(ta,"EDITOR.editor.markDirty(true)");
		
		if (curval) ta.value = curval;
		else ta.value = "";

		content.appendChild(ta);
	
		clearSiteStatus();
	
		EDITOR.setFrameSize();
		ta.focus();

		this.markDirty(false);
	
	};

	this.markDirty = function(dirty)
	{
		this.isDirty = dirty;
	};

	this.cleanup = function()
	{
	};
	
	/*******************************************************************
		FUNCTION: getContent
		PURPOSE:	get the html for the specified file.  The file is
							called by docmgr and convert to html.  that html is
							returned to here
	*******************************************************************/
	this.getContent = function() {
	
		objtype = "file";
		updateSiteStatus(_I18N_LOADING);	
		var p = new PROTO();
		p.add("command","docmgr_object_getcontent");
		p.add("object_id",EDITOR.objectId);
		p.add("lock","1");
		p.post(API_URL,createMethodReference(this,"writeContent"));
	
	};
	
	this.writeContent = function(data)
	{
	
		clearSiteStatus();
	
		EDITOR.remotesaved = 1;
		this.markDirty(false);

		this.loadEditor(data.content);

	};
	
	/*********************************************
	  FUNCTION: checkState
	  PURPOSE:  checks if file needs to be saved
	*********************************************/
	this.checkState = function() 
	{

		if (this.isDirty==true)
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
		p.add("command","docmgr_file_save");
		p.add("parent_path",EDITOR.obj.parent);			//pass the path too so we know where to put it (parent folder)
		p.add("name",EDITOR.obj.name);
		if (EDITOR.objectId) p.add("object_id",EDITOR.objectId);
		p.add("lock","1");
		p.add("revision_notes",EDITOR.revision_notes);
		p.add("editor_content",ge("editor_content").value);
	
		p.post(API_URL,"EDITOR.writeServerSave");
	
	};
	

  this.mbOpenObject = function(res)
  {
  	this.getContent();
  };

  this.mbSaveObject = function(res)
  {

    if (res.savetype!="docmgr")
    {
      //make sure the file extension is in the name
      if (EDITOR.obj.name.indexOf("." + res.savetype)==-1) EDITOR.obj.name += "." + res.savetype;
    }

    updateSiteStatus(_I18N_SAVING);
    this.saveServer();

  };
	
	/*******************************************************************
	  FUNCTION: setFrameSize
	  PURPOSE:  sets the fckeditor to fill the window when resized
	  INPUTS:   none
	*******************************************************************/
	this.setFrameSize = function()
	{
	 
	  var ref = ge("editor_content");
	  var base = 50;
	
	  ref.style.height = (getWinHeight() - base) + "px";
	   
	};

  /*******************************************************************
    FUNCTION: print
    PURPOSE:  prints the document to a pdf file
    INPUTS:   none
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
    else 
    {    
      window.open(data.url);
    }

  }

}
	