<?php

namespace ymlluo\WxApi\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WxMediaUpload implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $filepath;
    public $filename;
    public $mediaId;
    public $expiredAt;
    public $url;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($mediaId,$filepath,$filename,$expiredAt,$url='')
    {
        $this->filepath = $filepath;
        $this->filename = $filename;
        $this->mediaId = $mediaId;
        $this->expiredAt = $expiredAt;
        $this->url =$url;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('wx:resource:uploaded');
    }
}
