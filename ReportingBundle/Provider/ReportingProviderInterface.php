<?php
namespace ReportingBundle\Provider;


use CoreStreamer\Bundle\ReportingBundle\Entity\ReportInterface;

interface ReportingProviderInterface
{
    public function gather(\DateTime $start, \DateTime $end);
    public function prepareReports(\DateTime $start, \DateTime $end);
    public function saveReports($reports=array());
    public function remove(ReportInterface $report);
    public function save(ReportInterface $report);

    public function getDataFields();
    public function getReport(\Datetime $datetime, $type);
    public function getReports(\Datetime $start, \Datetime $end, $type);
    public function getDailyReportsOfMonth($month, $year);
    public function getMonthlyReportsOfYear($year);
    public function getYearlyReports();
    public function getAvailableReportYears();
    public function getAvailableReportMonths();
    public function getAvailableReportDays($year, $month);
}