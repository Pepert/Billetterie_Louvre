<?php

namespace Pepert\TicketingBundle\StripePaymentSystem;

use Stripe\Stripe;

class StripePayment
{
    public function setStripeApi()
    {
        $stripe = array(
            "secret_key"      => "sk_test_OxI0SpEBW14428ED1Z2llUp0",
            "publishable_key" => "pk_test_xqkBN14wQxgvHZmHH5Xm9l3y"
        );

        Stripe::setApiKey($stripe['secret_key']);

        return $stripe['publishable_key'];
    }
}