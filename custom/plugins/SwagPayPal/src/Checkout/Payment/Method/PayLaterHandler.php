<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Method;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class PayLaterHandler extends AbstractPaymentMethodHandler
{
    protected function isVaultable(): bool
    {
        return false;
    }

    protected function requirePreparedOrder(): bool
    {
        return true;
    }
}
