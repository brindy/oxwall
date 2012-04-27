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
            throw "SubmitError";
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
	    $('#'+this.id+'_error').append(errorMessage);    	
	},

    removeErrors: function(){
        $('#'+this.id+'_error').empty();
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
	this.events = {
		submit:[],
		success:[]
	}
};

OwForm.prototype = {

	addElement: function( element ){
		this.elements[element.name] = element;
	},

	validate: function(){

        var error = false;
        var element = null;
             
        $.each( this.elements,
            function(index, data){
                try{
                    data.validate();
                }catch (e){
                    error = true;

                    if( element == null ){      
                        element = data;    
                    }
                }
            }
        );

        if(error){   
            element.input.focus();
            throw "Fill the inputs!";
        } 
	},

	bind: function( event, fnc ){
		this.events[event].push(fnc);		
	},

    trigger: function( event, data ){
        if( this.events[event] == undefined || this.events[event].length == 0 ){
            return;
        }

        for( var i = 0; i < this.events[event].length; i++ ){
            this.events[event][i](data);    
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
            OW.error(e);
            return false;
        }
              
       if( this.ajax ){
           
            var dataToSend = this.getValues();
            var postString = '';

            $.each( dataToSend, function( index, data ){
                if( $.isArray(data) ){
                    for( var i = 0; i < data.legth; i++ ){
                        postString += index + '[]=' + data[i] + '&';
                    }
                }
                else{
                    postString += index + '=' + data + '&';
                }
            } );

            $.ajax({
                type: 'POST',
                url: this.actionUrl,
                data: postString,
                dataType: self.ajaxDataType,
                success : function(data){
                    self.resetForm();
                    self.trigger('success', data); 
                },
                error : function( XMLHttpRequest, textStatus, errorThrown ){
                    OW.error(textStatus);
                    //alert('Ajax Error: '+textStatus+'!');
                    throw textStatus;
                }
            });

          return false;
        }

        return true;
   }	
}

