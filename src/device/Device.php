<?php
namespace nerdmann\react\phenocean\device;

use Evenement\EventEmitter;
use nerdmann\react\phenocean\EnoceanStream;
use nerdmann\react\phenocean\packet\Packet;
use React\EventLoop\LoopInterface;
use nerdmann\react\phenocean\telegram\Telegram;

abstract class Device extends EventEmitter
{

    private $deviceID;

    private $deviceType;

    protected $stream;

    protected $loop;

    public function __construct(array $id, array $type)
    {
        $this->deviceID = $id;
        $this->deviceType = $type;
    }

    public function setEnoceanStream(EnoceanStream $stream, LoopInterface $loop)
    {
        $this->stream = $stream;
        $this->loop = $loop;
        
        $stream->on("packet", function ($packet) {
            $this->checkPacket($packet);
        });
    }

    public function checkPacket(Packet $packet)
    {
        if (is_subclass_of($packet, "nerdmann\\react\\phenocean\\telegram\\Telegram")) {
            
            if ($packet->getSenderID() == $this->deviceID) {
                $this->getMessage($packet);
            }
        }
    }

    abstract protected function getMessage(Packet $packet);

    protected function sendTelegram(Telegram $telegram)
    {
        $telegram->setDestinationID($this->deviceID);
        // echo "SEND:".$telegram."\n";
        $this->stream->sendPacket($telegram);
    }

    public static function isValidID($id, $length)
    {
        if (! is_array($id))
            return false;
        if (count($id) != $length)
            return false;
        foreach ($id as $i) {
            if (! is_int($i) || $i < 0 || $i > 255)
                return false;
        }
        return true;
    }
}

