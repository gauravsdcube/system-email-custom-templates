<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\components;

use humhub\modules\admin\models\forms\ApproveUserForm;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\user\models\Invite;
use Yii;
use yii\base\Component;
use yii\base\Event;

class EmailDefinitionRegistry extends Component
{
    public const EVENT_REGISTER = 'registerDefinitions';

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $definitions = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getDefinitions(): array
    {
        if (self::$definitions === null) {
            self::$definitions = self::buildCoreDefinitions();
            Event::trigger(self::class, self::EVENT_REGISTER);
        }

        return self::$definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getDefinitionsByCategory(): array
    {
        $grouped = [];
        foreach (self::getDefinitions() as $key => $definition) {
            $category = $definition['category'] ?? 'general';
            $grouped[$category][$key] = $definition;
        }

        ksort($grouped);
        return $grouped;
    }

    public static function getDefinition(string $key): ?array
    {
        return self::getDefinitions()[$key] ?? null;
    }

    public static function register(array $definition): void
    {
        if (empty($definition['key'])) {
            return;
        }

        if (self::$definitions === null) {
            self::getDefinitions();
        }

        self::$definitions[$definition['key']] = $definition;
    }

    /**
     * Resolve a template key from a mail compose call.
     *
     * @param mixed $view
     * @param array $params
     */
    public static function resolveKey($view, array $params = []): ?string
    {
        if (isset($params['notification']) && $params['notification'] instanceof BaseNotification) {
            $notificationKey = 'notification.' . self::notificationClassToKey($params['notification']::class);
            if (isset(self::getDefinitions()[$notificationKey])) {
                return $notificationKey;
            }
        }

        $htmlView = self::extractHtmlView($view);
        if ($htmlView !== null) {
            foreach (self::getDefinitions() as $key => $definition) {
                if (!empty($definition['view']) && self::normalizeViewPath($definition['view']) === self::normalizeViewPath($htmlView)) {
                    return $key;
                }
            }
        }

        $backtraceKey = self::resolveFromBacktrace();
        if ($backtraceKey !== null) {
            return $backtraceKey;
        }

        return null;
    }

    /**
     * @param mixed $view
     */
    private static function extractHtmlView($view): ?string
    {
        if (is_string($view)) {
            return $view;
        }

        if (is_array($view) && !empty($view['html'])) {
            return (string)$view['html'];
        }

        return null;
    }

    private static function normalizeViewPath(string $path): string
    {
        return str_replace('\\', '/', Yii::getAlias($path));
    }

    private static function notificationClassToKey(string $class): string
    {
        $class = str_replace('\\', '.', $class);
        $class = preg_replace('/^humhub\.modules\./', '', $class);
        $class = preg_replace('/\.notifications\./', '.', $class);
        $class = preg_replace('/Notification$/', '', $class);

        return strtolower((string)$class);
    }

    private static function resolveFromBacktrace(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        foreach ($trace as $frame) {
            $class = $frame['class'] ?? '';
            $function = $frame['function'] ?? '';

            if ($class === ApproveUserForm::class) {
                if ($function === 'setApprovalDefaults' || $function === 'approve') {
                    return 'admin.registration_approval';
                }
                if ($function === 'setDeclineDefaults' || $function === 'decline') {
                    return 'admin.registration_decline';
                }
                if ($function === 'setSendMessageDefaults' || $function === 'send') {
                    return 'admin.registration_message';
                }
            }

            if ($class === Invite::class && $function === 'sendInviteMail') {
                return self::resolveInviteKeyFromTrace($trace);
            }
        }

        return null;
    }

  /**
   * @param array<int, array<string, mixed>> $trace
   */
    private static function resolveInviteKeyFromTrace(array $trace): ?string
    {
        foreach ($trace as $frame) {
            if (($frame['class'] ?? '') !== Invite::class || ($frame['function'] ?? '') !== 'sendInviteMail') {
                continue;
            }

            if (!isset($frame['object']) || !$frame['object'] instanceof Invite) {
                continue;
            }

            /** @var Invite $invite */
            $invite = $frame['object'];

            if ($invite->source === Invite::SOURCE_SELF || $invite->source === Invite::SOURCE_INVITE_BY_LINK) {
                return 'user.invite_self';
            }
            if ($invite->source === Invite::SOURCE_INVITE && $invite->space !== null) {
                return 'user.invite_space';
            }
            if ($invite->source === Invite::SOURCE_INVITE) {
                return 'user.invite';
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function buildCoreDefinitions(): array
    {
        $appName = Yii::$app->name;

        $definitions = [
            'user.invite_self' => self::definition(
                'user.invite_self',
                Yii::t('SystemEmailCustomizerModule.base', 'Self-registration invite'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when a user requests a registration link for themselves.'),
                Yii::t('SystemEmailCustomizerModule.base', 'Immediately after a user submits their email on the registration page.'),
                '@humhub/modules/user/views/mails/UserInviteSelf',
                [
                    'registration_url' => Yii::t('SystemEmailCustomizerModule.base', 'Registration link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Welcome to {app_name}',
                    'header' => '<h2 style="margin:0;">Welcome to {app_name}</h2>',
                    'body' => "Hello,\n\nThank you for your interest in {app_name}. Please click the button below to complete your registration:\n\n{button:Sign up|{registration_url}}\n\nIf you did not request this email, you can safely ignore it.",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'user.invite' => self::definition(
                'user.invite',
                Yii::t('SystemEmailCustomizerModule.base', 'User invitation'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when an administrator invites someone to join the platform.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an admin creates a new user invitation from the admin panel.'),
                '@humhub/modules/user/views/mails/UserInvite',
                [
                    'originator_name' => Yii::t('SystemEmailCustomizerModule.base', 'Name of the person who sent the invite'),
                    'registration_url' => Yii::t('SystemEmailCustomizerModule.base', 'Registration link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'You\'ve been invited to join {app_name}',
                    'header' => '<h2 style="margin:0;">You\'ve been invited</h2>',
                    'body' => "Hello,\n\n{originator_name} has invited you to join {app_name}.\n\n{button:Sign up|{registration_url}}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'user.invite_space' => self::definition(
                'user.invite_space',
                Yii::t('SystemEmailCustomizerModule.base', 'Space invitation'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when a user is invited to join a specific space.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a space member invites someone by email.'),
                '@humhub/modules/user/views/mails/UserInviteSpace',
                [
                    'originator_name' => Yii::t('SystemEmailCustomizerModule.base', 'Name of the person who sent the invite'),
                    'space_name' => Yii::t('SystemEmailCustomizerModule.base', 'Space name'),
                    'registration_url' => Yii::t('SystemEmailCustomizerModule.base', 'Registration link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'You\'ve been invited to join {space_name} on {app_name}',
                    'header' => '<h2 style="margin:0;">Join {space_name}</h2>',
                    'body' => "Hello,\n\n{originator_name} has invited you to join the space **{space_name}** on {app_name}.\n\n{button:Sign up|{registration_url}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'user.already_registered' => self::definition(
                'user.already_registered',
                Yii::t('SystemEmailCustomizerModule.base', 'Already registered notice'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when someone requests an invite but already has an account.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an invite is requested for an email that is already registered.'),
                '@humhub/modules/user/views/mails/UserAlreadyRegistered',
                [
                    'password_recovery_url' => Yii::t('SystemEmailCustomizerModule.base', 'Password recovery link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Welcome to {app_name}',
                    'header' => '<h2 style="margin:0;">Account already exists</h2>',
                    'body' => "Hello,\n\nIt looks like you already have an account. You can reset your password here:\n\n{password_recovery_url}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'user.password_recovery' => self::definition(
                'user.password_recovery',
                Yii::t('SystemEmailCustomizerModule.base', 'Password recovery'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when a user requests a password reset.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a user submits the forgot-password form.'),
                '@humhub/modules/user/views/mails/RecoverPassword',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'password_reset_url' => Yii::t('SystemEmailCustomizerModule.base', 'Password reset link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Password Recovery',
                    'header' => '<h2 style="margin:0;">Password recovery</h2>',
                    'body' => "Hello {display_name},\n\nPlease click the button below within the next 24 hours to reset your password:\n\n{button:Reset password|{password_reset_url}}\n\nIf you did not request a password reset, please ignore this email.",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'user.change_email' => self::definition(
                'user.change_email',
                Yii::t('SystemEmailCustomizerModule.base', 'Email change confirmation'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when a user changes their email address.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a user updates their account email in account settings.'),
                '@humhub/modules/user/views/mails/ChangeEmail',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'confirm_url' => Yii::t('SystemEmailCustomizerModule.base', 'Email confirmation link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Confirm your new email address',
                    'header' => '<h2 style="margin:0;">Confirm your email</h2>',
                    'body' => "Hello {display_name},\n\nPlease confirm your new email address:\n\n{button:Confirm email|{confirm_url}}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'user.change_username' => self::definition(
                'user.change_username',
                Yii::t('SystemEmailCustomizerModule.base', 'Username change confirmation'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when a user changes their username.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a user updates their username in account settings.'),
                '@humhub/modules/user/views/mails/ChangeUsername',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'confirm_url' => Yii::t('SystemEmailCustomizerModule.base', 'Username confirmation link'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Confirm your new username',
                    'header' => '<h2 style="margin:0;">Confirm your username</h2>',
                    'body' => "Hello {display_name},\n\nPlease confirm your new username:\n\n{button:Confirm username|{confirm_url}}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'admin.registration_approval' => self::definition(
                'admin.registration_approval',
                Yii::t('SystemEmailCustomizerModule.base', 'Registration approved'),
                'admin',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent to a user when their registration request is approved.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an administrator approves a pending user registration.'),
                '@humhub/views/mail/TextOnly',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'admin_name' => Yii::t('SystemEmailCustomizerModule.base', 'Administrator name'),
                    'login_url' => Yii::t('SystemEmailCustomizerModule.base', 'Login page URL'),
                    'login_link' => Yii::t('SystemEmailCustomizerModule.base', 'Login link (HTML)'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Your account request has been approved',
                    'header' => '<h2 style="margin:0;">Registration approved</h2>',
                    'body' => "Hello {display_name},\n\nYour account request for {app_name} has been approved by {admin_name}.\n\nYou can now sign in here: {login_link}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'admin.registration_decline' => self::definition(
                'admin.registration_decline',
                Yii::t('SystemEmailCustomizerModule.base', 'Registration declined'),
                'admin',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent to a user when their registration request is declined.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an administrator declines a pending user registration.'),
                '@humhub/views/mail/TextOnly',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'admin_name' => Yii::t('SystemEmailCustomizerModule.base', 'Administrator name'),
                    'message' => Yii::t('SystemEmailCustomizerModule.base', 'Decline message from admin'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Your account request has been reviewed',
                    'header' => '<h2 style="margin:0;">Registration update</h2>',
                    'body' => "Hello {display_name},\n\nYour account request for {app_name} has been reviewed by {admin_name}.\n\n{message}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'admin.registration_message' => self::definition(
                'admin.registration_message',
                Yii::t('SystemEmailCustomizerModule.base', 'Registration follow-up message'),
                'admin',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when an administrator messages a user about their registration request.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an admin sends a custom message from the user approval screen.'),
                '@humhub/views/mail/TextOnly',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'admin_name' => Yii::t('SystemEmailCustomizerModule.base', 'Administrator name'),
                    'message' => Yii::t('SystemEmailCustomizerModule.base', 'Custom message from admin'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Message about your account request',
                    'header' => '<h2 style="margin:0;">Account request update</h2>',
                    'body' => "Hello {display_name},\n\n{admin_name} has sent you a message about your account request:\n\n{message}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'activity.mail_summary' => self::definition(
                'activity.mail_summary',
                Yii::t('SystemEmailCustomizerModule.base', 'Activity digest'),
                'activity',
                Yii::t('SystemEmailCustomizerModule.base', 'Periodic summary of activity notifications.'),
                Yii::t('SystemEmailCustomizerModule.base', 'Based on each user\'s activity email frequency setting.'),
                '@humhub/modules/activity/views/mails/mailSummary',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'activities' => Yii::t('SystemEmailCustomizerModule.base', 'Rendered activity summary content'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Your activity summary for {app_name}',
                    'header' => '<h2 style="margin:0;">Activity summary</h2>',
                    'body' => "Hello {display_name},\n\nHere is your latest activity summary:\n\n{activities}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'notification.space.invite' => self::definition(
                'notification.space.invite',
                Yii::t('SystemEmailCustomizerModule.base', 'Space invite notification'),
                'notifications',
                Yii::t('SystemEmailCustomizerModule.base', 'Notification email when a user is invited to a space.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a space invitation notification is sent by email.'),
                '@notification/views/mails/wrapper',
                [
                    'headline' => Yii::t('SystemEmailCustomizerModule.base', 'Notification headline'),
                    'content' => Yii::t('SystemEmailCustomizerModule.base', 'Rendered notification body'),
                    'space_name' => Yii::t('SystemEmailCustomizerModule.base', 'Space name'),
                    'originator_name' => Yii::t('SystemEmailCustomizerModule.base', 'Sender name'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => '{headline}',
                    'header' => '<h2 style="margin:0;">{headline}</h2>',
                    'body' => "{content}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'notification.space.approvalrequest' => self::definition(
                'notification.space.approvalrequest',
                Yii::t('SystemEmailCustomizerModule.base', 'Space membership approval request'),
                'notifications',
                Yii::t('SystemEmailCustomizerModule.base', 'Notification when someone requests to join a space.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a membership request is submitted and admins are notified.'),
                '@notification/views/mails/wrapper',
                [
                    'headline' => Yii::t('SystemEmailCustomizerModule.base', 'Notification headline'),
                    'content' => Yii::t('SystemEmailCustomizerModule.base', 'Rendered notification body'),
                    'space_name' => Yii::t('SystemEmailCustomizerModule.base', 'Space name'),
                    'originator_name' => Yii::t('SystemEmailCustomizerModule.base', 'Applicant name'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => '{headline}',
                    'header' => '<h2 style="margin:0;">{headline}</h2>',
                    'body' => "{content}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'notification.space.approvalrequestaccepted' => self::definition(
                'notification.space.approvalrequestaccepted',
                Yii::t('SystemEmailCustomizerModule.base', 'Space membership approved'),
                'notifications',
                Yii::t('SystemEmailCustomizerModule.base', 'Notification when a space membership request is approved.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an admin approves a pending space membership.'),
                '@notification/views/mails/wrapper',
                [
                    'headline' => Yii::t('SystemEmailCustomizerModule.base', 'Notification headline'),
                    'content' => Yii::t('SystemEmailCustomizerModule.base', 'Rendered notification body'),
                    'space_name' => Yii::t('SystemEmailCustomizerModule.base', 'Space name'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => '{headline}',
                    'header' => '<h2 style="margin:0;">{headline}</h2>',
                    'body' => "{content}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'notification.space.approvalrequestdeclined' => self::definition(
                'notification.space.approvalrequestdeclined',
                Yii::t('SystemEmailCustomizerModule.base', 'Space membership declined'),
                'notifications',
                Yii::t('SystemEmailCustomizerModule.base', 'Notification when a space membership request is declined.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When an admin declines a pending space membership.'),
                '@notification/views/mails/wrapper',
                [
                    'headline' => Yii::t('SystemEmailCustomizerModule.base', 'Notification headline'),
                    'content' => Yii::t('SystemEmailCustomizerModule.base', 'Rendered notification body'),
                    'space_name' => Yii::t('SystemEmailCustomizerModule.base', 'Space name'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => '{headline}',
                    'header' => '<h2 style="margin:0;">{headline}</h2>',
                    'body' => "{content}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
            'notification.friendship.request' => self::definition(
                'notification.friendship.request',
                Yii::t('SystemEmailCustomizerModule.base', 'Friend request'),
                'notifications',
                Yii::t('SystemEmailCustomizerModule.base', 'Notification when someone sends a friend request.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a friendship request notification is emailed.'),
                '@notification/views/mails/wrapper',
                [
                    'headline' => Yii::t('SystemEmailCustomizerModule.base', 'Notification headline'),
                    'content' => Yii::t('SystemEmailCustomizerModule.base', 'Rendered notification body'),
                    'originator_name' => Yii::t('SystemEmailCustomizerModule.base', 'Sender name'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => '{headline}',
                    'header' => '<h2 style="margin:0;">{headline}</h2>',
                    'body' => "{content}",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            ),
        ];

        if (Yii::$app->hasModule('twofa')) {
            $definitions['user.twofa_verification_code'] = self::definition(
                'user.twofa_verification_code',
                Yii::t('SystemEmailCustomizerModule.base', 'Two-factor authentication code'),
                'user',
                Yii::t('SystemEmailCustomizerModule.base', 'Sent when a user logs in with email-based two-factor authentication enabled.'),
                Yii::t('SystemEmailCustomizerModule.base', 'When a user signs in and the Two-Factor Authentication module sends a verification code by email.'),
                '@twofa/views/mails/VerifyingCode',
                [
                    'display_name' => Yii::t('SystemEmailCustomizerModule.base', 'Recipient display name'),
                    'code' => Yii::t('SystemEmailCustomizerModule.base', 'Verification code'),
                    'date_time' => Yii::t('SystemEmailCustomizerModule.base', 'Date and time the code was sent'),
                    'app_name' => Yii::t('SystemEmailCustomizerModule.base', 'Application name'),
                ],
                [
                    'subject' => 'Your login verification code',
                    'header' => '<h2 style="margin:0;">Your login verification code</h2>',
                    'body' => "Hello {display_name},\n\nYour account is secured by two-factor authentication. Please use the following code to complete your login:\n\n## {code}\n\n**Date and time:** {date_time}\n\nIf you did not attempt to log in, please ignore this email or contact an administrator.",
                    'footer' => '<p style="margin:0;">{app_name}</p>',
                ]
            );
        }

        return $definitions;
    }

    /**
     * @param array<string, string> $variables
     * @param array<string, string> $defaults
     * @return array<string, mixed>
     */
    private static function definition(
        string $key,
        string $title,
        string $category,
        string $description,
        string $trigger,
        string $view,
        array $variables,
        array $defaults
    ): array {
        return [
            'key' => $key,
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'trigger' => $trigger,
            'view' => $view,
            'variables' => $variables,
            'defaults' => array_merge([
                'header_bg_color' => '#f0f4f8',
                'footer_bg_color' => '#f8f9fa',
                'header_font_color' => '#1f2937',
                'footer_font_color' => '#6b7280',
            ], $defaults),
        ];
    }
}
