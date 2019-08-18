<?php
namespace nerdmann\react\phenocean\device;

use nerdmann\react\phenocean\packet\Packet;
use nerdmann\react\phenocean\telegram\Telegram;
use nerdmann\react\phenocean\telegram\TelegramVLD;
use nerdmann\react\phenocean\Event;

class F60202 extends Device
{

    public function __construct(array $id)
    {
        parent::__construct($id, array(
            0xF6,
            0x02,
            0x02
        ));
    }

    public function getMessage(Packet $telegram)
    {
        switch ($telegram->getTelegramType()) {
            case Telegram::TELEGRAM_TYPE_RPS:
                $this->decodeStatusMessage($telegram);
                break;
        }
    }
    
    private function decodeStatusMessage(Telegram $telegram) {
        $event = new Event("status");
        $event->setProperty("rocker1", $telegram->getDataBit(0,0b11100000));
        $event->setProperty("pressed1", $telegram->getDataBit(0,0b00010000));
        $event->setProperty("rocker2", $telegram->getDataBit(0,0b00001110));
        $event->setProperty("pressed2", $telegram->getDataBit(0,0b00000001));
        $this->emit("event",array($event));
    }
}

