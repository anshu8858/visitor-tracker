<?php

namespace Anshu8858\VisitorTracker\Eventing;

class EventStorage
{
    private $events = [];

    private $isOn = true;

    public function logEvent($event, $object)
    {
        $this->events[] = [
            'event' => $event,
            'object' => $object,
        ];
    }

    public function popAll()
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }

    public function turnOff()
    {
        $this->isOn = false;
    }

    public function turnOn()
    {
        $this->isOn = true;
    }

    public function isOn()
    {
        return $this->isOn;
    }

    public function isOff()
    {
        return ! $this->isOn;
    }
}
