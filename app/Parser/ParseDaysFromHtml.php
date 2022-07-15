<?php

namespace App\Parser;

use Carbon\Carbon;
use Illuminate\Support\Collection;

trait ParseDaysFromHtml
{
    /**
     * @param string $monthsHtml
     * @return Collection
     */
    public function parseCalendar(string $monthsHtml): Collection
    {
        $monthsComponents = $this->getCalendarDayComponents($monthsHtml);
        $monthsCollection = collect();

        foreach ($monthsComponents as $monthComponent) {
            $monthsCollection->push([
                'month_name' => $this->getMonthName($monthComponent),
                'days' => $this->getDays($monthComponent)
            ]);
        }

        return $monthsCollection;
    }

    /**
     * @param string $monthsHtml
     * @return array
     */
    private function getCalendarDayComponents(string $monthsHtml): array
    {
        preg_match_all('/(?<=<div).*?(?=<\/table>)/', $monthsHtml, $monthsComponents);

        return $monthsComponents[0];
    }

    /**
     * @param string $component
     * @return string
     */
    private function getMonthName(string $component): string
    {
        preg_match('/(?<=elementtiming="LCP-target">).*?(?=<\/h3>)/', $component, $parsedMonthName);

        // formatted month, looks like 'September-2022'
        return preg_replace('/ /', '-', $parsedMonthName[0]);
    }

    /**
     * @param string $component
     * @return array
     */
    private function getDays(string $component): array
    {
        preg_match_all('/(?<=<td)[\w\W].*?(?=<\/div><\/td>)/', $component, $parsedDays);
        $daysArray = [];

        foreach ($parsedDays[0] as $day) {
            preg_match('/(?<=calendar-day-)(?<result>[\d\/]+)(?=")/', $day, $parsedDate);
            preg_match('/(?<=aria-disabled=")(?<result>true|false)(?=" aria-label)/', $day, $availability);
            preg_match('/available for check out/', $day, $availableForCheckOut);

            $changedDateFormat = Carbon::createFromFormat('m/d/Y', $parsedDate['result'])->format('Y-m-d');
            $available = !filter_var($availability['result'], FILTER_VALIDATE_BOOLEAN);

            $daysArray[] = [
                'date' => $changedDateFormat,
                'available' => $available,
                'available_for_checkin' => $available && !$availableForCheckOut
            ];
        }

        return $daysArray;
    }
}
