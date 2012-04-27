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
 * Gift template edit component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.virtual_gifts.components
 * @since 1.0
 */
class VIRTUALGIFTS_CMP_TemplateEdit extends OW_Component
{
    public function __construct( $templates )
    {
        parent::__construct();        
       
        $form = new EditTemplateForm($templates);
        $this->addForm($form);
        
        $giftsService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
               
        $categoriesSetup = $giftsService->categoriesSetup();
        $this->assign('categoriesSetup', $categoriesSetup);
        
        $this->assign('setPrice', OW::getPluginManager()->isPluginActive('usercredits'));
        
        $single = count($templates) == 1;
        if ( $single )
        {
            $tpl = $giftsService->findTemplateById($templates[0]);
            $this->assign('imageUrl', $giftsService->getGiftFileUrl($tpl->id, $tpl->uploadTimestamp, $tpl->extension));
            
        }
        
        $this->assign('single', $single);
    }
}

/**
 * Edit gift template form class
 */
class EditTemplateForm extends Form
{
    /**
     * Class constructor
     */
    public function __construct( $tpls )
    {
        parent::__construct('edit-template-form');

        $this->setAction(OW::getRouter()->urlFor('VIRTUALGIFTS_CTRL_Admin', 'editTemplate'));
        
        $single = count($tpls) == 1;

        $this->setEnctype('multipart/form-data');
        $language = OW::getLanguage();
        
        $giftService = VIRTUALGIFTS_BOL_VirtualGiftsService::getInstance();
        
        if ( $single )
        {
            $file = new FileField('file');
            $file->setLabel($language->text('virtualgifts', 'gift_image'));
            $this->addElement($file);
            
            $tpl = $giftService->findTemplateById($tpls[0]);
        }
        
        $tplId = new HiddenField('tplId');
        $tplId->setRequired(true);
        $tplId->setValue(implode('|', $tpls));
        $this->addElement($tplId);
        
        if ( $giftService->categoriesSetup() )
        {
            $categories = new Selectbox('category');
            $categories->setLabel($language->text('virtualgifts', 'category'));
            $categories->setOptions($giftService->getCategories());
            if ( $single )
            {
                $categories->setValue($tpl->categoryId);
            }
            $this->addElement($categories);
        }
        
        if ( OW::getPluginManager()->isPluginActive('usercredits') )
        {
            $price = new TextField('price');
            $price->setLabel($language->text('virtualgifts', 'gift_price'));
            if ( $single )
            {
                $price->setValue($tpl->price);
            }
            $this->addElement($price);
        }
        
        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('virtualgifts', 'btn_save'));
        $this->addElement($submit);
    }
}