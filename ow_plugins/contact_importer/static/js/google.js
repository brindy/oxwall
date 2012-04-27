google.load('friendconnect', '0.8');

CI_Google = function(siteId)
{
    var self = this, loggedId = false, loginObservers = [];

    google.friendconnect.container.initOpenSocialApi({
	site: siteId,
	onload: function(securityToken)
	{
            // Create a request to grab the current viewer.
            var req = opensocial.newDataRequest();
            req.add(req.newFetchPersonRequest('VIEWER'), 'viewer_data');
            // Sent the request
            req.send(onData);
	}
    });

    function onData(data)
    {
	// If the view_data had an error, then user is not signed in
	if (data.get('viewer_data').hadError())
	{
	    loggedId = false;
	}
	else
	{
	    loggedId = true;

	    while( loginObservers.length )
	    {
		loginObservers.shift().apply(this);
	    }
	}
    }


    this.login = function()
    {
	google.friendconnect.requestSignIn();
    };

    this.request = function()
    {
	if (loggedId)
	{
	  this.invite();
	}
	else
	{
	  this.login();
	  loginObservers.push(this.invite);
	}
    };

    this.invite = function()
    {
	google.friendconnect.requestInvite();
    }
};