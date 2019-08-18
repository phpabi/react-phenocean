<?php
namespace nerdmann\react\phenocean\telegram;

use nerdmann\react\phenocean\device\Device;
use nerdmann\react\phenocean\packet\Packet;
use OutOfBoundsException;
use InvalidArgumentException;

class Telegram extends Packet
{

    const TELEGRAM_TYPE_RPS = 0xF6;

    const TELEGRAM_TYPE_1BS = 0xD5;

    const TELEGRAM_TYPE_4BS = 0xA5;

    const TELEGRAM_TYPE_VLD = 0xD2;

    const TELEGRAM_TYPE_MSC = 0xD1;

    const TELEGRAM_TYPE_ADT = 0xA6;

    const TELEGRAM_TYPE_SM_LRN_REQ = 0xC6;

    const TELEGRAM_TYPE_SM_LRN_ANS = 0xC7;

    const TELEGRAM_TYPE_SM_REC = 0xA7;

    const TELEGRAM_TYPE_SYS_EX = 0xC5;

    const TELEGRAM_TYPE_SEC = 0x30;

    const TELEGRAM_TYPE_SEC_ENCAPS = 0x31;

    const TELEGRAM_TYPE_UTE = 0xD4;

    private $telegramType;

    private $telegramData;

    private $senderID;

    private $status;

    private $destinationID;

    private $subTelNum;

    private $dBm;

    private $secLevel;

    protected $maxTelegramDataLength;

    public function __construct($telegramType)
    {
        parent::__construct(self::PACKET_TYPE_RADIO_ERP1);
        $this->telegramType = $telegramType;
        $this->telegramData = array();
        $this->senderID = array(
            0x00,
            0x00,
            0x00,
            0x00
        );
        $this->status = 0x00;
        $this->destinationID = array(
            0xFF,
            0xFF,
            0xFF,
            0xFF
        );
        $this->subTelNum = 0x03;
        $this->dBm = 0xFF;
        $this->secLevel = 0x00;
        $this->maxTelegramDataLength = 99;
    }

    public function setData(array $data)
    {
        if (array_shift($data) != $this->telegramType)
            throw new InvalidArgumentException("Received telegram data is not compatible with Telegram class " . __CLASS__);
        $this->status = array_pop($data);
        $this->senderID = array_splice($data, - 4);
        $this->telegramData = $data;
    }

    public function getData(): array
    {
        return array_merge(array(
            $this->telegramType
        ), $this->telegramData, $this->senderID, array(
            $this->status
        ));
    }

    public function setOptData(array $optData)
    {
        $this->subTelNum = array_shift($optData);
        $this->secLevel = array_pop($optData);
        $this->dBm = array_pop($optData);
        $this->destinationID = $optData;
    }

    public function getOptData(): array
    {
        return array_merge(array(
            $this->subTelNum
        ), $this->destinationID, array(
            $this->dBm
        ), array(
            $this->secLevel
        ));
    }

    public function setDestinationID(array $destinationID)
    {
        if (! Device::isValidID($destinationID, 4))
            throw new InvalidArgumentException("Sender ID must be a 4 hexadecimal integer array (for example array(0xD5,0x06,0x07,0x08))");
        $this->destinationID = $destinationID;
        $this->subTelNum = 0x03;
        $this->dBm = 0xFF;
        $this->secLevel = 0x00;
        $this->senderID = array(0x00,0x00,0x00,0x00);
    }

    public function getTelegramType()
    {
        return $this->telegramType;
    }

    public function getDataByte($byte): int
    {
        if (count($this->telegramData) < $byte + 1 || $byte < 0)
            throw new OutOfBoundsException("Telegram data has only " . count($this->telegramData) . " bytes. Byte " . $byte . " was requested");
        return $this->telegramData[$byte];
    }

    public function getDataBit($byte, $mask = 0b11111111): int
    {
        $b = $this->getDataByte($byte);
        $return = $b & $mask;
        // Move value to right
        while (! ($mask & 1)) {
            $mask >>= 1;
            $return >>= 1;
        }
        
        return $return;
    }

    public function setDataByte($byte, $value)
    {
        if ($byte < 0)
            throw new OutOfBoundsException("Byte cannot be negative");
        if ($byte > ($this->maxTelegramDataLength - 1))
            throw new \OutOfBoundsException("Telegram allows only " . $this->maxTelegramDataLength . " bytes. " . $byte . " too high");
        
        while ($byte > count($this->telegramData) - 1)
            array_push($this->telegramData, 0x00);
        $this->telegramData[$byte] = $value;
    }

    public function setDataBit($byte, $mask, $value)
    {
        try {
            $b = $this->getDataByte($byte);
        } catch (OutOfBoundsException $e) {
            $b = 0x00;
        }
        while (($mask & $value) != $value)
            $value <<= 1;
        $newB = ($b & ~ $mask) | $value;
        $this->setDataByte($byte, $newB);
    }

    public static function createTelegram(array $data, array $optData): Telegram
    {
        $type = $data[0];
        switch ($type) {
            case self::TELEGRAM_TYPE_VLD:
                $return = new TelegramVLD();
                break;
            case self::TELEGRAM_TYPE_UTE:
                $return = new TelegramUTE();
                break;
            case self::TELEGRAM_TYPE_RPS:
                $return = new TelegramRPS();
                break;
            case self::TELEGRAM_TYPE_1BS:
                $return = new Telegram1BS();
                break;
            case self::TELEGRAM_TYPE_4BS:
                $return = new Telegram4BS();
                break;
            default:
                $return = new Telegram($type);
        }
        $return->setData($data);
        $return->setOptData($optData);
        return $return;
    }
    /**
     * @return array
     */
    public function getTelegramData()
    {
        return $this->telegramData;
    }

    /**
     * @return array
     */
    public function getSenderID()
    {
        return $this->senderID;
    }

    /**
     * @return number
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getDestinationID()
    {
        return $this->destinationID;
    }

    /**
     * @return number
     */
    public function getSubTelNum()
    {
        return $this->subTelNum;
    }

    /**
     * @return number
     */
    public function getDBm()
    {
        return $this->dBm;
    }

    /**
     * @return number
     */
    public function getSecLevel()
    {
        return $this->secLevel;
    }

    /**
     * @return number
     */
    public function getMaxTelegramDataLength()
    {
        return $this->maxTelegramDataLength;
    }

}

