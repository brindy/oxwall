<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.admin.class
 * @since 1.0
 */
class ColorField extends FormElement
{

    // need to remake with getElementJs method
    public function __construct( $name )
    {
        parent::__construct($name);
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('admin')->getStaticJsUrl() . 'color_picker.js');
    }

    /**
     * @see FormElement::renderInput()
     *
     * @param array $params
     * @return string
     */
    public function renderInput( $params = null )
    {
        parent::renderInput($params);

        $output = '<div class="color_input"><input type="text" id="colorh_' . $this->getId() . '" name="' . $this->getName() . '" ' . ( $this->getValue() !== null ? '" value="' . $this->getValue() . '"' : '' ) . ' />' .
            '&nbsp;<input type="button" class="color_button" id="color_' . $this->getId() . '" style="background:' . ( $this->getValue() !== null ? $this->getValue() : '' ) . '" />
        <div style="display:none;"><div id="colorcont_' . $this->getId() . '"></div></div></div>';

        $varName = rand(10, 100000);

        $js = "var callback" . $varName . " = function(color){
            $('#colorh_" . $this->getId() . "').attr('value', color);
            $('#color_" . $this->getId() . "').css({backgroundColor:color});
            window.colorPickers['" . $this->getId() . "'].close();
        };
        new ColorPicker($('#colorcont_" . $this->getId() . "'), callback" . $varName . ", '" . $this->getValue() . "');
        $('#color_" . $this->getId() . "').click(
            function(){
                if( !window.colorPickers )
                {
                    window.colorPickers = {};
                }
                window.colorPickers['" . $this->getId() . "'] = new OW_FloatBox({\$contents:$('#colorcont_" . $this->getId() . "'), \$title:'Color Picker'});
            }
        );";

        OW::getDocument()->addOnloadScript($js);

        return $output;
    }
}