<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Dto\UsersRegister\VerificationUrlData;
use App\Dto\UsersRegister\BuildEmailVarsRequestData;
use App\Services\UserRegisterService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class UserRegisterServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private UserRegisterService $userRegisterService;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->user = User::orderBy('id')->first();

        $this->userRegisterService = new UserRegisterService();
    }

    public function testVerificationUrl(): void
    {
        $verificationUrlDataArray = [
            'user_id' => 1,
            'email' => 'test@email.dummyagro',
        ];

        $verificationUrlData = VerificationUrlData::from($verificationUrlDataArray);

        $actual = $this->userRegisterService->verificationUrl($verificationUrlData);

        $expected = '/email-confirmation?userId=1&signature=';

        $this->assertStringStartsWith($expected, $actual);
        $this->assertStringEndsWith('hash=' . sha1('test@email.dummyagro'), $actual);
    }

    public function testBuildEmailVarsWithoutPassword(): void
    {
        $logoImage = 'logo-image.png';
        $brandUrl = 'https://test.bspoke.gr';
        Config::set('services.mandrill.logo_image', $logoImage);
        Config::set('services.mandrill.brand_url', $brandUrl);

        $buildEmailVarsRequestDataArray = [
            'user' => $this->user,
            'web_url' => 'https://test.bspoke.gr',
            'password' => null,
        ];

        $buildEmailVarsRequestData = BuildEmailVarsRequestData::from($buildEmailVarsRequestDataArray);

        $response = $this->userRegisterService->buildEmailVars($buildEmailVarsRequestData);

        $expected = [
            'subject' => 'Καλώς ήρθατε!',
            'title' => 'Καλώς ήρθατε!',
            'salutation' => 'Αγαπητέ/ή ' . $this->user->name . ',',
            'welcome1' => 'Ο λογαριασμός σας δημιουργήθηκε με επιτυχία και μπορείτε να ξεκινήσετε την περιήγηση σας στην εφαρμογή! Προσθέστε τα χωράφια σας και λάβετε συμβουλές, ώστε να οργανώσετε καλύτερα τις καθημερινές σας εργασίες.',
            'login_info' => null,
            'goodbye' => null,
            'login_url' => null,
            'confirm_button' => null,
            'confirm_info' => null,
            'confirm_url' => null,
            'logo_image' => 'logo-image.png',
            'brand_url' => 'https://test.bspoke.gr',
        ];

        $this->assertEquals($expected, $response->toArray());
    }

    public function testBuildEmailVarsWithPassword(): void
    {
        $logoImage = 'logo-image.png';
        $brandUrl = 'https://test.bspoke.gr';
        Config::set('services.mandrill.logo_image', $logoImage);
        Config::set('services.mandrill.brand_url', $brandUrl);

        $buildEmailVarsRequestDataArray = [
            'user' => $this->user,
            'web_url' => 'https://test.bspoke.gr',
            'password' => 'test',
        ];

        $buildEmailVarsRequestData = BuildEmailVarsRequestData::from($buildEmailVarsRequestDataArray);

        $response = $this->userRegisterService->buildEmailVars($buildEmailVarsRequestData);

        $expected = [
            'subject' => 'Καλώς ήρθατε!',
            'title' => 'Καλώς ήρθατε!',
            'salutation' => 'Αγαπητέ/ή ' . $this->user->name . ',',
            'welcome1' => 'Ο λογαριασμός σας δημιουργήθηκε με επιτυχία και μπορείτε να ξεκινήσετε την περιήγηση σας στην εφαρμογή! Προσθέστε τα χωράφια σας και λάβετε συμβουλές, ώστε να οργανώσετε καλύτερα τις καθημερινές σας εργασίες.',
            'login_info' => 'Στη συνέχεια ακολουθήστε τον διαθέσιμο σύνδεσμο και χρησιμοποιήστε τα παρακάτω στοιχεία για να συνδεθείτε:<br/><br/>Email: ' . $this->user->email . '<br/>Κωδικός Πρόσβασης: test',
            'goodbye' => 'Η ομάδα της Bspoke',
            'login_url' => 'https://test.bspoke.gr',
            'confirm_button' => null,
            'confirm_info' => null,
            'confirm_url' => null,
            'logo_image' => $logoImage,
            'brand_url' => $brandUrl,
        ];

        $this->assertEquals($expected, $response->toArray());

        // email not verified
        $this->user->email_verified_at = null;
        $this->user->save();

        $response = $this->userRegisterService->buildEmailVars($buildEmailVarsRequestData);

        $verificationUrlDataArray = [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
        ];

        $verificationUrlData = VerificationUrlData::from($verificationUrlDataArray);

        $url = $this->userRegisterService->verificationUrl($verificationUrlData);

        $expected = [
            'subject' => 'Καλώς ήρθατε!',
            'title' => 'Καλώς ήρθατε!',
            'salutation' => 'Αγαπητέ/ή ' . $this->user->name . ',',
            'welcome1' => 'Ο λογαριασμός σας δημιουργήθηκε με επιτυχία και μπορείτε να ξεκινήσετε την περιήγηση σας στην εφαρμογή! Προσθέστε τα χωράφια σας και λάβετε συμβουλές, ώστε να οργανώσετε καλύτερα τις καθημερινές σας εργασίες.',
            'login_info' => 'Στη συνέχεια ακολουθήστε τον διαθέσιμο σύνδεσμο και χρησιμοποιήστε τα παρακάτω στοιχεία για να συνδεθείτε:<br/><br/>Email: ' . $this->user->email . '<br/>Κωδικός Πρόσβασης: test',
            'goodbye' => 'Η ομάδα της Bspoke',
            'login_url' => 'https://test.bspoke.gr',
            'confirm_button' => 'Επιβεβαίωση',
            'confirm_info' => 'Για να συνδεθείτε για πρώτη φορά στην εφαρμογή, ακολουθήστε τον παρακάτω σύνδεσμο, για να επιβεβαιώσετε τον λογαριασμό σας:',
            'confirm_url' => 'https://test.bspoke.gr' . $url,
            'logo_image' => $logoImage,
            'brand_url' => $brandUrl,
        ];

        $this->assertEquals($expected, $response->toArray());
    }
}
