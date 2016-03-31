<?php

namespace Pepert\TicketingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('PepertTicketingBundle:Default:index.html.twig');
    }
}
