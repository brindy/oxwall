
var userPhoto = function( params )
{
    this.params = params;    

    var self = this;
    
    $("#photo-set-approval-status a").bind("click", function() {
        self.ajaxSetApprovalStatus(this);
    });

    $("#photo-mark-featured a").bind("click", function() {
        self.ajaxSetFeaturedStatus(this);
    });
    
    $("#btn-photo-edit a").bind("click", function() {
    	var $form_content = $("#photo_edit_form");

        window.edit_photo_floatbox = new OW_FloatBox({
            $title: OW.getLanguageText('photo', 'tb_edit_photo'),
            $contents: $form_content,
            icon_class: 'ow_ic_edit',
            width: 500
        });
        
        window.edit_photo_floatbox.bind('show', function() {
        	var textarea = $("#photo-desc-area").get(0);
			textarea.htmlarea();
			textarea.htmlareaRefresh();
		});
    });

    $("#photo-delete a").bind( "click", function() {
        if ( confirm(self.params.txtDelConfirm) )
        {
            self.ajaxDeletePhoto();
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
		    data: { ajaxFunc: 'ajaxSetApprovalStatus', photoId: self.params.photoId, status: status },
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
            data: { ajaxFunc: 'ajaxSetFeaturedStatus', photoId: self.params.photoId, status: status },
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
    
    this.ajaxDeletePhoto = function( )
    {        
        $.ajax({
            url: self.params.ajaxResponder,
            type: 'POST',
            data: { ajaxFunc: 'ajaxDeletePhoto', photoId: self.params.photoId },
            dataType: 'json',
            success: function(data) 
            {           
                if ( data.result == true )
                {
                    OW.info(data.msg);
                    if (data.url)
                        document.location = data.url;
                }
                else if (data.error != undefined)
                {
                    OW.warning(data.error);
                }
            }
        });
    }
}