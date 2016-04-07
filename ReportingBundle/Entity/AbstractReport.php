<?php
namespace ReportingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

abstract class AbstractReport implements ReportInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $reportDatetime;

    /**
     * @ORM\Column(type="string")
     */
    protected $type=TimePeriodReporting::DAILY;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $filters=NULL;

    /**
     * @param mixed $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \DateTime $datetime
     */
    public function setReportDatetime($datetime)
    {
        $this->reportDatetime = $datetime;
    }

    /**
     * @return \DateTime
     */
    public function getReportDatetime()
    {
        return $this->reportDatetime;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}