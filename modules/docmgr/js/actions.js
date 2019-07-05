
var ACTIONS = new DOCMGR_ACTIONS();

function DOCMGR_ACTIONS()
{

	this.workerId;

	/**
		moves the selected objects to the user's trash folder
		*/
	this.trash = function(id)
	{

		if (id) var ids = new Array(id);
		else var ids = BROWSE.getSelected();

		if (ids.length==0)
		{
			alert(_I18N_NO_OBJ_SELECTED);
			return false;
		}

    //setup the request
    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","docmgr_object_trash");
    p.add("object_id",ids);
    p.post(API_URL,"ACTIONS.writeTrash");

	};

	this.writeTrash = function(data)
	{
		if (data.error) alert(data.error);
		else BROWSE.refresh();		
	};

	this.emptyTrash = function()
	{

    if (confirm(_I18N_EMPTY_TRASH_CONFIRM))
    {
      //setup the xml
      var p = new PROTO();
      p.add("command","docmgr_object_emptytrash");
      p.post(API_URL,"ACTIONS.writeTrash");
    }

	};

	this.remove = function(id)
	{

		if (id) var ids = new Array(id);
		else var ids = BROWSE.getSelected();

		if (ids.length==0)
		{
			alert(_I18N_NO_OBJ_SELECTED);
			return false;
		}

    if (confirm(_I18N_OBJECT_DELETE_CONFIRM))
    {

	    updateSiteStatus(_I18N_PLEASEWAIT);

	    //setup the xml
	    var p = new PROTO();
	    p.add("command","docmgr_object_delete");
	    p.add("object_id",ids);
	    p.post(API_URL,"ACTIONS.writeTrash");

		}

	};

	this.addFilter = function()
	{
		RECORDS.hideBreadcrumbs();
  	RECORDS.openFilters("config/filters/search.xml",SEARCH.ajaxSearch);
	};

	/**
		loads the form for creating a new collection
		*/
	this.addCollection = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(500,240,_I18N_ADD_COLLECTION);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"ACTIONS.saveCollection()");
		MODAL.addForm("config/forms/objects/collection-add.xml","ACTIONS.emptyData","ACTIONS.addGenericForm");

	};

	this.addGenericForm = function(cont)
	{
		clearSiteStatus();
		MODAL.container.appendChild(cont);
		ge("name").focus();
	};

	this.emptyData = function()
	{
		return new Array();
	};

	this.saveCollection = function()
	{
		updateSiteStatus(_I18N_SAVING);
		var p = new PROTO();
		p.add("command","docmgr_collection_save");
		p.add("parent_id",BROWSE.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"ACTIONS.writeSaveCollection");
	};

	this.writeSaveCollection = function(data)
	{
		clearSiteStatus();
		MODAL.hide();
		BROWSE.refresh();		
	};

	/**
		loads the form for creating a new collection
		*/
	this.addURL = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(500,280,_I18N_ADD_WEBSITE);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"ACTIONS.saveURL()");
		MODAL.addForm("config/forms/objects/url-add.xml","","ACTIONS.addGenericForm");

	};

	this.saveURL = function()
	{
		updateSiteStatus(_I18N_SAVING);
		var p = new PROTO();
		p.add("command","docmgr_url_save");
		p.add("parent_id",BROWSE.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"ACTIONS.writeSaveCollection");
	};

	this.addDocument = function()
	{

		var url = "index.php?module=editor&editor=dmeditor&parentPath=" + BROWSE.path;
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

	this.addTextFile = function()
	{

		var url = "index.php?module=editor&editor=text&parentPath=" + BROWSE.path;
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

	this.checkout = function(e,id)
	{
		e.cancelBubble = true;
	
		//download like normal
		var p = new PROTO();
		p.add("command","docmgr_file_get");
		p.add("lock","1");
		p.add("object_id",id);
		p.redirect(API_URL);

	};

	this.email = function(id)
	{

		var arr = new Array();
	  var types = new Array("file","document");

		if (id)
		{
			arr.push(id);
		}
		else
		{
			for (var i=0;i<RECORDS.selected.length;i++)
			{
				var rec = RECORDS.selected[i];

				if (arraySearch(rec.getAttribute("object_type"),types)!=-1)
				{
					arr.push(RECORDS.selected[i].getAttribute("record_id"));
				}
			}		
		}

	  if (arr.length==0) 
	  {
	    alert(_I18N_OBJECT_EMAIL_SELECT_ERROR);
	    return false;
	  } 
		else 
		{
	    var url = "index.php?module=composeemail&docmgrAttachments=" + arr.join(",");
			var parms = centerParms(800,600,1) + ",resizable=yes,scrollbars=yes,menubar=yes";
			var ref = window.open(url,"_blank",parms);

			if (ref) ref.focus();
			else alert("Please disable your popup blocker");

	  }

	};

	this.viewLink = function(id)
	{

	  var types = new Array("file","document");

		if (id)
		{
			ACTIONS.workerId = id;
		}
		else
		{
			for (var i=0;i<RECORDS.selected.length;i++)
			{
				var rec = RECORDS.selected[i];

				if (arraySearch(rec.getAttribute("object_type"),types)!=-1)
				{
					ACTIONS.workerId = RECORDS.selected[i].getAttribute("record_id");
					break;
				}
			}		
		}


	  if (ACTIONS.workerId==null)
	  {
	    alert(_I18N_OBJECT_EMAIL_SELECT_ERROR);
	    return false;
	  } 
		else 
		{
			MODAL.open(450,150,_I18N_VIEW_LINK_OPT);
			MODAL.addToolbarButtonRight(_I18N_EMBED_LINK_EMAIL,"ACTIONS.embedLink()");
			MODAL.addForm("config/forms/objects/email-link.xml");
		}

	};

	this.generateLink = function(id)
	{

		var ex = ge("expire").value;
	
		if (ex!="0")
		{
	
			//setup the xml
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_object_createlink");
			p.add("object_id",ACTIONS.workerId);
			p.add("expire",ex);
			p.post(API_URL,"ACTIONS.writeGenerateLink");
		
		}	

	};

	this.writeGenerateLink = function(data)
	{
		clearSiteStatus();
	
		if (data.error) alert(data.error);
		else 
		{
			ge("object_link").value = data.object_link;
		}
	};

	this.embedLink = function()
	{
		var dest = ge("object_link").value;
		var link = "<a href=\"" + dest + "\">" + dest + "</a>";
		var url = "index.php?module=composeemail&editor_content=" + link;

		var parms = centerParms(800,600,1) + ",resizable=yes,scrollbars=yes,menubar=yes";
		var ref = window.open(url,"_blank",parms);

		if (ref) ref.focus();
		else alert("Please disable your popup blocker");

	};

	this.propLink = function(e,id)
	{
	
		var str = "";

		if (e)
		{

			var ref = getEventSrc(e).parentNode.parentNode.parentNode.parentNode;
			var link = SITE_URL + "index.php?module=docmgr&action=viewObject&objectId=" + id;

			str = "<div><a href=\"" + link + "\">Click To View \"" + ref.getAttribute("object_name") + "\"</div>";			

		}
		else
		{

			var arr = new Array();

			for (var i=0;i<RECORDS.selected.length;i++)
			{
				var rec = RECORDS.selected[i];

				if (arraySearch(rec.getAttribute("object_type"),types)!=-1)
				{
					arr.push(RECORDS.selected[i].getAttribute("record_id"));
				}
			}		

			if (arr.length==0) 
			{
				alert(_I18N_OBJECT_LINK_SELECT_ERROR);
				return false;
			} 
			else 
			{
	
				var str = "";
	
				for (var i=0;i<RECORDS.selected.length;i++)
				{
					var row = RECORDS.selected[i];
					var link = SITE_URL + "index.php?module=docmgr&action=viewObject&objectId=" + row.getAttribute("record_id");
	
					str += "<div><a href=\"" + link + "\">Click To View \"" + row.getAttribute("object_name") + "\"</div>";			
	
				}
	
			}

		}

		var url = "index.php?module=composeemail&editor_content=" + encodeURIComponent(str);

		var parms = centerParms(800,600,1) + ",resizable=yes,scrollbars=yes,menubar=yes";
		var ref = window.open(url,"_blank",parms);

		if (ref) ref.focus();
		else alert("Please disable your popup blocker");

	};

	this.createWorkflow = function(id)
	{

		var arr = new Array();

		if (id)
		{
			arr.push(id);
		}
		else
		{
			for (var i=0;i<RECORDS.selected.length;i++)
			{
				var rec = RECORDS.selected[i];
				arr.push(RECORDS.selected[i].getAttribute("record_id"));
			}		
		}
	
		if (arr.length==0) 
		{
			alert(_I18N_WORKFLOW_OBJECT_SELECT_ERROR)
			return false;
		} 
		else 
		{
	
			var objstr = arr.join(",");
	
			var url = "index.php?module=workflow&action=newWorkflow&objectId=" + objstr;
			location.href = url;
	
		}
	
	};

  this.move = function(id)
  {	
		if (id) ACTIONS.workerId = id;

    MINIB.open("move","ACTIONS.moveObject","","collection");
  };
    
  this.moveObject = function(arr)
  {
   
    var res = arr[0];
    curres = arr[0]; 
  
    updateSiteStatus(_I18N_PLEASEWAIT);
		var arr = new Array();

		if (ACTIONS.workerId)
		{
			arr.push(ACTIONS.workerId);
			ACTIONS.workerId = null;
		}
		else
		{
			for (var i=0;i<RECORDS.selected.length;i++)
			{
				arr.push(RECORDS.selected[i].getAttribute("record_id"));
			}		
		}

		var p = new PROTO();
		p.add("command","docmgr_object_move");
		p.add("source_parent_id",BROWSE.id);

		p.add("dest_parent_id",res.id);
		p.add("object_id",arr);
		p.post(API_URL,"ACTIONS.writeMove");
     
  }; 

	this.writeMove = function(data)
	{
		if (data.error) alert(data.error);
		else BROWSE.refresh();
	};

  /**
    removes an editing lock from the current file
    */
  this.lock = function(e,id)
  {

		e.cancelBubble = true;
   
    updateSiteStatus(_I18N_PLEASEWAIT);
  
    //unlock our document
    var p = new PROTO(); 
		p.add("command","docmgr_lock_set");
    p.add("object_id",id);
    p.post(API_URL,"ACTIONS.writeLock");

  };

  this.unlock = function(e,id,bitmask)
  {

		e.cancelBubble = true;
   
    updateSiteStatus(_I18N_PLEASEWAIT);
  
    //unlock our document
    var p = new PROTO(); 
  
    if (bitmask=="admin") p.add("command","docmgr_lock_clearall");
    else p.add("command","docmgr_lock_clear");
  
    p.add("object_id",id);
    p.post(API_URL,"ACTIONS.writeLock");

  };

  this.writeLock = function(data)
  {
    clearSiteStatus();
    if (data.error) alert(data.error);
    else 
    {    
      BROWSE.refresh();

			if (PROPERTIES.visible==true) PROPERTIES.load(null,PROPERTIES.obj.id);
    }

  }
	
}

	