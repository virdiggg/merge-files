<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Virdiggg\MergeFiles\Merge;

try {
    $mf = new Merge();
    $mf->setAuthor('Me');
    $mf->setCreator('Also Me');
    $mf->setOutputName('mergedpdf.pdf');
    $mf->setOutputPath(__DIR__ . '/output/');
    $mf->setKeywords(['pdf', 'word', 'excel', 'image']);
    $mf->setTitle('Merged PDF');
    $mf->setSubject('Merged PDF');
    // $mf->setPassword('password');
    // ['copy', 'print', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-highres'] or [] empty array
    // $mf->setPermissions(['copy']);

    $files = [
        __DIR__.'/input/new_pdf.pdf',
        __DIR__.'/input/Book1.xlsx',
        __DIR__.'/input/download.pdf',
        __DIR__.'/input/word.docx',
        __DIR__.'/input/Wikipedia-logo-v2.png'
    ];
    $mf->mergeToPDF($files);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}