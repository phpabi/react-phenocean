<?php
namespace nerdmann\react\phenocean\telegram;

class TelegramRPS extends Telegram
{

    public function __construct()
    {
        parent::__construct(self::TELEGRAM_TYPE_RPS);
        $this->maxTelegramDataLength = 1;
    }
}
