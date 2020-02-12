<?php
namespace CohaOrdPosCom\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

class Theme implements SubscriberInterface {

    public static function getSubscribedEvents()
    {
        return [
            'Theme_Inheritance_Template_Directories_Collected' => 'onCollectDirectoriesEvent',
        ];
    }

    public function onCollectDirectoriesEvent(Enlight_Event_EventArgs $args ) {
        // get the current directories
        $directories = $args->getReturn();

        if(Shopware()->Config()->getByNamespace('CohaOrdPosCom', 'show')) {
            // add our own
            array_push(
                $directories,
                __DIR__ . '/../Resources/views/'
            );
        }

        // and we re done
        return $directories;
    }
}