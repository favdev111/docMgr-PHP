
var ADDR = new SITE_ADDR();

function SITE_ADDR()
{

	this.mode;
	this.editBtn;
	this.delBtn;
	this.id;
	this.timer;

	this.load = function()
	{

		ADDR.mode = "view";

    RECORDS.load(ge("container"),"listDetailView","select");
		RECORDS.hideDetail();

		ADDR.toolbar();
		ADDR.header();
		ADDR.search();

	};

	this.header = function()
	{
    RECORDS.openHeaderRow();
    RECORDS.addHeaderCell(_I18N_NAME,"nameCell");
    RECORDS.addHeaderCell(_I18N_EMAIL,"emailCell");
    RECORDS.addHeaderCell(_I18N_WORK_PHONE,"workPhoneCell");
    RECORDS.addHeaderCell(_I18N_MOBILE,"mobileCell");
    RECORDS.addHeaderCell(_I18N_HOME_PHONE,"homePhoneCell");
    RECORDS.closeHeaderRow();
	};

	this.toolbar = function()
	{

		TOOLBAR.open();
		TOOLBAR.addSearch("ADDR.ajaxSearch()",_I18N_SEARCH);
		
		TOOLBAR.addGroup();
		TOOLBAR.add(_I18N_NEW,"ADDR.edit()");
 		ADDR.editBtn = TOOLBAR.add(_I18N_EDIT,"ADDR.cycleMode()");
		ADDR.delBtn = TOOLBAR.add(_I18N_DELETE,"ADDR.bulkRemove()");
		
		TOOLBAR.close();

		ADDR.delBtn.style.display = "none";

	};

	this.ajaxSearch = function()
	{

    if (ADDR.timer) clearTimeout(ADDR.timer);

    updateSiteStatus(_I18N_PLEASEWAIT);
    ADDR.timer = setTimeout("ADDR.search()","200");

	};

	this.search = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

		var p = new PROTO();
		p.add("command","addressbook_contact_search");
		p.add("search_string",ge("siteToolbarSearch").value);
		p.post(API_URL,"ADDR.writeSearch");

	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		RECORDS.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			RECORDS.openRecordRow();
			RECORDS.addRecordCell(_I18N_NO_RECORDS_FOUND,"one");
			RECORDS.closeRecordRow();			
		}	
		else
		{

			for (var i=0;i<data.record.length;i++)
			{

				var rec = data.record[i];

				var row = RECORDS.openRecordRow("ADDR.edit('" + rec.id + "')");
				row.setAttribute("contact_id",rec.id);

				RECORDS.addRecordCell(rec.first_name + " " + rec.last_name,"nameCell");
				RECORDS.addRecordCell(rec.email,"emailCell");
				RECORDS.addRecordCell(rec.work_phone,"workPhoneCell");
				RECORDS.addRecordCell(rec.mobile,"mobileCell");
				RECORDS.addRecordCell(rec.home_phone,"homePhoneCell");

				RECORDS.closeRecordRow();			

			}

		}


		RECORDS.closeRecords();

	};

	this.edit = function(id)
	{

    if (id && ADDR.mode!="view") return false;

		RECORDS.showDetail();

		var height = parseInt(RECORDS.detailContainer.getSize().y);

    if (height < 500) 
    {
			//var listHeight = parseInt(RECORDS.recordList.getSize().y);
			var listHeight = ge("container").getSize().y - 410;
			RECORDS.recordList.style.height = listHeight + "px";
      RECORDS.setSizes();
    }

		ADDR.id = id;
		ADDR.loadSubHeader();

		updateSiteStatus(_I18N_PLEASEWAIT);
    RECORDS.addDetailForm("config/forms/addressbook/contact.xml","ADDR.contactData");

	};

  this.contactData = function()
  {

		if (!ADDR.id) return new Array();

    var p = new PROTO();
    p.add("command","addressbook_contact_get");
    p.add("contact_id",ADDR.id);
		p.setAsync(false);

    var data = p.post(API_URL);
		return data.record[0];

  };

  this.loadSubHeader = function()
  {

    var SUBTOOL = new SITE_TOOLBAR();

    SUBTOOL.open("left",RECORDS.detailHeader);
    SUBTOOL.addGroup();
    SUBTOOL.add(_I18N_SAVE,"ADDR.save()");
    if (ADDR.id) SUBTOOL.add(_I18N_DELETE,"ADDR.remove()");

		SUBTOOL.addGroup("right");
		SUBTOOL.add(_I18N_CLOSE,"ADDR.hideDetail()");

    SUBTOOL.close();

  };

  this.cycleMode = function()
  {
   
    if (ADDR.mode=="edit")
    {
      ADDR.mode = "view";
      RECORDS.setRowMode("select");
      ADDR.editBtn.innerHTML = " Edit ";
			ADDR.delBtn.style.display = "none";
    }
    else
    {   
      ADDR.mode = "edit";
      RECORDS.setRowMode("multiselect");
      ADDR.editBtn.innerHTML = "Cancel";
			ADDR.delBtn.style.display = "";
    }

  };

	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","addressbook_contact_save");
		p.addDOM(RECORDS.recordDetail);

		if (ADDR.id) p.add("contact_id",ADDR.id);

		p.post(API_URL,"ADDR.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			ADDR.search();
			ADDR.id = data.contact_id;
			ADDR.loadSubHeader();
		}

	};

	this.bulkRemove = function()
	{

		var arr = new Array();

		for (var i=0;i<RECORDS.selected.length;i++)
		{
			arr.push(RECORDS.selected[i].getAttribute("contact_id"));
		}

		if (arr.length==0)
		{
			alert(_I18N_NO_ITEMS_SELECTED);
			return false;
		}

    if (confirm(_I18N_CONTINUE_CONFIRM))
    {
      updateSiteStatus(_I18N_PLEASEWAIT);
      var p = new PROTO();
      p.add("command","addressbook_contact_delete");
      p.add("contact_id",arr);
      p.post(API_URL,"ADDR.writeRemove");
    }

	};

  this.remove = function()
  {

    if (confirm(_I18N_CONTINUE_CONFIRM))
    {
      updateSiteStatus(_I18N_PLEASEWAIT);
      var p = new PROTO();
      p.add("command","addressbook_contact_delete");
      p.add("contact_id",ADDR.id);
      p.post(API_URL,"ADDR.writeRemove");
    }

  };

  this.writeRemove = function(data)
  {
    clearSiteStatus();  

    if (data.error) alert(data.error);
    else
    {
      ADDR.id = "";
      ADDR.search();
			RECORDS.hideDetail();
    }       

  };

	this.hideDetail = function()
	{
		ADDR.id = null;
		RECORDS.hideDetail();
	};

}
