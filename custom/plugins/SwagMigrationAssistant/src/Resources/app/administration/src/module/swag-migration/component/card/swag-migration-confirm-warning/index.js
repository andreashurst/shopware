import template from './swag-migration-confirm-warning.html.twig';
import './swag-migration-confirm-warning.scss';

const { Component, Store } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

/**
 * @private
 * @sw-package fundamentals@after-sales
 */
Component.register('swag-migration-confirm-warning', {
    template,

    data() {
        return {
            isCurrencyChecked: false,
            isLanguageChecked: false,
        };
    },

    computed: {
        ...mapState(() => Store.get('swagMigration'), [
            'environmentInformation',
        ]),

        hasDifferentCurrency() {
            return this.sourceSystemCurrency !== this.targetSystemCurrency;
        },

        sourceSystemCurrency() {
            return this.environmentInformation.sourceSystemCurrency;
        },

        targetSystemCurrency() {
            return this.environmentInformation.targetSystemCurrency;
        },

        hasDifferentLanguage() {
            return this.sourceSystemLanguage !== this.targetSystemLanguage;
        },

        sourceSystemLanguage() {
            return this.environmentInformation.sourceSystemLocale;
        },

        targetSystemLanguage() {
            return this.environmentInformation.targetSystemLocale;
        },

        isContinuable() {
            return (!this.hasDifferentCurrency || this.isCurrencyChecked) &&
                (!this.hasDifferentLanguage || this.isLanguageChecked);
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.onCheckboxValueChanged();
        },

        onCheckboxValueChanged() {
            Store.get('swagMigration').setWarningConfirmed(this.isContinuable);
        },
    },
});
