<?php

declare(strict_types=1);

namespace App\Auth\Registration;

use App\Auth\Internal\CacheStore;
use App\Auth\Internal\ErrorMessage;
use App\Auth\UserLocator;
use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use CEvent;
use CSite;
use CUser;

/**
 * Регистрация по e-mail: пароль генерируется случайно, имя и телефон опциональны.
 * Подтверждение по коду из письма — только если включена опция ядра
 * main/new_user_registration_email_confirmation (письмо NEW_USER_CONFIRM шлёт CUser::Register).
 */
final class RegistrationService
{
    public const RESEND_INTERVAL = 60;

    private const CACHE_DIR = '/auth/registration/';
    private const REMEMBER_TTL = 86400;

    private readonly CacheStore $cache;

    public function __construct(
        private readonly UserLocator $users = new UserLocator(),
    ) {
        $this->cache = new CacheStore(self::CACHE_DIR);
    }

    /**
     * @return array{registered: true, user?: array{id: int, login: string, email: string, name: string, phone: string}, confirmation?: array{email: string, resend_after: int}}
     */
    public function register(RegisterRequest $request): array
    {
        global $USER;

        $email = $this->normalizeEmail($request->email);
        $this->assertEmailAvailable($email);

        $name = trim($request->name) !== '' ? trim($request->name) : $this->generateDisplayName();
        $password = Random::getString(16);

        $result = $USER->Register(
            $email,
            $name,
            '',
            $password,
            $password,
            $email,
            $this->siteId(),
            $request->captchaWord,
            (string) $request->captchaSid,
        );

        if (!is_array($result) || ($result['TYPE'] ?? 'OK') === 'ERROR' || empty($result['ID'])) {
            throw new SystemException(ErrorMessage::fromResult($result, 'Не удалось зарегистрироваться'));
        }

        $userId = (int) $result['ID'];
        $this->savePhone($userId, $request->phone);

        if (!$this->confirmationRequired()) {
            $USER->Authorize(
                (new Context())->setUserId($userId)->setMethod(Method::Registration),
                $request->remember,
            );

            return ['registered' => true, 'user' => $this->users->getData($userId)];
        }

        $this->putCooldown($email);
        $this->putRemember($userId, $request->remember);

        return [
            'registered' => true,
            'confirmation' => ['email' => $email, 'resend_after' => self::RESEND_INTERVAL],
        ];
    }

    /**
     * Регистрация без подтверждения (например, заказ без регистрации): создаёт
     * активного пользователя со случайным паролем и сразу авторизует. Возвращает
     * данные, включая пароль, для обработки на событиях и письма со сменой пароля.
     *
     * @param array{email: string, name?: string, phone?: string} $data
     * @return array{id: int, login: string, email: string, name: string, phone: string, password: string}
     */
    public function registerByEmail(array $data): array
    {
        global $USER;

        $email = $this->normalizeEmail((string) ($data['email'] ?? ''));
        $this->assertEmailAvailable($email);

        $name = trim((string) ($data['name'] ?? ''));
        $name = $name !== '' ? $name : $this->generateDisplayName();
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = Random::getString(16);

        $fields = [
            'LOGIN' => $email,
            'EMAIL' => $email,
            'NAME' => $name,
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'ACTIVE' => 'Y',
            'CHECKWORD' => Random::getString(32),
            'LID' => $this->siteId(),
            'LANGUAGE_ID' => defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru',
        ];

        $groups = $this->defaultGroups();

        if ($groups !== []) {
            $fields['GROUP_ID'] = $groups;
        }

        $userId = (int) $USER->Add($fields);

        if ($userId <= 0) {
            throw new SystemException(ErrorMessage::clean($USER->LAST_ERROR ?: 'Не удалось создать пользователя'));
        }

        $this->savePhone($userId, $phone);

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            true,
        );

        return [
            'id' => $userId,
            'login' => $email,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
        ];
    }

    /**
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function confirm(ConfirmRegistrationRequest $request): array
    {
        global $USER;

        $email = $this->normalizeEmail($request->email);
        $user = $this->users->findOrFail($email, 'Пользователь не найден', false);

        if ($user['ACTIVE'] === 'Y') {
            throw new SystemException('Аккаунт уже подтверждён, выполните вход');
        }

        $expected = trim((string) $user['CONFIRM_CODE']);
        $code = trim($request->code);

        if ($expected === '' || $code === '' || !hash_equals($expected, $code)) {
            throw new SystemException('Неверный код подтверждения');
        }

        $userId = (int) $user['ID'];
        $updated = $USER->Update($userId, ['ACTIVE' => 'Y', 'CONFIRM_CODE' => '']);

        if ($updated === false) {
            throw new SystemException(ErrorMessage::clean($USER->LAST_ERROR ?: 'Не удалось подтвердить аккаунт'));
        }

        $this->clearCooldown($email);

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            $request->remember || $this->takeRemember($userId),
        );

        return ['user' => $this->users->getData($userId)];
    }

    /**
     * @return array{sent: true, resend_after: int}
     */
    public function resend(string $email): array
    {
        $email = $this->normalizeEmail($email);
        $user = $this->users->find($email, false);

        $result = ['sent' => true, 'resend_after' => self::RESEND_INTERVAL];

        if ($user === null || $user['ACTIVE'] === 'Y') {
            return $result;
        }

        $secondsLeft = $this->cooldownSecondsLeft($email);

        if ($secondsLeft > 0) {
            $result['resend_after'] = $secondsLeft;

            return $result;
        }

        $this->sendConfirmation($user);
        $this->putCooldown($email);

        return $result;
    }

    private function confirmationRequired(): bool
    {
        return Option::get('main', 'new_user_registration_email_confirmation', 'N') === 'Y'
            && Option::get('main', 'new_user_email_required', 'Y') !== 'N';
    }

    private function normalizeEmail(string $email): string
    {
        $email = trim($email);

        if (!check_email($email)) {
            throw new SystemException('Укажите корректный e-mail');
        }

        return $email;
    }

    private function assertEmailAvailable(string $email): void
    {
        if ($this->users->emailExists($email)) {
            throw new SystemException('Пользователь с таким e-mail уже зарегистрирован');
        }
    }

    private function generateDisplayName(): string
    {
        return 'Пользователь ' . random_int(10000, 99999);
    }

    private function savePhone(int $userId, string $phone): void
    {
        global $USER;

        $phone = trim($phone);

        if ($phone === '') {
            return;
        }

        if ($USER->Update($userId, ['PERSONAL_PHONE' => $phone]) === false) {
            throw new SystemException(ErrorMessage::clean($USER->LAST_ERROR ?: 'Не удалось сохранить телефон'));
        }
    }

    /**
     * @return int[]
     */
    private function defaultGroups(): array
    {
        $groupId = (string) Option::get('main', 'new_user_registration_def_group', '');

        return $groupId !== '' ? array_map('intval', explode(',', $groupId)) : [];
    }

    /**
     * @param array{ID: int|string, LOGIN: string, EMAIL: string|null, NAME: string|null, LAST_NAME: string|null, CONFIRM_CODE: string|null, ...} $user
     */
    private function sendConfirmation(array $user): void
    {
        CEvent::SendImmediate('NEW_USER_CONFIRM', $this->siteId(), [
            'USER_ID' => (int) $user['ID'],
            'LOGIN' => (string) $user['LOGIN'],
            'EMAIL' => (string) $user['EMAIL'],
            'NAME' => (string) $user['NAME'],
            'LAST_NAME' => (string) $user['LAST_NAME'],
            'CONFIRM_CODE' => (string) $user['CONFIRM_CODE'],
            'USER_IP' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'USER_HOST' => '',
        ]);
    }

    private function cooldownSecondsLeft(string $email): int
    {
        $vars = $this->cache->get($this->cooldownKey($email), self::RESEND_INTERVAL);
        $sentAt = (int) ($vars['sent_at'] ?? 0);

        return max(0, self::RESEND_INTERVAL - (time() - $sentAt));
    }

    private function putCooldown(string $email): void
    {
        $this->cache->put($this->cooldownKey($email), ['sent_at' => time()], self::RESEND_INTERVAL);
    }

    private function clearCooldown(string $email): void
    {
        $this->cache->forget($this->cooldownKey($email));
    }

    private function cooldownKey(string $email): string
    {
        return 'confirm_' . md5(mb_strtolower($email));
    }

    private function putRemember(int $userId, bool $remember): void
    {
        if ($remember) {
            $this->cache->put($this->rememberKey($userId), ['remember' => true], self::REMEMBER_TTL);
        }
    }

    private function takeRemember(int $userId): bool
    {
        $key = $this->rememberKey($userId);
        $vars = $this->cache->get($key, self::REMEMBER_TTL);
        $this->cache->forget($key);

        return (bool) ($vars['remember'] ?? false);
    }

    private function rememberKey(int $userId): string
    {
        return 'remember_' . $userId;
    }

    private function siteId(): string
    {
        return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : (string) CSite::GetDefSite();
    }
}
