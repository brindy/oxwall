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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.links.controllers
 * @since 1.0
 */
class LINKS_CTRL_Save extends OW_ActionController
{

    public function index( $params )
    {

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $eventParams = array('pluginKey' => 'links', 'action' => 'add_link');
        $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);

        if ( !OW::getUser()->isAuthorized('links', 'add') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');

            return;
        }
        else
        {
            if ( $credits === false )
            {
                $this->assign('authMsg', OW::getEventManager()->call('usercredits.error_message', $eventParams));
            }
            else
            {
                $this->assign('authMsg', null);
            }
        }

        $plugin = OW::getPluginManager()->getPlugin('links');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $plugin->getKey(), 'main_menu_item');

        $id = empty($params['id']) ? null : $params['id'];

        $service = LinkService::getInstance();

        if ( $id !== null && intval($id) > 0 )
        {
            $this->setPageHeading(OW::getLanguage()->text('links', 'edit_page_heading'));
            OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'edit_page_heading'));

            $link = $service->findById($id);

            $eventParams = array(
                'action' => LinkService::PRIVACY_ACTION_VIEW_LINKS,
                'ownerId' => $link->userId
            );

            $privacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if (!empty($privacy))
            {
                $link->setPrivacy($privacy);
            }
        }
        else
        {
            $this->setPageHeading(OW::getLanguage()->text('links', 'save_page_heading'));
            OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'save_page_heading'));
            /*
              @var $link Link
             */
            $link = new Link();

            $eventParams = array(
                'action' => LinkService::PRIVACY_ACTION_VIEW_LINKS,
                'ownerId' => OW::getUser()->getId()
            );

            $privacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if (!empty($privacy))
            {
                $link->setPrivacy($privacy);
            }

            $link->setUserId(OW::getUser()->getId());
        }
        $this->setPageHeadingIconClass('ow_ic_link');

        $form = new LinkSaveForm($link);
        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $form->process();
            $this->redirect(OW::getRouter()->urlForRoute('link', array('id' => $link->getId())));
        }
    }

    public function delete( $params )
    {
        $id = $params['id'];

        $service = LinkService::getInstance();

        $dto = $service->findById($id);

        $service->delete($dto);

        BOL_FlagService::getInstance()->deleteByTypeAndEntityId('link', $id);

        $this->redirect(OW::getRouter()->urlForRoute('links'));
    }
}

class LinkSaveForm extends Form
{
    /**
     * 
     * @var Link
     */
    private $link;

    public function __construct( $link )
    {
        parent::__construct('save');

        $language = OW::getLanguage()->getInstance();

        $this->link = $link;
        $urlField = new TextField('url');

        $urlField->setHasInvitation(true)->setInvitation('http://www.example.com');

        $this->addElement(
            $urlField->setRequired(true)->
                addValidator(new UrlValidator())->
                setLabel($language->text('links', 'save_form_url_field_label'))->
                setValue($this->link->getUrl()));

        $titleField = new TextField('title');

        $this->addElement($titleField->setRequired(true)
                ->setLabel($language->text('links', 'save_form_title_field_label'))
                ->setValue($this->link->getTitle())
        );

        $descriptionTextArea = new WysiwygTextarea('description');
        $descriptionTextArea->setLabel($language->text('links', 'save_form_desc_field_label'));
        $descriptionTextArea->setValue($this->link->getDescription());
        $descriptionTextArea->setRequired(true);
            
        $this->addElement($descriptionTextArea);

        $tagService = BOL_TagService::getInstance();

        $tags = array();

        if ( intval($this->link->getId()) > 0 )
        {
            $arr = $tagService->findEntityTags($this->link->getId(), 'link');

            foreach ( (!empty($arr) ? $arr : array() ) as $dto )
            {
                $tags[] = $dto->getLabel();
            }
        }

        $tagsField = new TagsField('tags', $tags);
        $this->addElement($tagsField->setLabel($language->text('links', 'save_form_tags_field_label')));

        $submit = new Submit('submit');

        $this->addElement($submit);
    }

    public function process()
    {
        $service = LinkService::getInstance();
        $data = $this->getValues();
        $data['title'] = UTIL_HtmlTag::stripJs($data['title']);

        $url = (mb_ereg_match('^http(s)?:\\/\\/', $data['url']) ? $data['url'] : 'http://' . $data['url']);

        $this->link->setTimestamp(time())
            ->setUrl($url)
            ->setDescription($data['description'])
            ->setTitle(UTIL_HtmlTag::stripTags($data['title'], $service->getAllowedHtmlTags(), array('*')));

        $tags = array();

        $isNew = empty($this->link->id);

        $service->save($this->link);

        if ( intval($this->link->getId()) > 0 )
            $tags = TagsField::getTags($data['tags']);

        $tagService = BOL_TagService::getInstance();

        $tagService->updateEntityTags($this->link->getId(), 'link', $tags);

        if ( !$isNew )
        {
            return;
        }

        $eventParams = array('pluginKey' => 'links', 'action' => 'add_link');

        if ( OW::getEventManager()->call('usercredits.check_balance', $eventParams) === true )
        {
            OW::getEventManager()->call('usercredits.track_action', $eventParams);
        }

        //Newsfeed
        $event = new OW_Event('feed.action', array(
                'pluginKey' => 'links',
                'entityType' => 'link',
                'entityId' => $this->link->getId(),
                'userId' => $this->link->getUserId()
            ));
        OW::getEventManager()->trigger($event);
    }
}
