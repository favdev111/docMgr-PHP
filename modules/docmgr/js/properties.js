
var PROPERTIES = new OBJECT_PROPERTIES();

function OBJECT_PROPERTIES()
{

	this.obj;			//for storing all data we retrieved from this object during the search
	this.dirty;
	this.visible;
	this.limited = false;

	/**
		hands off viewing to appropriate method
		*/
	this.load = function(e,id,limited)
	{

		if (e) e.cancelBubble = true;
		if (limited) PROPERTIES.limited = true;

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_object_getinfo");
		p.add("object_id",id);
		p.post(API_URL,"PROPERTIES.writeLoad");

	}

	this.writeLoad = function(data)
	{
		if (data.error) alert(data.error);
		else
		{

			PROPERTIES.obj = data.record[0];

			if (PROPERTIES.obj.locked=="t") 
			{
				PROPERTIES.obj.lock_status = "Locked";
				PROPERTIES.obj.lock_owner = PROPERTIES.obj.lock_owner_name.join(",");
			}
			else 
			{
				PROPERTIES.obj.lock_status = "Unlocked";
				PROPERTIES.obj.lock_owner = "";
			}
	
			//show the detail pane
			RECORDS.showDetail();
			PROPERTIES.toolbar();
			PROPERTIES.properties();
			PROPERTIES.visible = true;

		}

	};

	this.loadFromData = function(obj)
	{
		PROPERTIES.obj = obj;

		//show the detail pane
		RECORDS.showDetail();
		PROPERTIES.toolbar();
		PROPERTIES.properties();
	};

 	/**
    loads the toolbar for the detail record we are working on
    */
  this.toolbar = function()
  {
   
    var SUBTOOL = new SITE_TOOLBAR();

    SUBTOOL.open("left",RECORDS.detailHeader);
    SUBTOOL.addGroup();
    SUBTOOL.add(_I18N_SAVE,"PROPERTIES.save()");

		//shortcut for closing propertie detail view
    if (PROPERTIES.obj.object_type=="file") SUBTOOL.add(_I18N_PREVIEW,"PROPERTIES.preview()");

    SUBTOOL.addGroup();

		//if it's a URL object, give us the opportunity to edit the URL
		if (PROPERTIES.obj.object_type=="url")
		{
			SUBTOOL.add(_I18N_URL,"PROPERTIES.editURL()");
		}

		//admin users only
		if (PROPERTIES.obj.bitmask_text=="admin" && !PROPERTIES.limited)
		{
			SUBTOOL.add(_I18N_KEYWORDS,"KEYWORDS.load()");
	    SUBTOOL.add(_I18N_HISTORY,"HISTORY.load()");
	    SUBTOOL.add(_I18N_PERMISSIONS,"PERMISSIONS.load()");
	    SUBTOOL.add(_I18N_LOCATION,"PARENTS.load()");
		}

    SUBTOOL.add(_I18N_DISCUSSION,"DISCUSSION.load()");
    SUBTOOL.add(_I18N_LOGS,"LOGS.load()");

		if (PROPERTIES.obj.object_type=="collection")
		{
			SUBTOOL.add(_I18N_OPTIONS,"OPTIONS.load()");
		}

    SUBTOOL.addGroup("right");
    SUBTOOL.add(_I18N_CLOSE,"PROPERTIES.close()");


    SUBTOOL.close();

	};

	this.close = function()
	{
		PROPERTIES.obj = new Array();
		RECORDS.hideDetail();
		PROPERTIES.visible = false;

    if (PROPERTIES.dirty==true)
    {
			BROWSE.refresh();
			PROPERTIES.dirty = false;
    }

	};	

	this.preview = function()
	{
		var ts = new Date().getTime();
		var url = SITE_URL + "app/showpreview.php?sessionId=" + SESSION_ID + "&objectId=" + PROPERTIES.obj.id + "&objDir=" + PROPERTIES.obj.object_directory + "&timestamp=" + ts;
		var parms = centerParms(800,600,1) + ",resizable=1,scrollbars=1";
		window.open(url,"_preview",parms);
	};

	/**
		loads the properties form for the current object
		*/
	this.properties = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);

		if (PROPERTIES.obj.object_type=="document" || PROPERTIES.obj.object_type=="file") var file = "config/forms/objects/properties-lock.xml";
		else var file = "config/forms/objects/properties-basic.xml";

		RECORDS.addDetailForm(file,"PROPERTIES.propertyData");

	};

	/**
		passes our data to the eform methods
		*/
	this.propertyData = function()
	{
		return PROPERTIES.obj;
	};

	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_object_save");
		p.add("object_id",PROPERTIES.obj.id);
		p.add("name",ge("name").value);
		p.add("summary",ge("summary").value);
		p.post(API_URL,"PROPERTIES.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			BROWSE.refresh();
			PROPERTIES.dirty = false;
		}

	};

	/**
		edit a URL object's field
		*/
	this.editURL = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(400,105,_I18N_EDIT_URL);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"PROPERTIES.saveURL()");
		MODAL.addForm("config/forms/objects/url-edit.xml","PROPERTIES.urlData");
	};

	this.urlData = function()
	{
		var p = new PROTO();
		p.add("command","docmgr_url_get");
		p.add("object_id",PROPERTIES.obj.id);
		p.setAsync(false);
		return p.post(API_URL);
	};

	this.saveURL = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_url_save");
		p.add("object_id",PROPERTIES.obj.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"PROPERTIES.writeSaveURL");
	};

	this.writeSaveURL = function()
	{
		clearSiteStatus();
		MODAL.hide();
	};
}


