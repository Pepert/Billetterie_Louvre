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

use Stripe\Customer;
use Stripe\Charge;

class TicketingController extends Controller
{
    public function indexAction(Request $request)
    {
        $request->getSession()->remove('idBuyer');
        $request->getSession()->remove('idTransaction');

        $buyer = new User();

        $form = $this->createForm(UserType::class, $buyer);

        if($form->handleRequest($request)->isValid())
        {
            $nbTickets = $form["ticket_number"]->getData();

            $buyerExists = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PepertTicketingBundle:User')
                ->findOneBy(
                    array('email' => $form["email"]->getData())
                );

            if($buyerExists)
            {
                $buyer = $buyerExists;
            }
            else
            {
                $em = $this->getDoctrine()->getManager();
                $em->persist($buyer);
                $em->flush();
            }

            $idBuyer = $buyer->getId();
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


        $transaction = new Transaction();
        $buyer->addTransaction($transaction);

        $transaction->setTicketNumber($nbTickets);

        for($i = 0; $i < $nbTickets; $i ++)
        {
            $ticket = new Ticket();
            $transaction->addTicket($ticket);
        }

        $formBuilder = $this->createFormBuilder($transaction);

        $formBuilder
            ->add('tickets', CollectionType::class, array('entry_type' => TicketType::class))
            ->add('submit',SubmitType::class)
        ;

        $form = $formBuilder->getForm();

        if($form->handleRequest($request)->isValid())
        {
            $tickets = $transaction->getTickets();
            $type = $buyer->getTicketType();

            $em = $this->getDoctrine()->getManager();

            $priceCalculator = $this->container->get('pepert_ticketing.price_calculator');

            foreach($tickets as $ticket)
            {
                $ticket->setVisitDay($buyer->getVisitDay());
                $ticket->setTicketType($type);
            }

            $tickets = $priceCalculator->tarif($tickets,$nbTickets);

            $transaction->setTotalPrice($priceCalculator->calculerPrixTotal($tickets,$type));

            $transaction->setTickets($tickets);

            $em->persist($buyer);
            $em->flush();

            $idTransaction = $transaction->getId();
            $request->getSession()->set('idTransaction', $idTransaction);

            $service = $this->container->get('pepert_ticketing.stripe');

            $pk = $service->setStripeApi();

            return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
                'publishable_key' => $pk,
                'price' => $transaction->getTotalPrice()*100,
            ));
        }

        return $this->render('PepertTicketingBundle:Ticketing:ticket.html.twig', array(
            'form' => $form->createView(),
            'nbTickets' => $nbTickets,
        ));
    }

    public function paypalAction(Request $request)
    {
        $idTransaction = $request->getSession()->get('idTransaction');

        $em = $this->getDoctrine()->getManager();

        $transaction = $em
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $service = $this->container->get('pepert_ticketing.paypal_api');

        $requete = $service->setCheckoutApi($transaction);

        header("Location:".$requete);
        exit();
    }

    public function paypalValidatedAction(Request $request)
    {
        $idTransaction = $request->getSession()->get('idTransaction');

        $em = $this->getDoctrine()->getManager();

        $transaction = $em
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $service = $this->container->get('pepert_ticketing.paypal_api');

        $requete = $service->doCheckoutApi();

        $transaction->setTransactionDate(new \DateTime());
        $transaction->setTransactionId($requete);

        $em->persist($transaction);
        $em->flush();

        return $this->render('PepertTicketingBundle:Ticketing:paypalValidated.html.twig');
    }

    public function stripeValidatedAction(Request $request)
    {
        $idTransaction = $request->getSession()->get('idTransaction');

        $em = $this->getDoctrine()->getManager();

        $transaction = $em
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $service = $this->container->get('pepert_ticketing.stripe');

        $service->setStripeApi();

        $token  = $_POST['stripeToken'];

        $customer = Customer::create(array(
            'email' => 'playpero@hotmail.com',
            'card'  => $token
        ));

        $charge = Charge::create(array(
            'customer' => $customer->id,
            'amount'   => $transaction->getTotalPrice()*100,
            'currency' => 'eur'
        ));

        $transaction->setTransactionDate(new \DateTime());
        $transaction->setTransactionId($charge->id);

        $em->persist($transaction);
        $em->flush();

        return $this->render('PepertTicketingBundle:Ticketing:stripeValidated.html.twig');
    }
}