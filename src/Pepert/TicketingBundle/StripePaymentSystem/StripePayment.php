<?php

namespace Pepert\TicketingBundle\StripePaymentSystem;

use Stripe\Stripe;

class StripePayment
{
    public function setStripeApi()
    {
        $stripe = array(
            "secret_key"      => "sk_test_zOQvDcnvJMLIyWyK0QUYxAxK",
            "publishable_key" => "pk_test_yMYkdAAnYrIbPtrkCvrTM9mX"
        );

        Stripe::setApiKey($stripe['secret_key']);

        return $stripe['publishable_key'];
    }
}