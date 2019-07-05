
/**
	object revision history manager
	*/

var HISTORY = new OBJECT_HISTORY();

function OBJECT_HISTORY()
{

	this.obj;			//for storing all data we retrieved from this object during the search
	this.searchTimer; 

	/**
		hands off viewing to appropriate method
		*/
	this.load = function()
	{

		this.obj = PROPERTIES.obj;

		//setup our window and toolbar
		MODAL.open(1000,480,_I18N_REVISION_HISTORY);
		MODAL.addToolbarButtonLeft(_I18N_DELETE_REVISION,"HISTORY.remove()");
		MODAL.addToolbarButtonRight(_I18N_PROMOTE_REVISION,"HISTORY.delete()");

		//add the header for search results
    MODAL.openRecordHeader();   
		MODAL.addHeaderCell(createNbsp(),"historyRecordCheckbox");
		MODAL.addHeaderCell(_I18N_VERSION,"historyRecordVersion");
		MODAL.addHeaderCell(_I18N_CUSTOM_VERSION,"historyRecordCustomVersion");
		MODAL.addHeaderCell(_I18N_MODIFIED_ON,"historyRecordModifiedOn");
		MODAL.addHeaderCell(_I18N_MODIFIED_BY,"historyRecordModifiedBy");
		MODAL.addHeaderCell(_I18N_SIZE,"historyRecordSize");
		MODAL.addHeaderCell(_I18N_NOTES,"historyRecordNotes");
		MODAL.closeRecordHeader();

		//pull our records
		HISTORY.search();

	}

	/**
		returns all revisions for the current object
		*/
	this.search = function()
	{
	
	  updateSiteStatus(_I18N_PLEASEWAIT);

		if (PROPERTIES.obj.object_type=="document") var cmd = "docmgr_document_gethistory";
		else var cmd = "docmgr_file_gethistory";

		var p = new PROTO();
		p.add("command",cmd);
		p.add("object_id",HISTORY.obj.id);
		p.post(API_URL,"HISTORY.writeSearch");
	
	};

	/**
		response handler for our search results
		*/
	this.writeSearch = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.history)
		{
				MODAL.openRecordRow();
				MODAL.addRecordCell(_I18N_NORESULTS_FOUND,"one");
				MODAL.closeRecordRow();
		}
		else
		{

			for (var i=0;i<data.history.length;i++)
			{

				var entry = data.history[i];

				MODAL.openRecordRow();
				MODAL.addRecordCell(createCheckbox("fileId[]",entry.id),"historyRecordCheckbox");
				MODAL.addRecordCell(entry.version,"historyRecordVersion");
				MODAL.addRecordCell(entry.custom_version,"historyRecordCustomVersion");
				MODAL.addRecordCell(entry.view_modified_date,"historyRecordModifiedOn");
				MODAL.addRecordCell(entry.view_modified_by,"historyRecordModifiedBy");
				MODAL.addRecordCell(entry.size,"historyRecordSize");
				MODAL.addRecordCell(entry.notes,"historyRecordNotes");
				MODAL.closeRecordRow();

			}

		}	

		MODAL.closeRecords();
	
	};	

	/**
		returns the revision record ids of all checked revisions
		*/
	this.getSelected = function()
	{

		var arr = new Array();
		var inputs = MODAL.container.getElementsByTagName("input");

		for (var i=0;i<inputs.length;i++)
		{
			if (inputs[i].checked==true) arr.push(inputs[i].value);
		}

		return arr;

	};

	/**
		deletes any selected revisions of the file
		*/
	this.remove = function()
	{

		var selected = HISTORY.getSelected();

		if (selected.length==0)
		{
			alert(_I18N_REVISION_DELETE_SELECT_ERROR);
			return false;
		}

		if (confirm(_I18N_REVISION_DELETE_CONFIRM))
		{

			if (PROPERTIES.obj.object_type=="document") var cmd = "docmgr_document_removerevision";
			else var cmd = "docmgr_file_removerevision";

	    var p = new PROTO();  
	    p.add("command",cmd);
	    p.add("object_id",HISTORY.obj.id);
	    p.add("file_id",selected);
	    p.post(API_URL,"HISTORY.writeAction");
		}

	};

	/**
		promotes the checked file to the latest revision
		*/
	this.promote = function()
	{

		var selected = HISTORY.getSelected();

		//bail if nothing selected
		if (selected.length==0)
		{
			alert(_I18N_REVISION_PROMOTE_SELECT_ERROR);
			return false;
		}

		//bail if more than one selected
		if (selected.length > 1)
		{
			alert(_I18N_REVISION_TOOMANY_ERROR);
			return false;
		};

		updateSiteStatus(_I18N_PLEASEWAIT);

		if (PROPERTIES.obj.object_type=="document") var cmd = "docmgr_document_promote";
		else var cmd = "docmgr_file_promote";

		var p = new PROTO();
		p.add("command",cmd);
		p.add("object_id",HISTORY.obj.id);
		p.add("file_id",selected[0]);
    p.post(API_URL,"HISTORY.writeAction");

	};

	/**
		response handler for promotion or deletion.  Simply shows any errors
		or refreshes the list if there are no errors to show
		*/
	this.writeAction = function(data)
	{
		if (data.error) alert(data.error);
		else HISTORY.search();
	};

}
