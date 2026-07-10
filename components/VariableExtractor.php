<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\components;

use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\Invite;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\mail\MessageInterface;

class VariableExtractor
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    public static function extract(string $templateKey, array $params = [], ?MessageInterface $message = null, ?User $recipient = null): array
    {
        $variables = [
            'app_name' => (string)Yii::$app->name,
        ];

        if ($recipient === null && $message !== null) {
            $recipient = self::resolveRecipientFromMessage($message);
        }

        switch ($templateKey) {
            case 'user.invite_self':
            case 'user.invite':
            case 'user.invite_space':
                $variables = array_merge($variables, self::extractInviteVariables($params));
                break;

            case 'user.already_registered':
                $variables['password_recovery_url'] = (string)($params['passwordRecoveryUrl'] ?? Url::to(['/user/password-recovery'], true));
                break;

            case 'user.twofa_verification_code':
                self::applyRecipientVariables($variables, self::resolveUserFromParamsOrRecipient($params, $recipient));
                $variables['code'] = (string)($params['code'] ?? '');
                $variables['date_time'] = Yii::$app->formatter->asDatetime(time());
                break;

            case 'user.password_recovery':
                self::applyRecipientVariables($variables, self::resolveUserFromParamsOrRecipient($params, $recipient));
                $variables['password_reset_url'] = (string)($params['linkPasswordReset'] ?? '');
                break;

            case 'user.change_email':
                self::applyRecipientVariables($variables, self::resolveUserFromParamsOrRecipient($params, $recipient));
                $variables['confirm_url'] = (string)($params['approveUrl'] ?? $params['link'] ?? $params['confirmUrl'] ?? '');
                break;

            case 'user.change_username':
                self::applyRecipientVariables($variables, self::resolveUserFromParamsOrRecipient($params, $recipient));
                break;

            case 'magic_link_login':
                self::applyRecipientVariables($variables, self::resolveUserFromParamsOrRecipient($params, $recipient));
                $variables['magic_link_url'] = (string)($params['magicLinkUrl'] ?? $params['magic_link_url'] ?? '');
                $variables['expiry_minutes'] = (string)($params['expiryMinutes'] ?? $params['expiry_minutes'] ?? '');
                break;

            case 'admin.registration_approval':
            case 'admin.registration_decline':
            case 'admin.registration_message':
                $variables = array_merge($variables, self::extractAdminRegistrationVariables($params, $recipient));
                break;

            case 'activity.mail_summary':
                self::applyRecipientVariables($variables, self::resolveUserFromParamsOrRecipient($params, $recipient));
                $variables['activities'] = (string)($params['activities'] ?? $params['content'] ?? '');
                break;

            default:
                if (str_starts_with($templateKey, 'notification.')) {
                    $variables = array_merge($variables, self::extractNotificationVariables($params));
                }
                break;
        }

        if ($message !== null) {
            $subject = $message->getSubject();
            if (is_string($subject) && $subject !== '' && empty($variables['headline'])) {
                $variables['headline'] = $subject;
            }
        }

        self::applyUrlFallbacks($templateKey, $variables, $params, $recipient);

        return array_map(static fn($value) => is_scalar($value) ? (string)$value : '', $variables);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private static function extractInviteVariables(array $params): array
    {
        $variables = [
            'registration_url' => (string)($params['registrationUrl'] ?? ''),
            'originator_name' => (string)($params['originatorName'] ?? ''),
            'space_name' => '',
        ];

        if ($variables['registration_url'] === '') {
            $invite = self::findInviteFromTrace();
            if ($invite instanceof Invite) {
                $variables['registration_url'] = Url::to(['/user/registration', 'token' => $invite->token], true);
            }
        }

        $originator = $params['originator'] ?? null;
        if ($originator instanceof User) {
            $variables['originator_name'] = $originator->displayName;
        }

        $space = $params['space'] ?? null;
        if ($space !== null && method_exists($space, 'getName')) {
            $variables['space_name'] = (string)$space->name;
        }

        return $variables;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private static function extractAdminRegistrationVariables(array $params, ?User $recipient = null): array
    {
        $variables = [
            'display_name' => '',
            'first_name' => '',
            'admin_name' => '',
            'message' => '',
            'login_url' => '',
            'login_link' => '',
        ];

        self::applyRecipientVariables($variables, $recipient);

        $admin = Yii::$app->has('user', true) ? Yii::$app->user->identity : null;
        if ($admin instanceof User) {
            $variables['admin_name'] = Html::encode($admin->displayName);
        }

        $rawMessage = (string)($params['message'] ?? '');
        if ($rawMessage !== '') {
            $variables['message'] = trim(strip_tags(html_entity_decode($rawMessage, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }

        $loginUrl = Url::to(['/user/auth/login'], true);
        $variables['login_url'] = urldecode($loginUrl);
        $variables['login_link'] = Html::a($variables['login_url'], $loginUrl);

        return $variables;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private static function extractNotificationVariables(array $params): array
    {
        $variables = [
            'headline' => (string)($params['headline'] ?? ''),
            'content' => (string)($params['content'] ?? ''),
            'space_name' => '',
            'originator_name' => '',
        ];

        $notification = $params['notification'] ?? null;
        if ($notification instanceof BaseNotification) {
            $variables['headline'] = $variables['headline'] ?: $notification->getMailSubject();
            $originator = $notification->originator;
            if ($originator instanceof User) {
                $variables['originator_name'] = $originator->displayName;
            }
            $space = $notification->getSpace();
            if ($space !== null) {
                $variables['space_name'] = (string)$space->name;
            }
        }

        return $variables;
    }

    /**
     * @param array<string, string> $variables
     */
    private static function applyRecipientVariables(array &$variables, ?User $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (empty($variables['display_name'])) {
            $variables['display_name'] = Html::encode($user->displayName);
        }

        if (empty($variables['first_name'])) {
            $variables['first_name'] = self::resolveFirstName($user);
        }
    }

    private static function resolveFirstName(User $user): string
    {
        $profile = $user->profile;
        if ($profile !== null && !empty($profile->firstname)) {
            return Html::encode((string)$profile->firstname);
        }

        return Html::encode((string)($user->username ?? ''));
    }

    private static function resolveUserFromParamsOrRecipient(array $params, ?User $recipient): ?User
    {
        $user = $params['user'] ?? null;
        if ($user instanceof User) {
            return $user;
        }

        return $recipient;
    }

    private static function resolveRecipientFromMessage(MessageInterface $message): ?User
    {
        $to = $message->getTo();
        $email = is_array($to) ? array_key_first($to) : $to;
        if (!is_string($email) || $email === '') {
            return null;
        }

        return User::findOne(['email' => $email]);
    }

    /**
     * @param array<string, string> $variables
     * @param array<string, mixed> $params
     */
    private static function applyUrlFallbacks(string $templateKey, array &$variables, array $params, ?User $recipient): void
    {
        if (empty($variables['registration_url']) && str_starts_with($templateKey, 'user.invite')) {
            $invite = self::findInviteFromTrace();
            if ($invite instanceof Invite) {
                $variables['registration_url'] = Url::to(['/user/registration', 'token' => $invite->token], true);
            }
        }

        if (empty($variables['password_reset_url']) && $templateKey === 'user.password_recovery') {
            $variables['password_reset_url'] = (string)($params['linkPasswordReset'] ?? '');
        }

        if (empty($variables['password_recovery_url']) && $templateKey === 'user.already_registered') {
            $variables['password_recovery_url'] = (string)($params['passwordRecoveryUrl'] ?? Url::to(['/user/password-recovery'], true));
        }

        if (empty($variables['confirm_url']) && $templateKey === 'user.change_email') {
            $variables['confirm_url'] = (string)($params['approveUrl'] ?? $params['link'] ?? $params['confirmUrl'] ?? '');
        }

        if (empty($variables['magic_link_url']) && $templateKey === 'magic_link_login') {
            $variables['magic_link_url'] = (string)($params['magicLinkUrl'] ?? $params['magic_link_url'] ?? '');
        }

        if (empty($variables['expiry_minutes']) && $templateKey === 'magic_link_login') {
            $variables['expiry_minutes'] = (string)($params['expiryMinutes'] ?? $params['expiry_minutes'] ?? '');
        }

        if (empty($variables['login_url'])) {
            $variables['login_url'] = urldecode(Url::to(['/user/auth/login'], true));
        }

        if (empty($variables['login_link'])) {
            $variables['login_link'] = Html::a($variables['login_url'], $variables['login_url']);
        }

        if ($recipient instanceof User) {
            self::applyRecipientVariables($variables, $recipient);
        }
    }

    private static function findInviteFromTrace(): ?Invite
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
        foreach ($trace as $frame) {
            if (($frame['class'] ?? '') === Invite::class
                && ($frame['function'] ?? '') === 'sendInviteMail'
                && isset($frame['object'])
                && $frame['object'] instanceof Invite) {
                return $frame['object'];
            }
        }

        return null;
    }

    /**
     * Sample variables for previews.
     *
     * @return array<string, string>
     */
    public static function getPreviewVariables(string $templateKey): array
    {
        $samples = [
            'app_name' => (string)Yii::$app->name,
            'display_name' => 'Jane Doe',
            'first_name' => 'Jane',
            'user_name' => 'Jane Doe',
            'user_email' => 'jane.doe@example.com',
            'originator_name' => 'John Admin',
            'admin_name' => 'John Admin',
            'space_name' => 'Example Space',
            'registration_url' => Url::to(['/user/registration'], true),
            'password_recovery_url' => Url::to(['/user/password-recovery'], true),
            'password_reset_url' => Url::to(['/user/password-recovery/reset'], true),
            'confirm_url' => Url::to(['/user/account/edit'], true),
            'login_url' => Url::to(['/user/auth/login'], true),
            'login_link' => Html::a('Sign in', Url::to(['/user/auth/login'], true)),
            'headline' => 'Example notification headline',
            'content' => '<p>This is an example notification body with <strong>formatted text</strong>.</p>',
            'activities' => '<ul><li>Alice posted an update</li><li>Bob commented on your post</li></ul>',
            'message' => 'Thank you for registering. We need a little more information before we can approve your account.',
            'application_date' => date('Y-m-d H:i'),
            'application_answers' => "Question: Why do you want to join?\nAnswer: To collaborate with the community.",
            'decline_reason' => 'Incomplete profile information',
            'admin_notes' => 'Please update your profile and apply again.',
            'code' => '123456',
            'date_time' => date('Y-m-d H:i:s'),
            'magic_link_url' => Url::to(['/magic-link-auth/auth/verify', 'token' => 'preview-token', 'guid' => '00000000-0000-0000-0000-000000000000'], true),
            'expiry_minutes' => '10',
        ];

        $definition = EmailDefinitionRegistry::getDefinition($templateKey);
        if ($definition === null) {
            return $samples;
        }

        $filtered = [];
        foreach (array_keys($definition['variables'] ?? []) as $variable) {
            $filtered[$variable] = $samples[$variable] ?? '{' . $variable . '}';
        }

        return array_merge($samples, $filtered);
    }
}
