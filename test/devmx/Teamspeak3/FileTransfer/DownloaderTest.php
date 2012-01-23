<?php

namespace devmx\Teamspeak3\FileTransfer;
use devmx\Test\Transmission\TransmissionStub;
require_once dirname( __FILE__ ) . '/../../../../devmx/Teamspeak3/FileTransfer/Downloader.php';

/**
 * Test class for Downloader.
 * Generated by PHPUnit on 2012-01-23 at 12:14:42.
 */
class DownloaderTest extends \PHPUnit_Framework_TestCase
{

    

    /**
     * @todo Implement testTransfer().
     */
    public function testTransfer()
    {
       $transmission = new TransmissionStub('foo', 30033);
       $toRead = "this is a file\n with newlines in it \n";
       $downloader = new Downloader($transmission, 'foobar', strlen($toRead));
       $this->assertEquals('', $transmission->getReceived());
       $this->assertEquals('', $transmission->getSentData());
       $transmission->setToReceive($toRead);
       $file = $downloader->transfer();
       $this->assertEquals($toRead, $file);
       $this->assertEquals('foobar', $transmission->getSentData());
    }

}

?>
