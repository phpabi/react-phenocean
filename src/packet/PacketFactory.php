<?php
namespace nerdmann\react\phenocean\packet;

use nerdmann\react\phenocean\telegram\Telegram;
use nerdmann\react\phenocean\EnoceanProtocolException;

class PacketFactory
{

    private $header;

    private $dataLength;

    private $optDataLength;

    private $packetType;

    private $headerCRC;

    private $data;

    private $optData;

    private $dataCRC;

    public function __construct()
    {
        $this->header = array();
        $this->headerCRC = array();
        $this->data = array();
        $this->optData = array();
        $this->dataCRC = array();
        $this->dataLendth = 0;
        $this->optDataLength = 0;
        $this->packetType = 0;
    }

    public function setHeader(array $data, array $crc)
    {
        $this->header = $data;
        $this->headerCRC = $crc;
        
        $calc = Packet::getCRC($this->header);
        if ($calc != $this->headerCRC)
            throw new EnoceanProtocolException("Bad header checksum (expected: " . sprintf("%x", $calc[0]) . ")", $this->getPacketRaw());
        
        $this->dataLength = $this->header[0] * 256 + $this->header[1];
        $this->optDataLength = $this->header[2];
        $this->packetType = $this->header[3];
    }

    public function getPacketRaw()
    {
        return array_merge(array(
            0x55
        ), $this->header, $this->headerCRC, $this->data, $this->optData, $this->dataCRC);
    }

    public function getDataLength()
    {
        return $this->dataLength;
    }

    public function getOptDataLength()
    {
        return $this->optDataLength;
    }

    public function setData(array $data, array $optData, array $crc)
    {
        $this->data = $data;
        $this->optData = $optData;
        $this->dataCRC = $crc;
        $calc = Packet::getCRC(array_merge($this->data, $this->optData));
        if ($calc != $this->dataCRC)
            throw new EnoceanProtocolException("Bad data checksum (expected: " . sprintf("%x", $calc[0]) . ")", $this->getPacketRaw());
    }

    public function getPacket()
    {
        switch ($this->packetType) {
            case Packet::PACKET_TYPE_RADIO_ERP1: // Telegram
                return Telegram::createTelegram($this->data, $this->optData);
                break;
            default:
                return new Packet($this->packetType, $this->data, $this->optData);
        }
    }
}
    