
var curres;
var curfield;
var showsuggest = "";
var runsugg = "";

function suggestAddress(e) 
{

	if (timer) clearTimeout(timer);

	//get the calling field
	var ref = getEventSrc(e);
	curfield = ref;

	//figure out what our search string is
	var arr = ref.value.split(",");
	var ss = arr[arr.length - 1];

	//set the field we'll write our results to
	curres = ge(ref.id + "suggest");

	//if we have a string, proceed
	if (ss.length > 0) 
	{

		runsugg = 1;
		timer = setTimeout("runSuggestAddress('" + ss + "')",250);

	} 
	else 
	{
		clearElement(curres);
		curres.style.display = "none";
		showsuggest = "";
		runsugg = "";
		clearSiteStatus();
	}

}

function hideAllSuggest()
{

	setTimeout("runHideAllSuggest()","200");
}

function runHideAllSuggest()
{

	runsugg = "";
	ge("tosuggest").style.display = "none";
	ge("ccsuggest").style.display = "none";
	ge("bccsuggest").style.display = "none";

}

function runSuggestAddress(ss) 
{

    updateSiteStatus(_I18N_PLEASEWAIT);
    var p = new PROTO();
    p.add("command","email_suggest_search");
    p.add("search_string",ss);
    p.add("filter","both");
    p.post(API_URL,"writeSuggestResults");

  //var url = "index.php?module=emailsuggest&addressbook=both&limit=10&searchString=" + ss;
  //loadReq(url,"writeSuggestResults");

}

function writeSuggestResults(data) {

	//if somehow we got here and they were a fast typer and suggest is now hidden, bail
	if (!runsugg) return false;

	clearSiteStatus();

  if (data.error) alert(data.error);
  else {

    clearElement(curres);

		if (!data.record) 
		{

			curres.style.display = "none";
			showsuggest = "";

		} else {			

			showsuggest = 1;
			curres.style.display = "block";

	    for (var i=0;i<data.record.length;i++) {

				if (data.record[i].email) 
				{
	      	curres.appendChild(suggestEntry(data.record[i]));
				}

	    }

		}

  }

}

function suggestEntry(data) {

	var val = data.first_name + " " + data.last_name + " <" + data.email + ">";

	var mydiv = ce("div","","",val);
	mydiv.setAttribute("email",val);

	setClick(mydiv,"useSuggestEntry(event)");
	return mydiv;

}

function useSuggestEntry(e) 
{

	var ref = getEventSrc(e);
	var data = ref.getAttribute("email");

	//replace the last entry with this value
	var arr = curfield.value.split(",");
	var key = arr.length - 1;
	arr[key] = data;

	curfield.value = arr.join(", ") + ", ";

	clearElement(curres);
	curres.style.display = "none";
	showsuggest = "";

	setCaretPosition(curfield,curfield.value.length);

}

function pickFirstSuggest() {

	clearSiteStatus();

	var arr = curres.getElementsByTagName("div");

	var data = arr[0].getAttribute("email");

	//replace the last entry with this value
	var arr = curfield.value.split(",");
	var key = arr.length - 1;
	arr[key] = data;

	curfield.value = arr.join(", ");

	clearElement(curres);
	curres.style.display = "none";
	showsuggest = "";

}

