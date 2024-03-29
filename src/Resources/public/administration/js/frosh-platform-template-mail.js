(()=>{var l=`{% block sw_mail_template_list_grid %}
<sw-card
    :title="$tc('sw-mail-template.list.titleMailTemplateList')"
    position-identifier="sw-mail-template-list"
>

    <template>
        {% block sw_mail_template_list_grid_empty_state %}
        <sw-empty-state
            v-if="!isLoading && !showListing"
            :title="$tc('sw-mail-template.list.emptyStateTitle')"
            :subline="$tc('sw-mail-template.list.emptyStateSubTitle')"
            :absolute="false"
        >

            {% block sw_mail_template_list_grid_empty_state_icon %}
            <template #icon>
                <img
                    :src="'/administration/static/img/empty-states/settings-empty-state.svg' | asset"
                    alt=""
                >
            </template>
            {% endblock %}

        </sw-empty-state>
        {% endblock %}
    </template>

    <template #grid>
        <sw-entity-listing
            v-if="isLoading || showListing"
            id="mailTemplateGrid"
            class="sw-mail-templates-list-grid"
            detail-route="sw.mail.template.detail"
            identifier="sw-mail-template-list"
            :items="mailTemplates"
            :columns="getListColumns()"
            :repository="mailTemplateRepository"
            :full-page="false"
            :is-loading="isLoading"
            :allow-view="acl.can('mail_templates.viewer')"
            :allow-edit="acl.can('mail_templates.editor')"
            :allow-delete="acl.can('mail_templates.deleter')"
            :show-selection="acl.can('mail_templates.deleter')"
            :skeleton-item-amount="skeletonItemAmount"
            @update-records="updateRecords"
        >
            {% block sw_mail_template_list_grid_columns_frosh_template_mail %}
            <template #column-froshTemplateMail="{ item }">
                <sw-label
                    appearance="circle"
                    :variant="item.extensions.froshTemplateMail.subject ? 'success' : 'warning'"
                    v-tooltip="getFroshTooltip('subject', item.extensions.froshTemplateMail.subject)"
                >
                    <sw-icon name="small-exclamationmark" small />
                </sw-label>
                <sw-label
                    appearance="circle"
                    :variant="item.extensions.froshTemplateMail.plain ? 'success' : 'warning'"
                    v-tooltip="getFroshTooltip('plain', item.extensions.froshTemplateMail.plain)"
                >
                    <sw-icon name="default-text-editor-align-left" small />
                </sw-label>
                <sw-label
                    appearance="circle"
                    :variant="item.extensions.froshTemplateMail.html ? 'success' : 'warning'"
                    v-tooltip="getFroshTooltip('html', item.extensions.froshTemplateMail.html)"
                >
                    <sw-icon name="default-text-code" small />
                </sw-label>
            </template>
            {% endblock %}

            {% block sw_mail_template_list_grid_columns_technical_name %}
            <template #column-technicalName="{ item }">
                {{ item.extensions.froshTemplateMail.technicalName }}
            </template>
            {% endblock %}

            <template #more-actions="{ item }">
                {% block sw_mail_template_list_grid_columns_actions_duplicate %}
                <sw-context-menu-item
                    class="sw-mail-template-list-grid__duplicate-action"
                    :disabled="!acl.can('mail_templates.creator')"
                    @click="onDuplicate(item.id)"
                >
                    {{ $tc('sw-mail-template.list.contextMenuDuplicate') }}
                </sw-context-menu-item>
                {% endblock %}
            </template>
        </sw-entity-listing>
    </template>
</sw-card>
{% endblock %}
`;Shopware.Component.override("sw-mail-template-list",{template:l,methods:{getListColumns(){let e=this.$super("getListColumns");return e.unshift({property:"froshTemplateMail",label:"sw-mail-template.list.columnTemplateMail",allowResize:!0},{property:"technicalName",label:"sw-mail-template.list.columnTechnicalName",allowResize:!0,visible:!1}),e},getFroshTooltip(e,t){return{message:t?this.$t(`sw-mail-template.frosh.${e}`,{folder:t}):this.$t("sw-mail-template.frosh.noTemplate")}}}});var a=`{% block sw_mail_template_options_form_subject_field %}
    {% parent %}
    <sw-label
        appearance="circle"
        :variant="mailTemplate.extensions.froshTemplateMail.subject ? 'success' : 'warning'"
        v-tooltip="getFroshTooltip('subject', mailTemplate.extensions.froshTemplateMail.subject)"
    >
        <sw-icon name="small-exclamationmark" small />
    </sw-label>
{% endblock %}

{% block sw_mail_template_mail_text_form_content_plain_field %}
    {% parent %}
    <sw-label
        appearance="circle"
        :variant="mailTemplate.extensions.froshTemplateMail.plain ? 'success' : 'warning'"
        v-tooltip="getFroshTooltip('plain', mailTemplate.extensions.froshTemplateMail.plain)"
    >
        <sw-icon name="default-text-editor-align-left" small />
    </sw-label>
{% endblock %}

{% block sw_mail_template_mail_text_form_content_html_field %}
    {% parent %}
    <sw-label
        appearance="circle"
        :variant="mailTemplate.extensions.froshTemplateMail.html ? 'success' : 'warning'"
        v-tooltip="getFroshTooltip('html', mailTemplate.extensions.froshTemplateMail.html)"
    >
        <sw-icon name="default-text-code" small />
    </sw-label>
{% endblock %}
`;Shopware.Component.override("sw-mail-template-detail",{template:a,methods:{getFroshTooltip(e,t){return{message:t?this.$t(`sw-mail-template.frosh.${e}`,{folder:t}):this.$t("sw-mail-template.frosh.noTemplate")}}}});})();
