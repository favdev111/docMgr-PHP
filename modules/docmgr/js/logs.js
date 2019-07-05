
var LOGS = new OBJECT_LOGS();

function OBJECT_LOGS()
{

	this.obj;			//for storing all data we retrieved from this object during the search
	this.searchTimer; 

	/**
		hands off viewing to appropriate method
		*/
	this.load = function()
	{

		this.obj = PROPERTIES.obj;

		MODAL.open(760,480,_I18N_LOGS);

  	var sel = createSelect("logFilter");
  	setChange(sel,"LOGS.search()");
  	sel[0] = new Option(_I18N_VIEW_LAST_TEN,"lastten");
  	sel[1] = new Option(_I18N_VIEW_MY_ENTRIES,"myentries");
  	sel[2] = new Option(_I18N_VIEW_VIRUS_SCANS,"virus");
  	sel[3] = new Option(_I18N_VIEW_EMAILS,"email");
  	sel[4] = new Option(_I18N_VIEW_FILE_VIEWS,"view");
  	sel[5] = new Option(_I18N_VIEW_CHECKIN_CHECKOUT,"checkin");
  	sel[6] = new Option(_I18N_VIEW_ALL_ENTRIES,"all");

		MODAL.toolbarLeft.appendChild(sel);

		//add the header
    MODAL.openRecordHeader();   
    MODAL.addHeaderCell(_I18N_DATE,"logRecordDate");
    MODAL.addHeaderCell(_I18N_ENTRY,"logRecordDescription");
    MODAL.addHeaderCell(_I18N_ACCOUNT,"logRecordAccount");
    MODAL.addHeaderCell(_I18N_IP_ADDRESS,"logRecordIPAddress");
		MODAL.closeRecordHeader();

		LOGS.search();

	}

	this.search = function()
	{
	
	  updateSiteStatus(_I18N_PLEASEWAIT);

		//load our logs
		var p = new PROTO();
		p.add("command","docmgr_log_getlist");
		p.add("object_id",LOGS.obj.id);
		p.add("filter",ge("logFilter").value);
		p.post(API_URL,"LOGS.writeSearch");
	
	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.log)
		{
				MODAL.openRecordRow();
				MODAL.addRecordCell(_I18N_NORESULTS_FOUND,"one");
				MODAL.closeRecordRow();
		}
		else
		{

			for (var i=0;i<data.log.length;i++)
			{

				var entry = data.log[i];

				MODAL.openRecordRow();
				MODAL.addRecordCell(entry.log_time_view,"logRecordDate");
				MODAL.addRecordCell(entry.log_type_view,"logRecordDescription");
				MODAL.addRecordCell(entry.account_name,"logRecordAccount");
				MODAL.addRecordCell(entry.ip_address,"logRecordIPAddress");
				MODAL.closeRecordRow();

			}

		}	

		MODAL.closeRecords();
	
	};	

}


