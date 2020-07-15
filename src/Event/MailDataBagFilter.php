<?php

namespace Frosh\TemplateMail\Event;

use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\EventDispatcher\Event;

class MailDataBagFilter extends Event
{
    /**
     * @var ParameterBag
     */
    private $dataBag;

    /**
     * @var MailTemplateEntity
     */
    private $mailTemplateEntity;

    /**
     * @var Context
     */
    private $context;

    public function __construct(ParameterBag $dataBag, MailTemplateEntity $mailTemplateEntity, Context $context)
    {
        $this->dataBag = $dataBag;
        $this->mailTemplateEntity = $mailTemplateEntity;
        $this->context = $context;
    }

    public function getDataBag(): ParameterBag
    {
        return $this->dataBag;
    }

    public function getMailTemplateEntity(): MailTemplateEntity
    {
        return $this->mailTemplateEntity;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
