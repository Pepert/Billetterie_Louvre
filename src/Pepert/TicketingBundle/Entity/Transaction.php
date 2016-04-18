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
     * @ORM\Column(name="transaction_id", type="string", length=255)
     */
    private $transactionId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="transaction_date", type="date", nullable=true)
     */
    private $transactionDate;

    /**
     * @var float
     *
     * @ORM\Column(name="total_price", type="float")
     */
    private $totalPrice;

    /**
     * @var int
     *
     * @ORM\Column(name="ticket_number", type="integer")
     */
    private $ticketNumber;

    /**
     * @ORM\ManyToOne(targetEntity = "Pepert\TicketingBundle\Entity\User",inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity = "Pepert\TicketingBundle\Entity\Ticket", mappedBy="transaction", cascade={"persist"})
     */
    private $tickets;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tickets = new \Doctrine\Common\Collections\ArrayCollection();
        $this->transactionId = 'Pas de transaction effectuée';
        $this->totalPrice = 0;
    }

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
     * Set transactionId
     *
     * @param string $transactionId
     *
     * @return Transaction
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
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
     * Set totalPrice
     *
     * @param float $totalPrice
     *
     * @return Transaction
     */
    public function setTotalPrice($totalPrice)
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    /**
     * Get totalPrice
     *
     * @return float
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
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
     * Add ticket
     *
     * @param \Pepert\TicketingBundle\Entity\Ticket $ticket
     *
     * @return Transaction
     */
    public function addTicket(\Pepert\TicketingBundle\Entity\Ticket $ticket)
    {
        $this->tickets[] = $ticket;

        $ticket->setTransaction($this);

        return $this;
    }

    /**
     * Remove ticket
     *
     * @param \Pepert\TicketingBundle\Entity\Ticket $ticket
     */
    public function removeTicket(\Pepert\TicketingBundle\Entity\Ticket $ticket)
    {
        $this->tickets->removeElement($ticket);
    }

    public function removeAllTickets()
    {
        $this->tickets = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get tickets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    public function setTickets($tickets)
    {
        $this->tickets = $tickets;
    }

    /**
     * Set ticketNumber
     *
     * @param integer $ticketNumber
     *
     * @return Transaction
     */
    public function setTicketNumber($ticketNumber)
    {
        $this->ticketNumber = $ticketNumber;

        return $this;
    }

    /**
     * Get ticketNumber
     *
     * @return integer
     */
    public function getTicketNumber()
    {
        return $this->ticketNumber;
    }
}
