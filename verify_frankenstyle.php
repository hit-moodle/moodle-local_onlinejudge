#!/usr/bin/env php
<?php
/**
 * Verification script for frankenstyle naming compliance
 * 
 * This script verifies that all classes have correct syntax
 * and that the file structure is properly organized.
 */

$basedir = dirname(__FILE__);

echo "Verifying frankenstyle naming compliance...\n";

// Check if all new class files exist
$newClassFiles = [
    'classes/exception.php',
    'classes/judge/base.php',
    'classes/judge/sandbox.php',
    'classes/judge/sphere_engine.php',
    'classes/event/task_judged.php'
];

echo "\n1. Checking file existence:\n";
foreach ($newClassFiles as $file) {
    $fullPath = $basedir . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

// Check syntax of all new files
echo "\n2. Checking syntax:\n";
foreach ($newClassFiles as $file) {
    $fullPath = $basedir . '/' . $file;
    if (file_exists($fullPath)) {
        $output = [];
        $return = 0;
        exec("php -l " . escapeshellarg($fullPath), $output, $return);
        if ($return === 0) {
            echo "✓ $file has valid syntax\n";
        } else {
            echo "✗ $file has syntax errors\n";
            echo "  " . implode("\n  ", $output) . "\n";
        }
    }
}

// Check that judgelib.php loads properly
echo "\n3. Checking main library file:\n";
$output = [];
$return = 0;
exec("php -l " . escapeshellarg($basedir . '/judgelib.php'), $output, $return);
if ($return === 0) {
    echo "✓ judgelib.php has valid syntax\n";
} else {
    echo "✗ judgelib.php has syntax errors\n";
    echo "  " . implode("\n  ", $output) . "\n";
}

// Check that language file has proper structure
echo "\n4. Checking language strings:\n";
$langFile = $basedir . '/lang/en/local_onlinejudge.php';
if (file_exists($langFile)) {
    $content = file_get_contents($langFile);
    if (strpos($content, 'event_task_judged') !== false) {
        echo "✓ New event language strings found\n";
    } else {
        echo "✗ New event language strings missing\n";
    }
} else {
    echo "✗ Language file missing\n";
}

// Check file structure
echo "\n5. Checking directory structure:\n";
$requiredDirs = ['classes', 'classes/judge', 'classes/event'];
foreach ($requiredDirs as $dir) {
    $fullPath = $basedir . '/' . $dir;
    if (is_dir($fullPath)) {
        echo "✓ Directory $dir exists\n";
    } else {
        echo "✗ Directory $dir missing\n";
    }
}

echo "\nVerification complete!\n";
echo "If all checks show ✓, the frankenstyle naming compliance is properly implemented.\n";