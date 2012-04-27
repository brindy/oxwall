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
            data: '&entityType='+self.entityType+'&entityId='+self.entityId+'&rate='+rate+'&ownerId='+self.ownerId,
            dataType: 'json',
            success : function(data){

                if( data.errorMessage ){
                    OW.error(data.errorMessage);
                    self.userRate = self.userRateBackup;
                    self.setRate(self.userRateBackup);
                    return;
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
