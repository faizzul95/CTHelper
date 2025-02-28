<?php

if (file_exists('../vendor/autoload.php'))
    include_once '../vendor/autoload.php';

use CT\Helpers\Debug;

// Set global variable
global $debug;
$debug = new Debug();

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
 * Test the dd() method - Dump and Die
 * Note: This function should terminate script execution
 */
function testDd()
{
    global $debug;
    echo "Testing dd() - Dump and Die method<br>";
    echo "This will terminate script execution after displaying data<br>";

    $testArray = ['name' => 'John', 'age' => 30, 'skills' => ['PHP', 'MySQL', 'JavaScript']];
    $debug->dd($testArray, "This is a string", 123);

    echo "This line should not be executed";
}

/**
 * Test the dump() method - Variable dumping without termination
 */
function testDump()
{
    global $debug;
    echo "Testing dump() - Variable dumping method<br>";

    $testObject = new stdClass();
    $testObject->name = "Test Object";
    $testObject->value = 42;

    $debug->dump("Simple string", 123, $testObject);

    echo "Script continues after dump()<br>";
}

/**
 * Test the pr() method - Print_r formatting without termination
 */
function testPr()
{
    global $debug;
    echo "Testing pr() - Print_r formatting method<br>";

    $testArray = [
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob']
        ],
        'settings' => [
            'debug' => true,
            'cache' => false
        ]
    ];

    $debug->pr($testArray);

    echo "Script continues after pr()<br>";
}

/**
 * Test the executionTime() method - Measuring script execution time
 */
function testExecutionTime()
{
    global $debug;
    echo "Testing executionTime() - Measuring script execution time<br>";

    $start = microtime(true);

    // Simulate processing time
    usleep(500000); // 500ms sleep

    // Basic format
    echo "Basic format:<br>";
    $debug->executionTime($start);

    // Sleep a bit more
    usleep(500000); // 500ms more

    // Detailed format
    echo "Detailed format:<br>";
    $debug->executionTime($start, null, true, true);

    // Non-echo mode
    $timeString = $debug->executionTime($start, null, false);
    echo "Non-echo mode result: {$timeString}<br>";
}

/**
 * Test the memoryUsage() method - Displaying memory usage
 */
function testMemoryUsage()
{
    global $debug;
    echo "Testing memoryUsage() - Displaying memory usage<br>";

    // Allocate some memory
    $arr = [];
    for ($i = 0; $i < 100000; $i++) {
        $arr[] = "Item " . $i;
    }

    // Regular memory usage
    echo "Regular memory usage:<br>";
    $debug->memoryUsage();

    // Peak memory usage
    echo "Peak memory usage:<br>";
    $debug->memoryUsage(true);

    // Detailed memory usage
    echo "Detailed memory usage:<br>";
    $debug->memoryUsage(false, true, true);

    // Non-echo mode
    $memoryString = $debug->memoryUsage(true, false);
    echo "Non-echo mode result: {$memoryString}<br>";

    // Clean up
    unset($arr);
}

/**
 * Test the backtrace() method - Display function call stack
 */
function testBacktrace()
{
    global $debug;
    nestedFunction1();
}

function nestedFunction1()
{
    nestedFunction2();
}

function nestedFunction2()
{
    nestedFunction3();
}

function nestedFunction3()
{
    global $debug;
    echo "Testing backtrace() - Display function call stack<br>";

    // HTML format
    echo "HTML format:<br>";
    $debug->backtrace();

    // Plain text format
    echo "Plain text format:<br>";
    $debug->backtrace(false);

    // Limited stack frames
    echo "Limited to 2 stack frames:<br>";
    $debug->backtrace(true, true, 2);

    // Non-echo mode
    $traceString = $debug->backtrace(true, false);
    echo "Backtrace string length: " . strlen($traceString) . " characters<br>";
}

/**
 * Test the exception() method - Format exception display
 */
function testException()
{
    global $debug;
    echo "Testing exception() - Format exception display<br>";

    try {
        // Generate a division by zero error
        $result = 10 / 0;
    } catch (Throwable $e) {  // This will catch both Exception and Error types
        // Display the exception without terminating
        $debug->exception($e, false);
    }

    try {
        // Generate a file not found error
        require_once 'non_existent_file.php';
    } catch (Error $e) {
        // Display the error without terminating
        $debug->exception($e, false);
    }

    echo "Script continues after exception display<br>";
}

/**
 * Test the timer methods - startTimer() and endTimer()
 */
function testTimers()
{
    global $debug;
    echo "Testing timer methods - startTimer() and endTimer()<br>";

    // Start default timer
    $debug->startTimer();

    // Start named timers
    $debug->startTimer('process1');
    $debug->startTimer('process2');

    // Simulate different execution times
    usleep(300000); // 300ms

    // End first named timer
    echo "Process 1 timer:<br>";
    $debug->endTimer('process1');

    usleep(200000); // 200ms more

    // End second named timer
    echo "Process 2 timer:<br>";
    $debug->endTimer('process2');

    usleep(100000); // 100ms more

    // End default timer with detailed format
    echo "Default timer with detailed format:<br>";
    $debug->endTimer('default', true, true);

    // Test non-existent timer
    echo "Non-existent timer:<br>";
    $result = $debug->endTimer('nonexistent');
    echo "Result: {$result}<br>";
}

/**
 * Test the formatSql() method - SQL query formatting
 */
function testFormatSql()
{
    global $debug;
    echo "Testing formatSql() - SQL query formatting<br>";

    // Simple query
    $sql1 = "SELECT * FROM users WHERE status = 'active'";
    echo "Simple query:<br>";
    $debug->formatSql($sql1);

    // Complex query
    $sql2 = "SELECT u.id, u.name, p.title FROM users u LEFT JOIN posts p ON u.id = p.user_id WHERE u.status = 'active' AND p.published = 1 ORDER BY p.created_at DESC LIMIT 10";
    echo "Complex query:<br>";
    $debug->formatSql($sql2);

    // Query with parameters
    $sql3 = "SELECT * FROM products WHERE category_id = ? AND price > ? AND name LIKE ?";
    $params3 = [5, 100, "%laptop%"];
    echo "Query with positional parameters:<br>";
    $debug->formatSql($sql3, $params3);

    // Query with named parameters
    $sql4 = "SELECT * FROM orders WHERE user_id = :user_id AND status = :status AND order_date > :date";
    $params4 = [
        'user_id' => 42,
        'status' => 'pending',
        'date' => '2023-01-01'
    ];
    echo "Query with named parameters:<br>";
    $debug->formatSql($sql4, $params4);

    // Query with named parameters
    $sql5 = "SELECT * FROM orders WHERE user_id = :0 AND status = :1 AND order_date <= :2";
    $params5 = [
        152,
        'success',
        '2023-01-23'
    ];
    echo "Query with index parameters:<br>";
    $debug->formatSql($sql5, $params5);

    // Complex query with JOIN across 3 tables and more than 8 parameters
    $sql6 = "
    SELECT o.id AS order_id, o.order_date, o.status, o.total_amount,
        u.id AS user_id, u.name AS user_name, u.email, u.created_at AS user_registered,
        p.id AS product_id, p.name AS product_name, p.price, p.stock
    FROM orders o
    INNER JOIN users u ON o.user_id = u.id
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.user_id = :0 
    AND o.status = :1 
    AND o.order_date BETWEEN :2 AND :3
    AND o.total_amount >= :4
    AND u.email LIKE :5
    AND u.created_at >= :6
    AND p.price BETWEEN :7 AND :8
    AND (p.stock IS NULL OR p.stock >= :9)
    ORDER BY o.order_date DESC, p.price ASC
    LIMIT 50;
    ";

    $params6 = [
        152,              // :0 -> user_id
        'success',        // :1 -> order status
        '2023-01-01',     // :2 -> order_date start
        '2023-01-23',     // :3 -> order_date end
        100.00,           // :4 -> minimum order total_amount
        '%@gmail.com%',   // :5 -> user email filter
        '2022-01-01',     // :6 -> user account created after this date
        10.00,            // :7 -> product price min
        500.00,           // :8 -> product price max
        5                // :9 -> product stock (allow null values)
    ];

    echo "<strong>Advanced Query with 3-Table JOIN & 10 Parameters:</strong><br>";
    $debug->formatSql($sql6, $params6);
}

/**
 * Test the highlight() method - Content highlighting
 */
function testHighlight()
{
    global $debug;
    echo "Testing highlight() - Content highlighting<br>";

    // Default yellow highlight
    echo "Text with ";
    $debug->highlight("default yellow");
    echo " highlighting<br>";

    // Custom color highlight
    echo "Text with ";
    $debug->highlight("custom red", "red");
    echo " highlighting<br>";

    echo "Text with ";
    $debug->highlight("custom green", "#85c585");
    echo " highlighting<br>";

    // Non-echo mode
    $highlighted = $debug->highlight("non-echo mode", "cyan", false);
    echo "Using non-echo mode: {$highlighted}<br>";
}

/**
 * Test the inspect() method - Variable inspection
 */
function testInspect()
{
    global $debug;
    echo "Testing inspect() - Variable inspection<br>";

    // Simple variable
    $string = "Hello World";
    echo "Simple string variable:<br>";
    $debug->inspect($string, 'string');

    // Array
    $array = ['a' => 1, 'b' => 2, 'c' => [3, 4, 5]];
    echo "Array variable:<br>";
    $debug->inspect($array, 'array');

    // Object
    $object = new stdClass();
    $object->property1 = "Value 1";
    $object->property2 = 42;
    $object->nested = new stdClass();
    $object->nested->property = "Nested property";
    echo "Object variable:<br>";
    $debug->inspect($object, 'object');

    // Non-echo mode
    $inspection = $debug->inspect($string, 'non-echo', false);
    echo "Inspection result length: " . strlen($inspection) . " characters<br>";
}

/**
 * Test the error(), warning() and success() methods - Message display
 */
function testMessages()
{
    global $debug;
    echo "Testing error(), warning() and success() methods - Message display<br>";

    // Error message
    $debug->error("Something went wrong with the operation.", "Operation Failed");

    // Error with termination - commented out to allow test to continue
    // $debug->error("This would terminate the script.", "Fatal Error", true);

    // Success message
    $debug->success("Operation completed successfully.", "Operation Complete");

    // Warning message
    $debug->warning("System under maintenanced.");

    // Custom success message
    $debug->success("User data saved to the database.", "User Saved");
}

/**
 * Test the phpInfo() method - PHP environment information
 */
function testPhpInfo()
{
    global $debug;
    echo "Testing phpInfo() - PHP environment information<br>";

    $debug->phpInfo();

    // Non-echo mode
    $info = $debug->phpInfo(false);
    echo "PHP info length: " . strlen($info) . " characters<br>";
}

/**
 * Test the console() method - Browser console logging
 */
function testConsole()
{
    global $debug;
    echo "Testing console() - Browser console logging<br>";
    echo "Check your browser's JavaScript console to see the logs.<br>";

    // Log a string
    $debug->console("Simple string message");

    // Log a number
    $debug->console(42);

    // Log an array
    $debug->console(['a' => 1, 'b' => 2, 'c' => 3]);

    // Log with label
    $debug->console("Labeled message", "Label");

    // Log an object
    $obj = new stdClass();
    $obj->name = "Test Object";
    $obj->value = 42;
    $debug->console($obj, "Test Object");
}

/**
 * Test the table() method - Array data table display
 */
function testTable()
{
    global $debug;
    echo "Testing table() - Array data table display<br>";

    // Simple data
    $data1 = [
        ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ['id' => 3, 'name' => 'Bob', 'email' => 'bob@example.com']
    ];

    echo "Simple table with auto-headers:<br>";
    $debug->table($data1);

    // Custom headers
    $data2 = [
        [1, 'John Doe', 'Active'],
        [2, 'Jane Smith', 'Inactive'],
        [3, 'Bob Johnson', 'Pending']
    ];

    $headers2 = ['User ID', 'Full Name', 'Status'];

    echo "Table with custom headers:<br>";
    $debug->table($data2, $headers2);

    // Table with nested data
    $data3 = [
        [
            'id' => 1,
            'name' => 'John',
            'details' => ['age' => 30, 'location' => 'New York']
        ],
        [
            'id' => 2,
            'name' => 'Jane',
            'details' => ['age' => 28, 'location' => 'Boston']
        ]
    ];

    echo "Table with nested data:<br>";
    $debug->table($data3);

    // Non-echo mode
    $tableHTML = $debug->table($data1, [], false);
    echo "Table HTML length: " . strlen($tableHTML) . " characters<br>";
}

/**
 * Test the constants() method - Display defined constants
 */
function testConstants()
{
    global $debug;
    echo "Testing constants() - Display defined constants<br>";

    // Define some custom constants
    define('TEST_CONSTANT_1', 'Value 1');
    define('TEST_CONSTANT_2', 42);
    define('TEST_CONSTANT_3', ['a', 'b', 'c']);

    $debug->constants();

    // Non-echo mode
    $constantsOutput = $debug->constants(false);
    echo "Constants output length: " . strlen($constantsOutput) . " characters<br>";
}

/**
 * Test the extensions() method - Display PHP extensions
 */
function testExtensions()
{
    global $debug;
    echo "Testing extensions() - Display PHP extensions<br>";

    $debug->extensions();

    // Non-echo mode
    $extensionsOutput = $debug->extensions(false);
    echo "Extensions output length: " . strlen($extensionsOutput) . " characters<br>";
}

/**
 * Test the params() method - Display function parameters
 */
function testParams()
{
    global $debug;
    testParamsHelper('string value', 42, ['array', 'values'], new stdClass());
}

function testParamsHelper($param1, $param2, $param3, $param4)
{
    global $debug;
    echo "Testing params() - Display function parameters<br>";

    // Display with backtrace
    echo "With backtrace:<br>";
    $debug->params(func_get_args());

    // Display without backtrace
    echo "Without backtrace:<br>";
    $debug->params(func_get_args(), false);

    // Non-echo mode
    $paramsOutput = $debug->params(func_get_args(), true, false);
    echo "Params output length: " . strlen($paramsOutput) . " characters<br>";
}

/**
 * Test the flow() and showFlow() methods - Execution flow logging
 */
function testFlow()
{
    global $debug;
    echo "Testing flow() and showFlow() - Execution flow logging<br>";

    $debug->flow("Starting flow test");

    // Simulate some processing steps
    $debug->flow("Step 1: Initialization");
    usleep(100000); // 100ms

    $debug->flow("Step 2: Processing data");
    usleep(200000); // 200ms

    // Nested function call
    testFlowNested();

    $debug->flow("Step 4: Finalizing");
    usleep(100000); // 100ms

    // Display the flow log
    $debug->showFlow();
}

function testFlowNested()
{
    global $debug;
    $debug->flow("Step 3: Nested function processing");
    usleep(150000); // 150ms
}

/**
 * Test the serverInfo() - Show the server information.
 */
function testServerInfo()
{
    global $debug;
    echo "Testing serverInfo() - Display Server Information<br>";

    $debug->serverInfo();
}

/**
 * Test the debugSection() method - Debugging function execution.
 */
function testDebugSection()
{
    global $debug;
    echo "<strong>Testing debugSection()</strong><br>";

    // Simple function test
    $testFunction = function ($x, $y) {
        return $x + $y;
    };
    $result = $debug->debugSection($testFunction, [5, 10], true);
    echo "Simple function result: " . $result . "<br><br>";

    // Test with method in class
    class TestClass
    {
        public $property = 'test value';

        public function add($x, $y)
        {
            return $x + $y + strlen($this->property);
        }

        public static function multiply($x, $y)
        {
            return $x * $y;
        }
    }

    $testObj = new TestClass();
    $result = $debug->debugSection([$testObj, 'add'], [10, 20], true);
    echo "Object method result: " . $result . "<br><br>";

    // Test with static method
    $result = $debug->debugSection(['TestClass', 'multiply'], [6, 7], true);
    echo "Static method result: " . $result . "<br><br>";

    // Test with exception throwing function
    $exceptionFunction = function ($divisor) {
        if ($divisor === 0) {
            throw new \Exception("Division by zero");
        }
        return 100 / $divisor;
    };

    try {
        $result = $debug->debugSection($exceptionFunction, [2], true);
        echo "Function with valid input result: " . $result . "<br><br>";

        $result = $debug->debugSection($exceptionFunction, [0], true);
    } catch (\Exception $e) {
        echo "Caught expected exception: " . $e->getMessage() . "<br><br>";
    }

    // Test with recursive function to analyze performance
    $fibonacci = function ($n) use (&$fibonacci) {
        if ($n <= 1) return $n;
        return $fibonacci($n - 1) + $fibonacci($n - 2);
    };

    $result = $debug->debugSection($fibonacci, [10], true);
    echo "Recursive function result: " . $result . "<br><br>";

    // Test with complex arguments
    $complexFunction = function ($string, $array, $object, $callback) {
        $result = $string . ' - Array count: ' . count($array);
        $result .= ' - Object class: ' . get_class($object);
        $result .= ' - Callback result: ' . $callback(5);
        return $result;
    };

    $result = $debug->debugSection(
        $complexFunction,
        [
            "Complex test",
            [1, 2, 3, 4, 5],
            new \stdClass(),
            function ($x) {
                return $x * $x;
            }
        ],
        true
    );
    echo "Complex arguments result: " . $result . "<br><br>";

    // Test with function that uses globals
    global $globalVar;
    $globalVar = 42;
    $globalUsingFunction = function () {
        global $globalVar;
        return "Global value: " . $globalVar;
    };

    $result = $debug->debugSection($globalUsingFunction, [], true);
    echo "Global-using function result: " . $result . "<br><br>";
}

/**
 * Test the callStack() method - Generating a call stack trace.
 */
function testCallStack()
{
    global $debug;
    echo "<strong>Testing callStack()</strong><br>";

    // Basic call
    $debug->callStack();
    echo "<br>Basic call stack generated.<br><br>";

    // Test with nested function calls
    function level3()
    {
        global $debug;
        $debug->callStack(true, 10);
        echo "<br>Level 3 call stack generated.<br><br>";
    }

    function level2()
    {
        level3();
    }

    function level1()
    {
        level2();
    }

    level1();

    // Test with different limit values
    $debug->callStack(true, 1);
    echo "<br>Limited (1) call stack generated.<br><br>";

    $debug->callStack(true, 5);
    echo "<br>Limited (5) call stack generated.<br><br>";

    // Test without echo
    $stackOutput = $debug->callStack(false);
    echo "Call stack length: " . strlen($stackOutput) . " characters<br><br>";

    // Test inside try-catch block
    try {
        throw new \Exception("Test exception for call stack");
    } catch (\Exception $e) {
        $debug->callStack();
        echo "<br>Call stack during exception handling.<br><br>";
    }
}

/**
 * Test the analyze() method - Variable analysis.
 */
function testAnalyze()
{
    global $debug;
    echo "<strong>Testing analyze()</strong><br>";

    // Basic types
    $debug->analyze("Hello, World!", "Test String");
    $debug->analyze(12345, "Test Integer");
    $debug->analyze(3.14159, "Test Float");
    $debug->analyze(true, "Test Boolean");
    $debug->analyze([1, 2, 3, "a" => "apple"], "Test Array");
    $debug->analyze([1, 2, 3, 244], "Test Array 2");
    $debug->analyze([1, 2, 3, 4 => "apple"], "Test Array 3");
    $debug->analyze((object) ["name" => "John", "age" => 30], "Test Object");
    $debug->analyze(null, "Test NULL");

    // Complex strings
    $longString = str_repeat("This is a very long string for testing. ", 20);
    $debug->analyze($longString, "Long String");

    $multilineString = "Line 1\nLine 2\nLine 3\r\nLine 4";
    $debug->analyze($multilineString, "Multiline String");

    $htmlString = '<div class="container"><h1>Title</h1><p>Paragraph</p></div>';
    $debug->analyze($htmlString, "HTML String");

    // Complex arrays
    $multiDimensionalArray = [
        'first' => [1, 2, 3],
        'second' => [
            'nested' => [
                'deep' => [
                    'deeper' => 'value'
                ]
            ]
        ],
        'third' => [true, false, null, 3.14]
    ];
    $debug->analyze($multiDimensionalArray, "Multi-dimensional Array");

    $largeArray = array_fill(0, 100, "item");
    $debug->analyze($largeArray, "Large Array");

    // Complex objects
    class TestComplexObject
    {
        public $publicProp = 'public';
        protected $protectedProp = 'protected';
        private $privateProp = 'private';
        public $arrayProp = [1, 2, 3];
        public $objectProp;

        public function __construct()
        {
            $this->objectProp = new \stdClass();
            $this->objectProp->name = 'Inner Object';
        }

        public function getMethod()
        {
            return 'method result';
        }
    }

    $complexObj = new TestComplexObject();
    $debug->analyze($complexObj, "Complex Object");

    // Recursive objects
    class RecursiveObject
    {
        public $name;
        public $child;

        public function __construct($name, $child = null)
        {
            $this->name = $name;
            $this->child = $child;
        }
    }

    $recursiveObj = new RecursiveObject('Parent');
    $recursiveObj->child = new RecursiveObject('Child');
    $recursiveObj->child->child = new RecursiveObject('Grandchild');
    $recursiveObj->child->child->child = $recursiveObj; // Create circular reference

    $debug->analyze($recursiveObj, "Recursive Object");

    // Resources (if available)
    if (function_exists('imagecreate')) {
        $img = imagecreate(100, 100);
        $debug->analyze($img, "Resource (Image)");
        imagedestroy($img);
    }

    $file = @fopen('php://memory', 'r');
    if ($file) {
        $debug->analyze($file, "Resource (File)");
        fclose($file);
    }

    // Special cases
    $debug->analyze(INF, "Infinity");
    $debug->analyze(NAN, "Not a Number");

    // Test without echo
    $output = $debug->analyze("no echo test", "No Echo", false);
    echo "Analyze without echo - output length: " . strlen($output) . " characters<br><br>";

    // Closures and callable
    $closure = function ($x) {
        return $x * 2;
    };
    $debug->analyze($closure, "Closure");

    $debug->analyze('str_replace', "Callable (Function name)");
    $debug->analyze([$complexObj, 'getMethod'], "Callable (Object method)");

    echo "<br>Variable analysis completed.<br><br>";
}

/**
 * Test the variables() method - Displaying variables in scope.
 */
function testVariables()
{
    global $debug;
    echo "<strong>Testing variables()</strong><br>";

    // Create various test variables
    $string = "Test string";
    $number = 42;
    $float = 3.14159;
    $boolean = true;
    $array = [1, 2, 3, 4, 5];
    $assocArray = ['name' => 'John', 'age' => 30];
    $object = new stdClass();
    $object->property = 'value';
    $null = null;

    // Local variables
    $debug->variables(false, true);

    // With globals
    $debug->variables(true, true);

    // Without echo
    $output = $debug->variables(false, false);
    echo "Variables output length: " . strlen($output) . " characters<br><br>";
}

/**
 * Test nested and complex scenarios involving multiple debug features.
 */
function testComplexScenarios()
{
    global $debug;
    echo "<strong>Testing Complex Scenarios</strong><br>";

    // Scenario 1: Debug a section that itself uses debug features
    $nestedDebugFunction = function ($value) use ($debug) {
        // Analyze the input
        $debug->analyze($value, "Inside Nested Function");

        // Show call stack from within
        $debug->callStack(true, 3);

        // Process the value
        return is_array($value) ? array_sum($value) : (is_numeric($value) ? $value * 2 : strlen($value));
    };

    $debug->debugSection($nestedDebugFunction, [[1, 2, 3, 4, 5]], true);

    // Scenario 2: Debug in exception handling
    $exceptionHandlingTest = function () use ($debug) {
        try {
            // Deliberately cause an error
            $result = 100 / 0;
            return $result;
        } catch (\Exception | \Error $e) {
            // Analyze the exception
            $debug->analyze($e, "Caught Exception/Error");

            // Show variables in this scope
            $debug->variables(false, true);

            // Show call stack at point of exception
            $debug->callStack();

            return "Error handled: " . $e->getMessage();
        }
    };

    $debug->debugSection($exceptionHandlingTest, [], true);

    // Scenario 3: Performance testing with recursive functions
    $factorial = function ($n) use (&$factorial) {
        if ($n <= 1) return 1;
        return $n * $factorial($n - 1);
    };

    for ($i = 5; $i <= 10; $i++) {
        $debug->debugSection($factorial, [$i], true);
    }

    // Scenario 4: Memory usage tracking
    $memoryTest = function ($size) {
        $array = [];
        for ($i = 0; $i < $size; $i++) {
            $array[] = str_repeat("X", 1000); // Approximately 1KB per item
        }
        return "Created array with " . count($array) . " items";
    };

    $debug->debugSection($memoryTest, [10], true);
    $debug->debugSection($memoryTest, [100], true);

    echo "<br>Complex scenarios completed.<br><br>";
}

// List of all test functions
$allTests = [
    'testDump',
    'testPr',
    'testExecutionTime',
    'testMemoryUsage',
    'testBacktrace',
    'testException',
    'testTimers',
    'testFormatSql',
    'testHighlight',
    'testInspect',
    'testMessages',
    'testPhpInfo',
    'testConsole',
    'testTable',
    'testConstants',
    'testExtensions',
    'testParams',
    'testFlow',
    'testServerInfo',
    'testDebugSection',
    'testCallStack',
    'testAnalyze',
    'testVariables',
    'testComplexScenarios',
    // 'testDd' // Commented out as it terminates execution
];

// Run selected tests - you can choose specific tests or run all
$testSelection = [
    'testDump',
    'testPr',
    'testMemoryUsage',
    'testTable'
];

// To run all tests, uncomment the next line:
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

// Uncomment to test dd() separately as it terminates execution
// testDd();