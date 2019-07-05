
var OPTIONS = new OBJECT_OPTIONS();

function OBJECT_OPTIONS()
{

	this.obj;			//for storing all data we retrieved from this object during the search

	/**
		hands off viewing to appropriate method
		*/
	this.load = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);

		MODAL.open(350,150,_I18N_OPTIONS);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"OPTIONS.save()");
		MODAL.addForm("config/forms/objects/options.xml","OPTIONS.getData");

	};

	this.getData = function()
	{

		//get our info
		var p = new PROTO();
    p.add("command","docmgr_options_get");
    p.add("object_id",PROPERTIES.obj.id);
		p.setAsync(false);
    var data = p.post(API_URL);

		if (data.error) alert(data.error);
		else if (!data.record) return new Array();
		else return data.record[0];

	};


	this.save = function()
	{

		updateSiteStatus(_I18N_SAVING);
 
		var p = new PROTO();
		p.add("command","docmgr_options_save");
		p.add("object_id",PROPERTIES.obj.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"OPTIONS.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else MODAL.hide();

	};

}


