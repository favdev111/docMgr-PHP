SITEMENU = new APP_SITEMENU();

function APP_SITEMENU()
{

  this.load = function()
  {
   
    MODAL.open(600,400,_I18N_SITENAV_MENU);

    SITEMENU.addCell(_I18N_FILES,"SITEMENU.jump('docmgr')","docmgr.png");
    //SITEMENU.addCell(_I18N_ADDRESS_BOOK,"SITEMENU.jump('addressbook')","address-book.png");
    //SITEMENU.addCell(_I18N_EMAIL,"SITEMENU.jump('composeemail')","email.png");

    //don't let guest accounts see these others
    if (!perm_check(GUEST_ACCOUNT) || perm_check(ADMIN))
    {

      SITEMENU.addCell(_I18N_WORKFLOW,"SITEMENU.jump('workflow')","workflow.png");

			if (perm_check(INSERT_OBJECTS))
			{
	      SITEMENU.addCell(_I18N_FILE_IMPORT,"SITEMENU.jump('docmgrimport')","import.png");
			}

      if (perm_check(ADMIN))
      {
        SITEMENU.addCell(_I18N_SYSTEM_CONFIGURATION,"SITEMENU.jump('config')","settings.png");
      }
      else if (perm_check(EDIT_PROFILE))
      {
        SITEMENU.addCell(_I18N_EDIT_PROFILE,"SITEMENU.jump('config')","profile.png");
      }

    }

    SITEMENU.addCell(_I18N_LOGOUT,"SITEMENU.logout()","logout.png");

    //we need this since we are floating divs inside
    MODAL.modalref.style.width = "600px";

  };

	this.addCell = function(title,link,imgsrc)
	{

		var cell = ce("div","siteMenuCell");
		
		var img = createImg(THEME_PATH + "/images/sitemenu/" + imgsrc);
		cell.appendChild(img);
		cell.appendChild(ce("div","","",title));
		setClick(cell,link);

		MODAL.add(cell);

	};

	this.jump = function(mod)
	{
		location.href = "index.php?module=" + mod;
	};

	this.logout = function()
	{
		sessionStorage.clear();
		localStorage.clear();
		location.href = "index.php?logout=1";
	};

}
