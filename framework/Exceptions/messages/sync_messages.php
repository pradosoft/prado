#!/usr/bin/env php
<?php

/**
 * PRADO Message Synchronization CLI
 * Usage: php sync_messages.php <translation-file> [output-file] --master=[master-file] [-d|--deprecated]
 *
 * This script will take the master 'messages.txt' and ensure the other language files
 * have the same keys.  The text is not translated, only passed through from English.
 */

// 1. Manual Flag Detection (Order Independent)
$keepDeprecated = false;
$masterPath = 'messages.txt';
$positionalArgs = [];

// Loop through all arguments starting after the script name
for ($i = 1; $i < $argc; $i++) {
	$arg = $argv[$i];

	if ($arg === '-d' || $arg === '--deprecated') {
		$keepDeprecated = true;
	} elseif (strpos($arg, '--master=') === 0) {
		$masterPath = substr($arg, 9);
	} elseif ($arg === '--master' && isset($argv[$i + 1])) {
		$masterPath = $argv[++$i]; // Capture next arg as value
	} else {
		// Anything not starting with - is a positional argument (paths)
		if ($arg[0] !== '-') {
			$positionalArgs[] = $arg;
		}
	}
}

$translationPath = $positionalArgs[0] ?? null;

if (!$translationPath) {
	die("Usage: php sync_messages.php <translation-file> [output-file] --master=[master-file] [-d]\n");
}

$defaultOutput = pathinfo($translationPath, PATHINFO_FILENAME) . '.synced.txt';
$outputPath = $positionalArgs[1] ?? $defaultOutput;

if (!file_exists($masterPath) || !file_exists($translationPath)) {
	die("Error: Source files not found.\n" . "Master: $masterPath\n" . "Translation: $translationPath\n");
}

function isComment($line)
{
	$trimmed = trim($line);
	return $trimmed !== '' && ($trimmed[0] === '#' || $trimmed[0] === ';');
}

$masterLines = file($masterPath);
$translationLines = file($translationPath);

// Map for quick lookup of translated messages
$translations = [];
foreach ($translationLines as $line) {
	if (strpos($line, '=') !== false && !isComment($line)) {
		[$key, $val] = explode('=', $line, 2);
		$translations[trim($key)] = ltrim($val);
	}
}

$finalOutput = "";
$masterKeysProcessed = [];

foreach ($masterLines as $line) {
	$trimmedLine = trim($line);

	if ($trimmedLine === '') {
		$finalOutput .= $line;
		continue;
	}

	if (isComment($line)) {
		$found = false;
		foreach ($translationLines as $tLine) {
			if (trim($tLine) === $trimmedLine) {
				$finalOutput .= $tLine;
				$found = true;
				break;
			}
		}
		if (!$found) {
			$finalOutput .= $line;
		}
		continue;
	}

	if (preg_match('/^([^=]+)(=[\s]*)(.*)$/', $line, $matches)) {
		$fullKeyPart = $matches[1];
		$trimmedKey = trim($fullKeyPart);
		$separator = $matches[2];
		$masterKeysProcessed[] = $trimmedKey;

		if (array_key_exists($trimmedKey, $translations)) {
			$val = $translations[$trimmedKey];
			$finalOutput .= $fullKeyPart . $separator . trim($val) . "\n";
		} else {
			$finalOutput .= $line;
		}
	} else {
		$finalOutput .= $line;
	}
}

// Identify Deprecated Keys
$deprecatedKeys = array_diff(array_keys($translations), $masterKeysProcessed);

// UI Reporting (Always happens)
if (!empty($deprecatedKeys)) {
	echo "\n  Found " . count($deprecatedKeys) . " deprecated key(s) in translation:\n";
	foreach ($deprecatedKeys as $dk) {
		echo "    - $dk\n";
	}
} else {
	echo "\n  No deprecated keys found.\n";
}

// Append to file output if $keepDeprecated is true
if ($keepDeprecated && !empty($deprecatedKeys)) {
	echo "\n  Appending deprecated keys to file...\n";
	$finalOutput .= "\n\n# --- DEPRECATED MESSAGES ---\n";
	foreach ($deprecatedKeys as $dk) {
		$finalOutput .= "# [DEPRECATED] " . $dk . " = " . trim($translations[$dk]) . "\n";
	}
}

if (file_put_contents($outputPath, $finalOutput) !== false) {
	echo "\n  Sync successful: $outputPath\n\n";
}
