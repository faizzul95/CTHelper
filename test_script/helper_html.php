<?php
if (file_exists('../vendor/autoload.php'))
    include_once '../vendor/autoload.php';

use CT\Helpers\HTML;

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
 * Helper function to display the result of a test
 */
function displayTestResult($description, $expected, $actual, $testPassed = true)
{
    echo "<div style='margin-bottom: 10px;'>";
    echo "<strong>Test: </strong>" . htmlspecialchars($description) . "<br>";
    echo "<strong>Expected: </strong><code>" . htmlspecialchars($expected) . "</code><br>";
    echo "<strong>Actual: </strong><code>" . htmlspecialchars($actual) . "</code><br>";
    echo "<strong>Result: </strong><span style='color: " . ($testPassed ? "green" : "red") . ";'>" .
        ($testPassed ? "PASS" : "FAIL") . "</span>";
    echo "</div>";
}

/**
 * Test ul() method - Unordered List
 */
function testUl()
{
    echo "Testing ul() method<br><br>";

    // Test with normal values
    $items = ['Item 1', 'Item 2', 'Item 3'];
    $expected = '<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>';
    $result = HTML::ul($items);
    displayTestResult("Normal list items", $expected, $result, $expected === $result);

    // XSS attack test
    $maliciousItems = ['<script>alert("XSS")</script>', 'Item 2'];
    $expected = '<ul><li>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</li><li>Item 2</li></ul>';
    $result = HTML::ul($maliciousItems);
    displayTestResult("List with XSS attempt", $expected, $result, $expected === $result);

    // Empty array test
    $emptyItems = [];
    $expected = '<ul></ul>';
    $result = HTML::ul($emptyItems);
    displayTestResult("Empty list", $expected, $result, $expected === $result);
}

/**
 * Test ol() method - Ordered List
 */
function testOl()
{
    echo "Testing ol() method<br><br>";

    // Test with normal values
    $items = ['Item 1', 'Item 2', 'Item 3'];
    $expected = '<ol><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol>';
    $result = HTML::ol($items);
    displayTestResult("Normal list items", $expected, $result, $expected === $result);

    // XSS attack test
    $maliciousItems = ['<script>alert("XSS")</script>', 'Item 2'];
    $expected = '<ol><li>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</li><li>Item 2</li></ol>';
    $result = HTML::ol($maliciousItems);
    displayTestResult("List with XSS attempt", $expected, $result, $expected === $result);
}

/**
 * Test p() method - Paragraph
 */
function testP()
{
    echo "Testing p() method<br><br>";

    // Test with normal content
    $content = 'This is a paragraph.';
    $expected = '<p>This is a paragraph.</p>';
    $result = HTML::p($content);
    displayTestResult("Normal paragraph", $expected, $result, $expected === $result);

    // Test with attributes
    $attributes = ['class' => 'text-center', 'id' => 'main-paragraph'];
    $expected = '<p class="text-center" id="main-paragraph">This is a paragraph.</p>';
    $result = HTML::p($content, $attributes);
    displayTestResult("Paragraph with attributes", $expected, $result, $expected === $result);

    // XSS attack test
    $maliciousContent = '<script>alert("XSS")</script>';
    $expected = '<p>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</p>';
    $result = HTML::p($maliciousContent);
    displayTestResult("Paragraph with XSS attempt in content", $expected, $result, $expected === $result);

    // XSS through attributes
    $maliciousAttributes = ['onclick' => 'alert("XSS")', 'class' => 'legitimate'];
    $expected = '<p class="legitimate">Safe content</p>';
    $result = HTML::p('Safe content', $maliciousAttributes);
    displayTestResult(
        "Paragraph with XSS attempt in attributes",
        $expected,
        $result,
        strpos($result, 'onclick') === false
    );
}

/**
 * Test span() method
 */
function testSpan()
{
    echo "Testing span() method<br><br>";

    // Test with normal content
    $content = 'This is a span.';
    $expected = '<span>This is a span.</span>';
    $result = HTML::span($content);
    displayTestResult("Normal span", $expected, $result, $expected === $result);

    // Test with attributes
    $attributes = ['class' => 'highlight', 'id' => 'important'];
    $expected = '<span class="highlight" id="important">This is a span.</span>';
    $result = HTML::span($content, $attributes);
    displayTestResult("Span with attributes", $expected, $result, $expected === $result);

    // XSS attack test
    $maliciousContent = '<script>alert("XSS")</script>';
    $expected = '<span>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</span>';
    $result = HTML::span($maliciousContent);
    displayTestResult("Span with XSS attempt in content", $expected, $result, $expected === $result);
}

/**
 * Test button() method
 */
function testButton()
{
    echo "Testing button() method<br><br>";

    // Test with normal text
    $text = 'Click Me';
    $expected = '<button>Click Me</button>';
    $result = HTML::button($text);
    displayTestResult("Normal button", $expected, $result, $expected === $result);

    // Test with attributes
    $attributes = ['class' => 'btn-primary', 'id' => 'submit-btn'];
    $expected = '<button class="btn-primary" id="submit-btn">Click Me</button>';
    $result = HTML::button($text, $attributes);
    displayTestResult("Button with attributes", $expected, $result, $expected === $result);

    // XSS attempt in attributes
    $maliciousAttributes = ['onclick' => 'alert("XSS")', 'formaction' => 'javascript:alert(1)'];
    $result = HTML::button('Click Me', $maliciousAttributes);
    displayTestResult(
        "Button with XSS in attributes",
        "No 'onclick' or 'formaction' attributes",
        $result,
        strpos($result, 'onclick') === false && strpos($result, 'formaction') === false
    );
}

/**
 * Test div() method
 */
function testDiv()
{
    echo "Testing div() method<br><br>";

    // Test with normal content
    $content = 'This is a div.';
    $expected = '<div>This is a div.</div>';
    $result = HTML::div($content);
    displayTestResult("Normal div", $expected, $result, $expected === $result);

    // Test with attributes
    $attributes = ['class' => 'container', 'id' => 'main'];
    $expected = '<div class="container" id="main">This is a div.</div>';
    $result = HTML::div($content, $attributes);
    displayTestResult("Div with attributes", $expected, $result, $expected === $result);

    // XSS attack test
    $maliciousContent = '<script>alert("XSS")</script>';
    $expected = '<div>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</div>';
    $result = HTML::div($maliciousContent);
    displayTestResult("Div with XSS attempt in content", $expected, $result, $expected === $result);

    // XSS through style attribute
    $maliciousStyle = ['style' => 'background-image: url(javascript:alert("XSS"))'];
    $result = HTML::div('Content', $maliciousStyle);
    displayTestResult(
        "Div with XSS in style attribute",
        "No 'style' attribute",
        $result,
        strpos($result, 'style') === false
    );
}

/**
 * Test image() method
 */
function testImage()
{
    echo "Testing image() method<br><br>";

    // Test with normal values
    $src = 'image.jpg';
    $alt = 'Sample Image';
    $expected = '<img src="image.jpg" alt="Sample Image">';
    $result = HTML::image($src, $alt);
    displayTestResult("Normal image", $expected, $result, $expected === $result);

    // Test with additional attributes
    $attributes = ['class' => 'img-fluid', 'id' => 'main-image'];
    $expected = '<img class="img-fluid" id="main-image" src="image.jpg" alt="Sample Image">';
    $result = HTML::image($src, $alt, $attributes);
    displayTestResult("Image with attributes", $expected, $result, $expected === $result);

    // XSS through src attribute
    $maliciousSrc = 'javascript:alert("XSS")';
    $result = HTML::image($maliciousSrc, 'Alt Text');
    displayTestResult(
        "Image with XSS in src",
        "No 'javascript:' in src",
        $result,
        strpos($result, 'javascript:') === false
    );

    // XSS through onerror attribute
    $maliciousAttributes = ['onerror' => 'alert("XSS")'];
    $result = HTML::image('image.jpg', 'Alt Text', $maliciousAttributes);
    displayTestResult(
        "Image with onerror attribute",
        "No 'onerror' attribute",
        $result,
        strpos($result, 'onerror') === false
    );
}

/**
 * Test href() method
 */
function testHref()
{
    echo "Testing href() method<br><br>";

    // Test with normal values
    $href = 'https://example.com';
    $text = 'Visit Example';
    $expected = '<a href="https://example.com">Visit Example</a>';
    $result = HTML::href($href, $text);
    displayTestResult("Normal link", $expected, $result, $expected === $result);

    // Test with additional attributes
    $attributes = ['class' => 'btn', 'target' => '_blank'];
    $expected = '<a class="btn" target="_blank" href="https://example.com">Visit Example</a>';
    $result = HTML::href($href, $text, $attributes);
    displayTestResult("Link with attributes", $expected, $result, $expected === $result);

    // XSS through javascript: protocol
    $maliciousHref = 'javascript:alert("XSS")';
    $result = HTML::href($maliciousHref, 'Click me');
    displayTestResult(
        "Link with javascript: protocol",
        "No 'javascript:' in href",
        $result,
        strpos($result, 'javascript:') === false
    );

    // XSS through onclick attribute
    $maliciousAttributes = ['onclick' => 'alert("XSS")'];
    $result = HTML::href('https://example.com', 'Click me', $maliciousAttributes);
    displayTestResult(
        "Link with onclick attribute",
        "No 'onclick' attribute",
        $result,
        strpos($result, 'onclick') === false
    );
}

/**
 * Test css() method
 */
function testCss()
{
    echo "Testing css() method<br><br>";

    // Test with normal values
    $href = 'styles.css';
    $expected = '<link href="styles.css" rel="stylesheet">';
    $result = HTML::css($href);
    displayTestResult("Normal CSS link", $expected, $result, $expected === $result);

    // Test with additional attributes
    $attributes = ['id' => 'main-styles', 'media' => 'screen'];
    $expected = '<link id="main-styles" media="screen" href="styles.css" rel="stylesheet">';
    $result = HTML::css($href, $attributes);
    displayTestResult("CSS link with attributes", $expected, $result, $expected === $result);

    // XSS through href attribute
    $maliciousHref = 'javascript:alert("XSS")';
    $result = HTML::css($maliciousHref);
    displayTestResult(
        "CSS link with javascript: protocol",
        "No 'javascript:' in href",
        $result,
        strpos($result, 'javascript:') === false
    );

    // XSS through onload attribute
    $maliciousAttributes = ['onload' => 'alert("XSS")'];
    $result = HTML::css('styles.css', $maliciousAttributes);
    displayTestResult(
        "CSS link with onload attribute",
        "No 'onload' attribute",
        $result,
        strpos($result, 'onload') === false
    );
}

/**
 * Test table() method
 */
function testTable()
{
    echo "Testing table() method<br><br>";

    // Test with normal values
    $data = [
        ['Name', 'Age', 'Location'],
        ['John Doe', 30, 'New York'],
        ['Jane Smith', 25, 'Los Angeles']
    ];
    $expected = '<table><tr><td>Name</td><td>Age</td><td>Location</td></tr><tr><td>John Doe</td><td>30</td><td>New York</td></tr><tr><td>Jane Smith</td><td>25</td><td>Los Angeles</td></tr></table>';
    $result = HTML::table($data);
    displayTestResult("Normal table", $expected, $result, $expected === $result);

    // Test with attributes
    $attributes = ['class' => 'table-striped', 'id' => 'data-table'];
    $expected = '<table class="table-striped" id="data-table"><tr><td>Name</td><td>Age</td><td>Location</td></tr><tr><td>John Doe</td><td>30</td><td>New York</td></tr><tr><td>Jane Smith</td><td>25</td><td>Los Angeles</td></tr></table>';
    $result = HTML::table($data, $attributes);
    displayTestResult("Table with attributes", $expected, $result, $expected === $result);

    // XSS in cell content
    $maliciousData = [
        ['Name', 'Script'],
        ['John', '<script>alert("XSS")</script>']
    ];
    $expected = '<table><tr><td>Name</td><td>Script</td></tr><tr><td>John</td><td>&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;</td></tr></table>';
    $result = HTML::table($maliciousData);
    displayTestResult("Table with XSS in content", $expected, $result, $expected === $result);

    // XSS through attributes
    $maliciousAttributes = ['onmouseover' => 'alert("XSS")', 'style' => 'expression(alert("XSS"))'];
    $result = HTML::table($data, $maliciousAttributes);
    displayTestResult(
        "Table with XSS in attributes",
        "No dangerous attributes",
        $result,
        strpos($result, 'onmouseover') === false && strpos($result, 'style') === false
    );
}

/**
 * Advanced test for complex XSS vectors
 */
function testAdvancedXSS()
{
    echo "Testing advanced XSS prevention<br>";

    // Test 1: Encoded JavaScript in URL
    $encodedJS = 'java&#115;cript:alert(1)';
    $result = HTML::href($encodedJS, 'Click me');
    displayTestResult(
        "Encoded JavaScript in URL",
        "No JavaScript execution",
        $result,
        strpos($result, 'javascript') === false
    );

    // Test 2: Data URI with embedded JS
    $dataURI = 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==';
    $result = HTML::image($dataURI, 'Alt Text');
    displayTestResult(
        "Data URI with embedded JS",
        "No data: protocol",
        $result,
        strpos($result, 'data:') === false
    );

    // Test 3: SVG with embedded script
    $svgXSS = '<svg onload="alert(1)">';
    $result = HTML::div($svgXSS);
    displayTestResult(
        "SVG with embedded script",
        "Escaped SVG",
        $result,
        strpos($result, '<svg onload') === false
    );

    // Test 4: CSS expression
    $cssExpr = ['style' => 'background:url(javascript:alert(1))'];
    $result = HTML::div('Content', $cssExpr);
    displayTestResult(
        "CSS expression",
        "No style attribute",
        $result,
        strpos($result, 'style') === false
    );

    // Test 5: Multiple vectors in one attribute
    $multiVector = ['title' => '"><script>alert(1)</script>', 'class' => 'normal-class'];
    $result = HTML::p('Content', $multiVector);
    displayTestResult(
        "Multiple vectors in attribute",
        "Sanitized output",
        $result,
        strpos($result, '<script>') === false
    );

    // Test 6: HTML injection with unusual tags
    $unusualTags = '<iframe src="javascript:alert(1)"></iframe><embed src="javascript:alert(1)">';
    $result = HTML::div($unusualTags);
    displayTestResult(
        "HTML injection with unusual tags",
        "Escaped HTML",
        $result,
        strpos($result, '<iframe') === false
    );
}

/**
 * Test for handling null and array inputs
 */
function testEdgeCases()
{
    echo "Testing edge cases<br>";

    // Test with null content
    $result = HTML::p(null);
    $expected = '<p></p>';
    displayTestResult("Null content in paragraph", $expected, $result, $expected === $result);

    // Test with array content (should be empty as per secureValue method)
    $arrayContent = ['item1', 'item2'];
    $result = HTML::p($arrayContent);
    $expected = '<p></p>';
    displayTestResult("Array content in paragraph", $expected, $result, $expected === $result);

    // Test with empty attributes
    $result = HTML::div('Content', []);
    $expected = '<div>Content</div>';
    displayTestResult("Empty attributes in div", $expected, $result, $expected === $result);

    // Test with numeric content
    $numericContent = 123;
    $result = HTML::span($numericContent);
    $expected = '<span>123</span>';
    displayTestResult("Numeric content in span", $expected, $result, $expected === $result);
}

/**
 * Comprehensive security test focused on pattern bypasses
 */
function testSecurityBypass()
{
    echo "Testing security bypass attempts<br>";

    // Test 1: Case sensitivity bypass
    $upperCaseJS = 'jAvAsCrIpT:alert(1)';
    $result = HTML::href($upperCaseJS, 'Click me');
    displayTestResult(
        "Case sensitivity bypass",
        "No JavaScript execution",
        $result,
        stripos($result, 'javascript:') === false
    );

    // Test 2: Protocol hiding with whitespace
    $whitespaceJS = 'javascript: alert(1)';
    $result = HTML::href($whitespaceJS, 'Click me');
    displayTestResult(
        "Protocol hiding with whitespace",
        "No JavaScript execution",
        $result,
        stripos($result, 'javascript:') === false
    );

    // Test 3: Complex attribute combination
    $mixedAttributes = [
        'onclick' => 'alert(1)',
        'data-x' => 'safe',
        'onmouseover' => 'alert(2)',
        'id' => 'test-id'
    ];
    $result = HTML::div('Content', $mixedAttributes);
    displayTestResult(
        "Complex attribute combination",
        "Only safe attributes remain",
        $result,
        strpos($result, 'onclick') === false &&
            strpos($result, 'onmouseover') === false &&
            strpos($result, 'data-x') !== false &&
            strpos($result, 'id') !== false
    );

    // Test 4: Nested vectors
    $nestedVector = 'normal" onmouseover="alert(1)';
    $result = HTML::p($nestedVector);
    $expected = '<p>normal&quot; onmouseover=&quot;alert(1)</p>';
    displayTestResult(
        "Nested XSS attempt in content",
        $expected,
        $result,
        $expected === $result
    );

    // Test 5: Style with expression
    $styleExpr = ['style' => 'width: expression(alert(1))'];
    $result = HTML::div('Content', $styleExpr);
    displayTestResult(
        "Style with expression",
        "No style attribute",
        $result,
        strpos($result, 'style') === false
    );
}

// List of all test functions
$allTests = [
    'testUl',
    'testOl',
    'testP',
    'testSpan',
    'testButton',
    'testDiv',
    'testImage',
    'testHref',
    'testCss',
    'testTable',
    'testAdvancedXSS',
    'testEdgeCases',
    'testSecurityBypass'
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
