<?php declare(strict_types=1);

namespace NINJA\CartUpselling\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private SalesChannelRepository $salesChannelProductRepository;

    protected SystemConfigService $systemConfigService;

    public function __construct(
        SalesChannelRepository $salesChannelProductRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class => 'onCheckoutCartPageLoaded'
        ];
    }

    public function getRepository(): SalesChannelRepository
    {
        return $this->salesChannelProductRepository;
    }

    public function onCheckoutCartPageLoaded(CheckoutCartPageLoadedEvent $event)
    {
        if ((boolean) $this->systemConfigService->get('NINJACartUpselling.config.active', $event->getSalesChannelContext()->getSalesChannel()->getId()) === false) {
            return;
        }

        $productIds = $this->systemConfigService->get('NINJACartUpselling.config.products', $event->getSalesChannelContext()->getSalesChannel()->getId());

        if (empty($productIds)) {
            return;
        }

        $criteria = new Criteria($productIds);
        $criteria->addFilter(new EqualsFilter('active', true));
        $products = $this->salesChannelProductRepository->search($criteria, $event->getSalesChannelContext())->getEntities();

        $event->getPage()->assign([
            'NINJACartUpsellingProducts' => $products
        ]);
    }
}