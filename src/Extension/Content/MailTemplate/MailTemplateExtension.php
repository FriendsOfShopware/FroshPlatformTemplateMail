<?php

namespace Frosh\TemplateMail\Extension\Content\MailTemplate;


use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MailTemplateExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new StringField('frosh_template_mail', 'froshTemplateMail'))->addFlags(new Runtime())
        );
    }

    public function getDefinitionClass(): string
    {
        return MailTemplateDefinition::class;
    }
}
