<?php
namespace ReportingBundle\Entity;


interface ReportInterface
{
    public function getId();
    public function setId($id);
    public function getReportDateTime();
    public function setReportDateTime($datetime);
    public function getType();
    public function setType($type);
    public function getFilters();
    public function setFilters($filters);
}