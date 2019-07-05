
var SHARE = new DOCMGR_SHARE();

function DOCMGR_SHARE()
{

	this.id;

	this.getSelected = function()
	{

		var arr = new Array();

		for (var i=0;i<RECORDS.selected.length;i++)
		{
			arr.push(RECORDS.selected[i].getAttribute("record_id"));
		}

		return arr;

	}
	
	this.load = function(id)
	{
	
		//return a reference to the parent row
		if (id) var arr = new Array(id);
		else var arr = SHARE.getSelected();
	
		if (arr.length==0)
		{
			alert(_I18N_SELECT_OBJECT_SHARE_ERROR);
			return false;
		}
		else if (arr.length > 1)
		{
			alert(_I18N_SELECT_OBJECT_TOOMANY_ERROR);
			return false;
		}
	
		//store the id
		SHARE.id = arr[0];
	
		//create the popup
		MODAL.open(550,300,_I18N_SHARE_SETTINGS);
		MODAL.addToolbarButtonRight(_I18N_ADD_USER,"SHARE.addUser()");

		MODAL.openRecordHeader();
		MODAL.addHeaderCell(_I18N_ACCOUNT_NAME,"nameCell");
		MODAL.addHeaderCell(_I18N_SHARE_SETTINGS,"shareSettingsCell");
		MODAL.addHeaderCell(_I18N_OPTIONS,"modalOptionsCell");
		MODAL.closeRecordHeader();

		SHARE.search();
	
	}
	
	this.search = function()
	{
		//fetch the body information
		var p = new PROTO();
		p.add("command","docmgr_share_search");
		p.add("object_id",SHARE.id);
		p.post(API_URL,"SHARE.writeSearch");
	}
	
	this.writeSearch = function(data)
	{
	
		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record) 
		{

			MODAL.openRecordRow();
			MODAL.addRecordCell(_I18N_OBJECT_NOT_SHARED,"one");
			MODAL.closeRecordRow();
		}
		else
		{

			for (var i=0;i<data.record.length;i++)
			{

				var share = data.record[i];

				MODAL.openRecordRow();	
				MODAL.addRecordCell(share.share_account_name,"nameCell");

				var sel = createSelect("shareSettings[]");
				sel.style.marginTop = "-3px";
				setChange(sel,"SHARE.update(event,'" + share.share_account_id + "')");
		
				sel[0] = new Option(_I18N_NONE,"none");
				sel[1] = new Option(_I18N_VIEW,"view");
				sel[2] = new Option(_I18N_EDIT,"edit");
				sel.value = share.bitmask_text;

				MODAL.addRecordCell(sel,"shareSettingsCell");
	
				var img = createImg(THEME_PATH + "/images/icons/delete.png");
				setClick(img,"SHARE.remove('" + share.share_account_id + "')");
				MODAL.addRecordCell(img,"modalOptionsCell");

				MODAL.closeRecordRow();
	
			}
	
		}
	
		MODAL.closeRecords();

	};
	
	this.addUser = function()
	{

		//create the popup
		MODAL.open(550,300,_I18N_ADD_USER);
		MODAL.addToolbarButtonRight(_I18N_SAVE,"SHARE.save()");
		MODAL.addToolbarButtonLeft(_I18N_BACK,"SHARE.load()");
		MODAL.addForm("config/forms/objects/share-add.xml");

	};
	
	this.save = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		//save these results
		var p = new PROTO();
		p.add("command","docmgr_share_save");
		p.add("object_id",SHARE.id);
		p.addDOM(MODAL.container);
		p.post(API_URL,"SHARE.writeSave");
	};
	
	this.writeSave = function(data)
	{
		clearSiteStatus();
		if (data.error) alert(data.error);
		else SHARE.load(SHARE.id);
	};

	this.update = function(e,aid)
	{

		var src = getEventSrc(e);
		var level = src.value;

		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("command","docmgr_share_save");
		p.add("object_id",SHARE.id);
		p.add("share_account_id",aid);
		p.add("share_level",level);
		p.post(API_URL,"SHARE.writeSave");

	};

	this.remove = function(aid)
	{

		if (confirm(_I18N_CONTINUE_CONFIRM))
		{
			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_share_delete");
			p.add("object_id",SHARE.id);
			p.add("share_account_id",aid);
			p.post(API_URL,"SHARE.writeSave");
		}

	};
	
}
