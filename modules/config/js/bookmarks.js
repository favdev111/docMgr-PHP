
var BOOKMARKS = new DOCMGR_BOOKMARKS();

function DOCMGR_BOOKMARKS()
{

	this.accountId;
	this.accountName;
	this.timer;
	this.id;
	this.record;

	this.load = function()
	{

		BOOKMARKS.mode = "view";

    RECORDS.load(ge("container"),"listView","active");

		BOOKMARKS.toolbar();
		BOOKMARKS.header();
		BOOKMARKS.search();

	};

	this.header = function()
	{
    RECORDS.openHeaderRow();
    RECORDS.addHeaderCell(_I18N_LOGIN,"accountLoginCell");
    RECORDS.addHeaderCell(_I18N_NAME,"accountNameCell");
    RECORDS.addHeaderCell(_I18N_EMAIL,"accountEmailCell");
    RECORDS.closeHeaderRow();
	};

	this.toolbar = function()
	{

		TOOLBAR.open();
		TOOLBAR.addSearch("BOOKMARKS.ajaxSearch()",_I18N_SEARCH);
		TOOLBAR.close();

	};

	this.ajaxSearch = function()
	{

    if (BOOKMARKS.timer) clearTimeout(BOOKMARKS.timer);

    updateSiteStatus(_I18N_PLEASEWAIT);
    BOOKMARKS.timer = setTimeout("BOOKMARKS.search()","200");

	};

	this.search = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

		var p = new PROTO();
		p.add("command","config_account_search");
		p.add("search_string",ge("siteToolbarSearch").value);
		p.post(API_URL,"BOOKMARKS.writeSearch");

	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		RECORDS.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			RECORDS.openRecordRow();
			RECORDS.addRecordCell("No records found","one");
			RECORDS.closeRecordRow();			
		}	
		else
		{

			for (var i=0;i<data.record.length;i++)
			{

				var rec = data.record[i];

				var row = RECORDS.openRecordRow("BOOKMARKS.select(event)");
				row.setAttribute("account_id",rec.id);
				row.setAttribute("account_name",rec.full_name);

				RECORDS.addRecordCell(rec.login,"accountLoginCell");
				RECORDS.addRecordCell(rec.full_name,"accountNameCell");
				RECORDS.addRecordCell(rec.email,"accountEmailCell");

				RECORDS.closeRecordRow();			

			}

		}


		RECORDS.closeRecords();

	};

	this.select = function(e)
	{

		var ref = getEventSrc(e);
		if (!ref.hasAttribute("account_id")) ref = ref.parentNode;

		BOOKMARKS.accountId = ref.getAttribute("account_id");
		BOOKMARKS.accountName = ref.getAttribute("account_name");
		BOOKMARKS.loadBookmarks();

	};

	this.loadBookmarks = function()
	{

		MODAL.open(640,480,_I18N_BOOKMARKS + " - " + BOOKMARKS.accountName);
		MODAL.addToolbarButtonLeft(_I18N_CREATE_BOOKMARK,"BOOKMARKS.edit()");

		MODAL.openRecordHeader();
		MODAL.addHeaderCell(_I18N_NAME,"bookmarkNameCell");
		MODAL.addHeaderCell(_I18N_DEFAULT_BROWSE_PATH,"bookmarkDefaultPathCell");
		MODAL.addHeaderCell(_I18N_OPTIONS,"bookmarkOptionCell");
		MODAL.closeRecordHeader();

    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","docmgr_bookmark_search");
    p.add("account_id",BOOKMARKS.accountId);
    p.post(API_URL,"BOOKMARKS.writeLoadBookmarks");

	};


  this.writeLoadBookmarks = function(data)
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
        var opt = data.record[i];

        var img = createImg(THEME_PATH + "/images/icons/delete.png");
        setClick(img,"BOOKMARKS.remove(event,'" + opt.id + "')");

				if (opt.default_browse=="t") var def = _I18N_YES;
				else var def = _I18N_NO;

        MODAL.openRecordRow("BOOKMARKS.edit('" + opt.id + "')");
        MODAL.addRecordCell(opt.name,"bookmarkNameCell");
				MODAL.addRecordCell(def,"bookmarkDefaultPathCell");
        MODAL.addRecordCell(img,"bookmarkOptionCell");
        MODAL.closeRecordRow();

      }

    }

    MODAL.closeRecords();

  };


	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_bookmark_save");
		p.add("account_id",BOOKMARKS.accountId);
		p.addDOM(MODAL.container);
	
		if (BOOKMARKS.id) p.add("bookmark_id",BOOKMARKS.id);

		p.post(API_URL,"BOOKMARKS.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();
		if (data.error) alert(data.error);

	};

  this.remove = function(e,id)
  {

		e.cancelBubble = true;

    if (confirm(_I18N_CONTINUE_CONFIRM))
    {
      updateSiteStatus(_I18N_PLEASEWAIT);
      var p = new PROTO();
      p.add("command","docmgr_bookmark_delete");
      p.add("bookmark_id",id);
      p.post(API_URL,"BOOKMARKS.writeRemove");
    }

  };

  this.writeRemove = function(data)
  {
    clearSiteStatus();  

    if (data.error) alert(data.error);
    else BOOKMARKS.loadBookmarks();

  };


	/**
		permissions editting
		*/
	this.edit = function(id)
	{

		BOOKMARKS.id = id;

		updateSiteStatus(_I18N_PLEASEWAIT);

		MODAL.open(640,480,_I18N_EDIT_BOOKMARK);

		MODAL.clearNavbarRight();
		MODAL.addNavbarButtonRight(_I18N_BACK,"BOOKMARKS.loadBookmarks()");

		MODAL.addToolbarButtonRight(_I18N_SAVE,"BOOKMARKS.save()");
		MODAL.addForm("config/forms/config/bookmark.xml","BOOKMARKS.bookmarkData");

	}

	this.bookmarkData = function()
	{

		if (!BOOKMARKS.id)
		{
			BOOKMARKS.record = new Array();
			return BOOKMARKS.record;
		}

		var p = new PROTO();
		p.add("command","docmgr_bookmark_get");
		p.add("account_id",BOOKMARKS.accountId);
		p.add("bookmark_id",BOOKMARKS.id);
		p.setAsync(false);

		var data = p.post(API_URL);
		BOOKMARKS.record = data.record[0];

		return data.record[0];

	};

	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_bookmark_save");
		p.add("account_id",BOOKMARKS.accountId);
		p.addDOM(MODAL.container);

		if (BOOKMARKS.id) p.add("bookmark_id",BOOKMARKS.id);

		p.post(API_URL,"BOOKMARKS.writeSave");

	};

	this.writeSave= function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
	};

}


/**
	loads the actual tree
	*/
EFORM.objectTree = function(curform)
{

	//convert object parents to a string
	var valarr = new Array();

	if (EFORM.data && isData(EFORM.data[curform.data])) valarr = new Array(EFORM.data[curform.data]);

  //use a multiform class if set
	if (curform.display && curform.display=="multiform") var cn = "eformInputMultiTitleCell";
	else var cn = "eformInputTitleCell";
  
	//load the main cell and the header
	var mydiv = ce("div","eformInputRow",curform.name + "FormDiv");
	var header = ce("div",cn,curform.name + "FormDivTitle",curform.title);
  
	//container for all the boxes
	var cont = ce("div","eformInputFormCell")

	//div for the tree
	var tcell = ce("div","parentsTree");
	cont.appendChild(tcell);

	//create the form tree
	var opt = new Array();
	opt.container = tcell;
	opt.mode = "radio";
	opt.ceiling = "0";
	opt.ceilingname = ROOT_NAME;
	opt.curval = valarr;
	opt.formname = "object_id";

	var t = new TREEFORM();
	t.load(opt);

	mydiv.appendChild(header);
	mydiv.appendChild(cont);
	mydiv.appendChild(createCleaner());

	return mydiv;

}


