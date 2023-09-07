<?php

namespace ShowersAndBs\TransactionalInbox\Services;

use Anik\Amqp\ConsumableMessage;
use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;
use ShowersAndBs\TransactionalOutbox\DTO\ThirstyxMessage;

class InboxService
{

    /**
     * Initialize object
     *
     * @param ThirstyxMessage $messageDTO
     */
    public function __construct(private ThirstyxMessage $messageDTO)
    {
        $this->message = $messageDTO;
    }

    /**
     * Get true if the message should be processed
     *
     * @return bool
     */
    public function shouldConsumeMessage(): bool
    {
        $eventsToBeDispatched = config('transactional_inbox.events');

        return array_key_exists($this->message->event, $eventsToBeDispatched);
    }

    /**
     * Get true if the message is not saved
     *
     * @param  string $eventKey
     * @return string|null
     */
    public function shouldPersistMessage(): bool
    {
        return ! (new IncomingMessage)->isReceived($this->message->event_id);
    }

    /**
     * Get class name of the event to be dispatched or null
     *
     * @param  string $eventKey
     * @return string|null
     */
    public function eventClassToDispatch(): ?string
    {
        return config('transactional_inbox.events.' . $this->message->event);
    }

    /**
     * Store the message to database with status PENDING
     *
     * @return IncomingMessage
     */
    public function persistMessage(): ?IncomingMessage
    {
        $model = new IncomingMessage;

        $model->persistMessage($this->message);

        return $model;
    }

}
