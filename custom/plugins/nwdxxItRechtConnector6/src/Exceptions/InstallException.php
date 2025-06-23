<?php


namespace Nwdxx\ItRechtConnector6\Exceptions;

use Shopware\Core\Framework\ShopwareHttpException;

class InstallException extends ShopwareHttpException
{
    public function getErrorCode(): string
    {
        return 'FRAMEWORK__PLUGIN_NOT_INSTALLED';
    }
}
