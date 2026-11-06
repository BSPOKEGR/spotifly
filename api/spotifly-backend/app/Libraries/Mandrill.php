<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Mail\MandrillFallBack;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use MailchimpTransactional\ApiClient as MailChimpClient;

class Mandrill
{
    private const string MODE_NORMAL = 'normal';
    private const string MODE_WHITELIST = 'whitelist';

    public const CONFIG_PREFIX = 'services.mandrill';

    private MailChimpClient $mailChimpClient;
    private string $template;
    /**
     * @var array<string, string>
     */
    private array $recipient;
    /**
     * @var array<string, string>
     */
    private array $vars;

    private array $contents;
    /**
     * @var array<string, string|bool|array<string, string|array<string, string>>>
     */
    private array $message;
    private string $mode;
    /**
     * @var array<string>
     */
    private array $whitelist;

    /**
     * Mandrill constructor.
     *
     * @param MailChimpClient $mailChimpClient
     *
     * @return void
     */
    public function __construct(MailChimpClient $mailChimpClient)
    {
        $modeSetting = Config::get(\sprintf('%s.mode', self::CONFIG_PREFIX));
        $this->mode = \in_array($modeSetting, [self::MODE_NORMAL, self::MODE_WHITELIST]) ? $modeSetting : self::MODE_WHITELIST;
        $whitelistSetting = Config::get(\sprintf('%s.white_list', self::CONFIG_PREFIX));
        $this->whitelist = explode(',', $whitelistSetting);

        $apiKey = Config::get(\sprintf('%s.api_key', self::CONFIG_PREFIX));
        $senderEmail = Config::get(\sprintf('%s.sender_email', self::CONFIG_PREFIX));
        $senderName = Config::get(\sprintf('%s.sender_name', self::CONFIG_PREFIX));

        if (!empty($apiKey)) {
            $this->mailChimpClient = $mailChimpClient;
            $this->mailChimpClient->setApiKey($apiKey);
        } else {
            Log::error('Mandrill KEY not set!');
        }

        $this->message = [
            'subject' => '',
            'from_email' => $senderEmail,
            'from_name' => $senderName,
            'merge' => false, // will automatically be set to true if merge_vars are provided
            'global_merge_vars' => [],
            'merge_vars' => [],
        ];

        $this->resetContents();
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function setRecipient(string $email, string $name): void
    {
        $this->recipient = [
            'email' => $email,
            'name' => $name,
            'type' => 'to'
        ];
    }

    public function setSubject(string $subject): void
    {
        $this->message['subject'] = $subject;
    }

    public function setVar(string $name, mixed $value): void
    {
        $this->vars[] = [
            'name' => $name,
            'content' => $value
        ];
    }

    /**
     * @param array<string, string> $vars
     */
    public function setVars(array $vars): void
    {
        foreach ($vars as $name => $value) {
            $this->setVar($name, $value);
        }
    }

    public function resetContents(): void
    {
        $this->vars = [];
        $this->contents = [];
    }

    public function setMergeLanguage(string $mergeLanguage): void
    {
        $this->message['merge_language'] = $mergeLanguage;
    }

    public function send(): bool
    {
        $this->message['to'] = [$this->recipient];

        $this->message['merge_vars'] = [
            [
                'rcpt' => $this->recipient['email'],
                'vars' => $this->vars
            ]
        ];

        // normal & whitelist
        if ($this->mode === self::MODE_NORMAL || ($this->mode === self::MODE_WHITELIST && in_array($this->recipient['email'], $this->whitelist))) {
            Log::info("Sending email to '" . $this->recipient['email'] . "'...");
            // Send the message!
            $this->mailChimpClient->messages->sendTemplate([
                'template_name' => $this->template,
                'template_content' => $this->vars,
                'message' => $this->message,
            ]);

            $this->resetContents();

            return true;
        } elseif ($this->mode === self::MODE_WHITELIST) {
            Log::info("Recipient '" . $this->recipient['email'] . "' is not in the whitelist!");
            // fallback to mail
            Mail::to($this->recipient['email'])->send(new MandrillFallBack($this->message['subject'], $this->vars));
            $this->resetContents();

            return true;
        }

        return false;
    }
}
