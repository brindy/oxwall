
var OwUtils = function(){

    var langs = {};

    this.error = function( text ){
    	drawMessage(text, 'error');
    };

    this.warning = function( text ){
    	drawMessage(text, 'warning');
    };

    this.info = function( text ){
    	drawMessage(text, 'info');
    };

   this.addScriptFiles = function( urlList, callback ){
        var scripts = $('script');
        
        //TODO: Sardar proverka 4toby script ne podklyu4alsya dvajhdy
        
/*        var docScripts = [];
        
        for( var i = 0; i < scripts.length; i++  ){
            docScripts[i] = $(scripts[i]).attr('src');
        }*/

        var recursiveInclude = function(urlList, i){
        	if( (i+1) == urlList.length )
        	{
        		$.getScript(urlList[i], callback);
        		return;
        	}

        	$.getScript(urlList[i], function(){ recursiveInclude(urlList, ++i); });
        }
        
        recursiveInclude(urlList, 0);
    };

    /*this.addScriptFile = function( url, callback ){
        var scripts = $('script');

        for( var i = 0; i < scripts.length; i++  ){
            if( $(scripts[i]).attr('src') == $.trim(url) ){
                return;
            }
        }

        $.getScript(url, callback);

        //$('head').append( $('<script type="text/javascript" src="'+url.trim()+'"></script>') );
    };*/

    
    this.addScript = function( script ){
        eval(script);
    },

    this.addCssFile = function( url ){
       $('head').append($('<link type="text/css" rel="stylesheet" href="'+$.trim(url)+'" />'));
    },

    this.addCss = function( css ){
        $('head').append($('<style type="text/css">'+css+'</style>'));
    }

    this.getLanguageText = function(prefix, key, assignedVars)
    {
        if ( langs[prefix] === undefined ) {
                return prefix + '+' + key;
        }

        if ( langs[prefix][key] === undefined ) {
                return prefix + '+' + key;
        }

        var langValue = langs[prefix][key];

        if ( assignedVars ) {
                for( varName in assignedVars ) {
                        langValue = langValue.replace('{$'+varName+'}', assignedVars[varName]);
                }
        }

        return langValue;
    };

    this.registerLanguageKey = function(prefix, key, value)
    {
            if ( langs[prefix] === undefined ) {
                    langs[prefix] = {};
            }

            langs[prefix][key] = value;
    };
}


function drawMessage(msg_text, type, delay)
{
	type = type || 'message';
	delay = delay || (1000*10);
	if (drawMessage.in_process) {
		if (msg_text) {
			drawMessage.queue.unshift([msg_text, type, delay]);
		}
		return;
	}

	if (!msg_text) {
		var item = drawMessage.queue.shift();
		if (!item) {
			return;
		}
		msg_text = item[0];
		type = item[1];
		delay = item[2];
	}

	drawMessage.in_process = true;

	// getting draw position
	var $last = jQuery('.macos_msg_node:last');
	var top_pos = (!$last.length) ? 30 : $last.position().top + $last.outerHeight() + 2;

	var $msg_block =
		// creating message block
		jQuery('<div class="macos_msg_node macos_'+type+'" style="display: none"></div>')
			.appendTo('body')
			.html(msg_text)
			.prepend('<a class="close_btn" href="#"></a>')
			.css('top', top_pos)
			.fadeTo(50, 0.1, function() {
				jQuery(this).css('display', '');
				drawMessage.in_process = false;
				drawMessage();

				jQuery(this).fadeTo(300, 1, function() {
					if (delay > 0) {
						window.setTimeout(function() {
							try {
								$msg_block.fadeOut(2500, function() {
									jQuery(this).remove();
								});
							} catch (e) {}
						}, delay);
					}
				});
			});

	$msg_block.children('.close_btn')
		.click(function() {
			jQuery(this).parent().fadeOut(100, function() {
				jQuery(this).remove();
			});
			return false;
		}
                );

}
drawMessage.in_process = false;
drawMessage.queue = [];

window.OW = new OwUtils();