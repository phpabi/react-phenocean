<?php
namespace nerdmann\react\phenocean\telegram;

class Telegram1BS extends Telegram
{
        
    public function __construct()
    {
        parent::__construct(self::TELEGRAM_TYPE_1BS);
        $this->maxTelegramDataLength = 1;
    }
    
    
}
