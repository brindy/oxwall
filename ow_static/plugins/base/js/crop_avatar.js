var cropAvatar = function( params )
{
    this.params = params;    

    this.$preview = $('#preview');
    
    this.$image = $('#jcrop-target');
    
    //this.coords = {};
    
    this.$preview.css({
        width: this.params.previewSize
    });
    
    this.$crop_btn = $('#crop-btn');

    var self = this;
    
    this.initCrop = function(){
        $('#jcrop-target').Jcrop({
            onChange: this.showPreview,
            onSelect: this.showPreview,
            aspectRatio: 1
        });
    };
    
    this.showPreview = function showPreview(coords){
        var rx = 100 / coords.w;
        var ry = 100 / coords.h;
        
        self.coords = coords;
        self.$preview.css({
            width: Math.round(rx * self.$image.width()) + 'px',
            height: Math.round(ry * self.$image.height()) + 'px',
            marginLeft: '-' + Math.round(rx * coords.x) + 'px',
            marginTop: '-' + Math.round(ry * coords.y) + 'px'
        });
    };
    
    this.$crop_btn.bind("click", function(){
        self.ajaxCropPhoto();
    });
    
    this.ajaxCropPhoto = function( )
    {
        var request = JSON.stringify({ ajaxFunc: 'ajaxCropPhoto', coords: self.coords, view_size: self.$image.width() });
        var data = {request: request};
    
        $.ajax({
            url: self.params.ajaxResponder,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(data){ 
                if (data.result)
                    window.location.href = data.location;
                else
                    alert("Please make selection");
            }
        });
    }
}