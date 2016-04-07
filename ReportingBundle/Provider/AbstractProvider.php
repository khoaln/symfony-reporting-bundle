<?php
namespace ReportingBundle\Provider;


use CoreStreamer\Bundle\BaseBundle\Entity\StreamingDeviceReport;
use CoreStreamer\Bundle\ReportingBundle\Entity\AbstractReport;
use CoreStreamer\Bundle\ReportingBundle\Entity\ReportInterface;
use CoreStreamer\Bundle\ReportingBundle\Entity\TimePeriodReporting;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Smvn\Bundle\CommonBundle\DataTable\DataTable;

abstract class AbstractProvider implements ReportingProviderInterface
{
    const SECONDS_IN_DAY = 86400;
    const END_DATETIME = "2345-06-07 08:09:10";

    /* @var $container Container*/
    protected $container;

    public function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->getReportRepository());
    }

    public function createQueryBuilder($alias=null)
    {
        return $this->getRepository()->createQueryBuilder($alias);
    }

    /*
     * Prepare daily reports
     */
    public function prepareReports(\DateTime $start, \DateTime $end)
    {
        $reportClass = $this->getReportClass();
        $range = $end->getTimestamp() - $start->getTimestamp();
        $num = ceil(($range / self::SECONDS_IN_DAY) + 1) - 1;
        $reports = array();

        for ($i = 0; $i < $num; $i++) {
            $report = new $reportClass();
            $datetime = new \DateTime();
            $datetime->setTimestamp($start->getTimestamp() + ($i * self::SECONDS_IN_DAY));
            $datetime->setTime(0, 0, 0);
            $report->setReportDatetime($datetime);
            $reports[$datetime->getTimestamp()] = $report;
        }

        return $reports;
    }

    public function saveReports($reports=array())
    {
        $mReports = array();
        $yReports = array();
        $filters = null;
        $count = 0;
        foreach ($reports as $timestamp => $report) {
            /** @var AbstractReport $report */
            $time = $report->getReportDateTime();
            $m = $this->getStartOfMonth($time);
            $y = $this->getStartOfYear($time);
            $filters = $report->getFilters();
            $mReports[$m->getTimestamp()] = array(
                'datetime' => $m,
                'filters' => $filters
            );

            $yReports[$y->getTimestamp()] = array(
                'datetime' => $y,
                'filters' => $filters
            );

            $count ++;
            $this->save($report);
            if ($count == 30) {
                $count = 0;
                $this->flush();
            }
        }
        $this->flush();

        $this->saveMonthlyReports($mReports);
        $this->saveYearlyReports($yReports);
        $this->saveTotalReport($filters);
    }

    protected function savePeriodicReports($reports=array(), $type=TimePeriodReporting::MONTHLY,
        $unitType=TimePeriodReporting::DAILY) {

        $reportClass = $this->getReportClass();
        $count = 0;

        $periodFunctionName = "Month";

        if (TimePeriodReporting::YEARLY == $type) {
            $periodFunctionName = "Year";
        }

        foreach ($reports as $timestamp => $info) {
            /** @var AbstractReport $report */
            $report = new $reportClass();
            $report->setReportDatetime($info['datetime']);
            $report->setType($type);
            $report->setFilters($info['filters']);

            $fStart = "getStartOf".$periodFunctionName;
            $fEnd = "getEndOf".$periodFunctionName;
            $start = $this->$fStart($info['datetime']);
            $end = $this->$fEnd($info['datetime']);

            $selects = array();
            $fields = $this->getDataFields();
            foreach ($fields as $field) {
                $selects[] = sprintf("sum(r.%s) as %s", $field, $field);
            }

            $qb = $this->getRepository()
                ->createQueryBuilder('r')
                ->select(implode(', ', $selects))
                ->andWhere('r.type = :type')
                ->andWhere('r.reportDatetime >= :start')
                ->andWhere('r.reportDatetime <= :end')
                ->setParameter('type', $unitType)
                ->setParameter('start', $start->format('Y-m-d H:i:s'))
                ->setParameter('end', $end->format('Y-m-d H:i:s'));

            if (null != $info['filters']) {
                $qb->andWhere('r.filters = :filters')
                    ->setParameter('filters', $info['filters']);
            }
            $result = $qb->getQuery()->getSingleResult();

            foreach ($fields as $field) {
                $setField = "set".ucfirst($field);
                $report->$setField($result[$field]);
            }

            $count ++;
            $this->save($report);
            if ($count == 30) {
                $count = 0;
                $this->flush();
            }
        }
        $this->flush();
    }

    protected function saveMonthlyReports($reports=array())
    {
        $this->savePeriodicReports($reports);
    }

    protected function saveYearlyReports($reports=array())
    {
        $this->savePeriodicReports(
            $reports,
            TimePeriodReporting::YEARLY,
            TimePeriodReporting::DAILY
        );
    }

    protected function saveTotalReport($filters = null)
    {
        $reportClass = $this->getReportClass();
        /** @var AbstractReport $report */
        $report = new $reportClass();
        $report->setReportDatetime(new \DateTime(self::END_DATETIME));
        $report->setType(TimePeriodReporting::TOTAL);

        $selects = array();
        $fields = $this->getDataFields();
        foreach ($fields as $field) {
            $selects[] = sprintf("sum(r.%s) as %s", $field, $field);
        }

        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('r')
            ->select(implode(', ', $selects))
            ->andWhere('r.type = :type')
            ->setParameter('type', TimePeriodReporting::YEARLY);

        if ($filters) {
            $report->setFilters($filters);
            $queryBuilder->andWhere("r.filters = :filters")
                ->setParameter("filters", $filters);
        }

        $result = $queryBuilder->getQuery()
            ->getSingleResult();

        foreach ($fields as $field) {
            $setField = "set".ucfirst($field);
            $report->$setField($result[$field]);
        }

        $this->saveAndFlush($report);
    }

    public function remove(ReportInterface $report)
    {
        $this->getEntityManager()->remove($report);
    }

    public function save(ReportInterface $report)
    {
        $eReport = $this->getReport(
            $report->getReportDateTime(),
            $report->getType(),
            $report->getFilters()
        );

        if ($eReport) {
            $fields = $this->getDataFields();
            foreach ($fields as $field) {
                $field = ucfirst($field);
                $setField = "set".$field;
                $getField = "get".$field;
                $eReport->$setField($report->$getField());
            }
            return $eReport;
        } else {
            $this->getEntityManager()->persist($report);
            return $report;
        }
    }

    public function saveAndFlush(ReportInterface $report)
    {
        $report = $this->save($report);
        $this->flush();
        return $report;
    }

    public function getReport(\Datetime $datetime, $type, $filters=NULL)
    {
        return $this->getRepository()->findOneBy(array(
                'reportDatetime' => $datetime,
                'type' => $type,
                'filters' => $filters
            ));
    }

    public function getYearlyReport($year)
    {
        return $this->getReport(
            \DateTime::createFromFormat('Y-m-d H:i:s', "$year-01-01 00:00:00"),
            TimePeriodReporting::YEARLY
        );
    }

    public function getMonthlyReport($year, $month)
    {
        return $this->getReport(
            \DateTime::createFromFormat('Y-m-d H:i:s', "$year-$month-01 00:00:00"),
            TimePeriodReporting::MONTHLY
        );
    }

    public function getDailyReport($year, $month, $day)
    {
        return $this->getReport(
            \DateTime::createFromFormat('Y-m-d H:i:s', "$year-$month-$day 00:00:00"),
            TimePeriodReporting::DAILY
        );
    }

    public function getTotalReport($filters=NULL)
    {
        $condition = array(
            'type' => TimePeriodReporting::TOTAL,
            'filters' => $filters
        );

        return $this->getRepository()->findOneBy($condition);
    }

    public function getReports(\Datetime $start=NULL, \Datetime $end=NULL, $type=TimePeriodReporting::TOTAL, $paging=FALSE)
    {
        $qb = $this->getRepository()->createQueryBuilder('r');

        if ($type) {
            $qb->andWhere('r.type = :type')
                ->setParameter('type', $type);
        }

        if ($start) {
            $qb->andWhere('r.reportDatetime >= :start')
                ->setParameter('start', $start->format('Y-m-d H:i:s'));
        }

        if ($end) {
            $qb->andWhere('r.reportDatetime <= :end')
                ->setParameter('end', $end->format('Y-m-d H:i:s'));
        }

        $qb->orderBy('r.reportDatetime', 'asc');

        $query = $qb->getQuery();

        if ($paging) {
            $dataTable = $this->getDataTable();
            $dataTable->setQuery($query);
            return $dataTable;
        } else {
            return $query->getResult();
        }
    }

    public function getDailyReportsOfMonth($year, $month, $paging=FALSE)
    {
        $datetime = new \DateTime();
        $datetime->setDate($year, $month, 1);
        return $this->getReports(
            $this->getStartOfMonth($datetime),
            $this->getEndOfMonth($datetime),
            TimePeriodReporting::DAILY,
            $paging
        );
    }

    public function getMonthlyReportsOfYear($year, $paging=FALSE)
    {
        $datetime = new \DateTime();
        $datetime->setDate($year, 1, 1);
        return $this->getReports(
            $this->getStartOfYear($datetime),
            $this->getEndOfYear($datetime),
            TimePeriodReporting::MONTHLY,
            $paging
        );
    }

    public function getYearlyReports($paging=FALSE)
    {
        $query = $this->getRepository()
            ->createQueryBuilder('r')
            ->andWhere('r.type = :type')
            ->setParameter('type', TimePeriodReporting::YEARLY)
            ->orderBy('r.reportDatetime', 'asc')
            ->getQuery();

        if ($paging) {
            $dataTable = $this->getDataTable();
            $dataTable->setQuery($query);
            return $dataTable;
        } else {
            return $query->getResult();
        }
    }

    public function getAvailableReportYears()
    {
        $result = $this->getRepository()
            ->createQueryBuilder('r')
            ->select('distinct r.reportDatetime')
            ->andWhere('r.type = :type')
            ->setParameter('type', TimePeriodReporting::YEARLY)
            ->getQuery()->getResult();

        $years = array();
        foreach ($result as $date) {
            $y = $date['reportDatetime']->format('Y');
            $years[$y] = $y;
        }

        return $years;
    }

    public function getAvailableReportMonths()
    {
        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = $i;
        }

        return $months;
    }

    public function getAvailableReportDays($year, $month)
    {
        $days = array();
        $d = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        for ($i = 1; $i <= $d; $i++) {
            $days[$i] = $i;
        }

        return $days;
    }

    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    public function getEntityManager()
    {
        /* @var $em EntityManager */
        $em = $this->getServiceContainer()->get("doctrine.orm.entity_manager");
        return $em;
    }

    public function getDataTable()
    {
        /* @var $dt DataTable */
        $dt = $this->getServiceContainer()->get('data_table_factory')->create();
        return $dt;
    }

    public function setServiceContainer(Container $container)
    {
        $this->container = $container;
    }

    public function  getServiceContainer()
    {
        return $this->container;
    }

    abstract public function getReportRepository();

    abstract public function getReportClass();

    public function getStartOfDay(\DateTime $datetime)
    {
        $year = $datetime->format('Y');
        $month = $datetime->format('m');
        $day = $datetime->format('d');
        $start = new \DateTime();
        $start->setDate($year, $month, $day);
        $start->setTime(0, 0, 0);
        return $start;
    }

    public function getStartOfMonth(\DateTime $datetime)
    {
        $year = $datetime->format('Y');
        $month = $datetime->format('m');
        $start = new \DateTime();
        $start->setDate($year, $month, 1);
        $start->setTime(0, 0, 0);
        return $start;
    }

    public function getStartOfYear(\DateTime $datetime)
    {
        $year = $datetime->format('Y');
        $start = new \DateTime();
        $start->setDate($year, 1, 1);
        $start->setTime(0, 0, 0);
        return $start;
    }

    public function getEndOfDay(\DateTime $datetime)
    {
        $year = $datetime->format('Y');
        $month = $datetime->format('m');
        $day = $datetime->format('d');
        $end = new \DateTime();
        $end->setDate($year, $month, $day);
        $end->setTime(23, 59, 59);
        return $end;
    }

    public function getEndOfMonth(\DateTime $datetime)
    {
        $end = $this->getNextMonth($datetime);
        $end->modify("-1 second");
        return $end;
    }

    public function getEndOfYear(\DateTime $datetime)
    {
        $year = $datetime->format('Y');
        $end = new \DateTime();
        $end->setDate($year, 12, 31);
        $end->setTime(23, 59, 59);
        return $end;
    }

    public function getNextMonth(\DateTime $datetime)
    {
        $year = $datetime->format('Y');
        $month = $datetime->format('m');
        $next = new \DateTime();
        $next->setDate($year, $month + 1, 1);
        $next->setTime(0, 0, 0);
        return $next;
    }

    public function __toString()
    {
        return "Abstract Provider";
    }

    public function getDataFields()
    {
        return array();
    }

    public function getMonthName($monthNumber)
    {
        return date("F", mktime(0, 0, 0, $monthNumber, 10));

    }
}