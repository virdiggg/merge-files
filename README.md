# A Simple File Merging Library

<img src="https://img.shields.io/packagist/php-v/virdiggg/merge-files" /> <img src="https://img.shields.io/badge/codeigniter--version-3-green" /> <img src="https://img.shields.io/github/license/virdiggg/merge-files" />

## Not support image inside docx/doc
## I don't plan to update this library in the meantime

### HOW TO USE
- Install this library with composer
```bash
composer require virdiggg/merge-files
```
- Install [Ghostscript](https://ghostscript.readthedocs.io/en/gs10.04.0/Install.html) and make sure it can be called in command promp with `gs`. You will get something like this when you type `gs`.
```sh
$ gs
$ GPL Ghostscript 10.04.0 (2024-09-18)
$ GS >
```
- Create function to call this library
```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Virdiggg\MergeFiles\Merge;

try {
    $mf = new Merge();
    $mf->setAuthor('Me');
    $mf->setCreator('Also Me');
    $mf->setOutputName('mergedpdf.pdf');
    $mf->setOutputPath(__DIR__ . '/output/');
    // $mf->setKeywords(['pdf', 'word', 'excel', 'image']);
    $mf->setTitle('Merged PDF'); // Mandatory
    $mf->setSubject('Merged PDF'); // Mandatory
    // $mf->setPassword('password'); // Mandatory if Permission is set
    // $mf->setPermissions(['copy']); // Optional, must be an array

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
```

## Permission List
You can use any of the options listed below. Use `[]` (an empty array) if you do not want to set any permissions.
- `copy`
- `print`
- `modify`
- `annot-forms`
- `fill-forms`
- `extract`
- `assemble`
- `print-highres`