<?php
namespace nerdmann\react\phenocean\telegram;

class Telegram4BS extends Telegram
{
        
    public function __construct()
    {
        parent::__construct(self::TELEGRAM_TYPE_4BS);
        $this->maxTelegramDataLength = 4;
    }
    
    
}
