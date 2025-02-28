<?php

if (file_exists('../vendor/autoload.php'))
    include_once '../vendor/autoload.php';

use CT\Helpers\Input;

// Set global variable
global $input;
$input = new Input();

// Helper function to run the specified test functions
function runTest($scriptNames)
{
    $results = [];

    if (!is_array($scriptNames)) {
        return ['error' => 'Script names must be provided as an array'];
    }

    foreach ($scriptNames as $function) {
        if (function_exists($function)) {
            try {
                echo "<h2>Running {$function}()</h2>";
                echo "<div style='margin-left: 20px;'>";
                $start = microtime(true);
                call_user_func($function);
                $end = microtime(true);
                $results[$function] = [
                    'status' => 'Pass',
                    'time' => round(($end - $start) * 1000, 2) . 'ms'
                ];
                echo "</div>";
                echo "<hr>";
            } catch (Exception $e) {
                $results[$function] = [
                    'status' => 'Fail',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            $results[$function] = [
                'status' => 'Fail',
                'error' => 'Function does not exist'
            ];
        }
    }

    return $results;
}

/**
 * Helper function to verify output contains expected strings
 */
function assertContains($needle, $haystack, $message = "Output doesn't contain expected string")
{
    if (strpos($haystack, $needle) === false) {
        echo "<div style='color: red;'>FAILED: {$message}</div>";
        echo "<div>Expected to find: " . htmlspecialchars($needle) . "</div>";
        echo "<div>Actual output: " . htmlspecialchars($haystack) . "</div>";
        throw new Exception($message);
    }
    echo "<div style='color: green;'>PASSED: Found expected string</div>";
}

/**
 * Helper function to check if output is properly escaped
 */
function assertEscaped($dangerous, $output, $message = "XSS vulnerability detected")
{
    if (strpos($output, $dangerous) !== false) {
        echo "<div style='color: red;'>FAILED: {$message}</div>";
        echo "<div>Unescaped content found: " . htmlspecialchars($dangerous) . "</div>";
        throw new Exception($message);
    }
    echo "<div style='color: green;'>PASSED: Content properly escaped</div>";
}

/**
 * Test Input::text method
 */
function testText()
{
    echo "Testing Input::text() method<br>";

    // Basic test
    $output = Input::text('username');
    echo "Basic text input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="text"', $output);
    assertContains('name="username"', $output);

    // With value
    $output = Input::text('username', 'john_doe');
    echo "Text input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="john_doe"', $output);

    // With attributes
    $output = Input::text('username', 'john_doe', ['class' => 'form-control', 'id' => 'user']);
    echo "Text input with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('class="form-control"', $output);
    assertContains('id="user"', $output);

    // XSS test
    $dangerousValue = '"><script>alert("XSS")</script>';
    $output = Input::text('username', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
    assertContains('value="&quot;&gt;&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;"', $output);
}

/**
 * Test Input::radio method
 */
function testRadio()
{
    echo "Testing Input::radio() method<br>";

    // Basic test
    $output = Input::radio('gender', 'male');
    echo "Basic radio input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="radio"', $output);
    assertContains('name="gender"', $output);
    assertContains('value="male"', $output);

    // Checked test
    $output = Input::radio('gender', 'female', true);
    echo "Checked radio input: " . htmlspecialchars($output) . "<br>";
    assertContains('checked="checked"', $output);

    // With attributes
    $output = Input::radio('gender', 'other', false, ['class' => 'option', 'id' => 'gender-other']);
    echo "Radio with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('class="option"', $output);
    assertContains('id="gender-other"', $output);

    // XSS test
    $dangerousValue = 'male"><script>alert("XSS")</script>';
    $output = Input::radio('gender', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::textarea method
 */
function testTextarea()
{
    echo "Testing Input::textarea() method<br>";

    // Basic test
    $output = Input::textarea('comments');
    echo "Basic textarea: " . htmlspecialchars($output) . "<br>";
    assertContains('<textarea', $output);
    assertContains('name="comments"', $output);
    assertContains('</textarea>', $output);

    // With value
    $output = Input::textarea('comments', 'This is a comment');
    echo "Textarea with value: " . htmlspecialchars($output) . "<br>";
    assertContains('>This is a comment</textarea>', $output);

    // With attributes
    $output = Input::textarea('comments', 'This is a comment', ['rows' => '5', 'cols' => '40']);
    echo "Textarea with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('rows="5"', $output);
    assertContains('cols="40"', $output);

    // XSS test
    $dangerousValue = '<script>alert("XSS")</script>';
    $output = Input::textarea('comments', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
    assertContains('>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</textarea>', $output);
}

/**
 * Test Input::select method
 */
function testSelect()
{
    echo "Testing Input::select() method<br>";

    $options = [
        '' => 'Select a color',
        'red' => 'Red',
        'green' => 'Green',
        'blue' => 'Blue'
    ];

    // Basic test
    $output = Input::select('color', $options);
    echo "Basic select: " . htmlspecialchars($output) . "<br>";
    assertContains('<select name="color"', $output);
    assertContains('</select>', $output);
    assertContains('<option value=""', $output);
    assertContains('>Select a color</option>', $output);

    // With selected option
    $output = Input::select('color', $options, 'green');
    echo "Select with selected option: " . htmlspecialchars($output) . "<br>";
    assertContains('value="green" selected="selected"', $output);

    // With attributes
    $output = Input::select('color', $options, 'blue', ['class' => 'form-select', 'id' => 'color-picker']);
    echo "Select with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('class="form-select"', $output);
    assertContains('id="color-picker"', $output);

    // XSS test in options
    $dangerousOptions = [
        'safe' => 'Safe Option',
        'dangerous' => '"><script>alert("XSS")</script>',
        'danger-val' => 'Labeled Danger'
    ];

    $output = Input::select('vulnerable', $dangerousOptions);
    echo "XSS test in options: " . htmlspecialchars($output) . "<br>";
    assertEscaped('"><script>alert("XSS")</script>', $output);

    // XSS test in selected value
    $dangerousSelected = '"><script>alert("XSS")</script>';
    $output = Input::select('vulnerable', $options, $dangerousSelected);
    echo "XSS test in selected value: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousSelected, $output);
}

/**
 * Test Input::checkbox method
 */
function testCheckbox()
{
    echo "Testing Input::checkbox() method<br>";

    // Basic test
    $output = Input::checkbox('agree');
    echo "Basic checkbox: " . htmlspecialchars($output) . "<br>";
    assertContains('type="checkbox"', $output);
    assertContains('name="agree"', $output);

    // Checked test
    $output = Input::checkbox('remember', 'yes', true);
    echo "Checked checkbox: " . htmlspecialchars($output) . "<br>";
    assertContains('value="yes"', $output);
    assertContains('checked="checked"', $output);

    // With attributes
    $output = Input::checkbox('subscribe', '1', false, ['class' => 'form-check', 'id' => 'newsletter']);
    echo "Checkbox with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('class="form-check"', $output);
    assertContains('id="newsletter"', $output);

    // XSS test
    $dangerousValue = '"><script>alert("XSS")</script>';
    $output = Input::checkbox('vulnerable', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::hidden method
 */
function testHidden()
{
    echo "Testing Input::hidden() method<br>";

    // Basic test
    $output = Input::hidden('csrf_token');
    echo "Basic hidden input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="hidden"', $output);
    assertContains('name="csrf_token"', $output);

    // With value
    $output = Input::hidden('csrf_token', 'abc123xyz');
    echo "Hidden input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="abc123xyz"', $output);

    // XSS test
    $dangerousValue = '"><script>alert("XSS")</script>';
    $output = Input::hidden('csrf_token', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::number method
 */
function testNumber()
{
    echo "Testing Input::number() method<br>";

    // Basic test
    $output = Input::number('quantity');
    echo "Basic number input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="number"', $output);
    assertContains('name="quantity"', $output);

    // With value
    $output = Input::number('quantity', '5');
    echo "Number input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="5"', $output);

    // With attributes
    $output = Input::number('quantity', '5', ['min' => '0', 'max' => '10', 'step' => '1']);
    echo "Number input with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('min="0"', $output);
    assertContains('max="10"', $output);
    assertContains('step="1"', $output);

    // XSS test
    $dangerousValue = '5"><script>alert("XSS")</script>';
    $output = Input::number('quantity', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::password method
 */
function testPassword()
{
    echo "Testing Input::password() method<br>";

    // Basic test
    $output = Input::password('password');
    echo "Basic password input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="password"', $output);
    assertContains('name="password"', $output);

    // With attributes
    $output = Input::password('password', '', ['class' => 'form-control', 'autocomplete' => 'off']);
    echo "Password input with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('class="form-control"', $output);
    assertContains('autocomplete="off"', $output);

    // XSS test
    $dangerousValue = '"><script>alert("XSS")</script>';
    $output = Input::password('password', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::email method
 */
function testEmail()
{
    echo "Testing Input::email() method<br>";

    // Basic test
    $output = Input::email('email');
    echo "Basic email input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="email"', $output);
    assertContains('name="email"', $output);

    // With value
    $output = Input::email('email', 'test@example.com');
    echo "Email input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="test@example.com"', $output);

    // XSS test
    $dangerousValue = 'test@example.com"><script>alert("XSS")</script>';
    $output = Input::email('email', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::url method
 */
function testUrl()
{
    echo "Testing Input::url() method<br>";

    // Basic test
    $output = Input::url('website');
    echo "Basic URL input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="url"', $output);
    assertContains('name="website"', $output);

    // With value
    $output = Input::url('website', 'https://example.com');
    echo "URL input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="https://example.com"', $output);

    // XSS test
    $dangerousValue = 'https://example.com"><script>alert("XSS")</script>';
    $output = Input::url('website', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::file method
 */
function testFile()
{
    echo "Testing Input::file() method<br>";

    // Basic test
    $output = Input::file('upload');
    echo "Basic file input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="file"', $output);
    assertContains('name="upload"', $output);

    // With attributes
    $output = Input::file('upload', ['accept' => 'image/*', 'multiple' => 'multiple']);
    echo "File input with attributes: " . htmlspecialchars($output) . "<br>";
    assertContains('accept="image/*"', $output);
    assertContains('multiple="multiple"', $output);

    // XSS test in attributes
    $dangerousAttributes = ['onchange' => 'javascript:alert("XSS")'];
    $output = Input::file('upload', $dangerousAttributes);
    echo "XSS test in attributes: " . htmlspecialchars($output) . "<br>";
    // The class should sanitize these, but we'll test if the output contains it anyway
    assertEscaped('javascript:alert', $output);
}

/**
 * Test Input::date method
 */
function testDate()
{
    echo "Testing Input::date() method<br>";

    // Basic test
    $output = Input::date('birthday');
    echo "Basic date input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="date"', $output);
    assertContains('name="birthday"', $output);

    // With value
    $output = Input::date('birthday', '2000-01-01');
    echo "Date input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="2000-01-01"', $output);

    // XSS test
    $dangerousValue = '2000-01-01"><script>alert("XSS")</script>';
    $output = Input::date('birthday', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test Input::time method
 */
function testTime()
{
    echo "Testing Input::time() method<br>";

    // Basic test
    $output = Input::time('meeting');
    echo "Basic time input: " . htmlspecialchars($output) . "<br>";
    assertContains('type="time"', $output);
    assertContains('name="meeting"', $output);

    // With value
    $output = Input::time('meeting', '14:30');
    echo "Time input with value: " . htmlspecialchars($output) . "<br>";
    assertContains('value="14:30"', $output);

    // XSS test
    $dangerousValue = '14:30"><script>alert("XSS")</script>';
    $output = Input::time('meeting', $dangerousValue);
    echo "XSS test output: " . htmlspecialchars($output) . "<br>";
    assertEscaped($dangerousValue, $output);
}

/**
 * Test complex security scenarios to prevent XSS and attribute injection.
 */
function testAdvancedSecurity()
{
    echo "<h2>Testing Advanced Security Scenarios</h2>";

    // 1️⃣ Test attribute name XSS (Ensure only bad values are removed, not safe event handlers)
    $dangerousAttributes = [
        'data-value' => 'safe',
        'onmouseover="alert(\'XSS\')"' => 'should-be-escaped', // 🚨 Dangerous, should be removed
        'onmouseover' => 'console.log("hovered")', // ✅ Safe, should stay
        'onclick' => 'alert("XSS")', // ✅ Safe, should stay
        'onfocus' => 'fetch("http://evil.com")', // 🚨 Dangerous, should be removed
        'onerror' => 'document.cookie', // 🚨 Dangerous, should be removed
    ];

    $output = Input::text('username', 'john', $dangerousAttributes);
    echo "<strong>Attribute Name XSS Test</strong><br>";

    assertEscaped('onmouseover="alert', $output, 'Unsafe onmouseover should be removed');
    assertContains('onmouseover="console.log(&quot;hovered&quot;)"', $output, 'Safe onmouseover should remain');
    assertContains('onclick="alert(&quot;XSS&quot;)"', $output, 'Safe onclick should remain');
    assertEscaped('fetch("http://evil.com")', $output, 'Malicious fetch() should be removed');
    assertEscaped('document.cookie', $output, 'Malicious document.cookie should be removed');

    // 2️⃣ Test HTML injection in attribute values
    $htmlInjection = [
        'class' => '<script>alert("XSS")</script>', // 🚨 Dangerous, should be removed
        'id' => '<img src="x" onerror="alert(\'XSS\')">' // 🚨 Dangerous, should be removed
    ];
    $output = Input::text('username', 'john', $htmlInjection);
    echo "<strong>HTML Injection Test</strong><br>";

    assertEscaped('<script>', $output, 'Script tags should be escaped');
    assertEscaped('<img src', $output, 'Injected image tag should be escaped');

    // 3️⃣ Test Unicode XSS evasion (Should escape Unicode-encoded `<script>`)
    $unicodeXss = 'john\u003cscript\u003ealert(1)\u003c/script\u003e';
    $output = Input::text('username', $unicodeXss);
    echo "<strong>Unicode XSS Evasion Test</strong><br>";

    assertEscaped('<script>', $output, 'Unicode-encoded script tags should be escaped');

    // 4️⃣ Test NULL byte injection (Should ignore anything after NULL byte)
    $nullByteXss = "john\0<script>alert(1)</script>";
    $output = Input::text('username', $nullByteXss);
    echo "<strong>NULL Byte Injection Test</strong><br>";

    assertEscaped('<script>', $output, 'Script tags after NULL byte should be escaped');

    // 5️⃣ Test JS prototype pollution (Should allow non-malicious values)
    $prototypeAttack = '__proto__[alert]=1';
    $output = Input::text('username', $prototypeAttack);
    echo "<strong>JS Prototype Pollution Test</strong><br>";

    assertContains('value="__proto__[alert]=1"', $output, 'Valid prototype string should be allowed');

    // 6️⃣ Test self-closing tags (Prevent injected `<img>` from executing JavaScript)
    $selfClosingXss = 'john<img src="x" onerror="alert(\'XSS\')">';
    $output = Input::text('username', $selfClosingXss);
    echo "<strong>Self-Closing Tag Test</strong><br>";

    assertEscaped('<img src=', $output, 'Injected image tags should be escaped');

    // 7️⃣ Test SVG-based XSS (Should escape `<svg>` if it has event handlers)
    $svgXss = 'john<svg onload="alert(\'XSS\')">';
    $output = Input::text('username', $svgXss);
    echo "<strong>SVG-Based XSS Test</strong><br>";

    assertEscaped('<svg onload=', $output, 'Injected SVG tags should be escaped');

    // 8️⃣ Test `javascript:` URI injection (Should block dangerous URLs)
    $javascriptUrl = ['href' => 'javascript:alert(1)']; // 🚨 Dangerous, should be removed
    $output = Input::text('username', 'john', $javascriptUrl);
    echo "<strong>JavaScript URI Injection Test</strong><br>";

    assertEscaped('javascript:', $output, 'Injected JavaScript URLs should be escaped');

    // 9️⃣ Test iframe injection (Should escape `<iframe>` attempts)
    $iframeXss = 'john<iframe src="http://evil.com"></iframe>';
    $output = Input::text('username', $iframeXss);
    echo "<strong>Iframe Injection Test</strong><br>";

    assertEscaped('<iframe', $output, 'Injected iframe tags should be escaped');

    echo "<h3 style='color: green;'>✅ All tests completed successfully!</h3>";
}

/**
 * Test multiple inputs together 
 */
function testInputsWithForm()
{
    echo "Testing multiple inputs in a form context<br>";

    echo "<form action=\"\" method=\"post\">";

    echo Input::text('username', 'john_doe', ['class' => 'form-control', 'required' => 'required']) . "<br>";
    echo Input::email('email', 'john@example.com', ['class' => 'form-control']) . "<br>";
    echo Input::password('password', '', ['class' => 'form-control']) . "<br>";

    echo "Gender: ";
    echo Input::radio('gender', 'male', true, ['id' => 'gender-male']) . " Male ";
    echo Input::radio('gender', 'female', false, ['id' => 'gender-female']) . " Female <br>";
    echo Input::radio('gender', 'others', false, ['id' => 'gender-others', 'disabled' => 'disabled']) . " others <br>";

    echo "Interests: ";
    echo Input::checkbox('interests[]', 'sports', true, ['id' => 'int-sports']) . " Sports ";
    echo Input::checkbox('interests[]', 'music', false, ['id' => 'int-music']) . " Music ";
    echo Input::checkbox('interests[]', 'reading', true, ['id' => 'int-reading']) . " Reading <br>";

    $countryOptions = [
        '' => 'Select Country',
        'us' => 'United States',
        'ca' => 'Canada',
        'uk' => 'United Kingdom'
    ];
    echo Input::select('country', $countryOptions, 'us', ['class' => 'form-control']) . "<br>";

    echo Input::textarea('bio', 'This is my biography.', ['rows' => '3', 'class' => 'form-control']) . "<br>";

    echo Input::date('birthday', '1990-01-01') . "<br>";

    echo Input::file('profile_pic', ['accept' => 'image/*']) . "<br>";

    echo Input::hidden('csrf_token', 'abc123xyz') . "<br>";

    echo "<button type=\"submit\">Submit</button>";

    echo "</form>";

    echo "<div style='color: green;'>Form rendered successfully</div>";
}

// List of all test functions
$allTests = [
    'testText',
    'testRadio',
    'testTextarea',
    'testSelect',
    'testCheckbox',
    'testHidden',
    'testNumber',
    'testPassword',
    'testDate',
    'testTime',
    'testEmail',
    'testUrl',
    'testFile',
    'testAdvancedSecurity',
    'testInputsWithForm'
];

// Run all tests
$testSelection = $allTests;

// Run the selected tests
$runner = runTest($testSelection);

// Display test results
echo "<h2>Test Results</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Test</th><th>Status</th><th>Time/Error</th></tr>";

foreach ($runner as $test => $result) {
    echo "<tr>";
    echo "<td>{$test}</td>";
    echo "<td style='color: " . ($result['status'] === 'Pass' ? 'green' : 'red') . ";'>{$result['status']}</td>";
    echo "<td>" . (isset($result['time']) ? $result['time'] : $result['error']) . "</td>";
    echo "</tr>";
}

echo "</table>";
