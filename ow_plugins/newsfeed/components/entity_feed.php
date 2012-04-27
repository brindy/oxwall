<?php

class NEWSFEED_CMP_EntityFeed extends NEWSFEED_CMP_FeedList
{
    public function getFeed( $params, $needTotal = true )
    {
        $feed = NEWSFEED_BOL_Service::getInstance()->getFeed($params['feedType'], $params['feedId'], $params['displayCount'], $params['startTime']);
        $total = false;
        if ( $needTotal )
        {
            $total = NEWSFEED_BOL_Service::getInstance()->findFeedCount($params['feedType'], $params['feedId']);
        }
        
        return array( 'feed' => $feed, 'total' => $total );
    }
}