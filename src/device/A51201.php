<?php
namespace nerdmann\react\phenocean\device;

use nerdmann\react\phenocean\packet\Packet;
use nerdmann\react\phenocean\telegram\Telegram;
use nerdmann\react\phenocean\telegram\Telegram4BS;
use nerdmann\react\phenocean\Event;

class A51201 extends Device
{

    public function __construct(array $id)
    {
        parent::__construct($id, array(
            0xA5,
            0x12
        ));
    }

    protected function getMessage(Packet $telegram)
    {
        if ($telegram->getTelegramType() == Telegram::TELEGRAM_TYPE_4BS) {
            $this->handle4BS($telegram);
        }
    }

    private function handle4BS(Telegram4BS $telegram)
    {
        $learnBit = !$telegram->getDataBit(3, 0b00001000);
        if ($learnBit )
            $this->handleLearnRequest($telegram);
        else {
            $event = new Event("metering");
            $value = $telegram->getDataByte(0) * 256 * 256 + $telegram->getDataByte(1) * 256 + $telegram->getDataByte(2);
            switch ($telegram->getDataBit(3, 0b00000011)) {
                case 4:
                    $value /= 10;
                case 3:
                    $value /= 10;
                case 2:
                    $value /= 10;
            }
            $event->setProperty("value", $value);
            $event->setProperty("unit", ($telegram->getDataBit(3, 0b00000100) ? "W" : "kWh"));
            
            $this->emit("event", array(
                $event
            ));
        }
    }

    private function handleLearnRequest(Telegram4BS $telegram)
    {
        $event = new Event("teachin");
        $event->setProperty("func", $telegram->getDataBit(0,0b11111100));
        $event->setProperty("type", ($telegram->getDataBit(0,0b00000011)<<5)|($telegram->getDataBit(1,0b11111000)));
        $event->setProperty("manufacturerID", ($telegram->getDataBit(1,0b00000111)<<8)|($telegram->getDataByte(2)));
        $this->emit("event",array($event));
    }
}


