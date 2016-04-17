<?php

namespace Pepert\TicketingBundle\PriceCalculator;

use Doctrine\Common\Collections\ArrayCollection;
use Pepert\TicketingBundle\Entity\Ticket;

class PepertPriceCalculator
{
    public function tarif($tickets,$nbTickets)
    {
        $ticketsSorted = $tickets->getValues();

        usort($ticketsSorted, array($this, "cmp"));

        $tarifFamilleAdulteDispo = 2;
        $tarifFamilleEnfantDispo = 2;
        $nombreHomonymesDispo = 4;

        for($i = 0; $i < $nbTickets; $i ++)
        {
            $name = $ticketsSorted[$i]->getName();
            $age = $ticketsSorted[$i]->getVisitDay()->diff($ticketsSorted[$i]->getBirthday())->y;

            $nombreHomonymes = $this->occurrencesDepuisPosition($ticketsSorted,$name,$nbTickets,$i);

            if(($nombreHomonymes - $nombreHomonymesDispo) >= 0 && (($age >= 12 && $tarifFamilleAdulteDispo > 0)
                    || ($age < 12 && $tarifFamilleEnfantDispo > 0)))
            {
                $ticketsSorted[$i]->setTarifName('famille');
                $ticketsSorted[$i]->setPrice(0);
                if($age < 12)
                {
                    $tarifFamilleEnfantDispo --;
                    $nombreHomonymesDispo --;
                }
                else
                {
                    $tarifFamilleAdulteDispo --;
                    $nombreHomonymesDispo --;
                }

                if($nombreHomonymesDispo == 0)
                {
                    $nombreHomonymesDispo = 4;
                    $tarifFamilleAdulteDispo = 2;
                    $tarifFamilleEnfantDispo = 2;
                    if($ticketsSorted[$i]->getTicketType() !== "Journée")
                    {
                        $ticketsSorted[$i]->setPrice(17.5);
                    }
                    else
                    {
                        $ticketsSorted[$i]->setPrice(35);
                    }
                }
            }
            else
            {
                $this->definirTarifName($ticketsSorted[$i]);
            }
        }

        return new ArrayCollection($ticketsSorted);
    }

    public function definirTarifName(Ticket $ticket)
    {
        $age = $ticket->getVisitDay()->diff($ticket->getBirthday())->y;
        $tarifReduit = $ticket->getTarifReduit();

        if($tarifReduit)
        {
            $tarifName = 'reduit';
            $price = 10;
        }
        else
        {
            if($age < 4)
            {
                $tarifName = 'gratuit';
                $price = 0;
            }
            else if($age < 12)
            {
                $tarifName = 'enfant';
                $price = 8;
            }
            else if($age >= 60)
            {
                $tarifName = 'senior';
                $price = 12;
            }
            else
            {
                $tarifName = 'normal';
                $price = 16;
            }
        }

        if($ticket->getTicketType() !== "Journée")
        {
            $price = $price/2;
        }

        $ticket->setTarifName($tarifName);
        $ticket->setPrice($price);
    }

    public function calculerPrixTotal($tickets,$type)
    {
        $price = 0;
        $compteurTarifFamille = 4;

        foreach($tickets as $ticket)
        {
            $tarifName = $ticket->getTarifName();

            if($tarifName == 'enfant')
            {
                $price += 8;
            }
            else if($tarifName == 'normal')
            {
                $price += 16;
            }
            else if($tarifName == 'senior')
            {
                $price += 12;
            }
            else if($tarifName == 'reduit')
            {
                $price += 10;
            }
            else if($tarifName == 'famille')
            {
                if($compteurTarifFamille > 1)
                {
                    $compteurTarifFamille --;
                    $price += 0;
                }
                else
                {
                    $compteurTarifFamille = 4;
                    $price += 35;
                }
            }
            else
            {
                $price += 0;
            }
        }

        if($type === "Demi-journée")
        {
            $price = $price/2;
        }

        return $price;
    }

    private function cmp($a, $b)
    {
        return strcmp($a->getName(), $b->getName());
    }

    private function occurrencesDepuisPosition($tickets, $name, $nbTickets, $i)
    {
        $occurrences = 0;
        $enfantDispo = 2;
        $adulteDispo = 2;

        for($a = $i; $a < $nbTickets; $a ++)
        {
            if($tickets[$a]->getName() == $name)
            {
                $age = $tickets[$a]->getVisitDay()->diff($tickets[$a]->getBirthday())->y;
                if($age < 12 && $enfantDispo > 0)
                {
                    $occurrences ++;
                    $enfantDispo --;
                }
                else if($age >= 12 && $adulteDispo > 0)
                {
                    $occurrences ++;
                    $adulteDispo --;
                }
            }
        }

        return $occurrences;
    }
}