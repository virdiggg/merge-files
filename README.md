# Merge Files

A lightweight PHP document merging library designed for practical backend workflows and legacy PDF compatibility handling.

This library allows multiple document types—including Word, Excel, images, and existing PDFs—to be consolidated into a single PDF output through a unified processing pipeline.

Unlike many PDF merging utilities that fail on newer PDF structures, this library automatically normalizes incompatible PDF versions using Ghostscript preprocessing to maintain compatibility with legacy PHP PDF ecosystems such as FPDF, FPDI, and mPDF.

---

<img src="https://img.shields.io/packagist/php-v/virdiggg/merge-files" /><img src="https://img.shields.io/badge/codeigniter--version-3-green" /><img src="https://img.shields.io/github/license/virdiggg/merge-files" />

---

## Features

- Merge multiple file formats into a single PDF
- Automatic PDF compatibility normalization using Ghostscript
- Handles mixed document ingestion workflows
- Supports PDF metadata configuration
- Optional PDF permission restrictions
- Lightweight and framework-friendly
- Designed for internal systems and document automation pipelines

---

## Supported File Types

- DOC
- DOCX
- XLS
- XLSX
- JPG
- JPEG
- PNG
- PDF

---

## Why Ghostscript Is Required

Many PHP PDF libraries such as FPDI, FPDF, and mPDF have limitations when importing or merging PDFs generated with newer PDF specifications (commonly PDF 1.5+).

This library automatically preprocesses incompatible PDFs through Ghostscript to:

- Normalize PDF versions
- Improve merge compatibility
- Prevent parser/import failures
- Avoid dependency on commercial PDF parser addons

Ghostscript is therefore a mandatory dependency.

---

## Current Limitations

### Embedded Images Inside DOC/DOCX

Images embedded inside `.doc` or `.docx` files are currently not supported during conversion.

### Maintenance Status

This project is currently in maintenance mode.  
No major feature updates are planned at the moment.

### Required PHP Extension

The `mbstring` extension is required.

---

# Installation

Install the package via Composer:

```bash
composer require virdiggg/merge-files
```

---

# Ghostscript Installation

Install Ghostscript and ensure the `gs` command is globally accessible from your terminal or command prompt.

Official installation guide:

https://ghostscript.readthedocs.io/en/latest/Install.html

Verify installation:

```bash
gs
```

Expected output:

```bash
GPL Ghostscript 10.xx.x
GS>
```

---

# Basic Usage

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

    $mf->setTitle('Merged PDF');
    $mf->setSubject('Merged PDF');

    // Optional
    // $mf->setKeywords(['pdf', 'word', 'excel', 'image']);

    // Required if permissions are used
    // $mf->setPassword('password');

    // Optional permissions
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
```

---

# PDF Permissions

You may configure PDF permissions using the following options.

Use an empty array `[]` if you do not want to restrict permissions.

Available permissions:

- `copy`
- `print`
- `modify`
- `annot-forms`
- `fill-forms`
- `extract`
- `assemble`
- `print-highres`

Example:

```php
$mf->setPermissions([
    'copy',
    'print'
]);
```

---

# Recommended Use Cases

This library is suitable for:

- Internal document automation systems
- Batch PDF consolidation workflows
- Compliance document generation
- Office document archival pipelines
- Backend administrative systems
- Legacy PHP applications requiring PDF normalization

---

# Notes

This project prioritizes compatibility and operational reliability over minimal external dependencies.

If your workflow involves handling PDFs from unpredictable external sources, Ghostscript preprocessing significantly improves merge stability and interoperability across different PDF generators.
