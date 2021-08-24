<?php

namespace App;

use Illuminate\Notifications\Notifiable as NotifiableTrait;
use Illuminate\Support\Str;

class Notifiable
{
    use NotifiableTrait;

    /**
     * Initialize the model for notifications.
     *
     * @param  string|null  $id
     * @param  string|null  $discordWebhookUrl
     * @param  string|null  $slackWebhookUrl
     * @param  string|null  $nexmoNumber
     * @param  string|null  $twilioNumber
     * @param  string|null  $fcmToken
     * @return void
     */
    public function __construct(
        public ?string $id = null,
        public ?string $discordWebhookUrl = null,
        public ?string $slackWebhookUrl = null,
        public ?string $nexmoNumber = null,
        public ?string $twilioNumber = null,
        public ?string $fcmToken = null,
    ) {
        //
    }

    /**
     * Get the unique ID for the notifiable.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->id ?: Str::uuid();
    }

    /**
     * Route notifications for the Discord channel.
     *
     * @return string
     */
    public function routeNotificationForDiscord(): string
    {
        return $this->discordWebhookUrl;
    }

    /**
     * Route notifications for the Slack channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForSlack($notification)
    {
        return $this->slackWebhookUrl;
    }

    /**
     * Route notifications for the Nexmo channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForNexmo($notification)
    {
        return $this->nexmoNumber;
    }

    /**
     * Route notifications for the Twilio channel.
     *
     * @return string
     */
    public function routeNotificationForTwilio()
    {
        return $this->twilioNumber;
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @return string
     */
    public function routeNotificationForFcm()
    {
        return $this->fcmToken;
    }
}
