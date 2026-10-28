<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** In-app bell + phone push. Text is stored as a key + params so each user reads it in their own language. */
class CrmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $type, public array $params, public ?string $url) {}

    public function via(User $user): array
    {
        $ch = ['database'];
        if (config('webpush.vapid.public_key') && $user->pushSubscriptions()->exists()) {
            $ch[] = WebPushChannel::class;
        }
        return $ch;
    }

    public function toArray(User $user): array
    {
        return ['type' => $this->type, 'params' => $this->params, 'url' => $this->url];
    }

    public function toWebPush(User $user, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Paradigm Cargo CRM')
            ->body(__('notify.'.$this->type, $this->params, $user->locale))
            ->icon('/icons/icon-192.png')
            ->data(['url' => $this->url])
            ->options(['TTL' => 86400]);
    }

    public static function text(array $data, ?string $locale = null): string
    {
        return __('notify.'.($data['type'] ?? 'generic'), $data['params'] ?? [], $locale);
    }
}
