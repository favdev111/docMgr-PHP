
var PROFILE = new SITE_PROFILE();

function SITE_PROFILE()
{

	this.load = function()
	{

		PROFILE.account = new Array();
		PROFILE.toolbar();

    updateSiteStatus(_I18N_PLEASEWAIT);

		var cont = ge("container");
		clearElement(cont);

		loadForms("config/forms/config/account-profile-limited.xml","","PROFILE.writeForm","PROFILE.accountData");

	}

	this.accountData = function()
	{
  
    var p = new PROTO();
    p.add("command","config_account_get");
    p.add("account_id",USER_ID);
		p.setAsync(false);
    var data = p.post(API_URL);

		return data.record[0];

	};

	this.writeForm = function(cont)
	{
		clearSiteStatus();
		ge("container").appendChild(cont);
	};

  this.toolbar = function()
  {

    TOOLBAR.open("left",RECORDS.detailHeader);

    TOOLBAR.addGroup("20px");
    TOOLBAR.add(_I18N_SAVE,"PROFILE.save()");

		TOOLBAR.addGroup();
		TOOLBAR.add(_I18N_RESET_PASSWORD,"PROFILE.resetPassword()");
		TOOLBAR.add(_I18N_SETTINGS,"PROFILE.settings()");

    TOOLBAR.close();

  };

	this.save = function()
	{

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","config_account_saveprofile");
		p.add("account_id",USER_ID);
		p.addDOM(ge("container"));
		p.post(API_URL,"PROFILE.writeSave");

	};

	this.writeSave = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
	};

	this.resetPassword = function()
	{

		MODAL.open(400,150,"Reset Account Password");
		MODAL.addForm("config/forms/config/password.xml","PROFILE.passwordData");

		MODAL.addToolbarButtonRight("Save","PROFILE.savePassword()");

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
		p.add("account_id",PROFILE.account.id);
		p.add("password",p1.value);
		p.post(API_URL,"PROFILE.writeSave");

	};

  this.settings = function() 
  {

    MODAL.open(400,150,_I18N_SETTINGS);
    MODAL.addToolbarButtonRight(_I18N_SAVE,"PROFILE.saveSettings()");
    MODAL.addForm("config/forms/config/account-settings.xml","PROFILE.getSettingsData");

  };

	this.getSettingsData = function()
	{
		var p = new PROTO();
		p.add("command","config_account_getsettings");
		p.add("account_id",PROFILE.account.id);
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
		p.add("account_id",PROFILE.account.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"PROFILE.writeSaveSettings");

	};

	this.writeSaveSettings = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else MODAL.hide();
	};

}
