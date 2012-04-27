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


var fileUpload = function( $params )
{
    var self = this;

    this.sentRequest = false;

    var ajaxResponderUrl = $params['ajaxResponderUrl'];
    var fileResponderUrl = $params['fileResponderUrl'];
    var attachFileHtml = $params['attachFileHtml'];
    var fileList = $params['fileList'];

    this.elementId = $params['elementId'];

    this.element = $( "#" + this.elementId );

    this.uploadFileList = $( "#" + this.elementId +"_list" );

    this.sendFile = false;

    this.form = undefined;
    this.frame = undefined;

    this.init = function()
	{
        $( "#" + this.elementId ).change( function(){
            OW.trigger('mailbox.attach_file', [{input_id: self.elementId}])
        } );

        OW.bind('mailbox.attach_file', function( $param )
        {
            if ( $param.input_id === self.elementId )
            {
                self.sendFile();
            }
        });

        OW.bind('mailbox.attach_file_complete', function( $param )
        {
            if ( $param.input_id === self.elementId )
            {
                self.sendComplete($param);
            }
        });

        OW.bind('mailbox.delete_attach_file', function( $param )
        {
            if ( $param.input_id === self.elementId )
            {
                self.sendComplete($param);
            }
        });

        self.displayFileList( fileList );
    }

    this.sendFile = function()
	{
        if( this.sentRequest != true )
        {
            this.sentRequest = true;

            var input = $( "#" + this.elementId );

            self.form = $('#' + self.elementId + '_form');

            if ( self.form && self.form.length == 0 )
            {
                self.form = $("<form id='" + self.elementId + "_form' method='POST' action='" + fileResponderUrl + "' enctype='multipart/form-data' target='"+ self.elementId + "_frame'></form>");
                self.form.hide();
            }

            self.frame = $('#' + self.elementId + '_frame');

            if ( self.frame && self.frame.length == 0 )
            {
                self.frame = $("<iframe id='" + self.elementId + "_frame' name='" + self.elementId + "_frame' src='' style='width: 100px; height: 100px;'></iframe>");
                self.frame.hide();
            }

            var cloneInput = input.clone();
            cloneInput.val('');
            cloneInput.attr('disabled','disabled');

            cloneInput.change( function(){OW.trigger('mailbox.attach_file', [{input_id: self.elementId}])} );
            cloneInput.appendTo('.' + self.elementId + '_class');

            input.attr('name', 'attachmet');
            input.appendTo(self.form);
            //input.unbind();

            input.attr('id', self.elementId +'_input' );

            $('body').append(self.frame);
            $('body').append(self.form);

            self.form.submit();
        }
    }

    this.sendComplete= function( $param )
	{
        var input = $( "#" + this.elementId );

        if ( self.form && self.form.length > 0 )
        {
            self.form.remove();
            self.form = undefined;
        }

        if ( self.frame && self.frame && self.frame.length > 0 )
        {
            self.frame.remove();
            self.frame = undefined;
        }

        input.removeAttr('disabled');

        this.sentRequest = false;

        if ( $param.error != true )
        {
            self.displayUploadFile( $param );
        }
        else
        {
            OW.error( $param.message );
        }
    }

    this.deleteUploadFile = function( $element, $hash )
	{
        if( self.sentRequest == false )
        {
            self.sentRequest == true;

            var input = $( "#" + this.elementId );
            input.attr('disabled','disabled');

            $.ajax( {
                url: ajaxResponderUrl,
                type: 'POST',
                data: {function_: 'deleteUploadFile', hash: $hash},
                dataType: 'json',
                success: function( data )
                {
                    self.sentRequest == false;
                    input.removeAttr('disabled');

                    if( data.result == true )
                    {
                        $element.unbind();
                        $element.remove();

                        if( self.uploadFileList && self.uploadFileList.find(".file").length == 0 )
                        {
                            self.uploadFileList.find( ".ow_attachments_label" ).hide();
                        }
                    }
                    else if( data.error != undefined )
                    {
                        OW.error( data.error );
                    }
                }
            } );
        }

    }

    this.displayUploadFile  = function( $param )
    {
        var $filename = $param.filename;
        var $filesize = $param.filesize
        var $hash = $param.hash;

        var $link = $( attachFileHtml );
        $link.attr('id',$hash);
        $link.find('.file').text($filename);
        $link.find('.filesize').text($filesize);

        var $a = $link.find('.ow_delete_attachment')

        $a.click( function() { self.deleteUploadFile( $link, $hash ) } );

        $link.bind( "mouseover", function(){ $(this).find('.ow_delete_attachment').show(); })
             .bind( "mouseout", function(){ $(this).find('.ow_delete_attachment').hide(); });

        $link.find("a.file").click( function(){ OW.trigger('mailbox.delete_attach_file', [{element: $link, hash: $hash}]); } );
        self.uploadFileList.append($link);

        self.uploadFileList.find( ".ow_attachments_label" ).show();
    }

    this.displayFileList = function( $list )
	{
        $.each( $list,
            function( i, item )
            {
                self.displayUploadFile(item);
            } );

        this.element.val($list);
    }

    this.reset = function()
	{
        self.uploadFileList.find( ".ow_attachments_label" ).hide();
        self.uploadFileList.find("div.ow_mailbox_attachment").remove();
    }
}
