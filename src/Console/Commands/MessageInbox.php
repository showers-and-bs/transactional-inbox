<?php

namespace ShowersAndBs\TransactionalInbox\Console\Commands;

use Illuminate\Console\Command;
use ShowersAndBs\TransactionalInbox\Models\IncomingMessage;

class MessageInbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amqp:inbox
                            {--stat : Display statistics}
                            {--id= : Display message with the given id}
                            {--event-id= : Display messages with the given event_id}
                            {--rerun : Rerun the event handler assigned to the publishable event}
                            {--status= : Display messages with the given status}
                            {--event= : Display messages with the given event, the event name can be shorten}
                            {--limit=10 : Display limited number of messages}
                            {--no-limit : Display all messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Browse incoming messages';

    /**
     * Descriptive message status
     *
     * @var array
     */
    private $statusMap = [
        IncomingMessage::PENDING => 'PENDING',
        IncomingMessage::PROCESSING => 'PROCESSING',
        IncomingMessage::FAILED => 'FAILED',
        IncomingMessage::COMPLETE => 'COMPLETE',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stat = $this->option('stat') ?? false;
        $id = $this->option('id');
        $eventId = $this->option('event-id');
        $rerun = $this->option('rerun') ?? false;
        $noLimit = $this->option('no-limit') ?? false;
        $limit = $this->option('limit');
        $status = $this->option('status');
        $event = $this->option('event');

        if($stat) {
            $this->showStatistics();
            return;
        }

        if($id && $eventId) {
            $this->error('Only one of these two, --id or --event-id, can be provided');
            return;
        }

        if($eventId) {
            try {
                $message = $this->getMessageForGivenEventId($eventId);
                $id = $message->id;
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        }

        if ($id) {
            if(! is_numeric($id)) {
                $this->error('Option --id must be integer');
                return;
            }

            $this->showMessage($id);

            if ($rerun) {
                $this->rerunMessage($id);
                return;
            }

            return;
        }

        if ($rerun && is_null($id)) {
            $this->error('Option --id must be set');
            return;
        }

        if ($noLimit) {
            $this->showList(null, $status, $event);
            return;
        }

        $this->showList($limit, $status, $event);
    }

    /**
     * Show messages in the list
     *
     * @return void
     */
    private function showList(int $limit = null, int $status = null, string $event = null)
    {
        $messages = IncomingMessage::query()
            ->select(['id', 'event_created_at', 'created_at', 'event_id', 'event', 'status', 'complete_at'])
            ->orderBy('id', 'desc')
            ->when(! is_null($status), function($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(! is_null($event), function($query) use ($event) {
                $query->where('event', 'like', "%$event%");
            })
            ->when($limit, function($query) use ($limit) {
                $query->limit($limit);
            })
            ->get()
            ->map(function($item) {
                return [
                    $item->id,
                    date('Y-m-d H:i:s', strtotime($item->event_created_at)),
                    date('Y-m-d H:i:s', strtotime($item->created_at)),
                    $item->complete_at ? date('Y-m-d H:i:s', strtotime($item->complete_at)) : null,
                    $item->event_id,
                    $item->event,
                    $item->status . '|' . $this->statusMap[$item->status],
                ];
            });

        $this->table(
            ['id', 'event_created_at', 'created_at', 'complete_at', 'event_id', 'event', 'status'],
            $messages
        );
    }

    /**
     * Display message details
     *
     * @return void
     */
    private function showMessage(int $id)
    {
        try {
            $message = IncomingMessage::findOrFail($id);

            $output = [
                ['id', $message->id],
                ['event_created_at', date('Y-m-d H:i:s', strtotime($message->event_created_at))],
                ['created_at', date('Y-m-d H:i:s', strtotime($message->created_at))],
                ['complete_at', date('Y-m-d H:i:s', strtotime($message->complete_at))],
                ['updated_at', date('Y-m-d H:i:s', strtotime($message->updated_at))],
                ['event_id', $message->event_id],
                ['event', $message->event],
                ['payload', unserialize($message->payload)],
                ['status', $message->status . '|' . $this->statusMap[$message->status]],
            ];

            $this->table(
                ['property', 'value'],
                $output
            );
        } catch(\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Resend the message by changing status to PENDING
     *
     * @return void
     */
    private function rerunMessage(int $id)
    {
        try {
            $message = IncomingMessage::find($id);

            if(is_null($message)) {
                $this->error("The message id:{$message->id} does not exist.");
                return;
            }

            list($handlerClass, $handlerMethod) = $message->getEventHandler();

            $this->info("Rerun the event handler '{$handlerClass}@{$handlerMethod}' for the message id:{$message->id}");

            $message->runEventHandler();

        } catch(\Exception $e) {
            $this->error($e->getMessage());
            report($e);
        }
    }

    /**
     * Display statistical data
     *
     * @return void
     */
    private function showStatistics()
    {
        $this->line('Today');
        $this->showStatisticsToday();

        $this->newLine();

        $this->line('Overall');
        $this->showStatisticsAllTime();
    }

    /**
     * Display statistical data
     *
     * @return void
     */
    private function showStatisticsAllTime()
    {
        $groupByStatus = IncomingMessage::query()
            ->selectRaw('status, count(status) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(function($item) {
                return [
                    $item->status . '|' . $this->statusMap[$item->status],
                    $item->count,
                ];
            });

        $this->table(
            ['status', 'count'],
            $groupByStatus
        );
    }

    /**
     * Display statistical data for today
     *
     * @return void
     */
    private function showStatisticsToday()
    {
        $groupByStatus = IncomingMessage::query()
            ->selectRaw('status, count(status) as count')
            ->whereDate('created_at', date('Y-m-d'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(function($item) {
                return [
                    $item->status . '|' . $this->statusMap[$item->status],
                    $item->count,
                ];
            });

        $this->table(
            ['status', 'count'],
            $groupByStatus
        );
    }

    /**
     * Ge
     *
     * @param  string $eventId [description]
     * @return [type]          [description]
     */
    public function getMessageForGivenEventId(string $eventId)
    {
        return IncomingMessage::query()
            ->where('event_id', $eventId)
            ->firstOrFail();
    }
}
