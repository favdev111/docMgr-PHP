
var AUTH = new APP_AUTH();

document.onkeyup = AUTH.handleKeyUp;

function APP_AUTH()
{

	this.load = function()
	{
    //tries to login without passing a username or password, in case a cookie was set
    var p = new PROTO();
    p.add("command","login");
    p.post(API_URL,"AUTH.writeCheckCookie");
	};

	this.writeCheckCookie = function(data)
	{

	  clearSiteStatus();

	  if (data.error) AUTH.loadForm();
	  else
	  {   
      if (data.record) sessionStorage.accountSettings = JSON.encode(data.record[0]);
			else sessionStorage.accountSettings = "";

			//prevent loops
			if (MODULE=="login") var url = "index.php";
			else var url = location.href;

	    location.href = url;

	  } 

	}

  this.login = function()
  {

		var login = ge("login").value;
		var password = ge("password").value;

	  if (ge("save_password").checked==true) var saveCookie = true;
	  else var saveCookie = false;

    var p = new PROTO();

		//force proto to pass these in the header
    p.LOGIN = login;
    p.PASSWORD = password;
    p.SAVECOOKIE = saveCookie;

    //tries to login without passing a username or password, in case a cookie was set
		updateSiteStatus(_I18N_PLEASEWAIT);
    p.add("command","login");
    p.post(API_URL,"AUTH.writeLogin");

  };

  this.writeLogin = function(data)
  {
    clearSiteStatus(); 

    if (!data.error)
    {
      if (data.record) sessionStorage.accountSettings = JSON.encode(data.record[0]);
			else sessionStorage.accountSettings = "";

			var url = location.href;

			//if we got here from a session timeout, kick us back to the page that timed out
			if (ge("timeout").value=="1")
			{
				url = document.referrer;
			}

	    location.href = url;

    }
		else
		{
			ge("loginError").innerHTML = data.error;
		}

  };

	this.handleKeyUp = function(evt) 
	{

	  if (!evt) evt = window.event;

	  if (evt.keyCode=="13")
	  {
	    AUTH.login();
  	}

	};

	this.loadForm = function()
	{

		var cont = ge("container");
		clearElement(cont);

		cont.appendChild(ce("div","","welcomeMessage","Welcome To DocMGR"));

		var row = ce("div","loginRow");
		row.appendChild(ce("div","loginColumn loginHeader","","Login"));
		row.appendChild(ce("div","loginColumn","",createTextbox("login")));
		cont.appendChild(row);

		var row = ce("div","loginRow");
		row.appendChild(ce("div","loginColumn loginHeader","","Password"));
		row.appendChild(ce("div","loginColumn","",createPassword("password")));
		cont.appendChild(row);

		var row = ce("div","loginRow");
		row.appendChild(ce("div","loginColumn loginHeader","",createCheckbox("save_password")));
		row.appendChild(ce("div","loginColumn","","Save Password"));

		if (USE_COOKIES!=1) row.style.display = "none";

		cont.appendChild(row);

		cont.appendChild(ce("div","loginSubmit","",createBtn("Login","Login","AUTH.login()")));

		cont.appendChild(ce("div","","loginError"));

    if (ge("timeout").value=="1")
    {
      ge("loginError").innerHTML = _I18N_SESSION_TIMED_OUT;
    }

		ge("login").focus();

	};
	
}
