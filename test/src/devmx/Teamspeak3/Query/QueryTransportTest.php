<?php

namespace devmx\Teamspeak3\Query;
use devmx\Transmission\TransmissionStub;
use devmx\Teamspeak3\Query\Transport\Common;
 
class TestTranslator extends Transport\Common\CommandTranslator {
    
}

class TestHandler extends Transport\Common\ResponseHandler {
    
}

/**
 * Test class for QueryTransport.
 * Generated by PHPUnit on 2012-01-23 at 18:26:26.
 */
class QueryTransportTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \devmx\Teamspeak3\Query\QueryTransport
     */
    protected $transport;
    
    /**
     *
     * @var \devmx\Test\Transmission\TransmissionStub 
     */
    protected $transmission;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->transmission = new TransmissionStub('foo', 1337);
        $this->transport = new QueryTransport($this->transmission, new Common\CommandTranslator(), new Common\ResponseHandler());
    }

        
    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::setTranslator
     * @covers devmx\Teamspeak3\Query\QueryTransport::getTranslator
     * @covers devmx\Teamspeak3\Query\QueryTransport::__construct
     */
    public function testSetGetTranslator()
    {
        $this->transport->setTranslator(new TestTranslator());
        $this->assertInstanceOf('\devmx\Teamspeak3\Query\TestTranslator', $this->transport->getTranslator());
    }

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::setHandler
     * @covers devmx\Teamspeak3\Query\QueryTransport::getHandler
     * @covers devmx\Teamspeak3\Query\QueryTransport::__construct
     */
    public function testSetGetHandler()
    {
        $this->transport->setHandler(new TestHandler());
        $this->assertInstanceOf('\devmx\Teamspeak3\Query\TestHandler', $this->transport->getHandler());
    }


    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::getTransmission
     * @covers devmx\Teamspeak3\Query\QueryTransport::__construct
     */
    public function testGetTransmission()
    {
       $this->assertEquals($this->transmission, $this->transport->getTransmission());
    }

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::connect
     * @covers devmx\Teamspeak3\Query\QueryTransport::isConnected
     * @covers devmx\Teamspeak3\Query\QueryTransport::checkWelcomeMessage
     */
    public function testConnect()
    {
        $this->transmission->addToReceive($this->getWelcomeMessage());
        $this->transport->connect();
        $this->assertEquals($this->transmission->getReceived(), $this->getWelcomeMessage());
        $this->assertTrue($this->transport->isConnected());
    }
    
    /**
     * @expectedException \devmx\Teamspeak3\Query\Exception\InvalidServerException
     * @covers devmx\Teamspeak3\Query\QueryTransport::connect 
     */
    public function testFailedConnect() {
        $this->transmission->addToReceive("TS2\n");
        $this->transport->connect();
    }
    
    protected function getWelcomeMessage() {
        $msg = <<<'EOF'
TS3
Welcome to the TeamSpeak 3 ServerQuery interface, type "help" for a list of commands and "help <command>" for information on a specific command.
EOF;
        return $msg."\r\n";
    }
    
    protected function connectTransport() {
        $this->transmission->addToReceive($this->getWelcomeMessage());
        $this->transport->connect();
    }

    
    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::getAllEvents
     */
    public function testGetAllEvents()
    {
        $this->connectTransport();
        $raw = <<<'EOF'
notifyfoo asdf=jklö sdfg=sdf
notifybar asdf=sdff fnord=asd
EOF;
        $this->transmission->setToReceive($raw."\n");
        $this->transmission->errorOnDelay(true);
        $events = $this->transport->getAllEvents();
        $this->assertTrue(is_array($events));
        $this->assertCount(2, $events);
        $this->assertEquals('sdff', $events[1]->getValue('asdf'));
        $this->assertEquals('notifyfoo', $events[0]->getReason());
    }
    

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::sendCommand
     */
    public function testSendCommand()
    {
        $this->connectTransport();
        $raw = "foo=bar asdf=sdg|foo=bar2 asdf=sdg2\nerror id=0 msg=ok\n";
        $this->transmission->setToReceive($raw);
        $cmd = new Command('foo');
        $response = $this->transport->sendCommand($cmd);
        $this->assertEquals($cmd, $response->getCommand());
        $this->assertEquals(0, $response->getErrorID());
        $this->assertEquals(array(array('foo'=>'bar', 'asdf'=>'sdg'), array('foo'=>'bar2', 'asdf'=>'sdg2')), $response->getItems());                
    }
    
    /**
     *@covers devmx\Teamspeak3\Query\QueryTransport::getAllEvents 
     */
    public function testGetAllEvents_DryRun() {
        $this->connectTransport();
        //also cover case where a event occurs after a response which should not be catched
        $raw = <<<'EOF'
notifysomething foo=bar asdf=jklö
notifybar asdf=sdff fnord=asd
foo=bar asdf=sdg|foo=bar2 asdf=sdg2 
error id=0 msg=ok
notifybar asdf=sdfff fnord=asda

EOF;
        $this->transmission->setToReceive($raw);
        $this->assertInstanceOf('\devmx\Teamspeak3\Query\CommandResponse',$this->transport->sendCommand(new Command('foo')));
        $this->transmission->close();
        $events = $this->transport->getAllEvents(true);
        $this->assertCount(2, $events);
        $this->assertEquals(array(array('asdf'=>'sdff', 'fnord'=>'asd')), $events[1]->getItems());
    }

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::query
     */
    public function testQuery()
    {
        $this->connectTransport();
        $this->transmission->setToReceive("error id=0 msg=ok\n");
        $resp = $this->transport->query('foo', array('foo'=>'bar'), array('a'));
        $this->assertEquals('foo', $resp->getCommand()->getName());
        $this->assertEquals('bar', $resp->getCommand()->getParameter('foo'));
        $this->assertTrue($resp->getCommand()->optionIsSet('a'));
    }

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::waitForEvent
     */
    public function testWaitForEvent()
    {
        $this->connectTransport();
        $raw = <<<'EOF'
notifyfoo asdf=jklö sdfg=sdf
notifybar asdf=sdff fnord=asd

EOF;
        $this->transmission->setToReceive($raw);
        $events = $this->transport->waitForEvent();
        $this->assertTrue(is_array($events));
        $this->assertCount(1, $events);
        $this->assertEquals('sdf', $events[0]->getValue('sdfg'));
        $this->assertEquals('notifyfoo', $events[0]->getReason());
    }
    
    public function testWaitForEvent_Timeout() {
        $this->connectTransport();
        $this->transmission->timeout();
        $this->assertEquals(array(), $this->transport->waitForEvent());
    }
    
    /**
     * @expectedException \devmx\Teamspeak3\Query\Exception\NotConnectedException
     */
    public function testExceptionWhenNotConnected_sendCommand() {
        $this->transport->sendCommand(new Command('foo'));
    }
    
    /**
     * @expectedException \devmx\Teamspeak3\Query\Exception\NotConnectedException
     */
    public function testExceptionWhenNotConnected_waitForEvent() {
        $this->transport->waitForEvent();
    }
    
    /**
     * @expectedException \devmx\Teamspeak3\Query\Exception\NotConnectedException
     */
    public function testExceptionWhenNotConnected_getAllEvents() {
        $this->transport->getAllEvents();
    }

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::disconnect
     */
    public function testDisconnect()
    {
        $this->connectTransport();
        $this->transmission->addToReceive("error id=0 msg=ok\n");
        $this->transport->disconnect();
        $this->assertFalse($this->transmission->isEstablished());
        $this->assertEquals("quit\n", $this->transmission->getSentData());
    }

    /**
     * @covers devmx\Teamspeak3\Query\QueryTransport::__clone
     */
    public function test__clone()
    {
        $this->connectTransport();
        $new = clone $this->transport;
        $this->assertEquals(1, TransmissionStub::cloned());
    }

}

?>
