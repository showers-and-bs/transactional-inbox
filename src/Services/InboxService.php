<?php

namespace ShowersAndBs\TransactionalInbox\Services;

use Anik\Amqp\ConsumableMessage;
use ShowersAndBs\ThirstyEvents\DTO\RabbitMqMessagePayload;
use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;

class InboxService
{

    /**
     * Initialize object
     *
     * @param RabbitMqMessagePayload $messageDTO
     */
    public function __construct(private RabbitMqMessagePayload $messageDTO)
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
        return in_array($this->message->event, array_keys(config('transactional_inbox.events')));
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
