<?php

declare(strict_types=1);

namespace Sylius\RefundPlugin\Generator;

use Doctrine\Common\Collections\Collection;
use Sylius\RefundPlugin\Entity\LineItemInterface;
use Sylius\RefundPlugin\Entity\TaxItem;
use Sylius\RefundPlugin\Entity\TaxItemInterface;

final class TaxItemsGenerator implements TaxItemsGeneratorInterface
{
    public function generate(Collection $lineItems): array
    {
        $temporaryTaxItems = [];

        /** @var LineItemInterface $item */
        foreach ($lineItems as $item) {
            $taxRate = $item->taxRate();

            if ($taxRate === null) {
                continue;
            }

            if (isset($temporaryTaxItems[$taxRate])) {
                $temporaryTaxItems[$taxRate] += $item->taxAmount();

                continue;
            }

            $temporaryTaxItems[$taxRate] = $item->taxAmount();
        }

        return $this->prepareTaxItemsArray($temporaryTaxItems);
    }

    /** @return array<TaxItemInterface> */
    private function prepareTaxItemsArray(array $temporaryTaxItems): array
    {
        $taxItems = [];
        foreach ($temporaryTaxItems as $label => $amount) {
            $taxItems[] = new TaxItem($label, $amount);
        }

        return $taxItems;
    }
}
