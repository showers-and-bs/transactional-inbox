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
    | List of events that should be received from the message broker.
    |
    */

    'events' => [
        \ShowersAndBs\ThirstyEvents\Events\TestEvent::class,
    ],

];
