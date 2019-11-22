<?php

namespace WebEtDesign\CmsBundle\Services;

use DateTime;
use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_GetReportsResponse as Google_Response;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_Report as Google_Report;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_ReportRow;
use MediaFigaro\GoogleAnalyticsApi\Service\GoogleAnalyticsService;
use Symfony\Component\HttpFoundation\JsonResponse;

class Analytics
{
    /**
     * @var GoogleAnalyticsService
     */
    private $analyticsService;

    private $viewId;

    /**
     * @var Google_Service_AnalyticsReporting
     */
    private $analytics;

    /**
     * @var Google_Client
     */
    private $client;
    /**
     * @var int
     */
    public $maxPage;

    /**
     * Analytics constructor.
     * @param GoogleAnalyticsService $analyticsService
     * @param $viewId
     * @param int $maxPage
     */
    public function __construct(GoogleAnalyticsService $analyticsService, $viewId, int $maxPage = 10)
    {
        $this->analyticsService = $analyticsService;
        $this->viewId = $viewId;
        $this->client = $analyticsService->getClient();
        $this->analytics = new Google_Service_AnalyticsReporting($this->client);

        $this->maxPage = $maxPage;
    }

    /**
     * @param string $metric_name
     * @param string $dimension_name
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getBasicChart($metric_name, $dimension_name, $start, $end = "today"){
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate(date('Y-m-d', strtotime($start)));
        $dateRange->setEndDate(date('Y-m-d', strtotime($end)));

        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression("ga:" . $metric_name);
        $metric->setAlias(ucfirst($metric_name));

        $dimension = new Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName("ga:" . $dimension_name);

        return $this->makeRequest([$metric], [$dimension], [$dateRange], "formatDataChart",$this->maxPage);
    }

    /**
     * Return Number or Users per Browser
     * @param string $start
     * @return array
     */
    public function getBrowsers($start = "30 days ago"){
        return $this->getBasicChart( "users", "browser", $start);
    }

    /**
     * Return the number of users each day for periods :
     *      [monday -> today] this week
     *      [monday -> sunday] last week
     * @return array
     */
    public function getUserWeek(){
        $thisWeek = new Google_Service_AnalyticsReporting_DateRange();
        // this monday
        $thisWeek->setStartDate(date('Y-m-d', strtotime('this week')));
        // today
        $thisWeek->setEndDate(date('Y-m-d'));

        $lastWeek = new Google_Service_AnalyticsReporting_DateRange();
        // last monday
        $lastWeek->setStartDate(date('Y-m-d', strtotime('last week')));
        //last sunday
        $lastWeek->setEndDate(date('Y-m-d', strtotime('sunday this week')));

        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression("ga:users");
        $metric->setAlias("sessions");

        $dimension_1 = new Google_Service_AnalyticsReporting_Dimension();
        $dimension_1->setName("ga:date");

        $dimension_2 = new Google_Service_AnalyticsReporting_Dimension();
        $dimension_2->setName("ga:nthDay");

        $response_this_week = $this->makeRequest([$metric], [$dimension_1, $dimension_2], [$thisWeek]);
        $response_last_week = $this->makeRequest([$metric], [$dimension_1, $dimension_2], [$lastWeek]);

        $response_this_week = $this->getDiffForWeek($response_this_week);
        $response_last_week = $this->getDiffForWeek($response_last_week);

        $data = [
            "labels" => ["Lun.", "Mar.", "Mer.", "Jeu.", "Ven.", "Sam.", "Dim."],
            "values" => [
                "last_week" => $response_last_week["values"],
                "this_week" => $response_this_week["values"]
            ]
        ];

        return $data;

    }

    /**
     * add 0 to values table if a day is not defined
     * @param array $array
     * @return array
     */
    private function getDiffForWeek($array){
        $previous = null;
        // append 0 in values array if days are missing because API not return days without session
        foreach ($array['labels'] as $key => $item) {
            if ($previous && date('d-m-Y', strtotime($previous . " 1 day") ) != date('d-m-Y', strtotime($item))){

                $diff = abs(strtotime($previous) - strtotime($item));
                $years = floor($diff / (365*60*60*24));
                $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)) - 1;

                for ($i = 0; $i < $days; $i++){
                    array_splice($array['values'], $key, 0, 0);
                }
            }
            $previous = $item;
        }
        return $array;
    }

    /**
     * Return the number of users each month for periods :
     *      [january -> today] this year
     *      [january -> december] last year
     * @return array
     */
    public function getUserYear(){
        $thisYear = new Google_Service_AnalyticsReporting_DateRange();
        // this monday
        $thisYear->setStartDate(date('Y-m-d', strtotime('first day of january this year')));
        // today
        $thisYear->setEndDate(date('Y-m-d', time()));

        $lastYear = new Google_Service_AnalyticsReporting_DateRange();
        // last monday
        $lastYear->setStartDate(date('Y-m-d', strtotime('first day of january last year')));
        //last sunday
        $lastYear->setEndDate(date('Y-m-d', strtotime('last day of december last year')));

        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression("ga:users");
        $metric->setAlias("sessions");

        $dimension_1 = new Google_Service_AnalyticsReporting_Dimension();
        $dimension_1->setName("ga:month");

        $dimension_2 = new Google_Service_AnalyticsReporting_Dimension();
        $dimension_2->setName("ga:nthMonth");

        $response_this_year = $this->makeRequest([$metric], [$dimension_1, $dimension_2], [$thisYear]);
        $response_last_year = $this->makeRequest([$metric], [$dimension_1, $dimension_2], [$lastYear]);

        $response_this_year = $this->getDiffForYear($response_this_year);
        $response_last_year = $this->getDiffForYear($response_last_year);

        $data = [
            "labels" => ['Jan.','Fev.','Mar.','Avr.','Mai.','Jui.', 'Juil.','Aou.','Sep.','Oct.','Nov.','Dec.'],
            "values" => [
                "last_year" => $response_last_year["values"],
                "this_year" => $response_this_year["values"]
            ]
        ];

        return $data;
    }

    /**
     * add 0 to values table if a month is not defined
     * @param array $array
     * @return array
     */
    private function getDiffForYear($array){
        $previous = "00";

        // append 0 in values array if days are missing because API not return days without session
        foreach ($array['labels'] as $key => $item) {
            if (intval($previous) + 1 != intval($item)){
                $diff = intval($item) - intval($previous) - 1;
                for ($i = 0; $i < $diff; $i++){
                    array_splice($array['values'], $key, 0, 0);
                }
            }
            $previous = $item;
        }

        return $array;
    }

    /**
     * Return number of users per source
     * @param string $start
     * @return array
     */
    public function getSources($start = "first day of january this year"){

        $data = $this->getBasicChart( "users", "channelGrouping", $start);

        foreach ($data["labels"] as $key => $label) {
            if ($label == "(none)"){
                $data["labels"][$key] = "Direct";
            }
        }
        return $data;
    }

    /**
     * Return of users per device
     * @param string $start
     * @return array
     */
    public function getDevices($start = "30 days ago"){

        $data = $this->getBasicChart( "users", "deviceCategory", $start);

        foreach ($data["labels"] as $key =>$label) {
            switch (strtolower($label)) {
                case 'mobile':
                    $data["labels"][$key] = "Mobile";
                    break;
                case 'desktop':
                    $data["labels"][$key] = "Ordinateur";
                    break;
                case 'tablet':
                    $data["labels"][$key] = "Tablette";
                    break;
            }
        }

        return $data;
    }

    /**
     * Return of users per country
     * @param string $start
     * @return array
     */
    public function getCountries($start = "first day of january this year"){

        $response = $this->getBasicChart( "users", "country", $start);
        $data = [];
        $data[] = ['Country', 'Popularity'];

        foreach ($response["labels"] as $key => $item) {
            $data[] = [
                $response["labels"][$key],
                intval($response["values"][$key])
            ];
        }

        return $data;
    }

    public function getUsers($start = "30 days ago"){

        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate(date('Y-m-d', strtotime($start)));
        $dateRange->setEndDate(date('Y-m-d', strtotime("today")));

        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression("ga:users");
        $metric->setAlias("Users");

        $d1 = new Google_Service_AnalyticsReporting_Dimension();
        $d1->setName('ga:hour');

        $d2 = new Google_Service_AnalyticsReporting_Dimension();
        $d2->setName('ga:dayOfWeekName');

        $d3 = new Google_Service_AnalyticsReporting_Dimension();
        $d3->setName('ga:day');

        $data = $this->makeRequest([$metric], [$d1, $d2, $d3], [$dateRange], "formatDataUsers");

        return $data;

    }

    private function makeRequest(array $metrics, array $dimensions, array $dates, $method = "formatDataChart", $max = null){
        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->viewId);
        $request->setMetrics($metrics);
        $request->setDimensions($dimensions);
        $request->setDateRanges($dates);

        if ($max){
            $request->setPageSize($max);
        }

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );
        $response = $this->analytics->reports->batchGet( $body );

        return $this->$method($response);
    }

    private function formatDataChart(Google_Response $response)
    {
        $data = [
            "labels" => [],
            "values" => []
        ];

        /** @var Google_Report $report */
        foreach ($response->getReports() as $report) {
            /** @var Google_Service_AnalyticsReporting_ReportRow $row */
            foreach ($report->getData()->getRows() as $row) {
                array_push($data["values"], $row->getMetrics()[0]->getValues()[0]);
                array_push($data["labels"], ucfirst($row->getDimensions()[0]));
            }

        }
        return $data;
    }

    private function formatDataUsers(Google_Response $response)
    {
        $data = [
            "labels" => [],
            "values" => []
        ];

        $days = ["Monday" => 0, "Tuesday" => 0, "Wednesday" => 0, "Thursday" => 0, "Friday" => 0, "Saturday" => 0, "Sunday" => 0];
        $visits = [];

        for($i = 0; $i < 24; $i++){
            $visits[] = $days;
        }


        /** @var Google_Report $report */
        foreach ($response->getReports() as $report) {
            /** @var Google_Service_AnalyticsReporting_ReportRow $row */
            foreach ($report->getData()->getRows() as $row) {
                $visits[intval($row->getDimensions()[0])][ucfirst($row->getDimensions()[1])] += intval($row->getMetrics()[0]->getValues()[0]);
            }

            if ($report->getData()->getMaximums()){
                $data["max"] = $report->getData()->getMaximums()[0]->getValues()[0];
            }
        }

        $data["values"] = $visits;

        return $data;
    }

}
