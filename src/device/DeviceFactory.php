<?php
namespace nerdmann\react\phenocean\device;

use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use nerdmann\react\phenocean\EnoceanStream;

final class DeviceFactory
{

    public static function create(array $eep, array $deviceID, EnoceanStream $stream, LoopInterface $loop): Device
    {
        if (! Device::isValidID($eep, 3))
            throw new InvalidArgumentException("EEP must be a 3 hexadecimal integer array (for example array(0xD2,0x01,0x12))");
        if (! Device::isValidID($deviceID, 4))
            throw new InvalidArgumentException("Device ID must be a 4 hexadecimal integer array (for example array(0xD5,0x06,0x07,0x08))");
        
        $eepString = __NAMESPACE__ . "\\" . vsprintf("%02X%02X%02X", $eep);
        
        if (class_exists($eepString)) {
            $return = new $eepString($deviceID);
            $return->setEnoceanStream($stream, $loop);
            return $return;
        }
        
        $eepString = substr($eepString, 0, - 2);
        if (class_exists($eepString)) {
            $return = new $eepString($deviceID);
            $return->setEnoceanStream($stream, $loop);
            return $return;
        }
        
        $eepString = substr($eepString, 0, - 2);
        if (class_exists($eepString)) {
            $return = new $eepString($deviceID);
            $return->setEnoceanStream($stream, $loop);
            return $return;
        }
        
        throw new InvalidArgumentException("No compatible Device class for EEP " . vsprintf("%02X-%02X-%02X", $eep) . " found");
    }
}

