<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\components;

use humhub\components\mail\Mailer;
use humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate;
use humhub\modules\user\models\User;
use Yii;
use yii\mail\MessageInterface;

class MailInterceptor extends Mailer
{
    /**
     * @var array<int, array{templateKey: string, params: array<string, mixed>, template: SystemEmailTemplate}>
     */
    private static array $pendingCustomizations = [];

    /**
     * @inheritdoc
     */
    public function compose($view = null, array $params = [])
    {
        $message = parent::compose($view, $params);
        $templateKey = EmailDefinitionRegistry::resolveKey($view, $params);

        if ($templateKey === null) {
            return $message;
        }

        $template = SystemEmailTemplate::findByKey($templateKey);
        if ($template === null || !$template->is_active) {
            return $message;
        }

        self::$pendingCustomizations[spl_object_id($message)] = [
            'templateKey' => $templateKey,
            'params' => $params,
            'template' => $template,
        ];

        return $message;
    }

    /**
     * Apply custom templates after recipients/subject are set on the message.
     *
     * @inheritdoc
     */
    public function beforeSend($message)
    {
        $messageId = spl_object_id($message);
        if (isset(self::$pendingCustomizations[$messageId])) {
            $pending = self::$pendingCustomizations[$messageId];
            unset(self::$pendingCustomizations[$messageId]);

            $recipient = $this->resolveRecipient($pending['params'], $message);
            $variables = VariableExtractor::extract(
                $pending['templateKey'],
                $pending['params'],
                $message,
                $recipient
            );
            $processed = (new TemplateProcessor())->process(
                $pending['template'],
                $variables,
                $recipient,
                false
            );

            $message->setHtmlBody($processed['body']);
            if ($processed['subject'] !== '') {
                $message->setSubject($processed['subject']);
            }
        }

        return parent::beforeSend($message);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveRecipient(array $params, MessageInterface $message): ?User
    {
        foreach (['user', 'recipient', 'originator'] as $key) {
            if (isset($params[$key]) && $params[$key] instanceof User) {
                return $params[$key];
            }
        }

        $to = $message->getTo();
        $email = is_array($to) ? array_key_first($to) : $to;
        if (!is_string($email) || $email === '') {
            return null;
        }

        return User::findOne(['email' => $email]);
    }
}
