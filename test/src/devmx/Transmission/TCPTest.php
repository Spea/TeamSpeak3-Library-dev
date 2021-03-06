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
                          ->setMethods(array('closeStream', 'setTimeout', 'getLine', 'setBlocking','write', 'hasEof'))
                          ->setConstructorArgs( array('foo', 9987, 19.21))
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
     * @expectedException \devmx\Transmission\Exception\EstablishingFailedException
     * @expectedExceptionMessage Cannot establish connection to tcp://foo:9987: Error 123 with message "oops!"
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
        $this->tcp->expects($this->once())->method('closeStream')->will($this->returnValue(true));
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
     * @expectedException \devmx\Transmission\Exception\InvalidHostException
     * @dataProvider invalidHostProvider
     * @covers devmx\Transmission\TCP::setHost
     */
    public function testSetHost_InvalidHost($host) {
        new Tcp($host, 9987);
    }
    
    public function invalidHostProvider() {
        return array(
            array('  '),
            array('.'),
            array('foo.bar.'),
            array('foo bar'),
            array('a-'),
            array('b-.b-'),
        );
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
     * @dataProvider invalidPortProvider
     * @covers devmx\Transmission\TCP::setPort 
     */
    public function testSetPort_InvalidPorts($port) {
        try {
           new TCP('foo',$port); 
        }
        catch(\Exception $e) {
            $this->assertInstanceOf('\devmx\Transmission\Exception\InvalidPortException', $e);
            $this->assertEquals(sprintf('Port %s is invalid, valid port must be between 0 and 65535', $port), $e->getMessage());
            return;
        }
        $this->fail("No Exception thrown");
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
     * @expectedException \devmx\Transmission\Exception\LogicException
     */
    public function testReceiveLine_ExceptionWhenNotEstablished()
    {
        $this->tcp->receiveLine();
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveLine
     * @covers devmx\Transmission\TCP::requiresTimeout
     * @covers devmx\Transmission\TCP::getTimeout
     */
    public function testReceiveLine()
    {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->once())
                    ->method('getLine')
                    ->will($this->returnValue("foobar\n"));
        $this->assertEquals("foobar\n", $this->tcp->receiveLine());
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveLine
     * @covers devmx\Transmission\TCP::requiresTimeout
     * @covers devmx\Transmission\TCP::getTimeout
     */
    public function testReceiveLine_CustomParams() {
        $this->establish();
        $this->expectTimeout(22, 23);
        $this->tcp->expects($this->once())
                    ->method('getLine')
                    ->will($this->returnValue("foobar\n"));
        $this->assertEquals("foobar\n", $this->tcp->receiveLine(22.23));
    }
    
    /**
     * @covers \devmx\Transmission\TCP::receiveLine
     */
    public function testReceiveLine_Timeout() {
        $this->establish();
        $this->expectTimeout(23, 23);
        $this->tcp->expects($this->exactly(2))
                  ->method('getLine')
                  ->will($this->onConsecutiveCalls('asdf', ''));
        try {
           $this->tcp->receiveLine(23.23); 
        } catch (\devmx\Transmission\Exception\TimeoutException $e) {
            $this->assertEquals(23.23, $e->getTimeout());
            $this->assertEquals('asdf', $e->getData());
            return;
        }
        $this->fail('No TimeoutException thrown');
        
    }

    /**
     * @covers devmx\Transmission\TCP::checkForData
     */
    public function testCheckForData()
    {
       $this->establish();
       $this->tcp->expects($this->exactly(2))
                  ->method('setBlocking');
       $this->tcp->expects($this->at(1))
                  ->method('setBlocking')
                  ->with($this->equalTo(0))
                  ->will($this->returnValue(true));
       $this->tcp->expects($this->exactly(3))
                  ->method('getLine')
                  ->with($this->equalTo(8094))
                  ->will($this->onConsecutiveCalls('foo', "bar\n", ''));
       $this->tcp->expects($this->at(5))
                  ->method('setBlocking')
                  ->with($this->equalTo(1));
       $this->assertEquals("foobar\n", $this->tcp->checkForData());
    }
    
    /**
     * @expectedException \devmx\Transmission\Exception\LogicException
     */
    public function testReceiveData_ExceptionWhenNotEstablished() {
        $this->tcp->receiveData(123);
    }

    /**
     * @covers devmx\Transmission\TCP::receiveData
     * @covers devmx\Transmission\TCP::requiresTimeout
     * @covers devmx\Transmission\TCP::getTimeout
     */
    public function testReceiveData()
    {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->at(2))
                  ->method('getLine')
                  ->with($this->equalTo(4))
                  ->will($this->returnValue('aa'));
        $this->tcp->expects($this->at(3))
                  ->method('getLine')
                  ->with($this->equalTo(2))
                  ->will($this->returnValue('bb'));
        $this->assertEquals('aabb', $this->tcp->receiveData(4));
        
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveData
     * @covers devmx\Transmission\TCP::requiresTimeout
     * @covers devmx\Transmission\TCP::getTimeout
     */
    public function testReceiveData_CustomTimeout() {
        $this->establish();
        $this->expectTimeout(34, 23);
        $this->tcp->expects($this->at(2))
                  ->method('getLine')
                  ->with($this->equalTo(4))
                  ->will($this->returnValue('aa'));
        $this->tcp->expects($this->at(3))
                  ->method('getLine')
                  ->with($this->equalTo(2))
                  ->will($this->returnValue('bb'));
        $this->assertEquals('aabb', $this->tcp->receiveData(4, 34.23));
    }
    
    /**
     * @covers devmx\Transmission\TCP::receiveData
     */
    public function testReceiveData_Timeout() {
        $this->establish();
        $this->expectTimeout(34, 23);
        $this->tcp->expects($this->exactly(2))
                  ->method('getLine')
                  ->will($this->onConsecutiveCalls('a', ''));
        try {
            $this->tcp->receiveData(3, 34.23);
        } catch(\devmx\Transmission\Exception\TimeoutException $e) {
            $this->assertEquals(34.23, $e->getTimeout());
            $this->assertEquals('a', $e->getData());
            return;
        }
        $this->fail('No TimeOutException thrown');
    }
    
    /**
     * @covers devmx\Transmission\TCP::send
     * @expectedException \devmx\Transmission\Exception\LogicException
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
    
    /**
     * @covers devmx\Transmission\TCP::send
     */
    public function testSend_MultipleAttemps() {
        $this->establish();
        $this->expectDefaultTimeout();
        $this->tcp->expects($this->at(2))
                  ->method('write')
                  ->with($this->equalTo('foobar'))
                  ->will($this->returnValue(3));
        $this->tcp->expects($this->at(3))
                  ->method('write')
                  ->with($this->equalTo('bar'))
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
        $this->tcp->send('foobar', 23.42);
    }
    
    public function testSend_Timeout() {
        $this->establish();
        $this->expectTimeout(1,20);
        $this->tcp->expects($this->exactly(2))
                  ->method('write')
                  ->will($this->onConsecutiveCalls(3, 0));
        try {
            $this->tcp->send('asdfg',1.2);
        } catch (\ devmx\Transmission\Exception\TimeoutException $e) {
            $this->assertEquals(1.2, $e->getTimeout());
            $this->assertEquals('fg', $e->getData());
            return;
        }
        $this->fail('No TimeoutException was thrown');
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
    
    /**
     * @expectedException \devmx\Transmission\Exception\RuntimeException
     */
    public function testCheckConnection_onAction() {
        $this->establish();
        $this->tcp->expects($this->once())
                  ->method('hasEof')
                  ->will($this->returnValue(true));
        $this->tcp->receiveData(123);
    }
    
    
    public function testCheckConnection_onTimeout() {
        $this->establish();
        $this->tcp->expects($this->exactly(2))
                  ->method('getLine')
                  ->will($this->onConsecutiveCalls('a', ''));
        $this->tcp->expects($this->exactly(2))
                  ->method('hasEof')
                  ->will($this->onConsecutiveCalls(false, true));
        try  {
            $this->tcp->receiveData(12);
        } catch(Exception\RuntimeException $e) {
            $this->assertEquals("Connection to foo:9987 was closed by foreign host.", $e->getMessage());
            return;
        }
        $this->fail("no exception thrown");
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
