import template from './sw-mail-template-list.html.twig';

Shopware.Component.override('sw-mail-template-list', {
    template,
    methods: {
        getListColumns() {
            const columns = this.$super('getListColumns');

            columns.unshift({
                property: 'froshTemplateMail',
                label: 'sw-mail-template.list.columnTemplateMail',
                allowResize: true,
            }, {
                property: 'technicalName',
                label: 'sw-mail-template.list.columnTechnicalName',
                allowResize: true,
                visible: false,
            });

            return columns;
        },
        getFroshTooltip(field, folder) {
            const message = folder
                ? this.$t(`sw-mail-template.frosh.${field}`, { folder: folder })
                : this.$t('sw-mail-template.frosh.noTemplate');

            return { message: message };
        },
    },
});
