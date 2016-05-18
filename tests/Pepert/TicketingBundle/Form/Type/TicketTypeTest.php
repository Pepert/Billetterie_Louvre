<?php

namespace Pepert\Tests;

use Pepert\TicketingBundle\Entity\Ticket;
use Pepert\TicketingBundle\Form\Type\TicketType;
use Symfony\Component\Form\Test\TypeTestCase;

class TicketTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = array(
            'name' => 'Peronnet',
            'firstname' => 'Léonard',
            'birthday' => array('year' => 1985, 'month' => 04, 'day' => 13),
            'country' => 'FR',
            'tarif_reduit' => false,
        );

        $form = $this->factory->create(TicketType::class);

        // submit the data to the form directly
        $form->submit($formData);

        $ticket = new Ticket();
        $ticket->setName('Peronnet');
        $ticket->setFirstname('Léonard');
        $ticket->setBirthday(new \DateTime('1985-04-13'));
        $ticket->setCountry('FR');
        $ticket->setTarifReduit(false);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($ticket, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}