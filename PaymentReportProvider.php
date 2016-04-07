<?php
namespace Reporting\Provider;


use Entity\Payment;
use Entity\PaymentReport;
use ReportingBundle\Provider\AbstractProvider;

class PaymentReportProvider extends AbstractProvider
{
    public function gather(\DateTime $start, \DateTime $end)
    {
        $reports = $this->prepareReports($start, $end);

        /* @var $report PaymentReport */
        foreach ($reports as $key => $report) {
            $datetime = $report->getReportDatetime();
            $startOfDay = $this->getStartOfDay($datetime);
            $endOfDay = $this->getEndOfDay($datetime);

            $totalOfDay = $this->getEntityManager()
              ->createQueryBuilder()
              ->select('sum(p.amount)')
              ->from("CoreStreamerBaseBundle:Payment", 'p')
              ->andWhere("p.createdAt >= :start")
              ->andWhere("p.createdAt <= :end")
              ->andWhere("p.status = :status")
              ->setParameter("start", $startOfDay->format('Y-m-d H:i:s'))
              ->setParameter("end", $endOfDay->format('Y-m-d H:i:s'))
              ->setParameter("status", Payment::FINISHED)
              ->getQuery()
              ->getSingleScalarResult();

            $totalOfDay = ($totalOfDay) ? $totalOfDay : 0;
            $report->setTotal($totalOfDay);
        }
        $this->saveReports($reports);
    }

    public function __toString()
    {
        return "Payment Reports Provider";
    }

    public function getDataFields()
    {
        return array(
            'total',
        );
    }

    public function getReportClass()
    {
        return "\\CoreStreamer\Bundle\BaseBundle\Entity\PaymentReport";
    }

    public function getReportRepository()
    {
        return "CoreStreamerBaseBundle:PaymentReport";
    }
}