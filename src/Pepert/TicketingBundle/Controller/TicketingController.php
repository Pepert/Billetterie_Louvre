<?php

namespace Pepert\TicketingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Entity\Ticket;
use Pepert\TicketingBundle\Form\Type\UserType;
use Pepert\TicketingBundle\Form\Type\TicketType;

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

    public function ticketAction(Request $request, $nbTickets)
    {
        $idBuyer = $request->getSession()->get('idBuyer');

        $buyer = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        for($i = 0; $i < $nbTickets; $i ++)
        {
            $ticket = new Ticket();
            $ticket->setUser($buyer);
        }

        $form = $this->createForm(TicketType::class, $ticket);

        if ($form->handleRequest($request)->isValid())
        {
            $tickets = $buyer->getTickets();

            $em = $this->getDoctrine()->getManager();

            for($i = 0; $i < $nbTickets; $i ++)
            {
                $em->persist($tickets[$i]);
                $tickets[$i]->setUser($buyer);
            }

            $em->persist($buyer);
            $em->flush();

            return $this->render('PepertTicketingBundle:Ticketing:validation.html.twig');
        }

        return $this->render('PepertTicketingBundle:Ticketing:ticket.html.twig', array(
            'form' => $form->createView(),
            'nbTickets' => $nbTickets,
        ));
    }
}