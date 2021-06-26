<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Shopware\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Content\MailTemplate\Service\Event\MailSentEvent;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\CountryAddToSalesChannelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainDefinition;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OrderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;
    use CountryAddToSalesChannelTestBehaviour;
    use SetupExampleTemplatesTrait;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = $this->getContainer()->get(OrderService::class);

        $this->orderRepository = $this->getContainer()->get('order.repository');

        $this->addCountriesToSalesChannel();

        $contextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $this->salesChannelContext = $contextFactory->create(
            '',
            Defaults::SALES_CHANNEL,
            [SalesChannelContextService::CUSTOMER_ID => $this->createCustomer('Jon', 'Doe')]
        );
    }

    public function testOrderConfirmationMail(): void
    {
        $eventDispatcher = $this->getContainer()->get('event_dispatcher');
        $mailSentEvent = null;

        $eventDispatcher->addListener(MailSentEvent::class, function (MailSentEvent $e) use(&$mailSentEvent) {
            $mailSentEvent = $e;
        });

        $this->performOrder();

        static::assertNotNull($mailSentEvent, 'Mail did not sent');

        static::assertSame(
            [
                'text/html' => 'HTML CONFIRM',
                'text/plain' => "TEXT CONFIRM"
            ],
            $mailSentEvent->getContents()
        );
        static::assertSame('SUBJECT CONFIRM', $mailSentEvent->getSubject());
    }

    private function fillCart(string $contextToken): void
    {
        $cart = $this->getContainer()->get(CartService::class)->createNew($contextToken);

        $productId = $this->createProduct();
        $cart->add(new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId));
        $cart->setTransactions($this->createTransaction());
    }

    private function createProduct(): string
    {
        $productId = Uuid::randomHex();

        $product = [
            'id' => $productId,
            'name' => 'Test product',
            'productNumber' => '123456789',
            'stock' => 1,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 19.99, 'net' => 10, 'linked' => false],
            ],
            'manufacturer' => ['id' => $productId, 'name' => 'shopware AG'],
            'tax' => ['id' => $this->getValidTaxId(), 'name' => 'testTaxRate', 'taxRate' => 15],
            'categories' => [
                ['id' => $productId, 'name' => 'Test category'],
            ],
            'visibilities' => [
                [
                    'id' => $productId,
                    'salesChannelId' => Defaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $this->getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());

        return $productId;
    }

    private function createTransaction(): TransactionCollection
    {
        return new TransactionCollection([
            new Transaction(
                new CalculatedPrice(
                    13.37,
                    13.37,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                ),
                $this->getValidPaymentMethodId()
            ),
        ]);
    }

    private function performOrder(): string
    {
        $data = new RequestDataBag(['tos' => true]);
        $this->fillCart($this->salesChannelContext->getToken());

        return $this->orderService->createOrder($data, $this->salesChannelContext);
    }

    private function createCustomer(string $firstName, string $lastName, array $options = []): string
    {
        $customerId = Uuid::randomHex();
        $salutationId = $this->getValidSalutationId();
        $paymentMethodId = $this->getValidPaymentMethodId();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => Defaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $customerId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'city' => 'Schöppingen',
                'street' => 'Ebbinghoff 10',
                'zipcode' => '48624',
                'salutationId' => $salutationId,
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $customerId,
            'defaultPaymentMethodId' => $paymentMethodId,
            'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'not',
            'firstName' => $firstName,
            'lastName' => $lastName,
            'salutationId' => $salutationId,
            'customerNumber' => '12345',
        ];

        $customer = array_merge_recursive($customer, $options);

        $this->getContainer()->get('customer.repository')->create([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
