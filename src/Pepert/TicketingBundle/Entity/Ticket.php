<?php

namespace Pepert\TicketingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ticket
 *
 * @ORM\Table(name="ticket")
 * @ORM\Entity(repositoryClass="Pepert\TicketingBundle\Repository\TicketRepository")
 */
class Ticket
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255)
     */
    private $country;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="visit_day", type="date")
     */
    private $visitDay;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date")
     */
    private $birthday;

    /**
     * @var bool
     *
     * @ORM\Column(name="tarif_reduit", type="boolean")
     */
    private $tarifReduit;

    /**
     * @var string
     *
     * @ORM\Column(name="tarif_name", type="string", length=255)
     */
    private $tarifName;

    /**
     * @var string
     *
     * @ORM\Column(name="ticket_type", type="string", length=255)
     */
    private $ticketType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="paiement_day", type="date")
     */
    private $paiementDay;

    /**
     * @ORM\ManyToOne(targetEntity = "Pepert\TicketingBundle\Entity\User",inversedBy="tickets")
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
     * Set name
     *
     * @param string $name
     *
     * @return Ticket
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return Ticket
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Ticket
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set visitDay
     *
     * @param \DateTime $visitDay
     *
     * @return Ticket
     */
    public function setVisitDay($visitDay)
    {
        $this->visitDay = $visitDay;

        return $this;
    }

    /**
     * Get visitDay
     *
     * @return \DateTime
     */
    public function getVisitDay()
    {
        return $this->visitDay;
    }

    /**
     * Set ticketType
     *
     * @param string $ticketType
     *
     * @return Ticket
     */
    public function setTicketType($ticketType)
    {
        $this->ticketType = $ticketType;

        return $this;
    }

    /**
     * Get ticketType
     *
     * @return string
     */
    public function getTicketType()
    {
        return $this->ticketType;
    }

    /**
     * Set user
     *
     * @param \Pepert\TicketingBundle\Entity\User $user
     *
     * @return Ticket
     */
    public function setUser(\Pepert\TicketingBundle\Entity\User $user = null)
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
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return Ticket
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set tarifReduit
     *
     * @param boolean $tarifReduit
     *
     * @return Ticket
     */
    public function setTarifReduit($tarifReduit)
    {
        $this->tarifReduit = $tarifReduit;

        return $this;
    }

    /**
     * Get tarifReduit
     *
     * @return boolean
     */
    public function getTarifReduit()
    {
        return $this->tarifReduit;
    }

    /**
     * Set tarifName
     *
     * @param string $tarifName
     *
     * @return Ticket
     */
    public function setTarifName($tarifName)
    {
        $this->tarifName = $tarifName;

        return $this;
    }

    /**
     * Get tarifName
     *
     * @return string
     */
    public function getTarifName()
    {
        return $this->tarifName;
    }

    /**
     * Set paiementDay
     *
     * @param \DateTime $paiementDay
     *
     * @return Ticket
     */
    public function setPaiementDay($paiementDay)
    {
        $this->paiementDay = $paiementDay;

        return $this;
    }

    /**
     * Get paiementDay
     *
     * @return \DateTime
     */
    public function getPaiementDay()
    {
        return $this->paiementDay;
    }
}
