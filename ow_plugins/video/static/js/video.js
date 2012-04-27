
var videoClip = function( params )
{
    this.params = params;    

    var self = this;
    
    $("#clip-set-approval-staus a").bind("click", function() {
        self.ajaxSetApprovalStatus(this);
    });

    $("#clip-mark-featured a").bind("click", function() {
        self.ajaxSetFeaturedStatus(this);
    });

    $("#clip-delete a").bind( "click", function() {
        if ( confirm(self.params.txtDelConfirm) )
        {
            self.ajaxDeleteClip();
        }
        else
        {
            return false;
        }
    });
        
    this.ajaxSetApprovalStatus = function( dom_element )
    {
        var status = $(dom_element).attr('rel');
        
        $.ajax({
		    url: self.params.ajaxResponder,
		    type: 'POST',
		    data: { ajaxFunc: 'ajaxSetApprovalStatus', clipId: self.params.clipId, status: status },
		    dataType: 'json',
		    success: function(data) 
		    {	        
		        if ( data.result == true )
		        {
		            var newStatus = status == 'approve' ? 'disapprove' : 'approve';
		            var newLabel = status == 'approve' ? self.params.txtDisapprove : self.params.txtApprove;
		            $(dom_element).html(newLabel);
		            $(dom_element).attr('rel', newStatus)
		            
		            OW.info(data.msg);
		        }
		        else if (data.error != undefined)
		        {
		            OW.warning(data.error);
		        }
		    }
        });
    }
    
    this.ajaxSetFeaturedStatus = function( dom_element )
    {
        var status = $(dom_element).attr('rel');
        
        $.ajax({
            url: self.params.ajaxResponder,
            type: 'POST',
            data: { ajaxFunc: 'ajaxSetFeaturedStatus', clipId: self.params.clipId, status: status },
            dataType: 'json',
            success: function(data) 
            {           
                if ( data.result == true )
                {
                    var newStatus = status == 'remove_from_featured' ? 'mark_featured' : 'remove_from_featured';
                    var newLabel = status == 'remove_from_featured' ? self.params.txtMarkFeatured : self.params.txtRemoveFromFeatured;
                    $(dom_element).html(newLabel);
                    $(dom_element).attr('rel', newStatus)
                    
                    OW.info(data.msg);
                }
                else if (data.error != undefined)
                {
                    OW.warning(data.error);
                }
            }
        });
    }
    
    this.ajaxDeleteClip = function( )
    {        
        $.ajax({
            url: self.params.ajaxResponder,
            type: 'POST',
            data: { ajaxFunc: 'ajaxDeleteClip', clipId: self.params.clipId },
            dataType: 'json',
            success: function(data) 
            {
            	if ( data.result == true )
            	{
            		OW.info(data.msg);
            		if (data.url)
            			document.location.href = data.url;
            	}
            	else if (data.error != undefined)
            	{
            		OW.warning(data.error);
            	}            	
            }
        });
    }
}