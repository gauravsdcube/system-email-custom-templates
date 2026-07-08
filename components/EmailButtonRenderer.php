<?php
/**
 * @copyright Copyright (c) 2026 D Cube Consulting. All rights reserved.
 * @author D Cube Consulting <info@dcubeconsulting.co.uk>
 */



namespace humhub\modules\systemEmailCustomizer\components;

use humhub\helpers\MailStyleHelper;

/**
 * Converts {button:Label|URL} shortcodes into email-client-safe button markup.
 */
class EmailButtonRenderer
{
    /**
     * Matches button shortcodes. URL may be a variable placeholder or absolute URL.
     */
    public const SHORTCODE_PATTERN = '/\{button:([^|{}]+)\|([^}]+)\}/u';

    public static function process(string $content): string
    {
        $content = self::normalizeShortcodes($content);

        return preg_replace_callback(self::SHORTCODE_PATTERN, static function (array $matches): string {
            return self::renderShortcodeMatch($matches);
        }, $content) ?? $content;
    }

    /**
     * Replace button shortcodes with markdown-safe placeholders before rich-text conversion.
     *
     * @return array{0: string, 1: array<string, string>} Placeholder map: token => rendered button HTML
     */
    public static function extractToPlaceholders(string $content): array
    {
        $content = self::normalizeShortcodes($content);
        $buttons = [];
        $index = 0;

        $content = preg_replace_callback(self::SHORTCODE_PATTERN, static function (array $matches) use (&$buttons, &$index): string {
            $placeholder = 'SECbtn' . str_pad((string) ++$index, 8, '0', STR_PAD_LEFT);
            $buttons[$placeholder] = self::renderShortcodeMatch($matches);

            return $placeholder;
        }, $content) ?? $content;

        return [$content, $buttons];
    }

    /**
     * @param array<string, string> $buttons
     */
    public static function injectFromPlaceholders(string $content, array $buttons): string
    {
        foreach ($buttons as $placeholder => $html) {
            $quoted = preg_quote($placeholder, '/');
            $content = preg_replace('/<p>\s*' . $quoted . '\s*<\/p>/', $html, $content) ?? $content;
            $content = str_replace($placeholder, $html, $content);
        }

        return $content;
    }

    private static function renderShortcodeMatch(array $matches): string
    {
        $label = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($label === '' || $url === '') {
            return $matches[0];
        }

        return self::renderButton($label, $url);
    }

    /**
     * Unescape markdown/editor mangling inside button shortcodes.
     */
    public static function normalizeShortcodes(string $content): string
    {
        $content = preg_replace(
            '/(\{button:[^{}]+?)\\\\\|/',
            '$1|',
            $content
        ) ?? $content;

        return str_replace(['&#124;', '&verbar;', '%7C'], '|', $content);
    }

    public static function renderButton(string $label, string $url): string
    {
        if (!self::isAllowedUrl($url)) {
            return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        }

        $bgColor = htmlspecialchars(MailStyleHelper::getColorPrimary(), ENT_QUOTES, 'UTF-8');
        $textColor = htmlspecialchars(MailStyleHelper::getTextColorContrast(), ENT_QUOTES, 'UTF-8');
        $fontFamily = htmlspecialchars(MailStyleHelper::getFontFamily(), ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return '<table border="0" cellspacing="0" cellpadding="0" align="center" role="presentation" style="margin:20px auto;">'
            . '<tr><td align="center" style="border-radius:5px;background-color:' . $bgColor . ';">'
            . '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" '
            . 'style="display:inline-block;padding:12px 28px;font-family:' . $fontFamily . ';'
            . 'font-size:14px;font-weight:600;line-height:1.2;color:' . $textColor . ';'
            . 'text-decoration:none;border-radius:5px;background-color:' . $bgColor . ';">'
            . '<strong style="font-weight:600;color:' . $textColor . ';">' . $safeLabel . '</strong>'
            . '</a></td></tr></table>';
    }

    public static function buildShortcode(string $label, string $url): string
    {
        return '{button:' . $label . '|' . $url . '}';
    }

    private static function isAllowedUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\s<>"\']/', $url)) {
            return false;
        }

        if (preg_match('/^\{[a-z0-9_.]+\}$/i', $url)) {
            return true;
        }

        return (bool) preg_match('#^https?://#i', $url) || str_starts_with($url, '/');
    }
}
