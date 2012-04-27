
var UploadPhoto = function( params )
{
    this.slots = params.slots;
    var self = this;
    self.params = params;
    
    $("#photo_slots").click(function(){
    	$(".ow_photo_submit_wrapper").removeClass("ow_mild_green");
    	$("#photo_check_all").attr("checked", false);
    });
    
    // highlight wrapper
    $(".ow_photo_submit_wrapper").click(function(){
        var self = $(this);
        if ( self.hasClass("ow_mild_green") )
        {
            self.removeClass("ow_mild_green");
        }
        else
        {
            self.addClass("ow_mild_green");
        }
        
        return false;
    });
    
    // on slot hover
    $(".ow_photo_submit_slot").hover(
    	function()
    	{
	        $(".ow_action_delete", $(this)).css("display", "inline-block");
	        $(".ow_action_tag", $(this)).css("display", "inline-block");
	        $(".ow_action_edit", $(this)).css("display", "inline-block");
	    },
	    function(){
	        var photoId = $("a.ow_action_delete", $(this)).attr("rel");
	    	$(".ow_action_delete", $(this)).css("display", "none");
	        
	        if ( !self.slots[photoId]['tag'] )
	        {
	        	$(".ow_action_tag", $(this)).css("display", "none");
	        }
	        if ( !self.slots[photoId]['desc'] )
	        {
	        	$(".ow_action_edit", $(this)).css("display", "none");
	        }
	    }
	);

    // click edit action
    $(".ow_action_edit", "#ow_photo_submit").click(function(){
    	var $contents = $("#photo_desc_cont").children();
    	var photoId = $(this).attr("rel");
    	
    	$('textarea', $contents).val("");
    	$('#btn-edit-description', $contents).attr("rel", photoId);
    	if ( self.slots[photoId]['desc'] != '' )
    	{
    		$('textarea', $contents).val(self.slots[photoId]['desc']);
    	}

    	window.edit_photo_floatbox = new OW_FloatBox({
            $title: OW.getLanguageText('photo', 'describe_photo'),
            $contents: $contents,
            icon_class: 'ow_ic_edit',
            width: 400
        });
    	
    	return false;
    });
    
    // submit edit description
    $("#btn-edit-description").click(function(){
    	var photoId = $(this).attr("rel");
    	var desc = $('#photo-desc-area').val();
    	
    	if ( photoId ) // single
    	{
        	if ( $(this).attr("rel") != "" && desc )
        	{
        		self.slots[photoId]['desc'] = desc;
        		$(".ow_action_edit[rel="+photoId+"]").css("display", "inline-block");
        	}
    	}
    	else // multiple
    	{
    		var $checkedSlots = $(".ow_photo_submit_wrapper.ow_mild_green");
    		if ( $checkedSlots.length && desc )
        	{
    			$checkedSlots.each(function(){
    				var id = $(this).attr('id').split("_")[1];
    				self.slots[id]['desc'] = desc;
            		$(".ow_action_edit[rel="+id+"]").css("display", "inline-block");
    			});
        	}
    	}
    	
    	window.edit_photo_floatbox.close();
    	$(".ow_photo_submit_wrapper").removeClass("ow_mild_green");
    	$("#photo_check_all").attr("checked", false);
    });
    
    // click tags action
    $(".ow_action_tag", "#ow_photo_submit").click(function(){
    	var $contents = $("#photo_tag_cont").children();
    	var photoId = $(this).attr("rel");
    	
    	$('#photo-tag-input', $contents).val("");
    	$('#btn-edit-tags', $contents).attr("rel", photoId);
    	if ( self.slots[photoId]['tag'] != '' )
    	{
    		$('#photo-tag-input', $contents).val(self.slots[photoId]['tag']);
    	}
    	
    	window.edit_photo_floatbox = new OW_FloatBox({
            $title: OW.getLanguageText('photo', 'add_tags'),
            $contents: $contents,
            icon_class: 'ow_ic_edit',
            width: 400
        });
    	
    	return false;
    });
    
    // submit edit tags
    $("#btn-edit-tags").click(function(){
    	var photoId = $(this).attr("rel");
    	var tags = $('#photo-tag-input').val();
    	
    	if ( photoId )
    	{
	    	if ( $(this).attr("rel") != "" && tags )
	    	{
	    		self.slots[photoId]['tag'] = tags;
	    		$(".ow_action_tag[rel="+photoId+"]").css("display", "inline-block");
	    	}
    	}
    	else
    	{
    		var $checkedSlots = $(".ow_photo_submit_wrapper.ow_mild_green");
    		if ( $checkedSlots.length && tags )
        	{
    			$checkedSlots.each(function(){
    				var id = $(this).attr('id').split("_")[1];
    				self.slots[id]['tag'] = tags;
            		$(".ow_action_tag[rel="+id+"]").css("display", "inline-block");
    			});
        	}
    	}
    	
    	window.edit_photo_floatbox.close();
    	$(".ow_photo_submit_wrapper").removeClass("ow_mild_green");
    	$("#photo_check_all").attr("checked", false);
    });

    // submit form
	$("#"+self.params.formId).submit(function () {
		if ( $("input[name=album]").val() == "" )
		{
			return false;
		}
		var $submit = $("input[type=submit]", $(this));
		OW.inProgressNode($submit);
		
		if ( self.params.singleSlotId )
		{
			self.slots[self.params.singleSlotId]['tag'] = $("input[name=tags]").val();
			self.slots[self.params.singleSlotId]['desc'] = $("textarea[name=description]").val();
		}
		
		$.ajax({
            url: self.params.ajaxSubmitResponder,
            type: 'POST',
            data: { slots: self.slots, album: $("input[name=album]", $(this)).val() },
            dataType: 'json',
            success: function(data) 
            {
            	OW.activateNode($submit);

                if ( data.url != '' )
                {
                	document.location = data.url;
                }
                else
                {
                	document.location.reload();
                }
            }
        });
		
		return false;
	});

	// click delete action
    $(".ow_action_delete", "#ow_photo_submit").click(function(){
    	var photoId = $(this).attr("rel");
    	if ( photoId && confirm(OW.getLanguageText("photo", "confirm_delete")) )
    	{
    		self.ajaxDelete(photoId);
    	}
    	
    	return false;
    });
    
    this.ajaxDelete = function( photoId )
    {
        $.ajax({
            url: self.params.ajaxDeleteResponder,
            type: 'POST',
            data: { photoId: photoId },
            dataType: 'json',
            success: function(data) 
            {
            	if ( data.result )
        		{
        			$("#wrapper_"+photoId).remove();
        		}
            }
        });
    }
    
    // click group edit action
    $("#btn-group-description").click(function(){
    	var checkedCount = $(".ow_photo_submit_wrapper.ow_mild_green").length;
    	if ( !checkedCount )
    	{
    		OW.warning(OW.getLanguageText('photo', 'no_photo_selected'));
    		return;
    	}
    	
    	var $contents = $("#photo_desc_cont").children();
    	$('textarea', $contents).val("");
    	$("#btn-edit-description").attr("rel", "");
    	
    	window.edit_photo_floatbox = new OW_FloatBox({
            $title: OW.getLanguageText('photo', 'add_description'),
            $contents: $contents,
            icon_class: 'ow_ic_edit',
            width: 400
        });
    });
    
    // click group tag action
    $("#btn-group-tag").click(function(){
    	var checkedCount = $(".ow_photo_submit_wrapper.ow_mild_green").length;
    	if ( !checkedCount )
    	{
    		OW.warning(OW.getLanguageText('photo', 'no_photo_selected'));
    		return;
    	}
    	
    	var $contents = $("#photo_tag_cont").children();
    	$('#photo-tag-input', $contents).val("");
    	$("#btn-edit-tags").attr("rel", "");
    	
    	window.edit_photo_floatbox = new OW_FloatBox({
            $title: OW.getLanguageText('photo', 'add_tags'),
            $contents: $contents,
            icon_class: 'ow_ic_edit',
            width: 500
        });
    });
    
    // click check all
    $("#photo_check_all").change(function(){
    	if ( $(this).attr("checked") )
    	{
    		$(".ow_photo_submit_wrapper").addClass("ow_mild_green");
    	}
    	else
    	{
    		$(".ow_photo_submit_wrapper").removeClass("ow_mild_green");
    	}
    });
}