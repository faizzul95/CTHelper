<?php

namespace CT\Helpers;

/**
 * Input Class
 *
 * @category  Form Input
 * @package   Input
 * @author    Mohd Fahmy Izwan Zulkhafri <faizzul14@gmail.com>
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version   1.0.0
 */
class Input
{
    /**
     * Generate HTML input field of type text.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function text($name, $value = '', $attributes = array())
    {
        return self::generateInput('text', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type radio.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param bool $checked Whether the radio button should be checked.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function radio($name, $value = '', $checked = false, $attributes = array())
    {
        if ($checked) {
            $attributes['checked'] = 'checked';
        }
        return self::generateInput('radio', $name, $value, $attributes);
    }

    /**
     * Generate HTML textarea.
     *
     * @param string $name Name attribute of the textarea.
     * @param string $value Value of the textarea.
     * @param array $attributes Additional attributes for the textarea.
     * @return string HTML representation of the textarea.
     */
    public static function textarea($name, $value = '', $attributes = array())
    {
        $attributes['name'] = $name;
        return '<textarea ' . self::formatAttributes($attributes) . '>' . htmlspecialchars($value) . '</textarea>';
    }

    /**
     * Generate HTML select dropdown.
     *
     * @param string $name Name attribute of the select dropdown.
     * @param array $options Associative array of options (value => label).
     * @param string $selected Value of the selected option.
     * @param array $attributes Additional attributes for the select dropdown.
     * @return string HTML representation of the select dropdown.
     */
    public static function select($name, $options = array(), $selected = '', $attributes = array())
    {
        $html = '<select name="' . $name . '"' . self::formatAttributes($attributes) . '>';
        foreach ($options as $value => $label) {
            $isSelected = ($value == $selected) ? 'selected="selected"' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '" ' . $isSelected . '>' . htmlspecialchars($label) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Generate HTML input field of type checkbox.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param bool $checked Whether the checkbox should be checked.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function checkbox($name, $value = '', $checked = false, $attributes = array())
    {
        if ($checked) {
            $attributes['checked'] = 'checked';
        }
        return self::generateInput('checkbox', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type hidden.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function hidden($name, $value = '', $attributes = array())
    {
        return self::generateInput('hidden', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type number.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function number($name, $value = '', $attributes = array())
    {
        return self::generateInput('number', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type password.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function password($name, $value = '', $attributes = array())
    {
        return self::generateInput('password', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type date.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function date($name, $value = '', $attributes = array())
    {
        return self::generateInput('date', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type time.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function time($name, $value = '', $attributes = array())
    {
        return self::generateInput('time', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type email.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function email($name, $value = '', $attributes = array())
    {
        return self::generateInput('email', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type URL.
     *
     * @param string $name Name attribute of the input field.
     * @param string $value Value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function url($name, $value = '', $attributes = array())
    {
        return self::generateInput('url', $name, $value, $attributes);
    }

    /**
     * Generate HTML input field of type file.
     *
     * @param string $name Name attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string HTML representation of the input field.
     */
    public static function file($name, $attributes = array())
    {
        return self::generateInput('file', $name, '', $attributes);
    }

    /**
     * Generate a secure HTML input field while allowing safe attributes.
     *
     * This function ensures:
     * - Attribute values are scanned for malicious patterns.
     * - Only harmful attributes are removed, leaving safe ones intact.
     * - JavaScript event attributes (e.g., onclick, onmouseover) are allowed unless they contain XSS.
     *
     * @param string $type The type of input field.
     * @param string $name The name attribute of the input field.
     * @param string $value The value attribute of the input field.
     * @param array $attributes Additional attributes for the input field.
     * @return string Secure HTML representation of the input field.
     */
    protected static function generateInput($type, $name, $value = '', $attributes = array())
    {
        // Escape essential attributes
        $attributes['type'] = htmlspecialchars($type, ENT_QUOTES | ENT_HTML5);
        $attributes['name'] = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5);
        $attributes['value'] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);

        // Define patterns to detect dangerous content inside attribute values
        $xssPatterns = [
            '/<script.*?>.*?<\/script>/is',  // Blocks <script> tags
            '/javascript\s*:/is',           // Blocks "javascript:" URLs
            '/document\.cookie/is',         // Prevents cookie theft
            '/document\.location/is',       // Prevents redirections
            '/eval\s*\(/is',                // Blocks eval()
            '/fetch\s*\(/is',               // Blocks fetch() abuse
            '/XMLHttpRequest/is',           // Blocks XHR-based attacks
            '/onerror\s*=\s*["\']?.*?\b(fetch|eval|alert|XMLHttpRequest|document)\b.*?["\']?/is', // Blocks dangerous event handlers
            '/alert\s*\(["\']?[^"\']{30,}["\']?\)/is' // Blocks alert() if it's suspiciously long
        ];

        // Filter attributes
        $safeAttributes = [];
        foreach ($attributes as $attrName => $attrValue) {
            // Ensure attribute names contain only safe characters
            if (!preg_match('/^[a-zA-Z0-9-]+$/', $attrName)) {
                continue;
            }

            // Check if the attribute value contains actual malicious content
            foreach ($xssPatterns as $pattern) {
                if (preg_match($pattern, $attrValue)) {
                    continue 2; // Skip this attribute
                }
            }

            // Escape and add safe attribute
            $safeAttributes[$attrName] = $attrName != 'value' ? htmlspecialchars($attrValue, ENT_QUOTES | ENT_HTML5) : $attrValue;
        }

        return '<input ' . self::formatAttributes($safeAttributes) . '>';
    }

    /**
     * Format attributes for HTML element.
     *
     * @param array $attributes Associative array of attributes.
     * @return string Formatted attributes for HTML element.
     */
    protected static function formatAttributes($attributes)
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '"';
        }
        return $html;
    }
}
