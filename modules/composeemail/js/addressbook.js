
var ADDRESSBOOK = new ADDRBOOK();

function ADDRBOOK()
{

	this.timer;

	this.load = function()
	{
	
		MODAL.open(550,360,_I18N_ADDRESS_BOOK);
	
		//header
		var header = ce("div","addrHeader","");
		var search = createTextbox("addrSearch");
		setKeyUp(search,"ajaxAddrSearch()");
		setClick(search,"checkSearchField()");
	
		MODAL.addSearch("ADDRESSBOOK.ajaxSearch()",_I18N_SEARCH);
	
		var filter = createSelect("filter","ADDRESSBOOK.search()");
		filter[0] = new Option(_I18N_MY_ADDRESS_BOOK,"local");
		filter[1] = new Option(_I18N_DOCMGR_ACCOUNTS,"account");

		MODAL.toolbarRight.appendChild(filter);

		ADDRESSBOOK.search();

	};
	
	/**************************************************************
	  FUNCTION: ajaxAddrSearch
	  PURPOSE:  uses a ADDRESSBOOK.timer to prevent queries from being sent
	            at every key stroke, but queries after a set time
	            of inactivity
	**************************************************************/
	this.ajaxSearch = function()
	{
	
	  //reset the ADDRESSBOOK.timer
	  clearTimeout(ADDRESSBOOK.timer);

		updateSiteStatus(_I18N_PLEASEWAIT);
	
	  //set it again.  when it times out, it will run.  this method keeps fast typers from querying the database a lot
	  ADDRESSBOOK.timer = setTimeout("ADDRESSBOOK.search()",250);
	
	};

	this.search = function()
	{
	
		var ss = ge("siteModalSearch").value;

		updateSiteStatus(_I18N_PLEASEWAIT);

		var p = new PROTO();

		if (ge("filter").value=="account") p.add("command","config_account_search");
		else p.add("command","addressbook_contact_search");

		p.add("search_string",ss);
		p.post(API_URL,"ADDRESSBOOK.writeResults");

	};

	this.writeResults = function(data)
	{

		clearSiteStatus();

		MODAL.openRecords();

		if (data.error) alert(data.error);
		else if (!data.record) 
		{
			MODAL.openRecordRow();
			MODAL.addRecordCell("No results found");
			MODAL.closeRecordRow();
		}
		else 
		{
	
			for (var i=0;i<data.record.length;i++) 
			{
				ADDRESSBOOK.addrEntry(data.record[i]);
			}
	
		}
	
	};
	
	this.addrEntry = function(entry)
	{

		var row = MODAL.openRecordRow();
	
		if (entry.email) setClick(row,"ADDRESSBOOK.useAddress('" + entry.first_name + "','" + entry.last_name + "','" + entry.email + "','" + entry.id + "')");
		else setClick(row,"alert('There is no email address set for this contact')");
	
		if (entry.email) var email = entry.email;
		else email = _I18N_NOT_SET;

		MODAL.addRecordCell(entry.first_name + " " + entry.last_name,"abName");
		MODAL.addRecordCell(email,"abEmail");

		MODAL.closeRecordRow();
	
	};
	
	this.useAddress = function(fn,ln,email,id) 
	{
	
		if (!cursorfocus) cursorfocus = "to";
	
		//if using a local filter, add this contact id to the contact_id field so any merging will be done
		if (ge("filter").value=="local") 
		{
	
			var add = 0;
			var cid = ge("contact_id");
	
			if (cid.value.length > 0) var arr = cid.value.split(",");
			else var arr = new Array();
	
			arr.push(id);
			cid.value = arr.join(",");
	
		}	
	
		var tostr = ge(cursorfocus).value;
		if (tostr.length>0) tostr += ", ";
		tostr += fn + " " + ln + " <" + email + ">";
	
		ge(cursorfocus).value = tostr;

	};
	
}
	