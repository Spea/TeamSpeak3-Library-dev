<?php
/*
  This file is part of TeamSpeak3 Library.

  TeamSpeak3 Library is free software: you can redistribute it and/or modify
  it under the terms of the GNU Lesser General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  TeamSpeak3 Library is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public License
  along with TeamSpeak3 Library. If not, see <http://www.gnu.org/licenses/>.
 */
namespace devmx\Teamspeak3\Query\Transport\Decorator;
use devmx\Teamspeak3\Query\Transport\AbstractQueryDecorator;
use devmx\Teamspeak3\Query\Command;

/**
 * This decorator for a QueryTransportInterface provides some useful information such as the number of total openend connections
 * or the sent commands or rather the received responses
 * @author drak3
 */
class DebuggingDecorator extends AbstractQueryDecorator
{
    /**
     * The number of opened connections
     * @var int
     */
    protected $openedConnections;
    
    /**
     * The number of total closed connections
     * @var int
     */
    protected $closedConnections;
    
    /**
     * Array of all commands sent
     * @var array of \devmx\Teamspeak3\Query\Command
     */
    protected $sentCommands = array();
    
    /**
     * Array of all responses received
     * @var array of \devmx\Teamspeak3\Query\CommandResponse
     */
    protected $receivedResponses = array();
    
    /**
     * Array of all received events
     * @var array of \devmx\Teamspeak3\Query\Event
     */
    protected $receivedEvents = array();
    
    /**
     * The number of existing clones
     * @var int
     */
    static protected $cloned = 0;
    
    /**
     * Returns the number of connections opened
     * @return int
     */
    public function getOpenedConnections()
    {
        return $this->openedConnections;
    }
    
    /**
     * Returns the number of connections closed
     * @return type 
     */
    public function getClosedConnections()
    {
        return $this->closedConnections;
    }
    
    /**
     * Returns all commands sent over this query
     * @return array of \devmx\Teamspeak3\Query\Command
     */
    public function getSentCommands()
    {
        return $this->sentCommands;
    }
    
    /**
     * Returns all received responses
     * @return array of \devmx\Teamspeak3\Query\CommandResponse
     */
    public function getReceivedResponses()
    {
        return $this->receivedResponses;
    }
    
    /**
     * Returns all received events
     * @return array of \devmx\Teamspeak3\Query\Event
     */
    public function getReceivedEvents()
    {
        return $this->receivedEvents;
    }
    
    /**
     * Returns the number of clones of this class
     * @return int
     */
    static public function getNumberOfClones()
    {
        return self::$cloned;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        $this->openedConnections++;
        return $this->decorated->connect();
    }
    
    /**
     * {@inheritdoc} 
     */
    public function disconnect()
    {
        $this->closedConnections++;
        return $this->decorated->disconnect();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAllEvents()
    {
        $events = $this->decorated->getAllEvents();
        $this->receivedEvents = array_merge($this->receivedEvents, $events);
        return $events;
    }
    
    /**
     * {@inheritdoc}
     */
    public function sendCommand(\devmx\Teamspeak3\Query\Command $command)
    {
        $response = $this->decorated->sendCommand($command);
        $this->sentCommands[] = $command;
        $this->receivedResponses[] = $response;
        return $response;
    }
    
    /**
     * {@inheritdoc}
     */
    public function query($name, array $args=array(), array $options=array()) {
        $response = $this->decorated->query($name , $args , $options);
        $this->sentCommands[] = new Command($name, $args, $options);
        $this->receivedResponses[] = $response;
        return $response;
    }
    
    /**
     * {@inheritdoc}
     */
    public function waitForEvent()
    {
        $events = $this->decorated->waitForEvent();
        $this->receivedEvents = array_merge($this->receivedEvents, $events);
        return $events;
    }
    
    /**
     * {@inheritdoc}
     */
    public function __clone() {
        $this->decorated = clone $this->decorated;
        self::$cloned++;
    }
    
}

?>
