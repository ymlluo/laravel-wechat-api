<?php

namespace ymlluo\WxApi\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WxMediaDownload implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $filepath;
    public $filename;
    public $mediaId;
    public $mimeType;
    public $extra;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($mediaId,$filepath,$filename,$extra = [])
    {
        $this->mediaId = $mediaId;
        $this->filepath = $filepath;
        $this->filename = $filename;
        $this->extra = $extra;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('wx:media:downloaded');
    }
}
