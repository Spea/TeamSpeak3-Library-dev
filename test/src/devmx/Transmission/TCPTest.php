<?php

namespace devmx\Transmission;

require_once dirname( __FILE__ ) . '/../../../../src/devmx/Transmission/TCP.php';

class MockedTcp extends TCP {
    protected $establishStatus = true;
    protected $errnoToReturn = 0;
    protected $errmsgToReturn = '';
    
    public function establishStatus($status, $errno=0, $errmsg='') {
        $this->establishStatus = $status;
        $this->errnoToReturn = $errno;
        $this->errmsgToReturn = $errmsg;
    }
    protected function open($host, $port, &$errno, &$errmsg, $timeout) {
        $this->stream = $this->establishStatus;
        $errno = $this->errnoToReturn;
        $errmsg = $this->errmsgToReturn;
    }
}

/**
 * Test class for TCP.
 * Generated by PHPUnit on 2012-03-02 at 21:56:09.
 */
class TCPTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \devmx\Transmission\MockedTcp
     */
    protected $tcp;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->tcp = $this->getMockBuilder('\devmx\Transmission\MockedTcp')
                            ->setConstructorArgs( array('foo', 9987, 19, 21))
                            ->setMethods( array('setTimeOut', 'getLine', 'setBlocking', 'write', 'closeStream') )
                            ->getMock();
    }

    
    /**
     * @covers devmx\Transmission\TCP::establish
     * @covers devmx\Transmission\TCP::isEstablished
     * @covers devmx\Transmission\TCP::__construct
     */
    public function testEstablish()
    {
        $this->establish();
        $this->assertTrue($this->tcp->isEstablished());
    }
    
    /**
     * @covers devmx\Transmission\TCP::establish
     * @expectedException \RunTimeException
     */
    public function testEstablish_Error() {
        $this->tcp->establishStatus(false, 123, 'oops!');
        $this->tcp->establish();
    }
    
    /**
     * @covers devmx\Transmission\TCP::establish
     */
    public function testReEstablish() {
        $this->tcp->establishStatus('asdf');
        $this->tcp->establish();
        $this->tcp->establishStatus('foo');
        $this->tcp->establish();
        $this->assertEquals('asdf', $this->tcp->getStream());
    }
    
    /**
     * @covers devmx\Transmission\TCP::establish
     */
    public function testReEstablish_Force() {
        $this->tcp->establishStatus('asdf');
        $this->tcp->establish();
        $this->tcp->establishStatus('foo');
        $this->tcp->establish(-1, true);
        $this->assertEquals('foo', $this->tcp->getStream());
    }
    
    /**
     *Establishes a connection on the tcp object 
     */
    protected function establish() {
        $this->tcp->establishStatus(true);
        $this->tcp->establish();
    }
    
    /**
     * @covers devmx\Transmission\TCP::close
     * @covers devmx\Transmission\TCP::isEstablished
     */
    public function testClose()
    {
        $this->establish();
        $this->tcp->expects($this->once())
                   ->method('closeStream')
                   ->will($this->returnValue(true));
        $this->tcp->close();
        $this->assertFalse($this->tcp->isEstablished());
    }

    

    /**
     * @covers devmx\Transmission\TCP::getHost
     * @covers devmx\Transmission\TCP::__construct
     */
    public function testGetHost()
    {
        $this->assertEquals('foo', $this->tcp->getHost());
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @covers devmx\Transmission\TCP::setHost
     */
    public function testSetHost_InvalidHost() {
        new TCP('  ', 9987);
    }

    /**
     * @covers devmx\Transmission\TCP::getPort
     * @covers devmx\Transmission\TCP::__construct
     */
    public function testGetPort()
    {
        $this->assertEquals(9987, $this->tcp->getPort());
    }
    
    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidPortProvider
     * @covers devmx\Transmission\TCP::setPort 
     */
    public function testSetPort_InvalidPorts($port) {
        new TCP('foo',$port);
    }
    
    public function invalidPortProvider() {
        return array(
            array(-1),
            array(789900),
            array(0),
            array(-12)
        );
    }

    
    /**
     * @covers devmx\Transmission\TCP::receiveLine
     * @expectedException \BadMethodCallException
     */
    public function testReceiveLine_ExceptionWhenNotEstablished()
    {
        $this->tcp->receiveLine();
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveLine
     * @covers devmx\Transmission\TCP::checkTimeOut
     */
    public function testReceiveLine()
    {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->once())
                    ->method('getLine')
                    ->with($this->equalTo(4096))
                    ->will($this->returnValue("foobar\n"));
        $this->assertEquals("foobar\n", $this->tcp->receiveLine());
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveLine
     * @covers devmx\Transmission\TCP::checkTimeOut 
     */
    public function testReceiveLine_CustomParams() {
        $this->establish();
        $this->expectTimeout(22, 23);
        $this->tcp->expects($this->once())
                    ->method('getLine')
                    ->with($this->equalTo(4097))
                    ->will($this->returnValue("foobar\n"));
        $this->assertEquals("foobar\n", $this->tcp->receiveLine(4097, 22, 23));
    }

    /**
     * @covers devmx\Transmission\TCP::getAll
     * @todo Implement testGetAll().
     */
    public function testGetAll()
    {
       $this->establish();
       $this->tcp->expects($this->exactly(2))
                  ->method('setBlocking');
       $this->tcp->expects($this->at(0))
                   ->method('setBlocking')
                  ->with($this->equalTo(0));
       $this->tcp->expects($this->exactly(3))
                  ->method('getLine')
                  ->with($this->equalTo(8094))
                  ->will($this->onConsecutiveCalls('foo', "bar\n", ''));
       $this->tcp->expects($this->at(4))
                  ->method('setBlocking')
                  ->with($this->equalTo(1));
       $this->assertEquals("foobar\n", $this->tcp->getAll());
    }
    
    /**
     * @expectedException \BadMethodCallException 
     */
    public function testReceiveData_ExceptionWhenNotEstablished() {
        $this->tcp->receiveData(123);
    }

    /**
     * @covers devmx\Transmission\TCP::receiveData
     * @covers devmx\Transmission\TCP::checkTimeOut
     */
    public function testReceiveData()
    {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->exactly(2))
                  ->method('getLine')
                  ->with($this->equalTo(4))
                  ->will($this->onConsecutiveCalls('aa', 'bb'));
        $this->assertEquals('aabb', $this->tcp->receiveData(4));
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveData
     * @covers devmx\Transmission\TCP::checkTimeOut
     */
    public function testReceiveData_CustomTimeout() {
        $this->establish();
        $this->expectTimeout(34, 23);
        $this->tcp->expects($this->exactly(2))
                  ->method('getLine')
                  ->with($this->equalTo(4))
                  ->will($this->onConsecutiveCalls('aa', 'bb'));
        $this->assertEquals('aabb', $this->tcp->receiveData(4, 34, 23));
    }
    
    /**
     * @covers devmx\Transmission\TCP::getMaxTries
     * @covers devmx\Transmission\TCP::setMaxTries
     * @covers devmx\Transmission\TCP::receiveData
     * @expectedException \RuntimeException
     */
    public function testReceiveData_MaxTries() {
        $this->establish();
        $this->tcp->setMaxTries(2);
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->exactly(2))
                  ->method('getLine')
                  ->with($this->equalTo(5))
                  ->will($this->onConsecutiveCalls('aa', 'bb'));
        $this->tcp->receiveData(5);
    }

    /**
     * @covers devmx\Transmission\TCP::send
     * @expectedException \BadMethodCallException
     */
    public function testSend_ExceptionWhenNotEstablished()
    {
        $this->tcp->send('foo');
    }
    
    /**
     * @covers devmx\Transmission\TCP::send
     */
    public function testSend() {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->once())
                  ->method('write')
                  ->with($this->equalTo('foobar'))
                  ->will($this->returnValue(6));
        $this->tcp->send('foobar');
    }
    
    public function testSend_MultipleAttemps() {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->at(1))
                  ->method('write')
                  ->with($this->equalTo('foobar'))
                  ->will($this->returnValue(3));
        $this->tcp->expects($this->at(2))
                  ->method('write')
                  ->with($this->equalTo('bar'))
                  ->will($this->returnValue(3));
        $this->tcp->send('foobar');
    }
    
    /**
     * @covers devmx\Transmission\TCP::send
     * @expectedException \RuntimeException
     */
    public function testSend_MaxTries() {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->setMaxTries(1);
        $this->tcp->expects($this->at(1))
                  ->method('write')
                  ->with($this->equalTo('foobar'))
                  ->will($this->returnValue(3));
        $this->tcp->send('foobar');
    }
    
    /**
     * @covers devmx\Transmission\TCP::send 
     */
    public function testSend_CustomTimeOut() {
        $this->establish();
        $this->expectTimeout( 23, 42 );
        $this->tcp->expects($this->once())
                  ->method('write')
                  ->with($this->equalTo('foobar'))
                  ->will($this->returnValue(6));
        $this->tcp->send('foobar', 23, 42);
    }

    /**
     * @covers devmx\Transmission\TCP::getStream
     */
    public function testGetStream()
    {
        $this->tcp->establishStatus('foobar');
        $this->tcp->establish();
        $this->assertEquals('foobar', $this->tcp->getStream());
    }
    
    protected function expectDefaultTimeout() {
        $this->expectTimeout(19, 21);
    }
    
    protected function expectTimeout($seconds, $micro) {
        $this->tcp->expects($this->once())
                  ->method('setTimeOut')
                  ->with($this->equalTo($seconds), $this->equalTo($micro));
    }

}

?>