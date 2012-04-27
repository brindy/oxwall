<?php

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
 * Virtual Gifts admin action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.controllers
 * @since 1.0
 */
class VIRTUALGIFTS_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * Returns menu component
     *
     * @return BASE_CMP_ContentMenu
     */
    private function getMenu()
    {
        $language = OW::getLanguage();
        $menuItems = array();
        
        $item = new BASE_MenuItem();
        $item->setLabel($language->text('virtualgifts', 'existing_gifts'));
        $item->setUrl(OW::getRouter()->urlForRoute('virtual_gifts_templates'));
        $item->setKey('templates');
        $item->setIconClass('ow_ic_heart');
        $item->setOrder(0);

        array_push($menuItems, $item);
        
        $item = new BASE_MenuItem();
        $item->setLabel($language->text('virtualgifts', 'categories'));
        $item->setUrl(OW::getRouter()->urlForRoute('virtual_gifts_categories'));
        $item->setKey('categories');
        $item->setIconClass('ow_ic_folder');
        $item->setOrder(1);

        array_push($menuItems, $item);
        
        $menu = new BASE_CMP_ContentMenu($menuItems);

        return $menu;
    }
    
    /**
     * Default action
     */
	public function index()
	{
        $language = OW::getLanguage();
        $giftsService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
                
        $form = new AddTemplateForm();
        $this->addForm($form);
        
        if ( OW::getRequest()->isPost() && $_POST['form_name'] == 'add-template-form' && $form->isValid($_POST) )
        {
        	if ( empty($_FILES['file']['tmp_name']) )
        	{
        		OW::getFeedback()->warning($language->text('virtualgifts', 'no_image_selected'));
        		$this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_templates'));
        	}
        	
            $values = $form->getValues();
            
            $template = new VIRTUALGIFTS_BOL_Template();
            if ( !empty($values['category']) )
            {
                $template->categoryId = (int) $values['category'];
            }
            $template->uploadTimestamp = time();
            $template->price = abs(floatval($values['price']));
            $template->extension = UTIL_File::getExtension($_FILES['file']['name']);

            if ( !$giftsService->extIsAllowed($template->extension) )
            {
            	OW::getFeedback()->warning($language->text('virtualgifts', 'file_type_not_allowed'));
            	$this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_templates'));
            }
            
            $res = $giftsService->addTemplate($template, $_FILES['file']['tmp_name']);
            
            if ( $res )
            {
                OW::getFeedback()->info($language->text('virtualgifts', 'template_added'));
                
                $event = new BASE_CLASS_EventCollector('usercredits.action_add');
                
                $event->add(array(
                    'pluginKey' => 'virtualgifts',
                    'action' => 'template_' . $template->id,
                    'amount' => $template->price == 0 ? 0 : -$template->price,
                    'hidden' => 1
                ));

                OW::getEventManager()->trigger($event);
            }
            else 
            {
            	OW::getFeedback()->warning($language->text('virtualgifts', 'template_not_added'));
            }
            
            $this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_templates'));
        }
        
        $this->addComponent('menu', $this->getMenu());
        
        $categoriesSetup = $giftsService->categoriesSetup();
        
        if ( $categoriesSetup )
        {
            $templates = $giftsService->getTemplateListByCategories();
            if ( !$templates )
            {
                $templates = $giftsService->getTemplateList();
                $categoriesSetup = false;
            }
        }
        else 
        {
            $templates = $giftsService->getTemplateList();
        }
        
        $this->assign('categoriesSetup', $categoriesSetup);
        
        $this->assign('uncategorized', $giftsService->getUncategorizedTemplateList());
        
        $this->assign('templates', $templates);
        
        $this->assign('setPrice', OW::getPluginManager()->isPluginActive('usercredits'));
        
        $language->addKeyForJs('admin', 'btn_label_edit');
        
        $this->setPageHeading(OW::getLanguage()->text('virtualgifts', 'admin_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
        
        $url = OW::getPluginManager()->getPlugin('virtualgifts')->getStaticCssUrl() . 'style.css';
        OW::getDocument()->addStyleSheet($url);
        
        $script = 
        '$(".ow_gift_holder .gift_delete_btn").click(function(){
            if ( confirm(' . json_encode($language->text('virtualgifts', 'confirm_template_delete')) . ') ) 
            {
                var set = {};
                set["tpl-list[0]"] = $(this).attr("rel");
                $.post('. json_encode(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Admin', 'ajaxDeleteTemplates')) . ', 
                    set,
                    function(){
                        OW.info(' . json_encode($language->text('virtualgifts', 'gift_deleted')) . '); 
                        document.location.reload();
	                }
                );
            }
        });
        
        $("#btn_delete_common").click(function(){
            if ( confirm(' . json_encode($language->text('virtualgifts', 'confirm_template_delete')) . ') ) 
            {
                var set = {};
                var count = 0;
                
                $("input[type=checkbox]:checked", ".ow_gift_holder").each(function(i){
                    set["tpl-list["+i+"]"] = $(this).val();
                    count = count + 1;
                });

                if ( !count ) return false;
                
                $.post('. json_encode(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Admin', 'ajaxDeleteTemplates')) . ', 
                    set, 
                    function(){
                        OW.info(' . json_encode($language->text('virtualgifts', 'gift_deleted')) . '); 
                        document.location.reload();
	                }
	            );
            }
        });
        
        $("#check_all_tpls").change(function(){
            $("input[type=checkbox]", ".ow_gift_holder").attr("checked", this.checked);
        });
        
        $("#btn_edit_common").click(function(){
            var tpls = {};
            var count = 0;
            
            $("input[type=checkbox]:checked", ".ow_gift_holder").each(function(i){
                tpls[i] = $(this).val();
                count = count + 1;
            });
            
            if ( !count ) return false;
            var fb = OW.ajaxFloatBox("VIRTUALGIFTS_CMP_TemplateEdit", [tpls], {width: 400, title: '.json_encode($language->text('admin', 'btn_label_edit')).'});
        });
        
        $(".ow_gift_holder .gift_edit_btn").click(function(){
            var tpls = {};
            tpls[0] = $(this).attr("rel");
            var fb = OW.ajaxFloatBox("VIRTUALGIFTS_CMP_TemplateEdit", [tpls], {width: 400, title: '.json_encode($language->text('admin', 'btn_label_edit')).'});
        });
        ';
        
        OW::getDocument()->addOnloadScript($script);
	}
	
	public function editTemplate( $params )
	{
        if ( OW::getRequest()->isPost() && $_POST['form_name'] == 'edit-template-form' )
        {
            $tpls = explode('|', $_POST['tplId']);
            
            if ( !count($tpls) )
            {
                $this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_templates'));
            }
            
            $giftsService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
            foreach ( $tpls as $tplId )
            {
                $dto = $giftsService->findTemplateById((int)$tplId);
                
                if ( !$dto )
                {
                    continue;
                }
            
                $dto->categoryId = !empty($_POST['category']) ? (int) $_POST['category'] : null;
                
                $dto->price = abs(floatval($_POST['price']));
                
                $giftsService->updateTemplate($dto);
                
                $event = new BASE_CLASS_EventCollector('usercredits.action_update');
                $event->add(array(
                    'pluginKey' => 'virtualgifts',
                    'action' => 'template_' . $tplId,
                    'amount' => $dto->price == 0 ? 0 : -$dto->price,
                    'hidden' => 1
                ));

                OW::getEventManager()->trigger($event);
                
                if ( !empty($_FILES['file']['tmp_name']) )
                {
                    $extension = UTIL_File::getExtension($_FILES['file']['name']);
        
                    if ( $giftsService->extIsAllowed($extension) )
                    {
                        $giftsService->updateTemplateImage($dto, $_FILES['file']);
                    }                    
                }
            }
            
            OW::getFeedback()->info(OW::getLanguage()->text('virtualgifts', 'template_updated'));
        }
        
        $this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_templates'));
	}
	
	public function ajaxDeleteTemplates()
	{
	    if ( empty($_POST['tpl-list']) )
	    {
            return;
	    }

	    $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
	    $event = new BASE_CLASS_EventCollector('usercredits.action_delete');
	    $trigger = false;
	    
        foreach ( $_POST['tpl-list'] as $tplId )
        {
            if ( $giftService->deleteTemplate($tplId) )
            {
                $event->add(array(
                    'pluginKey' => 'virtualgifts',
                    'action' => 'template_' . $tplId
                ));
                $trigger = true;
            }
        }
        
        if ( $trigger )
        {
            OW::getEventManager()->trigger($event);
        }
	}
	
	public function categories()
	{
        $language = OW::getLanguage();
        $giftsService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        if ( !empty($_GET['catId']) )
        {
            $tpls = $giftsService->findTemplatesByCategory($_GET['catId']);
            
            if ( $tpls )
            {
                OW::getFeedback()->warning($language->text('virtualgifts', 'category_not_deleted'));
            }
            
        	else if ( $giftsService->deleteCategory(intval($_GET['catId'])) )
        	{
                OW::getFeedback()->info($language->text('virtualgifts', 'category_deleted'));
        	}
        	
        	$this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_categories'));
        }
        
        $form = new AddCategoryForm();
        $this->addForm($form);
        
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $values = $form->getValues();
            
            $title = htmlspecialchars($values['catTitle']);
            $catSetup = $giftsService->categoriesSetup();
            
            if ( !$catId = $giftsService->addCategory($title) )
            {
                OW::getFeedback()->info($language->text('virtualgifts', 'category_not_added'));
                $this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_categories'));
            }
            
            if ( !$catSetup )
            {
                $tpls = $giftsService->findAllTemplates();
                foreach ( $tpls as $template )
                {
                    $template->categoryId = $catId;
                    $giftsService->updateTemplate($template);
                }
            }
            
            OW::getFeedback()->info($language->text('virtualgifts', 'category_added'));
            $this->redirect(OW::getRouter()->urlForRoute('virtual_gifts_categories'));
        }
        
        $menu = $this->getMenu();
        $menu->getElement('categories')->setActive(true);
        $this->addComponent('menu', $menu);
        
        $categories = $giftsService->getCategories();
        $this->assign('categories', $categories);
        
        $this->setPageHeading(OW::getLanguage()->text('virtualgifts', 'admin_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
        
        $baseJsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
        OW::getDocument()->addScript($baseJsDir . "jquery-ui-1.8.9.custom.min.js");
        
        OW::getDocument()->addOnloadScript(
            '$("#categories_tbl tbody").sortable({
                cursor: "move",
                update: function(event, ui){
                    if (ui.sender ) { return; }
    
                    var set = {};
    
                    $("tr", "#categories_tbl tbody").each(function(i){
                        set["category-list["+i+"]"] = $(this).attr("rel");
                    });

                    var url = '.json_encode(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Admin', 'ajaxReorderCategories')).';
                    $.post(url, set);
                }
	        }).disableSelection();'
        );
	}

    public function uninstall()
    {
        if ( isset($_POST['action']) && $_POST['action'] == 'delete_content' )
        {
            OW::getConfig()->saveConfig('virtualgifts', 'uninstall_inprogress', 1);
            
            VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance()->setMaintenanceMode(true);
            
            OW::getFeedback()->info(OW::getLanguage()->text('virtualgifts', 'plugin_set_for_uninstall'));
            $this->redirect();
        }
              
        $this->setPageHeading(OW::getLanguage()->text('virtualgifts', 'page_title_uninstall'));
        $this->setPageHeadingIconClass('ow_ic_delete');
        
        $this->assign('inprogress', (bool) OW::getConfig()->getValue('virtualgifts', 'uninstall_inprogress'));
        
        $js = new UTIL_JsGenerator();
        $js->jQueryEvent('#btn-delete-content', 'click', 'if ( !confirm("'.OW::getLanguage()->text('virtualgifts', 'confirm_delete_gifts').'") ) return false;');
        
        OW::getDocument()->addOnloadScript($js);
    }
    
    public function ajaxReorderCategories( )
    {
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        if ( !empty($_POST['category-list']) )
        {
            foreach ( $_POST['category-list'] as $order => $id )
            {
                $dto = $giftService->findCategoryById($id);
                if ( empty($dto) )
                {
                    continue;
                }
                
                $dto->order = $order + 1;
                $giftService->updateCategory($dto);
            }
        }        
    }
}


/**
 * Add gift template form class
 */
class AddTemplateForm extends Form
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('add-template-form');

        $this->setEnctype('multipart/form-data');
        $language = OW::getLanguage();

        $file = new FileField('file');
        $file->setLabel($language->text('virtualgifts', 'gift_image'));
        $this->addElement($file);
        
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        if ( $giftService->categoriesSetup() )
        {
	        $categories = new Selectbox('category');
	        $categories->setLabel($language->text('virtualgifts', 'category'));
	        $categories->setOptions($giftService->getCategories());
	        $this->addElement($categories);
        }
        
        if ( OW::getPluginManager()->isPluginActive('usercredits') )
        {
            $price = new TextField('price');
            $price->setLabel($language->text('virtualgifts', 'gift_price'));
            $this->addElement($price);
        }
        
        // submit
        $submit = new Submit('add');
        $submit->setValue($language->text('virtualgifts', 'btn_add'));
        $this->addElement($submit);
    }
}

class AddCategoryForm extends Form 
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct('add-category-form');

        $language = OW::getLanguage();

        $title = new TextField('catTitle');
        $title->setLabel($language->text('virtualgifts', 'category_title'));
        $title->setRequired(true);
        $this->addElement($title);
        
        // submit
        $submit = new Submit('add');
        $submit->setValue($language->text('virtualgifts', 'btn_add'));
        $this->addElement($submit);
    }    
}