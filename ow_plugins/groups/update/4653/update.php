<?php

try
{
    $widgetService = Updater::getWidgetService();

    $widget = $widgetService->addWidget('GROUPS_CMP_InviteWidget', false);
    $widgetPlace = $widgetService->addWidgetToPlace($widget, 'group');
    $widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_LEFT);
}
catch ( Exception $e )
{}