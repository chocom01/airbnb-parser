<?php

namespace App\Scraper\Airbnb;

use App\Parser\ParseDaysFromHtml;
use App\Scraper\WebDriver;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Calendar extends WebDriver
{
    use ParseDaysFromHtml;

    /**
     * @param int $listingId
     * @param int $fromMonth
     * @param int $fromYear
     * @param int $countMonths
     * @return Collection
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws UnsupportedOperationException
     */
    public function getMonths(int $listingId, int $fromMonth, int $fromYear, int $countMonths): Collection
    {
        $this->browser->get("https://www.airbnb.com/rooms/{$listingId}");
        $this->waitForCalendarDayElement();

        // for debug
        // $this->scrollToComponent('//*[@id="site-content"]/div/div[1]/div[3]/div/div[1]/div/div[7]/div/div[2]');
        // $this->browser->takeScreenshot(now()->timestamp . '.png');

        $monthsHtml = $this->getMonthsHtml($fromMonth, $fromYear, $countMonths);
        return $this->parseCalendar(implode( $monthsHtml));
    }

    /**
     * @param int|null $fromMonth
     * @param int|null $fromYear
     * @param int|null $countMonths
     * @return array
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws UnsupportedOperationException
     */
    private function getMonthsHtml(?int $fromMonth, ?int $fromYear, ?int $countMonths): array
    {
        // Airbnb month scraping like 'July 2022' and that's why send query parameter(getMonths method) as int better
        $from = Carbon::createFromFormat('!m Y', "{$fromMonth} {$fromYear}")->format('F Y');
        $to = Carbon::createFromFormat('!m Y', "{$fromMonth} {$fromYear}")->addMonths($countMonths)->format('F Y');
        $arr = [];

        $currentMonthOnCalendar = $this->getLeftSideMonthName();

        // Clicking on the next month button while current month(on the left of the calendar) will become requested $fromMonth
        while ($from !== $currentMonthOnCalendar) {
            $this->nextMonth();
            $this->checkThatNextMonthNameLoaded($currentMonthOnCalendar);

            $currentMonthOnCalendar = $this->getLeftSideMonthName();
        }

        // Scrap Calendar html element and
        // Clicking on the next month button while current month will become requested $toMonth
        // if only one count month requested, then only do{} element starts
        do {
            $arr[] = $this->getCalendarHtml();

            $this->nextMonth();
            $this->checkThatNextMonthNameLoaded($currentMonthOnCalendar);

            $currentMonthOnCalendar = $this->getLeftSideMonthName();
        } while ($currentMonthOnCalendar !== $to);

        return $arr;
    }

    /**
     *
     * @param string $currentMonthOnCalendar
     * @return void
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    private function checkThatNextMonthNameLoaded(string $currentMonthOnCalendar): void
    {
        $checkMonth = Carbon::createFromFormat('F Y', $currentMonthOnCalendar)->addMonth()->format('F Y');

        $this->browser->wait(3, 50)
            ->until(fn() => $checkMonth === $this->getLeftSideMonthName(), 'Month name not found');
    }

    /**
     * @return string
     * @throws UnsupportedOperationException
     */
    private function getCalendarHtml(): string
    {
        return $this->entireHtmlOfElement('//div[@data-section-id="AVAILABILITY_CALENDAR_INLINE"]//div//div[2]//div//div[1]//div//div//div//div//div[2]//div[2]//div[1]//div[2]/*');
    }

    /**
     * @return void
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    private function waitForCalendarDayElement(): void
    {
        parent::waitForElement('//div[@data-section-id="AVAILABILITY_CALENDAR_INLINE"]//div//div[2]//div//div[1]//div//div//div//div//div[2]//div[2]//div[1]//div[2]//table//tbody//tr[5]//td[7]');
    }

    /**
     * @return string
     */
    private function getLeftSideMonthName(): string
    {
        return parent::getTextOfElement('//div[@data-section-id="AVAILABILITY_CALENDAR_INLINE"]//div//div[2]//div//div[1]//div//div//div//div//div[2]//div[2]//div//div[2]//div[1]//div//div//h3');
    }

    /**
     * @return void
     */
    private function nextMonth(): void
    {
        parent::clickOnElement('//div[@data-section-id="AVAILABILITY_CALENDAR_INLINE"]//div//div[2]//div//div[1]//div//div//div//div//div[2]//div[1]//div[2]//button');
    }

    public function putToFile($listingId, $fromMonth, $fromYear, $countMonths): void
    {
        $this->browser->get("https://www.airbnb.com/rooms/{$listingId}");
        $this->waitForCalendarDayElement();

        $monthsHtml = $this->getMonthsHtml($fromMonth, $fromYear, $countMonths);
        Storage::put('html.txt', implode(PHP_EOL, $monthsHtml));
    }

    public function debugParser(): Collection
    {
        $monthsHtml = html_entity_decode(Storage::get('html.txt'));
        $parsedMonths = $this->parseCalendar(html_entity_decode(implode( $monthsHtml)));

        return $parsedMonths;
    }
}
