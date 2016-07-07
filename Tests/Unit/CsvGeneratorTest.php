<?php

namespace CanalTP\MttBundle\Tests\Unit;

use CanalTP\MttBundle\Entity\Calendar;
use CanalTP\MttBundle\CsvGenerator;
use CanalTP\MttBundle\Calendar\GridPeriodsCsv;

class CsvGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group test
     */
    public function testGenerateCsv()
    {
        $calendar = new Calendar();
        $calendar->setId(1);
        $calendar->setTitle('title');
        $calendar->setStartDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2016-07-07 12:00:00'));
        $calendar->setEndDate(\DateTime::createFromFormat('Y-m-d H:i:s', '2016-07-08 12:00:00'));
        $calendar->setWeeklyPattern('0100000');

        $csvModel = new GridPeriodsCsv([$calendar]);

        $expected = <<<EOL
grid_calendar_id;start_date;end_date
1;20160707;20160708

EOL;

        $this->assertEquals(CsvGenerator::generateCSV($csvModel), $expected);
    }
}
