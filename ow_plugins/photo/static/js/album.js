
var photoAlbum = function( params )
{
    this.params = params;    

    var self = this;
    
    $("#btn-edit-album").bind("click", function() {
    	
    	var $form_content = $("#edit_album_form");

        window.edit_section_floatbox = new OW_FloatBox({
            $title: OW.getLanguageText('photo', 'edit_album'),
            $contents: $form_content,
            icon_class: 'ow_ic_edit',
            width: 550
        });

    });

    $("#btn-delete-album").bind( "click", function() {
        if ( confirm(OW.getLanguageText('photo', 'confirm_delete_album')) )
        {
            self.ajaxDeletePhotoAlbum();
        }
        else
        {
            return false;
        }
    });
    
    this.ajaxDeletePhotoAlbum = function( )
    {        
        $.ajax({
            url: self.params.ajaxResponder,
            type: 'POST',
            data: { ajaxFunc: 'ajaxDeletePhotoAlbum', albumId: self.params.albumId },
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