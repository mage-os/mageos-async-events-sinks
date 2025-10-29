<?php
namespace MageOS\AsyncEventsAWS\Plugin\MageOS\AsyncEventsAdminUi;

use MageOS\AsyncEventsAdminUi\Ui\Source\Notifiers as Subject;

class UiSourceNotifiers
{
    public function afterToOptionArray(Subject $subject, $result)
    {
        $result[] = [
            'value' => 'eventbridge',
            'label' => 'AWS Event Bridge',
        ];
        return $result;
    }
}
