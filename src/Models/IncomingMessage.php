<?php

namespace ShowersAndBs\TransactionalInbox\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ShowersAndBs\ThirstyEvents\DTO\RabbitMqMessagePayload;

class IncomingMessage extends Model
{
    const PENDING = 0;

    const PROCESSING = 1;

    const FAILED = 2;

    const COMPLETE = 3;

    /**
     * Persist an publishable event to the database
     *
     * @param \ShowersAndBs\ThirstyEvents\DTO\RabbitMqMessagePayload $message
     */
    public function persistMessage(RabbitMqMessagePayload $message): void
    {
        $this->event_id         = $message->event_id;
        $this->event            = $message->event;
        $this->payload          = $message->payload;
        $this->event_created_at = date('Y-m-d H:i:s', strtotime($message->created_at));
        $this->status           = self::PENDING;

        $this->save();
    }

    /**
     * Check if the message with given eventId exists in the inbox table
     *
     * @param  string  $eventId
     * @return bool
     */
    public function isReceived(string $eventId): bool
    {
        return $this->where('event_id', $eventId)->exists();
    }

    public function setProcessing(): void
    {
        $this->status = self::PROCESSING;
        $this->save();
    }

    public function setFailed(): void
    {
        $this->status = self::FAILED;
        $this->save();
    }

    public function setComplete(): void
    {
        $this->complete_at = now();
        $this->status = self::COMPLETE;
        $this->save();
    }
}
