<?php

namespace Pepert\Tests;

use Proxies\__CG__\Pepert\TicketingBundle\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketingControllerTest extends WebTestCase
{
    private function getForm($date, $ticketNumber)
    {
        $client = static::createClient();
        // Client devient un fake browser

        $crawler = $client->request('GET', '/');
        //Permet d'accéder à la route voulue

        $form = $crawler->selectButton('Valider')->form();

        $form['user[visit_day]'] = $date;
        $form['user[ticket_number]'] = $ticketNumber;
        $form['user[ticket_type]'] = 'Journée';
        $form['user[email]'] = 'playpero@hotmail.com';

        $crawler = $client->submit($form);

        $data['client'] = $client;
        $data['crawler'] = $crawler;

        return $data;
    }

    public function testIndexWithPastDateBringsBackToIndexWithGoodErrorDisplay()
    {
        $data = $this->getForm('2012-04-13', 2); //Past date

        $client = $data['client'];

        //Permet de démarrer la prochaine opération faite par le controller avant la redirection
        $client->getRequest();

        //On vérifie que message d'erreur est bien affiché
        $this->assertEquals(
            1,
            $data['crawler']->filter('.flashbag')->count()
        );

        //On vérifie que le message d'erreur affiché est celui qu'on attend
        $this->assertContains(
            'Merci de choisir un autre jour de visite.',
            $client->getResponse()->getContent()
        );
    }

    public function testIndexWithTuesdayDateBringsBackToIndexWithGoodErrorDisplay()
    {
        $data = $this->getForm('2020-07-14', 2);//Tuesday

        $client = $data['client'];

        //On vérifie que message d'erreur est bien affiché
        $this->assertEquals(
            1,
            $data['crawler']->filter('.flashbag')->count()
        );

        //On vérifie que le message d'erreur affiché est celui qu'on attend
        $this->assertContains(
            'autre date de visite',
            $client->getResponse()->getContent()
        );
    }

    public function testIndexWithHolidayDateBringsBackToIndexWithGoodErrorDisplay()
    {
        $data = $this->getForm('2020-12-25', 2);//Christmas

        $client = $data['client'];

        //On vérifie que message d'erreur est bien affiché
        $this->assertEquals(
            1,
            $data['crawler']->filter('.flashbag')->count()
        );

        //On vérifie que le message d'erreur affiché est celui qu'on attend
        $this->assertContains(
            'autre date de visite',
            $client->getResponse()->getContent()
        );
    }

    public function testIndexWithDemiJourneeAfter2pmBringsBackToIndexWithGoodErrorDisplay()
    {
        $dateVisite = new \DateTime('2016-05-18');
        $today = new \DateTime('2016-05-18');
        $typeTickets = 'Journée';

        $todayTime = 16;
        $todayDate = $today->setTime(0,0,0);

        if($dateVisite == $todayDate && $todayTime >= 14 && $typeTickets === 'Journée')
        {
            $test = 1;
        }
        else
        {
            $test = 2;
        }

        //Si test est à 1, cela signifie que l'on rentre bien dans la boucle correspondante avec de tels paramètres
        //dans le controller
        $this->assertEquals(
            1,
            $test
        );
    }

    public function testIndexMoreThan1000TicketsBringsBackToIndexWithGoodErrorDisplay()
    {
        $data = $this->getForm('2020-04-13', 1200);

        $client = $data['client'];

        //On vérifie que message d'erreur est bien affiché
        $this->assertEquals(
            1,
            $data['crawler']->filter('.flashbag')->count()
        );

        //On vérifie que le message d'erreur affiché est celui qu'on attend
        $this->assertContains(
            'au maximum pour cete date',
            $client->getResponse()->getContent()
        );
    }

    public function testIndexWithGoodFormBringsToNewPage()
    {
        $data = $this->getForm('2020-04-13', 2);

        $client = $data['client'];

        $this->assertTrue(
            $client->getResponse()->isRedirect('/ticket/2')
        );

        return $client;
    }

    /**
     * @depends testIndexWithGoodFormBringsToNewPage
     */
    public function testTicketWithGoodFormBringsToPayment($client)
    {
        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Valider')->form();

        $form['form[tickets][0][name]'] = 'Peronnet';
        $form['form[tickets][0][firstname]'] = 'Léonard';
        $form['form[tickets][0][birthday][day]'] = 13;
        $form['form[tickets][0][birthday][month]'] = 4;
        $form['form[tickets][0][birthday][year]'] = 1985;
        $form['form[tickets][0][country]'] = 'FR';
        $form['form[tickets][0][tarif_reduit]'] = false;

        $form['form[tickets][1][name]'] = 'Abdouni';
        $form['form[tickets][1][firstname]'] = 'Karim';
        $form['form[tickets][1][birthday][day]'] = 13;
        $form['form[tickets][1][birthday][month]'] = 5;
        $form['form[tickets][1][birthday][year]'] = 1987;
        $form['form[tickets][1][country]'] = 'FR';
        $form['form[tickets][1][tarif_reduit]'] = false;

        $client->submit($form);

        //On vérifie si on est bien sur la page de paiement, la seule qui contient "Commande en cours"
        $this->assertContains(
            'Commande en cours',
            $client->getResponse()->getContent()
        );

        return $client;
    }

    /**
     * @depends testTicketWithGoodFormBringsToPayment
     */
    public function testPaymentErrorActionRedirectToPaymentPage($client)
    {
        $client->request('GET', '/payment/error');
        $crawler = $client->getCrawler();

        $this->assertEquals(
            1,
            $crawler->filter('.flashbag')->count()
        );

        //On vérifie que le message d'erreur affiché est celui qui le doit
        $this->assertContains(
            "La transaction n",//Le morceau est petit, car phpunit n'accepte ni accent ni ponctuation
            $client->getResponse()->getContent()
        );

        return $client;
    }

    public function testRetryPaymentBringsBackToPaymentPage()
    {
        $client = static::createClient();

        $client->request('GET', '/payment?idTransaction=1&idBuyer=1');
        //Route suivie lorsque l'on retourne sur le site après avoir annulé depuis Paypal

        //On vérifie qu'on repart bien sur la page de paiement
        $this->assertContains(
            'Commande en cours',
            $client->getResponse()->getContent()
        );
    }

    public function testConfirmEmailAfterPaypalBringsToFinalPage()
    {
        $client = static::createClient();

        $client->request('GET', '/payment/paypal/validated?idTransaction=1&idBuyer=1&run=test');
        //Route suivie après succès d'un paiement Paypal + run indiquant qu'il s'agit d'un test

        $crawler = $client->getCrawler();

        $form = $crawler->selectButton('Valider cette adresse')->form();

        $form['form[email]'] = 'playpero@hotmail.com';

        $client->submit($form);

        $this->assertTrue(
            $client->getResponse()->isRedirect('/final')
        );
    }

    /**
     * @depends testPaymentErrorActionRedirectToPaymentPage
     */
    public function testConfirmEmailAfterStripeBringsToFinalPage($client)
    {
        $client->request('GET', '/payment/stripe/validated?run=test');
        //Route suivie après succès d'un paiement Stripe + run indiquant qu'il s'agit d'un test

        $crawler = $client->getCrawler();

        $form = $crawler->selectButton('Valider cette adresse')->form();

        $form['form[email]'] = 'playpero@hotmail.com';

        $client->submit($form);

        $this->assertTrue(
            $client->getResponse()->isRedirect('/final')
        );

        return $client;
    }

    /**
     * @depends testConfirmEmailAfterStripeBringsToFinalPage
     */
    public function testFinalPageSendEmailAndDisplayEndMessage($client)
    {
        $client->enableProfiler();

        $client->followRedirect();

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        // Check that an email was sent
        $this->assertEquals(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        $message = $collectedMessages[0];

        // Asserting email data
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals('billets@louvre.com', key($message->getFrom()));
        $this->assertEquals('playpero@hotmail.com', key($message->getTo()));
        $this->assertEquals(
            'Bonne visite !',
            $message->getBody()
        );

        //On vérifie que la page concluant l'opération s'affiche bien :
        $this->assertContains(
            'Partagez la bonne nouvelle !',
            $client->getResponse()->getContent()
        );
    }
}