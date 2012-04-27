var OwUtils = function(){
    var langs = {};
    var messageTime = 10000;
    var $messageCont = $('<div class="ow_message_cont"></div>');
    var events = {};

    $(function(){
    	$messageCont.appendTo(document.body);
    });

    this.message = function( message, type, paramTime ){
        var $messageNode = $('<div class="ow_message_node '+type+'" style="display:none;"><a class="close_button" href="javascript://" onclick="$(this).parent().slideUp(200, function(){$(this).remove();})"></a>'+message+'</div>').appendTo($messageCont);
        if( paramTime == undefined ){
            paramTime = messageTime;
        }

        $messageNode.fadeIn(1000,
            function(){
                window.setTimeout(
                    function(){
                        $messageNode.fadeOut(1000,
                            function(){
                                $messageNode.remove();
                            }
                    );
                    }, paramTime
                );
            }
        );
    }

    this.error = function( message ){
    	this.message(message, 'error');
    };

    this.warning = function( message ){
    	this.message(message, 'warning');
    };

    this.info = function( message ){
    	this.message(message, 'info');
    };



   this.addScriptFiles = function( urlList, callback ){
        var scripts = $('script');

        //TODO: Sardar require once check

/*        var docScripts = [];

        for( var i = 0; i < scripts.length; i++  ){
            docScripts[i] = $(scripts[i]).attr('src');
        }*/

        if( urlList && urlList.length > 0 ){
            var recursiveInclude = function(urlList, i){

                if( (i+1) == urlList.length )
                {
                    $.getScript(urlList[i], callback);
                    return;
                }

                $.getScript(urlList[i], function(){recursiveInclude(urlList, ++i);});
            }

            recursiveInclude(urlList, 0);
        }else{
            callback.apply(this);
        }



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
    	if (!script)
    	{
    		return false;
    	}

        (new Function(script))();
    },

    this.addCssFile = function( url )
    {
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

    this.inProgressNode = function(node)
    {
    	$(node).inprogress();
    }

    this.activateNode = function(node)
    {
    	$(node).activate();
    };

    this.showUsers = function(userIds, title)
    {
    	title = title || OW.getLanguageText('base', 'ajax_floatbox_users_title');

    	this.ajaxFloatBox('BASE_CMP_FloatboxUserList', [userIds], {iconClass: "ow_ic_user", title: title, width: 450});
    },

    this.getActiveFloatBox = function getFloatBox()
    {
    	if ( typeof window.OWActiveFloatBox == 'undefined' )
    	{
    		return false;
    	}

    	return window.OWActiveFloatBox;
    },

    this.ajaxFloatBox = function(cmpClass, params, options)
    {
    	params = params || [];

    	options = options || {};
    	options = $.extend({}, {
    		title: '',
    		width: false,
    		height: false,
    		iconClass: false
    	}, options);

    	var self = this,
            rsp = this.ajaxFloatboxRsp,
            jsonParams = JSON.stringify(params),
            $preloader = $('<div class="ow_ajax_floatbox_preloader ow_preloader_content"></div>');

    	var floatBox = new OW_FloatBox({
            $title: options.title,
            $contents: $preloader,
            width: options.width,
            height: options.height,
            icon_class: options.iconClass
        });

        var ajaxOptions = {
			url: this.ajaxFloatboxRsp + '?cmpClass=' + cmpClass + '&r=' + Math.random(),
			dataType: 'json',
			type: 'POST',
			data: {params: jsonParams},
			success: function(markup)
                        {
                            var contentHtml = markup.content;
                            if ( !$(contentHtml).length )
                            {
                                    contentHtml = '<span>' + contentHtml + '</span>';
                            }

                            floatBox.setContent(contentHtml);

                            if (markup.styleSheets) {
                                    $.each(markup.styleSheets, function(i, o){
                                        OW.addCssFile(o);
                                    });
                            }

                            if (markup.styleDeclarations) {
                                    OW.addCss(markup.styleDeclarations);
                            }

                            if (markup.scriptFiles)
                            {
                                    OW.addScriptFiles(markup.scriptFiles, function(){
                                            if (markup.onloadScript) {
                                                    OW.addScript(markup.onloadScript);
                                            }
                                    });
                            }
                            else
                            {
                                    if (markup.onloadScript) {
                                            OW.addScript(markup.onloadScript);
                                    }
                            }

                            floatBox.fitWindow();
			}
		};

    	$.ajax(ajaxOptions);

    	return floatBox;
    };

    this.bind = function(type, func)
	{
		if (events[type] == undefined)
		{
			events[type] = [];
		}

		events[type].push(func);

	};

	this.trigger = function(type, params, applyObject)
	{
		if (events[type] == undefined) {
			return false;
		}

		applyObject = applyObject || this;
		params = params || [];

		if ( !$.isArray(params) )
		{
			params = [params];
		}

		for (var i = 0, func; func = events[type][i]; i++)
		{
			if (func.apply(applyObject, params) === false)
			{
				return false;
			}
		}

		return true;
	};

	this.unbind = function( type )
	{
		if (events[type] == undefined) {
			return false;
		}

		events[type] = [];
	};

	this.editLanguageKey = function( prefix, key, success )
	{
        var fb = OW.ajaxFloatBox("BASE_CMP_LanguageValueEdit", [prefix, key, true], {width: 520, title: this.getLanguageText('admin', 'edit_language')});

        OW.bind("admin.language_key_edit_success", function( e ) {
            fb.close();
            OW.unbind("admin.language_key_edit_success");
            success(e);
        });
	};



    this.bindAutoClicks = function(context){
    	var autoClicks;

    	if ( context )
    	{
    		autoClicks = $('.form_auto_click', context);
    	}
    	else
    	{
    		autoClicks = $('.form_auto_click');
    	}

        $.each(autoClicks, function(i,o){
            var context = $(o);
            $('textarea.invitation', context)
            .bind('focus.auto_click', {context:context},
                function(e){
                    $('.ow_submit_auto_click', e.data.context).show();
                    $(this).unbind('focus.auto_click')
                }
            );/*
            .bind('keyup.auto_click',
                function(){
                    if( $(this).val() != '' ){
                        $(this).unbind('focus.auto_click').unbind('keyup.auto_click').unbind('mouseup.auto_click').unbind('blur.auto_click');
                    }
                }
            )
            .bind('mouseup.auto_click',
                function(){
                    if( $(this).val() != '' ){
                        $(this).unbind('focus.auto_click').unbind('keyup.auto_click').unbind('mouseup.auto_click').unbind('blur.auto_click');
                    }
                }
            )
            .bind('blur.auto_click', {context:context},
                function(e){
                    if( $(this).hasClass('invitation') ){
                        $('.ow_submit_auto_click', e.data.context).hide();
                    }
                }
            );*/
        });
    };

    this.initWidgetMenu = function( items ){
        var $toolbarCont = null;
        var $contex = null;
        var condIds = [];
        var linkIds = [];
        $.each( items, function(key, value){
                if( $toolbarCont === null ){
                    $contex = $('#'+value['contId']).closest('.ow_box, .ow_box_empty');
                    $toolbarCont = $('.ow_box_toolbar_cont', $contex);
                }
                condIds.push('#'+value['contId']);
                linkIds.push('#'+value['id']);
            }
        );

        var contIdSelector = $(condIds.join(','));
        var linkIdSelector = $(linkIds.join(','));

        $.each( items, function(key, value){
                $('#'+value['id']).bind('click', {value:value},
                    function(e){
                        contIdSelector.hide();
                        $('#'+e.data.value.contId).show();
                        linkIdSelector.removeClass('active');
                        $(this).addClass('active');

                        if( e.data.value.toolbarId != undefined ){
                            if( e.data.value.toolbarId ){
                                if( $toolbarCont.length === 0 ){
                                    $toolbarCont = $('<div class="ow_box_toolbar_cont"></div>');
                                    $contex.append($toolbarCont);
                                }
                                $toolbarCont.html($('#'+e.data.value.toolbarId).html());
                            }
                            else{
                                if( $toolbarCont.length !== 0 ){
                                    $toolbarCont.remove();
                                    $toolbarCont = [];
                                }
                            }
                        }
                    }
                );
            }
        );
    };

    this.showTip = function( $el, params ){
        params = params || {};
        params = $.extend({side:'top', show:null, width:null, timeout:0, offset:5, hideEvent: null}, params);

        var showTipN = function(){

            var $rootEl = $el.data('owTip');
            var coords = $el.offset();

            switch( params.side )
            {
                case 'top':
                    var left = coords.left + $el.outerWidth()/2 - $rootEl.outerWidth()/2;
                    var top = coords.top - $rootEl.outerHeight() - params.offset;
                    break;

                case 'bot':
                    var left = coords.left + $el.outerWidth()/2 - $rootEl.outerWidth()/2;
                    var top = coords.top + $el.outerHeight() + params.offset;
                    break;

                case 'right':
                    var left = coords.left + $el.outerWidth() + params.offset;
                    var top = coords.top + $el.outerHeight()/2 - $rootEl.outerHeight()/2;
                    break;

                case 'left':
                    var left = coords.left - $rootEl.outerWidth() - params.offset;
                    var top = coords.top + $el.outerHeight()/2 - $rootEl.outerHeight()/2;
                    break;

                 default:
                     return;
            }

            $rootEl.css({left:left, top:top});

            setTimeout( function(){
                $el.data('owTip').show( 1,
                    function(){
                        if( params.hideEvent ){
                            $el.bind(params.hideEvent, function(){OW.hideTip($el)})
                        }
                        $el.data('owTipStatus', true);
                        if( $el.data('owTipHide') == true ){
                            OW.hideTip($el);
                        }
                    }
                );
            }, params.timeout);
        }

        if( $el.data('owTip') ){
            if( $el.data('owTipStatus') == true ){
                return;
            }
            showTipN();
            return;
        }

        var showContent;

        if( params.show != null ){
            showContent = ( typeof(params.show) == 'string' ? params.show : params.show.html() );
        }
        else{
            if( !$el.attr('title') ){
                return;
            }

            showContent = '<span class="ow_tip_title">'+$el.attr('title')+'</span>';
        }

        var $rootEl = $('<div class="ow_tip ow_tip_'+params.side+'"></div>').css({display:'none'}).append($('<div class="ow_tip_arrow"><span></span></div><div class="ow_tip_box">'+ showContent +'</div>'));
        if( params.width != null ){
            $rootEl.css({width:params.width});
        }
        $('body').append($rootEl);

        $el.removeAttr('title');
        $el.data('owTip', $rootEl);
        showTipN();
    };

    this.hideTip = function( $el ){
        if( $el.data('owTip') && $el.data('owTipStatus') == true ){
            $el.data('owTip').hide();
            $el.data('owTipStatus', false);
            $el.data('owTipHide', false);
        }
    };

    this.resizeImg = function($context, params){
        if( !params.width ){
            return;
        }

        $( 'img', $context ).each(function(){
            $(this).load(
                function(){
                    if( $(this).data('imgResized') != true){
                        var $fakeImg = $(this).clone();
                        $fakeImg.css({width:'auto',height:'auto',visibility:'hidden',position:'absolute',left:'-9999px'}).removeAttr('width').removeAttr('height');
                        $(document.body).append ($fakeImg);
                        var self = this;
                        $fakeImg.load(function(){
                            var width = $(this).width();
                            if( width < params.width  ){
                                $(self).css({width:'auto', height:'auto'});
                            }
                            else if( $(self).width() >= params.width ){
                                $(self).css({width:params.width, height:'auto'});
                            }
                            $(self).data('imgResized', true);
                            $(this).remove();
                        });
                    }
                }
            );
        });
    };

    this.showImageInFloatBox = function( src ){
        var floatBox = new OW_FloatBox({$title:'&nbsp;', icon_class:'ow_ic_picture', width:350,$contents:'<div class="ow_image_float_box ow_preloader_content" style="width:340px;height:220px;text-align:center;"></div>'});

        var $fakeImg = $('<img src="'+src+'" />');
        $fakeImg.css({visibility:'hidden',position:'absolute',left:'-9999px'});
        $(document.body).append ($fakeImg);

        $fakeImg.load(function(){
            var width = $fakeImg.width();
            var height = $fakeImg.height();

            if( width > 340 || height > 220 ){

                if( width > 800 ){
                    $fakeImg.css({width:'800px', height:'auto'});
                }
                else if( $fakeImg.height > 600 ){
                    $fakeImg.css({height:'600px', width:'auto'});
                }

                width = $fakeImg.width();
                height = $fakeImg.height();
                $('.ow_image_float_box', floatBox.$container).removeClass('ow_preloader_content');

                floatBox.fitWindow({
                    "width": width + 20,
                    "height": height + 80,
                    "animate": true,
                    "complete": function()
                    {
                        $('.ow_image_float_box', floatBox.$container).css({height:'auto', width:'auto'}).append($fakeImg);
                    }
                });
            }
            else{
                $('.ow_image_float_box', floatBox.$container).removeClass('ow_preloader_content').append($fakeImg);
            }
            $fakeImg.css({visibility:'visible',position:'static',left:0});
        });
    }
}


//Enable / Disable node
jQuery.fn.extend({
	inprogress: function() {
		this.each(function()
		{
			var $this = jQuery(this).addClass('ow_inprogress');
			this.disabled = true;

			if ( this.tagName != 'INPUT' && this.tagName != 'TEXTAREA' && this.tagName != 'SELECT' )
			{
				this.jQuery_disabled_clone = $this.clone().removeAttr('id').removeAttr('onclick').get(0);

				$this.hide()
					.bind('unload', function(){
						$this.activate();
					})
					.after(this.jQuery_disabled_clone);
			}
		});

		return this;
	},

	activate: function() {
		this.each(function()
		{
			var $this = jQuery(this).removeClass('ow_inprogress');
			this.disabled = false;

			if ( this.jQuery_disabled_clone )
			{
				jQuery(this.jQuery_disabled_clone).remove();
				this.jQuery_disabled_clone = null;

				jQuery(this)
					.unbind('unload', function(){
						$this.activate();
					})
					.show();
			}
		});

		return this;
	}
});

window.OW = new OwUtils();

function lg(o){
	console.log(o);
}

$( //8aa: resize fullsize images to fit to it's parendt width
	function (){
		if(typeof($) == 'undefined') return;

		$('.fullsize-image').hide();
		var node = $('.fullsize-image')[0];

		while( node = $(node).parent()[0]){

			if( node.tagName != 'DIV'){
				continue;
			}

			if($('.fullsize-image').width() > parseInt($(node).innerWidth()))
				$('.fullsize-image').width( (parseInt($(node).innerWidth()) - 10) + 'px' );

			$('.fullsize-image').show();
			break;
		}

	}
);


/**
 * Float box constructor.
 *
 * @param string|jQuery $title
 * @param string|jQuery $contents
 * @param jQuery $controls
 * @param object position {top, left} = center
 * @param integer width = auto
 * @param integer height = auto
 */
function OW_FloatBox(options)
{
    var fl_box = this;
    var fb_class;
    this.parentBox = OW.getActiveFloatBox();

    this.verion = 2;

    this.events = {close: [], show: []};

    if (typeof document.body.style.maxHeight === 'undefined') { //if IE 6
            jQuery('body').css({height: '100%', width: '100%'});
            jQuery('html').css('overflow', 'hidden');
            if (document.getElementById('floatbox_HideSelect') === null) { //iframe to hide select elements in ie6
                    jQuery('body').append('<iframe id="floatbox_HideSelect"></iframe><div id="floatbox_overlay"></div>');
                    fb_class = OW_FloatBox.detectMacXFF() ? 'floatbox_overlayMacFFBGHack' : 'floatbox_overlayBG';
                    jQuery('#floatbox_overlay').addClass(fb_class);
            }
    }
    else { //all others
            if (document.getElementById('floatbox_overlay') === null) {
                    jQuery('body').append('<div id="floatbox_overlay"></div>');
                    fb_class = OW_FloatBox.detectMacXFF() ? 'floatbox_overlayMacFFBGHack' : 'floatbox_overlayBG';
                    jQuery('#floatbox_overlay').addClass(fb_class).click(function(){
                        fl_box.close();
                    });
            }
    }

    jQuery('body').addClass('floatbox_nooverflow');

    var activeCanvas = jQuery('.floatbox_canvas_active');

    this.$canvas = jQuery('.floatbox_canvas', '#floatbox_prototype').clone().appendTo(document.body);
    activeCanvas.removeClass('floatbox_canvas_active');
    this.$canvas.addClass('floatbox_canvas_active');

    if (this.parentBox)
    {
        this.$canvas.addClass('floatbox_canvas_sub');
        this.parentBox.bind('close', function()
        {
            fl_box.close();
        });
    }

    this.$canvas.click(function(e){
        if ( $(e.target).is(this) )
        {
            fl_box.close();
        }
    });

    this.$container = jQuery('.floatbox_container', this.$canvas).hide();

    if (typeof options.$title == 'string') {
            options.$title = jQuery('<span>'+options.$title+'</span>');
    }
    else {
            this.$title_parent = options.$title.parent();
    }

    this.$header = jQuery('.floatbox_header', this.$container);

    var $fbTitle = jQuery('.floatbox_cap', this.$header)
            .find('.floatbox_title')
                    .append(options.$title);

    if (typeof options.icon_class == 'string')
    {
    	$fbTitle.addClass(options.icon_class);
    }

    this.$body = jQuery('.floatbox_body', this.$container);

    if (typeof options.$contents == 'string') {
    		var $contentsNode = jQuery(options.$contents);

    		if ( !$contentsNode.length )
    		{
    			$contentsNode = jQuery('<span>' + options.$contents + '</span>');
    		}

            options.$contents = jQuery($contentsNode);
    }
    else {
            this.$contents_parent = options.$contents.parent();
    }

    this.$body.append(options.$contents);


    this.$bottom = jQuery('.floatbox_bottom', this.$container);

    if (options.$controls) {
            if (typeof options.$controls == 'string') {
                    options.$controls = jQuery('<span>'+options.$controls+'</span>');
            }
            else {
                    this.$controls_parent = options.$controls.parent();
            }

            this.$bottom.append(options.$controls);
    }


    if (options.width)
            this.$container.css("width", options.width);
    if (options.height)
            this.$body.css("height", options.height);

    jQuery('.close', this.$header)
            .one('click', function() {
                    fl_box.close();
                    return false;
            });

    this.esc_listener =
    function(event) {
            if (event.keyCode == 27) {
                    fl_box.close();
                    return false;
            }
            return true;
    }

    jQuery(document).bind('keydown', this.esc_listener);

    this.$container
            .fadeTo(1, 0.1, function()
            {
                    var $this = jQuery(this);

                    $this.css('display', 'block');

                    if (options.position)
                    {
                        if (options.position.left) $this.css('margin-left', options.position.left);
                        if (options.position.top) $this.css('margin-top', options.position.top);
                    }
                    else
                    {
                        fl_box.fitWindow();
                    }

                    // trigger on show event
                    fl_box.trigger('show');

                    $this.fadeTo(100, 1);
            });

   window.OWActiveFloatBox = this;
}

OW_FloatBox.version = 2;
OW_FloatBox.detectMacXFF = function()
{
    var userAgent = navigator.userAgent.toLowerCase();
    return (userAgent.indexOf('mac') != -1 && userAgent.indexOf('firefox') != -1);
}

OW_FloatBox.prototype = {

    fitWindow: function( params )
    {
        params = params || {};
        params = $.extend({
            "width": null,
            "height": null,
            "animate": false,
            "complete": function() {}
        }, params);

        var css = {};
        css.marginTop = ( jQuery(window).height() / 2 ) - ( (params.height || this.$container.height()) /2 + 100 );
        css.marginTop = css.marginTop < 20 ? 20 : css.marginTop;

        if ( params.width )
        {
            css.width = params.width;
        }

        if ( params.height )
        {
            css.height = params.height;
        }

        if ( params.animate )
        {
            this.$container.animate(css, 'fast', function()
            {
                params.complete.apply(this);
            });
        }
        else
        {
            this.$container.css(css);
            params.complete.apply(this);
        }
    },

    setContent: function( $contents )
    {
        this.$body.html($contents);
    },

    close: function()
    {
            if (this.trigger('close') === false) {
                    return false;
            }

            jQuery(document).unbind('keydown', this.esc_listener);

            if (this.$title_parent && this.$title_parent.length) {
                    this.$title_parent.append(
                            jQuery('.floatbox_title', this.$header).children()
                    );
            }
            if (this.$contents_parent && this.$contents_parent.length) {
                    this.$contents_parent.append(this.$body.children());
            }
            if (this.$controls_parent && this.$controls_parent.length) {
                    this.$controls_parent.append(this.$bottom.children());
            }

            this.$canvas.remove();

            if (jQuery('.floatbox_canvas:visible').length === 0) {
                    jQuery('html, body').removeClass('floatbox_nooverflow');
                    jQuery('#floatbox_overlay, #floatbox_HideSelect').remove();
            }

            window.OWActiveFloatBox = this.parentBox;

    return true;
    },

    bind: function(type, func)
    {
            if (this.events[type] == undefined) {
                    throw 'form error: unknown event type "'+type+'"';
            }

            this.events[type].push(func);

    },

    trigger: function(type, params)
    {
            if (this.events[type] == undefined) {
                    throw 'form error: unknown event type "'+type+'"';
            }

            params = params || [];

            for (var i = 0, func; func = this.events[type][i]; i++) {
                    if (func.apply(this, params) === false) {
                            return false;
                    }
            }

            return true;
    }
}


/* OW Forms */

var OwFormElement = function( id, name ){
    this.id = id;
    this.name = name;
    this.input = document.getElementById(id);
    this.validators = [];
}

OwFormElement.prototype = {

    validate: function(){

        var error = false;

        try{
            for( var i = 0; i < this.validators.length; i++ ){
                this.validators[i].validate(this.getValue());
            }
        }catch (e) {
            error = true;
            this.showError(e);
        }

        if( error ){
            throw e;
        }
    },

    addValidator: function( validator ){
        this.validators.push(validator);
    },

    getValue: function(){
        return $(this.input).val();
    },

    setValue: function( value ){
        $(this.input).val(value);
    },

    resetValue: function(){
        $(this.input).val('');
    },

    showError: function( errorMessage ){
        $('#'+this.id+'_error').append(errorMessage).fadeIn(50);
    },

    removeErrors: function(){
        $('#'+this.id+'_error').empty().fadeOut(50);
    }
}

var OwForm = function( formId, formName ){
    this.id = formId;
    this.name = formName;
    this.form = document.getElementById(formId);
    this.elements = {};
    this.ajax = false;
    this.ajaxDataType = 'json';
    var actionUrl = $(this.form).attr('action');
    this.actionUrl = ( !actionUrl ? location.href : actionUrl );
    this.reset = true;
    this.showErrors = true;
    this.events = {
        submit:[],
        success:[]
    }
};

OwForm.prototype = {

    addElement: function( element ){
        this.elements[element.name] = element;
    },

    getElement: function( name ){
        if( this.elements[name] === undefined ){
            return null;
        }

        return this.elements[name];
    },

    validate: function(){

        var error = false;
        var element = null;
        var errorMessage;

        $.each( this.elements,
            function(index, data){
                try{
                    data.validate();
                }catch (e){
                    error = true;

                    if( element == null ){
                        element = data;
                        errorMessage = e;
                    }
                }
            }
            );

        if( error ){
            element.input.focus();

            if( this.validateErrorMessage ){
                throw this.validateErrorMessage;
            }else{
                throw errorMessage;
            }
        }
    },

    bind: function( event, fnc ){
        this.events[event].push(fnc);
    },

    sucess: function( fnc ){
        this.bind('success', fnc);
    },

    submit: function( fnc ){
        this.bind('submit', fnc);
    },

    trigger: function( event, data ){
        if( this.events[event] == undefined || this.events[event].length == 0 ){
            return;
        }

        for( var i = 0; i < this.events[event].length; i++ ){
            this.events[event][i].apply(this.form, [data]);
        }
    },

    getValues: function(){

        var values = {};

        $.each(this.elements,
            function( index, data ){
                values[data.name] = data.getValue();
            }
            );

        return values;
    },

    setValues: function( values ){

        var self = this;

        $.each( values,
            function( index, data ){
                if(self.elements[index]){
                    self.elements[index].setValue(data);
                }
            }
            );
    },

    resetForm: function(){
        $.each( this.elements,
            function( index, data ){
                data.resetValue();
            }
            );
    },

    removeErrors: function(){

        $.each( this.elements,
            function( index, data ){
                data.removeErrors();
            }
            );
    },

    submitForm: function(){

        var self = this;

        this.removeErrors();

        try{
            this.validate();
        }catch(e){
            if( this.showErrors ){
                OW.error(e);
            }
            return false;
        }

        $('#' + this.id + ' input[type=button], ' + '#' + this.id + ' input[type=submit]').addClass('ow_inprogress');

        if( this.ajax ){
            var dataToSend = this.getValues();
            var postString = '';

            $.each( dataToSend, function( index, data ){
                if( $.isArray(data) ){
                    for( var i = 0; i < data.length; i++ ){
                        postString += index + '[]=' + encodeURIComponent(data[i]) + '&';
                    }
                }
                else{
                    postString += index + '=' + encodeURIComponent(data) + '&';
                }
            } );

            $.ajax({
                type: 'post',
                url: this.actionUrl,
                data: postString,
                dataType: self.ajaxDataType,
                success : function(data){
                    if(self.reset){
                        self.resetForm();
                    }

                    self.trigger('success', data);
                    OW.activateNode('#' + self.id + ' input[type=button], ' + '#' + self.id + ' input[type=submit]');
                },
                error : function( XMLHttpRequest, textStatus, errorThrown ){
                    OW.error(textStatus);
                    throw textStatus;
                }
            });

            return false;
        }

        $.each(this.elements,
            function( i, o ){
                if( $(o.input).hasClass('invitation') ){
                    $(o.input).attr('disabled', 'disabled');
                }
            }
            );

        return true;
    }
}

// custom fields
var addInvitationBeh = function( formElement, invitationString ){
    formElement.invitationString = invitationString;

    formElement.getValue = function(){
        var val = $(this.input).val();
        if( val != '' && val != this.invitationString ){
            $(this.input).removeClass('invitation');
            return val;
        }
        else{
            return '';
        }
    };

    $(formElement.input
        ).bind('focus.invitation', {formElement:formElement},
            function(e){
                el = $(this);
                el.removeClass('invitation');
                if( el.val() == '' || el.val() == e.data.formElement.invitationString){
                    el.val('');
                    //hotfix for media panel
                    if( 'htmlarea' in el.get(0) ){
                        el.unbind('focus.invitation').unbind('blur.invitation');
                        el.get(0).htmlarea();
                        el.get(0).htmlareaFocus();
                    }
                }
                else{
                    el.unbind('focus.invitation').unbind('blur.invitation');
                }
            }
        )/*.bind('blur.invitation', {formElement:formElement},
            function(e){
                el = $(this);
                if( el.val() == '' || el.val() == e.data.formElement.invitationString){
                    el.addClass('invitation');
                    el.val(e.data.formElement.invitationString);
                }
                else{
                    el.unbind('focus.invitation').unbind('blur.invitation');
                }
            }
    );*/
}

var OwTextField = function( id, name, invitationString ){
    var formElement = new OwFormElement(id, name);
    if( invitationString ){
        addInvitationBeh(formElement, invitationString);
    }
    return formElement;
}

var OwTextArea = function( id, name, invitationString ){
    var formElement = new OwFormElement(id, name);
    if( invitationString ){
        addInvitationBeh(formElement, invitationString);
    }
    return formElement;
}

var OwWysiwyg = function( id, name, invitationString ){
    var formElement = new OwFormElement(id, name);
    addInvitationBeh(formElement, invitationString);
    formElement.resetValue = function(){$(this.input).val('');$(this.input).keyup();};
    formElement.getValue = function(){
                var val = $(this.input).val();
                if( val != '' && val != '<br>' && val != '<div><br></div>' && val != this.invitationString ){
                    $(this.input).removeClass('invitation');
                    return val;
                }
                else{
                    return '';
                }
            };

    return formElement;
}

var OwRadioField = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var value = $("input[name='"+this.name +"']:checked", $(this.input.form)).val();
        return ( value == undefined ? '' : value );
    };

    formElement.resetValue = function(){
        $("input[name='"+this.name +"']:checked", $(this.input.form)).removeAttr('checked');
    };

    formElement.setValue = function(value){
        $("input[name='"+ this.name +"'][value='"+value+"']", $(this.input.form)).attr('checked', 'checked');
    };

    return formElement;
}

var OwCheckboxGroup = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var $inputs = $("input[name='"+ this.name +"[]']:checked", $(this.input.form));
        var values = [];

        $.each( $inputs, function(index, data){
                if( this.checked == true ){
                    values.push($(this).val());
                }
            }
        );

        return values;
    };

    formElement.resetValue = function(){
        var $inputs = $("input[name='"+ this.name +"[]']:checked", $(this.input.form));

        $.each( $inputs, function(index, data){
                $(this).removeAttr('checked');
            }
        );
    };

    formElement.setValue = function(value){
        for( var i = 0; i < value.length; i++ ){
            $("input[name='"+ this.name +"[]'][value='"+value[i]+"']", $(this.input.form)).attr('checked', 'checked');
        }
    };

    return formElement;
}

var OwCheckboxField = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var $input = $("input[name='"+this.name+"']:checked", $(this.input.form));
        if( $input.length == 0 ){
            return '';
        }
        return 'on';
    };

    formElement.setValue = function(value){
        var $input = $("input[name='"+this.name+"']:checked", $(this.input.form));
        if( value ){
            $input.attr('checked', 'checked');
        }
        else{
            $input.removeAttr('checked');
        }
    };
    formElement.resetValue = function(){
        var $input = $("input[name='"+this.name+"']:checked", $(this.input.form));
        $input.removeAttr('checked');
    };

    return formElement;
}


/* end of forms */


/* Drag and drop fix */

DND_InterfaceFix = new (function(){

	var embed = function(context){
		var $context = $(context);
		var cWidth = $context.innerWidth();

		var configureEmbed = function($embed) {
			var embed = $embed.get(0);
			if ( embed.default_width === undefined || embed.default_width === null ) {
				embed.default_width = $embed.width();
			}

			if ( cWidth < embed.default_width )
			{
				$embed.css('width', '100%')
					.attr('wmode', 'transparent');
			}
			else
			{
				$embed.css('width', embed.default_width + 'px');
			}
    	};

    	var configureObject = function($object) {
    		$object.css('width', '100%');
    	};

    	$('embed', context).each(function(){
    		var $node = $(this).hide();
    		configureEmbed($node);
    		$node.show();
    	});

    	$('object', context).each(function() {
    		var $node = $(this).hide(), $embeds = $('embed', this);

    		configureObject($node);
		if ( $embeds.length )
		{
		    configureEmbed($embeds);
		}
    		$node.show();
    	});
	};

	var image = function(context) {

		var $context = $(context), cWidth;
		var cWidth = $context.innerWidth();

		if ( !cWidth )
		{
		    return;
		}

		var resize = function(img) {
			var $img = $(img);

			if ( img.default_width === undefined || img.default_width === null ) {
				img.default_width = $img.width();
			}

			if ( img.default_width > cWidth ) {
				$img.css('width', '100%');
			} else {
				$img.css('width', img.default_width);
			}
		};

		$context.find('img').each(function(){
            $(this).css('max-width', '100%');
            if (this.naturalWidth == 0) {
                $(this).load(function(){
                    resize(this);
                });
            } else {
                resize(this);
            }

		});
	};


	var iframe = function(context)
        {
            var $iframe = $('iframe', context);
            var cWidth = $(context).innerWidth();
            $iframe.each(function(i, o)
            {
                var $o = $(o);
                if ( $o.width() > cWidth )
                {
                    $o.css('width', '100%');
                }
            });
	};

	this.fix = function(context) {
		this.embed(context);
		this.image(context);
		this.iframe(context);
	};

	this.embed = function(context) {
		$(context).each(function(){
			embed(this);
		})
	};

	this.image = function(context) {
		$(context).each(function(){
			image(this);
		});
	};

	this.iframe = function(context) {
		$(context).each(function(){
			iframe(this);
		});
	};

})();

/* Comments */
var OwComments = function( contextId, formName ){
	this.formName = formName;
	this.$cmpContext = $('#' + contextId);
}

OwComments.prototype = {
	repaintCommentsList: function( data ){
		owForms[this.formName].getElement('commentText').resetValue();

		if(data.error){
			OW.error(data.error);
			return;
		}
		$('.comments_list_cont', this.$cmpContext).empty().append($(data.commentList));
		OW.addScript(data.onloadScript);
	},

    updateCommentsCountOnPage: function( count ){
        if( count == 0 )
        {
            count = parseInt($('input[name=commentCountOnPage]', this.$cmpContext).val()) + 1;
        }
        $('input[name=commentCountOnPage]', this.$cmpContext).val(count);
    }
};

var OwCommentsList = function( params ){
	this.$context = $('#' + params.contextId);
	$.extend(this, params);
}

OwCommentsList.prototype = {
	init: function(){
		var self = this;

		//Js event trigger
		OW.trigger('base.comments_list_init', {entityType: this.entityType, entityId: this.entityId}, this);

        if( this.pagesCount > 0 )
        {
            for( var i = 1; i <= this.pagesCount; i++ )
            {
                $('a.page-'+i, self.$context).bind( 'click', {i:i},
                    function(event){
                        self.reload(event.data.i);
                    }
                );
            }
        }

        for( var i = 0; i < this.commentIds.length; i++ )
        {
            $('#del-'+this.commentIds[i]).bind( 'click', {i:i},
                function(e){
                    if( confirm(self.delConfirmMsg) )
                    {
                        $.ajax({
                            type: 'POST',
                            url: self.delUrl,
                            data: 'cid='+self.cid+'&commentCountOnPage='+self.commentCountOnPage+'&ownerId='+self.ownerId+'&pluginKey='+self.pluginKey+'&displayType='+self.displayType+'&entityType='+self.entityType+'&entityId='+self.entityId+'&page='+self.page + '&commentId=' + self.commentIds[e.data.i],
                            dataType: 'json',
                            success : function(data){
                                if(data.error){
                                        OW.error(data.error);
                                        return;
                                }

                                self.$context.replaceWith(data.commentList);
                                OW.addScript(data.onloadScript);
                            },
                            error : function( XMLHttpRequest, textStatus, errorThrown ){
                                alert('Ajax Error: '+textStatus+'!');
                                throw textStatus;
                            }
                        });
                    }
                }
             );

             if( $('#att'+this.commentIds[i]).length > 0 )
             {
                 $('.attachment_delete',$('#att'+this.commentIds[i])).bind( 'click', {i:i},
                    function(e){

                        $('#att'+self.commentIds[e.data.i]).slideUp(300, function(){$(this).remove();});

                        $.ajax({
                            type: 'POST',
                            url: self.delAtchUrl,
                            data: 'cid='+self.cid+'&commentCountOnPage='+self.commentCountOnPage+'&ownerId='+self.ownerId+'&pluginKey='+self.pluginKey+'&displayType='+self.displayType+'&entityType='+self.entityType+'&entityId='+self.entityId+'&page='+self.page + '&commentId=' + self.commentIds[e.data.i],
                            dataType: 'json'
                        });
                    }
                 );
             }



            $('#flag-'+this.commentIds[i]).bind( 'click', {i:i},
                function(e){
                    alert(self.commentIds[e.data.i]);
                }
            );
        }

        if( this.displayType == 3 || this.displayType == 4 )
        {
            $('.comments_view_all a', this.$context).one('click',
                function(){
                    $(this).replaceWith('<img src="'+self.preloaderImgUrl+'" alt="" />');
                    self.commentCountOnPage = 1000;
                    if( window.commentCmps && window.commentCmps[self.cid]  )
                    {
                        window.commentCmps[self.cid].updateCommentsCountOnPage(1000);
                    }
                    self.reload(1);
                }
            );

        }
	},

	reload:function( page ){
		var self = this;
		$.ajax({
            type: 'POST',
            url: self.respondUrl,
            data: 'cid='+self.cid+'&commentCountOnPage='+self.commentCountOnPage+'&ownerId='+self.ownerId+'&pluginKey='+self.pluginKey+'&displayType='+self.displayType+'&entityType='+self.entityType+'&entityId='+self.entityId+'&page='+page,
            dataType: 'json',
            success : function(data){
               if(data.error){
                        OW.error(data.error);
                        return;
                }

                self.$context.replaceWith(data.commentList);
                OW.addScript(data.onloadScript);
            },
            error : function( XMLHttpRequest, textStatus, errorThrown ){
                OW.error('Ajax Error: '+textStatus+'!');
                throw textStatus;
            }
        });
	}
}


var OwAttachment = function( params )
{
    var self = this;
    var floatbox;
    var $context = $('#'+params.uid);
    var $previewCont = $('#attachment_preview_'+params.uid);
    var $videoCont = $('#video_code_'+params.uid);
    this.params = params;

    OW.bind('base.init_attachment', function(uid){if(uid == self.params.uid) self.init();});

    this.init = function(){
        $previewCont.empty();
        this.item = null;
        while( $('#hd_'+this.params.uid).length > 0 ){
            $('#hd_'+this.params.uid).remove();
        }

        $('input[type=button]', $videoCont).unbind('click').click(function(){self.submitVideo();});

        $('a.video', $context).show().unbind('click').click(
            function(){
                floatbox = new OW_FloatBox({
                    $title: self.params.langs.addVideoLabel,
                    $contents: $videoCont,
                    width: 500,
                    height: 300,
                    icon_class: 'ow_ic_video'
                });
            }
        );

        this.hideLoader();
        $('textarea', $videoCont).val('');
        $previewCont.removeClass('item_loaded').empty();
        $('a.image', $context).show().empty().append($('<input type="file" name="attachment" />'));
        $('input[type=file]', $context).unbind('change').change(
            function(){
                self.submitFile();
            }
        );
    }

    this.submitFile = function(){
        this.disableButtons();
        this.showLoader();
        $form = $('<form method="post" action="' + this.params.addPhotoUrl + '" enctype="multipart/form-data" target="form_' + this.params.uid + '"></form>')
            .append($('input[type=file]', $context));
        $('<div style="display:none" id="hd_'+this.params.uid+'"><div>').appendTo($('body'))
            .append($('<iframe name="form_' + this.params.uid + '"></iframe>'))
            .append($form);
        $form.submit();
    }

    this.submitVideo = function(){
        code = $('textarea', $videoCont).val();

        if( !code || code.length < 10 ){
            OW.error(this.params.langs.emptyVideoCode);
            return;
        }
        this.showLoader();
        this.disableButtons();
        floatbox.close();
        $.ajax({type:'POST', url:this.params.addVideo, data:{code:code}, dataType: 'json',
            success: function(item){
                self.hideLoader();
                self.addItem(item);
            },
            error: function(){
                OW.error('error');
            }
        });
    }

    this.addItem = function( item ){
        this.item = item;
        if( $previewCont.length > 0 ){
            $previewCont.addClass('item_loaded').css({height:'auto'}).append(item.cmp);
            $('.attachment_delete', $previewCont).click(function(){self.deleteItem();});
        }
        OW.resizeImg($('.ow_oembed_attachment'),{width:'150'});
        OW.trigger('base.attachment_added', this.getEventParams());
    }

    this.deleteItem = function(){
        if( this.item.type == 'photo' ){
            $.ajax({type:'POST', url:this.params.deleteUrl, data:this.item});
        }
        $previewCont.children().slideUp(300, function(){self.init();});
        OW.trigger('base.attachment_deleted', this.getEventParams());
    }

    this.showLoader = function(){
        $previewCont.empty().addClass('attachment_preloader').animate({height:45});
    }

    this.hideLoader = function(){
        $previewCont.empty().removeClass('attachment_preloader').css({height:'auto'});
        return this;
    }

    this.disableButtons = function(){
        OW.hideTip($('a.video', $context));
        OW.hideTip($('a.image', $context));
        $('a.video', $context).hide();
        $('a.image', $context).hide();
    }

    this.getEventParams = function(){
        var data = {uid:this.item.uid, type:this.item.type, genId:this.item.genId};
        if( this.item.type == 'photo' ){
            data.url = this.item.url;
            data.href = this.item.url;
        }

        if( this.item.type == 'video' ){
            data.html = this.item.code;
        }

        return data;
    }
}

var OwRate = function( params ){
    this.cmpId = params.cmpId;
    this.userRate = params.userRate;
    this.entityId = params.entityId;
    this.entityType = params.entityType;
    this.itemsCount = params.itemsCount;
    this.respondUrl = params.respondUrl;
    this.ownerId = params.ownerId;
    this.$context = $('#rate_'+params.cmpId);
}

OwRate.prototype = {
    init: function(){
        var self = this;
        this.setRate(this.userRate);
        for( var i = 1; i <= this.itemsCount; i++ ){
            $('#' + this.cmpId + '_rate_item_' + i).bind( 'mouseover', {i:i},
                function(e){
                    self.setRate(e.data.i);
                }
            ).bind( 'mouseout',
                function(){
                    self.setRate(self.userRate);
                }
            ).bind( 'click', {i:i},
                function(e){
                    self.updateRate(e.data.i);
                }
            );
        }
    },

    setRate: function( rate ){
        for( var i = 1; i <= this.itemsCount; i++ ){
            var $el = $('#' + this.cmpId + '_rate_item_' + i);
            $el.removeClass('active');
            if( !rate ){
                continue;
            }
            if( i <= rate ){
                $el.addClass('active');
            }
        }
    },

    updateRate: function( rate ){
        var self = this;
        if( rate == this.userRate ){
            return;
        }
        this.userRateBackup = this.userRate;
        this.userRate = rate;
        $.ajax({
            type: 'POST',
            url: self.respondUrl,
            data: 'entityType='+encodeURIComponent(self.entityType)+'&entityId='+encodeURIComponent(self.entityId)+'&rate='+encodeURIComponent(rate)+'&ownerId='+encodeURIComponent(self.ownerId),
            dataType: 'json',
            success : function(data){

                if( data.errorMessage ){
                    OW.error(data.errorMessage);
                    self.userRate = self.userRateBackup;
                    self.setRate(self.userRateBackup);
                    return;
                }

                if( data.message ){
                    OW.info(data.message);
                }

                $('.total_score', self.$context).empty().append(data.totalScoreCmp);
            },
            error : function( XMLHttpRequest, textStatus, errorThrown ){
                alert('Ajax Error: '+textStatus+'!');
                throw textStatus;
            }
        });
    }
}

