<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V2\Common\Address;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\ItemCollection;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Shipping;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Shipping\Name as ShippingName;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class PurchaseUnitProvider
{
    private AmountProvider $amountProvider;

    private AddressProvider $addressProvider;

    private CustomIdProvider $customIdProvider;

    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(
        AmountProvider $amountProvider,
        AddressProvider $addressProvider,
        CustomIdProvider $customIdProvider,
        SystemConfigService $systemConfigService,
    ) {
        $this->amountProvider = $amountProvider;
        $this->addressProvider = $addressProvider;
        $this->customIdProvider = $customIdProvider;
        $this->systemConfigService = $systemConfigService;
    }

    public function createPurchaseUnit(
        CalculatedPrice $totalAmount,
        CalculatedPrice $shippingCosts,
        ?CustomerEntity $customer,
        ?ItemCollection $itemList,
        CurrencyEntity $currency,
        Context $context,
        bool $isNet,
        ?OrderEntity $order = null,
        ?OrderTransactionEntity $orderTransaction = null,
    ): PurchaseUnit {
        $purchaseUnit = new PurchaseUnit();

        if ($itemList !== null) {
            $purchaseUnit->setItems($itemList);
        }

        $amount = $this->amountProvider->createAmount(
            $totalAmount,
            $shippingCosts,
            $currency,
            $purchaseUnit,
            $isNet
        );

        $purchaseUnit->setAmount($amount);

        $shipping = $this->createShipping($customer, $order);
        if ($shipping !== null) {
            $purchaseUnit->setShipping($shipping);
        }

        if ($orderTransaction !== null) {
            $purchaseUnit->setCustomId($this->customIdProvider->createCustomId($orderTransaction, $context));
        }

        $orderNumber = $order?->getOrderNumber();

        if ($orderNumber !== null && $this->systemConfigService->getBool(Settings::SEND_ORDER_NUMBER, $order?->getSalesChannelId())) {
            $orderNumberPrefix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_PREFIX, $order?->getSalesChannelId());
            $orderNumberSuffix = $this->systemConfigService->getString(Settings::ORDER_NUMBER_SUFFIX, $order?->getSalesChannelId());
            $orderNumber = $orderNumberPrefix . $orderNumber . $orderNumberSuffix;
            $purchaseUnit->setInvoiceId($orderNumber);
        }

        return $purchaseUnit;
    }

    private function createShipping(?CustomerEntity $customer, ?OrderEntity $order): ?Shipping
    {
        $shippingAddress = $order?->getDeliveries()?->first()?->getShippingOrderAddress() ?? $customer?->getActiveShippingAddress();
        if ($shippingAddress === null) {
            return null;
        }

        $shipping = new Shipping();
        $address = new Address();
        $this->addressProvider->createAddress($shippingAddress, $address);
        $shipping->setAddress($address);
        $shipping->setName($this->createShippingName($shippingAddress));

        return $shipping;
    }

    private function createShippingName(CustomerAddressEntity|OrderAddressEntity $shippingAddress): ShippingName
    {
        $shippingName = new ShippingName();
        $shippingName->setFullName(\sprintf('%s %s', $shippingAddress->getFirstName(), $shippingAddress->getLastName()));

        return $shippingName;
    }
}
