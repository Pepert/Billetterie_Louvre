<?php

namespace Pepert\TicketingBundle\PriceCalculator;

use Doctrine\Common\Collections\ArrayCollection;
use Pepert\TicketingBundle\Entity\Ticket;

class PepertPriceCalculator
{
    public function tarifFamille($tickets,$nbTickets)
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

            $nombreHomonymes = $this->occurrences($ticketsSorted,$name,$nbTickets,$i);

            if(($nombreHomonymes - $nombreHomonymesDispo) >= 0 && (($age >= 12 && $tarifFamilleAdulteDispo > 0)
                    || ($age < 12 && $tarifFamilleEnfantDispo > 0)))
            {
                $ticketsSorted[$i]->setTarifName('famille');
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
        }
        else
        {
            if($age < 4)
            {
                $tarifName = 'gratuit';
            }
            else if($age < 12)
            {
                $tarifName = 'enfant';
            }
            else if($age >= 60)
            {
                $tarifName = 'senior';
            }
            else
            {
                $tarifName = 'normal';
            }
        }

        $ticket->setTarifName($tarifName);
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

    private function occurrences($tickets, $name, $nbTickets, $i)
    {
        $occurrences = 0;

        for($a = $i; $a < $nbTickets; $a ++)
        {
            if($tickets[$a]->getName() == $name)
            {
                $occurrences ++;
            }
        }

        return $occurrences;
    }
}