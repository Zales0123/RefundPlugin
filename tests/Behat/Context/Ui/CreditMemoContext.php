<?php

declare(strict_types=1);

namespace Tests\Sylius\RefundPlugin\Behat\Context\Ui;

use Behat\Behat\Context\Context;
use Doctrine\Common\Persistence\ObjectRepository;
use Sylius\Behat\Page\Admin\Order\ShowPageInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\RefundPlugin\Provider\CurrentDateTimeProviderInterface;
use Tests\Sylius\RefundPlugin\Behat\Page\Admin\CreditMemoDetailsPageInterface;
use Tests\Sylius\RefundPlugin\Behat\Page\Admin\CreditMemoIndexPageInterface;
use Tests\Sylius\RefundPlugin\Behat\Element\PdfDownloadElementInterface;
use Webmozart\Assert\Assert;

final class CreditMemoContext implements Context
{
    /** @var ShowPageInterface */
    private $orderShowPage;

    /** @var CreditMemoIndexPageInterface */
    private $creditMemoIndexPage;

    /** @var CreditMemoDetailsPageInterface */
    private $creditMemoDetailsPage;

    /** @var PdfDownloadElementInterface */
    private $pdfDownloadElement;

    /** @var ObjectRepository */
    private $creditMemoRepository;

    /** @var CurrentDateTimeProviderInterface */
    private $currentDateTimeProvider;

    public function __construct(
        ShowPageInterface $orderShowPage,
        CreditMemoIndexPageInterface $creditMemoIndexPage,
        CreditMemoDetailsPageInterface $creditMemoDetailsPage,
        PdfDownloadElementInterface $pdfDownloadElement,
        ObjectRepository $creditMemoRepository,
        CurrentDateTimeProviderInterface $currentDateTimeProvider
    ) {
        $this->orderShowPage = $orderShowPage;
        $this->creditMemoIndexPage = $creditMemoIndexPage;
        $this->creditMemoDetailsPage = $creditMemoDetailsPage;
        $this->pdfDownloadElement = $pdfDownloadElement;
        $this->creditMemoRepository = $creditMemoRepository;
        $this->currentDateTimeProvider = $currentDateTimeProvider;
    }

    /**
     * @When I browse the details of the only credit memo generated for order :order
     */
    public function browseTheDetailsOfTheOnlyCreditMemoGeneratedForOrder(OrderInterface $order): void
    {
        $creditMemo = $this->creditMemoRepository->findBy(['order' => $order])[0];

        $this->creditMemoDetailsPage->open(['orderNumber' => $order->getNumber(), 'id' => $creditMemo->getId()]);
    }

    /**
     * @When I browse credit memos
     */
    public function browseCreditMemos(): void
    {
        $this->creditMemoIndexPage->open();
    }

    /**
     * @When /^I download (\d+)(?:|st|nd|rd) credit memo$/
     */
    public function downloadCreditMemoFromIndex(int $index): void
    {
        $this->creditMemoIndexPage->downloadCreditMemo($index);
    }

    /**
     * @When I filter credit memos by :channelName channel
     */
    public function filterCreditMemosByChannel(string $channelName): void
    {
        $this->creditMemoIndexPage->filterByChannel($channelName);
        $this->creditMemoIndexPage->filter();
    }

    /**
     * @When /^I download (\d+)(?:|st|nd|rd) order's credit memo$/
     */
    public function downloadCreditMemoFromOrderShow(int $index): void
    {
        $this->orderShowPage->downloadCreditMemo($index);
    }

    /**
     * @When I download it
     */
    public function downloadCreditMemo(): void
    {
        $this->creditMemoDetailsPage->download();
    }

    /**
     * @When I resend credit memo from order :orderNumber
     */
    public function resendCreditMemoToCustomer(string $orderNumber): void
    {
        $this->creditMemoIndexPage->resendCreditMemo($orderNumber);
    }

    /**
     * @Then I should have :count credit memo generated for order :order
     */
    public function shouldHaveCountCreditMemoGeneratedForOrder(int $count, OrderInterface $order): void
    {
        $this->orderShowPage->open(['id' => $order->getId()]);
        Assert::same($this->orderShowPage->countCreditMemos(), $count);
    }

    /**
     * @Then this credit memo should contain :count :productName product with :tax tax applied
     */
    public function thisCreditMemoShouldContainProductWithTaxApplied(
        int $count,
        string $productName,
        string $tax
    ): void {
        Assert::same($this->creditMemoDetailsPage->countUnitsWithProduct($productName), $count);
        Assert::same($this->creditMemoDetailsPage->getUnitTax($count, $productName), $tax);
    }

    /**
     * @Then this credit memo should contain :count :shipmentName shipment with :total total
     */
    public function thisCreditMemoShouldContainShipmentWithTotal(
        int $count,
        string $shipmentName,
        string $total
    ): void {
        Assert::same($this->creditMemoDetailsPage->countUnitsWithProduct($shipmentName), $count);
        Assert::same($this->creditMemoDetailsPage->getUnitTotal($count, $shipmentName), $total);
    }

    /**
     * @Then it should have sequential number generated from current date
     */
    public function shouldHaveSequentialNumberGeneratedFromCurrentDate(): void
    {
        Assert::same(
            $this->creditMemoDetailsPage->getNumber(),
            $this->currentDateTimeProvider->now()->format('Y/m').'/'.'000000001'
        );
    }

    /**
     * @Then it should be issued in :channelName channel
     */
    public function creditMemoShouldBeIssuedInChannel(string $channelName): void
    {
        Assert::same($this->creditMemoDetailsPage->getChannelName(), $channelName);
    }

    /**
     * @Then it should be issued from :customerName, :street, :postcode :city in the :country
     */
    public function itShouldBeIssuedFrom(
        string $customerName,
        string $street,
        string $postcode,
        string $city,
        CountryInterface $country
    ): void {
        Assert::same(
            $this->creditMemoDetailsPage->getFromAddress(),
            $customerName . ' ' . $street . ' ' . $city . ' ' . strtoupper($country->getName()) . ' ' . $postcode
        );
    }

    /**
     * @Then it should be issued to :company, :street, :postcode :city in the :country with :taxId tax ID
     */
    public function itShouldBeIssuedTo(
        string $company,
        string $street,
        string $postcode,
        string $city,
        CountryInterface $country,
        string $taxId
    ): void {
        Assert::same(
            $this->creditMemoDetailsPage->getToAddress(),
            $company . ' ' . $taxId . ' ' . $city . ' ' . $street . ' ' . strtoupper($country->getName()) . ' ' . $postcode
        );
    }

    /**
     * @Then its total should be :total
     */
    public function creditMemoTotalShouldBe(string $total): void
    {
        Assert::same($this->creditMemoDetailsPage->getTotal(), $total);
    }

    /**
     * @Then it should be commented with :comment
     */
    public function itShouldBeCommentedWith(string $comment): void
    {
        Assert::same($this->creditMemoDetailsPage->getComment(), $comment);
    }

    /**
     * @Then there should be :count credit memo(s) generated
     */
    public function thereShouldBeCreditMemosGenerated(int $count): void
    {
        Assert::same($this->creditMemoIndexPage->countItems(), $count);
    }

    /**
     * @Then /^(\d+)(?:st|nd|rd) credit memo should be generated for the (order "[^"]+"), have total "([^"]+)" and date of being issued$/
     */
    public function creditMemoShouldBeGeneratedForOrderHasTotalAndDateOfBeingIssued(
        int $index,
        OrderInterface $order,
        string $total
    ): void {
        $orderNumber = $order->getNumber();

        Assert::true(
            $this->creditMemoIndexPage->hasCreditMemoWithOrderNumber($index, $orderNumber),
            sprintf('Order number for %d credit memo should be %s', $index, $orderNumber)
        );

        Assert::true(
            $this->creditMemoIndexPage->hasCreditMemoWithTotal($index, $total),
            sprintf('Total for %d credit memo should be %s', $index, $total)
        );

        $creditMemos = $this->creditMemoRepository->findBy(['order' => $order], ['number' => 'ASC']);
        $issuedAt = $creditMemos[$index - 1]->getIssuedAt();

        Assert::true($this->creditMemoIndexPage->hasCreditMemoWithDateOfBeingIssued($index, $issuedAt),
            sprintf('Date of being issued for %d credit memo should be %s', $index, $issuedAt->format('Y-m-d H:i:s'))
        );
    }

    /**
     * @Then /^the only credit memo should be generated for order "#([^"]+)"$/
     */
    public function theOnlyCreditMemoShouldBeGeneratedForOrder(string $orderNumber): void
    {
        Assert::true($this->creditMemoIndexPage->hasSingleCreditMemoForOrder($orderNumber));
    }

    /**
     * @Then /^(\d+)(?:st|nd|rd) credit memo should be issued in "([^"]+)" channel$/
     */
    public function specificCreditMemoShouldBeIssuedInChannel(int $index, string $channelName): void
    {
        Assert::true($this->creditMemoIndexPage->hasCreditMemoWithChannel($index, $channelName));
    }

    /**
     * @Then a pdf file should be successfully downloaded
     */
    public function pdfFileShouldBeSuccessfullyDownloaded(): void
    {
        Assert::true($this->pdfDownloadElement->isPdfFileDownloaded());
    }
}
