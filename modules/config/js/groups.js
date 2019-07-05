var GROUPS = new SITE_GROUPS();

function SITE_GROUPS()
{

	this.mode;
	this.editBtn;
	this.group;
	this.timer;

	this.load = function()
	{

		GROUPS.mode = "view";

    RECORDS.load(ge("container"),"listDetailView","select");

		GROUPS.loadToolbar();
		GROUPS.loadHeader();
		GROUPS.search();

	};

	this.loadHeader = function()
	{
    RECORDS.openHeaderRow();
    RECORDS.addHeaderCell(_I18N_NAME,"groupNameCell");
    RECORDS.addHeaderCell(_I18N_MEMBERS,"groupMembersCell");
    RECORDS.closeHeaderRow();
	};

	this.loadToolbar = function()
	{

		TOOLBAR.open();
		TOOLBAR.addSearch("GROUPS.ajaxSearch()",_I18N_SEARCH);
		
		TOOLBAR.addGroup();
		TOOLBAR.add(_I18N_NEW,"GROUPS.create()");

		TOOLBAR.close();

	};

	this.ajaxSearch = function()
	{

    if (GROUPS.timer) clearTimeout(GROUPS.timer);

    updateSiteStatus(_I18N_PLEASEWAIT);
    GROUPS.timer = setTimeout("GROUPS.search()","200");

	};

	this.search = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

		var p = new PROTO();
		p.add("command","config_group_search");
		p.add("search_string",ge("siteToolbarSearch").value);
		p.post(API_URL,"GROUPS.writeSearch");

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

				var row = RECORDS.openRecordRow("GROUPS.select('" + rec.id + "')");
				row.setAttribute("group_id",rec.id);

				RECORDS.addRecordCell(rec.name,"groupNameCell");
				RECORDS.addRecordCell(rec.member_count,"groupMembersCell");

				RECORDS.closeRecordRow();			

			}

		}


		RECORDS.closeRecords();

	};

	this.select = function(id)
	{

    if (GROUPS.mode!="view") return false;

		GROUPS.group = new Array();
  
    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","config_group_get");
    p.add("group_id",id);
    p.post(API_URL,"GROUPS.writeGroup");
	//console.log(GROUPS.writeGroup);
	};


  this.writeGroup = function(data)
  {

    if (data.error) alert(data.error);
    else
    {   
      GROUPS.group = data.record[0];
      GROUPS.loadSubHeader();
    
			updateSiteStatus(_I18N_PLEASEWAIT);
      RECORDS.addDetailForm("config/forms/config/group-edit.xml","GROUPS.getGroupData");
    }

  };

   
  this.getGroupData = function()
  {
    return GROUPS.group;
  };

  this.loadSubHeader = function()
  {

    var SUBTOOL = new SITE_TOOLBAR();

    SUBTOOL.open("left",RECORDS.detailHeader);
    SUBTOOL.addGroup("20px");
    SUBTOOL.add(_I18N_SAVE,"GROUPS.save()");
    SUBTOOL.add(_I18N_DELETE,"GROUPS.remove()");

		SUBTOOL.addGroup();
		SUBTOOL.add(_I18N_PERMISSIONS,"GROUPS.permissions()");
		SUBTOOL.add(_I18N_MEMBERS,"GROUPS.members()");

    SUBTOOL.close();

  };

	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_group_save");
		p.add("group_id",GROUPS.group.id);
		p.addDOM(RECORDS.recordDetail);
		p.post(API_URL,"GROUPS.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();
		if (data.error) alert(data.error);

	};

	this.remove = function()
	{

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","config_group_remove");
			p.add("group_id",GROUPS.group.id);
			p.post(API_URL,"GROUPS.writeRemove");
		}

	};

	this.writeRemove = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{
			GROUPS.group = new Array();
			GROUPS.search();
			RECORDS.clearDetail();
		}

	};

	this.create = function()
	{

		MODAL.open(400,110,_I18N_NEW_GROUP);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"GROUPS.saveNew()");

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.addForm("config/forms/config/group-edit.xml");

  };

	this.saveNew = function()
	{

		if (ge("name").value.length==0)
		{
			alert(_I18N_GROUP_NAME_ERROR);
			ge("name").focus();
			return false;
		}

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_group_save");
		p.addDOM(MODAL.container);
		p.post(API_URL,"GROUPS.writeSaveNew");
	}

	this.writeSaveNew = function(data)
	{
		
		if (data.error) alert(data.error);
		else
		{
			MODAL.hide();
			GROUPS.search();
			GROUPS.select(data.group_id);
		}
	};

	/**
		permissions editting
		*/
	this.permissions = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(500,400,_I18N_PERMISSIONS);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"GROUPS.savePermissions()");

		var p = new PROTO();
		p.add("command","config_group_getpermissions");
		p.add("group_id",GROUPS.group.id);
		p.post(API_URL,"GROUPS.writePermissions");

	};

	this.writePermissions = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell(_I18N_NO_RECORDS_FOUND,"one");
			MODAL.closeRecordRow();	
		}
		else
		{

			for (var i=0;i<data.record.length;i++)
			{
				var rec = data.record[i];

				MODAL.openRecordRow();
		
				var cb = createCheckbox("permission[]",rec.bitpos);
				if (rec.enabled=="t") cb.checked = true;

				MODAL.addRecordCell(cb,"checkboxCell");
				MODAL.addRecordCell(rec.name,"one");

				MODAL.closeRecordRow();

			}

		}

		MODAL.closeRecords();

	};

	this.savePermissions = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_group_savepermissions");
		p.add("group_id",GROUPS.group.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"GROUPS.writeSavePermissions");

	};

	this.writeSavePermissions = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else MODAL.hide();
	};

	/**
		group member management
		*/
	this.members = function(mode)
	{

		MODAL.open(600,400,_I18N_MEMBERS);
		MODAL.addSearch("GROUPS.searchMembers()",_I18N_SEARCH);

		GROUPS.searchMembers();
	};

	this.searchMembers = function()
	{

		//get the members
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_group_getmembers");
		p.add("group_id",GROUPS.group.id);
		p.add("search_string",ge("siteModalSearch").value);
		p.post(API_URL,"GROUPS.writeMembers");

	};

	this.writeMembers = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell(_I18N_NO_RECORDS_FOUND,"one");
			MODAL.closeRecordRow();	
		}
		else
		{

			for (var i=0;i<data.record.length;i++)
			{
				var rec = data.record[i];

				MODAL.openRecordRow();
				
				var cb = createCheckbox("account_id",rec.id);
				if (rec.member=="t") cb.checked = true;

				setClick(cb,"GROUPS.cycleMember(event)");

				MODAL.addRecordCell(cb,"checkboxCell");
       	MODAL.addRecordCell(rec.login,"three");
        MODAL.addRecordCell(rec.first_name + " " + rec.last_name,"two");
				MODAL.closeRecordRow();

			}

		}

		MODAL.closeRecords();

	};

	this.cycleMember = function(e)
	{

		var ref = getEventSrc(e);
		var cmd;

		if (ref.checked==true) cmd = "config_group_addmember";
		else cmd = "config_group_removemember";

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command",cmd);
		p.add("group_id",GROUPS.group.id);
		p.add("account_id",ref.value);
		p.post(API_URL,"GROUPS.writeSaveMembers");

	};

	this.writeSaveMembers = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
	};


}
