<?php
namespace nerdmann\react\phenocean;

use Evenement\EventEmitter;
use React\EventLoop\Factory;
use React\Stream\DuplexResourceStream;
use React\Stream\Util;
use Exception;
use nerdmann\react\phenocean\packet\Packet;
use nerdmann\react\phenocean\packet\PacketFactory;
use nerdmann\react\phenocean\device\DeviceFactory;
require __DIR__ . "/../vendor/autoload.php";

class EnoceanStream extends EventEmitter
{

    private $port;

    private $loop;

    private $input;

    private $packetFactory;

    public function __construct($port, $loop)
    {
        $this->port = $port;
        $this->loop = $loop;
        $this->buffer = array();
        $this->packetFactory = new PacketFactory();
        
        exec("stty -F " . $this->port . " 57600 raw");
        $s = fopen($this->port, "r+");
        
        $this->input = new DuplexResourceStream($s, $this->loop);
        $this->input->on("data", function ($chunk) {
            $this->addDataToBuffer($chunk);
            $this->searchPacketinBuffer();
        });
        
        $this->input->on("error", function (Exception $error) {
            echo $e->getMessage();
        });
        $this->input->on("end", function () {
            echo "ende";
        });
        
        Util::forwardEvents($this->input, $this, array(
            'end',
            'error',
            'close',
            'pipe',
            'drain'
        ));
    }

    public function sendPacket(Packet $packet)
    {
        $stringToSend = "";
        foreach ($packet->getRawPacket() as $byte)
            $stringToSend .= chr($byte);
        
        if (! $this->input->write($stringToSend))
            echo "ERROR WRITE";
    }

    private function addDataToBuffer(string $data): void
    {
        // String to array
        $this->hex_dump($data);
        foreach (str_split($data) as $char)
            $this->buffer[] = ord($char);
    }

    private function searchPacketinBuffer()
    {
        if ($syncByteKey = array_search(0x55, $this->buffer) === false) {
            // only garbage, delete and continue
            $this->reset();
        }
        if ($syncByteKey > 0) {
            // Delete garbage at the beginning
            array_splice($this->buffer, 0, $syncByteKey);
        }
        if (count($this->buffer) >= 6) {
            // Header handling
            try {
                $this->packetFactory->setHeader(array_slice($this->buffer, 1, 4), array_slice($this->buffer, 5, 1));
            } catch (EnoceanProtocolException $e) {
                // The 0x55 was not a sync byte. We have to wait for another 0x55 and hope that this is a sync byte
                // TODO: We should also check if in the 5 bytes of the "header" is a sync byte
                $this->reset(6);
                return;
            }
        }
        $packetLength = (6 + $this->packetFactory->getDataLength() + $this->packetFactory->getOptDataLength() + 1);
        if (count($this->buffer) >= $packetLength) {
            try {
                $data = array_slice($this->buffer, 6, $this->packetFactory->getDataLength());
                $optData = array_slice($this->buffer, 6 + $this->packetFactory->getDataLength(), $this->packetFactory->getOptDataLength());
                $crc = array_slice($this->buffer, 6 + $this->packetFactory->getDataLength() + $this->packetFactory->getOptDataLength(), 1);
                $this->packetFactory->setData($data, $optData, $crc);
            } catch (EnoceanProtocolException $e) {
                // The data checksum was not correct... Should not happen but we will ignore it
                $this->reset($packetLength);
            }
            $this->emit("packet", array(
                $this->packetFactory->getPacket()
            ));
            
            // Remove complete packet from buffer
            $this->reset($packetLength);
            // Maybe we have another complete packet in buffer?
            $this->searchPacketinBuffer();
        }
    }

    private function reset(int $length = PHP_INT_MAX): void
    {
        array_splice($this->buffer, 0, $length);
        $this->packetFactory = new PacketFactory();
    }

    private function hex_dump($data, $newline = "\n")
    {
        static $from = '';
        static $to = '';
        
        static $width = 16; // number of bytes per line
        
        static $pad = '.'; // padding for non-visible characters
        
        if ($from === '') {
            for ($i = 0; $i <= 0xFF; $i ++) {
                $from .= chr($i);
                $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
            }
        }
        
        $hex = str_split(bin2hex($data), $width * 2);
        $chars = str_split(strtr($data, $from, $to), $width);
        
        $offset = 0;
        foreach ($hex as $i => $line) {
            echo sprintf('%6X', $offset) . ' : ' . implode(' ', str_split($line, 2)) . ' [' . $chars[$i] . ']' . $newline;
            $offset += $width;
        }
    }
}

$loop = Factory::create();
$stream = new EnoceanStream("/dev/enocean", $loop);

$dev = DeviceFactory::create(array(
    0xD2,
    0x01,
    0x12
), array(
    0x05,
    0x85,
    0x2B,
    0x9A
), $stream, $loop);
$dev2 = DeviceFactory::create(array(
    0xD2,
    0x01,
    0x12
), array(
    0x05,
    0x84,
    0xe0,
    0x72
), $stream, $loop);
$blueRocker = DeviceFactory::create(array(
    0xF6,
    0x02,
    0x02
), array(
    0x00,
    0x35,
    0x8B,
    0x77
), $stream, $loop);
$meterPlug = DeviceFactory::create(array(
    0xA5,
    0x12,
    0x01
), array(
    0xFF,
    0xF2,
    0x7D,
    0x01
), $stream, $loop);
$switchPlug = DeviceFactory::create(array(
    0xFF,
    0xFF,
    0x01
), array(
    0xFF,
    0xF2,
    0x7D,
    0x00
), $stream, $loop);

$dev->on("event", function (Event $event) use ($dev) {
    switch ($event->getType()) {
        case "pairing":
            $dev->pair($event->getProperty("request"));
    }
});

$blueRocker->on("event", function (Event $event) use ($dev) {
    if ($event->getProperty("pressed1")) {
        switch ($event->getProperty("rocker1")) {
            case 0:
                $dev->setActuator(0x00, 0x64);
                break;
            case 1:
                $dev->setActuator(0x00, 0x00);
                break;
            case 2:
                $dev->setActuator(0x01, 0x64);
                break;
            case 3:
                $dev->setActuator(0x01, 0x00);
                break;
        }
    }
});

$meterPlug->on("event", function (Event $event) {
    print_r($event);
});
$switchPlug->on("event", function (Event $event) {
        print_r($event);
    });

$stream->on("packet", function (Packet $packet) {
    // echo "Received packet\n";
});
$stream->on("error", function (Exception $e) {
    var_dump($e);
});

$loop->run();













