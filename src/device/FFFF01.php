<?php
namespace nerdmann\react\phenocean\device;

use nerdmann\react\phenocean\packet\Packet;
use nerdmann\react\phenocean\telegram\Telegram;
use nerdmann\react\phenocean\telegram\TelegramVLD;
use nerdmann\react\phenocean\Event;

class FFFF01 extends Device
{

    public function __construct(array $id)
    {
        parent::__construct($id, array(
            0xFF,
            0xFF,
            0x01
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
        $event->setProperty("on", $telegram->getDataBit(0,0b00100000));
        $this->emit("event",array($event));
    }
}

