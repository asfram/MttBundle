<?php

namespace CanalTP\MttBundle\Controller;

/*
 * CalendarController
 */
use CanalTP\MttBundle\Entity\Calendar;
use CanalTP\MttBundle\Form\Type\CalendarType;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalendarController extends AbstractController
{

    public function createAction(Request $request)
    {
        $em = $this->get('doctrine')->getManager();
        $translator = $this->get('translator');

        $calendar = new Calendar();

        $form = $this->createForm(new CalendarType(), $calendar);
        $form->add('submit', 'submit', ['label' => 'global.validate', 'translation_domain' => 'messages']);

        $form->handleRequest($request);

        if ($request->isMethod('POST') && $form->isValid()) {
            $calendar->setCustomer($this->getUser()->getCustomer());
            $em->persist($calendar);
            $em->flush();
            $this->addFlash('success', $translator->trans('calendar.create.success', [], 'default'));

            return $this->redirectToRoute('canal_tp_mtt_calendars_create');
        }

        return $this->render('CanalTPMttBundle:Calendar:create.html.twig', ['form' => $form->createView()]);
    }

    public function viewAction($externalNetworkId, $externalRouteId, $externalStopPointId, $currentSeasonId)
    {
        $calendarManager = $this->get('canal_tp_mtt.calendar_manager');
        $perimeterManager = $this->get('nmm.perimeter_manager');
        $stopPointManager = $this->get('canal_tp_mtt.stop_point_manager');

        $perimeter = $perimeterManager->findOneByExternalNetworkId(
            $this->getUser()->getCustomer(),
            $externalNetworkId
        );
        $calendars = $calendarManager->getCalendarsForStopPoint(
            $perimeter->getExternalCoverageId(),
            $externalRouteId,
            $externalStopPointId
        );

        $prevNextStopPoints = $stopPointManager->getPrevNextStopPoints(
            $perimeter,
            $externalRouteId,
            $externalStopPointId
        );

        $currentSeason = $this->get('canal_tp_mtt.season_manager')->find($currentSeasonId);

        return $this->render(
            'CanalTPMttBundle:Calendar:view.html.twig',
            array(
                'pageTitle'           => $this->get('translator')->trans(
                    'calendar.view_title',
                    array(),
                    'default'
                ),
                'externalNetworkId'   => $externalNetworkId,
                'externalStopPointId' => $externalStopPointId,
                'calendars'           => $calendars,
                'current_route'       => $externalRouteId,
                'currentSeason'       => $currentSeason,
                'prevNextStopPoints'  => $prevNextStopPoints,
            )
        );
    }

    /**
     * Displays calendar list
     *
     * @return type
     */
    public function listAction()
    {
        $calendars = $this->getDoctrine()
            ->getRepository('CanalTPMttBundle:Calendar')
            ->findBy(
                ['customer' => $this->getUser()->getCustomer()],
                ['id'=>'desc']
            );

        return $this->render('CanalTPMttBundle:Calendar:list.html.twig', [
          'no_left_menu' => true,
          'calendars'    => $calendars
        ]);
    }

    public function exportAction()
    {
        $calendars = $this->getDoctrine()
            ->getRepository('CanalTPMttBundle:Calendar')
            ->findAll(
                ['customer' => $this->getUser()->getCustomer()],
                ['id' => 'desc']
            )
        ;

        // grid_calendars.csv
        $gridCalendars = Writer::createFromFileObject(new \SplTempFileObject());
        $gridCalendars->setOutputBOM(Reader::BOM_UTF8);
        $gridCalendars->setDelimiter(';');
        $headers = [
            'grid_calendar_id',
            'name',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];
        $gridCalendars->insertOne($headers);

        foreach ($calendars as $calendar) {
            $gridCalendars->insertOne([
                $calendar->getId(),
                $calendar->getTitle(),
                (int) $calendar->isCirculateTheDay(0),
                (int) $calendar->isCirculateTheDay(1),
                (int) $calendar->isCirculateTheDay(2),
                (int) $calendar->isCirculateTheDay(3),
                (int) $calendar->isCirculateTheDay(4),
                (int) $calendar->isCirculateTheDay(5),
                (int) $calendar->isCirculateTheDay(6),
            ]);
        }

        // grid_periods.csv
        $gridPeriods = Writer::createFromFileObject(new \SplTempFileObject());
        $gridPeriods->setOutputBOM(Reader::BOM_UTF8);
        $gridPeriods->setDelimiter(';');
        $headers = ['grid_calendar_id', 'start_date', 'end_date'];
        $gridPeriods->insertOne($headers);

        foreach ($calendars as $calendar) {
            $gridPeriods->insertOne([
                $calendar->getId(),
                $calendar->getStartDate()->format('Ymd'),
                $calendar->getEndDate()->format('Ymd'),
            ]);
        }

        $exportFile = 'export_calendars.zip';
        $exportPath = sys_get_temp_dir().'/'.$exportFile;
        unlink($exportPath);
        $zip = new \ZipArchive();
        $zip->open(sys_get_temp_dir().'/'.$exportFile, \ZipArchive::CREATE);
        $zip->addFromString('grid_calendars.csv', (string) $gridCalendars);
        $zip->addFromString('grid_periods.csv', (string) $gridPeriods);
        $zip->close();

        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$exportFile.'"');
        $response->setContent(file_get_contents($exportPath));

        return $response;
    }
}
