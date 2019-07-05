
var BOOKMARKS = new DOCMGR_BOOKMARKS();

function DOCMGR_BOOKMARKS()
{

	this.obj;
	this.id;

	this.add = function(e,id)
	{

    e.cancelBubble = true;

    //get our object info from the results
    for (var i=0;i<BROWSE.results.length;i++)
    {
      if (BROWSE.results[i].id==id)
      {
        BOOKMARKS.obj = BROWSE.results[i];
        break;
      }
    }

		MODAL.open(400,120,_I18N_CREATE_BOOKMARK);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"BOOKMARKS.saveNew()");
		MODAL.addForm("config/forms/objects/bookmark-new.xml","BOOKMARKS.addData","BOOKMARKS.addForm");

	};

	this.addData = function()
	{
		return new Array();
	}

	this.addForm = function(cont)
	{

		MODAL.add(cont);
		ge("name").value = BOOKMARKS.obj.name;

	};

	this.saveNew = function()
	{
		updateSiteStatus(_I18N_SAVING);
		var p = new PROTO();
		p.add("command","docmgr_bookmark_save");
		p.add("object_id",BOOKMARKS.obj.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"BOOKMARKS.writeSaveNew");
	};

	this.writeSaveNew = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			MODAL.hide();
			NAV.load();
		}

	}

	this.manage = function()
	{

		MODAL.open(500,300,_I18N_MANAGE_BOOKMARKS,"NAV.load()");

		MODAL.openRecordHeader();
		MODAL.addHeaderCell(_I18N_NAME,"nameCell");
		MODAL.addHeaderCell(_I18N_DEFAULT_BROWSE_PATH,"defaultPathCell");
		MODAL.addHeaderCell(_I18N_OPTIONS,"modalOptionsCell");
		MODAL.closeRecordHeader();

		BOOKMARKS.search();
	
	};

	this.search = function(data)
	{

		if (data && data.error) alert(data.error);
		else
		{

	  	updateSiteStatus(_I18N_PLEASEWAIT);

	    var p = new PROTO();
	    p.add("command","docmgr_bookmark_search");
	    p.post(API_URL,"BOOKMARKS.writeSearch");

		}

	};

	this.writeSearch = function(data)
	{
		
		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell(_I18N_NORESULTS_FOUND,"one");
			MODAL.closeRecordRow();
		}
		else
		{

			for (var i=0;i<data.record.length;i++)
			{

				MODAL.openRecordRow("BOOKMARKS.edit('" + data.record[i].id + "')");
				MODAL.addRecordCell(data.record[i].name,"nameCell");

				if (data.record[i].default_browse=="t") var def = _I18N_YES;
				else var def = _I18N_NO;

				MODAL.addRecordCell(def,"defaultPathCell");

	      var img = createImg(THEME_PATH + "/images/icons/delete.png");
        setClick(img,"BOOKMARKS.remove(event,'" + data.record[i].id + "')");
        MODAL.addRecordCell(img,"modalOptionsCell");

				MODAL.closeRecordRow();

			}

		}

		MODAL.closeRecords();

	}

	this.remove = function(e,id)
	{
		e.cancelBubble = true;

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
	    var p = new PROTO();
	    p.add("command","docmgr_bookmark_delete");
			p.add("bookmark_id",id);
	    p.post(API_URL,"BOOKMARKS.search");
		};

	};

	this.edit = function(id)
	{

		BOOKMARKS.id = id;

		MODAL.open(400,300,_I18N_EDIT_BOOKMARK);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"BOOKMARKS.save()");

		MODAL.clearNavbarRight();
		MODAL.addNavbarButtonRight(_I18N_BACK,"BOOKMARKS.manage()");

		MODAL.addForm("config/forms/objects/bookmark.xml","BOOKMARKS.editData");

	};

	this.editData = function()
	{
		var p = new PROTO();
		p.add("command","docmgr_bookmark_get");
		p.add("bookmark_id",BOOKMARKS.id);
		p.setAsync(false);

		var data = p.post(API_URL);

		if (data.error) alert(data.error);
		else return data.record[0];

	};

	this.save = function()
	{
		updateSiteStatus(_I18N_SAVING);
		var p = new PROTO();
		p.add("command","docmgr_bookmark_save");
		p.add("bookmark_id",BOOKMARKS.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"BOOKMARKS.writeSave");
	};

	this.writeSave = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
	};		

}
