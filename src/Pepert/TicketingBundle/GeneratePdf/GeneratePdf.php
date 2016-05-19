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
                border: solid 1px #3c3c3c;
                vertical-align: middle;
                color: white;
            }

            .background{
                <?php if($tickets[0]->getTicketType() == 'Journée') { ?>
                background-image: url(http://localhost/BilletterieLouvre/web/bundles/pepertticketing/images/ticket_background.jpg);
                <?php  }
                else {
                ?>
                background-image: url(http://localhost/BilletterieLouvre/web/bundles/pepertticketing/images/ticket_background2.jpg);
                <?php
                }
                 ?>
            }
        </style>

        <page backtop="20mm" backleft="10mm" backright="10mm" backbottom="5mm">

            <?php foreach($tickets as $ticket){ ?>
                <div class="background">
                    <table style="border-bottom: none; padding-top: 4mm; padding-bottom: 10mm;">
                        <tr>
                            <td style="width: 2%;">
                            </td>
                            <td style="width: 48%; font-size: 12pt; text-align: left; vertical-align: top;">
                                <strong style="font-size: 25pt;">Musée du Louvre</strong>
                                <br/>
                                Date de la visite : <?php echo $ticket->getVisitDay()->format('d-m-Y'); ?>
                            </td>
                            <td style="width: 10%;">
                            </td>
                            <td style="width: 40%;">
                                <img src="http://localhost/BilletterieLouvre/web/bundles/pepertticketing/images/logo_louvre_ticket.jpg">
                            </td>
                        </tr>
                    </table>
                    <table style="border-top: none; padding-bottom: 8mm; padding-left: 4mm; text-align: center;">
                        <tr>
                            <td style="width: 20%;">
                                <qrcode value="<?php echo $ticket->getReservationCode(); ?>" ec="H" style="width: 25mm; background-color: white; color: black;"></qrcode>
                            </td>
                            <td style="width: 58%; font-size: 18pt; text-align: center; color: black;">
                                <?php echo $ticket->getFirstName(); ?> <?php echo strtoupper($ticket->getName()); ?>
                            </td>
                            <td style="width: 22%; font-size: 12pt; vertical-align: bottom;">
                                <strong style="font-size: 25pt;"><?php echo $ticket->getPrice(); ?> €</strong>
                                <br/>
                                <?php echo $ticket->getTicketType(); ?> - <?php echo $ticket->getTarifName(); ?>
                            </td>
                        </tr>
                    </table>
                </div>
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