<?php
namespace Entity;

use ReportingBundle\Entity\AbstractReport;
use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentReport
 *
 * @ORM\Table(name="reporting_payment")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class PaymentReport extends AbstractReport
{
    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $total;

    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return string
     */
    public function getTotal()
    {
        return $this->total;
    }
}