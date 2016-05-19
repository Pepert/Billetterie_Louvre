<?php

namespace Pepert\TicketingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Entity\Ticket;
use Pepert\TicketingBundle\Entity\Transaction;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Pepert\TicketingBundle\Form\Type\UserType;
use Pepert\TicketingBundle\Form\Type\TicketType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

use Stripe\Customer;
use Stripe\Charge;
use Stripe\Error\Card;
use Stripe\Error\RateLimit;
use Stripe\Error\InvalidRequest;
use Stripe\Error\Authentication;
use Stripe\Error\ApiConnection;
use Stripe\Error\Base;
use Exception;

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
            $typeTickets = $form["ticket_type"]->getData();
            $dateVisite = $form["visit_day"]->getData();
            $today = new \DateTime();
            $todayTime = (int)$today->format('G');
            $visitDay = $dateVisite->format('D');
            $todayDate = $today->setTime(0,0,0);

            if($dateVisite < $todayDate)
            {
                $request->getSession()->getFlashBag()->add('erreur', 'Il est impossible de sélectionner une date
                déjà passée. Merci de choisir un autre jour de visite.');

                return $this->render('PepertTicketingBundle:Ticketing:index.html.twig', array(
                    'form' => $form->createView(),
                ));
            }
            else if($visitDay == 'Tue'
                || $dateVisite->format('d-m') == '01-05'
                || $dateVisite->format('d-m') == '01-11'
                || $dateVisite->format('d-m') == '25-12')
            {
                $request->getSession()->getFlashBag()->add('erreur', 'Le musée est fermé le mardi, le 1er mai, le 1er
                novembre et le 25 décembre. Merci de sélectionner une autre date de visite.');

                return $this->render('PepertTicketingBundle:Ticketing:index.html.twig', array(
                    'form' => $form->createView(),
                ));
            }
            else if($dateVisite == $todayDate && $todayTime >= 14 && $typeTickets === 'Journée')
            {
                $request->getSession()->getFlashBag()->add('erreur', 'Les billets \'Journée\' ne sont disponible qu\'avant
                14 heures pour le jour en cours. Merci de selectionner le type \'Demi-journée\' si vous souhaitez
                visiter le musée aujourd\'hui.');

                return $this->render('PepertTicketingBundle:Ticketing:index.html.twig', array(
                    'form' => $form->createView(),
                ));
            }

            $em = $this->getDoctrine()->getManager();

            $compteurTickets = $em
                ->getRepository('PepertTicketingBundle:Ticket')
                ->findBy(
                    array(
                        'visitDay' => $dateVisite,
                        'status' => 'Payé'
                    )
                );

            $ticketBought = count($compteurTickets);

            $nbTickets = $form["ticket_number"]->getData();

            if(($ticketBought + $nbTickets) >= 1000)
            {
                if($ticketBought == 1000)
                {
                    $request->getSession()->getFlashBag()->add('erreur', 'Il ne reste plus de billets disponibles ce jour là.
                Merci de choisir une autre date de visite.');
                }
                else
                {
                    $ticketLeft = 1000 - $ticketBought;
                    $request->getSession()->getFlashBag()->add('erreur', 'Il ne reste plus que '.$ticketLeft.' billet(s)
                    disponible(s) ce jour là. Merci de choisir une autre date de visite, ou de n\'acheter que '.$ticketLeft.
                        ' billet(s) au maximum pour cete date.');
                }

                return $this->render('PepertTicketingBundle:Ticketing:index.html.twig', array(
                    'form' => $form->createView(),
                ));
            }

            $buyerExists = $em
                ->getRepository('PepertTicketingBundle:User')
                ->findOneBy(
                    array('email' => $form["email"]->getData())
                );

            if($buyerExists)
            {
                $buyerExists->setVisitDay($buyer->getVisitDay());
                $buyerExists->setTicketType($buyer->getTicketType());
                $buyerExists->setTicketNumber($buyer->getTicketNumber());
                $buyer = $buyerExists;
            }

            $em->persist($buyer);
            $em->flush();

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
        if($nbTickets <= 0)
        {
            $request->getSession()->getFlashBag()->add('erreur', 'Vous devez acheter au moins un billet');
            return $this->redirectToRoute('pepert_ticketing_homepage');
        }
        else if($nbTickets > 25)
        {
            $request->getSession()->getFlashBag()->add('erreur', 'Vous ne pouvez commander que 25 billets maximum par transaction');
            return $this->redirectToRoute('pepert_ticketing_homepage');
        }

        $buyer = $this->getBuyer($request);

        if($request->getSession()->get('idTransaction'))
        {
            $transaction = $this->getTransaction($request);
            $transaction->removeAllTickets();
        }
        else{
            $transaction = new Transaction();
            $transaction->setTransactionDate(new \DateTime());
            $buyer->addTransaction($transaction);
        }

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
                $ticket->setFirstName(ucfirst(mb_strtolower($ticket->getFirstname(),'UTF-8')));
                $ticket->setName(ucfirst(mb_strtolower($ticket->getName(), 'UTF-8')));
            }

            $tickets = $priceCalculator->tarif($tickets,$nbTickets);
            $resumeTransaction = $priceCalculator->afficherCommande($tickets);

            $transaction->setTotalPrice($priceCalculator->calculerPrixTotal($tickets,$type));

            $transaction->setTickets($tickets);

            $em->persist($buyer);
            $em->flush();

            $idTransaction = $transaction->getId();
            $request->getSession()->set('idTransaction', $idTransaction);

            $service = $this->container->get('pepert_ticketing.stripe');

            $pk = $service->setStripeApi();

            $request->getSession()->getFlashBag()->clear();
            $request->getSession()->getFlashBag()->add('information', 'Les billets sont validés.');

            return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
                'publishable_key' => $pk,
                'commande' => $resumeTransaction,
                'price' => $transaction->getTotalPrice()*100,
                'nbTickets' => $nbTickets,
            ));
        }

        return $this->render('PepertTicketingBundle:Ticketing:ticket.html.twig', array(
            'form' => $form->createView(),
            'nbTickets' => $nbTickets,
        ));
    }

    public function paymentRetryAction(Request $request)
    {
        $idTransaction = $request->query->get('idTransaction');
        $idBuyer = $request->query->get('idBuyer');

        $request->getSession()->set('idTransaction', $idTransaction);
        $request->getSession()->set('idBuyer', $idBuyer);

        $transaction = $this->getTransaction($request);

        $tickets = $transaction->getTickets();

        $priceCalculator = $this->container->get('pepert_ticketing.price_calculator');
        $resumeTransaction = $priceCalculator->afficherCommande($tickets);

        $service = $this->container->get('pepert_ticketing.stripe');

        $pk = $service->setStripeApi();

        return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
            'publishable_key' => $pk,
            'commande' => $resumeTransaction,
            'price' => $transaction->getTotalPrice()*100,
            'nbTickets' => $transaction->getTicketNumber(),
        ));
    }

    public function paypalAction(Request $request)
    {
        $transaction = $this->getTransaction($request);
        $buyer = $this->getBuyer($request);

        $service = $this->container->get('pepert_ticketing.paypal_api');

        $requete = $service->setCheckoutApi($transaction, $buyer);

        header("Location:".$requete);
        exit();
    }

    public function paypalValidatedAction(Request $request)
    {
        $idTransaction = $request->query->get('idTransaction');
        $idBuyer = $request->query->get('idBuyer');

        $request->getSession()->set('idTransaction', $idTransaction);
        $request->getSession()->set('idBuyer', $idBuyer);

        $em = $this->getDoctrine()->getManager();

        $buyer = $this->getBuyer($request);

        $oldEmail = $buyer->getEmail();

        $form = $this->createFormBuilder($buyer)
            ->add('email',EmailType::class)
            ->add('submit',SubmitType::class)
            ->getForm();

        if($form->handleRequest($request)->isValid())
        {
            $email = $form["email"]->getData();

            if($email !== $oldEmail)
            {
                $em->persist($buyer);
                $em->flush();
            }

            return $this->redirectToRoute('pepert_ticketing_final');
        }

        $transaction = $this->getTransaction($request);

        //Je n'appelle ce service que si je ne suis pas en train de faire un test fonctionnel
        if($request->query->get('run') !== 'test')
        {
            $service = $this->container->get('pepert_ticketing.paypal_api');

            $requete = $service->doCheckoutApi();

            $transaction->setTransactionDate(new \DateTime());
            $transaction->setTransactionSystem('Paypal');
            $transaction->setTransactionId($requete);
        }

        $tickets = $transaction->getTickets();

        foreach($tickets as $ticket)
        {
            $infos = $ticket->getId()
                .$ticket->getFirstName()
                .$ticket->getName()
                .$ticket->getTarifName()
                .$ticket->getTicketType()
                .$ticket->getVisitDay()->format('d-m-Y')
                .$idBuyer;
            $reservationCode = md5($infos);
            $ticket->setReservationCode($reservationCode);
            $ticket->setStatus('Payé');
        }

        $em->persist($transaction);
        $em->flush();

        return $this->render('PepertTicketingBundle:Ticketing:paymentValidated.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function stripeValidatedAction(Request $request)
    {
        $transaction = $this->getTransaction($request);
        $buyer = $this->getBuyer($request);

        $em = $this->getDoctrine()->getManager();

        $oldEmail = $buyer->getEmail();

        $form = $this->createFormBuilder($buyer)
            ->add('email',EmailType::class)
            ->add('submit',SubmitType::class)
            ->getForm();

        if($form->handleRequest($request)->isValid())
        {
            $email = $form["email"]->getData();

            if($email !== $oldEmail)
            {
                $em->persist($buyer);
                $em->flush();
            }

            return $this->redirectToRoute('pepert_ticketing_final');
        }

        //Je n'appelle ce service que si je ne suis pas en train de faire un test fonctionnel
        if($request->query->get('run') !== 'test')
        {
            $service = $this->container->get('pepert_ticketing.stripe');

            $service->setStripeApi();

            $token = $request->get('stripeToken');

            try {
                $customer = Customer::create(array(
                    'email' => 'playpero@hotmail.com',
                    'card'  => $token
                ));

                $charge = Charge::create(array(
                    'customer' => $customer->id,
                    'amount'   => $transaction->getTotalPrice()*100,
                    'currency' => 'eur'
                ));
            } catch(Card $e) {
                $body = $e->getJsonBody();
                $err  = $body['error'];
                $messageErr = '';

                $messageErr .= 'Status is:' . $e->getHttpStatus() . "\n";
                $messageErr .='Type is:' . $err['type'] . "\n";
                $messageErr .='Code is:' . $err['code'] . "\n";
                $messageErr .='Param is:' . $err['param'] . "\n";
                $messageErr .='Message is:' . $err['message'] . "\n";

                $request->getSession()->getFlashBag()->add('erreur', $messageErr);

                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            } catch (RateLimit $e) {
                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            } catch (InvalidRequest $e) {
                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            } catch (Authentication $e) {
                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            } catch (ApiConnection $e) {
                $request->getSession()->getFlashBag()->add('erreur', 'Un problème de connexion avec Stripe est survenu.');

                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            } catch (Base $e) {
                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            } catch (Exception $e) {
                return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
            }

            $transaction->setTransactionDate(new \DateTime());
            $transaction->setTransactionSystem('Stripe');
            $transaction->setTransactionId($charge->id);
        }

        $tickets = $transaction->getTickets();

        foreach($tickets as $ticket)
        {
            $infos = $ticket->getFirstName()
                .$ticket->getName()
                .$ticket->getTarifName()
                .$ticket->getTicketType()
                .$ticket->getVisitDay()->format('d-m-Y')
                .$ticket->getId();
            $reservationCode = md5($infos);
            $ticket->setReservationCode($reservationCode);
            $ticket->setStatus('Payé');
        }

        $em->persist($transaction);
        $em->flush();

        return $this->render('PepertTicketingBundle:Ticketing:paymentValidated.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function paymentErrorAction(Request $request)
    {
        $transaction = $this->getTransaction($request);

        $service = $this->container->get('pepert_ticketing.stripe');

        $pk = $service->setStripeApi();

        $request->getSession()->getFlashBag()->add('information', 'La transaction n\'a pas pu être effectuée. Merci
         de réessayer.');

        return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
            'publishable_key' => $pk,
            'price' => $transaction->getTotalPrice()*100,
            'nbTickets' => $transaction->getTicketNumber(),
        ));
    }

    public function generateMailAction(Request $request)
    {
        $buyer = $this->getBuyer($request);
        $transaction = $this->getTransaction($request);

        $tickets = $transaction->getTickets();

        $service = $this->container->get('pepert_ticketing.generate_pdf');
        $content = $service->generateHtmlToPdf($tickets);

        try{
            $pdf = new \HTML2PDF('P','A4','fr');
            $pdf->writeHTML($content);
            $pdf->Output('billets.pdf', true);
        }catch(\HTML2PDF_exception $e){
            $request->getSession()->getFlashBag()->add('erreur', $e);
            return $this->render('PepertTicketingBundle:Ticketing:error.html.twig');
        }

        $attachment = \Swift_Attachment::newInstance($pdf, 'billets.pdf', 'application/pdf');

        $mail = \Swift_Message::newInstance()
            ->setSubject('Billets Musée du Louvre')
            ->setFrom('billets@louvre.com')
            ->setTo($buyer->getEmail())
            ->setBody('Bonne visite !')
            ->attach($attachment);

        $this->get('mailer')->send($mail);

        return $this->render('PepertTicketingBundle:Ticketing:end.html.twig');
    }

    private function getBuyer(Request $request)
    {
        $idBuyer = $request->getSession()->get('idBuyer');

        if($idBuyer == null)
        {
            $request->getSession()->getFlashBag()->clear();
            $request->getSession()->getFlashBag()->add('erreur', 'La session a expirée. Merci de réitérer votre commande.');
            return $this->redirectToRoute('pepert_ticketing_homepage');
        }

        $buyer = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        return $buyer;
    }

    private function getTransaction(Request $request)
    {
        $idTransaction = $request->getSession()->get('idTransaction');

        if($idTransaction == null)
        {
            $request->getSession()->getFlashBag()->clear();
            $request->getSession()->getFlashBag()->add('erreur', 'La session a expirée. Merci de réitérer votre commande.');
            return $this->redirectToRoute('pepert_ticketing_homepage');
        }

        $transaction = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        return $transaction;
    }
}