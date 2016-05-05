<?php

namespace Pepert\TicketingBundle\GeneratePdf;

class GeneratePdf
{
    public function generateHtmlToPdf($tickets)
    {
        ob_start();
        ?>
        <style type="text/css">
            table{
                width: 100%;
                border: solid 1px #000000;
                vertical-align: middle;
            }
        </style>

        <page backtop="20mm" backleft="10mm" backright="10mm" backbottom="5mm">

            <?php foreach($tickets as $ticket){ ?>
                <table style="border-bottom: none; padding-top: 4mm; padding-bottom: 10mm;">
                    <tr>
                        <td style="width: 65%; font-size: 12pt; text-align: center;">
                            <strong style="font-size: 25pt;">Musée du Louvre</strong>
                            <br/>
                            Date de la visite : <?php echo $ticket->getVisitDay()->format('d-m-Y'); ?>
                        </td>
                        <td style="width: 35%;">
                            <img src="http://localhost/BilletterieLouvre/web/bundles/pepertticketing/images/logo_louvre_ticket.png">
                        </td>
                    </tr>
                </table>
                <table style="border-top: none; padding-bottom: 8mm; padding-left: 4mm; text-align: center;">
                    <tr>
                        <td style="width: 20%;">
                            <qrcode value="<?php echo $ticket->getReservationCode(); ?>" ec="H" style="width: 25mm; background-color: white; color: black;"></qrcode>
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
                <br/>
                <br/>
                <br/>
                <br/>
                <br/>
            <?php } ?>

        </page>

        <?php
        $content = ob_get_clean();

        return $content;
    }
}