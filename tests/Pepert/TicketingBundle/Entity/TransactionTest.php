<?php

namespace Pepert\Tests;

use Pepert\TicketingBundle\Entity\Ticket;
use Pepert\TicketingBundle\Entity\Transaction;
use DateTime;
use Pepert\TicketingBundle\Entity\User;
use Symfony\Bridge\PhpUnit\DeprecationErrorHandler;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    private $transaction;

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
        $this->transaction = new Transaction();
    }

    public function testTransactionIdIsValidString()
    {
        $this->transaction->setTransactionId('Test éàù');

        $this->assertTrue(is_string($this->transaction->getTransactionId()));
    }

    /**
     * @dataProvider getData
     */
    public function testTransactionIdIsNotValidString($name)
    {
        $this->transaction->setTransactionId($name);

        $this->assertFalse(is_string($this->transaction->getTransactionId()));
    }

    public function testTransactionSystemIsValidString()
    {
        $this->transaction->setTransactionSystem('Test éàù');

        $this->assertTrue(is_string($this->transaction->getTransactionSystem()));
    }

    /**
     * @dataProvider getData
     */
    public function testTransactionSystemIsNotValidString($name)
    {
        $this->transaction->setTransactionSystem($name);

        $this->assertFalse(is_string($this->transaction->getTransactionSystem()));
    }

    public function testTransactionDateIsValidDateTime()
    {
        $this->transaction->setTransactionDate(new DateTime('2015-05-13'));

        $this->assertInstanceOf('DateTime',$this->transaction->getTransactionDate());
    }

    public function testTransactionDateIsNotValidDateTime()
    {
        $this->transaction->setTransactionDate('2015-05-13');

        $this->assertNotInstanceOf('DateTime',$this->transaction->getTransactionDate());
    }

    public function testTransactionTotalPriceIsValidFloat()
    {
        $this->transaction->setTotalPrice(27.50);

        $this->assertTrue(is_float($this->transaction->getTotalPrice()));
    }

    public function testTransactionTotalPriceIsValidInt()
    {
        $this->transaction->setTotalPrice(31);

        $this->assertTrue(is_int($this->transaction->getTotalPrice()));
    }

    public function testTransactionTotalPriceIsNotValidFloat()
    {
        $this->transaction->setTotalPrice('test');

        $this->assertFalse(is_float($this->transaction->getTotalPrice()));
    }

    public function testTransactionUserIsValidUser()
    {
        $this->transaction->setUser(new User());

        $this->assertInstanceOf(User::class, $this->transaction->getUser());
    }

    public function testAddTicketToTransactionIsValid()
    {
        $ticket1 = new Ticket();
        $ticket1->setPrice(1);
        $ticket2 = new Ticket();
        $ticket3 = new Ticket();

        $this->transaction->addTicket($ticket1);
        $this->transaction->addTicket($ticket2);
        $this->transaction->addTicket($ticket3);

        $tickets = $this->transaction->getTickets();

        $this->assertEquals(3,count($tickets));
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertInstanceOf(Ticket::class, $tickets[2]);

        return $tickets;
    }

    /**
     * @depends testAddTicketToTransactionIsValid
     */
    public function testRemoveTicketFromTransactionIsValid($tickets)
    {
        $this->transaction->setTickets($tickets);

        $this->transaction->removeTicket($tickets[0]);

        $this->assertEquals(2,count($this->transaction->getTickets()));

        $this->transaction->removeAllTickets();
        $this->assertEquals(0,count($this->transaction->getTickets()));
    }

    public function testTransactionTicketNumberIsValidInt()
    {
        $this->transaction->setTicketNumber(2);

        $this->assertTrue(is_int($this->transaction->getTicketNumber()));
    }

    public function testTransactionTicketNumberIsNotValidInt()
    {
        $this->transaction->setTicketNumber('test');

        $this->assertFalse(is_int($this->transaction->getTicketNumber()));
    }
}