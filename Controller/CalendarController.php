<?php

namespace CanalTP\MttBundle\Controller;

/*
 * CalendarController
 */
use CanalTP\MttBundle\Entity\Calendar;
use CanalTP\MttBundle\Form\Type\CalendarType;
use Symfony\Component\HttpFoundation\Request;

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
}
