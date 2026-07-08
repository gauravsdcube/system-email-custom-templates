<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\components;

use humhub\modules\admin\models\forms\ApproveUserForm;
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
    public static function extract(string $templateKey, array $params = [], ?MessageInterface $message = null): array
    {
        $variables = [
            'app_name' => (string)Yii::$app->name,
        ];

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
                $user = $params['user'] ?? null;
                if ($user instanceof User) {
                    $variables['display_name'] = $user->displayName;
                }
                $variables['code'] = (string)($params['code'] ?? '');
                $variables['date_time'] = Yii::$app->formatter->asDatetime(time());
                break;

            case 'user.password_recovery':
                $user = $params['user'] ?? null;
                if ($user instanceof User) {
                    $variables['display_name'] = $user->displayName;
                }
                $variables['password_reset_url'] = (string)($params['linkPasswordReset'] ?? '');
                break;

            case 'user.change_email':
            case 'user.change_username':
                $user = $params['user'] ?? null;
                if ($user instanceof User) {
                    $variables['display_name'] = $user->displayName;
                }
                $variables['confirm_url'] = (string)($params['link'] ?? $params['confirmUrl'] ?? '');
                break;

            case 'admin.registration_approval':
            case 'admin.registration_decline':
            case 'admin.registration_message':
                $variables = array_merge($variables, self::extractAdminRegistrationVariables($params));
                break;

            case 'activity.mail_summary':
                $user = $params['user'] ?? null;
                if ($user instanceof User) {
                    $variables['display_name'] = $user->displayName;
                }
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
    private static function extractAdminRegistrationVariables(array $params): array
    {
        $variables = [
            'display_name' => '',
            'admin_name' => '',
            'message' => (string)($params['message'] ?? ''),
            'login_url' => '',
            'login_link' => '',
        ];

        $form = self::findApproveUserFormFromTrace();
        if ($form instanceof ApproveUserForm) {
            $variables['display_name'] = Html::encode($form->user->displayName);
            $variables['admin_name'] = Html::encode($form->admin->displayName);
            $variables['message'] = strip_tags((string)$form->message);
            $loginUrl = Url::to(['/user/auth/login'], true);
            $variables['login_url'] = urldecode($loginUrl);
            $variables['login_link'] = Html::a($variables['login_url'], $loginUrl);
        }

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

    private static function findApproveUserFormFromTrace(): ?ApproveUserForm
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
        foreach ($trace as $frame) {
            if (($frame['class'] ?? '') === ApproveUserForm::class && isset($frame['object']) && $frame['object'] instanceof ApproveUserForm) {
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
