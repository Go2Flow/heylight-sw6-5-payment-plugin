const {Component, Mixin} = Shopware;

import template from './heylight-settings.html.twig';
import './style.scss';

Component.register('heylight-settings', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: [ 'HeyLightSettingsService' ],

    data() {
        return {
            isLoading: false,
            isTesting: false,
            isSaveSuccessful: false,
            isTestSuccessful: false,
            secretKey: false,
            isSupportModalOpen: false,
            validations: {
                'secretKey': {required: true},
                'promotionPublicApiKey': {required: true},
                'romotionWidgetFee': {required: true},
                'promotionProductMode': {required: true},
                'promotionTerms': {required: true},
                'promotionTermsCredit': {required: true},
            },
        }
    },
    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },
        onSave() {
            this.isLoading = true;
            let errors = this.validateConfig();
            if (errors.length) {
                this.createNotificationsForValidation(errors);
                this.isLoading = false;
                return;
            }

            this.isSaveSuccessful = false;
            this.$refs.systemConfig.saveAll().then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
            }).catch(() => {
                this.isLoading = false;
            });
        },

        onTest() {
            this.isTesting = true;
            this.isTestSuccessful = false;

            let credentials = this.getConfigValue('secretKey');

            this.HeyLightSettingsService.validateApiCredentials(credentials).then((response) => {
                const credentialsValid = response.credentialsValid;
                const error = response.error;

                if (credentialsValid) {
                    this.createNotificationSuccess({
                        title: this.$tc('HeyLight.settingsForm.messages.titleSuccess'),
                        message: this.$tc('HeyLight.settingsForm.messages.messageTestSuccess')
                    });
                    this.isTestSuccessful = true;
                } else {
                    this.createNotificationError({
                        title: this.$tc('HeyLight.settingsForm.messages.titleError'),
                        message: this.$tc('HeyLight.settingsForm.messages.messageTestError')
                    });
                }
                this.isTesting = false;
            }).catch((errorResponse) => {
                this.createNotificationError({
                    title: this.$tc('HeyLight.settingsForm.messages.titleError'),
                    message: this.$tc('HeyLight.settingsForm.messages.messageTestErrorGeneral')
                });
                this.isTesting = false;
            });
        },
        createNotificationsForValidation(errors) {
            errors.forEach(error => {
                let message = '';
                let fieldName = this.$tc('HeyLight.settingsForm.fields.'+error.field);
                error.errors.forEach(errorCode => {
                    message = message + ' ' + this.$tc('HeyLight.settingsForm.messages.'+errorCode, {field: fieldName})
                });
                this.createNotificationError({
                    title: this.$tc('HeyLight.settingsForm.messages.titleError'),
                    message: message
                });
            })
        },
        validateConfig() {
            let errors = [];
            for (const [salesChannelId, config] of Object.entries(this.$refs.systemConfig.actualConfigData)) {
                for (const [field, validations] of Object.entries(this.validations)) {
                    let fieldErrors = [];
                    let configValue = config[`Go2FlowHeyLightPayment.settings.${field}`];
                    if (validations.required) {
                        if (!this.isSet(configValue, salesChannelId !== 'null')) {
                            fieldErrors.push('required')
                        }
                    }
                    if (fieldErrors.length) {
                        errors.push({
                            sci: salesChannelId,
                            field: field,
                            errors: fieldErrors
                        })
                    }
                }
            }
            return errors;
        },
        isSet(value, allowNull) {
            return (
                value !== ''
                && (value !== null || allowNull)
                && (!Array.isArray(value) || value.length)
            );
        },
        getConfigValue(field) {
            let config = {};
            const defaultConfig = this.$refs.systemConfig.actualConfigData.null;
            const salesChannelId = this.$refs.systemConfig.currentSalesChannelId;

            if (salesChannelId === null) {
                config = defaultConfig;
            } else {
                config = this.$refs.systemConfig.actualConfigData[salesChannelId];
            }
            return config[`Go2FlowHeyLightPayment.settings.${field}`]
                || defaultConfig[`Go2FlowHeyLightPayment.settings.${field}`];
        },
    }
});
