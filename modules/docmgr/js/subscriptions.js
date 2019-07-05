
var SUBSCRIPTIONS = new DOCMGR_SUBSCRIPTIONS();

function DOCMGR_SUBSCRIPTIONS()
{

	this.obj;

	this.load = function(e,id)
	{

    e.cancelBubble = true;

    //get our object info from the results
    for (var i=0;i<BROWSE.results.length;i++)
    {
      if (BROWSE.results[i].id==id)
      {
        this.obj = BROWSE.results[i];
        break;
      }
    }

		SUBSCRIPTIONS.get();

	};

	this.get = function()
	{

		if (SUBSCRIPTIONS.obj.object_type=="collection") var file = "config/forms/objects/subscription-collection.xml";
		else var file = "config/forms/objects/subscription-default.xml";

		MODAL.open(450,360,_I18N_MANAGE_SUBSCRIPTIONS);
		MODAL.addToolbarButtonRight("Save","SUBSCRIPTIONS.save()");
		MODAL.addForm(file,"SUBSCRIPTIONS.subscriptionData");

	};

	this.subscriptionData = function()
	{


		//now get what we are currently subscribed to for this object
		//setup the xml
		var p = new PROTO();
		p.setAsync(false);
		p.add("command","docmgr_subscription_get");
		p.add("object_id",SUBSCRIPTIONS.obj.id);
		var data = p.post(API_URL);

		if (data.error) alert(data.error);
		else if (data.record) return data.record[0];
		else return new Array();

	};

	this.save = function()
	{

		updateSiteStatus(_I18N_SAVING);

		var p = new PROTO();
		p.add("command","docmgr_subscription_save");
		p.add("object_id",SUBSCRIPTIONS.obj.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"SUBSCRIPTIONS.writeSave");

	};

	this.writeSave = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			NAV.load();
			MODAL.hide();
		}
	};

}

