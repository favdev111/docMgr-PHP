
var KEYWORDS = new DOCMGR_KEYWORDS();

function DOCMGR_KEYWORDS()
{

	this.id;
	this.results;
	this.data;
	this.optionRecord;
	this.optionId;

	this.load = function()
	{

		RECORDS.load(ge("container"),"listDetailView","active");
    RECORDS.setRowMode('select');
    KEYWORDS.toolbar();
		KEYWORDS.header();
    KEYWORDS.search(); 

  };

  this.toolbar = function()
  {
    TOOLBAR.open();
		TOOLBAR.addGroup();
		TOOLBAR.add(_I18N_NEW,"KEYWORDS.edit()");
    TOOLBAR.close();
  };

	this.header = function()
	{
    //header row
		RECORDS.openHeaderRow();
    RECORDS.addHeaderCell(_I18N_NAME,"keywordNameCell");
    RECORDS.addHeaderCell(_I18N_TYPE,"keywordTypeCell");
    RECORDS.addHeaderCell(_I18N_REQUIRED,"keywordRequiredCell");
		RECORDS.closeHeaderRow();
	};

	this.search = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_keyword_search");
		p.post(API_URL,"KEYWORDS.writeSearch");
	};
	
	this.writeSearch = function(data)
	{

		clearSiteStatus();

		RECORDS.openRecords();
	
		if (data.error) alert(data.error);
		else if (!data.record)
		{
			KEYWORDS.results = new Array();
	    RECORDS.setRowMode('active');
			RECORDS.openRecordRow();
			RECORDS.addRecordCell(_I18N_NORESULTS_FOUND,"one");
			RECORDS.closeRecordRow();
		}
		else
		{

			KEYWORDS.results = data.record;
	    RECORDS.setRowMode('select');

	    for (var i=0;i<data.record.length;i++)
	    {
	
				var keyword = data.record[i];

				var row = RECORDS.openRecordRow("KEYWORDS.edit('" + keyword.id + "')");
	
				if (keyword.required=="t") var required = _I18N_YES;
				else var required = _I18N_NO;

				RECORDS.addRecordCell(keyword.name,"keywordNameCell");
				RECORDS.addRecordCell(keyword.type,"keywordTypeCell");
				RECORDS.addRecordCell(required,"keywordRequiredCell");

				RECORDS.closeRecordRow();
	
	    }
	
	  }

		RECORDS.closeRecords();	
	
	};

	this.keywordToolbar = function()
	{

    var SUBTOOL = new SITE_TOOLBAR();

    SUBTOOL.open("left",RECORDS.detailHeader);

    SUBTOOL.addGroup();
    SUBTOOL.add(_I18N_SAVE,"KEYWORDS.save()");

    if (KEYWORDS.id) 
		{
			SUBTOOL.add(_I18N_DELETE,"KEYWORDS.remove()");
			SUBTOOL.addGroup();
			SUBTOOL.add(_I18N_COLLECTIONS,"KEYWORDS.loadParents()");

			if (KEYWORDS.data.type=="select" || ge("type").value=="select") SUBTOOL.add(_I18N_SELECT_OPTIONS,"KEYWORDS.loadOptions()");

		}

		SUBTOOL.close();

	};

	this.edit = function(id)
	{
		KEYWORDS.id = id;
		RECORDS.addDetailForm("config/forms/config/keyword.xml","KEYWORDS.keywordData");
	};

	this.keywordData = function()
	{
		if (!KEYWORDS.id) 
		{
			KEYWORDS.data = new Array();
		}
		else
		{
			var p = new PROTO();
			p.add("command","config_keyword_get");
			p.add("keyword_id",KEYWORDS.id);
			p.setAsync(false);
			var data = p.post(API_URL);

			KEYWORDS.data = data.record[0];

		}

		KEYWORDS.keywordToolbar();

		return KEYWORDS.data;

	};

	this.save = function()
	{

		updateSiteStatus(_I18N_SAVING);
 
		var p = new PROTO();
		p.add("command","config_keyword_save");
		p.addDOM(RECORDS.recordDetail);

		if (KEYWORDS.id) p.add("keyword_id",KEYWORDS.id);
		
		p.post(API_URL,"KEYWORDS.writeSave");

	};

	this.writeSave = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else 
		{
			KEYWORDS.id = data.keyword_id;
			KEYWORDS.keywordToolbar();
			KEYWORDS.search();
		}
	};

	this.remove = function()
	{

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
 
			var p = new PROTO();
			p.add("command","config_keyword_delete");
			p.add("keyword_id",KEYWORDS.id);
			p.post(API_URL,"KEYWORDS.writeRemove");
		}

	};

	this.writeRemove = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else KEYWORDS.search();
	};

	/**
		hands off viewing to appropriate method
		*/
	this.loadParents = function()
	{

		MODAL.open(640,480,_I18N_KEYWORD_LIMIT_COLLECTION);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"KEYWORDS.saveParents()");

		//convert object parents to a string
		var valarr = new Array();

		//it comes comma delimited
		if (KEYWORDS.data.parent_id) valarr = KEYWORDS.data.parent_id;

		//div for the tree
		var tcell = ce("div","parentsTree");
		MODAL.add(tcell);

		//create the form tree
		var opt = new Array();
		opt.container = tcell;
		opt.mode = "checkbox";
		opt.ceiling = "0";
		opt.ceilingname = ROOT_NAME;
		opt.curval = valarr;
		var t = new TREEFORM();
		t.load(opt);

	};

	this.saveParents = function()
	{

		//check our form		
		var arr = MODAL.container.getElementsByTagName("input");
		var parr = new Array();

		for (var i=0;i<arr.length;i++) 
		{
				if (arr[i].checked==true) parr.push(arr[i].value);
		}

		updateSiteStatus(_I18N_SAVING);
 
		var p = new PROTO();
		p.add("command","config_keyword_saveparent");
		p.add("keyword_id",KEYWORDS.id);
		p.add("parent_id",parr);
		p.post(API_URL,"KEYWORDS.writeSaveParents");

	};

	this.writeSaveParents = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			MODAL.hide();
			KEYWORDS.edit(KEYWORDS.id);
		}

	};

	/**
		hands off viewing to appropriate method
		*/
	this.loadOptions = function()
	{

		MODAL.open(640,480,_I18N_SELECT_OPTIONS);
		MODAL.addToolbarButtonRight("Add Option","KEYWORDS.viewOption()");

		MODAL.openRecordHeader();
		MODAL.addHeaderCell(_I18N_NAME,"three");
		MODAL.addHeaderCell("Sort Order","three");
		MODAL.addHeaderCell(_I18N_OPTIONS,"three");
		MODAL.closeRecordHeader();

		KEYWORDS.searchOptions();

	};

	this.searchOptions = function()
	{

		if (!KEYWORDS.id)
		{
			alert(_I18N_KEYWORD_SAVE_FIRST_ERROR);
			return false;
		}

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_keyword_searchoptions");
		p.add("keyword_id",KEYWORDS.id);
		p.post(API_URL,"KEYWORDS.writeSearchOptions");
	};

	this.writeSearchOptions = function(data)
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
				setClick(img,"KEYWORDS.removeOption(event,'" + opt.id + "')");

				MODAL.openRecordRow("KEYWORDS.viewOption('" + opt.id + "')");
				MODAL.addRecordCell(opt.name,"three");
				MODAL.addRecordCell(opt.sort_order,"three");
				MODAL.addRecordCell(img,"three");
				MODAL.closeRecordRow();

			}

		}

		MODAL.closeRecords();

	};

	/**
		hands off viewing to appropriate method
		*/
	this.viewOption = function(id)
	{

		if (id) KEYWORDS.optionId = id;
		else KEYWORDS.optionId = "";

		MODAL.open(640,480,"Option");
		MODAL.clearNavbarRight();
		MODAL.addNavbarButtonRight(_I18N_BACK,"KEYWORDS.loadOptions()");
		MODAL.addForm("config/forms/config/keyword-option.xml","KEYWORDS.optionData");

		MODAL.addToolbarButtonRight(_I18N_SAVE,"KEYWORDS.saveOption()");

	};

	this.optionData = function()
	{

		if (!KEYWORDS.optionId) return new Array();

		var p = new PROTO();
		p.add("command","config_keyword_getoption");
		p.add("option_id",KEYWORDS.optionId);
		p.setAsync(false);
		var data = p.post(API_URL);

		return data.record[0];

	};

	this.saveOption = function()
	{

		updateSiteStatus(_I18N_SAVING);
 
		var p = new PROTO();
		p.add("command","config_keyword_saveoption");
		p.add("keyword_id",KEYWORDS.id);
		p.addDOM(MODAL.container);

		if (KEYWORDS.optionId) p.add("option_id",KEYWORDS.optionId);
		
		p.post(API_URL,"KEYWORDS.writeSaveOption");

	};

	this.writeSaveOption = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else KEYWORDS.optionId = data.option_id;
	};

	this.removeOption = function(e,id)
	{

		e.cancelBubble = true;

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
 
			var p = new PROTO();
			p.add("command","config_keyword_deleteoption");
			p.add("option_id",id);
			p.post(API_URL,"KEYWORDS.writeRemoveOption");
		}

	};

	this.writeRemoveOption = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else KEYWORDS.searchOptions();
	};

}




