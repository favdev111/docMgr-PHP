
var NAV = new DOCMGR_NAV();

function DOCMGR_NAV()
{

	this.bookmarks;

	this.load = function()
	{
    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","docmgr_bookmark_search");
    p.post(API_URL,"NAV.writeBookmarks");
	};

	this.writeBookmarks = function(data)
	{

		SIDEBAR.open();
		SIDEBAR.addGroup(_I18N_BOOKMARKS);

    var img = createImg(THEME_PATH + "/images/icons/settings.png");
    SIDEBAR.addHeaderImage(img,"BOOKMARKS.manage()",_I18N_MANAGE_BOOKMARKS);

		if (data.error) alert(data.error);
		else if (data.record)
		{

			NAV.bookmarks = data.record;
	
			for (var i=0;i<data.record.length;i++)
			{
				var rec = data.record[i];
				var row = SIDEBAR.add(rec.name,"BROWSE.setCeiling('" + rec.object_path + "')");

				if (rec.object_path==BROWSE.path) 
				{
					SIDEBAR.showCurrent(i);
				}

			}

		}

		//now do the saved searches
    var p = new PROTO();
    p.add("command","docmgr_search_search");
    p.post(API_URL,"NAV.writeSearch");

	};

	this.selectBookmark = function(path)
	{

		if (!NAV.bookmarks) return false;

		for (var i=0;i<NAV.bookmarks.length;i++)
		{

			if (NAV.bookmarks[i].object_path==path)
			{
				SIDEBAR.showCurrent(i);
				break;
			}

		}

	};

	this.writeSearch = function(data)
	{

		clearSiteStatus();

		SIDEBAR.addGroup(_I18N_SAVED_SEARCHES);

    var img = createImg(THEME_PATH + "/images/icons/settings.png");
    SIDEBAR.addHeaderImage(img);
		SIDEBAR.addHeaderSubmenu(_I18N_SAVE_CURRENT_SEARCH,"SAVEDSEARCHES.addNew()");
		SIDEBAR.addHeaderSubmenu(_I18N_MANAGE_SAVED_SEARCHES,"SAVEDSEARCHES.manage()");

		if (data.error) alert(data.error);
		else if (data.record)
		{
	
			for (var i=0;i<data.record.length;i++)
			{

				var rec = data.record[i];
				var row = SIDEBAR.add(rec.name,"SAVEDSEARCHES.run('" + rec.id + "')");

			}

		}

		SIDEBAR.close();

	};

}
