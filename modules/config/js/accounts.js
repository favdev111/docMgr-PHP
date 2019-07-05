
var ACCOUNTS = new SITE_ACCOUNTS();

function SITE_ACCOUNTS()
{

	this.mode;
	this.editBtn;
	this.account;
	this.timer;
	this.locMode;

	this.load = function()
	{

		ACCOUNTS.mode = "view";

    RECORDS.load(ge("container"),"listDetailView","select");

		ACCOUNTS.toolbar();
		ACCOUNTS.header();
		ACCOUNTS.search();

	};

	this.header = function()
	{
    RECORDS.openHeaderRow();
    RECORDS.addHeaderCell(_I18N_LOGIN,"accountLoginCell");
    RECORDS.addHeaderCell(_I18N_NAME,"accountNameCell");
    RECORDS.addHeaderCell(_I18N_EMAIL,"accountEmailCell");
    RECORDS.addHeaderCell(_I18N_LAST_ACCESS,"accountLastAccessCell");
    RECORDS.closeHeaderRow();
	};

	this.toolbar = function()
	{

		TOOLBAR.open();
		TOOLBAR.addSearch("ACCOUNTS.ajaxSearch()",_I18N_SEARCH);

		TOOLBAR.addGroup();
		//ACCOUNTS.editBtn = TOOLBAR.add(_I18N_EDIT,"ACCOUNTS.cycleMode()");
		TOOLBAR.add(_I18N_NEW,"ACCOUNTS.create()");

		//ldap is enabled
		if (typeof(USE_LDAP) != "undefined")
		{
			TOOLBAR.addGroup();
			TOOLBAR.add("Sync LDAP","ACCOUNTS.sync()");
		}

		TOOLBAR.close();

	};

	this.sync = function()
	{

		if (confirm(_I18N_SYNC_LDAP_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","config_account_sync");
			p.post(API_URL,"ACCOUNTS.writeSync");
		}

	};

	this.writeSync = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else ACCOUNTS.search();

	};

	this.ajaxSearch = function()
	{

    if (ACCOUNTS.timer) clearTimeout(ACCOUNTS.timer);

    updateSiteStatus(_I18N_PLEASEWAIT);
    ACCOUNTS.timer = setTimeout("ACCOUNTS.search()","200");

	};

	this.search = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

		var p = new PROTO();
		p.add("command","config_account_search");
		p.add("search_string",ge("siteToolbarSearch").value);
		p.post(API_URL,"ACCOUNTS.writeSearch");

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

				var row = RECORDS.openRecordRow("ACCOUNTS.select('" + rec.id + "')");
				row.setAttribute("account_id",rec.id);

				RECORDS.addRecordCell(rec.login,"accountLoginCell");
				RECORDS.addRecordCell(rec.full_name,"accountNameCell");
				RECORDS.addRecordCell(rec.email,"accountEmailCell");
				RECORDS.addRecordCell(dateView(rec.last_success_login),"accountLastAccessCell");

				RECORDS.closeRecordRow();			

			}

		}


		RECORDS.closeRecords();

	};

	this.select = function(id)
	{

    if (ACCOUNTS.mode!="view") return false;

		ACCOUNTS.account = new Array();
  
    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","config_account_get");
    p.add("account_id",id);
    p.post(API_URL,"ACCOUNTS.writeAccount");

	};


  this.writeAccount = function(data)
  {

    if (data.error) alert(data.error);
    else if (!data.record) alert("Unable to retrieve record");
    else
    {   
      ACCOUNTS.account = data.record[0];
      ACCOUNTS.loadSubHeader();
    
      RECORDS.addDetailForm("config/forms/config/account-profile.xml","ACCOUNTS.getAccountData");
    }

  };


  this.getAccountData = function()
  {
    return ACCOUNTS.account;
  };

  this.loadSubHeader = function()
  {

    var SUBTOOL = new SITE_TOOLBAR();

    SUBTOOL.open("left",RECORDS.detailHeader);

    SUBTOOL.addGroup("20px");
    SUBTOOL.add(_I18N_SAVE,"ACCOUNTS.save()");
    SUBTOOL.add(_I18N_DELETE,"ACCOUNTS.remove()");

		SUBTOOL.addGroup();
		SUBTOOL.add(_I18N_RESET_PASSWORD,"ACCOUNTS.resetPassword()");
		SUBTOOL.add(_I18N_SETTINGS,"ACCOUNTS.settings()");
		SUBTOOL.add(_I18N_PERMISSIONS,"ACCOUNTS.permissions()");
		SUBTOOL.add(_I18N_GROUPS,"ACCOUNTS.groups()");

    SUBTOOL.close();

  };

  this.cycleMode = function()
  {
   
    if (ACCOUNTS.mode=="edit")
    {
      ACCOUNTS.mode = "view";
      RECORDS.setRowMode("select");
      ACCOUNTS.editBtn.innerHTML = " Edit ";
    }
    else
    {   
      ACCOUNTS.mode = "edit";
      RECORDS.setRowMode("multiselect");
      ACCOUNTS.editBtn.innerHTML = "Cancel";
    }

  };

	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_account_save");
		p.add("account_id",ACCOUNTS.account.id);
		p.addDOM(RECORDS.recordDetail);
		p.post(API_URL,"ACCOUNTS.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();
		if (data.error) alert(data.error);

	};

  this.create = function() 
  {

    MODAL.open(450,320,"Create New Account");
    MODAL.addToolbarButtonRight("Save","ACCOUNTS.saveNew()");
    MODAL.addForm("config/forms/config/account-create.xml","ACCOUNTS.createData");

  };

	this.createData = function()
	{
		return new Array();
	}

  this.saveNew = function()
  {  

		//sanity checking
		if (ge("password").value!=ge("password2").value)
		{
			alert("The passwords do not match");
			ge("password").focus();
			return false;
		}

    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","config_account_save");
    p.addDOM(MODAL.container);
    p.post(API_URL,"ACCOUNTS.writeSaveNew");

  }

  this.writeSaveNew = function(data)  
  {

    if (data.error) alert(data.error);
    else
    {
      MODAL.hide();
      ACCOUNTS.search();
			ACCOUNTS.select(data.account_id);
    }

  };

  this.remove = function()
  {

    if (confirm("Are you sure you want to remove this account?"))
    {
      updateSiteStatus(_I18N_PLEASEWAIT);
      var p = new PROTO();
      p.add("command","config_account_delete");
      p.add("account_id",ACCOUNTS.account.id);
      p.post(API_URL,"ACCOUNTS.writeRemove");
    }

  };

  this.writeRemove = function(data)
  {
    clearSiteStatus();  

    if (data.error) alert(data.error);
    else
    {
      ACCOUNTS.account = new Array();
      ACCOUNTS.search();
			RECORDS.clearDetail();
    }       

  };


	this.resetPassword = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(400,150,"Reset Account Password");
		MODAL.addForm("config/forms/config/password.xml","ACCOUNTS.passwordData");

		MODAL.addToolbarButtonRight("Save","ACCOUNTS.savePassword()");

	};

	this.passwordData = function()
	{
		return new Array();
	};

	this.savePassword = function()
	{

		var p1 = ge("password");
		var p2 = ge("password_confirm");

		if (p1.value != p2.value)
		{
			alert("Your passwords do not match");
			p1.focus();
			return false;
		}

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_account_savepassword");
		p.add("account_id",ACCOUNTS.account.id);
		p.add("password",p1.value);
		p.post(API_URL,"ACCOUNTS.writePasswordSave");

	};

	this.writePasswordSave = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else MODAL.hide();
	}

	/**
		permissions editting
		*/
	this.permissions = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(500,400,"Account Permissions");
		MODAL.addToolbarButtonRight("Save","ACCOUNTS.savePermissions()");

		var p = new PROTO();
		p.add("command","config_account_getpermissions");
		p.add("account_id",ACCOUNTS.account.id);
		p.post(API_URL,"ACCOUNTS.writePermissions");

	};

	this.writePermissions = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell("No records found","one");
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
				MODAL.addRecordCell(rec.name,"nameCell");

				MODAL.closeRecordRow();

			}

		}

		MODAL.closeRecords();

	};

	this.savePermissions = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_account_savepermissions");
		p.add("account_id",ACCOUNTS.account.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"ACCOUNTS.writeSavePermissions");

	};

	this.writeSavePermissions = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else MODAL.hide();
	};

	/**
		account group management
		*/
	this.groups = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		MODAL.open(500,400,"Account Groups");
		MODAL.addToolbarButtonRight("Save","ACCOUNTS.saveGroups()");

		var p = new PROTO();
		p.add("command","config_account_getgroups");
		p.add("account_id",ACCOUNTS.account.id);
		p.post(API_URL,"ACCOUNTS.writeGroups");

	};

	this.writeGroups = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell("No records found","one");
			MODAL.closeRecordRow();	
		}
		else
		{

			for (var i=0;i<data.record.length;i++)
			{
				var rec = data.record[i];

				MODAL.openRecordRow();
		
				var cb = createCheckbox("group_id[]",rec.id);
				if (rec.member=="t") cb.checked = true;

				MODAL.addRecordCell(cb,"checkboxCell");
				MODAL.addRecordCell(rec.name,"nameCell");

				MODAL.closeRecordRow();

			}

		}

		MODAL.closeRecords();

	};

	this.saveGroups = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_account_savegroups");
		p.add("account_id",ACCOUNTS.account.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"ACCOUNTS.writeSaveGroups");

	};

	this.writeSaveGroups = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else MODAL.hide();
	};

  this.settings = function() 
  {

		updateSiteStatus(_I18N_PLEASEWAIT);
    MODAL.open(400,150,_I18N_SETTINGS);
    MODAL.addToolbarButtonRight(_I18N_SAVE,"ACCOUNTS.saveSettings()");
    MODAL.addForm("config/forms/config/account-settings.xml","ACCOUNTS.getSettingsData");

  };

	this.getSettingsData = function()
	{
		var p = new PROTO();
		p.add("command","config_account_getsettings");
		p.add("account_id",ACCOUNTS.account.id);
		p.setAsync(false);
		var data = p.post(API_URL);

		if (data.record) return data.record[0];
		else return new Array();
	};

	this.saveSettings = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_account_savesettings");
		p.add("account_id",ACCOUNTS.account.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"ACCOUNTS.writeSaveSettings");

	};

	this.writeSaveSettings = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else MODAL.hide();
	};

}
