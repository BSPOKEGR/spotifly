<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Libraries\Mandrill;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class MandrillChannel
{
    private Mandrill $mandrill;
    private string $logoImage;
    private string $brandUrl;

    /**
     * MandrillChannel constructor
     *
     * @param Mandrill $mandrill
     */
    public function __construct(Mandrill $mandrill)
    {
        $this->mandrill = $mandrill;
        $this->logoImage = Config::get('services.mandrill.logo_image');
        $this->brandUrl = Config::get('services.mandrill.brand_url');
    }

    /**
     * Send the given notification.
     *
     * @param mixed  $notifiable
     * @param Notification  $notification
     *
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        /**
         * @var ResetPasswordNotification $notification
         */
        $variables = $notification->toMandrill($notifiable);

        /**
         * if fore any reason we got not variables means we don't have anything to send
         */
        if (empty($variables)) {
            return;
        }

        $this->mandrill->setTemplate($variables['template']);

        // transform contents
        foreach ($variables['placeholders'] as $key => $value) {
            $this->mandrill->setVar($key, $value);
        }

        // add default vars for all notification messages
        $this->mandrill->setVar('logo_image', $this->logoImage);
        $this->mandrill->setVar('brand_url', $this->brandUrl);

        $this->mandrill->setRecipient($variables['recipient_email'], $variables['recipient_name']);
        $this->mandrill->setSubject($variables['subject']);

        if (!empty($variables['merge_language'])) {
            $this->mandrill->setMergeLanguage($variables['merge_language']);
        }

        $this->mandrill->send();
    }
}
