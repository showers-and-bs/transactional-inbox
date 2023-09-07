<?php

namespace ShowersAndBs\TransactionalInbox\Console\Commands;

use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Exchanges\Fanout;
use Anik\Amqp\Queues\Queue;
use Anik\Laravel\Amqp\Facades\Amqp;
use Illuminate\Console\Command;
use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;
use ShowersAndBs\TransactionalInbox\Services\InboxService;

class MessageConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amqp:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume AMQP messages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queueName = config('transactional_inbox.queue');

        $this->info("CONSUMER: Start consuming AMQP messages from the queue named `{$queueName}`...");

        $handler = [$this, 'processReceivingMessage'];
        $bindingKey = '';
        $exchange = new Fanout('amq.fanout');
        $queue = (new Queue($queueName))->setDeclare(true)->setDurable(true);
        $qos = null;
        $options = [];

        Amqp::consume($handler, $bindingKey, $exchange, $queue, $qos, $options);
    }

    /**
     * Handler to process receiving messages
     *
     * @param  VerifiedConsumableMessage $message
     * @return void
     */
    public function processReceivingMessage(ConsumableMessage $message)
    {
        $body = $message->getMessageBody();

        $messageDTO = @unserialize($body);

        if (false === ($messageDTO instanceof \ShowersAndBs\TransactionalOutbox\DTO\ThirstyxMessage)) {
            $this->line("CONSUMER: Unexpected message {$body}");
            // $message->reject(); // this is going to requeue the message
            $message->ack();
            return;
        }

        $inboxService = new InboxService($messageDTO);

        // just ignore events that are not of interest
        if(! $inboxService->shouldConsumeMessage()) {
            $this->line("CONSUMER: Not for consuming {$messageDTO->event}");
            $message->ack();
            return;
        }

        // ignore duplicated messages, consumer should be idempotent
        if(! $inboxService->shouldPersistMessage()) {
            $this->line("CONSUMER: Already persisted {$messageDTO->event_id}");
            $message->ack();
            return;
        }

        // STEP 1 - persist message to inbox table
        $inboxModel = $inboxService->persistMessage();
        $message->ack();

        // STEP 2 - dispatch event to processing
        // do this in try/catch block to not interrupt deamon execution by an exception in outer code
        try {
            // get event class to dispatch
            $event = $inboxService->eventClassToDispatch();

            if (! class_exists($event)) {
                throw new \Exception("Event class with the name {$event} is not defined.", 1);
            }

            $this->line("CONSUMER: Dispatching to process event {$event}");

            // dispatch event to be processed in the main app
            event(new $event($inboxModel));

        } catch (\Throwable $e) {
            $this->line("CONSUMER: Exception thrown!!! " .  $e->getMessage());
            \Log::error("CONSUMER: Exception thrown!!!", [$e->getMessage()]);
        }
    }

    /**
     * Write a string as standard output and log.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        parent::line($string, $style, $verbosity);

        \Illuminate\Support\Facades\Log::info($string);
    }
}
