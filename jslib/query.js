
/******************************************************************
  CLASS:  PROTO 
  PURPOSE:  wrapper for handling AJAX requests.  Can handle all
            the encoding and decoding of requests and responses.
            Also allows us to swap for a different javascript
            request library later
  INPUTS:   reqmode => "POST" or "GET".  mode to send request as
******************************************************************/

var QUERY = new function()
{

  /****************************************************************
    FUNCTION: encode
    PURPOSE:  converts an array to an query formatted string.
							handles arrays but NOT nested arrays.  
    INPUTS:   obj -> array to convert
							parentObject -> passback object for recursive calls
  ****************************************************************/
	this.encode = function(obj)
	{

		var rv = '';

		for (var key in obj)
		{

			if (typeof(obj[key])=="object")
			{

				for (var i=0;i<obj[key].length;i++)
				{
					//add the [] to the form name if not in there so the receiver will treat it as an array
					if (key.indexOf("[]")==-1) rv += '&' + key + '[]=' + encodeURIComponent( obj[key][i] );
		  		else rv += '&' + key + '=' + encodeURIComponent( obj[key][i] );
				}

			} else 
			{
	  		rv += '&' + key + '=' + encodeURIComponent( obj[key] );
			}

		}

   	return rv.replace(/^&/,'');

	};

  /****************************************************************
    FUNCTION: decode
    PURPOSE:  converts an xml formatted string to an array
    INPUTS:   str -> string to convert.  Must not have the "?" in it
  ****************************************************************/
	this.decode = function(str)
	{

		str  = str.replace(/\+/g, ' ');
		var args = str.split('&'); // parse out name/value pairs separated via &

		var ret = new Array();
	
		// split out each name=value pair
		for (var i = 0; i < args.length; i++) 
		{
	
			var pair = args[i].split('=');
			var name = decodeURIComponent(pair[0]);
		
			var value = (pair.length==2) ? decodeURIComponent(pair[1]) : name;

			//used array syntax, store as array
			if (name.indexOf("[]")!=-1) 
			{

				//add if it already exists, otherwise create new entry
				if (ret[name]) ret[name].push(value);
				else ret[name] = new Array(value);

			//just store regular text entry
			} else ret[name] = value;

		}

		return ret;

	};

}

