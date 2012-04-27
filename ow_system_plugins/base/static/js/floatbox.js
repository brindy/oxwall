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

    //jQuery('html, body').css('overflow', 'hidden');

    this.$container = jQuery('.floatbox_container', '#floatbox_prototype').clone().hide().appendTo('body');

    if (typeof options.$title == 'string') {
            options.$title = jQuery('<span>'+options.$title+'</span>');
    }
    else {
            this.$title_parent = options.$title.parent();
    }

    this.$header = jQuery('.floatbox_header', this.$container);

    jQuery('.floatbox_cap', this.$header)
            .find('.floatbox_title')
                    .append(options.$title);


    this.$body = jQuery('.floatbox_body', this.$container);

    if (typeof options.$contents == 'string') {
            options.$contents = jQuery('<span>'+options.$contents+'</span>');
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
            this.$container.css("height", options.height);

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

                    if (options.position) {
                            $this.css(options.position);
                    }
                    else {
                            var position = {
                                    top:((jQuery(window).height()/2) - ($this.height()/2))/*.ceil()*/,
                                    left:((jQuery(window).width()/2) - ($this.width()/2))/*.ceil()*/
                            };

                            $this.css(position);
                    }

                    // trigger on show event
                    fl_box.trigger('show');

                    $this.fadeTo(100, 1);
            });

}

OW_FloatBox.detectMacXFF = function()
{
    var userAgent = navigator.userAgent.toLowerCase();
    return (userAgent.indexOf('mac') != -1 && userAgent.indexOf('firefox') != -1);
}

OW_FloatBox.prototype = {
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

            this.$container.remove();

            if (jQuery('.floatbox_container:visible').length === 0) {
                    jQuery('html, body').css('overflow', '');
                    jQuery('#floatbox_overlay, #floatbox_HideSelect').remove();
            }

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
