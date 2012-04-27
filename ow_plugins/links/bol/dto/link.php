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
 * @package ow_plugins.links.bol.dto
 * @since 1.0
 */
class Link extends OW_Entity
{
    public $id, $userId, $url, $timestamp, $description, $title, $privacy='everybody';

    /**
     * @return unknown
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param unknown_type $description
     */
    public function setDescription( $description )
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return unknown
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return unknown
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return unknown
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param unknown_type $timestamp
     */
    public function setTimestamp( $timestamp )
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @param unknown_type $url
     */
    public function setUrl( $url )
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param unknown_type $userId
     */
    public function setUserId( $userId )
    {
        $this->userId = $userId;

        return $this;
    }

    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setPrivacy( $privacy )
    {
        $this->privacy = $privacy;

        return $this;
    }

    public function getPrivacy()
    {
        return $this->privacy;
    }
}
?>