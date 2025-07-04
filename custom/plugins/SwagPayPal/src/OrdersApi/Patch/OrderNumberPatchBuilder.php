<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Patch;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Patch;

#[Package('checkout')]
class OrderNumberPatchBuilder
{
    public function createRemoveOrderNumberPatch(): Patch
    {
        return (new Patch())->assign([
            'op' => Patch::OPERATION_REMOVE,
            'path' => '/purchase_units/@reference_id==\'default\'/invoice_id',
        ]);
    }
}
