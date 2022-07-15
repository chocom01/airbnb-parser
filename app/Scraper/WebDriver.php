<?php

// You must also have Selenium server started and listening on port :4444/wd/hub.

namespace App\Scraper;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class WebDriver
{
    protected RemoteWebDriver $browser;

    public function __construct()
    {
        $options = (new ChromeOptions)->addArguments(collect(['--window-size=1920,1080'])
            ->unless(false, function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless',
            ]);
        })->all());

        $this->browser = RemoteWebDriver::create(
            'http://localhost:4444/wd/hub',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * @param string $xPath
     * @param int $timeInSeconds
     * @param int $intervalInMillisecond
     * @return void
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    protected function waitForElement(string $xPath, int $timeInSeconds = 10, int $intervalInMillisecond = 200): void
    {
        $element = WebDriverBy::xpath($xPath);

        $this->browser->wait($timeInSeconds, $intervalInMillisecond)
            ->until(WebDriverExpectedCondition::visibilityOfElementLocated($element),
                'Element not found: ' . $xPath);
    }

    /**
     * @param string $xPath
     * @return string
     */
    protected function getTextOfElement(string $xPath): string
    {
        return $this->browser->findElement(WebDriverBy::xpath($xPath))->getText();
    }

    protected function clickOnElement(string $xPath): void
    {
        $this->browser->findElement(WebDriverBy::xpath($xPath))->click();
    }

    /**
     * @throws UnsupportedOperationException
     */
    protected function entireHtmlOfElement(string $xPath): mixed
    {
        return $this->browser->findElement(WebDriverBy::xpath($xPath))
            ->getDomProperty('innerHTML');
    }

    /**
     * @param string $xPath
     * @return void
     */
    protected function scrollToComponent(string $xPath): void
    {
        $element = $this->browser->findElement(
            WebDriverBy::xpath($xPath)
        );

        $this->browser->executeScript('arguments[0].scrollIntoView();', [$element]);
    }

    public function __destruct()
    {
        $this->browser->quit();
    }
}
