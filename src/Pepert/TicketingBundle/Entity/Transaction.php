<?php

namespace Pepert\TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transaction
 *
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="Pepert\TicketingBundle\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var object
     *
     * @ORM\Column(name="transaction_object", type="object")
     */
    private $transactionObject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="transaction_date", type="date")
     */
    private $transactionDate;

    /**
     * @ORM\ManyToOne(targetEntity = "Pepert\TicketingBundle\Entity\User",inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transactionDate
     *
     * @param \DateTime $transactionDate
     *
     * @return Transaction
     */
    public function setTransactionDate($transactionDate)
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    /**
     * Get transactionDate
     *
     * @return \DateTime
     */
    public function getTransactionDate()
    {
        return $this->transactionDate;
    }

    /**
     * Set user
     *
     * @param \Pepert\TicketingBundle\Entity\User $user
     *
     * @return Transaction
     */
    public function setUser(\Pepert\TicketingBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Pepert\TicketingBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set transactionObject
     *
     * @param \stdClass $transactionObject
     *
     * @return Transaction
     */
    public function setTransactionObject($transactionObject)
    {
        $this->transactionObject = $transactionObject;

        return $this;
    }

    /**
     * Get transactionObject
     *
     * @return \stdClass
     */
    public function getTransactionObject()
    {
        return $this->transactionObject;
    }
}
