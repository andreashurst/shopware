<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Pagelet\PageletLoadedEvent;
use Shopware\Storefront\Pagelet\Wishlist\GuestWishlistPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCheckoutSubscriber implements EventSubscriberInterface
{
    public const PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID = 'payPalEcsButtonData';

    /**
     * @param EntityRepository<CustomerCollection> $customerRepository
     */
    public function __construct(
        private readonly PayPalExpressCheckoutDataService $expressCheckoutDataService,
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly SystemConfigService $systemConfigService,
        private readonly PaymentMethodUtil $paymentMethodUtil,
        private readonly ExcludedProductValidator $excludedProductValidator,
        private readonly EntityRepository $customerRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            NavigationPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            OffcanvasCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            SearchPageLoadedEvent::class => 'addExpressCheckoutDataToPage',

            'sales_channel.product.search.result.loaded' => 'addExcludedProductsToSearchResult',

            QuickviewPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',
            GuestWishlistPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',

            SwitchBuyBoxVariantEvent::class => 'addExpressCheckoutDataToBuyBoxSwitch',

            'framework.validation.address.create' => 'disableAddressValidation',
            'framework.validation.customer.create' => 'disableCustomerValidation',

            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',

            CustomerEvents::MAPPING_REGISTER_CUSTOMER => 'addPayerIdToCustomer',
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerWritten',
        ];
    }

    /**
     * @param CheckoutCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|NavigationPageLoadedEvent|OffcanvasCartPageLoadedEvent|ProductPageLoadedEvent|SearchPageLoadedEvent $event
     */
    public function addExpressCheckoutDataToPage(PageLoadedEvent $event): void
    {
        $addProductToCart = $event instanceof ProductPageLoadedEvent
            || $event instanceof NavigationPageLoadedEvent
            || $event instanceof SearchPageLoadedEvent;

        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData(
            $event,
            $addProductToCart
        );

        if ($expressCheckoutButtonData === null) {
            return;
        }

        if ($event instanceof ProductPageLoadedEvent
            && $this->excludedProductValidator->isProductExcluded($event->getPage()->getProduct(), $event->getSalesChannelContext())) {
            return;
        }

        if (!$addProductToCart
            && $this->excludedProductValidator->cartContainsExcludedProduct($event->getPage()->getCart(), $event->getSalesChannelContext())) {
            return;
        }

        $event->getPage()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
        $this->logger->debug('Added data to page {page}', ['page' => $event::class]);
    }

    public function addExpressCheckoutDataToPagelet(PageletLoadedEvent $event): void
    {
        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData($event, true);

        if ($expressCheckoutButtonData === null) {
            return;
        }

        if ($event instanceof QuickviewPageletLoadedEvent
            && $this->excludedProductValidator->isProductExcluded($event->getPagelet()->getProduct(), $event->getSalesChannelContext())) {
            return;
        }

        $event->getPagelet()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    public function addExpressCheckoutDataToBuyBoxSwitch(SwitchBuyBoxVariantEvent $event): void
    {
        $expressCheckoutButtonData = $this->getExpressCheckoutButtonData($event);

        if ($expressCheckoutButtonData === null) {
            return;
        }

        $event->getProduct()->addExtension(
            self::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID,
            $expressCheckoutButtonData
        );
    }

    /**
     * @param SalesChannelEntitySearchResultLoadedEvent<ProductCollection> $event
     */
    public function addExcludedProductsToSearchResult(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        if (!$this->checkSettings($event->getSalesChannelContext(), $event::class)) {
            return;
        }

        $productIds = [];
        $products = $event->getResult()->getEntities();
        foreach ($products as $product) {
            $productIds[] = $product->getId();
            $productIds[] = $product->getParentId();
        }

        $excluded = $this->excludedProductValidator->findExcludedProducts(\array_filter($productIds), $event->getSalesChannelContext());

        foreach ($products as $product) {
            if (\in_array($product->getId(), $excluded, true) || ($product->getParentId() && \in_array($product->getParentId(), $excluded, true))) {
                $product->addExtension(ExcludedProductValidator::PRODUCT_EXCLUDED_FOR_PAYPAL, new ArrayStruct());
            }
        }
    }

    public function disableAddressValidation(BuildValidationEvent $event): void
    {
        if (!$event->getContext()->hasExtension(ExpressCustomerService::EXPRESS_CHECKOUT_ACTIVE)) {
            return;
        }

        $event->getDefinition()->set('additionalAddressLine1')
            ->set('additionalAddressLine2')
            ->set('phoneNumber');
    }

    public function disableCustomerValidation(BuildValidationEvent $event): void
    {
        if (!$event->getContext()->hasExtension(ExpressCustomerService::EXPRESS_CHECKOUT_ACTIVE)) {
            return;
        }

        $event->getDefinition()->set('birthdayDay')
            ->set('birthdayMonth')
            ->set('birthdayYear');
    }

    public function addPayerIdToCustomer(DataMappingEvent $event): void
    {
        if (!$event->getContext()->hasExtension(ExpressCustomerService::EXPRESS_CHECKOUT_ACTIVE)) {
            return;
        }

        $input = $event->getInput();
        $output = $event->getOutput();
        $output['customFields'][ExpressCustomerService::EXPRESS_PAYER_ID] = $input->get(ExpressCustomerService::EXPRESS_PAYER_ID);
        $event->setOutput($output);
    }

    /**
     * If double opt-in is enabled, we need to patch the customer to disable it again.
     * Otherwise, we will not get a logged-in customer and an email will be sent, requiring confirmation.
     * We don't need this confirmation as PayPal is our trusted source.
     */
    public function onCustomerWritten(EntityWrittenEvent $event): void
    {
        if (!$event->getContext()->hasExtension(ExpressCustomerService::EXPRESS_CHECKOUT_ACTIVE)) {
            return;
        }

        // as we're in express checkout, there will be only one write result
        foreach ($event->getWriteResults() as $result) {
            $id = $result->getProperty('id');
            $salesChannelId = $result->getProperty('salesChannelId');
            $expressId = $result->getProperty('customFields')[ExpressCustomerService::EXPRESS_PAYER_ID] ?? null;
            $guest = (bool) $result->getProperty('guest');

            // double-checking if we should patch
            if (!$id || !$salesChannelId || !$expressId || !$guest) {
                continue;
            }

            if ($this->systemConfigService->getBool('core.loginRegistration.doubleOptInGuestOrder', $salesChannelId)) {
                // prevent looping on with another written event after patching
                $event->getContext()->removeExtension(ExpressCustomerService::EXPRESS_CHECKOUT_ACTIVE);

                $this->customerRepository->update([[
                    'id' => $id,
                    'doubleOptInRegistration' => false,
                    'doubleOptInEmailSentDate' => null,
                ]], $event->getContext());

                $event->getContext()->addExtension(ExpressCustomerService::EXPRESS_CHECKOUT_ACTIVE, new ArrayStruct());
            }
        }
    }

    public function onCheckoutConfirmLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        if ($event->getRequest()->query->has(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID) === false) {
            return;
        }

        $confirmPage = $event->getPage();
        $payPalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId($event->getContext());
        if ($payPalPaymentMethodId === null) {
            return;
        }

        $paymentMethods = $confirmPage->getPaymentMethods();
        if ($paymentMethods->has($payPalPaymentMethodId) === false) {
            return;
        }

        $filtered = $paymentMethods->filterByProperty('id', $payPalPaymentMethodId);
        $confirmPage->setPaymentMethods($filtered);
        $this->logger->debug('Removed other payment methods from selection for Express Checkout');
    }

    private function getExpressCheckoutButtonData(
        ShopwareSalesChannelEvent $event,
        bool $addProductToCart = false,
    ): ?ExpressCheckoutButtonData {
        if (!$this->checkSettings($event->getSalesChannelContext(), $event::class)) {
            return null;
        }

        return $this->expressCheckoutDataService->buildExpressCheckoutButtonData(
            $event->getSalesChannelContext(),
            $addProductToCart,
            $event
        );
    }

    private function checkSettings(SalesChannelContext $context, string $eventName): bool
    {
        if ($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($context) === false) {
            return false;
        }

        try {
            $this->settingsValidationService->validate($context->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            return false;
        }

        if ($this->expressOptionForEventEnabled($context->getSalesChannelId(), $eventName) === false) {
            return false;
        }

        return true;
    }

    private function expressOptionForEventEnabled(string $salesChannelId, string $eventName): bool
    {
        switch ($eventName) {
            case ProductPageLoadedEvent::class:
            case QuickviewPageletLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_DETAIL_ENABLED, $salesChannelId);
            case OffcanvasCartPageLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_OFF_CANVAS_ENABLED, $salesChannelId);
            case CheckoutRegisterPageLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_LOGIN_ENABLED, $salesChannelId);
            case CheckoutCartPageLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_CART_ENABLED, $salesChannelId);
            case NavigationPageLoadedEvent::class:
            case CmsPageLoadedEvent::class:
            case SearchPageLoadedEvent::class:
            case GuestWishlistPageletLoadedEvent::class:
            case SwitchBuyBoxVariantEvent::class:
            case SalesChannelEntitySearchResultLoadedEvent::class:
                return $this->systemConfigService->getBool(Settings::ECS_LISTING_ENABLED, $salesChannelId);
            default:
                return false;
        }
    }
}
