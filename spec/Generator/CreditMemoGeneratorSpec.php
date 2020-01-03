<?php

declare(strict_types=1);

namespace spec\Sylius\RefundPlugin\Generator;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopBillingDataInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\RefundPlugin\Entity\CreditMemo;
use Sylius\RefundPlugin\Entity\CreditMemoUnit;
use Sylius\RefundPlugin\Entity\CustomerBillingData;
use Sylius\RefundPlugin\Entity\ShopBillingData;
use Sylius\RefundPlugin\Exception\OrderNotFound;
use Sylius\RefundPlugin\Generator\CreditMemoGeneratorInterface;
use Sylius\RefundPlugin\Generator\CreditMemoIdentifierGeneratorInterface;
use Sylius\RefundPlugin\Generator\CreditMemoUnitGeneratorInterface;
use Sylius\RefundPlugin\Generator\NumberGenerator;
use Sylius\RefundPlugin\Model\OrderItemUnitRefund;
use Sylius\RefundPlugin\Model\ShipmentRefund;
use Sylius\RefundPlugin\Provider\CurrentDateTimeProviderInterface;

final class CreditMemoGeneratorSpec extends ObjectBehavior
{
    function let(
        OrderRepositoryInterface $orderRepository,
        CreditMemoUnitGeneratorInterface $orderItemUnitCreditMemoUnitGenerator,
        CreditMemoUnitGeneratorInterface $shipmentCreditMemoUnitGenerator,
        NumberGenerator $creditMemoNumberGenerator,
        CurrentDateTimeProviderInterface $currentDateTimeProvider,
        CreditMemoIdentifierGeneratorInterface $creditMemoIdentifierGenerator
    ): void {
        $this->beConstructedWith(
            $orderRepository,
            $orderItemUnitCreditMemoUnitGenerator,
            $shipmentCreditMemoUnitGenerator,
            $creditMemoNumberGenerator,
            $currentDateTimeProvider,
            $creditMemoIdentifierGenerator
        );
    }

    function it_implements_credit_memo_generator_interface(): void
    {
        $this->shouldImplement(CreditMemoGeneratorInterface::class);
    }

    function it_generates_credit_memo_basing_on_event_data(
        NumberGenerator $creditMemoNumberGenerator,
        OrderInterface $order,
        ChannelInterface $channel,
        ShopBillingDataInterface $shopBillingData,
        AddressInterface $customerBillingAddress,
        CreditMemoUnitGeneratorInterface $orderItemUnitCreditMemoUnitGenerator,
        CreditMemoUnitGeneratorInterface $shipmentCreditMemoUnitGenerator,
        CurrentDateTimeProviderInterface $currentDateTimeProvider,
        CreditMemoIdentifierGeneratorInterface $creditMemoIdentifierGenerator,
        \DateTime $dateTime
    ): void {
        $firstUnitRefund = new OrderItemUnitRefund(1, 500);
        $secondUnitRefund = new OrderItemUnitRefund(3, 500);
        $shipmentRefund = new ShipmentRefund(3, 400);

        $order->getCurrencyCode()->willReturn('GBP');
        $order->getLocaleCode()->willReturn('en_US');

        $order->getChannel()->willReturn($channel);
        $channel->getCode()->willReturn('WEB-US');
        $channel->getName()->willReturn('United States');
        $channel->getColor()->willReturn('Linen');

        $channel->getShopBillingData()->willReturn($shopBillingData);
        $shopBillingData->getCompany()->willReturn('Needful Things');
        $shopBillingData->getTaxId()->willReturn('000222');
        $shopBillingData->getCountryCode()->willReturn('US');
        $shopBillingData->getStreet()->willReturn('Main St. 123');
        $shopBillingData->getCity()->willReturn('New York');
        $shopBillingData->getPostcode()->willReturn('90222');

        $order->getBillingAddress()->willReturn($customerBillingAddress);
        $customerBillingAddress->getFirstName()->willReturn('Rick');
        $customerBillingAddress->getLastName()->willReturn('Sanchez');
        $customerBillingAddress->getPostcode()->willReturn('000333');
        $customerBillingAddress->getCountryCode()->willReturn('US');
        $customerBillingAddress->getStreet()->willReturn('Universe St. 444');
        $customerBillingAddress->getCity()->willReturn('Los Angeles');
        $customerBillingAddress->getCompany()->willReturn('Curse Purge Plus!');
        $customerBillingAddress->getProvinceName()->willReturn(null);
        $customerBillingAddress->getProvinceCode()->willReturn(null);

        $firstCreditMemoUnit = new CreditMemoUnit('Portal gun', 500, 50);
        $orderItemUnitCreditMemoUnitGenerator->generate(1, 500)->willReturn($firstCreditMemoUnit);

        $secondCreditMemoUnit = new CreditMemoUnit('Broken Leg Serum', 500, 50);
        $orderItemUnitCreditMemoUnitGenerator->generate(3, 500)->willReturn($secondCreditMemoUnit);

        $shipmentCreditMemoUnit = new CreditMemoUnit('Galaxy post', 400, 0);
        $shipmentCreditMemoUnitGenerator->generate(3, 400)->willReturn($shipmentCreditMemoUnit);

        $creditMemoNumberGenerator->generate()->willReturn('2018/07/00001111');

        $currentDateTimeProvider->now()->willReturn($dateTime);

        $creditMemoIdentifierGenerator->generate()->willReturn('7903c83a-4c5e-4bcf-81d8-9dc304c6a353');

        $this->generate($order, 1400, [$firstUnitRefund, $secondUnitRefund], [$shipmentRefund], 'Comment')->shouldBeLike(new CreditMemo(
            '7903c83a-4c5e-4bcf-81d8-9dc304c6a353',
            '2018/07/00001111',
            $order->getWrappedObject(),
            1400,
            'GBP',
            'en_US',
            $channel->getWrappedObject(),
            [
                $firstCreditMemoUnit->serialize(),
                $secondCreditMemoUnit->serialize(),
                $shipmentCreditMemoUnit->serialize(),
            ],
            'Comment',
            $dateTime->getWrappedObject(),
            new CustomerBillingData('Rick Sanchez', 'Universe St. 444', '000333', 'US', 'Los Angeles', 'Curse Purge Plus!'),
            new ShopBillingData('Needful Things', '000222', 'US', 'Main St. 123', 'New York', '90222')
        ));
    }
}
