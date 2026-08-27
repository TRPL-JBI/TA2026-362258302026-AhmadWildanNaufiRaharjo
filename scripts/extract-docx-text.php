<?php

$path = $argv[1] ?? '';
if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "Usage: php extract-docx-text.php <path>\n");
    exit(1);
}

$zip = new ZipArchive();
$zip->open($path);
$xml = $zip->getFromName('word/document.xml');
$zip->close();

$text = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml);
$text = preg_replace('/<w:br[^>]*\/>/', "\n", $text);
$text = strip_tags($text);
$text = html_entity_decode($text);
$text = preg_replace('/[ \t]+/', ' ', $text);
$text = preg_replace('/\n\s*\n+/', "\n\n", $text);

echo trim($text);
