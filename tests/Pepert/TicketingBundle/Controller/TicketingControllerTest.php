<?php

namespace Pepert\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TicketingControllerTest extends WebTestCase
{
    public function testIndexWithGoodFormBringsToNewPage()
    {
        $data = $this->getForm('2020-04-13', 2);

        $client = $data['client'];

        $client->getRequest();

        $this->assertTrue(
            $client->getResponse()->isRedirect('/ticket/2')
        );

        return $client;
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

        //Permet de démarrer la prochaine opération faite par le controller avant la redirection
        $client->getRequest();

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

        //Permet de démarrer la prochaine opération faite par le controller avant la redirection
        $client->getRequest();

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
        $data = $this->getForm('2018-05-16', 2);

        $client = $data['client'];

        //Permet de démarrer la prochaine opération faite par le controller avant la redirection
        $client->getRequest();

        /* A vérifier avec Karim
        //On vérifie que message d'erreur est bien affiché
        $this->assertEquals(
            1,
            $data['crawler']->filter('.flashbag')->count()
        );

        $this->assertContains(
            'avant 14 heures pour le jour en cours',
            $client->getResponse()->getContent()
        );
        */
    }

    public function testIndexMoreThan1000TicketsBringsBackToIndexWithGoodErrorDisplay()
    {
        $data = $this->getForm('2020-04-13', 1200);

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
            'plus de ticket disponible ce jour',
            $client->getResponse()->getContent()
        );
    }

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

    /**
     * @depends testIndexWithGoodFormBringsToNewPage
     */
    public function testTicketWithGoodFormBringsToPayment($client)
    {
        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Valider')->form();

        $form['form[tickets][0][name]'] = 'Peronnet';
        $form['form[tickets][0][firstname]'] = 'Léonard';
        $form['form[tickets][0][birthday][day]'] = '13';
        $form['form[tickets][0][birthday][month]'] = '04';
        $form['form[tickets][0][birthday][year]'] = '1985';
        $form['form[tickets][0][country]'] = 'FR';

        $form['form[tickets][1][name]'] = 'Abdouni';
        $form['form[tickets][1][firstname]'] = 'Karim';
        $form['form[tickets][1][birthday][day]'] = '13';
        $form['form[tickets][1][birthday][month]'] = '05';
        $form['form[tickets][1][birthday][year]'] = '1987';
        $form['form[tickets][1][country]'] = 'FR';

        $client->submit($form);

        $client->getRequest();

        /*
        $this->assertContains(
            'Commande en cours',
            $client->getResponse()->getContent()
        );
        */
    }

    /*
    private function getFormTicket($client)
    {
        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Valider')->form();

        $form['form[tickets][0][name]'] = 'Peronnet';
        $form['form[tickets][0][firstname]'] = 'Léonard';
        $form['form[tickets][0][birthday][day]'] = '13';
        $form['form[tickets][0][birthday][month]'] = '04';
        $form['form[tickets][0][birthday][year]'] = '1985';
        $form['form[tickets][0][country]'] = 'FR';

        $form['form[tickets][1][name]'] = 'Abdouni';
        $form['form[tickets][1][firstname]'] = 'Karim';
        $form['form[tickets][1][birthday][day]'] = '13';
        $form['form[tickets][1][birthday][month]'] = '05';
        $form['form[tickets][1][birthday][year]'] = '1987';
        $form['form[tickets][1][country]'] = 'FR';

        $crawler = $client->submit($form);

        $data['client'] = $client;
        $data['crawler'] = $crawler;

        return $data;
    }
    */
}