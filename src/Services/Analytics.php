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
     * Analytics constructor.
     * @param GoogleAnalyticsService $analyticsService
     * @param $viewId
     */
    public function __construct(GoogleAnalyticsService $analyticsService, $viewId)
    {
        $this->analyticsService = $analyticsService;
        $this->viewId = $viewId;
        $this->client = $analyticsService->getClient();
        $this->analytics = new Google_Service_AnalyticsReporting($this->client);

    }

    /**
     * Return Number or Users per Browser last 30 days
     * @return array
     */
    public function getBrowsers(){

        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("30daysAgo");
        $dateRange->setEndDate("today");

        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression("ga:users");
        $metric->setAlias("users");

        $dimension = new Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName("ga:browser");

        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->viewId);
        $request->setMetrics(array($metric));
        $request->setDimensions(array($dimension));
        $request->setDateRanges([$dateRange]);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );
        $response = $this->analytics->reports->batchGet( $body );

        return $this->formatDataChart($response);
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
        $thisWeek->setStartDate(
            date('Y-m-d',
                strtotime('this week',
                    time()
                )
            )
        );
        // today
        $thisWeek->setEndDate(
            date('Y-m-d', time()
            )
        );

        $lastWeek = new Google_Service_AnalyticsReporting_DateRange();
        // last monday
        $lastWeek->setStartDate(
            date("Y-m-d",
                strtotime(
                    date('Y-m-d',
                        strtotime('this week', time()
                        )
                    ) . "-1 week"
                )
            )
        );
        //last sunday
        $lastWeek->setEndDate(date("Y-m-d",
            strtotime(
                date('Y-m-d',
                    strtotime('this week', time()
                    )
                ) . "-1 day"
            )
        ));

        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression("ga:users");
        $metric->setAlias("sessions");

        $dimension_1 = new Google_Service_AnalyticsReporting_Dimension();
        $dimension_1->setName("ga:date");

        $dimension_2 = new Google_Service_AnalyticsReporting_Dimension();
        $dimension_2->setName("ga:nthDay");

        $response_this_week = $this->makeRequest([$metric], [$dimension_1, $dimension_2], [$thisWeek]);
        $response_last_week = $this->makeRequest([$metric], [$dimension_1, $dimension_2], [$lastWeek]);

        $previous = null;

        // append 0 in values array if days are missing because API not return days without session
        foreach ($response_last_week['labels'] as $key => $item) {
            if ($previous && date('d-m-Y', strtotime($previous . " 1 day") ) != date('d-m-Y', strtotime($item))){

                $diff = abs(strtotime($previous) - strtotime($item));
                $years = floor($diff / (365*60*60*24));
                $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
                $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)) - 1;

                for ($i = 0; $i < $days; $i++){
                    array_splice($response_last_week['values'], $key, 0, 0);
                }
            }
            $previous = $item;
        }

        $data = [
            "labels" => ["Lun.", "Mar.", "Mer.", "Jeu.", "Ven.", "Sam.", "Dim."],
            "values" => [
                "last_week" => $response_last_week["values"],
                "this_week" => $response_this_week["values"]
            ]
        ];

        return $data;

    }

    private function makeRequest(array $metrics, array $dimensions, array $dates){
        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($this->viewId);
        $request->setMetrics($metrics);
        $request->setDimensions($dimensions);
        $request->setDateRanges($dates);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );
        $response = $this->analytics->reports->batchGet( $body );

        return $this->formatDataChart($response);
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
                array_push($data["labels"], $row->getDimensions()[0]);
            }

        }
        return $data;
    }

    /**
     * @param Google_Response $response
     * @return array
     */
    private function formatData(Google_Response $response)
    {
        $data = [];
        $data_row = [];

        /** @var Google_Report $report */
        foreach ($response->getReports() as $report) {
            /** @var Google_Service_AnalyticsReporting_ReportRow $row */
            foreach ($report->getData()->getRows() as $row) {
                $data_row[$row->getDimensions()[0]] = $row->getMetrics()[0]->getValues()[0];
            }
            $data[]["value"] = $data_row;
            $data_row = [];

            if ($report->getData()->getTotals()){
                $data[]["total"] = $report->getData()->getTotals()[0]->getValues()[0];
            }
            if ($report->getData()->getMinimums()){
                $data[]["min"] = $report->getData()->getMinimums()[0]->getValues()[0];
            }
            if ($report->getData()->getMaximums()){
                $data[]["max"] = $report->getData()->getMaximums()[0]->getValues()[0];
            }
        }
        return $data;
    }

}
