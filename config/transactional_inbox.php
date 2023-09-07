<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Name of the queue to listen for messages from the broker in pub/sub
    |--------------------------------------------------------------------------
    |
    | !!! DO NOT MISS TO SET THIS PARAMETER !!!
    |
    */

    'queue' => 'TRANSACTIONAL_INBOX_QUEUE_NAME',

    /*
    |--------------------------------------------------------------------------
    | Events of interest
    |--------------------------------------------------------------------------
    |
    | Array keys should be values of the attribute `event` of messages
    | received from the message broker.
    | Array values should be class name of events to be dispatched for the
    | received message.
    |
    | The event class receive ShowersAndBs\TransactionalInbox\Models as argument.
    |
    */

    'events' => [
        // 'MEM_USER_LOGIN' => \App\Events\UserLogin::class,
    ],

];
