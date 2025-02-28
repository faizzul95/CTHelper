<?php

if (file_exists('../vendor/autoload.php')) {
    include_once '../vendor/autoload.php';
}

// FUNCTION FOR DEBUG

function dump()
{
    array_map(function ($param) {
        echo '<pre>';
        var_dump($param);
        echo '</pre>';
    }, func_get_args());
}

function dd()
{
    array_map(function ($param) {
        echo '<pre>';
        print_r($param);
        echo '</pre>';
    }, func_get_args());
    die;
}

function runTest($scriptNames)
{
    $results = [];

    if (!is_array($scriptNames)) {
        return ['error' => 'Script names must be provided as an array'];
    }

    foreach ($scriptNames as $scriptName) {
        // Check if script name is a string
        if (!is_string($scriptName)) {
            $results[] = ['error' => 'Invalid script name'];
            continue;
        }

        // Start the timer
        $start_time = microtime(true);
        $usage_start = memory_get_usage();

        // Execute the test script
        try {
            $result = call_user_func($scriptName);
            $execution_time = microtime(true) - $start_time;

            $results[$scriptName] = [
                'method' => $scriptName,
                'execution_time' => _formatExecutionTime( $execution_time),
                'memory_usage' => _formatBytes(memory_get_usage() - $usage_start, 2),
                // 'result' => json_encode($result)
                'result' => $result
            ];
        } catch (Exception $e) {
            $results[$scriptName] = ['error' => $e->getMessage()];
        }

        usleep(1500);
    }

    return $results;
}

function _formatExecutionTime($executionTime)
{
    // Calculate nanoseconds using microtime
    $microtime = microtime(true);
    $nanoseconds = (int) (($microtime - floor($microtime)) * 1000000000);

    // Calculate milliseconds and other time units
    $milliseconds = round(($executionTime - floor($executionTime)) * 1000, 2);
    $totalSeconds = floor($executionTime);
    $seconds = $totalSeconds % 60;
    $minutes = floor(($totalSeconds % 3600) / 60);
    $hours = floor($totalSeconds / 3600);

    // Format the execution time with nanoseconds
    $formattedExecutionTime = sprintf("%dh %dm %ds %dms %dns", $hours, $minutes, $seconds, $milliseconds, $nanoseconds);

    // Handle cases where some time units are zero
    $formattedExecutionTime = preg_replace('/^0+h /', '', $formattedExecutionTime);
    $formattedExecutionTime = preg_replace('/^0+m /', '', $formattedExecutionTime);
    $formattedExecutionTime = preg_replace('/^0+s /', '', $formattedExecutionTime);
    $formattedExecutionTime = preg_replace('/^0+ms /', '', $formattedExecutionTime);

    return $formattedExecutionTime;
}

function _formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0); // Ensure non-negative bytes
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); // Calculate power of 1024
    $pow = min($pow, count($units) - 1); // Limit to valid unit index

    $bytes /= (1 << (10 * $pow)); // Divide by appropriate factor

    return round($bytes, $precision) . ' ' . $units[$pow];
}
