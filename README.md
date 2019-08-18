# react-phenocean
This is an streamer for ReactPHP for Enocean devices.
You need any kind of gateway (like [USB300](https://www.enocean.com/de/produkte/enocean_module/usb-300-oem/)) to talk to enocean devices.

## Supported devices

All devices provides one (or more) EnOcean Equipment Profile (EEP). More information about EEPs can be found [here]( https://www.enocean-alliance.org/wp-content/uploads/2018/02/EEP268_R3_Feb022018_public.pdf).

Until now this lib supports the following EEP:

| EEP | Tested devices |
| --- | -------------- |
| A5-12-01 | [Eltako FSVA-230V](https://www.eltako.com/fileadmin/downloads/de/datenblatt/Datenblatt_FSVA-230V-10A.pdf) |
| D2-01-?? | D2-01-12: [NodOn Relay Switch](https://nodon.fr/en/nodon/enocean-relay-switch-2-channels/) |
| F6-02-02 | [NodOn Soft Remote](https://nodon.fr/en/nodon/enocean-soft-remote/), [Eltako FSM14-UC](https://www.eltako.com/fileadmin/downloads/de/datenblatt/Datenblatt_FSM14-UC.pdf)|

## Example

Create a ReactPHP loop and a new EnoceanStream. The first parameter is the device file forthe gateway.
```
$loop = Factory::create();
$stream = new nerdmann\react\phenocean\EnoceanStream("/dev/enocean", $loop);
```

Then create all your devices, that you want to use.
```
$relay = DeviceFactory::create(
  array(0xD2, 0x01, 0x12),       // The EEP of the device
  array(0xDE, 0xAD, 0xBE, 0xEF), // The ID of the device
  $stream,                       // The Enocean Stream
  $loop                          // The loop
);

$rocker = DeviceFactory::create(
  array(0xF6, 0x02, 0x02),       // The EEP of the device
  array(0xDE, 0xCA, 0xFB, 0xAD), // The ID of the device
  $stream,                       // The Enocean Stream
  $loop                          // The loop
);
```

Now you can connect these 2 devices or do whatever you want to do. The devices will trigger "event" events.
The NodOn switch has 2 relay (0x00 and 0x01). They can be activated (0x64, which means 100%) or deactivated (0x00 or 0%). If you have a device that can dim, you can also send value between 0x00 and 0x64.
```
$rocker->on("event", function (Event $event) use ($relay) {
    if ($event->getProperty("pressed1")) {
        switch ($event->getProperty("rocker1")) {
            case 0: $relay->setActuator(0x00, 0x64); break;
            case 1: $relay->setActuator(0x00, 0x00); break;
            case 2: $relay->setActuator(0x01, 0x64); break;
            case 3: $relay->setActuator(0x01, 0x00); break;
        }
    }
});
```

... Don't forget to run the loop :simple_smile:
```
$loop->run();
```
