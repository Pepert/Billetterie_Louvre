<?php

namespace Pepert\Tests;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GenerateApiPaypalTest extends WebTestCase
{
    private $user;
    private $transaction;

    public function setUp()
    {
        $this->user = $this->getMock(User::class, array('getId'));
        $this->user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));

        $this->transaction = $this->getMock(Transaction::class, array('getId','getTotalPrice','getTicketNumber'));
        $this->transaction->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(3));
        $this->transaction->expects($this->any())
            ->method('getTotalPrice')
            ->will($this->returnValue(24));
        $this->transaction->expects($this->any())
            ->method('getTicketNumber')
            ->will($this->returnValue(2));
    }

    public function testSetCheckoutApi()
    {
        $client = static::createClient();

        $container = $client->getContainer();

        $service = $container->get('pepert_ticketing.paypal_api');

        $result = $service->setCheckoutApi($this->transaction, $this->user);

        //On vérifie que le service retourne bien le chemin vers paypal, c'est que tout fonctionne
        $this->assertContains('.paypal.com/cgi-bin/', $result);
    }
}