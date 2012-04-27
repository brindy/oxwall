var OwComments = function( contextId, formName ){
	this.formName = formName;
	this.$cmpContext = $('#' + contextId);
}

OwComments.prototype = {
	repaintCommentsList: function( data ){
		owForms[this.formName].setValues(
				{
					'entity-type': data.entityType,
					'entity-id': data.entityId,
					'display-type': data.displayType
				}
		);
		
		$('.comments_list_cont', this.$cmpContext).empty().append($(data.commentsList));
		
		OW.addScript(data.onloadScript);
	}
};

var OwCommentsList = function( params ){
	this.$context = $('#' + params.contextId);
	this.displayType = params.displayType;
	this.entityType = params.entityType; 
	this.entityId = params.entityId;
	this.pagesCount = params.pagesCount;
	this.respondUrl = params.respondUrl;
    this.commentIds =  params.commentIds;
    this.delUrl = params.delUrl;
    this.page = params.page;
}

OwCommentsList.prototype = {
	init: function(){
		var self = this;

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
        
        for( var i = 0; i <= this.commentIds.length; i++ )
        {   
            $('#del-'+this.commentIds[i]).bind( 'click', {i:i},
                function(e){
                    $.ajax({
                        type: 'POST',
                        url: self.delUrl,
                        data: 'displayType='+self.displayType+'&entityType='+self.entityType+'&entityId='+self.entityId+'&page='+self.page + '&commentId=' + self.commentIds[e.data.i],
                        dataType: 'json',
                        success : function(data){
                            self.$context.replaceWith(data.commentList);
                            OW.addScript(data.onloadScript);
                        },
                        error : function( XMLHttpRequest, textStatus, errorThrown ){
                            alert('Ajax Error: '+textStatus+'!');
                            throw textStatus;
                        }
                    });
                }
             );

            $('#flag-'+this.commentIds[i]).bind( 'click', {i:i},
                function(e){
                    alert(self.commentIds[e.data.i]);
                }
            );
        }
	},

	reload:function( page ){
		var self = this;
		$.ajax({
            type: 'POST',
            url: self.respondUrl,
            data: 'displayType='+self.displayType+'&entityType='+self.entityType+'&entityId='+self.entityId+'&page='+page,
            dataType: 'json',
            success : function(data){
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