<?php

$root = dirname(__DIR__);
$bytes = file_get_contents($root . '/languages/aliyun-oss-zh_CN.mo');
$header = unpack('Vmagic/Vrevision/Vcount/Voriginals/Vtranslations', substr($bytes, 0, 20));
if ($header['magic'] !== 0x950412de) {
    throw new RuntimeException('Unsupported MO file format');
}
$translations = [];
for ($index = 0; $index < $header['count']; $index++) {
    $original = unpack('Vlength/Voffset', substr($bytes, $header['originals'] + $index * 8, 8));
    $translated = unpack('Vlength/Voffset', substr($bytes, $header['translations'] + $index * 8, 8));
    $translations[substr($bytes, $original['offset'], $original['length'])] =
        substr($bytes, $translated['offset'], $translated['length']);
}
$missing = [];
$sources = [];
foreach (array_merge(glob($root . '/src/*.php'), glob($root . '/view/*.php')) as $file) {
    $tokens = token_get_all(file_get_contents($file));
    foreach ($tokens as $index => $token) {
        if (!is_array($token) || $token[0] !== T_STRING || $token[1] !== '__') {
            continue;
        }
        $index++;
        while (isset($tokens[$index]) && is_array($tokens[$index]) && $tokens[$index][0] === T_WHITESPACE) {
            $index++;
        }
        if (($tokens[$index++] ?? null) !== '(') {
            continue;
        }
        while (isset($tokens[$index]) && is_array($tokens[$index]) && $tokens[$index][0] === T_WHITESPACE) {
            $index++;
        }
        $literal = $tokens[$index] ?? null;
        if (!is_array($literal) || $literal[0] !== T_CONSTANT_ENCAPSED_STRING) {
            throw new RuntimeException('Non-literal translation source in ' . $file);
        }
        $text = substr($literal[1], 1, -1);
        $text = $literal[1][0] === "'" ? str_replace(["\\\\", "\\'"], ["\\", "'"], $text) : stripcslashes($text);
        $sources[$text] = true;
        if (!isset($translations[$text]) || $translations[$text] === '') {
            $missing[$text] = true;
        }
    }
}
if ($missing) {
    throw new RuntimeException("Missing compiled Chinese translations:\n" . implode("\n", array_keys($missing)));
}
echo 'PASS: ' . count($sources) . " UI translation sources have non-empty compiled Chinese translations.\n";
