
var LOGS = new DOCMGR_LOGS();

function DOCMGR_LOGS()
{

	this.searchLimit = 50;
	this.searchOffset = 0;
	this.searchTimer;
	this.records;

	this.load = function()
	{

		RECORDS.load(ge("container"),"listPagerView","active");
		PAGER.load(LOGS);

		LOGS.header();
		LOGS.toolbar();
		LOGS.search();

	}; 

	this.toolbar = function()
	{

	  TOOLBAR.open();

	  TOOLBAR.addGroup();
	  TOOLBAR.add(_I18N_FILTER,"LOGS.addFilter()");

		TOOLBAR.close();

	};
	
	this.header = function()
	{
		RECORDS.openHeaderRow();
		RECORDS.addHeaderCell(_I18N_TIME,"logTimeCell");
		RECORDS.addHeaderCell(_I18N_MESSAGE,"logMessageCell");
		RECORDS.addHeaderCell(_I18N_LEVEL,"logLevelCell");
		RECORDS.addHeaderCell(_I18N_CATEGORY,"logCategoryCell");
		RECORDS.addHeaderCell(_I18N_IP_ADDRESS,"logIpCell");
		RECORDS.addHeaderCell(_I18N_USER_ID,"logAccountIdCell");
		RECORDS.addHeaderCell(_I18N_LOGIN,"logLoginCell");
		RECORDS.closeHeaderRow();
	};

  this.addFilter = function()
  {
    RECORDS.openFilters("config/filters/logs.xml",LOGS.ajaxSearch);
  }; 

  /**
    handles typing for a search result 
    */
  this.ajaxSearch = function() 
  {
    updateSiteStatus(_I18N_PLEASEWAIT);

    clearTimeout(LOGS.searchTimer);
    LOGS.searchTimer = setTimeout("LOGS.search()","250");

  };

	this.search = function()
	{
		updateSiteStatus(_I18N_PLEASEWAIT);
		var p = new PROTO();
		p.add("search_limit",LOGS.searchLimit);
		p.add("search_offset",LOGS.searchOffset);
		p.add("command","config_logs_search");
		p.addDOM(RECORDS.filterContainer);
		p.post(API_URL,"LOGS.writeSearch");
	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		RECORDS.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record)
		{
			LOGS.records = new Array();
			PAGER.update(0,0);
     	RECORDS.openRecordRow();
      RECORDS.addRecordCell(_I18N_NORESULTS_FOUND,"one");
      RECORDS.closeRecordRow();
    }
    else
    {

      PAGER.update(data.total_count,data.current_count);
			LOGS.records = data.record;

      for (var i=0;i<data.record.length;i++)
			{
				var rec = data.record[i];

				RECORDS.openRecordRow("LOGS.viewData('" + rec.id + "')");
				RECORDS.addRecordCell(dateView(rec.log_timestamp),"logTimeCell");
				RECORDS.addRecordCell(rec.message,"logMessageCell");
				RECORDS.addRecordCell(rec.level,"logLevelCell");
				RECORDS.addRecordCell(rec.category,"logCategoryCell");
				RECORDS.addRecordCell(rec.ip_address,"logIpCell");
				RECORDS.addRecordCell(rec.user_id,"logAccountIdCell");
				RECORDS.addRecordCell(rec.user_login,"logLoginCell");
				RECORDS.closeRecordRow();
			}
	
		}
	
		RECORDS.closeRecords();

	};	

	this.viewData = function(id)
	{

		var data = "";

		for (var i=0;i<LOGS.records.length;i++)
		{
			if (LOGS.records[i].id==id)
			{
				data = LOGS.records[i].data;
				break;		
			}

		}
		
		if (!data) data = _I18N_NO_DATA;

		MODAL.open(640,480,"Log Data");
		MODAL.add(ce("div","logData","",data));

	};

}