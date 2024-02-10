<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Extension\Content\MailTemplate;

use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('shopware.entity_extension')]
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
