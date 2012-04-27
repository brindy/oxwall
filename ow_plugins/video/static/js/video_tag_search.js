var videoTagSearch = function( params )
{
    this.params = params;    

    var self = this;
    
    $("#video-tag-search-input").bind( "keypress", function(e) {
        if (e.keyCode == 13)
        {
            var tag = $.trim($(this).val());
            
            if ( tag.length == 0 )
                return false;
            else
            {
	            document.location = self.params.listUrl  + '/' + tag;
            }
        }
    });
}