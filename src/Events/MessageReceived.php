<?php

namespace ShowersAndBs\TransactionalInbox\Events;

use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;

class MessageReceived
{
    /**
     * Message received from the broker
     *
     * @var \ShowersAndBs\TransactionalInbox\Models\IncomingMessage
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @param  IncomingMessage $message
     * @return void
     */
    public function __construct(IncomingMessage $message)
    {
        $this->message = $message;
    }
}
