/*******************************************************
	core.js
	Common functions used in our apps
	Created: 04/20/2006
*******************************************************/

var BROWSER;
var BROWSERPROG;
var BROWSERVERSION;
var BROWSERMOBILE = false;

//set browser
if (navigator.userAgent.toLowerCase().indexOf("webkit")!=-1)
{
  BROWSER = "webkit";
  if (navigator.userAgent.toLowerCase().indexOf("chrome")!=-1) BROWSERPROG = "chrome";
  else BROWSERPROG = "safari";
}
else if (navigator.userAgent.toLowerCase().indexOf("msie")!=-1)
{
	var str = navigator.userAgent.toLowerCase();
	var pos1 = str.indexOf("msie ") + 5;
	var sub = str.substr(pos1);
	var pos2 = sub.indexOf(";");
	var ver = parseFloat(sub.substr(0,pos2));

  BROWSER = "ie"; 
  BROWSERPROG = "ie";
	BROWSERVERSION = ver;
}
else 
{
  BROWSER = "mozilla";
  BROWSERPROG = "firefox";
}

if (navigator.userAgent.toLowerCase().indexOf("mobile")!=-1) BROWSERMOBILE = true;

/**
	not in a function, called in-line with the page is loaded
	*/

//returns a string for opening a window in the center of the screen.
//bases position on width and height of the window
function centerParms(width,height,complete) {

	xPos = (screen.width - width) / 2;
	yPos = (screen.height - height) / 2;

	string = "left=" + xPos + ",top=" + yPos;

	//return the width & height portions too
	if (complete) string += ",width=" + width +",height=" + height;

	return string;
}

//return the key of the array which matches our needle
function arraySearch(str,arr) {

        arrlen = arr.length;

        for (c=0;c<arrlen;c++) {

                if (arr[c]==str) return c;

        }

        return -1;
}


//reduce an array to just those keys that has values.  The keys are
//resequenced as well
function arrayReduce(arr) {

    var newarr = new Array();
    var len = arr.length;
    var c = 0;

    for (i=0;i<len;i++) {

        if (arr[i].length > 0) {
            newarr[c] = arr[i];
            c++;
        }

    }

    return newarr;

}

//close a window and refresh the parent.
function selfClose() {

	var url = window.opener.location.href;
	window.opener.location.href = url;
	window.opener.focus();
	self.close();

}


function openModuleWindow(module,objectId,width,height) {

        if (!width) width = "600";
        if (!height) height = "500";

        parm = centerParms(width,height,1) + ",status=yes,scrollbars=yes";

        url = "index.php?module=" + module + "&objectId=" + objectId;
        nw = window.open(url,"_modulewin",parm);
        nw.focus();

}

function openModalWindow(module,objectId,width,height) {

        if (!width) width = "600";
        if (!height) height = "500";

        winparm = "toolbar=0,location=0,status=0,menubar=0,scrollbars=0,resizable=0";
        parm = centerParms(width,height,1) + "," + winparm;
        url = "index.php?module=" + module + "&objectId=" + objectId;
        nw = window.open(url,"_modalwin",parm);
        nw.focus();

}


//pause script execution for the specified milliseconds
function pause(numberMillis) {
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true) {
        now = new Date();
        if (now.getTime() > exitTime)
            return;
    }
}

//show our current site status
function updateSiteStatus(msg) {

	var ss = ge("siteStatus");

	var xPos = (getWinWidth() - 300) / 2;

  if (BROWSER=="safari") var st = window.scrollY;
  else var st = getScrollTop();

	ss.style.left = xPos + "px";
	ss.style.top = st + "px";
	ss.style.display = "block";
	ge("siteStatusMessage").innerHTML = msg;

}

//clear the site status
function clearSiteStatus() {
	ge("siteStatus").style.display = "none";
	ge("siteStatus").style.top = "0";
	ge("siteStatus").style.left = "0";
	ge("siteStatusMessage").innerHTML = "";
}


function tempSiteStatus(msg) {

  updateSiteStatus(msg);
  setTimeout("clearSiteStatus()","3000");

}

//sort a multi dimensional array by desired key
function arrayMultiSort(arr,sort_key) {

	var newarr = new Array();
	var sortkey = new Array();

	//split our array into those w/ keys and w/o keys
	var fullsort = new Array();
	var emptysort = new Array();

	for (var i=0; i<arr.length; i++) {

		if (arr[i][sort_key]) {
			fullsort.push(arr[i]);
			sortkey.push(arr[i][sort_key]);
		}
		else emptysort[i].push(arr[i]);

	}

	//sort the key array
	sortkey.sort();

	//recreate our new array with the sort elements
	for (var i=0;i<sortkey.length;i++) {

		//assemble in the correct order
		for (c=0;c<fullsort.length;c++) {

			if (fullsort[c][sort_key]==sortkey[i]) {
				newarr.push(fullsort[c]);
				break;
			}
	
		}

	}

	//now add the elements w/o keys
	for (var i=0; i<emptysort.length;i++) newarr.push(emptysort[i]);
	
	return newarr;

}

function bitset_compare(bit1,bit2,admin) {

	return perm_check(bit2);

}

function perm_check(bitpos)
{

	var auth = perm_isset(BITSET,bitpos);

	if (!auth) auth = perm_isset(BITSET,ADMIN);

	return auth;

}

function perm_isset(bitmask,bitpos)
{

	var auth = false;

	var check = bitmask.reverse();

	//we are passed the position of the bit to check
	if (check.charAt(bitpos)=="1") auth = true;
	
	return auth;

}

function bit_comp(bit1,bit2)
{

  var auth = false;

  if ( parseInt(bit1) & parseInt(bit2) ) auth = true;

	return auth;

}


/*******************************************
	these two functions require mootools.js
*******************************************/
function getWinWidth() {

	var width = 0;

	if (window.getWidth) width = window.getWidth();
	else {

		if (document.all) width = document.body.offsetWidth;
		else width = window.innerWidth;

	}

	return width;

}

function getWinHeight() {

	var height = 0;

	if (window.getHeight) height = window.getHeight();
	else {

		if (document.all) height = document.body.offsetHeight;
		else height = window.innerHeight;

	}
	
	return height;

}

function microtime_calc() {
        list($msec, $sec) = explode(" ",microtime());
        return $msec + $sec;
}


function bitCal(limit) {

		limit = parseInt(limit);

    var num = 1;

    for (var i=0;i<limit;i++) {
        if (limit!=0) num = num * 2;
    }

    return num;
}

function revBitCal(limit) {

		limit = parseInt(limit);
    var counter = 0;

    while (limit!=1) {

        counter++;
        limit = limit/2;

    }

    return counter;

}

/******************************************************************
  FUNCTION: closeKeepAlive
  PURPOSE:  this is a hack to prevent uploads from hanging on
            webkit browsers.  apparently a bug in OS X
******************************************************************/
function closeKeepAlive() {
  if (/AppleWebKit|MSIE/.test(navigator.userAgent)) {
		protoReqSync(SITE_URL + "controls/ping.php");
  }
}

/********************************************************************
	Functions for creating or manipulating DOM objects

	created: 04/20/2006

********************************************************************/

//set a floatStyle for an object
function setFloat(myvar,floatVal) {

        myvar.setAttribute("style","float:" + floatVal);

        return myvar;
}

//set a className value for an object
function setClass(myvar,classVal) {

        myvar.setAttribute("class",classVal);
        return myvar;
}

//get a className value for an object
function getObjClass(myvar) {

        return myvar.getAttribute("class");

}

//set an onclick event for an object
function setClick(myvar,click) {

        myvar.setAttribute("onClick",click);

        return myvar;

}

//set an onclick event for an object
function setDblClick(myvar,click) {

        myvar.setAttribute("onDblClick",click);

        return myvar;

}

//set an onclick event for an object
function setMouseDown(myvar,click) {

        myvar.setAttribute("onMouseDown",click);

        return myvar;

}
//set an onclick event for an object
function setMouseUp(myvar,click) {

        myvar.setAttribute("onMouseUp",click);

        return myvar;

}

//set an onclick event for an object
function setMouseOver(myvar,click) {

        myvar.setAttribute("onMouseOver",click);

        return myvar;

}

//set an onclick event for an object
function setMouseOut(myvar,click) {

        myvar.setAttribute("onMouseOut",click);

        return myvar;

}

// IE ONLY
function setMouseEnter(myvar,click) {

	myvar.setAttribute("onMouseEnter",click);
  return myvar;

}

// IE ONLY
function setMouseLeave(myvar,click) {

	myvar.setAttribute("onMouseLeave",click);
  return myvar;

}

//create a new form
function createForm(formType,formName,checked) {

	var curform = ce("input");
	curform.setAttribute("name",formName);
	curform.setAttribute("type",formType);
	curform.setAttribute("id",formName);
	if (checked) curform.checked = true;

	return curform;

}

//create a new form
function createSelect(formName,change,dataArr,curVal) {

	var curform;

	var curform = document.createElement("select");
	curform.setAttribute("name",formName);
	curform.setAttribute("id",formName);
	if (change) curform.setAttribute("onChange",change);

	//add data and a curvalue
	if (dataArr && dataArr.length > 0) 
	{

		for (var i=0;i<dataArr.length;i++) {

			curform[i] = new Option(dataArr[i]["name"],dataArr[i]["value"]);

		}
	
	}

	if (curVal) curform.value = curVal;

	return curform;

}

//set an onclick event for an object
function setChange(myvar,click) {

        myvar.setAttribute("onChange",click);

        return myvar;

}

//set an onclick event for an object
function setKeyUp(myvar,click) {

        myvar.setAttribute("onkeyup",click);

        return myvar;

}

//set an onclick event for an object
function setFocus(myvar,click) {

        myvar.setAttribute("onfocus",click);

        return myvar;

}

//set an onclick event for an object
function setBlur(myvar,click) {

        myvar.setAttribute("onblur",click);

        return myvar;

}

//shorthand for getElementbyId
function ge(element) 
{
  return $(element);
}
   
//shorthand for creating an element
function ce(elementType,elementClass,elementId,txt) {

  var elem = new Element(elementType);
  
  //document.createElement(elementType);

  //add optional parameters
  if (elementId) elem.setAttribute("id",elementId);
  if (elementClass) setClass(elem,elementClass);

  //append extra text.  If passed an object, append with without the textnode wrapper
  if (isData(txt)) {
    if (typeof(txt)=="object") elem.appendChild(txt);
    else elem.appendChild(ctnode(txt));
  }

  return elem;

}

function createCleaner(fill) {

  var cleaner = document.createElement("div");
  setClass(cleaner,"cleaner");

  if (fill) cleaner.appendChild(createNbsp());

  return cleaner;

}

//shorthand for creating a text node
function ctnode(str) {
	return document.createTextNode(str);
}
 
function changeClass(id,section) {
	document.getElementById(id).className = section;
}


//hide an object from view
function hideObject(obj) {

        document.getElementById(obj).style.position="absolute";
        document.getElementById(obj).style.visibility="hidden";
        document.getElementById(obj).style.zIndex="-10";
				document.getElementById(obj).style.display="none";

}

//show an object in the browser
function showObject(obj,zIndex) {

	if (!zIndex) zIndex = 1;

        document.getElementById(obj).style.position="static";
        document.getElementById(obj).style.visibility="visible";
				document.getElementById(obj).style.display="block";
        document.getElementById(obj).style.zIndex=zIndex;

}

//cycle between hide and show
function cycleObject(obj,zIndex) {

        var visib = document.getElementById(obj).style.visibility;

        if (visib=="visible") hideObject(obj);
        else showObject(obj,zIndex);

}

/******* some better versions of the above functions ********/
function showObj(obj) {
	ge(obj).style.display = "block";	
}

function hideObj(obj) {
	ge(obj).style.display = "none";	
}

function cycleObj(obj) {

	var visib = ge(obj).style.display;
	if (visib=="block") hideObj(obj);
	else showObj(obj);

}

//calculates the left offset of an object
function calculateOffsetLeft(r){
        return Ya(r,"offsetLeft");
}

//calcuates teh top offset of an object
function calculateOffsetTop(r){
        return Ya(r,"offsetTop");
}

//does the legwork on offset calcuation
function Ya(r,attr) {
        var kb=0;
        while(r){
                kb+=r[attr];
                r=r.offsetParent;
        }
        return kb;
}

//returns the value of a radio form
function getRadioValue(name,obj) {

        if (!obj) return "";

				var setval = "";

				//loop through area, get form with right name and see if it's checked at all
				var arr = obj.getElementsByTagName("input");

				for (var i=0;i<arr.length;i++) {

					if (arr[i].type=="radio" && arr[i].id==name && arr[i].checked==true) {
						setval = arr[i].value;
						break;
					}

				}

				return setval;

}

function createTextbox(formName,curVal,formLen) {

        //create the base form
        curform = createForm("text",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 
        if (formLen) curform.setAttribute("size",formLen);

        return curform;
 
}

function createHidden(formName,curVal) {

        //create the base form
        curform = createForm("hidden",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 

        return curform;
 
}

function createPassword(formName,curVal,formLen) {

        //create the base form
        curform = createForm("password",formName);

        //set our attributes
        if (curVal) curform.setAttribute("value",curVal); 
        if (formLen) curform.setAttribute("size",formLen);

        return curform;
 
}

function createRadio(formName,formVal,curVal,clicker) {

        var checked;

        if (curVal && curVal == formVal) checked = 1;
        else checked = null;

        curform = createForm("radio",formName,checked);
        curform.setAttribute("value",formVal);

				if (clicker) setClick(curform,clicker);

        return curform;
 
}

function createRadioDiv(formName,formVal,curVal,txt,cn,oc) {

        var checked;

				var mydiv = ce("div");
				if (cn) setClass(mydiv,cn);

        curform = createRadio(formName,formVal,curVal);
				if (oc) setClick(curform,oc);

				mydiv.appendChild(curform);
				if (txt) mydiv.appendChild(ctnode(txt));

        return mydiv;
 
}

function createCheckbox(formName,formVal,curVal,clicker) {

        var checked;

        if (curVal && curVal == formVal) checked = 1;
        else checked = null;

        curform = createForm("checkbox",formName,checked);
        curform.setAttribute("value",formVal);

				if (clicker) setClick(curform,clicker);

        return curform;

}
 
function createTextarea(formName,curVal,rows,cols) {

        var curform = document.createElement("textarea");
        curform.setAttribute("name",formName);
        curform.setAttribute("id",formName);  

        if (rows) curform.setAttribute("rows",rows);
        if (cols) curform.setAttribute("cols",cols);

        if (curVal) curform.value = curVal;

        return curform;

}

function createBtn(formName,val,oc) {

        var btn = createForm("button",formName);
        btn.setAttribute("value",val);

        if (oc) setClick(btn,oc);

        return btn;

}

function setZIndex(objName,zIndex) {

        var obj = ge(objName);
        obj.style.zIndex = zIndex;
 
}

//create a table
function createTable(tableName,className,width,border,cellpadding,cellspacing) {

	if (!border) border = "0";
	if (!cellpadding) cellpadding = "0";
	if (!cellspacing) cellspacing = "0";

	var tbl = ce("table",className,tableName);

	//set our main attributes
	tbl.setAttribute("border",border);
	tbl.setAttribute("cellpadding",cellpadding);
	tbl.setAttribute("cellspacing",cellspacing);

	if (width) tbl.setAttribute("width",width);

	return tbl;

}

function createTableCell(cellName,className,rowspan,colspan) {

	var cell = ce("td");
	if (cellName) cell.setAttribute("id",cellName);
	if (className) setClass(cell,className);
	if (rowspan) cell.setAttribute("rowspan",rowspan);
	if (colspan) cell.setAttribute("colspan",colspan);

	return cell;

}

//removes all child nodes within an element
function clearElement(el) {

		if (!el) return false;

		if (!el.hasChildNodes) return false;

		while (el.hasChildNodes()) {
			el.removeChild(el.firstChild);
		}

}

//replaces content of an element with passed data
function setElement(elem,data) {

	//clear out the insides
	clearElement(elem);

	//append extra text.  If passed an object, append with without the textnode wrapper
	if (isData(data)) {
		if (typeof(data)=="object") elem.appendChild(data);
		else elem.appendChild(ctnode(data));
	}

}

//css
function loadStylesheet(csspath) {
	var oLink = document.createElement("link");
	oLink.href = csspath;
	oLink.rel = "stylesheet";
	oLink.type = "text/css";
	document.getElementsByTagName("head")[0].appendChild(oLink);
}

//javascript
function loadJavascript(jspath) {
	
	var e = document.createElement("script");
	e.src = jspath;
	e.type="text/javascript";
	document.getElementsByTagName("head")[0].appendChild(e); 

}

function createDateSelect(name,title,val) {

	var divname = name + "Div";
	var cell = ce("div","popupCell",divname);
  var head = ce("div","formHeader","",title);
  var form = createTextbox(name);
  form.setAttribute("size","10");
	if (val) form.value = val;

  var btn = createBtn(name + "_btn","...");

  if (!window.Calendar) {
    alert("You must include the calendar javascript file");
  }
   
  Calendar.setup({
          inputField      :    form,
          ifFormat        :   "%m/%d/%Y",
          button          :    btn,
          singleClick     :    true,           // double-click mode
          step            :    1                // show all years in drop-down boxes (instead of every other year as default)
      });


  cell.appendChild(head);
  cell.appendChild(form);
  cell.appendChild(btn); 

  return cell;

}

function getEventSrc(elem) {
  var elem = elem || window.event;
  return elem.target || elem.srcElement;
}


function createMethodReference(object, methodName,parms) {
    return function () 
		{
				return object[methodName].apply(object, arguments);
    };
};

function getScrollTop() {

	if (document.documentElement) return document.documentElement.scrollTop;
	else return document.body.scrollTop;

}

function getScrollLeft() {

	if (document.documentElement) return document.documentElement.scrollLeft;
	else return document.body.scrollLeft;

}

function createImg(src,clicker,title) {

	var img = ce("img");
	img.setAttribute("src",src);

	if (clicker) setClick(img,clicker);
	if (title) img.setAttribute("title",title);

	return img;	

}

function createLink(txt,dest) 
{
 
	var link = document.createElement("a");
	if (dest) link.setAttribute("href",dest);
	link.appendChild(ctnode(txt));

  return link;

}

function createNbsp()
{
  return ctnode(String.fromCharCode(160));
}



/*************************************************************
	generic code for processing ajax requests
*************************************************************/



//makes sure there's data in the field
function isData(data) {

	if (!data) return false;

	var data = data.toString();		//cast it as a string
	data = data.trim();				//remove any whitespace

	if (data && data.length > 0) return true;
	else return false;

}

//this function enables all forms in the area
function enableForms(mydiv,ignore) {

	var str = "";
	var ignorestr = ",";

	//get our supported form types
	var sel = mydiv.getElementsByTagName("select");
	var input = mydiv.getElementsByTagName("input");
	var ta = mydiv.getElementsByTagName("textarea");
	var i;


	//convert ignore into a string
	if (ignore) for (i=0;i<ignore.length;i++) ignorestr += ignore[i] + ",";

	//process selects
	for (i=0; i<sel.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + sel[i].name + ",")!=-1) continue;
		sel[i].disabled=false;

	}

	//process textarea
	for (i=0;i<ta.length;i++) {
		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + ta[i].name + ",")!=-1) continue;
		ta[i].disabled = false;
	}

	//process the rest
	for (i=0;i<input.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + input[i].name + ",")!=-1) continue;

		if (input[i].type=="button" || input[i].type=="submit") input[i].disabled = false;
		else input[i].disabled = false;

	}

}


function disableForms(mydiv,ignore) {

	var str = "";
	var ignorestr = ",";

	//get our supported form types
	var sel = mydiv.getElementsByTagName("select");
	var input = mydiv.getElementsByTagName("input");
	var ta = mydiv.getElementsByTagName("textarea");
	var i;


	//convert ignore into a string
	if (ignore) for (i=0;i<ignore.length;i++) ignorestr += ignore[i] + ",";

	//process selects
	for (i=0; i<sel.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + sel[i].name + ",")!=-1) continue;
		sel[i].disabled=true;

	}

	//process textarea
	for (i=0;i<ta.length;i++) {
		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + ta[i].name + ",")!=-1) continue;
		ta[i].disabled = true;
	}

	//process the rest
	for (i=0;i<input.length;i++) {

		//skip if it's in our ignore array
		if (ignorestr.indexOf("," + input[i].name + ",")!=-1) continue;

		if (input[i].type=="button" || input[i].type=="submit") input[i].disabled = true;
		else input[i].disabled = true;

	}

}


//runs a function when all the ajax requests are finished
function endReq(func,ms) 
{

	if (!ms) ms = "250";
	reqCheckTimer = setInterval("checkAjaxStatus()",ms);	
	reqEndFunc = func;

}

function checkAjaxStatus() 
{

	//if requests = 0, we're done
	if (ajaxReqNum==0) 
	{
		clearInterval(reqCheckTimer);
		eval(reqEndFunc);		
	}

}

//this function will load an external javascript file and parse it.  Generally, this
//is done when a page originally loads, but this allows us to load external
//scripts on the fly
function loadScript(fullUrl) 
{

        // Mozilla and alike load like this
        if (window.XMLHttpRequest) {
                req = new XMLHttpRequest();
                //FIXXXXME if there are network errors the loading will hang, since it is not done asynchronous since
                // we want to work with the script right after having loaded it
                req.open("GET",fullUrl,false); // true= asynch, false=wait until loaded
                req.send(null);
        } else if (window.ActiveXObject) {
                req = new ActiveXObject((navigator.userAgent.toLowerCase().indexOf('msie 5') != -1) ? "Microsoft.XMLHTTP" : "Msxml2.XMLHTTP");
                if (req) {
                        req.open("GET", fullUrl, false);
                        req.send();
                }
        }

        if (req!==false) {
                if (req.status==200) {
                        // eval the code in the global space (man this has cost me time to figure out how to do it grrr)
												return req.responseText;
                } else if (req.status==404) {
                        // you can do error handling here
												alert("Page not found");
                }

        }

}


/*********************************************************************
	PROTO Shortcut Functions
*********************************************************************/

//handles our xml requests for getting data
function protoReq(url,callback,reqMode) 
{

	var p = new PROTO("QUERY");

	if (reqMode=="POST") p.post(url,callback);
	else p.get(url,callback);

}

function protoReqSync(url,reqMode) 
{

	var p = new PROTO("QUERY");
	p.setAsync(false);

	if (reqMode=="POST") var ret = p.post(url);
	else var ret = p.get(url);

	return ret;

}

//handles our xml requests for getting data
function postReq(url,callback) 
{

	protoReq(url,callback,"POST");

}

//handles our xml requests for getting data
function getReq(url,callback) 
{

	protoReq(url,callback,"GET");

}

function protoRedirect(url) 
{

	var p = new PROTO("QUERY");
	p.redirect(url,reqMode);

}

function dom2Query(cont,ignore) 
{

	var p = new PROTO("QUERY");
	return p.encodeDOM(cont,ignore);

}

function dom2Array(cont,ignore) 
{

  var p = new PROTO();
  return p.traverse(cont,ignore);

}

/*************************************************************************
	end PROTO shortcut functions
*************************************************************************/

/********************************************************************
	legacy functions
********************************************************************/

function loadReq(url,callback,reqMode)
{
	protoReq(url,callback,reqMode);
}

function loadReqSync(url,reqMode)
{
	return protoReqSync(url,reqMode);
}

//data is already converted, just return as is
function parseXML(resp)
{
	return resp;
}

function loadXMLReq()
{

	alert("This function has been depreciated.  Please use protoReq(url,callbackFuncName,requestMode)");

}

/***********************************************************************
	Local Storage Functions
***********************************************************************/

Storage.prototype.setObject = function(key, value) {

    this.setItem(key, JSON.stringify(value));
};
 
Storage.prototype.getObject = function(key) {

    return JSON.parse(this.getItem(key));

};

//setup setString for consistency sake (setObject & setString)
Storage.prototype.setString = function(key, value) {
    this.setItem(key, value);
};
 
Storage.prototype.getString = function(key) {
    return this.getItem(key);
};

Element.prototype.insertAfter = function(newNode, refNode) {
	if(refNode.nextSibling) {
		return this.insertBefore(newNode, refNode.nextSibling);
	} else {
		return this.appendChild(newNode);
	}
};

function setupScroller()
{
	
	if (BROWSERMOBILE==true)
	{
		document.addEventListener('touchmove', function(elem){ elem.preventDefault(); });
	}

}

function isLoggedIn()
{
  var ret = false;
  if (parseInt(USER_ID)==USER_ID) ret = true;
  return ret;
}

function supportsFileAPI()
{

  var f = ce("input");
  f.setAttribute("type","file");

  if (f.files) return true;
  else return false;

}

