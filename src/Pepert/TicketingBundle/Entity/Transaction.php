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
     * @var string
     *
     * @ORM\Column(name="transaction_infos", type="text")
     */
    private $transactionInfos;

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
     * Set transactionInfos
     *
     * @param string $transactionInfos
     *
     * @return Transaction
     */
    public function setTransactionInfos($transactionInfos)
    {
        $this->transactionInfos = $transactionInfos;

        return $this;
    }

    /**
     * Get transactionInfos
     *
     * @return string
     */
    public function getTransactionInfos()
    {
        return $this->transactionInfos;
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
}
