window.ow_newsfeed_const = {};
window.ow_newsfeed_feed_list = {};

var NEWSFEED_Feed = function(autoId, data)
{
	var self = this;
	this.autoId = autoId;
	this.setData(data);

	this.containerNode = $('#' + autoId).get(0);
	this.$listNode = this.$('.ow_newsfeed');

	this.totalItems = 0;
	this.actionsCount = 0;

	this.actions = {};
	this.actionsById = {};


	this.$viewMore = this.$('.ow_newsfeed_view_more_c');

	this.$viewMore.find('input.ow_newsfeed_view_more').click(function(){
		var btn = this;
		OW.inProgressNode(this);
		self.loadMore(function(){
			OW.activateNode(btn);
			if ( self.totalItems > self.actionsCount)
			{
				self.$viewMore.show();
			}
		});
	});

	OW.bind('base.comments_list_init', function(p){

		if ( self.actions[p.entityType + '.' + p.entityId] )
		{
			self.actions[p.entityType + '.' + p.entityId].comments = this.totalCount;
			self.actions[p.entityType + '.' + p.entityId].refreshCounter();
		}
	});
};

NEWSFEED_Feed.prototype =
{
                setData: function(data) {
                    this.data = data;
                },

		adjust: function()
		{
			this.$('.ow_newsfeed_section').each(function() {
				if ( !$(this).next().is('.ow_newsfeed_item') )
				{
					$(this).remove();
				}
			});

			if ( this.$listNode.find('.ow_newsfeed_item:not(.newsfeed_nocontent)').length )
			{
				this.$listNode.find('.newsfeed_nocontent').hide();
			}
			else
			{
				this.$listNode.find('.newsfeed_nocontent').show();
			}
		},

		reloadItem: function( actionId )
		{
			var action = this.actionsById[actionId];

			if ( !action )
			{
				return false;
			}

			this.loadItemMarkup({actionId: actionId,  cycle: action.cycle}, function($m){
				$(action.containerNode).replaceWith($m);
			});
		},

		loadItemMarkup: function(params, callback)
		{
			var self = this;

			params.feedData = this.data;
			params.cycle = params.cycle || {lastSection: false, lastItem: false};

			params = JSON.stringify(params);

			$.getJSON(window.ow_newsfeed_const.LOAD_ITEM_RSP, {p: params}, function( markup ) {

				if ( markup.result == 'error' )
				{
					return false;
				}

				var $m = $(markup.html);
				callback.apply(self, [$m]);
				OW.bindAutoClicks($m);

				self.processMarkup(markup);
			});
		},

		loadNewItem: function(params, preloader, callback)
		{
			if ( typeof preloader == 'undefined' )
			{
				preloader = true;
			}

			var self = this;
			if (preloader)
			{
				var $ph = self.getPlaceholder();
				this.$listNode.prepend($ph);
			}
			this.loadItemMarkup(params, function($a) {
				this.$listNode.prepend($a.hide());

                                if ( callback )
                                {
                                    callback.apply(self);
                                }

				self.adjust();
				if ( preloader )
				{
					var h = $a.height();
					$a.height($ph.height());
					$ph.replaceWith($a.css('opacity', '0.1').show());
					$a.animate({opacity: 1, height: h}, 'fast');
				}
				else
				{
					$a.animate({opacity: 'show', height: 'show'}, 'fast');
				}
			});
		},

		loadList: function( callback )
		{
			var self = this, params = JSON.stringify(this.data);

			$.getJSON(window.ow_newsfeed_const.LOAD_ITEM_LIST_RSP, {p: params}, function( markup ) {

				if ( markup.result == 'error' )
				{
					return false;
				}

				var $m = $(markup.html).filter('li');
				callback.apply(self, [$m]);
				OW.bindAutoClicks($m);
				self.processMarkup(markup);
			});
		},

		loadMore: function(callback)
		{
			var self = this;
			var li = this.lastItem;

			this.loadList(function( $m )
			{
				var w = $('<li class="newsfeed_item_tmp_wrapper"></li>').append($m).hide();
				self.$viewMore.hide();
				li.$delim.show();

				self.$listNode.append(w);

				w.slideDown('normal', function() {
					w.before(w.children()).remove();
					if ( callback )
					{
						callback.apply(self);
					}
				});
			})
		},

		getPlaceholder: function()
		{
			return $('<li class="ow_newsfeed_placeholder ow_preloader"></li>');
		},

		processMarkup: function( markup )
		{
			if ( markup.styleDeclarations )
			{
				OW.addCss(markup.styleDeclarations);
			}

			if ( markup.onloadScript )
			{
				OW.addScript(markup.onloadScript);
			}
		},

		/**
	     * @return jQuery
	     */
		$: function(selector)
		{
			return $(selector, this.containerNode);
		}
}


var NEWSFEED_FeedItem = function(autoId, feed)
{
	this.autoId = autoId;
	this.containerNode = $('#' + autoId).get(0);

	this.feed = feed;
	feed.actionsById[autoId] = this;
	feed.actionsCount++;
	feed.lastItem = this;
};

NEWSFEED_FeedItem.prototype =
{
		construct: function(data)
		{
			var self = this;

			this.entityType = data.entityType;
			this.entityId = data.entityId;
			this.id = data.id;
			this.updateStamp = data.updateStamp;

			this.likes = data.likes;

			this.comments = data.comments;

			this.cycle = data.cycle || {lastSection: false, lastItem: false};

			this.$featuresCont = this.$('.newsfeed-features');

			this.$commentBtn = this.$('.newsfeed_comment_btn');
			this.$likeBtn = this.$('.newsfeed_like_btn');
			this.$unlikeBtn = this.$('.newsfeed_unlike_btn');
			this.$removeBtn = this.$('.newsfeed_remove_btn');
			this.$delim = this.$('.newsfeed-item-delim');

                        this.$attachment = this.$('.newsfeed_attachment');
                        this.hasAttachment = this.$attachment.length;

                        this.$attachment.find('.newsfeed_attachment_remove').click(function(){
                            var rsp = window.ow_newsfeed_const.REMOVE_ATTACHMENT;

                            self.$attachment.animate({opacity: 'hide', height: 'hide'}, 'fast', function() {
                                self.$attachment.remove();
                            });

                            $.get(rsp, {actionId: self.id});

                            return false;
                        });

			this.$commentBtn.click(function(){
				self.showComments();

                                return false;
			});

			this.$likeBtn.click(function(){
				self.like();

                                return false;
			});

			this.$unlikeBtn.click(function(){
				self.unlike();

                                return false;
			});

			this.$removeBtn.click(function(){
				if ( confirm(this.rel) )
				{
					self.remove();
				}

                                return false;
			});

			this.$('.newsfeed_features_btn').click(function(){
				if ( self.$featuresCont.is(':visible') )
				{
					self.$featuresCont.slideUp('fast');
				}
				else
				{
					self.$featuresCont.slideDown('fast');
				}

                                return false;
			});
		},

		refreshCounter: function() {
			var $c = this.$('.newsfeed_counter').hide(),
				$likes = $c.find('.newsfeed_counter_likes').hide(),
				$comments = $c.find('.newsfeed_counter_comments').hide(),
				$delim = $c.find('.newsfeed_counter_delim').hide();


			if ( this.likes > 0 && this.comments > 0 )
			{
				$delim.show();
			}

			if ( this.likes > 0 || this.comments > 0 )
			{
				$c.show();
			}

			if ( this.likes > 0 )
			{
				$likes.show().text(this.likes);
			}

			if ( this.comments > 0 )
			{
				$comments.show().text(this.comments);
			}
		},

		showComments: function()
		{
			this.$commentBtn.parents('.ow_newsfeed_control:eq(0)');
			var $c = this.$featuresCont.show();
			$c.show().find('.ow_newsfeed_comments').show().find('.ow_newsfeed_comment_input').focus();
			var $delim = $c.find('.ow_newsfeed_delimiter');

			if ( this.likes != 0 )
		    {
		        $delim.show();
		    }

		    this.comments = -1;
		},

		like: function()
		{
			var rsp = window.ow_newsfeed_const.LIKE_RSP,
		        self = this;

			this.$unlikeBtn.show();
			this.$likeBtn.hide();

		    $.getJSON(rsp, {entityType: self.entityType, entityId: self.entityId}, function(c){
		    	self.likes = c.count;
		        self.showLikes(c.markup);
		        self.refreshCounter();
		    });
		},

		unlike: function()
		{
			var rsp = window.ow_newsfeed_const.UNLIKE_RSP,
	        self = this;

			this.$unlikeBtn.hide();
			this.$likeBtn.show();

		    $.getJSON(rsp, {entityType: self.entityType, entityId: self.entityId}, function(c){
		    	self.likes = c.count;
		        self.showLikes(c.markup);
		        self.refreshCounter();
		    });
		},

		showLikes: function( likesHtml )
		{
			var $c = this.$featuresCont;

			if ( this.comments != 0 || this.likes != 0 )
			{
				$c.show();
			}
			else
			{
				$c.hide();

				return;
			}

	        var $delim = $c.find('.ow_newsfeed_delimiter').hide();
	        var $likes = $c.find('.ow_newsfeed_likes').hide();
	        $likes.empty().html(likesHtml);

			if ( this.likes != 0 )
			{
				$likes.show();
			}

			if ( this.comments != 0 && this.likes != 0 )
		    {
		        $delim.show();
		    }
		},

		remove: function()
		{
			var self = this;rsp = window.ow_newsfeed_const.DELETE_RSP;

			$.get(rsp, {actionId: this.id});

			$(this.containerNode).animate({opacity: 'hide', height: 'hide'}, 'fast', function() {
				$(this).remove();

				self.feed.adjust();
			});
		},

		/**
	     * @return jQuery
	     */
		$: function(selector)
		{
			return $(selector, this.containerNode);
		}
};