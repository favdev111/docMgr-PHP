
var NOTIFICATIONS = new SITE_NOTIFICATIONS();

function SITE_NOTIFICATIONS()
{

	this.ref;
	this.notifCount;
	this.results;
	this.id;

	this.load = function()
	{

		//bail if not logged in
		if (!isLoggedIn()) return false;

		if (sessionStorage["notification_count"]) NOTIFICATIONS.notifCount = sessionStorage["notification_count"];
		else NOTIFICATIONS.notifCount = "0";

		NOTIFICATIONS.ref = ge("notificationsDiv");
		NOTIFICATIONS.updateDisplay();
		NOTIFICATIONS.getCount();

		setInterval("NOTIFICATIONS.getCount()",30000);

	};

	this.getCount = function()
	{
		var p = new PROTO();
		p.add("command","notification_manage_search");
		p.post(API_URL,"NOTIFICATIONS.writeCount");
	};

	this.writeCount = function(data)
	{

		if (data.error) alert(data.error);
		else
		{

			//store our results for later
			if (data.record) 
			{
				NOTIFICATIONS.results = data.record;
				NOTIFICATIONS.notifCount = data.record.length;
			}
			else
			{
				NOTIFICATIONS.results = new Array();
				NOTIFICATIONS.notifCount = "0";
			}

			//store our total count
			sessionStorage["notification_count"] = NOTIFICATIONS.notifCount;
			
			NOTIFICATIONS.updateDisplay();

		}

	};



	this.updateDisplay = function()
	{
		clearElement(NOTIFICATIONS.ref);
		NOTIFICATIONS.ref.appendChild(ctnode(NOTIFICATIONS.notifCount));
	};

	this.open = function()
	{

		MODAL.open(900,500,_I18N_NOTIFICATIONS);
		MODAL.addToolbarButtonLeft(_I18N_CLEAR_ALL,"NOTIFICATIONS.clear()");

		MODAL.openRecordHeader();
		MODAL.addHeaderCell(_I18N_DATE,"notifDateCell");
		MODAL.addHeaderCell(_I18N_NAME,"notifNameCell");
		MODAL.addHeaderCell(_I18N_MESSAGE,"notifMessageCell");
		MODAL.addHeaderCell(_I18N_OPTIONS,"notifOptionsCell");
		MODAL.closeRecordHeader();

		NOTIFICATIONS.search();

	};

	this.search = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","notification_manage_search");
		p.post(API_URL,"NOTIFICATIONS.writeSearch");
	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		//update our counts
		NOTIFICATIONS.writeCount(data);

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			NOTIFICATIONS.results = new Array();
			MODAL.openRecordRow();
			MODAL.addRecordCell(_I18N_NORESULTS_FOUND);
			MODAL.closeRecordRow();
		}
		else
		{

			NOTIFICATIONS.results = data.record;

			for (var i=0;i<NOTIFICATIONS.results.length;i++)
			{
	
				var rec = NOTIFICATIONS.results[i];
	
				var img = createImg(THEME_PATH + "/images/icons/delete.png");
				img.style.marginLeft = "15px";
				setClick(img,"NOTIFICATIONS.remove(event,'" + rec.id + "')");
	
				var row = MODAL.openRecordRow("NOTIFICATIONS.view('" + rec.id + "')");
				row.setAttribute("notification_id",rec.id);
	
				if (rec.message) var msg = rec.message;
				else var msg = rec.record_name;

				MODAL.addRecordCell(rec.date_created_view,"notifDateCell");
				MODAL.addRecordCell(rec.i18n_name,"notifNameCell");
				MODAL.addRecordCell(msg,"notifMessageCell");
				MODAL.addRecordCell(img,"notifOptionsCell");
				MODAL.closeRecordRow();
	
			}

		}

		MODAL.closeRecords();

	};

	this.view = function(id)
	{

		updateSiteStatus(_I18N_PLEASEWAIT);

		NOTIFICATIONS.id = id;

		//delete the notification
		var p = new PROTO();
		p.add("command","notification_manage_delete");
		p.add("notification_id",id);
		p.post(API_URL,"NOTIFICATIONS.writeView");

	};

	this.writeView = function(data)
	{

		clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{

			var rec = new Array();
	
			//remove this entry from the records
			for (var i=0;i<NOTIFICATIONS.results.length;i++)
			{
				if (NOTIFICATIONS.results[i].id==NOTIFICATIONS.id)
				{
					rec = NOTIFICATIONS.results[i];
					break;
				}
			}
	
			if (rec.link) 
			{
				var url = rec.link;
			}
			else
			{
				//now use our information to figure out where to redirect the person
				var url = "index.php?module=" + rec.record_type + "&recordId=" + rec.record_id;
			}
	
	
			//load the url
			location.href = url;
	
		}

	};

	this.clear = function()
	{

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","notification_manage_clear");
			p.post(API_URL,"NOTIFICATIONS.writeClear");
		}
		
	};

	this.writeClear = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else 
		{
			NOTIFICATIONS.search();
		}

	};

	this.remove = function(e,id)
	{

		e.cancelBubble = true;
		NOTIFICATIONS.id = id;

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","notification_manage_delete");
			p.add("notification_id",id);
			p.post(API_URL,"NOTIFICATIONS.writeRemove");
		}
		
	};

	this.writeRemove = function(data)
	{
		clearSiteStatus();

		if (data.error) alert(data.error);
		else
		{

			//remove this entry from the records
			for (var i=0;i<NOTIFICATIONS.results.length;i++)
			{
				if (NOTIFICATIONS.results[i].id==NOTIFICATIONS.id)
				{
					NOTIFICATIONS.results.splice(i,1);
				}
			}

			var arr = MODAL.recordContainer.getElementsByTagName("div");

			for (var i=0;i<arr.length;i++)
			{
				if (arr[i].hasAttribute("notification_id"))
				{
					if (arr[i].getAttribute("notification_id")==NOTIFICATIONS.id)
					{
						arr[i].parentNode.removeChild(arr[i]);
					}
				}
			}

			NOTIFICATIONS.id = "";
			NOTIFICATIONS.notifCount = NOTIFICATIONS.results.length;

			//store our total count
			sessionStorage["notification_count"] = NOTIFICATIONS.notifCount;
			
			NOTIFICATIONS.updateDisplay();

		}

	};

}
