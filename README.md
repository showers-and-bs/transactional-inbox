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

Publish config file

```sh
php artisan vendor:publish --tag=transactional-inbox-config
```

Set config attribute `queue`, that is a name of the rabbit queue for your app. It should be set once and never changed. In case of change, be sure that the queue with old name is empty or you risk to lose some valuable data.

To run message consumer deamon execute command

```sh
php artisan amqp:consume
```

Go to the config file and add the events you want to listen to in your app, for example.

```php
    'events' => [
        'MEM_TEST' => \App\Events\TestEvent::class,
    ],
```

In the example above MEM_TEST is name of the message that you have set in publishable event, the events that implement **ShouldBePublished** interface exposed by showers-and-bs/transactional-outbox.

The event receives object of class `ShowersAndBs\TransactionalInbox\Models\IncomingMessage` as argument, see example below:

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

Now its up to you how you will handle it further, but do not forget to set message status (processing, failed or complete) depending on processing result.
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
