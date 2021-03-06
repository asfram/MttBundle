<?php

namespace CanalTP\MttBundle\Tests\Functional\Controller;

class CalendarControllerTest extends AbstractControllerTest
{
    const EXTERNAL_NETWORK_ID = 'network:JDR:2';

    private function getViewRoute()
    {
        return $this->generateRoute(
            'canal_tp_mtt_calendar_view',
            // fake params since we mock navitia
            array(
                'externalNetworkId' => self::EXTERNAL_NETWORK_ID,
                'externalRouteId' => 'test',
                'externalStopPointId' => 'test'
            )
        );
    }

    public function setUp($login = true)
    {
        parent::setUp($login);
        $this->setService('canal_tp_mtt.navitia', $this->getMockedNavitia());
    }

    /**
     * Tests that calendar creation.
     */
    public function testCalendarsCreateAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_create');

        $crawler = $this->doRequestRoute($route);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(1, $crawler->filter('h3')->count(), 'Expected h3 title.');

        $formCrawler = $crawler->filter('form');
        $this->assertCount(1, $formCrawler, 'Titre');
        $this->assertCount(1, $formCrawler, 'Date de début');
        $this->assertCount(1, $formCrawler, 'Date de fin');
        $this->assertCount(1, $formCrawler, 'Jours de semaine (ex : 0000011, pour samedi et dimanche)');

        $form = $crawler->selectButton('Valider')->form();
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '01/06/2016';
        $form['mtt_calendar[weeklyPattern]'] = '0000011';

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(1, $crawler->filter('html:contains("Le calendrier a été créé")'));
    }

    /**
     * Tests error when creating a calendar.
     */
    public function testCalendarsFormErrors()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_create');
        $crawler = $this->doRequestRoute($route);

        // Test all fields required
        $form = $crawler->selectButton('Valider')->form();
        $crawler = $this->client->submit($form);

        $this->assertCount(4, $crawler->filter('.has-error'));

        // Test start date < end date
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[weeklyPattern]'] = '0000011';
        $form['mtt_calendar[startDate]'] = '02/01/2016';
        $form['mtt_calendar[endDate]'] = '01/01/2016';
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));

        // Test end date - start date < 1 day
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[weeklyPattern]'] = '0000011';
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '01/01/2016';
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));

        // Test weekly pattern  > 7 characters
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[weeklyPattern]'] = '00000111';
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '02/01/2016';
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));

        // Test weekly pattern  not 0 or 1
        $form['mtt_calendar[title]'] = 'Samedi et dimanche';
        $form['mtt_calendar[weeklyPattern]'] = '0050011';
        $form['mtt_calendar[startDate]'] = '01/01/2016';
        $form['mtt_calendar[endDate]'] = '02/01/2016';
        $crawler = $this->client->submit($form);

        $this->assertCount(1, $crawler->filter('.has-error'));
    }

    public function testCalendarsPresentViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());

        $this->assertTrue($crawler->filter('h3')->count() == 1, 'Expected h3 title.');
        $this->assertTrue($crawler->filter('.nav.nav-tabs > li')->count() == 4, 'Expected 4 calendars. Found ' . $crawler->filter('.nav.nav-tabs > li')->count());
    }

    /**
     * Tests calendar list page
     */
    public function testCalendarsListAction()
    {
        $route = $this->generateRoute('canal_tp_mtt_calendars_list');
        $crawler = $this->doRequestRoute($route);

        $this->assertTrue($crawler->filter('h1')->count() == 1, 'Expected h1 title.');

        //assert that page title exists and is correct
        $translator = $this->client->getContainer()->get('translator');
        $expectedTitle = $translator->trans('calendar.list.title', [], 'default');
        $this->assertTrue(
            $crawler->filter('h1:contains("' . $expectedTitle. '")')->count() == 1,
            $expectedTitle . ' was expected as page title, but wasn\'t found'
        );

        //assert that calendar create button exists and has correct URI
        $createRoute = $this->generateRoute('canal_tp_mtt_calendars_create');
        $createLabel = $translator->trans('calendar.list.create', [], 'default');

        $this->assertTrue(
            $crawler->filter('html:contains("' . $createLabel. '")')->count() == 1,
            'The label "' . $createLabel . '" wasn\'t found'
        );

        $createUri = $crawler->filter('#calendar_create_btn')->link()->getUri();
        $this->assertContains($createRoute, $createUri);
    }

    public function testCalendarsNamesViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());
        // comes from the stub
        $calendarsName = array('Semaine scolaire', 'Semaine hors scolaire', "Samedi", "Dimanche et fêtes");
        foreach ($calendarsName as $name) {
            $this->assertTrue(
                $crawler->filter('html:contains("' . $name . '")')->count() == 1,
                "Calendar $name not found in answer"
            );
        }
    }

    public function testHoursConsistencyViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());
        $nodeValues = $crawler->filter('.grid-time-column > div:first-child')->each(function ($node, $i) {
            return (int) substr($node->text(), 0, strlen($node->text() - 1));
        });
        foreach ($nodeValues as $value) {
            $this->assertTrue(
                is_numeric($value),
                'Hour not numeric found.'
            );
            $this->assertTrue(
                $value >= 0 && $value < 24,
                "Hour $value not in the range 0<->23."
            );
        }
    }

    public function testMinutesConsistencyViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());
        $nodeValues = $crawler->filter('.grid-time-column > div:not(:first-child)')->each(function ($node, $i) {
            $count = preg_match('/^([\d]+)/', $node->text(), $matches);
            if ($count == 1) {
                return (int) $matches[0];
            } else {
                return false;
            }
        });
        foreach ($nodeValues as $value) {
            $this->assertTrue(
                is_numeric($value),
                'Minute not numeric found.'
            );
            $this->assertTrue(
                $value >= 0 && $value < 60,
                "Minute $value not in the range 0<->59."
            );
        }
    }

    public function testExceptionsViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());

        $this->assertTrue(
            $crawler->filter('html:contains("Sauf le 09/05/2014")')->count() > 0,
            "the exception value was not found in html."
        );
        $this->assertTrue(
            $crawler->filter('html:contains("Y compris le 09/05/2014")')->count() > 0,
            "the exception value was not found in html."
        );
    }

    public function testFootNotesConsistencyViewAction()
    {
        $crawler = $this->doRequestRoute($this->getViewRoute());

        $this->assertTrue(
            $crawler->filter('html:contains("au plus tard la veille du déplacement du lundi au vendredi de 9h à 12h30 et de 13h30 à 16h30.")')->count() > 0,
            "the note value was not found in html."
        );

        $this->assertTrue(
            $crawler->filter(
                'html:contains("au plus tard la veille du déplacement du lundi au vendredi de 9h à 12h30 et de 13h30 à 16h30.")'
            )->count() == 1,
            "the note value was found in html more than once."
        );

        $this->assertTrue(
            $crawler->filter(
                '.tab-content > .tab-pane:first-child .notes-wrapper > div:not(:first-child)'
            )->count() == 4,
            "Expected 4 notes label, found " . $crawler->filter('.tab-content > .tab-pane:first-child .notes-wrapper > div:not(:first-child)')->count()
        );

        $notesLabels = $crawler
            ->filter(
                '.tab-content > .tab-pane:first-child .notes-wrapper > div:not(:first-child) > span.bold'
            )->each(function ($node, $i) {
                return $node->text();
            });

        $asciiStart = 97;
        foreach ($notesLabels as $label) {
            $this->assertTrue(ord($label) == $asciiStart, "Note label $label should be " . chr($asciiStart));
            $asciiStart++;
        }
        // check if we find consistent note in timegrid
        $notes = $crawler->filter('.grid-time-column > div:not(:first-child)')->each(function ($node, $i) {
            $count = preg_match('/^[\d]+([a-z]{1})/', $node->text(), $matches);
            if ($count == 1) {
                return $matches[1];
            }
        });

        foreach ($notes as $note) {
            if (!empty($note)) {
                $this->assertTrue(in_array($note, $notesLabels), "Found note label $note in timegrid not present in notes wrapper.");
            }
        }
    }

    public function testStopPointCodeBlock()
    {
        $translator = $this->client->getContainer()->get('translator');
        $season = $this->getRepository('CanalTPMttBundle:Season')->find(1);

        $crawler = $this->doRequestRoute($this->getViewRoute());
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("MERMB-1")')->count(),
            "Stop point code (external code) not found in stop point timetable view page"
        );
    }
}
