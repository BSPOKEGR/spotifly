<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\User;
use Tests\TestCase;
use Database\Seeders\UserSeeder;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\MandrillChannel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;

class ResetPasswordNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User
     */
    private $user;

    /**
     * @var ResetPasswordNotification
     */
    private $resetPasswordNotification;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->user = User::first();

        $this->helper->login($this->user);

        $this->resetPasswordNotification = new ResetPasswordNotification($this->user->remember_token);
    }

    public function testResetPasswordNotificationVia(): void
    {
        $expected = [MandrillChannel::class];
        $response = $this->resetPasswordNotification->via();
        $this->assertEquals($expected, $response);
    }

    public function testResetPasswordNotificationToMandrill(): void
    {
        $response = $this->resetPasswordNotification->toMandrill($this->user);

        $this->assertCount(5, $response);
        $this->assertEquals('spotifly-user-reset-password', $response['template']);
        $this->assertEquals('Επαναφορά Κωδικού!', $response['subject']);
        $this->assertEquals($this->user->email, $response['recipient_email']);
        $this->assertEquals($this->user->name, $response['recipient_name']);
        $this->assertCount(6, $response['placeholders']);
        $this->assertEquals('Επαναφορά Κωδικού!', $response['placeholders']['subject']);
        $this->assertEquals('Αγαπητέ/ή ' . $this->user->name . ',', $response['placeholders']['salutation']);
        $this->assertEquals('Ζητήσατε επαναφορά του Κωδικού Πρόσβασης.', $response['placeholders']['paragraph1']);
        $this->assertEquals('Παρακαλούμε ακολουθήστε τον παρακάτω σύνδεσμο για να αλλάξετε τον κωδικό σας:', $response['placeholders']['paragraph2']);
        $this->assertEquals('Ο σύνδεσμος επαναφοράς κωδικού θα λήξει σε 60 λεπτά.<br/>Εάν δε ζητήσατε επαναφορά του κωδικού πρόσβασης, αγνοείστε το μήνυμα.', $response['placeholders']['paragraph3']);
        $this->assertEquals(Config::get('auth.verification.web_client_url') . '/reset-password?token=' . $this->user->remember_token . '&email=' . $this->user->getEmailForPasswordReset(), $response['placeholders']['reset_password_url']);
    }

    public function testResetPasswordNotificationBuildEmailVars(): void
    {
        $class = new \ReflectionClass(ResetPasswordNotification::class);
        $method = $class->getMethod('buildEmailVars');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->resetPasswordNotification, [$this->user]);

        $this->assertCount(6, $response);
        $this->assertEquals('Επαναφορά Κωδικού!', $response['subject']);
        $this->assertEquals('Αγαπητέ/ή ' . $this->user->name . ',', $response['salutation']);
        $this->assertEquals('Ζητήσατε επαναφορά του Κωδικού Πρόσβασης.', $response['paragraph1']);
        $this->assertEquals('Παρακαλούμε ακολουθήστε τον παρακάτω σύνδεσμο για να αλλάξετε τον κωδικό σας:', $response['paragraph2']);
        $this->assertEquals('Ο σύνδεσμος επαναφοράς κωδικού θα λήξει σε 60 λεπτά.<br/>Εάν δε ζητήσατε επαναφορά του κωδικού πρόσβασης, αγνοείστε το μήνυμα.', $response['paragraph3']);
        $this->assertEquals(Config::get('auth.verification.web_client_url') . '/reset-password?token=' . $this->user->remember_token . '&email=' . $this->user->getEmailForPasswordReset(), $response['reset_password_url']);
    }
}
