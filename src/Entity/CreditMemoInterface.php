<?php

declare(strict_types=1);

namespace Sylius\RefundPlugin\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface CreditMemoInterface extends ResourceInterface
{
    public function getNumber(): string;

    public function getOrder(): OrderInterface;

    public function getTotal(): int;

    public function getCurrencyCode(): string;

    public function getLocaleCode(): string;

    public function getChannel(): ChannelInterface;

    /**
     * @return Collection|LineItemInterface[]
     */
    public function getLineItems(): Collection;

    /**
     * @return array<TaxItemInterface>
     */
    public function getTaxItems(): array;

    public function getComment(): string;

    public function getIssuedAt(): \DateTimeInterface;

    public function getFrom(): CustomerBillingDataInterface;

    public function getTo(): ?ShopBillingDataInterface;
}
