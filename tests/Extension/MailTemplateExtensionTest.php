<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Extension;

use Frosh\TemplateMail\Extension\Content\MailTemplate\MailTemplateExtension;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MailTemplateExtensionTest extends TestCase
{
    public function testExtendFieldsAddsStringField(): void
    {
        $collection = $this->getMockBuilder(FieldCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['add'])
            ->getMock();

        $collection
            ->expects(static::once())
            ->method('add')
            ->with((new StringField('frosh_template_mail', 'froshTemplateMail'))->addFlags(new Runtime()));

        (new MailTemplateExtension())->extendFields($collection);
    }

    public function testGetDefinitionClassReturnsMailTemplateDefinitionClass(): void
    {
        static::assertSame(
            MailTemplateDefinition::class,
            (new MailTemplateExtension())->getDefinitionClass()
        );
    }
}
