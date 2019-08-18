<?php
namespace nerdmann\react\phenocean;

class EnoceanProtocolException extends \Exception
{

    public function __construct(string $message, array $data)
    {
        $message = $message . "\n" . $this->hex_dump($data);
        parent::__construct($message, 0, null);
    }

    private function hex_dump($data, $newline = "\n"): string
    {
        static $width = 16; // number of bytes per line
        $hex = array_chunk($data, $width);
        
        $offset = 0;
        $return = "";
        foreach ($hex as $i => $line) {
            $format = "%02X" . str_repeat(" %02X", count($line) - 1);
            $return .= sprintf('%6X', $offset) . ' : ' . vsprintf($format, $line) . $newline; // . ' [' . $chars[$i] . ']' . $newline;
            $offset += $width;
        }
        return $return;
    }
}

