# Transactional inbox AKA Idempotent consumer

The package is implementation of [Transactional inbox AKA Idempotent consumer](https://microservices.io/patterns/data/transactional-outbox.html) for the project ThirstyX.

Read [this nice explanation](https://softwaremill.com/microservices-101/) of the pattern.

## Installation

The package intended only for the project ThirstyX, it is not in Packagist.

Create folder named **packages** in the same level where resides microservice folders.

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

Now you’ll have to make a slight adjustment to your composer.json file of the main app.

```json
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:showers-and-bs/transactional-inbox.git"
        }
    ],
```

You can now require your local package in the Laravel application using chosen namespace of the package.

```sh
composer require showers-and-bs/transactional-inbox:dev-master
```

Then publish config file

```sh
php artisan vendor:publish --tag=transactional-inbox-config
```

In config file set attribute `queue`, that is name of the rabbitmq queue for your app. It should be set one and not change anymore. In case of change be sure that the queue with old name is empty or you risk to lose some valuable data.

## Usage

To run message consumer deamon type

```sh
php artisan amqp:consume
```

Go to config file and add events that you wish to listen in your app, for example.

```php
    'events' => [
        'MEM_TEST' => \App\Events\TestEvent::class,
    ],
```

An event receive `ShowersAndBs\TransactionalInbox\Models\IncomingMessage` as argument, for example:

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;

class TestEvent
{
    use Dispatchable, SerializesModels;

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
```

Now its up to you how you will handle it further, but do not forget to set message states depending on processing result.
```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TestEventListener
{
    /**
     * Handle the event.
     */
    public function handle(\App\Events\TestEvent $event): void
    {
        $event->message->setProcessing();

        try {
            $messageContent = $event->message->payload;
            // do somthing with $messageContent

        } catch (\Exception $e) {

            $event->message->setFailed();
        }

        $event->message->setComplete();
    }
}
```

## For local development

Create folder named **packages** in the same level where resides microservice folders.

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
