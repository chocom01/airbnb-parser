<?php

namespace App\Http\Controllers;

use App\Scraper\Airbnb\Calendar;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\UnsupportedOperationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ScrapCalendarController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param Calendar $calendar
     * @return Response
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws UnsupportedOperationException
     */
    public function __invoke(Request $request, Calendar $calendar): Response
    {
        $request->validate([
            'listing_id' => 'required|numeric',
            'from_month' => 'required|numeric',
            'from_year' => 'required|numeric',
            'count_months' => 'required|numeric',
        ]);

        $monthsCollection = $calendar->getMonths(
            $request['listing_id'],
            $request['from_month'],
            $request['from_year'],
            $request['count_months']
        );

        return response($monthsCollection);
    }
}
