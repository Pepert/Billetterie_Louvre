<?php

namespace Pepert\TicketingBundle\PriceCalculator;

use Pepert\TicketingBundle\Entity\Ticket;

class PepertPriceCalculator
{
    public function calculerTarif(Ticket $ticket)
    {
        $age = $ticket->getVisitDay()->diff($ticket->getBirthday())->y;
        $tarifReduit = $ticket->getTarifReduit();
        $type = $ticket->getTicketType();

        if($tarifReduit)
        {
            $price = 10;
        }
        else
        {
            if($age < 4)
            {
                $price = 0;
            }
            else if($age < 12)
            {
                $price = 8;
            }
            else if($age >= 60)
            {
                $price = 12;
            }
            else
            {
                $price = 16;
            }
        }

        if($type === "Demi-journée")
        {
            $price = $price/2;
        }

        $ticket->setPrice($price);
    }
}