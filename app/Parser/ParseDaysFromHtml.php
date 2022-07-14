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
        $months = $this->getCalendarDayComponents($monthsHtml);
        $collection = collect();

        foreach ($months as $month) {
            $arr = [];

            $arr['abbr_name'] = $this->getMonthName($month);;
            $arr['days'] = $this->getDays($month);

            $collection->push($arr);
        }

        return $collection;
    }

    /**
     * @param string $months
     * @return array
     */
    private function getCalendarDayComponents(string $months): array
    {
        preg_match_all('/(?<=<div).*?(?=<\/table>)/',
            $months,
            $all_components);

        return $all_components[0];
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
        $result = [];

        foreach ($parsedDays[0] as $day) {
            preg_match('/(?<=calendar-day-)(?<result>[\d\/]+)(?=")/', $day, $parsedDate);
            preg_match('/(?<=aria-disabled=")(?<result>true|false)(?=" aria-label)/', $day, $available);
            preg_match('/available for check out/', $day, $availableForCheckin);
            $changedDateFormat = Carbon::createFromFormat('m/d/Y', $parsedDate['result'])->format('Y-m-d');

            $result[] = [
                'date' => $changedDateFormat,
                'available' => !filter_var( $available['result'], FILTER_VALIDATE_BOOLEAN),
                'available_for_checkin' => (bool) $availableForCheckin
            ];
        }

        return $result;
    }
}