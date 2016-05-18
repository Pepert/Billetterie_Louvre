<?php

namespace Pepert\Tests;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Form\Type\UserType;
use Symfony\Component\Form\Test\TypeTestCase;

class UserTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = array(
            'email' => 'playpero@hotmail.com',
            'visit_day' => '2016-05-18',
            'ticket_type' => 'Journée',
            'ticket_number' => 2,
        );

        $form = $this->factory->create(UserType::class);

        // submit the data to the form directly
        $form->submit($formData);

        $user = new User();
        $user->setEmail('playpero@hotmail.com');
        $user->setVisitDay(new \DateTime('2016-05-18'));
        $user->setTicketType('Journée');
        $user->setTicketNumber(2);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($user, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}