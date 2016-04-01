<?php

namespace Pepert\TicketingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Form\Type\UserType;

class TicketingController extends Controller
{
    public function indexAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        return $this->render('PepertTicketingBundle:Default:index.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}