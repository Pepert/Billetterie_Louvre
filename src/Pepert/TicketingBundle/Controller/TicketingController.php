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
            $today->setTimezone(new \DateTimeZone('Europe/Paris'));
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
                $request->getSession()->getFlashBag()->add('erreur', 'Les tickets \'Journée\' ne sont disponible qu\'avant
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

            if(count($compteurTickets) >= 1000)
            {
                $request->getSession()->getFlashBag()->add('erreur', 'Il n\'y a plus de ticket disponible ce jour là.
            Merci de choisir une autre date de visite.');

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

            $nbTickets = $form["ticket_number"]->getData();

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

        if($request->getSession()->get('idTransaction'))
        {
            $idTransaction = $request->getSession()->get('idTransaction');
            $transaction = $this
                ->getDoctrine()
                ->getManager()
                ->getRepository('PepertTicketingBundle:Transaction')
                ->find($idTransaction)
            ;
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

            $request->getSession()->getFlashBag()->add('information', 'Les tickets sont validés.');

            return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
                'publishable_key' => $pk,
                'price' => $transaction->getTotalPrice()*100,
                'nbTickets' => $nbTickets,
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
        $idBuyer = $request->getSession()->get('idBuyer');

        $em = $this->getDoctrine()->getManager();

        $buyer = $em
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        $form = $this->createFormBuilder($buyer)
            ->add('email',EmailType::class)
            ->add('submit',SubmitType::class)
            ->getForm();

        if($form->handleRequest($request)->isValid())
        {
            $email = $form["email"]->getData();

            if($email !== $buyer->getEmail())
            {
                $em->persist($buyer);
                $em->flush();
            }

            return $this->render('PepertTicketingBundle:Ticketing:end.html.twig');
        }

        $transaction = $em
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $service = $this->container->get('pepert_ticketing.paypal_api');

        $requete = $service->doCheckoutApi();

        $transaction->setTransactionDate(new \DateTime());
        $transaction->setTransactionId($requete);

        $tickets = $transaction->getTickets();

        foreach($tickets as $ticket)
        {
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
        $idTransaction = $request->getSession()->get('idTransaction');
        $idBuyer = $request->getSession()->get('idBuyer');

        $em = $this->getDoctrine()->getManager();

        $buyer = $em
            ->getRepository('PepertTicketingBundle:User')
            ->find($idBuyer)
        ;

        $form = $this->createFormBuilder($buyer)
            ->add('email',EmailType::class)
            ->add('submit',SubmitType::class)
            ->getForm();

        if($form->handleRequest($request)->isValid())
        {
            $email = $form["email"]->getData();

            if($email !== $buyer->getEmail())
            {
                $em->persist($buyer);
                $em->flush();
            }

            return $this->redirectToRoute('pepert_ticketing_final');
        }

        $transaction = $em
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $service = $this->container->get('pepert_ticketing.stripe');

        $service->setStripeApi();

        $token  = $_POST['stripeToken'];

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
        $transaction->setTransactionId($charge->id);

        $tickets = $transaction->getTickets();

        foreach($tickets as $ticket)
        {
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
        $idTransaction = $request->getSession()->get('idTransaction');

        $transaction = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $service = $this->container->get('pepert_ticketing.stripe');

        $pk = $service->setStripeApi();

        $request->getSession()->getFlashBag()->add('information', 'La transaction n\'a pas pu être effectuée. Merci
         de réessayer.');

        return $this->render('PepertTicketingBundle:Ticketing:paiement.html.twig', array(
            'publishable_key' => $pk,
            'price' => $transaction->getTotalPrice()*100,
        ));
    }

    public function generateMailAction(Request $request)
    {
        $idTransaction = $request->getSession()->get('idTransaction');

        $transaction = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository('PepertTicketingBundle:Transaction')
            ->find($idTransaction)
        ;

        $tickets = $transaction->getTickets();

        ob_start();
        ?>
            <style type="text/css">
                table{
                    width: 100%;
                    border: solid 1px #000000;
                    vertical-align: middle;
                }
            </style>

            <page backtop="5mm" backleft="10mm" backright="10mm" backbottom="5mm">

        <?php foreach($tickets as $ticket){ ?>
                <table style="border-bottom: none; padding-top: 4mm; padding-bottom: 10mm; margin-top: 18mm;">
                    <tr>
                        <td style="width: 65%; font-size: 12pt; text-align: center;">
                            <strong style="font-size: 25pt;">Musée du Louvre</strong>
                            <br/>
                            Date de la visite : <?php echo $ticket->getVisitDay()->format('d-m-Y'); ?>
                        </td>
                        <td style="width: 35%;">
                            <img src="BilletterieLouvre/web/bundles/pepertticketing/images/logo_louvre_ticket.png">
                        </td>
                    </tr>
                </table>
                <table style="border-top: none; padding-bottom: 8mm; padding-left: 4mm; text-align: center;">
                    <tr>
                        <td style="width: 20%;">
                            <qrcode value="1" ec="H" style="width: 25mm; background-color: white; color: black;"></qrcode>
                        </td>
                        <td style="width: 50%; font-size: 18pt; text-align: center;">
                            <?php echo $ticket->getFirstName(); ?> <?php echo strtoupper($ticket->getName()); ?>
                        </td>
                        <td style="width: 30%; font-size: 12pt;">
                            <?php echo $ticket->getTicketType(); ?> - <?php echo $ticket->getTarifName(); ?>
                            <br/>
                            <strong style="font-size: 25pt;"><?php echo $ticket->getPrice(); ?> €</strong>
                        </td>
                    </tr>
                </table>
        <?php } ?>

            </page>

<?php
        $content = ob_get_clean();
        try{
            $pdf = new \HTML2PDF('P','A4','fr');
            $pdf->writeHTML($content);
            $pdf->Output('test.pdf');
        }catch(\HTML2PDF_exception $e){
            die($e);
        }

        return $this->render('PepertTicketingBundle:Ticketing:end.html.twig');
    }
}