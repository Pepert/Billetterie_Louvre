<?php

namespace Pepert\TicketingBundle\GenerateApiPaypal;

use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use Exception;

use Pepert\TicketingBundle\Entity\User;

class GenerateApiPaypal
{
    public function setCheckoutApi(\Pepert\TicketingBundle\Entity\Transaction $currentTransaction, User $buyer)
    {
        require_once __DIR__ . '/bootstrap.php';

        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        $ticketArray = [];
        $tickets = $currentTransaction->getTickets();
        foreach($tickets as $ticket)
        {
            $item = new Item();
            $item->setName('Ticket '.$ticket->getTarifName())
                ->setPrice($ticket->getPrice())
                ->setCurrency("EUR")
                ->setQuantity(1);
            $ticketArray[] = $item;
        }

        $itemList = new ItemList();
        $itemList->setItems($ticketArray);

        $amount = new Amount();
        $amount->setCurrency("EUR")
            ->setTotal($currentTransaction->getTotalPrice());

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($currentTransaction->getTicketNumber().' ticket(s) pour le musée')
            ->setInvoiceNumber(uniqid());

        $baseUrl = "http://127.0.0.1/BilletterieLouvre/web/app_dev.php";
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl("$baseUrl/payment/paypal/validated?success=true&idTransaction="
            .$currentTransaction->getId()."&idBuyer=".$buyer->getId())
            ->setCancelUrl("$baseUrl/payment?idTransaction="
            .$currentTransaction->getId()."&idBuyer=".$buyer->getId());

        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));

        try {
            $payment->create($apiContext);
        } catch (Exception $ex) {
            header('Location: '.$baseUrl.'/payment/error');
            exit();
        }

        return $payment->getApprovalLink();
    }

    public function doCheckoutApi()
    {
        require_once __DIR__ . '/bootstrap.php';

        $baseUrl = "http://127.0.0.1/BilletterieLouvre/web/app_dev.php";

        if (isset($_GET['success']) && $_GET['success'] == 'true') {

            $paymentId = $_GET['paymentId'];

            try {
                $payment = Payment::get($paymentId, $apiContext);
            } catch (Exception $ex) {
                header('Location: '.$baseUrl.'/payment/error');
                exit();
            }

            $execution = new PaymentExecution();
            $execution->setPayerId($_GET['PayerID']);

            try {
                $payment->execute($execution, $apiContext);

                try {
                    $payment = Payment::get($paymentId, $apiContext);
                } catch (Exception $ex) {
                    header('Location: '.$baseUrl.'/payment/error');
                    exit();
                }
            } catch (Exception $ex) {
                header('Location: '.$baseUrl.'/payment/error');
                exit();
            }
            return $payment->id;
        } else {
            header('Location: '.$baseUrl.'/payment/error');
            exit();
        }
    }
}