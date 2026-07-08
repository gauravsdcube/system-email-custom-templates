<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\components;

use humhub\modules\systemEmailCustomizer\models\SystemEmailTemplate;
use humhub\modules\user\models\User;
use Yii;


class TemplateProcessor
{
    /**
     * @param array<string, string> $variables
     * @return array{subject: string, body: string}
     */
    public function process(SystemEmailTemplate $template, array $variables = [], ?User $recipient = null, bool $isPreview = false): array
    {
        $subject = $this->replaceVariables($template->subject ?: '', $variables);
        $header = $this->replaceVariables($template->header ?: '', $variables);
        $body = $this->replaceVariables($template->body ?: '', $variables);
        $footer = $this->replaceVariables($template->footer ?: '', $variables);

        [$body, $bodyButtons] = EmailButtonRenderer::extractToPlaceholders($body);
        $body = $this->convertRichTextToHtml($body, $recipient, $isPreview);
        $body = EmailButtonRenderer::injectFromPlaceholders($body, $bodyButtons);

        [$header, $headerButtons] = EmailButtonRenderer::extractToPlaceholders($header);
        $header = EmailButtonRenderer::injectFromPlaceholders(
            $this->processHeaderFooter($header, $recipient, $isPreview),
            $headerButtons
        );

        [$footer, $footerButtons] = EmailButtonRenderer::extractToPlaceholders($footer);
        $footer = EmailButtonRenderer::injectFromPlaceholders(
            $this->processHeaderFooter($footer, $recipient, $isPreview),
            $footerButtons
        );

        return [
            'subject' => $subject,
            'body' => $this->buildCompleteEmail($template, $header, $body, $footer),
        ];
    }

    /**
     * @param array<string, string> $variables
     */
    private function replaceVariables(string $content, array $variables): string
    {
        $content = EmailButtonRenderer::normalizeShortcodes($content);

        for ($pass = 0; $pass < 3; $pass++) {
            $previous = $content;
            foreach ($variables as $key => $value) {
                if ($value !== '') {
                    $content = str_replace('{' . $key . '}', $value, $content);
                }
            }
            if ($content === $previous) {
                break;
            }
        }

        return $content;
    }

    private function processHeaderFooter(string $content, ?User $recipient, bool $isPreview): string
    {
        if ($content === '') {
            return '';
        }

        if ($this->looksLikeRichText($content)) {
            return $this->convertRichTextToHtml($content, $recipient, $isPreview);
        }

        if (strpos($content, '<') !== false) {
            return $this->processPlainUrls($content);
        }

        return $this->processPlainUrls(nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')));
    }

    private function looksLikeRichText(string $content): bool
    {
        return (bool)preg_match('/^#+\s+/m', $content)
            || (bool)preg_match('/\*\*.*\*\*/', $content)
            || (bool)preg_match('/!\[.*\]\(file-guid:/', $content)
            || (bool)preg_match('/\[.*\]\(.*\)/', $content);
    }

    private function convertRichTextToHtml(string $content, ?User $recipient, bool $isPreview): string
    {
        $result = \humhub\modules\content\widgets\richtext\converter\RichTextToEmailHtmlConverter::process($content, [
            'minimal' => false,
            'exclude' => ['mention', 'oembed'],
            \humhub\modules\content\widgets\richtext\converter\RichTextToEmailHtmlConverter::OPTION_RECEIVER_USER => $recipient,
        ]);

        return $this->processPlainUrls($this->removeConflictingColorStyles($result));
    }

    private function buildCompleteEmail(SystemEmailTemplate $template, string $header, string $body, string $footer): string
    {
        $headerBgColor = $template->header_bg_color ?: '#f0f4f8';
        $footerBgColor = $template->footer_bg_color ?: '#f8f9fa';
        $headerFontColor = $template->header_font_color ?: '#1f2937';
        $footerFontColor = $template->footer_font_color ?: '#6b7280';

        if ($header !== '') {
            $header = $this->applyFontColor($header, $headerFontColor);
        }
        if ($footer !== '') {
            $footer = $this->applyFontColor($footer, $footerFontColor);
        }

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>';
        $html .= '<body style="margin:0;padding:0;background:#eef2f6;font-family:Arial,sans-serif;line-height:1.6;color:#1f2937;">';
        $html .= '<div style="max-width:640px;margin:24px auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">';

        if ($header !== '') {
            $html .= '<div style="background-color:' . htmlspecialchars($headerBgColor, ENT_QUOTES, 'UTF-8') . ';padding:24px;text-align:center;">';
            $html .= $header;
            $html .= '</div>';
        }

        $html .= '<div style="padding:24px;">' . $body . '</div>';

        if ($footer !== '') {
            $html .= '<div style="background-color:' . htmlspecialchars($footerBgColor, ENT_QUOTES, 'UTF-8') . ';padding:20px;text-align:center;font-size:12px;">';
            $html .= $footer;
            $html .= '</div>';
        }

        $html .= '</div></body></html>';

        return $html;
    }

    private function applyFontColor(string $content, string $fontColor): string
    {
        if (strpos($content, '<') === false) {
            return '<div style="color:' . htmlspecialchars($fontColor, ENT_QUOTES, 'UTF-8') . ';">' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        return preg_replace_callback('/<([^>]+)>/', static function ($matches) use ($fontColor) {
            $tag = $matches[1];
            if (str_starts_with($tag, '/')) {
                return $matches[0];
            }
            if (preg_match('/style\s*=\s*["\'][^"\']*color\s*:/i', $tag)) {
                return $matches[0];
            }
            if (preg_match('/style\s*=\s*["\']([^"\']*)["\']/i', $tag, $styleMatches)) {
                $styles = rtrim($styleMatches[1], ';') . '; color:' . $fontColor;
                $tag = preg_replace('/style\s*=\s*["\'][^"\']*["\']/i', 'style="' . $styles . '"', $tag);
            } else {
                $tag .= ' style="color:' . $fontColor . '"';
            }

            return '<' . $tag . '>';
        }, $content) ?? $content;
    }

    private function processPlainUrls(string $html): string
    {
        $existingLinks = [];
        $html = preg_replace_callback('/<a[^>]*>.*?<\/a>/is', static function ($matches) use (&$existingLinks) {
            $placeholder = '___EXISTING_LINK_' . count($existingLinks) . '___';
            $existingLinks[] = $matches[0];
            return $placeholder;
        }, $html) ?? $html;

        $html = preg_replace_callback('/\b(https?:\/\/[^\s<>"\'{}|\\^`\[\]]+)/i', static function ($matches) {
            $url = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color:#2563eb;text-decoration:underline;">' . $url . '</a>';
        }, $html) ?? $html;

        foreach ($existingLinks as $index => $link) {
            $html = str_replace('___EXISTING_LINK_' . $index . '___', $link, $html);
        }

        return $html;
    }

    private function removeConflictingColorStyles(string $html): string
    {
        $html = preg_replace_callback('/style\s*=\s*["\']([^"\']*)["\']/i', static function ($matches) {
            $styles = preg_replace('/(?:^|;)\s*color\s*:[^;]*/i', '', $matches[1]);
            $styles = preg_replace('/(?:^|;)\s*background-color\s*:[^;]*/i', '', $styles);
            $styles = trim($styles, " ;");

            return $styles === '' ? '' : 'style="' . $styles . '"';
        }, $html) ?? $html;

        return preg_replace('/\sstyle="\s*"/', '', $html) ?? $html;
    }
}
