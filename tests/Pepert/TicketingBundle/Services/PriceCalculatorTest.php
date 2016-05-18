<?php

namespace Pepert\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Pepert\TicketingBundle\Entity\Ticket;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PriceCalculatorTest extends WebTestCase
{
    public function testGoodPriceCalculation()
    {
        $ticket1 = new Ticket();
        $ticket1->setName('Peronnet');
        $ticket1->setVisitDay(new \DateTime('2016-05-18'));
        $ticket1->setBirthday(new \DateTime('1985-04-13'));
        $ticket1->setTicketType('Journée');

        $ticket2 = new Ticket();
        $ticket2->setName('Peronnet');
        $ticket2->setVisitDay(new \DateTime('2016-05-18'));
        $ticket2->setBirthday(new \DateTime('1984-04-13'));
        $ticket2->setTicketType('Journée');

        $ticket3 = new Ticket();
        $ticket3->setName('Peronnet');
        $ticket3->setVisitDay(new \DateTime('2016-05-18'));
        $ticket3->setBirthday(new \DateTime('2010-04-13'));
        $ticket3->setTicketType('Journée');

        $ticket4 = new Ticket();
        $ticket4->setName('Abdouni');
        $ticket4->setVisitDay(new \DateTime('2016-05-18'));
        $ticket4->setBirthday(new \DateTime('1987-04-13'));
        $ticket4->setTicketType('Journée');

        $ticket5 = new Ticket();
        $ticket5->setName('Peronnet');
        $ticket5->setVisitDay(new \DateTime('2016-05-18'));
        $ticket5->setBirthday(new \DateTime('2009-04-13'));
        $ticket5->setTicketType('Journée');

        $tickets = new ArrayCollection();
        $tickets->add($ticket1);
        $tickets->add($ticket2);
        $tickets->add($ticket3);
        $tickets->add($ticket4);
        $tickets->add($ticket5);

        $client = static::createClient();

        $container = $client->getContainer();

        $service = $container->get('pepert_ticketing.price_calculator');

        $tickets = $service->tarif($tickets,5);

        $this->assertEquals('normal', $ticket4->getTarifName());
        $this->assertEquals('famille', $ticket5->getTarifName());

        $tarifFinal = $service->calculerPrixTotal($tickets,'Journée');

        $this->assertEquals(51, $tarifFinal);
    }
}