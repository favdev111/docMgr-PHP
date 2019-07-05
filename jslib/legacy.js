
/*************************************************************
	generic code for processing ajax requests
*************************************************************/

//the number of simultaenous asynchronous requests we can have
var ajaxReqNum = 0;
var reqCheckTimer;
var reqEndFunc;

//checks to see if the element has a text value, or if there are children below it to go through
function hasChildNodes(obj) 
{

	if (!obj) return false;

	if (document.all) 
	{

		if (obj.firstChild && obj.firstChild.nodeValue) return false;
		else return true;

	} else 
	{

		if (obj.childNodes.length==1) return false;
		else return true;

	}

}


/**********************************************
	parse our xml into an associative array
	takes the top node as a reference
	ex:
	resp = req.responseXML;
	arr = parseXML(resp.firstChild);
**********************************************/

function parseXML(dataNode,curname) {

	if (!dataNode.childNodes) 
	{
		alert(dataNode);
		return false;
	}

	var len = dataNode.childNodes.length;
	var arr = new Array();
	var keyarr = new Array();

	var n=0;
	var i = 0;

	while (dataNode.childNodes[i]) 
	{

		var objNode = dataNode.childNodes[i];

		if (objNode.nodeType==1) 
		{

			var keyname = objNode.nodeName;

			if (objNode.hasChildNodes()) 
			{

				//if the key does not exist in our key array, added it and reset its counter
				if (!keyarr[keyname]) 
				{
					keyarr[keyname] = 0;
					arr[keyname] = new Array();
				}

				n = keyarr[keyname];

				arr[keyname][n] = new Array();

				//store single length nodes here
				if (!hasChildNodes(objNode)) 
				{
	
					//if already exists, convert to an array and add new entry
					if (isData(arr[keyname])) 
					{

						if (typeof(arr[keyname])!="object") arr[keyname] = new Array(arr[keyname]);
						arr[keyname].push(objNode.firstChild.nodeValue);

					//just store regular entry
					} else 
					{
						 arr[keyname] = objNode.firstChild.nodeValue;
					}

				} else 
				{

					var c = 0;
					while (objNode.childNodes[c]) 
					{

						var curNode = objNode.childNodes[c];
						var curName = curNode.nodeName;

						//only continue on nodes that are elements
 						if (curNode.nodeType==1) 
						{

							//there are nested tags here, get them
							if (hasChildNodes(curNode)) 
							{

								//what will our next iteration be
								if (!arr[keyname][n][curName]) arr[keyname][n][curName] = new Array();

								//add children to our new parent
								if (curNode.childNodes.length > 0) arr[keyname][n][curName].push(parseXML(curNode,curName));
		
							//just store as text
							} else 
							{

								//if already exists, convert to an array and store the new entry
								if (isData(arr[keyname][n][curName])) {

									if (typeof(arr[keyname][n][curName])!="object") arr[keyname][n][curName] = new Array(arr[keyname][n][curName]);
									arr[keyname][n][curName].push(curNode.firstChild.nodeValue);

								//just store as single text
								} else arr[keyname][n][curName] = curNode.firstChild.nodeValue;

							}

						}

						c++;

					}

				}

				keyarr[keyname]++;

			}

		}

		i++;

	}

	return arr;

}

function loadReqSync(fullUrl) {

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
												if (req.responseXML) {
													//get the root node and return it
  												for (z=0;z<req.responseXML.childNodes.length;z++) {
    												if (req.responseXML.childNodes[z].nodeType==1) { 
      													return req.responseXML.childNodes[z];   
    												}
  												}  
												}
												else return req.responseText;
                } else if (req.status==404) {
                        // you can do error handling here
												alert("Page not found");
                }

        }

}

/********************************************************************************************************
	FUNCTION: div2Query
	PURPOSE:	this function converts all inputs/selects in a div to aquery string to be passed to a server
						as a get or post.  The var docForm should be a reference to an dom object.  "ignore" is
						an optional array containing the names of fields you may want to ignore
	HISTORY:	updated 12/08/2008.  updated to respect the order the forms appear in the document
********************************************************************************************************/
function div2Query(cont,ignore) 
{

	var arr = traverseNodes(cont,ignore);
	var str = "";

	for (var i=0;i<arr.length;i++) {

		str += arr[i][0] + "=" + escape(arr[i][1]) + "&";

	}

	// Remove trailing separator
	if (str) str = str.substr(0, str.length - 1);

	return str;

}

//workhorse for div2Query and div2xml
function traverseNodes(cont,ignore) 
{

	var nodes = cont.childNodes;
	var ret = new Array();

	for (var i=0;i<nodes.length;i++) 
	{

			if (nodes[i].nodeName=="SELECT" || nodes[i].nodeName=="INPUT" || nodes[i].nodeName=="TEXTAREA") 
			{

				//skip if it's in our ignore array
				if (isData(ignore)) 
				{
					var key = arraySearch(nodes[i].name,ignore);
					if (key!=-1) continue;
				}

				//handle select dropdowns.  handles single and multiple selects
				if (nodes[i].nodeName=="SELECT") {

					for (var c=0;c<nodes[i].options.length;c++) {

						if (nodes[i].options[c].selected==true) {
							ret.push(new Array(nodes[i].name,nodes[i].options[c].value));
						}

					}

				//textareas
				} else if (nodes[i].tagName=="TEXTAREA") {

					ret.push(new Array(nodes[i].name,nodes[i].value));

				//all other forms
				} else {

					//skip buttons for now
					if (nodes[i].type=="button") continue;
	
					//process radios and checkboxes
					if (nodes[i].type=="checkbox" || nodes[i].type=="radio") {
						if (nodes[i].checked) ret.push(new Array(nodes[i].name,nodes[i].value));
					}
					//everything else
					else {
						ret.push(new Array(nodes[i].name,nodes[i].value));
					}

				}

			//not a form
			} else {

				//if there's something below here, run through it
				if (nodes[i].childNodes.length>0) {

					//get result arrays below this and merge to return array
					var temp = traverseNodes(nodes[i],ignore);
					for (var c=0;c<temp.length;c++) ret.push(temp[c]);

				}

			}

	}

	return ret;

}

function handleReqErrors(req) 
{

		//evaluate if there is no xml response, and there is a text response, contine and parse the text response
 		if (		(!req.responseXML || 
						(req.responseXML && !req.responseXML.hasChildNodes()) || req.responseXML.childNodes.length=="0")	&&
						req.responseText.length > 0) {

			//check for login message
			var loginCheck = req.responseText.indexOf("<!--EDEV LOGIN-->");
			if (loginCheck >= 0) {

				alert("Your session has expired, you will now be redirected to the login page");
				location.href = "index.php?show_login_form=1";

			} else {

				//parse error check
				var peCheck = req.responseText.indexOf("Parse error:");

				//script warning check
				var wCheck = req.responseText.indexOf("Warning:");

				if ((peCheck !=-1) || (wCheck!=-1)) {
					if (confirm("There was an error loading the page.  Do you wish to see the error text?")) {
						alert(req.responseText);
					}
				} else return true;

			}
			
			//if in this bracket set there was a problem
			return false;

		} else return true;


}

//parses xml response and hands it off to the appropriate function if it exists
function handleResponse(req,callback) 
{

	//check for errors.  bail if there are some
	if (!handleReqErrors(req)) return false;

	var respXML = req.responseXML;
	var respTXT = req.responseText;
	var z = 0;

	//get out if no callback
	if (!callback) return false;

	//determine if there's no xml info available.  This seems redundant, but the last 
	//check makes this work with opera
 	if (!respXML || (respXML && !respXML.hasChildNodes()) || respXML.childNodes.length=="0") 
	{

		//if there's no text either, get out
		if (!respTXT) return false;

		//if passed a function object, use it instead of evaluating the function first
		if (typeof(callback)=="object") func = callback;
		else func = eval(callback);
		func(respTXT);

	} else 
	{

		//if passed a function object, use it instead of evaluating the function first
		if (typeof(callback)=="object") func = callback;
		else func = eval(callback);

    //only pass on an element to our handler function
    for (z=0;z<respXML.childNodes.length;z++) 
		{
      if (respXML.childNodes[z].nodeType==1) 
			{
        func(respXML.childNodes[z],respTXT);      //pass respTXT for debugging purposes
        break;
      }
    }

	}

	//just return true if we make it to here
	return true;

}

//handles our xml requests for getting data
function loadReq(url,callback,reqMode,noCache) 
{

	var xmlreq = null;
	var parms = null;
	var openIndex = 0;
	var req;

	//we are running a request, increment our count
	ajaxReqNum++;

	//default to GET if reqMode is not set
	if (!reqMode) reqMode = "GET";

	//our callback function for processing the return from our xml request
	callBackFunc = function xmlHttpChange() {

					if (req.readyState == 4) {

						//the request is finished, decrement the count
						ajaxReqNum--;

						switch (req.status) {
							
							case 200:

								//handle the response
								handleResponse(req,callback);
								break;

							case 401:
								alert("Error 401 (Unauthorized):  You are not authorized to view this page");
								break;

							case 402:
								alert("Error 402 (Forbidden): You are forbidden to view this page");
								break;

							case 404:
								alert("Error 404 (Not Found): The requested page was not found. \n" + url);
								break;

						}

				}

			};

	//if it's a post, we need to strip the parameters out of the url
	//get a new request depending on ie or standard
	if (window.XMLHttpRequest) 			req = new XMLHttpRequest();
	else if (window.ActiveXObject) 	req = new ActiveXObject("Microsoft.XMLHTTP");

	//prevent xml file caching in ie
	if (url.indexOf(".xml") != '-1') {
		var time = new Date().getTime();
		if (url.indexOf("?") != '-1') url += "&" + time;
		else url += "?" + time;
	}

	//if it's a post method, we need to split our url into parameters and the url destination itself
	if (reqMode=="POST") {
		var pos = url.indexOf("?");
		if (pos!=-1) {
			parms = url.substr(pos + 1);
			url = url.substr(0,pos);
		}
	}		

	//register our callback function
	req.onreadystatechange = callBackFunc;

	//open the connection
  req.open(reqMode, url, true);

  //if it's a post, send the proper header
  if (reqMode=="POST") req.setRequestHeader("Content-type","application/x-www-form-urlencoded");

	//prevent caching if set
	if (noCache) req.setRequestHeader("If-Modified-Since", "Sat, 1 Jan 2005 00:00:00 GMT");

	//send the parameters
	req.send(parms);

}

function cdata(elementType,txt) {

  var xml = "<" + elementType + "><![CDATA[" + escape(txt) + "]]></" + elementType + ">";
  return xml
 
}
