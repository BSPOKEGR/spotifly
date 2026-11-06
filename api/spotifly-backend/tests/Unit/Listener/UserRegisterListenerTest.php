<?php

declare(strict_types=1);

namespace Tests\Unit\Listener;

use App\Events\UserRegisterEvent;
use App\Libraries\Mandrill;
use App\Listeners\UserRegisterListener;
use App\Models\User;
use App\Services\UserRegisterService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use MailchimpTransactional\ApiClient;
use Tests\TestCase;

class UserRegisterListenerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private UserRegisterListener $userRegisterListener;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->user = User::orderBy('id')->first();

        $this->helper->login($this->user);

        $this->userRegisterListener = new UserRegisterListener(new Mandrill(new ApiClient()), new UserRegisterService());
    }

    public function testIsListenerAttached(): void
    {
        Event::fake();
        Event::assertListening(
            UserRegisterEvent::class,
            UserRegisterListener::class
        );
    }

    public function testIsEventDispatched(): void
    {
        Event::fake();

        event(new UserRegisterEvent($this->user));

        Event::assertDispatched(UserRegisterEvent::class);

        event(new UserRegisterEvent($this->user, 'test'));

        Event::assertDispatched(UserRegisterEvent::class);
    }

    public function testHandle(): void
    {
        $this->assertFalse($this->user->settings->notifications);

        $this->userRegisterListener->handle(new UserRegisterEvent($this->user));

        $this->assertTrue($this->user->settings->notifications);
    }
}
