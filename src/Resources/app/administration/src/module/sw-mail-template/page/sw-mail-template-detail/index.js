import template from './sw-mail-template-detail.html.twig';
import './sw-mail-template-detail.scss';

Shopware.Component.override('sw-mail-template-detail', {
    template,
    methods: {
        getFroshTooltip(field, folder) {
            const message = folder
                ? this.$t(`sw-mail-template.frosh.${field}`, { folder: folder })
                : this.$t('sw-mail-template.frosh.noTemplate');

            return { message: message };
        },
    },
});
