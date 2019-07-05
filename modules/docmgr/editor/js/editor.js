
var EDITOR = new DOCMGR_EDITOR();

window.onresize = EDITOR.setFrameSize;
window.onbeforeunload = EDITOR.checkState;
window.onunload = EDITOR.refreshParent;

function DOCMGR_EDITOR()
{

	this.objectId;
	this.obj;	
	this.editor;
	this.type;
	this.remotesaved;
	this.revision_notes;
	this.readonly;
	this.readonlyCell;
	this.mbResult;

	this.load = function()
	{
		EDITOR.objectId = ge("objectId").value;
		EDITOR.editor = null;
		EDITOR.type = ge("editor").value;
		EDITOR.remotesaved = 0;

		//now go ahead and load the editor
		if (EDITOR.objectId) EDITOR.getObjectInfo();
		else EDITOR.loadEditor();

	};

	/**
		fetch object data based on the id passed to us when loaded
		*/
	this.getObjectInfo = function()
	{
		var p = new PROTO();
		p.add("command","docmgr_object_getinfo");
		p.add("object_id",EDITOR.objectId);
		p.post(API_URL,"EDITOR.writeObjectInfo");

	};

	/**
		store our object info and load the editor
		*/
	this.writeObjectInfo = function(data)
	{

		if (data.error) alert(data.error);
		else if (data.record) 
		{
			EDITOR.obj = data.record[0];
			EDITOR.setType();
			EDITOR.loadEditor();
		}

	};

	this.loadToolbar = function()
	{

		PULLDOWN.openHandler = null;
		PULLDOWN.closeHandler = null;
		MODAL.openHandler = null;
		MODAL.closeHandler = null;

		TOOLBAR.open();
		
		TOOLBAR.addGroup();

		TOOLBAR.add(_I18N_NEW);
		TOOLBAR.addSubmenu(_I18N_NEW_DOCMGR_DOCUMENT,"EDITOR.create('docmgr')");
		TOOLBAR.addSubmenu(_I18N_NEW_TEXT_DOCUMENT,"EDITOR.create('text')");
		TOOLBAR.add(_I18N_OPEN,"EDITOR.openServer()");
	
		TOOLBAR.add(_I18N_SAVE);
		TOOLBAR.addSubmenu(_I18N_SAVE_TO_DOCMGR,"EDITOR.saveServer()");

		//only if we have an existing object
		if (EDITOR.obj) TOOLBAR.addSubmenu(_I18N_SAVE_TO_DOCMGR_NOTES,"EDITOR.saveWithNotes()");

		TOOLBAR.addSubmenu(_I18N_SAVE_COPY_DOCMGR,"EDITOR.saveServerCopy()");

		TOOLBAR.add(_I18N_PRINT,"EDITOR.editor.print()");

		TOOLBAR.addGroup("right");

		//show our readonly status
		if (EDITOR.obj)
		{
		  if (EDITOR.obj.locked=="t") TOOLBAR.add(_I18N_READONLY_LOCKED);
		  else if (EDITOR.obj.bitmask_text=="view") TOOLBAR.add(_I18N_READONLY);
		}

		//show a close link
		TOOLBAR.add(_I18N_CLOSE,"self.close()");
	
		TOOLBAR.close();

	}

	this.setType = function()
	{

		var neweditor = "";

		//type,ext
		if (EDITOR.obj && EDITOR.obj.object_type!="document") 
		{

			var ow = new Array();
			var ext = fileExtension(EDITOR.obj.name);

			for (var i=0;i<extensions.object.length;i++)
			{
	
				var e = extensions.object[i];
	
				//we have a match, get the handler
				if (e.extension==ext)
				{
					if (isData(e.open_with)) neweditor = e.open_with.toString().split(",");
					break;
				}
	
			}
	
		}

		//default to docmgr editor
		if (!neweditor) neweditor = "dmeditor";
	
		EDITOR.type = neweditor;

	};

	this.loadEditor = function()
	{

		//load the toolbar here, it will change depending on our editor backend
		EDITOR.loadToolbar();

		if (EDITOR.editor) EDITOR.editor.cleanup();

		EDITOR.editor = null;

		clearElement(content);

		if (EDITOR.type=="text")
		{
			EDITOR.editor = new TEXTEDIT();						//textarea box
		}
		else 
		{
			EDITOR.editor = new DMEDITOR();				//ckeditor
		}
			
		EDITOR.editor.load();

	};


	this.create = function(type)
	{
		EDITOR.remotesaved = 0;
		EDITOR.objectId = null;
		EDITOR.obj = null;

		EDITOR.type = type;
		EDITOR.loadEditor();

	};
	
	this.openLocal = function()
	{
		EDITOR.editor.openLocal();
	};
	
	this.saveLocal = function()
	{
		EDITOR.editor.saveLocal();
	};
	
	this.saveWithNotes = function()
	{
		MODAL.open("500","200",_I18N_REVISION_NOTES);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"EDITOR.storeNotes()");
		MODAL.addForm("config/forms/objects/editor-revision-notes.xml");
	};
	
	this.storeNotes = function()
	{
		EDITOR.revision_notes = ge("revision_notes").value;
		MODAL.hide();
		EDITOR.saveServer();
	};
	
	this.saveServer = function()
	{
	
	  //if we've already saved this file once, just save it directly
	  if (EDITOR.remotesaved == 1) 
	  {
	     
	    EDITOR.editor.saveServer();
	  
			//reset our notes
			EDITOR.revision_notes = "";
	
	  //spawn minib to pick where to save the file
	  } 
		else 
	  {
	    EDITOR.objectId = "";
			MINIB.open("save","EDITOR.mbSaveObject",ge("parentPath").value,"document,file,collection");

			//if it's in text mode, fix the default file type
			if (EDITOR.type=="text")
			{
				 endReq('ge("fileType").value = "txt"');
			}

		}
	
	};

	this.saveServerCopy = function()
	{
		EDITOR.remotesaved = 0;
		EDITOR.saveServer();
	};
	
	/*******************************************************************
	  FUNCTION: writeServerSave
	  PURPOSE:  response handler for runServerSave
	  INPUTS:   resp -> ajax response
	*******************************************************************/
	this.writeServerSave = function(data) 
	{

	  clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{

	    //show it's been saved
	    EDITOR.editor.markDirty(false);
	  
	    //save the objectid
	    EDITOR.remotesaved = 1;   
	    EDITOR.objectId = data.object_id;
			EDITOR.obj.id = data.object_id;

		}

	};
	
	
	this.mbSaveObject = function(arr)
	{

		var res = arr[0];

		//update an existing object if passed back an id to update
		if (res.id)
		{
			EDITOR.objectId = res.id;
		}

		//setup a dummy entry to work with
		EDITOR.obj = new Array();
		EDITOR.obj.object_type = res.type;
		EDITOR.obj.name = res.name;
		EDITOR.obj.parent = res.parent;
		EDITOR.obj.savetype = res.savetype;

		EDITOR.editor.mbSaveObject(EDITOR.obj);

	};

	this.dialogSelectImage = function(url)
	{
		EDITOR.editor.dialogSelectImage(url);
	};
	
	/***********************************
		object opening methods
	***********************************/

	this.openServer = function()
	{
	  EDITOR.remotesaved = 0;
		MINIB.open("open","EDITOR.mbOpenObject",ge("parentPath").value,"document,file,collection");
	};
	
	this.mbOpenObject = function(arr)
	{
	
		var res = arr[0];
		curres = arr[0];
	
		updateSiteStatus(_I18N_PLEASEWAIT);

		//update the form values
		EDITOR.objectId = res.id;

		//setup a dummy entry to work with
		EDITOR.obj = new Array();
		EDITOR.obj.id = res.id;
		EDITOR.obj.object_type = res.type;
		EDITOR.obj.name = res.name;

		var oldType = EDITOR.type;

		EDITOR.setType();

		//setup the new editor for the new type if we are in the wrong one
		if (oldType!=EDITOR.type) 
		{
			EDITOR.loadEditor();
		}
		else 
		{
			EDITOR.editor.mbOpenObject(EDITOR.obj);
		}
	
	};
	
	this.pageLayout = function()
	{
		EDITOR.editor.pageLayout();
	}
	
	this.setFrameSize = function()
	{
		EDITOR.editor.setFrameSize();
	}
	
	this.checkState = function()
	{
		return EDITOR.editor.checkState();
	}
	
	this.refreshParent = function()
	{
	
		if (EDITOR.editor) EDITOR.editor.cleanup();
	
	  //if we have an object, make sure it's unlocked
	  if (EDITOR.objectId)
	  {
	
	    //unlock our document
	    var p = new PROTO(); 
	    p.add("command","docmgr_lock_clear");
	    p.add("object_id",EDITOR.objectId);
	    p.setAsync(false);
	    p.post(API_URL);
	
	  }

		if (window.opener)
		{
	
		  if (window.opener.closeWindow)
		  {
		    window.opener.closeWindow(EDITOR.obj);
		  } 
		  else
		  {   
		    var url = window.opener.location.href;
		    window.opener.location.href = url;
		  }
	
		}
	
	};

}

	