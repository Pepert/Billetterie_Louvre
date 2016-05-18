<?php

namespace Pepert\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pepert\TicketingBundle\Entity\Ticket;
use Doctrine\Common\Collections\ArrayCollection;

class GeneratePdfTest extends WebTestCase
{
    public function testSetStripeApi()
    {
        $ticket1 = new Ticket();
        $ticket1->setFirstname('Leonard');
        $ticket1->setName('Peronnet');
        $ticket1->setVisitDay(new \DateTime('2016-05-18'));
        $ticket1->setTicketType('Journée');
        $ticket1->setTarifName('normal');
        $ticket1->setPrice(16);
        $ticket1->setReservationCode('test1');

        $ticket2 = new Ticket();
        $ticket2->setFirstname('Karim');
        $ticket2->setName('Abdouni');
        $ticket2->setVisitDay(new \DateTime('2016-05-18'));
        $ticket2->setTicketType('Journée');
        $ticket2->setTarifName('normal');
        $ticket2->setPrice(16);
        $ticket2->setReservationCode('test2');

        $tickets = new ArrayCollection();
        $tickets->add($ticket1);
        $tickets->add($ticket2);

        $client = static::createClient();

        $container = $client->getContainer();

        $service = $container->get('pepert_ticketing.generate_pdf');

        $content = $service->generateHtmlToPdf($tickets);

        //On vérifie que le service retourne bien la clé test, c'est que tout fonctionne
        $this->assertContains('Date de la visite : 18-05-2016', $content);
        $this->assertContains('Leonard PERONNET', $content);
        $this->assertContains('Karim ABDOUNI', $content);
    }
}