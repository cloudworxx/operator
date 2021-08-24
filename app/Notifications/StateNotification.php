<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\NexmoMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use SnoerenDevelopment\DiscordWebhook\DiscordMessage;
use SnoerenDevelopment\DiscordWebhook\DiscordWebhookChannel;

class StateNotification extends Notification
{
    /**
     * Initialize the notification.
     *
     * @param  array  $payload
     * @param  array  $webhook
     * @return void
     */
    public function __construct(
        public array $payload,
        public array $webhook,
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return match ($this->webhook['channel']) {
            'slack' => ['slack'],
            'discord' => [DiscordWebhookChannel::class],
            'nexmo' => ['nexmo'],
            'twilio' => [TwilioChannel::class],
            'fcm' => [FcmChannel::class],
            default => [],
        };
    }

    /**
     * Get the Discord representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \SnoerenDevelopment\DiscordWebhook\DiscordMessage
     */
    public function toDiscord($notifiable): DiscordMessage
    {
        $message = DiscordMessage::create()
            ->username('Opsie Operator')
            ->tts(false);

        if ($this->payload['up']) {
            $message->content("Your website {$this->payload['url']} is now online.");
        } else {
            $message->content("Your website {$this->payload['url']} is offline.");
        }

        return $message;
    }

    /**
     * Get the Slack representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function toSlack($notifiable)
    {
        $message = new SlackMessage;

        if ($this->payload['up']) {
            $message->success()->content("Your website {$this->payload['url']} is now online.");
        } else {
            $message->error()->content("Your website {$this->payload['url']} is offline.");
        }

        $message->from(
            'Opsie Operator',
            array_rand([':male-construction-worker:', ':female-construction-worker:']),
        );

        $message->to($this->webhook['slack_channel']);

        return $message;
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return NexmoMessage
     */
    public function toNexmo($notifiable)
    {
        $message = new NexmoMessage;

        if ($this->payload['up']) {
            $message->content("Your website {$this->payload['url']} is now online.");
        } else {
            $message->content("Your website {$this->payload['url']} is offline.");
        }

        return $message;
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return TwilioSmsMessage
     */
    public function toTwilio($notifiable)
    {
        $message = new TwilioSmsMessage;

        if ($this->payload['up']) {
            $message->content("Your website {$this->payload['url']} is now online.");
        } else {
            $message->content("Your website {$this->payload['url']} is offline.");
        }

        return $message;
    }

    /**
     * Get the Vonage / SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return FcmMessage
     */
    public function toFcm($notifiable)
    {
        $notification = FcmNotification::create();

        if ($this->payload['up']) {
            $notification->setTitle('Website Online')->setBody("Your website {$this->payload['url']} is now online.");
        } else {
            $notification->setTitle('Website Offline')->setBody("Your website {$this->payload['url']} is offline.");
        }

        return FcmMessage::create()
            ->setData(['payload' => $this->payload])
            ->setNotification($notification);
    }
}
