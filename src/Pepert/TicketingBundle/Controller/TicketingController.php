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

        if ($form->handleRequest($request)->isValid())
        {
            $nbTickets = $form["ticket_number"]->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $idBuyer = $user->getId();
            $request->getSession()->set('idBuyer', $idBuyer);

            $request->getSession()->getFlashBag()->add('validation', 'Votre identité à bien été enregistrée');

            return $this->redirect($this->generateUrl('pepert_ticketing_tickets', array(
                'nbTickets' => $nbTickets
            )));
        }

        return $this->render('PepertTicketingBundle:Ticketing:index.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}