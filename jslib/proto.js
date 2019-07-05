
/******************************************************************
	CLASS:	PROTO
	PURPOSE:	wrapper for handling AJAX requests.  Can handle all
						the encoding and decoding of requests and responses.
						Also allows us to swap for a different javascript
						request library later
	INPUTS:		protocol => XML, JSON, or QUERY
******************************************************************/
//the number of simultaenous asynchronous requests we currently have going
var ajaxReqNum = 0;
		
function PROTO(pm)
{

	this.PM = "JSON";						//transfer protocol.  XML,JSON,QUERY
	this.REQMODE = "GET";				//request mode.  GET or POST
	this.ASYNC = true;					//asynchronous request mode
	this.DATA = new Object();		//array to store data to post.  I use Object instead of Array because JSON.encode has problems with array
	this.DATANUM = 0;						//counter for data members
	this.DEBUG = false;
	this.DECODE = true;
	this.LOGIN;
	this.PASSWORD;
	this.SAVECOOKIE = false;

	//if passed the protocol, use that.  Otherwise use the config option.
	//if that's not set then use JSON
	if (pm) this.PM = pm;		

	/****************************************************************
		FUNCTION:	setProtocol
		PURPOSE:	sets our transfer protocol
		INPUTS:		pm -> "JSON", "QUERY", or "XML"
	****************************************************************/
	this.setProtocol = function(pm)
	{

		this.PM = pm;

	};

	/****************************************************************
		FUNCTION:	setRequestMode
		PURPOSE:	sets our request mode
		INPUTS:		m -> "GET" or "POST"
	****************************************************************/
	this.setRequestMode = function(m)
	{

		this.REQMODE = m;

	};


	/****************************************************************
		FUNCTION:	setDecode
		PURPOSE:	sets whether or not to decode request responses
							into an array
		INPUTS:		m -> true or false
	****************************************************************/
	this.setDecode = function(m)
	{

		this.DECODE = m;

	};

	/****************************************************************
		FUNCTION:	setAsync
		PURPOSE:	sets the request to be asynchronous or synchronous
		INPUTS:		as -> true for async, false for sync
	****************************************************************/
	this.setAsync = function(as)
	{
		
		this.ASYNC = as;

	};

	/****************************************************************
		FUNCTION:	debug
		PURPOSE:	sets mode for debugging.  changes request method
							behavior
		INPUTS:		db ->	true: shows url and query data before submit
										false: disables debugging
										nosend: shows query, and doesn't send request
	****************************************************************/
	this.debug = function(db)
	{
		
		this.DEBUG = db;

	};

	/****************************************************************
		FUNCTION:	setData
		PURPOSE:	sets our DATA array to the input array
		INPUTS:		data (array) -> array of data to post during request
	****************************************************************/
	this.setData = function(data)
	{

		//decode if it's a string
		if (typeof(data)=="string") this.DATA = this.decode(data);
		else this.DATA = data;

		this.DATANUM++;				//just has to be not 0

	};

	/****************************************************************
		FUNCTION:	getData
		PURPOSE:	gets our DATA array to the input array
		INPUTS:		none
	****************************************************************/
	this.getData = function()
	{

		return this.DATA;

	};

	/****************************************************************
		FUNCTION:	add
		PURPOSE:	adds a new member to the DATA array to be sent
							with our request
		INPUTS:		key (string) -> form name
							data (string,array) -> form value
	****************************************************************/
	this.add = function(key,data,replace)
	{

		if (this.DATA[key] && !replace)
		{

			//if it's an array, just add to it.  otherwise create an array
			if (typeof(this.DATA[key])=="object")
			{
				this.DATA[key].push(data);
			} 
			else
			{
				//recreate entry as an array and add new value to it
				var tmp = this.DATA[key];
				this.DATA[key] = new Array(tmp);
				this.DATA[key].push(data);
			}

		} 
		else 
		{

			if (typeof(data)=="object" && isData(data)) 
			{

				//if it's already a proper array, add normally.  otherwise enclose in an array
				if (data.length) this.DATA[key] = data;
				else this.DATA[key] = new Array(data);

			} 
			else this.DATA[key] = data;

		}

		this.DATANUM++;

	};

	/****************************************************************
		FUNCTION:	addDOM
		PURPOSE:	adds the values of all forms in a DOM container 
							(div,form,whatever) to our DATA array
		INPUTS:		cont -> container to search
							ignore (array) -> forms to skip over
	****************************************************************/
	this.addDOM = function(cont,ignore)
	{

		var arr = this.traverse(cont,ignore);		

		//merge our results into the master data object
		for (var key in arr)
		{
			//just like before,  if it exists, make or add to the array, otherwise just add the value
			if (this.DATA[key])
			{
				if (typeof(this.DATA[key])=="object") 
				{
					this.DATA[key].combine(arr[key]);
				}
				else this.DATA[key] = new Array(this.DATA[key],arr[key]);
			} 
			else 
			{
				this.DATA[key] = arr[key];
			}

		}

	};

	/****************************************************************
		FUNCTION:	addString
		PURPOSE:	adds an already encoded string into our DATA array
		INPUTS:		str -> encoding string to add
	****************************************************************/
	this.addString = function(str)
	{
		this.addArray(this.decode(str));
	};

	/****************************************************************
		FUNCTION:	addArray
		PURPOSE:	adds an array into our DATA array
		INPUTS:		str -> encoding string to add
	****************************************************************/
	this.addArray = function(arr)
	{
		for (var key in arr)
		{
			this.add(key,arr[key]);
		}
	};


	/****************************************************************
		FUNCTION:	encodeDOM
		PURPOSE:	gets the values of all forms in a DOM container 
							(div,form,whatever) and encodes them to the current
							protocol
		INPUTS:		cont -> container to search
							ignore (array) -> forms to skip over
	****************************************************************/
	this.encodeDOM = function(cont,ignore)
	{

		var arr = this.traverse(cont,ignore);		
		return this.encode(arr);

	};

	/****************************************************************
		FUNCTION:	get
		PURPOSE:	submits the contents of DATA to the url
							provided using a GET request
		INPUTS:		url -> url to send DATA to
							callback -> function to call on successful response
	****************************************************************/
	this.get = function(url,callback)
	{

		this.REQMODE = "GET";
		return this.request(url,callback);

	};

	/****************************************************************
		FUNCTION:	post
		PURPOSE:	submits the contents of DATA to the url
							provided using a POST request
		INPUTS:		url -> url to send DATA to
							callback -> function to call on successful response
	****************************************************************/
	this.post = function(url,callback)
	{
		this.REQMODE = "POST";
		return this.request(url,callback);

	};

	this.setupMessage = function()
	{

		//setup the header
		var header = new Object();

		//already authenticated and have a session id
		if (SESSION_ID)	header["session_id"] = SESSION_ID;

		if (this.LOGIN) header["login"] = this.LOGIN;
		if (this.PASSWORD) header["password"] = this.PASSWORD;
		if (this.SAVECOOKIE==true) header["save_cookie"] = 1;

		//conbine the header and the body
		var data = new Object();
		data["header"] = header;
		data["body"] = this.DATA;

		return data;

	};

	/****************************************************************
		FUNCTION:	encodeData
		PURPOSE:	converts DATA to a url string for submitting to our
							destination
		INPUTS:		noprep - if set doesn't prepare data for sending
	****************************************************************/
	this.encodeData = function(noprep)
	{

		var msg = this.setupMessage();

		//if in regular query mode, just convert to normal serialized string
		if (this.PM=="QUERY" || noprep) var str = this.encode(msg);
		else var str = "apidata=" + encodeURIComponent(this.encode(msg)); 

		return str;

	};

  /****************************************************************
    FUNCTION: geturl
    PURPOSE:  compiles DATA and our desired url, and returns
              the compiled url
    INPUTS:   url -> destination url
  ****************************************************************/
  this.geturl = function(url)
  {
   
    var data = "";

    //split our url into parameters and the url destination itself
    var pos = url.indexOf("?");
    if (pos!=-1)
    {

      data = url.substr(pos + 1);
      url = url.substr(0,pos);   

    }

    if (this.DATANUM > 0)
    {

      //add our submitted data to the request string
      if (data) data += "&";
      data += this.encodeData();

    }

    var ret = url;
    if (data) ret += "?" + data;

    return ret;

  };

	/****************************************************************
		FUNCTION:	request
		PURPOSE:	master function for submitting ajax requests.  
							compiles DATA and our desired url, and submits
							the request.  Calls a callback function (if specified)
							on success or displays an error on failure
		INPUTS:		url -> destination url
							callback -> function to call when successful
	****************************************************************/
	this.request = function(url,callback)
	{

		var data = "";				//data we submit
		var retdata = "";			//data that gets returned from synchronous requests

		//split our url into parameters and the url destination itself
		var pos = url.indexOf("?");
		if (pos!=-1) 
		{

			data = url.substr(pos + 1);
			url = url.substr(0,pos);

		} 

		if (this.DATANUM > 0)
		{

			//add our submitted data to the request string
			if (data) data += "&";
			data += this.encodeData();

		} 

		var method = this.REQMODE;
		var async = this.ASYNC;
		var decode = this.DECODE;
		var parentClass = this;

		//init the xhr request using mootools
		var req = new Request({

			method: method,
			url: url,
			data: data,
			noCache: true,
			async: async,
			onRequest: function() {	ajaxReqNum++;},
			onSuccess: function(respTXT,respXML) 
			{

				ajaxReqNum--;

				var h = this.getHeader("Content-Type");

				if (h)
				{
					var pos = h.indexOf(";");
					if (pos!=-1) h = h.substr(0,pos);
				}

				if (callback)
				{

	    		//if passed a function object, use it instead of evaluating the function first
	      	if (typeof(callback)=="object") var func = callback;
	      	else var func = eval(callback);

				} else var func = "";

				//this is used when we call static xml files for config options and forms
				if (h=="application/xml" || h=="text/xml")
				{

					if (BROWSER=="ie" && BROWSERVERSION < 10)
					{
						for (var i=0;i<respXML.childNodes.length;i++)
						{
							if (respXML.childNodes[i].tagName)
							{
								var data = respXML.childNodes[i];
								break;
							}
						}
					}
					else
					{
						var data = respXML.childNodes[0];
					}

					//decode and pass the root node to callback
					if (decode) 
					{
						var arr = XML.decode(data);

						//if callback, call it, otherwise return the data (sync requests)
						if (func) func(arr);
						else retdata = arr;

					} 
					else 
					{
						if (func) func(data);
						else retdata = data;
					}

				} 
				//pass back json responses.  most of the api calls come back as
				//this.  will show us a head and body
				else if (h=="application/json" || h=="text/json")
				{

					//check for parse errors
					var err = parentClass.errorCheck(respTXT);

					if (err==false)
					{

						//go ahead and break into our pieces
						var decoded = JSON.decode(respTXT);

						//if there's an error, bail
						if (!parentClass.checkAuthentication(decoded.header)) return;

						//decode and pass root node to the callback
						if (decode)
						{
							//make sure we pass something back to make body parsing easier
							if (!decoded.body) decoded.body = new Object();

							//if callback, call it, otherwise return the data (sync requests)
							if (func) func(decoded.body,decoded.header);
							else retdata = decoded.body;
	
						} 
						else 
						{
	
							if (func) func(respTXT);
							else retdata = respTXT;
	
						}

					}

				//pass back regular text responses (html, parse errors)
				} 
				else 
				{

					var err = false;

					//check for login message
					var loginCheck = respTXT.indexOf("<!--EDEV LOGIN-->");

					if (loginCheck!=-1)
					{
						location.href = "index.php?show_login_form=expired";
					} 
					else 
					{
						err = parentClass.errorCheck(respTXT);
					}

					//no error, call our callback
					if (err==false) 
					{
						if (func) func(respTXT);
						else return respTXT;
					}

				}

			},

			//handle error codes
			onFailure: function(xhr) {

				ajaxReqNum--;
				if (xhr.status!=0) alert("Error loading page.  Status: " + xhr.status + "\n" + xhr.responseText);

			}

		});

		if (this.DEBUG) 
		{

			if (this.PM=="QUERY") var showdata = data;
			else var showdata = decodeURIComponent(data);

			alert("URL=>" + url + "\nDATA=>" + showdata);

		}

		if (!this.DEBUG || this.DEBUG!="nosend")
		{

			if (method=="POST") req.post();
			else req.get();

		}

		//empty the data array.  not sure if this is needed or not but since we do
		//multiple requests I'm assuming this helps w/ memory?
		this.DATA = new Object();
		this.DATANUM = 0;

		//return data stored from synchronous requests
		return retdata;

	};

	this.errorCheck = function(respTXT)
	{

		var str = respTXT.substr(0,100);
		var stripped = str.replace(/(<([^>]+)>)/ig,"").trim().substr(0,20);

		var err = false;

		var check = new Array("DEBUG:","Parse error:","Warning:","Fatal error:","Catchable fatal error:");

		for (var i=0;i<check.length;i++)
		{

			var key = stripped.indexOf(check[i]);
	
			if (key > -1)
			{

				err = true;

				var msg = "There was an error loading the page.  Do you wish to see the error text?";
				if (confirm(msg)) alert(respTXT);
				break;

			}

		}

		return err;

	};

	/****************************************************************
		FUNCTION:	encode
		PURPOSE:	converts an array to JSON or XML formatted string
		INPUTS:		arr -> array to convert
	****************************************************************/
	this.encode = function(arr)
	{

		if (this.PM=="JSON") var ret = JSON.encode(arr);
		else if (this.PM=="QUERY") var ret = QUERY.encode(arr);
		else var ret = "<data>" + XML.encode(arr) + "</data>";

		return ret;

	};

	/****************************************************************
		FUNCTION:	decode
		PURPOSE:	converts a JSON or XML formatted string to an array.
		INPUTS:		str -> string to convert
	****************************************************************/
	this.decode = function(str)
	{

		if (this.PM=="JSON") var ret = JSON.decode(str);
		else if (this.PM=="QUERY") var ret = QUERY.decode(str);
		else var ret = XML.decode(str);

		return ret;

	};

	/****************************************************************
		FUNCTION:	traverse
		PURPOSE:	pulls the values of all html forms in the specified
						 	DOM container and stores their names/values in
							an array
		INPUTS:		cont -> DOM container to search for values
							ignore -> array of html form names to skip
	****************************************************************/
	this.traverse = function(cont,ignore,ret) 
	{

		var nodes = cont.childNodes;
		if (!ret) ret = new Object();
	
		for (var i=0;i<nodes.length;i++) 
		{
	
			if (nodes[i].nodeName=="SELECT" || nodes[i].nodeName=="INPUT" || nodes[i].nodeName=="TEXTAREA") 
			{

				var formName = nodes[i].name;
				var nodeName = nodes[i].nodeName;

				//skip if it's in our ignore array
				if (isData(ignore)) 
				{
					var key = arraySearch(nodes[i].name,ignore);
					if (key!=-1) continue;
				}

				//handle select dropdowns.  handles single and multiple selects
				if (nodeName=="SELECT") 
				{

					for (var c=0;c<nodes[i].options.length;c++) 
					{
						
						var val = nodes[i].options[c].value;

						if (nodes[i].options[c].selected==true) 
						{

							//if the value already exists, convert to or add to the array
							if (ret[formName] || formName.indexOf("[]")!=-1)
							{

								//remove the [] to prevent server side parse errors
								if (this.PM!="QUERY")
								{
									var pos = formName.indexOf("[");
									if (pos!=-1) formName = formName.substr(0,pos);
								}

								if (!ret[formName]) ret[formName] = new Array(val);
								else if (typeof(ret[formName])=="object") ret[formName].push(val);
								else ret[formName] = new Array(ret[formName],val);

							} else ret[formName] = val;

						}

					}

				//all other forms
				} else 
				{

					//skip buttons for now
					if (nodes[i].type=="button") continue;
	
					//skip unchecked radio and checkboxes
					if ((nodes[i].type=="checkbox" || nodes[i].type=="radio") && nodes[i].checked==false) continue;

					var val = nodes[i].value;

					//if it has a [] in the name, store as an array
					if (formName.indexOf("[]")!=-1)
					{

						//remove the [] to prevent server side parse errors
						if (this.PM!="QUERY")
						{
							var pos = formName.indexOf("[");
							if (pos!=-1) formName = formName.substr(0,pos);
						}

						//create or add to our array
						if (!ret[formName]) 
						{
							ret[formName] = new Array(val);
						} else 
						{
							ret[formName].push(val);
						} 

					} else 
					{
						ret[formName] = val;
					}

				}

			//not a form
			} else 
			{

				//if there's something below here, run through it
				if (nodes[i].childNodes.length>0) 
				{

					//get result arrays below this and merge to return array
					ret = this.traverse(nodes[i],ignore,ret);

				}

			}
	
		}

		return ret;
	
	};


	this.upload = function(url,file,callback,progress)
	{

			//got here by mistake
			if (!file)
			{
				return false;
			}

			var req;
			var retdata = "";
			var decoderes = this.DECODE;
			ajaxReqNum++;

			//if passed some PROTO data, append to our post string
			if (this.DATANUM > 0)
			{
				url = url + "?" + this.encodeData();
			} 

  		//our callback function for processing the return from our xml request
  		this.callBackFunc = function xmlHttpChange(evt) 
			{

				ajaxReqNum--;

					var respTXT = evt.target.responseText;

								if (callback)
								{

	    						//if passed a function object, use it instead of evaluating the function first
	      					if (typeof(callback)=="object") var func = callback;
	      					else var func = eval(callback);

								} else var func = "";

								//decode and pass root node to the callback
								if (decoderes==true)
								{

									var arr = JSON.decode(respTXT);

									//if callback, call it, otherwise return the data (sync requests)
									if (func) func(arr.body,file);
									else retdata = arr;

								} 
								else 
								{

									if (func) func(respTXT,file);
									else retdata = respTXT;

								}

      };


			//start the request
	  	req = new XMLHttpRequest();

			//passed a progress handler, use it
	  	if (progress) 
			{

				var progfunc = eval(progress);

				req.upload.addEventListener("progress", function(e) {
																									progfunc(e,file);
																								} , false);


			}

			//callback function for when finished
	  	req.addEventListener("load",this.callBackFunc,false);

	  	req.open("POST", url,this.ASYNC);
	  	req.setRequestHeader("If-Modified-Since", "Mon, 26 Jul 1997 05:00:00 GMT");      
	  	req.setRequestHeader("Cache-Control", "no-cache");               
	  	req.setRequestHeader("X-Requested-With", "XMLHttpRequest");      
	  	req.setRequestHeader("X-File-Name", file.name);
	  	req.setRequestHeader("X-File-Size", file.size);
	  	req.setRequestHeader("Content-Type", "multipart/form-data");      
	  	req.send(file);

			//for sync requests
			return retdata;
	
	};

	this.redirect = function(url,newWindow)
	{
	 
		//create a form for us to submit so we can post our api data
		var form = ce("form");
		form.setAttribute("method","post");
		form.setAttribute("action",url);
	
		//hidden form to hold command
		var tf = createHidden("apidata");
		tf.value = this.encodeData(1);
		form.appendChild(tf);
	
		//mozilla makes us add it to the DOM before we can submit it
		document.body.appendChild(form);
	
		//if tagged as inline, shoot the content directly to the browser in a new window
		if (newWindow)
		{
		 
			form.setAttribute("target","_viewer");
	
			//open a window to view the file
			var parms = centerParms(800,600,1) + ",scrollbars=1,menubar=1,resizable=1,titlebar=1,toolbar=1,status=1,location=1";
			var ref = window.open('',"_viewer",parms);
	
			if (!ref)
			{
				alert(_I18N_POPUP_BLOCKER_ERROR);
			}	
			else
			{	 
				form.submit();
				ref.focus();	
			}
	
		}
		//otherwise force a download
		else
		{	 
			form.submit();
		}
	
		//done with the form, toss it
		document.body.removeChild(form);
	
	};

  this.checkAuthentication = function(header)
  {

    if (header.authentication_error && header.authentication_error=="session_timeout")
    {

      var url = "index.php?module=login&timeout=1";
      location.href = url;

      return false;

    }

    return true;

  };

}
