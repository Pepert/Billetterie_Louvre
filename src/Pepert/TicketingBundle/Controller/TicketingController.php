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

            return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig');
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

        $ch = curl_init($requete);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $resultat_paypal = curl_exec($ch);

        if (!$resultat_paypal)
        {
            //redirigé vers page d'erreur
        }
        else
        {
            $liste_param_paypal = [];

            $liste_parametres = explode("&",$resultat_paypal);
            foreach($liste_parametres as $param_paypal)
            {
                list($nom, $valeur) = explode("=", $param_paypal);
                $liste_param_paypal[$nom]=urldecode($valeur);
            }

            if ($liste_param_paypal['ACK'] == 'Success')
            {
                header("Location: https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=".
                    $liste_param_paypal['TOKEN']);
                exit();
            }
            else
            {
                echo "<p>Erreur de communication avec le serveur PayPal.<br />".$liste_param_paypal['L_SHORTMESSAGE0'].
                    "<br />".$liste_param_paypal['L_LONGMESSAGE0']."</p>";
            }
        }
        curl_close($ch);
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

        $requete = $service->doCheckoutApi($buyer);

        $ch = curl_init($requete);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resultat_paypal = curl_exec($ch);

        if (!$resultat_paypal)
        {
            return $this->render('PepertTicketingBundle:Ticketing:cancel.html.twig');
        }
        else
        {
            $liste_param_paypal = [];

            $liste_parametres = explode("&",$resultat_paypal);
            foreach($liste_parametres as $param_paypal)
            {
                list($nom, $valeur) = explode("=", $param_paypal);
                $liste_param_paypal[$nom]=urldecode($valeur);
            }

            if ($liste_param_paypal['ACK'] == 'Success'
                && $liste_param_paypal['AMT'] == $buyer->getTotalPrice()
                && $liste_param_paypal['CURRENCYCODE'] == 'EUR'
            )
            {
                $transaction = new Transaction();

                $transaction->setTransactionDate(new \DateTime());
                $transaction->setTransactionInfos($resultat_paypal);
                $buyer->addTransaction($transaction);

                $em->persist($buyer);
                $em->flush();

                return $this->render('PepertTicketingBundle:Ticketing:paypalValidated.html.twig');
            }
            else
            {
                return $this->render('PepertTicketingBundle:Ticketing:cancel.html.twig');
            }
        }
    }
}