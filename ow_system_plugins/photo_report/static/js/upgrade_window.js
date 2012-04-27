function showUpgradeWindow( nid, pluginkey, pid )
{
	var $upframe = $("<iframe name=\"service_embed_iframe\" src=\"\" style=\"width: 100%; height: 550px; border: 0px none;\" frameborder=\"0\"></iframe>");
	$upframe.attr("src", "https://writy.net/signin-embed.php?nid=" + nid + "&pluginkey=" + pluginkey + "&pid=" + pid);

	$.getJSON("https://writy.net/upgrade-embed.php?action=check-auth&callback=?", function(data) {
		if ( data.result != undefined )
		{
			var title = data.authenticated == "true" ? "Choose Plan" : "Sign in to your Wall.fm account"

			window.service_embed_floatbox = new OW_FloatBox({ $title: title, $contents: $upframe, width: 1000 });

			if ( data.authenticated == "false" )
			{
				document.iframe_ping_auth = window.setInterval(function() {
					$.getJSON("https://writy.net/upgrade-embed.php?action=check-auth&callback=?", function(data) {
						if ( data.result != undefined && data.authenticated == "true" )
						{
							window.service_embed_floatbox.$header.find(".floatbox_title").text("Choose Plan");
							window.clearInterval(document.iframe_ping_auth);
							document.iframe_ping_auth = false;
						}
					});
				}, 5000);
			}
			
			var iframe_ping_pay = window.setInterval(function() {
				if ( !document.iframe_ping_auth ) {
					$.getJSON("https://writy.net/upgrade-embed.php?action=check-pay&callback=?", function(data) {
						if ( data.result != undefined && data.redirect == "true" )
						{
							window.clearInterval(iframe_ping_pay);
							document.location.href = data.to;
						}
					});
				}
			}, 5000);
		}
		else {
			alert("Service is currently unavailable");
		}
	});
}