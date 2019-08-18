<?php
namespace nerdmann\react\phenocean\device;

use nerdmann\react\phenocean\packet\Packet;
use nerdmann\react\phenocean\telegram\Telegram;
use nerdmann\react\phenocean\telegram\TelegramVLD;
use nerdmann\react\phenocean\Event;
use nerdmann\react\phenocean\telegram\TelegramUTE;
use InvalidArgumentException;

class D201 extends Device
{

    const DIM_VALUE_SWITCH = 0x00;

    const DIM_VALUE_TIMER1 = 0x01;

    const DIM_VALUE_TIMER2 = 0x02;

    const DIM_VALUE_TIMER3 = 0x03;

    const DIM_VALUE_STOP = 0x04;

    public function __construct(array $id)
    {
        parent::__construct($id, array(
            0xD2,
            0x01
        ));
    }

    protected function getMessage(Packet $telegram)
    {
        switch ($telegram->getTelegramType()) {
            case Telegram::TELEGRAM_TYPE_VLD:
                $cmd = $telegram->getDataBit(0, 0b00001111);
                switch ($cmd) {
                    case 0x04:
                        $this->decodeStatusMessage($telegram);
                        break;
                    default:
                        break;
                }
                break;
            case Telegram::TELEGRAM_TYPE_UTE:
                
                $event = new Event("teachin");
                $event->setProperty("request", $telegram);
                $this->emit("event", array(
                    $event
                ));
                break;
        }
    }

    public function pair(TelegramUTE $request)
    {
        $response = $request->generateResponseTelegram(TelegramUTE::TELEGRAM_UTE_RESPONSE_TECHIN_SUCCESS);
        $this->sendTelegram($response);
    }

    protected function decodeStatusMessage(TelegramVLD $telegram): void
    {
        $event = new Event("status");
        $event->setProperty("power_failure", (bool) $telegram->getDataBit(0, 0b10000000));
        $event->setProperty("power_failure_detection", (bool) $telegram->getDataBit(0, 0b01000000));
        $event->setProperty("command_id", 0x04);
        $event->setProperty("over_current_switch", (bool) $telegram->getDataBit(1, 0b10000000));
        $event->setProperty("error_level", D201::getErrorLevel($telegram->getDataBit(1, 0b01100000)));
        $event->setProperty("io_channel", $telegram->getDataBit(1, 0b00011111));
        $event->setProperty("local_control", (bool) $telegram->getDataBit(2, 0b10000000));
        $event->setProperty("output_value", $telegram->getDataBit(2, 0b01111111));
        
        $this->emit("event", array(
            $event
        ));
    }

    public function setActuator($channel, $value, $dimming = 0x00)
    {
        if ($dimming > 0x04 || $dimming < 0x00)
            throw new InvalidArgumentException("Invalid Dimming value (see constants)");
        if ($channel > 0x1F || $channel < 0x00)
            throw new InvalidArgumentException("Invalid channel (between 0x00 and 0x1F)");
        if ($value > 0x64 || $value < 0x00)
            throw new InvalidArgumentException("Invalid value (between 0x00 and 0x64)");
        $t = new TelegramVLD();
        $t->setDataBit(0, 0b00001111, 0x01);
        $t->setDataBit(1, 0b11100000, $dimming);
        $t->setDataBit(1, 0b00011111, $channel);
        $t->setDataByte(2, $value);
        echo $t;
        $this->sendTelegram($t);
    }

    private static function getErrorLevel($i)
    {
        switch ($i) {
            case 0:
                return "ok";
            case 1:
                return "warn";
            case 2:
                return "fail";
            case 3:
                return "not supported";
        }
    }
}

