<?php

namespace Pepert\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StripePaymentTest extends WebTestCase
{
    public function testSetStripeApi()
    {
        $client = static::createClient();

        $container = $client->getContainer();

        $service = $container->get('pepert_ticketing.stripe');

        $result = $service->setStripeApi();

        //On vérifie que le service retourne bien la clé test, c'est que tout fonctionne
        $this->assertContains('pk_test_yMYkdAAnYrIbPtrkCvrTM9mX', $result);
    }
}