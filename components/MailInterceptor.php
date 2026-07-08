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

        $recipient = $this->resolveRecipient($params, $message);
        $variables = VariableExtractor::extract($templateKey, $params, $message);
        $processed = (new TemplateProcessor())->process($template, $variables, $recipient, false);

        $message->setHtmlBody($processed['body']);
        if ($processed['subject'] !== '') {
            $message->setSubject($processed['subject']);
        }

        return $message;
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
