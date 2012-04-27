var ThemesSelect = function(themeList, active)
{
	this.active = active;
	
	$.each(themeList, function(){
		$('.themes_select a.'+this.key).bind('click', {data:this},
			function(e){
				$('.themes_select a').removeClass('clicked');
				$(this).addClass('clicked');
                
				$context = $('.selected_theme_info');
				$('.theme_icon', $context).css({backgroundImage:'url('+e.data.data.iconUrl+')'});
				$('.theme_title', $context).empty().append(e.data.data.title);
				$('.theme_desc', $context).empty().append(e.data.data.description);
				$('.theme_preview img', $context).attr('src', e.data.data.previewUrl);
				$('.author', $context).empty().append(e.data.data.author);
				$('.author_url', $context).empty().append('<a href="'+e.data.data.authorUrl+'">'+e.data.data.authorUrl+'</a>');
				
				var url = e.data.data.changeUrl;
				
				$('.selected_theme_info input.theme_select_submit').unbind('click').click(function(){
	    			window.location = url;
	    		});
			}
		);
	});
	
	
}