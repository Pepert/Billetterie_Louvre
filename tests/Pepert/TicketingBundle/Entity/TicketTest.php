<?php

namespace Pepert\Tests;

use Pepert\TicketingBundle\Entity\Ticket;
use DateTime;
use Pepert\TicketingBundle\Entity\Transaction;

class TicketTest extends \PHPUnit_Framework_TestCase
{
    private $ticket;

    public static $data = array(
        array(3),
        array(array('salut','test'))
    );

    public function getData()
    {
        return self::$data;
    }

    public function setUp()
    {
        $this->ticket = new Ticket();
    }

    public function testTicketNameIsValidString()
    {
        $this->ticket->setName('Test éàù');

        $this->assertTrue(is_string($this->ticket->getName()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketNameIsNotValidString($name)
    {
        $this->ticket->setName($name);

        $this->assertFalse(is_string($this->ticket->getName()));
    }

    public function testTicketFirstnameIsValidString()
    {
        $this->ticket->setFirstname('Test éàù');

        $this->assertTrue(is_string($this->ticket->getFirstname()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketFirstnameIsNotValidString($name)
    {
        $this->ticket->setFirstname($name);

        $this->assertFalse(is_string($this->ticket->getFirstname()));
    }

    public function testTicketCountryIsValidString()
    {
        $this->ticket->setFirstname('Test éàù');

        $this->assertTrue(is_string($this->ticket->getFirstname()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketCountryIsNotValidString($country)
    {
        $this->ticket->setCountry($country);

        $this->assertFalse(is_string($this->ticket->getCountry()));
    }

    public function testTicketVisitDayIsValidDateTime()
    {
        $this->ticket->setVisitDay(new DateTime('2015-05-13'));

        $this->assertInstanceOf('DateTime',$this->ticket->getVisitDay());
    }

    public function testTicketVisitDayIsNotValidDateTime()
    {
        $this->ticket->setVisitDay('2015-05-13');

        $this->assertNotInstanceOf('DateTime',$this->ticket->getVisitDay());
    }

    public function testTicketBirthdayIsValidDateTime()
    {
        $this->ticket->setBirthday(new DateTime('1985-04-13'));

        $this->assertInstanceOf('DateTime',$this->ticket->getBirthday());
    }

    public function testTicketBirthdayIsNotValidDateTime()
    {
        $this->ticket->setBirthday('1985-04-13');

        $this->assertNotInstanceOf('DateTime',$this->ticket->getBirthday());
    }

    public function testTicketTarifReduitIsValidBoolean()
    {
        $this->ticket->setTarifReduit(true);

        $this->assertTrue(is_bool($this->ticket->getTarifReduit()));
    }

    public function testTicketTarifReduitIsNotValidBoolean()
    {
        $this->ticket->setTarifReduit('test');

        $this->assertFalse(is_bool($this->ticket->getTarifReduit()));
    }

    public function testTicketTarifNameIsValidString()
    {
        $this->ticket->setTarifName('Test éàù');

        $this->assertTrue(is_string($this->ticket->getTarifName()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketTarifNameIsNotValidString($name)
    {
        $this->ticket->setTarifName($name);

        $this->assertFalse(is_string($this->ticket->getTarifName()));
    }

    public function testTicketPriceIsValidInt()
    {
        $this->ticket->setPrice(25);

        $this->assertTrue(is_int($this->ticket->getPrice()));
    }

    public function testTicketPriceIsNotValidInt()
    {
        $this->ticket->setPrice('test');

        $this->assertFalse(is_int($this->ticket->getPrice()));
    }

    public function testTicketTypeIsValidString()
    {
        $this->ticket->setTicketType('Test éàù');

        $this->assertTrue(is_string($this->ticket->getTicketType()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketTypeIsNotValidString($name)
    {
        $this->ticket->setTicketType($name);

        $this->assertFalse(is_string($this->ticket->getTicketType()));
    }

    public function testTicketStatusIsValidString()
    {
        $this->ticket->setStatus('Test éàù');

        $this->assertTrue(is_string($this->ticket->getStatus()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketStatusIsNotValidString($name)
    {
        $this->ticket->setStatus($name);

        $this->assertFalse(is_string($this->ticket->getStatus()));
    }

    public function testTicketReservationCodeIsValidString()
    {
        $this->ticket->setReservationCode('Test éàù');

        $this->assertTrue(is_string($this->ticket->getReservationCode()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketReservationCodeIsNotValidString($name)
    {
        $this->ticket->setReservationCode($name);

        $this->assertFalse(is_string($this->ticket->getReservationCode()));
    }

    public function testTicketTransactionIsValidTransaction()
    {
        $this->ticket->setTransaction(new Transaction());

        $this->assertInstanceOf(Transaction::class, $this->ticket->getTransaction());
    }
}