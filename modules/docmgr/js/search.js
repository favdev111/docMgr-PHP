var SEARCH = new DOCMGR_SEARCH();

function DOCMGR_SEARCH()
{

	this.path = "/Users/" + USER_LOGIN;
	this.searchTimer;
	this.searchRange;
	this.sortField = "rank";
	this.sortDir = "DESC";

	this.load = function()
	{

	 	RECORDS.load(ge("container"),"listView","select");
		SEARCH.search();

	};

	this.setSearchRange = function(range)
	{
    BROWSE.searchLimit = RESULTS_PER_PAGE;
    BROWSE.searchOffset = 0;

		SEARCH.searchRange = range;
		SEARCH.search();
	}

  /** 
    handles typing for a search result
    */
  this.ajaxSearch = function()
  {

    BROWSE.searchLimit = RESULTS_PER_PAGE;
    BROWSE.searchOffset = 0;

	updateSiteStatus(_I18N_PLEASEWAIT);
	debugger;

    clearTimeout(SEARCH.searchTimer);
    SEARCH.searchTimer = setTimeout("SEARCH.search()","250");

  };

	this.search = function()
	{

		BROWSE.searchMode = "search";

			//had to do this funky because of IE
			var searchString = "";
			if (ge("siteToolbarSearch").value) searchString = ge("siteToolbarSearch").value;

			updateSiteStatus(_I18N_PLEASEWAIT);
			var p = new PROTO();
			p.add("command","docmgr_query_search");
			p.add("search_string",searchString);
	    p.add("search_limit",BROWSE.searchLimit);
	    p.add("search_offset",BROWSE.searchOffset);
			p.add("sort_field",SEARCH.sortField);
			p.add("sort_dir",SEARCH.sortDir);

			p.addDOM(RECORDS.filterContainer);

			//limit our search to the current collection if desired
			if (SEARCH.searchRange=="collection") p.add("object_id",BROWSE.id);

			//if not limited, set our browse ceiling to root so breadcrumbs work
			else BROWSE.ceiling = "/";

			p.post(API_URL,"BROWSE.writeBrowse");

			//store filters and search options
			RECORDS.storeFilters("search");

			sessionStorage["docmgr_search_string"] = searchString;
			sessionStorage["docmgr_search_range"] = SEARCH.searchRange;

	};

	this.clear = function()
	{

    //make sure we don't have any search filters displayed since we are browsing
    RECORDS.closeFilters();

    sessionStorage["docmgr_search_string"] = "";
    sessionStorage["docmgr_search_range"] = "";

    //keeps us from reloading a prior search now that we are back to browsing
    RECORDS.storeFilters("search"); 

		//default back to our collection filter
		PULLDOWN.selectRow(everywhereRow);
		ge("siteToolbarSearch").value = "";

	};

}
