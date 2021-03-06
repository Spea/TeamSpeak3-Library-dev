<?php

namespace devmx\Teamspeak3\Query\Transport\Decorator;
use devmx\Teamspeak3\Query\Command;
use devmx\Teamspeak3\Query\CommandResponse;
use devmx\Teamspeak3\Query\Event;
require_once dirname( __FILE__ ) . '/../../../../../../../src/devmx/Teamspeak3/Query/Transport/Decorator/DebuggingDecorator.php';

/**
 * Test class for DebuggingDecorator.
 * Generated by PHPUnit on 2012-01-25 at 15:58:26.
 */
class DebuggingDecoratorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator
     */
    protected $debug;
    /**
     * @var \devmx\Test\Teamspeak3\Query\Transport\QueryTransportStub
     */
    protected $transport;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->transport = new \devmx\Teamspeak3\Query\Transport\QueryTransportStub();
        $this->debug = new DebuggingDecorator($this->transport);
    }
    
    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::connect
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::disconnect
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::isConnected
     */
    public function testConnectDisconnect()
    {
        $this->debug->connect();
        $this->assertTrue($this->debug->isConnected());
        $this->debug->disconnect();
        $this->assertFalse($this->debug->isConnected());
    }

    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getAllEvents
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getReceivedEvents
     */
    public function testGetAllEvents_simple()
    {
        $e = new \devmx\Teamspeak3\Query\Event('somereason', array());
        $e2 = new \devmx\Teamspeak3\Query\Event('somereason', array());
        $this->transport->addEvent($e);
        $this->transport->addEvent($e2);
        $this->debug->connect();
        $this->assertEquals(array($e, $e2), $this->debug->getAllEvents());
    }
    
    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getAllEvents
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getReceivedEvents
     */
    public function testGetAllEvents_charge() {
        $e = new \devmx\Teamspeak3\Query\Event('somereason', array());
        $e2 = new \devmx\Teamspeak3\Query\Event('somereason', array());
        $this->transport->addEvent($e);
        $this->transport->addEvent($e2, 1, true);
        $this->debug->connect();
        $this->assertEquals(array($e), $this->debug->getAllEvents());
    }

    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getSentCommands
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getReceivedResponses
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::sendCommand
     */
    public function testSendCommand()
    {
        $cmd1 = new Command('foo');
        $cmd2 = new Command('bar');
        $r1 = new CommandResponse($cmd1, array('foo'=>'bar'));
        $r2 = new CommandResponse($cmd2, array('foo'=>'bar2'));
        $this->transport->addResponse($r1);
        $this->transport->addResponse($r2);
        
        $this->debug->connect();
        $this->assertEquals($r1, $this->debug->sendCommand($cmd1));
        $this->assertEquals($r2, $this->debug->sendCommand($cmd2));
        $this->assertEquals(array($r1, $r2), $this->debug->getReceivedResponses());
        $this->assertEquals(array($cmd1, $cmd2), $this->debug->getSentCommands());
    }

    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::query
     */
    public function testQuery()
    {
        $cmd1 = new Command('foo', array('a'=>'b'));
        $cmd2 = new Command('bar');
        $r1 = new CommandResponse($cmd1, array('foo'=>'bar'));
        $r2 = new CommandResponse($cmd2, array('foo'=>'bar2'));
        $this->transport->addResponse($r1);
        $this->transport->addResponse($r2);
        
        $this->debug->connect();
        $this->assertEquals($r1, $this->debug->query('foo', array('a'=>'b')));
        $this->assertEquals($r2, $this->debug->query('bar'));
        $this->assertEquals(array($r1, $r2), $this->debug->getReceivedResponses());
        $this->assertEquals(array($cmd1, $cmd2), $this->debug->getSentCommands());
    }

    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::waitForEvent
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getReceivedEvents
     */
    public function testWaitForEvent()
    {
        $e = new Event('foo', array('a'=>'b'));
        $this->transport->addEvent($e);
        $this->debug->connect();
        $this->assertEquals(array($e), $this->debug->waitForEvent());
        $this->assertEquals(array($e), $this->debug->getReceivedEvents());
    }
    

    
    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getOpenedConnections
     */
    public function testGetOpenedConnections()
    {
        for($i=0; $i<12; $i++) {
            $this->debug->connect();
        }
        $this->assertEquals(12, $this->debug->getOpenedConnections());
    }

    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getClosedConnections
     */
    public function testGetClosedConnections()
    {
        for($i=0; $i<13; $i++) {
            $this->debug->disconnect();
        }
        $this->assertEquals(13, $this->debug->getClosedConnections());
    }

    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getSentCommands
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getReceivedResponses
     */
    public function testGetSentCommandsAndReceivedResponses()
    {
        $cmd = new Command('foo');
        $cmd2 = new Command('bar', array('a'=>'b'));
        $r1 = new \devmx\Teamspeak3\Query\CommandResponse($cmd, array());
        $r2 = new \devmx\Teamspeak3\Query\CommandResponse($cmd2, array());
        $this->transport->addResponse($r1);
        $this->transport->addResponse($r2);
        $this->debug->connect();
        $this->debug->sendCommand($cmd2);
        $this->debug->sendCommand($cmd);
        $this->assertEquals(array($cmd2, $cmd), $this->debug->getSentCommands());
        $this->assertEquals(array($r2, $r1), $this->debug->getReceivedResponses());
    }
     
    /**
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::getNumberOfClones
     * @covers devmx\Teamspeak3\Query\Transport\Decorator\DebuggingDecorator::__clone
     */
    public function testGetTotalClones()
    {
        $this->assertEquals(0, DebuggingDecorator::getNumberOfClones());
        $cloned = clone $this->debug;
        $this->assertEquals(1, DebuggingDecorator::getNumberOfClones());
    }

    
}

?>
