/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.static.js
 * @since 1.0
 **/


var mailboxConversation = function( $params )
{
    var self = this;
    var responderUrl = $params.responderUrl;
    var deleteConfirmMessage = $params.deleteConfirmMessage;
    
	this.sentRequest = false;
	
    $("#mailbox-conversation-form .mailbox_mark_unread").bind( "click", 
    		function(){	
        		self.markConversationUnRead( this );
        	} );
    
    $("#mailbox-conversation-form .mailbox_delete").bind( "click", 
        function() {
                        if( !confirm( deleteConfirmMessage ) )
                        {
                                return false;
                        }
        } );
	
	
	this.markConversationUnRead = function( $element )
	{
    	var handler = this;
    	
        if( self.sentRequest == false )
        {    		
    		self.sentRequest == true;
            $.ajax( {
                url: responderUrl,
                type: 'POST',
                data: { function_: 'markConversationUnRead', conversationId: $( "#mailbox-conversation-form :hidden" ).val() },
                dataType: 'json',
                success: function( data ) 
                {
                	self.sentRequest == false;
                	
                    if( data.result == true )
                    {
                    	$( $element ).remove();
                    	OW.info( data.notice );
                    }
                    else if( data.error != undefined )
                    {
                    	OW.warning( data.error );
                    }
                }
            } );
        }
	}
}

var mailboxConversationList = function( $params )
{
    var self = this;
    var responderUrl = $params.responderUrl;
    var deleteConfirmMessage = $params.deleteConfirmMessage;
    
    this.sentRequest = false;
    
    var $checkboxes = $( "#mailbox-conversation-list-form :checkbox" );
    
    $( "#mailbox-conversation-list-form :checkbox[name='all']" ).bind( "change", 
    		function() {
				if( $( this ).attr( "checked" ) )
    			{
    				$checkboxes.attr( "checked", "checked" );
    			}
    			else
    			{
    				$checkboxes.removeAttr( "checked" );
    			}
			} );
    
    $table = $("#mailbox-conversation-list-form table");
    $tr = $table.find("tr").bind( "mouseover.mailbox_delete", function(){ $(this).find("td a.mailbox_delete").show(); } )
	   				 .bind( "mouseout.mailbox_delete", function(){ $(this).find("td a.mailbox_delete").hide(); } );
    
    $tr.find("a.mailbox_delete").bind( "click",
    		function() {
    			if( confirm( deleteConfirmMessage ) )
    			{
    				window.location = $(this).attr('url');
    			}
    		} );
    
    $tr.find(".mailbox_mark_as_read, .mailbox_mark_as_unread").bind( "click", 
    		function() { 
    			if( !self.validateForm() )
    			{
    				return false;
    			}
    		} ); 
    
    $tr.find("input.mailbox_delete").bind( "click", 
    		function() { 
    			if( !self.validateForm() )
    			{
    				return false;
    			}
    			
    			if( !confirm( deleteConfirmMessage ) )
    			{ 
    				return false;
    			}
    		} );
    				 
    this.bindFunction = function()
	{
            $aUnRead = $table.find("tr.ow_high2 td a.mailbox_new");

            $aUnRead.bind( "click",
                                function(){
                                    self.markConversationRead( this );
                                } );

            $trRead = $table.find("tr:not(.ow_high2)");
            $aRead = $trRead.find("td a.mailbox_new");

            $trRead.bind( "mouseover.mailbox_new", function(){ $(this).find("td a.mailbox_new").show(); } )
                    .bind( "mouseout.mailbox_new", function(){ $(this).find("td a.mailbox_new").hide(); } );

            $aRead.bind( "click",
                            function(){
                                    self.markConversationUnRead( this );
                            } );
	}

    this.validateForm =  function() 
    {
		if( $checkboxes.is(":checked") )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
    
    
    this.markConversationRead = function( $element )
	{
		var handler = this;
                if( self.sentRequest == false )
                {
                        var $a = $($element);
                        var $tr = $a.parent("td").parents("tr:first");

                        self.sentRequest == true;
                    $.ajax( {
                        url: responderUrl,
                        type: 'POST',
                        data: { function_: 'markConversationRead', conversationId: $tr.find( "input[type='checkbox']" ).val() },
                        dataType: 'json',
                        success: function( data )
                        {
                                self.sentRequest == false;
                            if( data.result == true )
                            {
                                $tr.removeClass( "ow_high2" );
                                $tr.addClass( "ow_high1" );

                                        $a.unbind("click");
                                        $a.bind( "click", function(){
                                                        self.markConversationUnRead( this );
                                                    } );

                                        $tr.bind( "mouseover.mailbox_new", function(){ $a.show(); } );
                                        $tr.bind( "mouseout.mailbox_new", function(){ $a.hide(); } );
                                        $a.hide();
                            }
                            else if( data.error != undefined )
                            {
                                OW.warning( data.error );
                            }
                        }
                    } );
                }
	}
	
    this.markConversationUnRead = function( $element )
	{
            var handler = this;
            if( self.sentRequest == false )
            {
                    var $a = $($element);
                    var $tr = $a.parents("tr:first");

                    self.sentRequest == true;
                $.ajax( {
                    url: responderUrl,
                    type: 'POST',
                    data: { function_: 'markConversationUnRead', conversationId: $tr.find( "input[type='checkbox']" ).val() },
                    dataType: 'json',
                    success: function( data )
                    {
                            self.sentRequest == false;
                        if( data.result == true )
                        {
                            $tr.addClass( "ow_high2" );
                            $tr.removeClass( "ow_high1" );

                            $a.unbind("click");
                                    $a.bind( "click", function(){
                                                    self.markConversationRead( this );
                                                } );

                                    $tr.unbind( "mouseover.mailbox_new" ).unbind( "mouseout.mailbox_new" );

                                    $a.show();
                        }
                        else if( data.error != undefined )
                        {
                            OW.warning( data.error );
                        }
                    }
                } );
            }
	}
}