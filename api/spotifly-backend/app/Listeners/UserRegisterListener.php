<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Dto\UsersRegister\BuildEmailVarsRequestData;
use App\Events\UserRegisterEvent;
use App\Libraries\Mandrill;
use App\Services\UserRegisterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class UserRegisterListener implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'user-auth';

    /**
     * The time (seconds) before the job should be processed.
     */
    public int $delay = 10;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of seconds before the job is retried in case of a timeout
     */
    public int $backoff = 300;

    /**
     * Url of web app that user can verify email.
     */
    private string $webUrl;

    /**
     * Log Channel
     */
    private string $logChannel;

    /**
     * Create the event listener.
     *
     * @param Mandrill $mandrill
     * @param UserRegisterService $userRegisterService
     *
     * @return void
     */
    public function __construct(
        private Mandrill $mandrill,
        private readonly UserRegisterService $userRegisterService
    ) {
        $this->mandrill = $mandrill;
        $this->webUrl = Config::get('auth.verification.web_client_url');
        $this->logChannel = Config::get('logging.queue_default');
    }

    /**
     * Handle the event.
     *
     * @param UserRegisterEvent $event
     *
     * @return void
     */
    public function handle(UserRegisterEvent $event)
    {
        $user = $event->getUser();

        Log::channel($this->logChannel)->info("User {$user->id} UserRegisterEvent start...");

        $password = $event->getPassword();
        $buildEmailVarsData = new BuildEmailVarsRequestData($user, $this->webUrl, $password);
        $data = $this->userRegisterService->buildEmailVars($buildEmailVarsData);
        $subject = $data->subject;

        $this->mandrill->setTemplate(Config::get('services.mandrill.templates.register_user'));
        $this->mandrill->setRecipient($user->email, $user->name);
        $this->mandrill->setSubject($subject);
        $this->mandrill->setVars($data->toArray());
        $this->mandrill->send();

        $user->settings->notifications = true;
        $user->push();

        Log::channel($this->logChannel)->info("User {$user->id} UserRegisterEvent end...");
    }

    /**
     * Handle failed events
     *
     * @param UserRegisterEvent $event
     * @param mixed $exception
     *
     * @return void
     */
    public function failed(UserRegisterEvent $event, $exception)
    {
        $user = $event->getUser();
        Log::channel($this->logChannel)->critical("User {$user->id} UserRegisterEvent failed: $exception");
    }
}
