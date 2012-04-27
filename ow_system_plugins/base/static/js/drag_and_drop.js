OW_Components_DragAndDrop = function() {

	var self = this;

        this.sectionSelector = '.place_section';


	this.$panel = $('#place_components');
	this.$sections = $(this.sectionSelector);
        this.$clonablePanel = $('#clonable_components');
	this.transfered = false;

        this.events = {
            complete: [],
            update: [],
            clone: [],
            remove: [],
            loadSettings: [],
            saveSettings: [],
            saveScheme: [],
            moveToPanel: []
        };

	this.defaultSettings = {

                placeholder: 'hidden-placeholder',
                connectWith: this.sectionSelector,

                out: function(e, ui){
                    ui.placeholder.removeClass('placeholder');
                    ui.placeholder.addClass('placeholder-hidden');
                },

                over: function(e, ui){
                    ui.placeholder.removeClass('placeholder-hidden');
                    ui.placeholder.addClass('placeholder');
                },

		helper: function(e, ui) {
			return self.getHelper(this, e, ui);
		},

		start: function(e, ui) {
                    return self.handlStart(this, e, ui);
		},

                stop: function(e, ui) {
                    self.handleStop(this, e, ui);
                    self.complete();
                }

	};

        this.initializePanel();
        this.initializeSections();
        this.initializeActions();
        this.initializeClonablePnael();
        this.initializeScheme();

};


OW_Components_DragAndDrop.prototype = {

        setHandler: function(handler) {

            this.bind('update', function(section, state) {
                handler.changeState(section, state);
            });

            this.bind('complete', function() {
                handler.complete();
            });

            this.bind('clone', function(section, stack, id, success) {
                handler.clone(section, stack, id, success);
            });

            this.bind('remove', function(id) {
                handler.remove(id);
            });

            this.bind('loadSettings', function(id, successFunction) {
                handler.loadSettings(id, successFunction);
            });

            this.bind('saveSettings', function(id, settings, successFunction) {
                handler.saveSettings(id, settings, successFunction);
            });

            this.bind('saveScheme', function(scheme) {
                handler.saveScheme(scheme);
            });

            this.bind('moveToPanel', function(cmpId) {
                handler.moveToPanel(cmpId);
            });
        },

        initializePanel: function() {
            var self = this;
            var panelSettings = this.extendSettings({
                stop: function(e, ui) {
                    if ( !self.transfered ) {
                        $(this).sortable('cancel');
                    }
                    self.handleStop(this, e, ui);
                    self.complete();
                    self.transferComplete();
                },

                over: function(e, ui) {
                    ui.placeholder.removeClass('placeholder');
                    ui.placeholder.addClass('placeholder-hidden');
                }
            });

            this.$panel.sortable(panelSettings).disableSelection();
        },

        initializeSections: function() {
            var self = this;
            var sectionSettings = this.extendSettings({
                cancel: '.dd_freezed',
                items: '.component:not(.dd_freezed)',
                receive: function(e, ui){
                    if (ui.placeholder.hasClass('placeholder')) {
                        self.transfered = true;
                    }
                },

                change: function(e, ui) {
                   self.handleSectionChange(this, e, ui);
                },

                update: function(e, ui) {
                    if (ui.item.cloning && self.transfered) {
                        ui.item.clone = self.clone(this, ui.item);
                    } else {
                        self.update(this);
                    }

                }

            });

            this.$sections.sortable(sectionSettings).disableSelection();
        },


        initializeClonablePnael: function() {
            var self = this;
            var settings = this.extendSettings({

                 stop: function(e, ui) {
                    if (self.transfered) {
                        self.handleClone(this, e, ui);
                        ui.item.cloning = false;
                        self.complete();
                    }

                    $(this).sortable('cancel');
                    self.transferComplete();
                },

                start: function(e, ui){
                     self.handlStart(this, e, ui);
                     $(ui.item).css('opacity', '1');
                     ui.item.cloning = true;
                },

                over: function(e, ui) {
                    ui.placeholder.removeClass('placeholder');
                    ui.placeholder.addClass('placeholder-hidden');
                }

            });

            this.$clonablePanel.sortable(settings).disableSelection();
        },


        initializeActions: function() {

            var self = this;

            var $allContainers = this.$sections.add(this.$panel);

            $allContainers.find('.component').hover(function(){
                $(this).find('.action').show();
            }, function(){
                $(this).find('.action').hide();
            });

            this.$sections.find('.dd_delete').unbind().click(function() {
               var $component = $(this).parents('.component:eq(0)');
               self.deleteFromSection($component);
            });

            this.$panel.find('.dd_edit').unbind().click(function() {
               var $component = $(this).parents('.component:eq(0)');

               self.showSettings($component);

               return false;
            });

            this.$sections.find('.dd_edit').unbind().click(function() {
               var $component = $(this).parents('.component:eq(0)');

               self.showSettings($component);

               return false;
            });

            this.$panel.find('.dd_delete').unbind().click(function() {
               if ( !confirm( OW_Language.text('components', 'delete_component_confirm') ) ) {
                   return false;
               }

               var $component = $(this).parents('.component:eq(0)');
               if (!$component.hasClass('clone')) {
                   return false;
               }
               self.deleteFromPanel($component.attr('id'));
               self.handleRemoveFromPanel($component);
            });
        },

        initializeScheme: function() {
            var self = this;
            var $left = $('.left_section', '#place_sections');
            var $right = $('.right_section', '#place_sections');

            $( '.scheme_item', '#dd_scheme' ).click(function(){

                var newLeftClass = $(this).attr('dd_leftclass');
                var newRightClass = $(this).attr('dd_rightclass');
                var $currentScheme = $( '.active', '#dd_scheme' );

                $left.removeClass($currentScheme.attr('dd_leftclass'));
                $right.removeClass($currentScheme.attr('dd_rightclass'));
                $currentScheme.removeClass('active');

                var newSchemeNumber = $(this).attr('dd_scheme');

                if (newSchemeNumber != $currentScheme.attr('dd_scheme')) {
                    self.trigger('saveScheme', [newSchemeNumber]);
                }
                $(this).addClass('active');
                $left.addClass(newLeftClass);
                $right.addClass(newRightClass);
            })
        },

        transferComplete: function() {
            this.initializeActions();
        },

        /* Animation */

        getHelper: function(sortable, e, ui) {
            var itemWidth = ui.outerWidth();
            if (itemWidth > 160)
            {
                var k = 160 / ui.outerWidth();
                var offset = k * (e.pageX - ui.position().left);
                $(sortable).sortable( 'option', 'cursorAt', {left: offset } );
            }

            return $('<div class="dd_helper" style="width: 160px; height: 30px"></div>');
        },

        handlStart: function(sortable, e, ui) {
            this.transfered = false;
            $(ui.item).show().css('opacity', '0.3');
        },

        handleStop: function(sortable, e, ui) {
            $(ui.item).show().css('opacity', '1');
        },

        handleClone: function(sortable, e, ui) {
            ui.item.after(ui.item.clone);
            $(ui.item.clone).show().css('opacity', '1');
            $(sortable).sortable('cancel');
            this.handleStop(sortable, e, ui);
        },

        handleSectionChange: function(sortable, e, ui) {
            var id = $(ui.item).attr('id');
            var $ph = $(ui.placeholder);
            if ( $ph.next().is('#' + id) || $ph.prev().is('#' + id)	) {
                    $ph.removeClass('placeholder');
                    $ph.addClass('hidden-placeholder');
            }
            else {
                    $ph.removeClass('hidden-placeholder');
                    $ph.addClass('placeholder');
            }
        },

        handleRemoveFromSection: function($item, completeFnc) {
            var self = this;
            $item.find('.action').hide();
            $item.fadeOut('fast', function(){
                $item.appendTo(self.$panel);
                self.transferComplete();
                $item.fadeIn('fast', completeFnc);
            });

        },

        handleRemoveFromPanel: function($item) {
            $item.fadeOut('fast');
        },

        /* / Animation */

        deleteFromSection: function($item) {
             var self = this;
             var sortable = $item.parents(this.sectionSelector).get(0);
             this.handleRemoveFromSection($item, function(){
                self.update(sortable);
                self.trigger('moveToPanel', [$item.attr('id')]);
                self.complete();
             });

        },

        clone: function(sortable, $cloningItem) {

            var self = this;
            var sourceId = $cloningItem.attr('id');
            var stack = $(sortable).sortable('toArray');
            var section = $(sortable).attr('ow_place_section');
            var $destItem = $cloningItem.clone().removeAttr('id').addClass('clone');

            self.trigger('clone', [section, stack, sourceId, function(id){
                $destItem.attr('id', id);
            }]);
            return $destItem;
        },

        saveScheme: function(scheme) {
            this.trigger('saveScheme', [scheme]);
        },

        deleteFromPanel: function(id) {
            this.trigger('remove', [id]);
        },

        update: function(sortable) {

            $selfNode = $(sortable);
            var section = $selfNode.attr('ow_place_section');
            var stack = $selfNode.sortable('toArray');
            this.changeState(section, stack);
        },

	changeState: function(sectionName, itemStack) {
            this.trigger('update', [
                sectionName,
                itemStack
            ]);
	},

	complete: function() {
            this.trigger('complete');
	},


        showSettings: function($component)
        {
            var self = this;
            var $title = $('.settings_title', '#fb_settings');
            var $content = $('.settings_content', '#fb_settings').addClass('ow_preloader');

            var $controls = $('.settings_controls', '#fb_settings');
            $controls.find('.dd_save').unbind();

            this.settingBox = new OW_FloatBox({
                $title: $title,
                $contents: $content,
                $controls: $controls,
                width: 500
            });

            this.settingBox.bind('close', function(){
                $content.empty();
            });
            var cmpId = $component.attr('id');
            this.trigger('loadSettings', [cmpId, function(settingMarkup){
                    $content.empty();
                    $content.removeClass('ow_preloader');
                    var $settingMarkup = $('<form class="settings_form">' + settingMarkup + '</form>');
                    $content.html($settingMarkup);

                    var $form = $content.find('.settings_form').submit(function(){
                        self.saveSettings(cmpId, this);
                        return false;
                    });

                    $controls.show().find('.dd_save').click(function(){
                        $form.submit();
                    });

            }]);
        },

        saveSettings: function(cmpId, form) {
            var self = this;
            var formState = this.formToArray(form);

            this.trigger('saveSettings', [cmpId, formState, function(settings){
                if (settings) {
                    self.applyComponentSettings(cmpId, settings);
                    self.settingBox.close();
                }
            }]);
        },


        applyComponentSettings: function(cmpId, settings) {
            var $component = $('#' + cmpId);
            var $section = $component.parents('.place_section:eq(0)');
            $component.find('.dd_title').text(settings.title);
            if (settings.freeze > 0 ) {
                var $freezed = $section.find('.dd_freezed');
                if ($freezed.length) {
                    $freezed.filter(':last');
                    $freezed.after($component);
                } else {
                    $section.prepend($component);
                }
                this.update($section.get(0));
                $component.addClass('dd_freezed');
                this.complete();
            } else {
                $component.removeClass('dd_freezed');
            }
        },




        formToArray: function(form) {
            var state = {};
            $(form.elements).each(function(i, item){
                var $item = $(item);
                if ($item.is('input:checkbox, input:radio')) {
                    state[item.name] = $item.attr('checked') ? true : false;
                } else {
                    state[item.name] = $item.val();
                }
            });
            return state;
        },

	bind: function(type, func) {
		if (this.events[type] == undefined) {
			throw 'undefined form event type "'+type+'"';
		}

		this.events[type].push(func);
	},

	trigger: function(type, params) {

		if (this.events[type] == undefined) {
			throw 'undefined form event type "'+type+'"';
		}

		params = params || [];

		for (var i = 0, func; func = this.events[type][i]; i++) {
			if (func.apply(this, params) === false) {
				return false;
			}
		}
                return true;
	},

        extendSettings: function(obj)
        {
            $.each(this.defaultSettings, function(prop, value) {
                if (obj[prop] === undefined) {
                    obj[prop] = value;
                }
            });
            return obj;
        }
};
