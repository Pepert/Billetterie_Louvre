<?php

namespace Pepert\TicketingBundle\GenerateApi;

use Pepert\TicketingBundle\Entity\User;

class GenerateApi
{
    private $api_paypal = 'https://api-3t.sandbox.paypal.com/nvp?';

    private $version = 124.0;

    private $userAPI = 'playpero_api1.aol.com';

    private $pass = '89SXSPKJ7LY99GPW';

    private $signature = 'AFcWxV21C7fd0v3bYYYRCpSSRl31AVWyXF5EI4EQcGTPirNd95eECLrq';

    public function setCheckoutApi(User $buyer)
    {
        $api_paypal = $this->api_paypal.'VERSION='.$this->version.'&USER='.$this->userAPI.'&PWD='.
            $this->pass.'&SIGNATURE='.$this->signature;

        $requete = $api_paypal."&METHOD=SetExpressCheckout".
            "&CANCELURL=".urlencode("http://127.0.0.1/BilletterieLouvre/web/app_dev.php/cancel").
            "&RETURNURL=".urlencode("http://127.0.0.1/BilletterieLouvre/web/app_dev.php/payment/paypal/validated").
            "&AMT=".urlencode($buyer->getTotalPrice()).
            "&CURRENCYCODE=EUR".
            "&DESC=".urlencode($buyer->getTicketNumber())." billets pour le musée du Louvre.".
            "&LOCALECODE=FR";

        return $requete;
    }

    public function doCheckoutApi(User $buyer)
    {
        $api_paypal = $this->api_paypal.'VERSION='.$this->version.'&USER='.$this->userAPI.'&PWD='.$this->pass.'&SIGNATURE='.$this->signature; // Ajoute tous les paramètres

        $requete = $api_paypal."&METHOD=DoExpressCheckoutPayment".
            "&TOKEN=".htmlentities($_GET['token'], ENT_QUOTES).
            "&AMT=".urlencode($buyer->getTotalPrice()).
            "&CURRENCYCODE=EUR".
            "&PayerID=".htmlentities($_GET['PayerID'], ENT_QUOTES).
            "&PAYMENTACTION=sale";

        return $requete;
    }
}