export const LOCALES = [
    'ar_EG',
    'cs_CZ',
    'da_DK',
    'de_DE',
    'el_GR',
    'en_AU',
    'en_GB',
    'en_IN',
    'en_US',
    'es_ES',
    'es_XC',
    'fi_FI',
    'fr_CA',
    'fr_FR',
    'fr_XC',
    'he_IL',
    'hu_HU',
    'id_ID',
    'it_IT',
    'ja_JP',
    'ko_KR',
    'nl_NL',
    'no_NO',
    'pl_PL',
    'pt_BR',
    'pt_PT',
    'ru_RU',
    'sk_SK',
    'sv_SE',
    'th_TH',
    'zh_CN',
    'zh_HK',
    'zh_TW',
    'zh_XC',
] as const;

export type LOCALE = typeof LOCALES[number];

export const COUNTRY_OVERRIDES = [
    'en-AU',
    'de-DE',
    'es-ES',
    'fr-FR',
    'en-GB',
    'it-IT',
    'en-US',
] as const;

export type COUNTRY_OVERRIDE = typeof COUNTRY_OVERRIDES[number];

export const INTENTS = [
    'CAPTURE',
    'AUTHORIZE',
] as const;

export type INTENT = typeof INTENTS[number];

export const LANDING_PAGES = [
    'LOGIN',
    'GUEST_CHECKOUT',
    'NO_PREFERENCE',
] as const;

export type LANDING_PAGE = typeof LANDING_PAGES[number];

export const BUTTON_COLORS = [
    'blue',
    'black',
    'gold',
    'silver',
    'white',
] as const;

export type BUTTON_COLOR = typeof BUTTON_COLORS[number];

export const BUTTON_SHAPES = [
    'sharp',
    'pill',
    'rect',
] as const;

export type BUTTON_SHAPE = typeof BUTTON_SHAPES[number];

export const SYSTEM_CONFIGS = [
    'SwagPayPal.settings.clientId',
    'SwagPayPal.settings.clientSecret',
    'SwagPayPal.settings.clientIdSandbox',
    'SwagPayPal.settings.clientSecretSandbox',
    'SwagPayPal.settings.merchantPayerId',
    'SwagPayPal.settings.merchantPayerIdSandbox',
    'SwagPayPal.settings.sandbox',

    'SwagPayPal.settings.intent',
    'SwagPayPal.settings.submitCart',
    'SwagPayPal.settings.brandName',
    'SwagPayPal.settings.landingPage',
    'SwagPayPal.settings.sendOrderNumber',
    'SwagPayPal.settings.orderNumberPrefix',
    'SwagPayPal.settings.orderNumberSuffix',
    'SwagPayPal.settings.excludedProductIds',
    'SwagPayPal.settings.excludedProductStreamIds',

    'SwagPayPal.settings.ecsDetailEnabled',
    'SwagPayPal.settings.ecsCartEnabled',
    'SwagPayPal.settings.ecsOffCanvasEnabled',
    'SwagPayPal.settings.ecsLoginEnabled',
    'SwagPayPal.settings.ecsListingEnabled',
    'SwagPayPal.settings.ecsButtonColor',
    'SwagPayPal.settings.ecsButtonShape',
    'SwagPayPal.settings.ecsButtonLanguageIso',
    'SwagPayPal.settings.ecsShowPayLater',

    'SwagPayPal.settings.spbButtonColor',
    'SwagPayPal.settings.spbButtonShape',
    'SwagPayPal.settings.spbButtonLanguageIso',
    'SwagPayPal.settings.spbShowPayLater',
    'SwagPayPal.settings.spbCheckoutEnabled',
    'SwagPayPal.settings.spbAlternativePaymentMethodsEnabled',

    'SwagPayPal.settings.acdcForce3DS',

    'SwagPayPal.settings.puiCustomerServiceInstructions',

    'SwagPayPal.settings.installmentBannerDetailPageEnabled',
    'SwagPayPal.settings.installmentBannerCartEnabled',
    'SwagPayPal.settings.installmentBannerOffCanvasCartEnabled',
    'SwagPayPal.settings.installmentBannerLoginPageEnabled',
    'SwagPayPal.settings.installmentBannerFooterEnabled',

    'SwagPayPal.settings.vaultingEnabled',
    'SwagPayPal.settings.vaultingEnableAlways',
    'SwagPayPal.settings.vaultingEnabledWallet',
    'SwagPayPal.settings.vaultingEnabledACDC',
    'SwagPayPal.settings.vaultingEnabledVenmo',

    'SwagPayPal.settings.crossBorderMessagingEnabled',
    'SwagPayPal.settings.crossBorderBuyerCountry',

    'SwagPayPal.settings.webhookId',
    'SwagPayPal.settings.webhookExecuteToken',
] as const;

export type SYSTEM_CONFIG = typeof SYSTEM_CONFIGS[number];
