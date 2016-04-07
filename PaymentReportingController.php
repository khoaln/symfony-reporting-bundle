<?php

namespace CoreStreamer\Bundle\AdminBundle\Controller;


use CoreStreamer\Bundle\AdminBundle\Form\Type\FilterType\ReportingFilterType;
use CoreStreamer\Bundle\AdminBundle\Reporting\Provider\PaymentReportProvider;
use CoreStreamer\Bundle\AdminBundle\Security\AccessManager;
use CoreStreamer\Bundle\BaseBundle\Entity\PaymentReport;
use Smvn\Bundle\CommonBundle\Controller\BaseCrudController;
use Smvn\Bundle\CommonBundle\DataTable\DataMappingBuilder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

class PaymentReportingController extends BaseCrudController
{
    function __toString()
    {
        return 'PaymentReport';
    }

    public function getEntityName()
    {
        return 'CoreStreamerBaseBundle:PaymentReport';
    }

    public function paymentReportingAction(Request $request)
    {
        /* @var $accessManager AccessManager */
        $accessManager = $this->get('access_manager');
        if (!$accessManager->checkAccess($this->__toString(), $accessManager::GRANT_LIST)) {
            return $this->redirectToRoute('admin.access_denied');
        }

        /* @var $totalReport PaymentReport */
        /* @var $report PaymentReport */
        $year = $request->query->get('year');
        $month = $request->query->get('month');

        if (isset($year) && isset($month)) { // Monthly reports
            return $this->forward('CoreStreamerAdminBundle:PaymentReporting:paymentReportingDaily', array(
                'year' => $year,
                'month' => $month
            ));
        } else if (isset($year)) { // Yearly reports
            return $this->forward('CoreStreamerAdminBundle:PaymentReporting:paymentReportingMonthly', array(
                'year' => $year
            ));
        } else { // Total reports
            return $this->forward('CoreStreamerAdminBundle:PaymentReporting:paymentReportingYearly');
        }
    }

    /**
     * @Template("CoreStreamerAdminBundle:Reporting:payments.html.twig")
     */
    public function paymentReportingDailyAction(Request $request, $year, $month)
    {
        /** @var Translator $trans */
        $trans = $this->get('translator');
        /* @var $provider PaymentReportProvider */
        $provider = $this->get('payment.reporting.provider');
        $totalChart = array();
        $timelyChart = array();

        $totalReport = $provider->getMonthlyReport($year, $month);
        $total = $totalReport ? $totalReport->getTotal() : 0;
        $totalChart[] = array($trans->trans('payment.plural'), '');
        $totalChart[] = array($trans->trans('payment.total'), $total);
        $compareChart = array(array());
        $compareReport = array();

        $timelyReports = $provider->getDailyReportsOfMonth($year, $month);
        $timelyChart[] = array($trans->trans('table.date'), $trans->trans('payment.total'));

        $dataTable = $provider->getDataTable();
        $dataTable->setQuery($timelyReports);

        $dataMapping = new DataMappingBuilder();
        $dataMapping->addField($trans->trans('table.date'), function ($row) {
            /** @var PaymentReport $row */
            return $row->getReportDatetime()->format('Y-m-d');
        })->addField($trans->trans('payment.plural'), 'total');

        $dataTable->setMapping($dataMapping->getMapping());

        foreach ($provider->getAvailableReportDays($year, $month) as $d) {
            $timelyChart[$d] = array("$d", 0);
        }

        /** @var PaymentReport $report */
        foreach ($timelyReports as $id => $report) {
            $filter = unserialize($report->getFilters());
            $d = intval($report->getReportDatetime()->format('d'));
            if (!empty($filter) && sizeof($filter) == 1 && array_key_exists('paymentType', $filter)) {
                if (!in_array($filter['paymentType'], $compareChart[0])) $compareChart[0][] = $filter['paymentType'];
                $compareReport["$d"][$filter['paymentType']] = $report->getTotal();
                unset($timelyReports[$id]);
            }

            if (empty($filter)) {
                $timelyChart[$d] = array("$d", $report->getTotal());
            }
        }
        foreach ($compareReport as $key => $val) {
            foreach ($compareChart[0] as $item) {
                if (!isset($compareReport["$key"]["$item"])) {
                    $compareReport["$key"]["$item"] = 0;
                }
            }
            ksort( $compareReport["$key"]);
        }
        array_multisort($compareChart[0]);
        $compareChart[0] = array_merge(array($trans->trans('table.year')), $compareChart[0]);

        foreach ($compareReport as $date => $type) {
            ksort($type);
            $data = array_merge(array("$date"), array_values($type));
            $compareChart[] = $data;
        }

        $timelyTitle = $trans->trans('table.day');

        $filterForm = $this->createForm(new ReportingFilterType(), array(
            'year' => $year,
            'month' => $month,
        ), array(
            'year' => $provider->getAvailableReportYears(),
            'month' => $provider->getAvailableReportMonths()
        ));
        $filterForm->handleRequest($request);

        if ($filterForm->isValid()) {
            return $this->redirectToRoute('admin.reporting.payments', array(
                'year' => $filterForm->get('year')->getData(),
                'month' => $filterForm->get('month')->getData()
            ));
        }

        return array(
            'form' => $filterForm->createView(),
            'title' => $trans->trans('payment.report'),
            'totalJson' => count($totalChart) ? json_encode($totalChart) : null,
            'timelyJson' => count($timelyChart) ? json_encode($timelyChart) : null,
            'compareJson' => count($compareChart) > 1 ? json_encode($compareChart) : null,
            'timelyTitle' => $timelyTitle,
            'dataTable' => $dataTable,
            'breadcrumb' => array(
                'reporting' => array(
                    'href' => $this->generateUrl("admin.reporting.payments"),
                    'title' => $trans->trans('payment.report')
                )
            ),
            'current_nav' => 'nav-reporting-payments',
            'reset_link' => $this->generateUrl("admin.reporting.payments")
        );
    }

    /**
     * @Template("CoreStreamerAdminBundle:Reporting:payments.html.twig")
     */
    public function paymentReportingMonthlyAction(Request $request, $year)
    {
        /** @var Translator $trans */
        $trans = $this->get('translator');
        /* @var $provider PaymentReportProvider */
        $provider = $this->get('payment.reporting.provider');
        $sf = $this;
        $totalChart = array();
        $timelyChart = array();
        $compareChart= array(array());
        $compareReport = array();

        $totalReport = $provider->getYearlyReport($year);
        $total = $totalReport ? intval($totalReport->getTotal()) : 0;
        $totalChart[] = array($trans->trans('payment.plural'), '');
        $totalChart[] = array($trans->trans('payment.total'), $total);

        $timelyReports = $provider->getMonthlyReportsOfYear($year);
        $timelyChart[] = array($trans->trans('table.month'), $trans->trans('payment.total'));
        foreach ($provider->getAvailableReportMonths() as $m) {
            $timelyChart[$m] = array("$m", 0);
        }
        /** @var PaymentReport $report */
        foreach ($timelyReports as $id => $report) {
            $filter = unserialize($report->getFilters());
            $m = intval($report->getReportDatetime()->format('m'));
            if (!empty($filter) && sizeof($filter) == 1 && array_key_exists('paymentType', $filter)) {
                if (!in_array($filter['paymentType'], $compareChart[0])) $compareChart[0][] = $filter['paymentType'];
                $compareReport["$m"][$filter['paymentType']] = $report->getTotal();
                unset($timelyReports[$id]);
            }

            if (empty($filter)) {
                $timelyChart[$m] = array("$m", intval($report->getTotal()));
            }
        }
        foreach ($compareReport as $key => $val) {
            foreach ($compareChart[0] as $item) {
                if (!isset($compareReport["$key"]["$item"])) {
                    $compareReport["$key"]["$item"] = 0;
                }
            }
            ksort( $compareReport["$key"]);
        }
        array_multisort($compareChart[0]);
        $compareChart[0] = array_merge(array($trans->trans('table.year')), $compareChart[0]);

        foreach ($compareReport as $month => $type) {
            ksort($type);
            $data = array_merge(array("$month"), array_values($type));
            $compareChart[] = $data;
        }

        $timelyTitle = $trans->trans('table.month');

        $dataTable = $provider->getDataTable();
        $dataTable->setQuery($timelyReports);

        $dataMapping = new DataMappingBuilder();
        $dataMapping->addField($trans->trans('table.month'), function ($row) use ($sf, $year) {
            /** @var PaymentReport $row */
            $month = $row->getReportDatetime()->format('m');
            return sprintf('<a href="%s">%s</a>', $sf->generateUrl('admin.reporting.payments', array(
                    'year' => intval($year),
                    'month' => intval($month)
                )
            ), $row->getReportDatetime()->format('Y-m'));
        })->addField($trans->trans('payment.plural'), 'total');

        $dataTable->setMapping($dataMapping->getMapping());

        $filterForm = $this->createForm(new ReportingFilterType(), array(
            'year' => $year,
            'month' => null,
        ), array(
            'year' => $provider->getAvailableReportYears(),
            'month' => $provider->getAvailableReportMonths()
        ));
        $filterForm->handleRequest($request);

        if ($filterForm->isValid()) {
            return $this->redirectToRoute('admin.reporting.payments', array(
                'year' => $filterForm->get('year')->getData(),
                'month' => $filterForm->get('month')->getData()
            ));
        }

        return array(
            'form' => $filterForm->createView(),
            'title' => $trans->trans('payment.report'),
            'totalJson' => count($totalChart) ? json_encode($totalChart) : null,
            'timelyJson' => count($timelyChart) ? json_encode($timelyChart) : null,
            'compareJson' => count($compareChart) > 1 ? json_encode($compareChart) : null,
            'timelyTitle' => $timelyTitle,
            'dataTable' => $dataTable,
            'breadcrumb' => array(
                'reporting' => array(
                    'href' => $this->generateUrl("admin.reporting.payments"),
                    'title' => $trans->trans('payment.report')
                )
            ),
            'current_nav' => 'nav-reporting-payments',
            'reset_link' => $this->generateUrl("admin.reporting.payments")
        );
    }

    /**
     * @Template("CoreStreamerAdminBundle:Reporting:payments.html.twig")
     */
    public function paymentReportingYearlyAction(Request $request)
    {
        /** @var Translator $trans */
        $trans = $this->get('translator');
        /* @var $provider PaymentReportProvider */
        $provider = $this->get('payment.reporting.provider');

        $sf = $this;

        $totalChart = array();
        $timelyChart = array();
        $compareChart = array(array());
        $compareReport = array();

        $totalReport = $provider->getTotalReport();
        $total = $totalReport ? intval($totalReport->getTotal()) : 0;
        $totalChart[] = array($trans->trans('payment.plural'), '');
        $totalChart[] = array($trans->trans('payment.total'), $total);

        $timelyReports = $provider->getYearlyReports();
        if (count($timelyReports)) $timelyChart[] = array($trans->trans('table.year'), $trans->trans('payment.total'));
        /** @var PaymentReport $report */
        foreach ($timelyReports as $id => $report) {
            $filter = unserialize($report->getFilters());
            $y = intval($report->getReportDatetime()->format('Y'));
            if (!empty($filter) && sizeof($filter) == 1 && array_key_exists('paymentType', $filter)) {
                if (!in_array($filter['paymentType'], $compareChart[0])) $compareChart[0][] = $filter['paymentType'];
                $compareReport["$y"][$filter['paymentType']] = $report->getTotal();
                unset($timelyReports[$id]);
            }

            if (empty($filter)) {
                $timelyChart[] = array("$y", intval($report->getTotal()));
            }
        }
        foreach ($compareReport as $key => $val) {
            foreach ($compareChart[0] as $item) {
                if (!isset($compareReport["$key"]["$item"])) {
                    $compareReport["$key"]["$item"] = 0;
                }
            }
            ksort( $compareReport["$key"]);
        }
        array_multisort($compareChart[0]);
        $compareChart[0] = array_merge(array($trans->trans('table.year')), $compareChart[0]);

        foreach ($compareReport as $year => $type) {
            ksort($type);
            $data = array_merge(array("$year"), array_values($type));
            $compareChart[] = $data;
        }
        
        $timelyTitle = $trans->trans('table.year');

        $dataTable = $provider->getDataTable();
        $dataTable->setQuery($timelyReports);

        $dataMapping = new DataMappingBuilder();
        $dataMapping->addField($trans->trans('table.year'), function ($row) use ($sf) {
            /** @var PaymentReport $row */
            $year = $row->getReportDatetime()->format('Y');
            return sprintf('<a href="%s">%s</a>', $sf->generateUrl('admin.reporting.payments', array('year' => $year)), $year);
        })->addField($trans->trans('payment.plural'), 'total');

        $dataTable->setMapping($dataMapping->getMapping());

        $filterForm = $this->createForm(new ReportingFilterType(), array(
            'year' => null,
            'month' => null,
        ), array(
            'year' => $provider->getAvailableReportYears(),
            'month' => $provider->getAvailableReportMonths()
        ));
        $filterForm->handleRequest($request);

        if ($filterForm->isValid()) {
            return $this->redirectToRoute('admin.reporting.payments', array(
                'year' => $filterForm->get('year')->getData(),
                'month' => $filterForm->get('month')->getData()
            ));
        }

        return array(
            'form' => $filterForm->createView(),
            'title' => $trans->trans('payment.report'),
            'totalJson' => count($totalChart) ? json_encode($totalChart) : null,
            'timelyJson' => count($timelyChart) ? json_encode($timelyChart) : null,
            'compareJson' => count($compareChart) > 1 ? json_encode($compareChart) : null,
            'timelyTitle' => $timelyTitle,
            'dataTable' => $dataTable,
            'breadcrumb' => array(
                'reporting' => array(
                    'href' => $this->generateUrl("admin.reporting.payments"),
                    'title' => $trans->trans('payment.report')
                )
            ),
            'current_nav' => 'nav-reporting-payments',
            'reset_link' => $this->generateUrl("admin.reporting.payments")
        );
    }

}