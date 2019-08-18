<?php
namespace nerdmann\react\phenocean\telegram;

class TelegramVLD extends Telegram
{

    private $dataVLD;

    public function __construct()
    {
        parent::__construct(self::TELEGRAM_TYPE_VLD);
        $this->maxTelegramDataLength = 14;
    }
}