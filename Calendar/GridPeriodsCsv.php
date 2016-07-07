<?php

namespace CanalTP\MttBundle\Calendar;

use CanalTP\MttBundle\CsvModelInterface;
use CanalTP\MttBundle\Entity\Calendar;

class GridPeriodsCsv implements CsvModelInterface
{
    private $calendars;

    /**
     * GridCalendarCsv constructor.
     * @param Calendar[] $calendars
     */
    public function __construct(array $calendars)
    {
        $this->calendars = $calendars;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return ['grid_calendar_id', 'start_date', 'end_date'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRows()
    {
        $rows = [];
        foreach ($this->calendars as $calendar) {
            $rows[] = [
                $calendar->getId(),
                $calendar->getStartDate()->format('Ymd'),
                $calendar->getEndDate()->format('Ymd'),
            ];
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileName()
    {
        return 'grid_periods.csv';
    }
}
