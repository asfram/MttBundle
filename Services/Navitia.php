<?php

/**
 * Description of Network
 *
 * @author vdegroote
 */
namespace CanalTP\MethBundle\Services;

class Navitia
{
    protected $navitia_component;
    protected $navitia_iussaad;

    public function __construct($navitia_component, $navitia_iussaad, $translator)
    {
        $this->navitia_component = $navitia_component;
        $this->navitia_iussaad = $navitia_iussaad;
        $this->translator = $translator;
    }

    /**
     * Returns Lines indexed by modes
     *
     * @param  String  $coverageId
     * @param  type    $networkId
     * @param  Boolean $commercial if true commercial_modes returned, else physical_modes
     * @return type
     */
    public function getLinesByMode($coverageId, $networkId, $commercial = true)
    {
        $result = $this->navitia_iussaad->getLines($coverageId, $networkId, 1);
        // no line found for this network
        if (empty($result) || !isset($result->lines)){
            throw new \Exception($this->translator->trans('services.navitia.no_lines_for_network', array('%network%'=>$networkId), 'exceptions'));
        }

        $lines_by_modes = array();
        foreach ($result->lines as $line) {
            if (!isset($lines_by_modes[$line->commercial_mode->id])) {
                $lines_by_modes[$line->commercial_mode->id] = array();
            }
            $lines_by_modes[$line->commercial_mode->id][] = $line;
        }

        return $lines_by_modes;
    }

    /**
     * Returns line title
     *
     * @param  String $coverageId
     * @param  String $networkId
     * @param  String $lineId
     * @return type
     */
    public function getLineTitle($coverageId, $networkId, $lineId)
    {
        $response = $this->navitia_iussaad->getLine($coverageId, $networkId, $lineId);

        return ($response->lines[0]->name);
    }

    /**
     * Returns Stop Point title
     *
     * @param  String $coverageId
     * @param  String $networkId
     * @param  String $lineId
     * @return type
     */
    public function getStopPointTitle($coverageId, $stopPointId)
    {
        $response = $this->navitia_iussaad->getStopPoint($coverageId, $stopPointId);

        return ($response->stop_points[0]->name);
    }

    /**
     * Returns Stop Point title
     *
     * @param  String $coverageId
     * @param  String $networkId
     * @param  String $lineId
     * @return type
     */
    public function getRouteData($routeExternalId, $externalCoverageId)
    {
        $response = $this->navitia_iussaad->getRoute($externalCoverageId, $routeExternalId);

        return ($response->routes[0]);
    }

    /**
     * Returns Calendars for a stop point and a route
     *
     * @param String $externalCoverageId
     * @param String $externalRouteId
     * @param String $externalStopPointId
     *
     * @return object
     */
    public function getStopPointCalendarsData($externalCoverageId, $externalRouteId, $externalStopPointId)
    {
        //TODO call navitia calendars api
        $calendarsResponse = json_decode(file_get_contents(dirname(__FILE__) . '/tmp/calendars.json'));

        return ($calendarsResponse);
    }

    /**
     * Returns Schedules for a calendar, a stop point and a route
     *
     * @param String $externalCoverageId
     * @param String $externalRouteId
     * @param String $externalStopPointId
     * @param String $externalCalendarId
     *
     * @return object
     */
    public function getCalendarStopSchedules($externalCoverageId, $externalRouteId, $externalStopPointId, $externalCalendarId)
    {
        // TODO call navitia stop schedules api
        $stop_schedulesResponse = json_decode(file_get_contents(dirname(__FILE__) . '/tmp/stop_schedules.json'));
        // TOREMOVE pick randomly inside cork file
        $randomIdx = rand(0, 2);

        return ($stop_schedulesResponse->stop_schedules[$randomIdx]);
    }
}
