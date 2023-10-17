# Inbox pattern

AKA **Idempotent consumer**

The package is implementation of [Idempotent consumer pattern](https://microservices.io/patterns/communication-style/idempotent-consumer.html) for the project ThirstyX.

Read [this nice explanation](https://softwaremill.com/microservices-101/#inbox-pattern) of the pattern.

## Installation

The package intended only for the project ThirstyX, it is not in Packagist.

Add path to Github repository to your composer.json file.

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:showers-and-bs/transactional-inbox.git"
        }
    ],
```
Now run composer require to pull in the package.

```sh
composer require showers-and-bs/transactional-inbox
```
## Usage

Run migrations to create table **incoming_messages**.

```sh
php artisan migration
```

Publish config file.

```sh
php artisan vendor:publish --tag=transactional-inbox-config
```

Set config attribute **queue**, that is a name of the rabbit queue for your app. It should be set once and never changed. In case of change, be sure that the queue with old name is empty or you risk to lose some valuable data.

To run message consumer deamon execute command amqp:consume. It connects your app to the message broker and listens for the messages delivered on the queue you set in the config file.

```sh
php artisan amqp:consume
```

Go to the config file and add the events you want to listen to in your app and corresponding handler methods similar to laravel routes, for example.

```php
    'events' => [
        \ShowersAndBs\ThirstyEvents\Events\TestEvent::class => [\App\Handlers\TestEventHandler::class, 'handle'],
    ],
```

In the example above TestEvent is the name of publishable event (the events that implement **ShouldBePublished** interface exposed by showers-and-bs/thirsty-events).

Arguments of the handler method are object of class `ShowersAndBs\TransactionalInbox\Models\IncomingMessage` and publishable event, see example below:

Now its up to you how you will handle it further, but do not forget to set message status (processing, failed or complete) depending on processing result.

```php
<?php

namespace App\Handlers;

use ShowersAndBs\ThirstyEvents\Events\TestEvent;
use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;

class TestEventHandler
{
    /**
     * Handle publishable event.
     */
    public function handle(IncomingMessage $incomingMessage, TestEvent $event): void
    {
        $incomingMessage->setProcessing();

        try {
            $text = $event->message;

            \Log::debug(__CLASS__, ["TestEvent public property `message` contains text `$text`"]);

            \Log::debug(var_export($event, 1));

        } catch (\Throwable $e) {
            $incomingMessage->setFailed();
            throw $e;
        }

        $incomingMessage->setComplete();
    }
}
```

## Console commands

There is a bunch of console commands that should help development and debugging.

Here are examples of use.

```sh
php artisan amqp:outbox --help # to list all available options
php artisan amqp:outbox # list the last 10 messages
php artisan amqp:outbox --limit=30 # list the last 30 messages
php artisan amqp:outbox --no-limit # list all the messages
php artisan amqp:outbox --no-limit --status=2 # list all the messages with the status 2 (FAILED)
php artisan amqp:outbox --no-limit --event=UserLogin # list all the messages for the event UserLogin
php artisan amqp:outbox --id=7 # display details for the message with id=7
php artisan amqp:outbox --event-id=a12f907c-115b-4eb4-ad20-140e547b98c6 # display details for the message with event id =a12f907c-115b-4eb4-ad20-140e547b98c6
php artisan amqp:outbox --id=7 --rerun # rerun the event handler assigned to the publishable event
```

## Guide for package development

Create folder named **packages** in the same level where reside microservice applications.

Get into it and run `git clone git@github.com:showers-and-bs/transactional-inbox.git`.

The folder structure should look like this:

<pre>
<code>...
&#9500;&#9472;&#9472; packages
&#9474;   &#9492;&#9472;&#9472; transactional-inbox
&#9474;       &#9492;&#9472;&#9472; composer.json
&#9500;&#9472;&#9472; content-service
&#9474;   &#9492;&#9472;&#9472; composer.json
&#9500;&#9472;&#9472; member-service
&#9474;   &#9492;&#9472;&#9472; composer.json
...</code>
</pre>

Now get into the folder `vendor/showers-and-bs`, delete folder `transactional-inbox` and crate symlink to the folder `packages/transactional-inbox`.

```sh
ln -s ../../../packages/transactional-inbox/ ./transactional-inbox
```

Happy coding!
