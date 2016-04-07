<?php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payment
 *
 * @ORM\Table(name="payments")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class Payment
{
    const OPEN = 'New';
    const FINISHED = 'Finished';
    const PROGRESSING = 'Progressing';
    const INVALID = "Invalid";

    const ISSUER_MOBI = 'MOBI';
    const ISSUER_VINA = 'VINA';
    const ISSUER_VT = 'VT';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Device
     * @ORM\ManyToOne(targetEntity="Device")
     * @ORM\JoinColumn(name="device_id", referencedColumnName="id")
     */
    protected $device;

    /**
     * @ORM\Column(type="float", scale=2, nullable=true)
     */
    protected $amount;

    /**
     * @ORM\Column(type="string")
     */
    protected $status = self::OPEN;

    /**
     * @ORM\Column(type="string")
     */
    protected $paymentType;

    /**
     * @ORM\Column(type="string")
     */
    protected $paymentCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $issuer;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $cardSerial;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     */
    protected $transactionReference;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updatedAt;

    public function __toString()
    {
        return sprintf("%s_%d", $this->getDevice()->getDeviceId(), $this->getId());
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        if ($this->createdAt == null) $this->createdAt = new \DateTime();
        else $this->updatedAt = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set paymentCode
     *
     * @param string $paymentCode
     * @return Singer
     */
    public function setPaymentCode($paymentCode)
    {
        $this->paymentCode = $paymentCode;

        return $this;
    }

    /**
     * Get paymentCode
     *
     * @return string
     */
    public function getPaymentCode()
    {
        return $this->paymentCode;
    }

    /**
     * Set paymentType
     *
     * @param string $paymentType
     * @return Singer
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    /**
     * Get paymentType
     *
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * Set amount
     *
     * @param float $amount
     * @return Payment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Payment
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set device
     *
     * @param \CoreStreamer\Bundle\BaseBundle\Entity\Device $device
     * @return Payment
     */
    public function setDevice(\CoreStreamer\Bundle\BaseBundle\Entity\Device $device = null)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * Get device
     *
     * @return \CoreStreamer\Bundle\BaseBundle\Entity\Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Payment
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Payment
     */
    public function setStatus($status)
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getJson()
    {
        return array(
            'id' => $this->id,
            'device' => $this->device->getId(),
            'amount' => floatval($this->amount),
            'status' => $this->status,
            'paymentType' => $this->paymentType,
            'paymentCode' => $this->paymentCode,
            'transactionReference' => $this->transactionReference
        );
    }

    /**
     * @param mixed $cardSerial
     */
    public function setCardSerial($cardSerial)
    {
        $this->cardSerial = $cardSerial;
    }

    /**
     * @return mixed
     */
    public function getCardSerial()
    {
        return $this->cardSerial;
    }

    /**
     * @param mixed $issuer
     */
    public function setIssuer($issuer)
    {
        $this->issuer = $issuer;
    }

    /**
     * @return mixed
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * @param mixed $transactionReference
     */
    public function setTransactionReference($transactionReference)
    {
        $this->transactionReference = $transactionReference;
    }

    /**
     * @return mixed
     */
    public function getTransactionReference()
    {
        return $this->transactionReference;
    }
}