(function($){
	
	$.fn.schemeSwitcher = function(options) {
		var $node = $(this);
		var $handle = $node.find('.handle'); 
		var $markers = $node.find(".marker");
		var $activeMarker = $node.find('.item .active');
		var prePosition = 0;
		if ( $activeMarker.lengt ) {
			prePosition = $node.find('.item .active').position().left;
		} else {
			prePosition = $( $markers.get(0) ).position().left;
		}
		
		$handle.css('left', prePosition);
		
		$handle.draggable({
		    containment: 'parent',
		    axis: 'x',
		    helper: function(){
		        return $(this).clone().addClass('helper');  
		    }
		});
		
		$markers.droppable({
		    tolerance: 'touch',
		    
			over: function(event, ui) {
		        var leftP = $(this).position().left;
		        $handle.css('left', leftP);
		        if ( options.change !== undefined ) {
		        	options.change.apple($node.get(0), [this, this.pointer]);
		        }
			}
		});
		
		$markers.each(function(){
		   var $pointer = $('<div class="marker_point"></div>');
		   $node.append($pointer);
		   $pointer.css('left', $(this).position().left );
		   this.pointer = $pointer.get(0); 
		});
		
		return this;
	}
	
})(jQuery)