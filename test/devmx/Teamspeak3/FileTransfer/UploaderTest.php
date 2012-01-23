<?php

namespace devmx\Teamspeak3\FileTransfer;
use devmx\Test\Transmission\TransmissionStub;
require_once dirname( __FILE__ ) . '/../../../../devmx/Teamspeak3/FileTransfer/Uploader.php';

/**
 * Test class for Uploader.
 * Generated by PHPUnit on 2012-01-23 at 12:29:43.
 */
class UploaderTest extends \PHPUnit_Framework_TestCase
{


   
    /**
     *Test whole transfer proccess 
     */
    public function testTransfer()
    {
        $transmission = new TransmissionStub('foobar', 212);
        $data = "this is a\n file". \str_repeat("asbd", 5124);
        $uploader = new Uploader($transmission, 'foobar', $data);
        $this->assertEquals('', $transmission->getReceived());
        $this->assertEquals('', $transmission->getSentData());
        $this->assertFalse($transmission->isEstablished());
        $uploader->transfer();
        $this->assertEquals('foobar'.$data,$transmission->getSentData());
    }

}

?>
