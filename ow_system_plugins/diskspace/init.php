<?php

function cloud_file_upload( OW_Event $e )
{
    $params = $e->getParams();

    if ( empty($params['path']) || empty($params['size']) )
    {
        return;
    }

    $filePath = $params['path'];
    $discspace = (int)$params['size'];

    DISKSPACE_BOL_FilesService::getInstance()->addFile($filePath, $discspace);
}

OW::getEventManager()->bind(OW_Storage::EVENT_ON_FILE_UPLOAD, 'cloud_file_upload');

function cloud_file_delete( OW_Event $e )
{
    $params = $e->getParams();
    
    if ( empty($params['path']) )
    {
        return;
    }

    DISKSPACE_BOL_FilesService::getInstance()->deleteFile($params['path']);
}

OW::getEventManager()->bind(OW_Storage::EVENT_ON_FILE_DELETE, 'cloud_file_delete');
