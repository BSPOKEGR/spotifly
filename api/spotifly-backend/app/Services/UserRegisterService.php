<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\UsersRegister\BuildEmailVarsRequestData;
use App\Dto\UsersRegister\BuildEmailVarsResponseData;
use App\Dto\UsersRegister\VerificationUrlData;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class UserRegisterService
{
    private const string EMAIL_TRANSLATION_PREFIX = 'notification.user_auth.register';

    /**
     * Get the verification URL for the given user.
     *
     * @param VerificationUrlData $verificationUrlData
     *
     * @return string
     */
    public function verificationUrl(VerificationUrlData $verificationUrlData): string
    {
        $url = URL::signedRoute(
            'verification.verify',
            [
                'id' => $verificationUrlData->userId,
                'hash' => sha1($verificationUrlData->email),
            ],
            absolute: false
        );

        $signature = \str_replace('?signature=', '', \strstr($url, '?signature='));
        $newUrl = '/email-confirmation?userId=' . $verificationUrlData->userId . '&signature=' . $signature . '&hash=' . sha1($verificationUrlData->email);

        return $newUrl;
    }

    /**
     * Build email vars.
     *
     * @param BuildEmailVarsRequestData $buildEmailVarsData
     *
     * @return BuildEmailVarsResponseData
     */
    public function buildEmailVars(BuildEmailVarsRequestData $buildEmailVarsData): BuildEmailVarsResponseData
    {
        $locale = 'el';

        $vars = [];
        $vars['subject'] = Lang::get(\sprintf('%s.subject', self::EMAIL_TRANSLATION_PREFIX), [], $locale);
        $vars['title'] = $vars['subject'];
        $vars['salutation'] = Lang::get(\sprintf('%s.salutation', self::EMAIL_TRANSLATION_PREFIX), ['name' => $buildEmailVarsData->user->name], $locale);

        $vars['welcome1'] = Lang::get(\sprintf('%s.%s', self::EMAIL_TRANSLATION_PREFIX, 'welcome1'), [], $locale);

        if (null !== $buildEmailVarsData->password) {
            // we need to use htmlspecialchars in order for email provider to not handle any password character as special
            $passwordEncoded =  htmlspecialchars($buildEmailVarsData->password, ENT_QUOTES, 'UTF-8');

            $vars['login_info'] = Lang::get(\sprintf('%s.login_info', self::EMAIL_TRANSLATION_PREFIX), ['email' => $buildEmailVarsData->user->email, 'password' => $passwordEncoded], $locale);
            $vars['goodbye'] = Lang::get(\sprintf('%s.goodbye', self::EMAIL_TRANSLATION_PREFIX), [], $locale);
            $vars['login_url'] = $buildEmailVarsData->webUrl;

            if (null === $buildEmailVarsData->user->email_verified_at) {
                $vars['confirm_button'] = Lang::get(\sprintf('%s.confirm_button', self::EMAIL_TRANSLATION_PREFIX), [], $locale);
                $vars['confirm_info'] = Lang::get(\sprintf('%s.confirm_info', self::EMAIL_TRANSLATION_PREFIX), ['email' => $buildEmailVarsData->user->email, 'password' => $passwordEncoded], $locale);
                $verificationUrlData = new VerificationUrlData($buildEmailVarsData->user->id, $buildEmailVarsData->user->getEmailForVerification());
                $vars['confirm_url'] = $buildEmailVarsData->webUrl . $this->verificationUrl($verificationUrlData);
            }
        }

        $vars['logo_image'] = Config::get('services.mandrill.logo_image');
        $vars['brand_url'] = Config::get('services.mandrill.brand_url');

        $buildEmailVarsResponseDataArray = [
            'subject' => $vars['subject'],
            'title' => $vars['title'],
            'salutation' => $vars['salutation'],
            'welcome1' => $vars['welcome1'],
            'login_info' => $vars['login_info'] ?? null,
            'goodbye' => $vars['goodbye'] ?? null,
            'login_url' => $vars['login_url'] ?? null,
            'confirm_button' => $vars['confirm_button'] ?? null,
            'confirm_info' => $vars['confirm_info'] ?? null,
            'confirm_url' => $vars['confirm_url'] ?? null,
            'logo_image' => $vars['logo_image'],
            'brand_url' => $vars['brand_url'],
        ];

        return BuildEmailVarsResponseData::from($buildEmailVarsResponseDataArray);
    }
}
