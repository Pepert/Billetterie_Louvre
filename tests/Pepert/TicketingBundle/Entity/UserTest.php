<?php

namespace Pepert\Tests;

use Pepert\TicketingBundle\Entity\User;
use Pepert\TicketingBundle\Entity\Transaction;
use DateTime;

class UserTest extends \PHPUnit_Framework_TestCase
{
    private $user;

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
        $this->user = new User();
    }

    public function testVisitDayIsValidDateTime()
    {
        $this->user->setVisitDay(new DateTime('2015-05-13'));

        $this->assertInstanceOf('DateTime',$this->user->getVisitDay());
    }

    public function testVisitDayIsNotValidDateTime()
    {
        $this->user->setVisitDay('2015-05-13');

        $this->assertNotInstanceOf('DateTime',$this->user->getVisitDay());
    }

    public function testTicketTypeIsValidString()
    {
        $this->user->setTicketType('Test éàù');

        $this->assertTrue(is_string($this->user->getTicketType()));
    }

    /**
     * @dataProvider getData
     */
    public function testTicketTypeIsNotValidString($name)
    {
        $this->user->setTicketType($name);

        $this->assertFalse(is_string($this->user->getTicketType()));
    }

    public function testTicketNumberIsValidInt()
    {
        $this->user->setTicketNumber(25);

        $this->assertTrue(is_int($this->user->getTicketNumber()));
    }

    public function testTicketNumberIsNotValidInt()
    {
        $this->user->setTicketNumber('test');

        $this->assertFalse(is_int($this->user->getTicketNumber()));
    }

    public function testEmailIsValidString()
    {
        $this->user->setEmail('test@gmail.fr');

        $this->assertTrue(is_string($this->user->getEmail()));
    }

    /**
     * @dataProvider getData
     */
    public function testEmailIsNotValidString($name)
    {
        $this->user->setEmail($name);

        $this->assertFalse(is_string($this->user->getEmail()));
    }

    public function testAddTransactionToUserIsValid()
    {
        $transaction1 = new Transaction();
        $transaction2 = new Transaction();

        $this->user->addTransaction($transaction1);
        $this->user->addTransaction($transaction2);

        $transactions = $this->user->getTransactions();

        $this->assertEquals(2,count($transactions));
        $this->assertInstanceOf(Transaction::class, $transactions[0]);
        $this->assertInstanceOf(Transaction::class, $transactions[1]);

        return $transactions;
    }

    /**
     * @depends testAddTransactionToUserIsValid
     */
    public function testRemoveTransactionFromUserIsValid($transactions)
    {
        $this->user->addTransaction($transactions[0]);
        $this->user->addTransaction($transactions[1]);

        $this->assertEquals(2,count($this->user->getTransactions()));

        $this->user->removeTransaction($transactions[1]);
        $this->assertEquals(1,count($this->user->getTransactions()));
    }
}