<?php

namespace CT\Helpers;

/**
 * Html Class
 *
 * Provides helper functions to generate secure HTML elements.
 * 
 * @category  HTML
 * @package   Html
 * @author    Mohd Fahmy Izwan Zulkhafri <faizzul14@gmail.com>
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version   1.0.0
 */
class HTML
{
    /**
     * Generate an unordered list (ul) HTML element.
     *
     * @param array $items List items
     * @return string Generated HTML
     */
    public static function ul(array $items)
    {
        return '<ul>' . self::generateListItems($items) . '</ul>';
    }

    /**
     * Generate an ordered list (ol) HTML element.
     *
     * @param array $items List items
     * @return string Generated HTML
     */
    public static function ol(array $items)
    {
        return '<ol>' . self::generateListItems($items) . '</ol>';
    }

    /**
     * Generate a paragraph (p) HTML element.
     *
     * @param string $content Text content
     * @param array $attributes Additional attributes for the paragraph
     * @return string Generated HTML
     */
    public static function p($content, $attributes = [])
    {
        return '<p' . self::formatAttributes($attributes) . '>' . self::secureValue($content) . '</p>';
    }

    /**
     * Generate a span (span) HTML element.
     *
     * @param string $content Text content
     * @param array $attributes Additional attributes for the span
     * @return string Generated HTML
     */
    public static function span($content, $attributes = [])
    {
        return '<span' . self::formatAttributes($attributes) . '>' . self::secureValue($content) . '</span>';
    }

    /**
     * Generate a button (button) HTML element.
     *
     * @param string $text Button text
     * @param array $attributes Additional attributes for the button
     * @return string Generated HTML
     */
    public static function button($text, $attributes = [])
    {
        return '<button' . self::formatAttributes($attributes) . '>' . self::secureValue($text) . '</button>';
    }

    /**
     * Generate a div HTML element.
     *
     * @param string $content Content inside the div
     * @param array $attributes Additional attributes for the div
     * @return string Generated HTML
     */
    public static function div($content, $attributes = [])
    {
        return '<div' . self::formatAttributes($attributes) . '>' . self::secureValue($content) . '</div>';
    }

    /**
     * Generate an image (img) HTML element.
     *
     * @param string $src Image source
     * @param string $alt Alternative text for the image
     * @param array $attributes Additional attributes for the image
     * @return string Generated HTML
     */
    public static function image($src, $alt = '', $attributes = [])
    {
        $attributes['src'] = $src;
        $attributes['alt'] = $alt;
        return '<img' . self::formatAttributes($attributes) . '>';
    }

    /**
     * Generate a link (a) HTML element.
     *
     * @param string $href URL of the link
     * @param string $text Text to display for the link
     * @param array $attributes Additional attributes for the link
     * @return string Generated HTML
     */
    public static function href($href, $text, $attributes = [])
    {
        $attributes['href'] = $href;
        return '<a' . self::formatAttributes($attributes) . '>' . self::secureValue($text) . '</a>';
    }

    /**
     * Generate a <link> HTML element for CSS files.
     *
     * @param string $href URL of the CSS file
     * @param array $attributes Additional attributes for the link
     * @return string Generated HTML
     */
    public static function css($href, $attributes = [])
    {
        $attributes['href'] = $href;
        $attributes['rel'] = 'stylesheet';
        return '<link' . self::formatAttributes($attributes) . '>';
    }

    /**
     * Generate an HTML table.
     *
     * @param array $data Two-dimensional array representing the table data
     * @param array $attributes Additional attributes for the table
     * @return string Generated HTML
     */
    public static function table(array $data, $attributes = [])
    {
        $html = '<table' . self::formatAttributes($attributes) . '>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . self::secureValue($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Generate list items for ul or ol.
     *
     * @param array $items List items
     * @return string Generated HTML for list items
     */
    private static function generateListItems(array $items)
    {
        $html = '';
        foreach ($items as $item) {
            $html .= '<li>' . self::secureValue($item) . '</li>';
        }
        return $html;
    }

    /**
     * Securely encode a string to prevent XSS attacks.
     *
     * - Accepts strings, numbers (int, float), and booleans.
     * - Rejects arrays, objects, closures, and JSON strings.
     * - Converts special characters into HTML entities.
     * - Returns an empty string for invalid inputs.
     *
     * @param mixed $value The input to be sanitized.
     * @param int $flags Optional flags for htmlspecialchars(). Default: ENT_QUOTES | ENT_SUBSTITUTE.
     * @return string The sanitized string, or an empty string if invalid input.
     */
    private static function secureValue($value, $flags = ENT_QUOTES | ENT_SUBSTITUTE)
    {
        // Reject arrays, objects, and closures
        if (is_array($value) || is_object($value) || $value instanceof \Closure) {
            return '';
        }

        // Reject JSON strings (prevents encoded objects)
        if (self::isJson($value)) {
            return '';
        }

        // Sanitize and return the string
        return htmlspecialchars($value, $flags);
    }

    /**
     * Detect if a string is valid JSON.
     *
     * @param string $string The input to check.
     * @return bool True if the input is valid JSON, otherwise false.
     */
    private static function isJson($string)
    {
        if (!is_string($string) || strlen($string) < 2) {
            return false;
        }

        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Secure attributes by removing potentially dangerous attributes.
     *
     * @param array $attributes Associative array of attributes.
     * @return array Filtered attributes.
     */
    private static function secureAttributes(array $attributes)
    {
        $dangerousPatterns = [
            '/^on\w+/i',              // Event handlers (onclick, onmouseover, etc.)
            '/^srcdoc$/i',            // Prevents malicious iframe injection
            '/^formaction$/i',        // Prevents form-based XSS attacks
            '/^style$/i',             // Prevents inline JavaScript execution
            '/javascript:/i',         // Blocks JavaScript execution
            '/data:/i',               // Blocks data URIs (potential XSS payloads)
            '/vbscript:/i',           // Blocks VBScript execution (IE-specific XSS)
            '/expression\(/i',        // Prevents CSS expression() execution
            '/url\(/i'                // Blocks CSS url() injection
        ];

        $safeAttributes = [];
        foreach ($attributes as $key => $value) {
            $lowerKey = strtolower($key);

            // Allow "data-*" attributes dynamically
            if (strpos($lowerKey, 'data-') === 0) {
                $safeAttributes[$key] = self::secureValue($value);
                continue;
            }

            // Remove potentially dangerous event handlers like "onload", "onclick", etc.
            if (strpos($lowerKey, 'on') === 0) {
                continue;
            }

            // Remove dangerous attributes based on patterns
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $key) || preg_match($pattern, $value)) {
                    continue 2; // Skip this attribute
                }
            }

            // Secure the value and add it to the safe attributes list
            $safeAttributes[$key] = self::secureValue($value);
        }

        return $safeAttributes;
    }

    /**
     * Format attributes for HTML elements securely.
     *
     * @param array $attributes Associative array of attributes.
     * @return string Formatted attributes for HTML element.
     */
    private static function formatAttributes(array $attributes)
    {
        $attributes = self::secureAttributes($attributes);
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . self::secureValue($key, ENT_QUOTES)  . '="' . self::secureValue($value) . '"';
        }
        return $html;
    }
}
