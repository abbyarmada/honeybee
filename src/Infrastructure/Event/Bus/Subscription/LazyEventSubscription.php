<?php

namespace Honeybee\Infrastructure\Event\Bus\Subscription;

use Closure;
use Honeybee\Common\Error\RuntimeError;
use Honeybee\Infrastructure\Event\Bus\Subscription\EventFilterList;
use Honeybee\Infrastructure\Event\Bus\Transport\EventTransportInterface;
use Honeybee\Infrastructure\Event\EventHandlerInterface;
use Honeybee\Infrastructure\Event\EventHandlerList;
use Honeybee\Infrastructure\Config\SettingsInterface;

class LazyEventSubscription extends EventSubscription
{
    protected $event_handlers_callback;

    protected $event_filters_callback;

    protected $event_transport_callback;

    public function __construct(
        Closure $event_handlers_callback,
        Closure $event_filters_callback,
        Closure $event_transport_callback,
        SettingsInterface $settings,
        $activated
    ) {
        $this->event_transport_callback = $event_transport_callback;
        $this->event_filters_callback = $event_filters_callback;
        $this->event_handlers_callback = $event_handlers_callback;
        $this->settings = $settings;
        $this->activated = (bool)$activated;
    }

    public function getEventHandlers()
    {
        if (!$this->event_handlers) {
            $this->event_handlers = $this->createEventHandlers();
        }
        return $this->event_handlers;
    }

    public function getEventFilters()
    {
        if (!$this->event_filters) {
            $this->event_filters = $this->createEventFilters();
        }
        return $this->event_filters;
    }

    public function getEventTransport()
    {
        if (!$this->event_transport) {
            $this->event_transport = $this->createEventTransport();
        }
        return $this->event_transport;
    }

    protected function createEventHandlers()
    {
        $create_function = $this->event_handlers_callback;
        $event_handlers = $create_function();

        if (!$event_handlers instanceof EventHandlerList) {
            throw new RuntimeError(
                sprintf(
                    "Invalid event-handler list type given: %s, expected instance of %s",
                    get_class($event_handlers),
                    EventHandlerList::CLASS
                )
            );
        }

        return $event_handlers;
    }

    protected function createEventFilters()
    {
        $create_function = $this->event_filters_callback;
        $event_filters = $create_function();

        if (!$event_filters instanceof EventFilterList) {
            throw new RuntimeError(
                sprintf(
                    "Invalid event-filter list type given: %s, expected instance of %s",
                    get_class($event_filters),
                    EventFilterList::CLASS
                )
            );
        }

        return $event_filters;
    }

    protected function createEventTransport()
    {
        $create_function = $this->event_transport_callback;
        $event_transport = $create_function();

        if (!$event_transport instanceof EventTransportInterface) {
            throw new RuntimeError(
                sprintf(
                    "Invalid event-transport type given: %s, expected instance of %s",
                    get_class($event_transport),
                    EventTransportInterface::CLASS
                )
            );
        }

        return $event_transport;
    }
}
