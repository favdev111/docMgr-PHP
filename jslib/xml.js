
/******************************************************************
  CLASS:  PROTO 
  PURPOSE:  wrapper for handling AJAX requests.  Can handle all
            the encoding and decoding of requests and responses.
            Also allows us to swap for a different javascript
            request library later
  INPUTS:   reqmode => "POST" or "GET".  mode to send request as
******************************************************************/

var XML = new function()
{

  /****************************************************************
    FUNCTION: encode
    PURPOSE:  converts an array to an xml formatted string
    INPUTS:   arr -> array to convert
  ****************************************************************/
	this.encode = function(arr)
	{

		var xml = "";

		for (var key in arr)
		{

			if (typeof(arr[key])=="object")
			{

				//this will only work for arrays (not objects) which is what we want
				for (var i=0;i<arr[key].length;i++)
				{

					//add regular string entry
					if (typeof(arr[key][i])=="string")
					{

						xml += this.entry(key,arr[key][i]);

					//add sub array
					} else 
					{

						xml += "<" + key + ">\n";
						xml += this.encode(arr[key][i]);
						xml += "</" + key + ">\n";

					}
				
				}

			} else if (typeof(arr[key])=="string")
			{

				xml += this.entry(key,arr[key]);

			}



		}

		return xml;

	};

  /****************************************************************
    FUNCTION: decode
    PURPOSE:  converts an xml formatted string to an array
    INPUTS:   str -> string to convert.  Must be encased in a root
										node, like "<data></data>"
  ****************************************************************/
	this.decode = function(dataNode)
	{

		//somehow passed something we couldn't iterate through
		if (!dataNode || !dataNode.childNodes) 
		{
			return false;
		}

		var arr = new Object();
		var i = 0;

		while (dataNode.childNodes[i]) 
		{
	
			var objNode = dataNode.childNodes[i];
	
			//don't go any further if it's something we can't process
			if (objNode.nodeType!=1 || !objNode.hasChildNodes()) 
			{
				i++;
				continue;
			}
	
			var objKey = objNode.nodeName;

			//see if our current node is an object or an array of objects
			if (this.isArray(objNode))
			{

				//get children of the current object
				var children = XML.decode(objNode);

				//create our array if it doesn't exist
				if (!arr[objKey]) arr[objKey] = new Array();

				//tack on the data
				arr[objKey].push(children);

			}
			//it's an object.  store single entities and sub-arrays as needed
			else
			{
				arr[objKey] = objNode.firstChild.nodeValue;
			}

			i++;

		}

		return arr;

	};

		
  /****************************************************************
    FUNCTION: entry
    PURPOSE:  converts key/value pair to a CDATA-wrapped
							xml entry
    INPUTS:   elementType -> key
							txt -> value
  ****************************************************************/
	this.entry = function(elementType,txt) 
	{
	 
  	var xml = "<" + elementType + "><![CDATA[" + escape(txt) + "]]></" + elementType + ">";
  	return xml
	
	};	

  /****************************************************************
    FUNCTION: hasChildNodes
		PURPOSE:	checks to see if the element has a text value, or if 
							there are children below it to go through
    INPUTS:   obj -> object to check
  ****************************************************************/
	this.isArray = function(obj) 
	{

		if (!obj) return false;

		if (obj.childNodes.length==1) return false;
		else return true;

	};
	
}
		
