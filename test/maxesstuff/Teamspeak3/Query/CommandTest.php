<?php

namespace devmx\Teamspeak3\Query;

require_once dirname( __FILE__ ) . '/../../../../devmx/Teamspeak3/Query/Command.php';

/**
 * Test class for Command.
 * Generated by PHPUnit on 2011-08-29 at 18:44:03.
 */
class CommandTest extends \PHPUnit_Framework_TestCase
{

    

   public function testEquals_orderIndependence() {
        $cmd1 = new Command("foo", Array("foo" => true, "asdf" => true), Array("foo" => Array("bar"), "bar" => Array("foo")) );
         
        $cmd2 = new Command("foo", Array("asdf" => true, "foo" => true), Array("bar" => Array("foo"), "foo" => Array("bar")) );
        
        $this->assertTrue($cmd1->equals($cmd2));
             
    }
    
    public function testEquals_otherOptionName() {
        $cmd1 = new Command("foo",
                            Array("foo"=>TRUE,"asdf"=>TRUE), 
                            Array("foo"=>"bar", "bar"=>"foo"));
        
        $cmd2 = new Command("foo", 
                            Array("foo"=>TRUE, "asdfg"=>TRUE), 
                            Array("foo"=>"bar", "bar"=>"foo"));
        $this->assertFalse($cmd1->equals($cmd2));
    }
    
    
    public function testEquals_additionalOption() {
        $cmd1 = new Command("foo",
                            Array("foo"=>TRUE,"asdf"=>TRUE), 
                            Array("foo"=>"bar", "bar"=>"foo"));
        
        $cmd3 = new Command("foo", 
                            Array("foo"=>TRUE, "asdfg"=>TRUE, "asdf"=>TRUE), 
                            Array("foo"=>"bar", "bar"=>"foo"));
        
        $this->assertFalse($cmd1->equals($cmd3));
    }
}

?>
