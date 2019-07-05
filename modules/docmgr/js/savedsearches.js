
var SAVEDSEARCHES = new DOCMGR_SAVEDSEARCHES();

function DOCMGR_SAVEDSEARCHES()
{

	this.id;

  this.run = function(id)
  {

		if (id) SAVEDSEARCHES.id = id;
   
    updateSiteStatus(_I18N_PLEASEWAIT);

    SEARCH.clear();

		//store we are doing this
		BROWSE.searchMode = "savedsearch";

    //now do the saved searches
    var p = new PROTO();
    p.add("command","docmgr_search_run");
    p.add("search_id",SAVEDSEARCHES.id);
    p.post(API_URL,"BROWSE.writeBrowse");

  };

	this.manage = function()
	{

		MODAL.open(400,300,_I18N_MANAGE_SAVED_SEARCHES,"NAV.load()");
		SAVEDSEARCHES.search();
	
	};

	this.search = function(data)
	{

		if (data && data.error) alert(data.error);
		else
		{

	  	updateSiteStatus(_I18N_PLEASEWAIT);

	    var p = new PROTO();
	    p.add("command","docmgr_search_search");
	    p.post(API_URL,"SAVEDSEARCHES.writeSearch");

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

				MODAL.openRecordRow("SAVEDSEARCHES.edit('" + data.record[i].id + "')");
				MODAL.addRecordCell(data.record[i].name,"nameCell");

	      var img = createImg(THEME_PATH + "/images/icons/delete.png");
        setClick(img,"SAVEDSEARCHES.remove(event,'" + data.record[i].id + "')");
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
	    p.add("command","docmgr_search_delete");
			p.add("search_id",id);
	    p.post(API_URL,"SAVEDSEARCHES.search");
		};

	};

	this.edit = function(id)
	{

		SAVEDSEARCHES.id = id;

		MODAL.open(400,300,_I18N_EDIT_SAVED_SEARCH);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"SAVEDSEARCHES.save()");

		MODAL.clearNavbarRight();
		MODAL.addNavbarButtonRight(_I18N_BACK,"SAVEDSEARCHES.manage()");

		MODAL.addForm("config/forms/objects/saved-search.xml","SAVEDSEARCHES.editData");

	};

	this.editData = function()
	{
		var p = new PROTO();
		p.add("command","docmgr_search_get");
		p.add("search_id",SAVEDSEARCHES.id);
		p.setAsync(false);

		var data = p.post(API_URL);

		if (data.error) alert(data.error);
		else return data.record[0];

	};

	this.buildSearch = function()
	{

    var p = new PROTO();
    p.add("command","docmgr_query_search");
    p.add("search_string",ge("siteToolbarSearch").value);
    p.addDOM(RECORDS.filterContainer);

    //limit our search to the current collection if desired
    if (SEARCH.searchRange=="collection") p.add("object_id",BROWSE.id);

		return p.encode(p.getData());

	};

	this.save = function()
	{

		updateSiteStatus(_I18N_SAVING);

		var p = new PROTO();
		p.add("command","docmgr_search_save");

		//updating or creating
		if (SAVEDSEARCHES.id) p.add("search_id",SAVEDSEARCHES.id);
		else
		{
			p.add("params",SAVEDSEARCHES.buildSearch());
		}

		p.addDOM(MODAL.container);
		p.post(API_URL,"SAVEDSEARCHES.writeSave");

	};

	this.writeSave = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else if (!SAVEDSEARCHES.id) MODAL.hide();

	};		

	this.addNew = function()
	{

		SAVEDSEARCHES.id = ""

		MODAL.open(400,300,_I18N_SAVE_CURRENT_SEARCH,"NAV.load()");
		MODAL.addToolbarButtonRight(_I18N_SAVE,"SAVEDSEARCHES.save()");

		MODAL.addForm("config/forms/objects/saved-search.xml","SAVEDSEARCHES.addData");

	};

	this.addData = function()
	{
		return new Array();
	};

}
