

function loadPage()
{
	loadSidebar();

	if (perm_check(ADMIN)) ACCOUNTS.load();
	else PROFILE.load();

}


function loadSidebar()
{

	SIDEBAR.open();

	SIDEBAR.addGroup(_I18N_NAVIGATION);

	//admin only
	if (perm_check(ADMIN))
	{
		//if they can get here, they can see Navigation	
		SIDEBAR.add(_I18N_ACCOUNTS,"ACCOUNTS.load()");
		SIDEBAR.add(_I18N_GROUPS,"GROUPS.load()");
		SIDEBAR.add(_I18N_KEYWORDS,"KEYWORDS.load()");
		SIDEBAR.add(_I18N_BOOKMARKS,"BOOKMARKS.load()");
		SIDEBAR.add(_I18N_LOGS,"LOGS.load()");
	}
	else
	{
		//if they can get here, they can see Navigation	
		SIDEBAR.add(_I18N_PROFILE,"PROFILE.load()");
	}

	SIDEBAR.close();

	SIDEBAR.showCurrent(0);

}

