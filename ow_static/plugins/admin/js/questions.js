var QuestionUtils = function()
{
   this.in_array = function (what, where)
   {

        var a=false;

        for(var i=0; i<where.length; i++)
        {
            if(what == where[i])
            {
                a=true;
                break;
            }
        }
        return a;
    }
}

var qUtils = new QuestionUtils();

var addQuestion = function( types )
{
    var self = this;
    var types = types;
    var answer_type='show';

    var $possible_values_tr = $("#qst_add_form .tr_qst_possible_values");
    var $columns_count_tr = $("#qst_add_form .tr_qst_column_count");
    var $answer_type = $("#qst_add_form .qst_answer_type");
    var $form_tr = $("#qst_add_form tr:not(.tr_qst_submit)");

    /* $answer_type.change( function(){ self.displayPossibleValues(); }  );

    this.displayPossibleValues = function()
    {
        if( qUtils.in_array( $answer_type.val(), types ) )
        {
            if( answer_type != 'show' )
            {
                answer_type = 'show';
                $possible_values_tr.show();
                $columns_count_tr.show();
                $form_tr.removeClass('ow_alt1');
                $form_tr.removeClass('ow_alt2');
                $("#qst_add_form tr:not(.tr_qst_submit):even").addClass('ow_alt2');
                $("#qst_add_form tr:not(.tr_qst_submit):odd").addClass('ow_alt1');
            }
        }
        else
        {
            if( answer_type != 'hide' )
            {
                answer_type = 'hide';
                $possible_values_tr.hide();
                $columns_count_tr.hide();
                $form_tr.removeClass('ow_alt1');
                $form_tr.removeClass('ow_alt2');
                $("#qst_add_form tr:not(.tr_qst_submit,.tr_qst_possible_values):even").addClass('ow_alt2');
                $("#qst_add_form tr:not(.tr_qst_submit,.tr_qst_possible_values):odd").addClass('ow_alt1');
            }
        }
    } */
}

var editQuestion = function( $params )
{
    var self = this;
    //var types = $params.types;
    var answer_type='show';

    self.sentRequest = false;
    self.responderUrl = $params.ajaxResponderUrl;
    self.deleteValueConfirmMessage = OW.getLanguageText('admin', 'questions_edit_delete_value_confirm_message');

    var $form = $("#qst_edit_form");
    var $possible_values_tr = $("#qst_edit_form tr.tr_qst_possible_values");
    var $answer_type = $("#qst_edit_form .qst_answer_type");
    var $form_tr = $("#qst_edit_form tr:not(.tr_qst_submit)");
    var $sortable = $form.find(".question_values");
    var $questionValueTemplate = $form.find(".question_value_block_template");
    var $questionName = $("#edit_questionName");

    $sortable.find( '.question_value_block' ).bind( "mouseover", function(){ $(this).find('.quest_buttons').show(); } )
                    .bind( "mouseout", function(){ $(this).find( '.quest_buttons' ).hide(); } );

    $sortable.find('.quest_buttons').hide();

    $sortable.sortable({
        cursor: 'move',
        items: '.question_value_block'
    });

    $form.submit(
        function()
        {
            var r = {};
            $sortable.find('.question_value_block').each(function(order, o){
                r[$(o).attr('question_value')] = order;
            });

            $("#question_values_order").val( JSON.stringify(r) );
        } );

   this.editQuestionValue = function( element ){
         var $element = element;
         var $name = $questionName.val();
         var $val = $(element).parents("div:eq(0)").parents("div:eq(0)").attr("question_value");
         var $lang_key = 'questions_question_' + $name + '_value_' + $val;

         editLangValue('base', $lang_key, OW.getLanguageText('admin', 'questions_edit_question_value_title'), function($data) { $($element).parents("div:eq(0)").find(".quest_value").text($data.value) } );
    };

    $sortable.find('.question_value_delete').click( function(){ self.deleteValue( this ) });
    $sortable.find('.question_value_edit').click( function(){ self.editQuestionValue( this ) });

    $("#edit_questionNameLang").click( function(){
         var $element = this;
         var $name = $questionName.val();
         var $val = $(this).parents("div:eq(0)").parents("div:eq(0)").attr("question_value");
         var $lang_key = 'questions_question_' + $name + '_label';

         editLangValue('base', $lang_key, OW.getLanguageText('admin', 'questions_edit_question_name_title'),function($data)
         {
             var $value = $.trim($data.value);

             if( $value !== undefined && $value.length === 0 || !($.trim($value)) )
             {
                $value = OW.getLanguageText('admin', 'questions_empty_lang_value');
             }
             
             $($element).text($value)
         } );
    });

    $("#edit_questionDescriptionLang").click( function(){
         var $element = this;
         var $name = $questionName.val();

         var $lang_key = 'questions_question_' + $name + '_description';

         editLangValue('base', $lang_key, OW.getLanguageText('admin', 'questions_edit_question_description_title'), function($data)
         {
             var $value = $.trim($data.value);

             if( $value !== undefined && $value.length === 0 )
             {
                $value = OW.getLanguageText('admin', 'questions_empty_lang_value');
             }

             $($element).text($value)
         } );
    } );

    this.deleteValue = function( $element )
    {
        if( self.sentRequest == false )
        {
            if( confirm( self.deleteValueConfirmMessage ) )
            {
                self.sentRequest = true;

                $.ajax( {
                    url: self.responderUrl,
                    type: 'POST',
                    data: { command: 'DeleteQuestionValue', questionName: $('#edit_questionName').val(), value: $($element).parents('.question_value_block').attr('question_value') },
                    dataType: 'json',
                    success: function( data )
                    {
                        self.sentRequest = false;

                        if( data.result == true )
                        {
                             $($element).parents('.question_value_block').remove();
                        }
                    }
                } );
            }
        }
    }

    this.displayAddValues = function( $values )
    {
        var itemsCount = 0;
        $.each($values, function( key, item )
        {
            itemsCount++;
            var $value = $questionValueTemplate.clone()
            $value.removeClass('question_value_block_template');
            $value.addClass('question_value_block');
            $value.attr('question_value', key);
            $value.find('div.quest_value').text( item );

            $value.find('.question_value_delete').click( function(){ self.deleteValue( this ) } )
            $value.find('.question_value_edit').click( function(){ self.editQuestionValue( this ) } );
            
            $value.bind( "mouseover", function(){ $(this).find('.quest_buttons').show(); } )
                .bind( "mouseout", function(){ $(this).find('.quest_buttons').hide(); } );
            $value.find('.quest_buttons').hide();
            $value.show();
            $sortable.append($value);
        });

        if( itemsCount > 0 )
        {
            $sortable = $form.find(".question_values");
            $sortable.sortable( 'refresh' );
        }
    }
}

var indexQuestions = function( $params )
{
    var self = this;
    var $questionAddUrl = $params.questionAddUrl;
    this.responderUrl = $params.ajaxResponderUrl;

    var $questionDiv = $('.ow_admin_profile_questions_list_div');
    var $questionTable = $('.ow_admin_profile_questions_list');
    var $questionTr = $questionTable.find('.question_tr');
    this.oldSection = undefined;

    $('.question_values').click( function()  { $(this).parents('center:eq(0)').next('div').toggle(); } );
    $('.add_new_question_button').click( function()  { window.location = $questionAddUrl } );

    $('.question_delete_button').click(
        function()
        {
            if( !confirm(OW.getLanguageText('admin', 'questions_delete_question_confirmation')) )
            {
                return false;
            }
        } );

    $('.section_delete_button').click(
        function(){
            if( !confirm(OW.getLanguageText('admin', 'questions_delete_section_confirmation')) )
            {
                return false;
            }
        } );

    $questionTable.find("tr.question_section_tr, tr.question_tr").bind( "mouseover", function(){ $(this).find(".delete_edit_buttons a").show(); } )
	   				 .bind( "mouseout", function(){ $(this).find(".delete_edit_buttons a").hide(); } );

    $(".edit_sectionNameLang").click( function() {
        
         var $tr = $(this).parents("tr:eq(0)");
         var $element = $tr.find(".section_value");
         var $name = $(this).parents(".ow_admin_profile_questions_list:eq(0)").attr("sectionName");
         var $lang_key = 'questions_section_' + $name + '_label';

         editLangValue('base', $lang_key, function($data)
         {
             var $value = $.trim($data.value);

             $($element).text($value)
         } );
         
     });

     $questionDiv.sortable({

       items: '.ow_admin_profile_questions_list',
       cancel: 'no_section',
       cursor: 'move',
       tolerance: 'pointer',
       handle: '.question_section_tr',
       placeholder: 'forum_placeholder ow_table_2 ow_smallmargin ow_admin_content',
       forcePlaceholderSize: true,
       
       update: function(event, ui)
       {
            var order = {};

            $questionDiv.find('.ow_admin_profile_questions_list:not(.no_section)').each(function(ord, o){
                order[$(o).attr('sectionName')] = ord;
            });

            $.ajax( {
                    url: self.responderUrl,
                    type: 'POST',
                    data: {
                               command: 'sortSection',
                               sectionOrder:JSON.stringify(order)
                           },
                    dataType: 'json'
                } );
        },

        start: function(event, ui)
        {
            $(ui.placeholder).append('<table class="ow_table_2 ow_smallmargin"><tr><td colspan="9"><div style="width:869px;"></div></td></tr></table>');
            $questionDiv.sortable( 'refreshPositions' );
        },

        stop: function(event, ui) {
        },

        helper: function(event, ui)
        {
            var itemWidth = ui.outerWidth();
            if (itemWidth > 160)
            {
                var k = 160 / ui.outerWidth();
                var offset = k * (event.pageX - ui.position().left);
                $(this).sortable( 'option', 'cursorAt', {left: offset } );
            }

            return $('<div class="ow_dnd_helper" style="width: 180px;height: 30px; text-align:center; vertical-align:middle;"></div>');
        }

     });

     $questionTable.sortable(
     {
       items: '.question_tr',
       cursor: 'move',
       placeholder: 'forum_placeholder',
       snap: true,
       snapToleranse: 50,
       forcePlaceholderSize: true,
       connectWith: '.ow_admin_profile_questions_list:not(.no_section)',

        update: function(event, ui) {

             var newSection = ui.item.parents(".ow_admin_profile_questions_list:eq(0)");

             if( ui.sender )
             {
                  var orderOld = {};

                  ui.sender.find('.question_tr:not(.no_question)').each(function(order, o){
                            orderOld[$(o).attr('question_name')] = order;
                            });

                  var $oldSectionName = ui.sender.attr("sectionName");

                  $.ajax( {
                            url: self.responderUrl,
                            type: 'POST',
                            data: {
                                       command: 'sortQuestions',
                                       sectionName: $oldSectionName,
                                       questionOrder:JSON.stringify(orderOld)

                                   },
                            dataType: 'json'
                        } );
               }

               var orderNew = {};

               newSection.find('.question_tr:not(.no_question)').each(function(order, o){
                    orderNew[$(o).attr('question_name')] = order;
                });

               var $question_tr = $questionTable.find(".question_tr:not(.no_question)");
               $question_tr.removeClass('ow_alt1');
               $question_tr.removeClass('ow_alt2');

               $questionTable.find('.question_tr:not(.no_question):odd').addClass('ow_alt2');
               $questionTable.find('.question_tr:not(.no_question):even').addClass('ow_alt1');

               var $newSectionName =  newSection.attr("sectionName");

               $.ajax( {
                    url: self.responderUrl,
                    type: 'POST',
                    data: {
                               command: 'sortQuestions',
                               sectionName: $newSectionName,
                               questionOrder:JSON.stringify(orderNew)

                           },
                    dataType: 'json'
                } );

        },

        start: function(event, ui)
        {
            $(ui.placeholder).append('<td colspan="9" style="width:869px;"></td>');
	},

        helper: function(event, ui)
        {
            this.oldSection = ui.parents(".ow_admin_profile_questions_list:eq(0)");
            var itemWidth = ui.outerWidth();
            if (itemWidth > 160)
            {
                var k = 160 / ui.outerWidth();
                var offset = k * (event.pageX - ui.position().left);
                $(this).sortable( 'option', 'cursorAt', {left: offset } );
            }

            return $('<div class="ow_dnd_helper" style="width: 180px; height: 30px; text-align:center; vertical-align:middle;"></div>');
        }

    });
}

var editAccountType = function( $params )
{
    var self = this;

    this.sentRequest = false;
    this.responderUrl = $params.ajaxResponderUrl;

    var $accountTypeTable = $('.account_type');
    var $accountTypeTR = $accountTypeTable.find('tr:not(.tr_first, .account_type_template)');
    var $accountTypeTemplate = $accountTypeTable.find('table tr.account_type_template');
    var $sortable = $(".account_type");
    var $sortableTr = $sortable.find('.account_type_tr');

    $accountTypeTR.bind( "mouseover", function(){ $(this).find('.edit_accont_type, .delete_accont_type').show(); } )
                    .bind( "mouseout", function(){$(this).find('.edit_accont_type, .delete_accont_type').hide(); } );

    $('.delete_accont_type').click(
        function(){
            if( !confirm(OW.getLanguageText('admin', 'questions_delete_account_type_confirmation')) )
            {
                return false;
            }
        } );

    $(".edit_accont_type").click( function() {

         var $tr = $(this).parents("tr:eq(0)");
         var $element = $tr.find(".account_type_value");
         var $name = $tr.attr("account_type_name");
         var $lang_key = 'questions_account_type_' + $name;

         editLangValue('base', $lang_key, function($data)
         {
             var $value = $.trim($data.value);

             $($element).text($value)
         } );
    });

    $sortable.sortable({
        
       items: '.account_type_tr',
       cursor: 'move',
       placeholder: 'forum_placeholder',
       forcePlaceholderSize: true,

        update: function(event, ui) {
            
            var r = {};
            $sortable.find('.account_type_tr').each(function(order, o){
                r[$(o).attr('account_type_name')] = order;
            });

            $sortableTr.removeClass('ow_alt1');
            $sortableTr.removeClass('ow_alt2');

            $sortable.find('.account_type_tr:odd').addClass('ow_alt2');
            $sortable.find('.account_type_tr:even').addClass('ow_alt1');

            $.ajax( {
                    url: self.responderUrl,
                    type: 'POST',
                    data: { command: 'sortAccountType', accountTypeList:JSON.stringify(r) },
                    dataType: 'json'
                } );
        },

        start: function(event, ui)
        {
            $(ui.placeholder).append('<td colspan="3"></td>');
	},

        stop: function(event, ui)
        {
            //$(ui.item).show().css('opacity', '1');
        },

        helper: function(event, ui)
        {
            var itemWidth = ui.outerWidth();
            if (itemWidth > 160)
            {
                var k = 160 / ui.outerWidth();
                var offset = k * (event.pageX - ui.position().left);
                $(this).sortable( 'option', 'cursorAt', {left: offset } );
            }

            return $('<div class="ow_dnd_helper" style="width: 350px; height: 30px; text-align:center; vertical-align:middle;"></div>');
        }

    });
}



