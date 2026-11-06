<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 60;

    private const string EMAIL_TRANSLATION_PREFIX = 'notification.user_auth.reset_password';

    /**
     * Url of web app that user can reset password.
     */
    private string $webUrl;

    /**
     * The token.
     */
    private string $token;

    /**
     * Log Channel
     */
    private string $logChannel;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     *
     * @return void
     */
    public function __construct(string $token)
    {
        // define queue parameters
        $this->queue = 'user-auth';
        $this->delay = 10;
        $this->token = $token;
        $this->webUrl = Config::get('auth.verification.web_client_url');
        $this->logChannel = Config::get('logging.queue_default');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<string>
     */
    public function via(): array
    {
        return [MandrillChannel::class];
    }

    /**
     * Get Mandrill representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array<string, mixed>
     */
    public function toMandrill($notifiable): array
    {
        Log::channel($this->logChannel)->info("User {$notifiable->id} ResetPasswordNotification...");

        $vars = $this->buildEmailVars($notifiable);

        return [
            'template' => Config::get('services.mandrill.templates.reset_password'),
            'subject' => $vars['subject'],
            'placeholders' => $vars,
            'recipient_email' => $notifiable->getEmailForPasswordReset(),
            'recipient_name' => $notifiable->name,
        ];
    }

    /**
     * Build email vars.
     *
     * @param mixed $notifiable
     *
     * @return array<string, string>
     */
    private function buildEmailVars($notifiable): array
    {
        $locale = 'el';
        $expireAfter = Config::get('auth.passwords.users.expire');

        $vars = [];
        $vars['subject'] = Lang::get(\sprintf('%s.subject', self::EMAIL_TRANSLATION_PREFIX), [], $locale);
        $vars['salutation'] = Lang::get(\sprintf('%s.salutation', self::EMAIL_TRANSLATION_PREFIX), ['name' => $notifiable->name], $locale);
        $vars['paragraph1'] = Lang::get(\sprintf('%s.paragraph1', self::EMAIL_TRANSLATION_PREFIX), [], $locale);
        $vars['paragraph2'] = Lang::get(\sprintf('%s.paragraph2', self::EMAIL_TRANSLATION_PREFIX), [], $locale);
        $vars['paragraph3'] = Lang::get(\sprintf('%s.paragraph3', self::EMAIL_TRANSLATION_PREFIX), ['count' => $expireAfter], $locale);

        $vars['reset_password_url'] = \sprintf('%s/reset-password?token=%s&email=%s', $this->webUrl, $this->token, $notifiable->getEmailForPasswordReset());

        return $vars;
    }
}
