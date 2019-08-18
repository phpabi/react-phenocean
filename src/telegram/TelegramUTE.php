<?php
namespace nerdmann\react\phenocean\telegram;

use InvalidArgumentException;

class TelegramUTE extends Telegram
{

    const TELEGRAM_UTE_TEACHINTYPE_REQUEST = 0;

    const TELEGRAM_UTE_TEACHINTYPE_DELETION_REQUEST = 1;

    const TELEGRAM_UTE_TEACHINTYPE_NO_SPECIFIED = 2;

    const TELEGRAM_UTE_TEACHINTYPE_NO_USED = 3;

    const TELEGRAM_UTE_CMDIDENT_REQUEST = 0;

    const TELEGRAM_UTE_CMDIDENT_RESPONSE = 1;

    const TELEGRAM_UTE_RESPONSE_TECHIN_SUCCESS = 1;

    const TELEGRAM_UTE_RESPONSE_DELETE_SUCCESS = 2;

    const TELEGRAM_UTE_RESPONSE_FAILED = 3;

    public function __construct()
    {
        parent::__construct(self::TELEGRAM_TYPE_UTE);
    }

    public function generateResponseTelegram($response = self::TELEGRAM_UTE_RESPONSE_TECHIN_SUCCESS)
    {
        $telegram = clone $this;
        $telegram->setCommandIdent(0x01);
        $telegram->setTeachinType($response);
        
        echo $this.PHP_EOL.$telegram.PHP_EOL;
        return $telegram;
    }

    /**
     *
     * @return boolean
     */
    public function getBidirectional()
    {
        return (bool) $this->getDataBit(0, 0b10000000);
    }

    /**
     *
     * @return boolean
     */
    public function getResponseExpected()
    {
        return ! (bool) $this->getDataBit(0, 0b01000000);
    }

    /**
     *
     * @return int
     */
    public function getTeachinType()
    {
        return $this->getDataBit(0, 0b00110000);
    }

    /**
     *
     * @return int
     */
    public function getCommandIdent()
    {
        return $this->getDataBit(0, 0b00001111);
    }

    /**
     *
     * @return int
     */
    public function getNumberOfIndivChannel()
    {
        return $this->getDataByte(1);
    }

    /**
     *
     * @return int
     */
    public function getManufactorID()
    {
        return ($this->getDataByte(2) << 3) & $this->getDataBit(3, 0b00000111);
    }

    /**
     *
     * @return int
     */
    public function getTypeEEP()
    {
        return $this->getDataByte(4);
    }

    /**
     *
     * @return int
     */
    public function getFuncEEP()
    {
        return $this->getDataByte(5);
    }

    /**
     *
     * @return int
     */
    public function getRorgEEP()
    {
        return $this->getDataByte(6);
    }

    /**
     *
     * @param int $teachinType
     */
    public function setTeachinType($teachinType)
    {
        if ($teachinType > 3 || $teachinType < 0)
            throw new InvalidArgumentException($teachinType . " is not a valid TeachinType or Response (0-3)");
        $this->setDataBit(0, 0b00110000, $teachinType);
    }

    /**
     *
     * @param int $commandIdent
     */
    public function setCommandIdent($commandIdent)
    {
        if ($commandIdent > 1 || $commandIdent < 0)
            throw new InvalidArgumentException($commandIdent . " is not a valid Command Identifier (0 or 1)");
        $this->setDataBit(0, 0b00001111, $commandIdent);
    }
}

