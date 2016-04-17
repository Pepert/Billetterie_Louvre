<?php

namespace Pepert\TicketingBundle\Controller;

use Pepert\TicketingBundle\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Entity\Ticket;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Pepert\TicketingBundle\Form\Type\UserType;
use Pepert\TicketingBundle\Form\Type\TicketType;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;

class TicketingController extends Controller
{
    public function indexAction(Request $request)
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        if($form->handleRequest($request)->isValid())
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

        $buyer->removeAllTickets();

        for($i = 0; $i < $nbTickets; $i ++)
        {
            $ticket = new Ticket();
            $buyer->addTicket($ticket);
        }

        $formBuilder = $this->createFormBuilder($buyer);

        $formBuilder
            ->add('tickets', CollectionType::class, array('entry_type' => TicketType::class))
            ->add('submit',SubmitType::class)
        ;

        $form = $formBuilder->getForm();


        if($form->handleRequest($request)->isValid())
        {
            $tickets = $buyer->getTickets();
            $type = $buyer->getTicketType();

            $em = $this->getDoctrine()->getManager();

            $priceCalculator = $this->container->get('pepert_ticketing.price_calculator');

            foreach($tickets as $ticket)
            {
                $ticket->setVisitDay($buyer->getVisitDay());
                $ticket->setTicketType($buyer->getTicketType());
            }

            $tickets = $priceCalculator->tarif($tickets,$nbTickets);

            $buyer->setTickets($tickets);

            $buyer->setTotalPrice($priceCalculator->calculerPrixTotal($tickets,$type));

            $em->persist($buyer);
            $em->flush();

            $service = $this->container->get('pepert_ticketing.stripe');

            $pk = $service->setStripeApi();

            return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
                'publishable_key' => $pk,
            ));
        }

        return $this->render('PepertTicketingBundle:Ticketing:ticket.html.twig', array(
            'form' => $form->createView(),
            'nbTickets' => $nbTickets,
        ));
    }

    public function paypalAction(Request $request)
    {
        $idBuyer = $request->getSession()->get('idBuyer');

        $em = $this->getDoctrine()->getManager();

        $buyer = $em
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        $service = $this->container->get('pepert_ticketing.paypal_api');

        $requete = $service->setCheckoutApi($buyer);

        header("Location:".$requete);
        exit();
    }

    public function paypalValidatedAction(Request $request)
    {
        $idBuyer = $request->getSession()->get('idBuyer');

        $em = $this->getDoctrine()->getManager();

        $buyer = $em
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        $service = $this->container->get('pepert_ticketing.paypal_api');

        $requete = $service->doCheckoutApi();

        $transaction = new Transaction();

        $transaction->setTransactionDate(new \DateTime());
        $transaction->setTransactionObject($requete);
        $buyer->addTransaction($transaction);

        $em->persist($buyer);
        $em->flush();

        return $this->render('PepertTicketingBundle:Ticketing:paypalValidated.html.twig');
    }

    public function stripeValidatedAction(Request $request)
    {
        $idBuyer = $request->getSession()->get('idBuyer');

        $em = $this->getDoctrine()->getManager();

        $buyer = $em
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        $service = $this->container->get('pepert_ticketing.stripe');

        $service->setStripeApi();

        $token  = $_POST['stripeToken'];

        $customer = Customer::create(array(
            'email' => 'playpero@hotmail.com',
            'card'  => $token
        ));

        Charge::create(array(
            'customer' => $customer->id,
            'amount'   => $buyer->getTotalPrice()*100,
            'currency' => 'eur'
        ));

        return $this->render('PepertTicketingBundle:Ticketing:stripeValidated.html.twig');
    }
}