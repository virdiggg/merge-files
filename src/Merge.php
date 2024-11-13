<?php

namespace Virdiggg\MergeFiles;

use Virdiggg\MergeFiles\Helpers\FileHelper as Fl;
use Virdiggg\MergeFiles\Helpers\StrHelper as Str;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Element\{TextRun, Image, Text, TextBreak};
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use setasign\Fpdi\Tcpdf\Fpdi as TC;

defined('APPPATH') or define('APPPATH', '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

class Merge
{
    /**
     * Allowed extensions, you can call it from your controller
     * 
     * @param array
     */
    public $allowedExt = ['doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'pdf'];

    /**
     * PDF Creator
     * 
     * @param string
     */
    private $creator;
    const CREATOR = 'Administrator';

    /**
     * PDF Author
     * 
     * @param string
     */
    private $author;
    const AUTHOR = 'Automatic System ' . self::CREATOR;

    /**
     * PDF Keywords
     * 
     * @param array
     */
    private $keywords = [];
    const PDF_KEYWORDS = ['Document', 'Information', 'PDF'];

    /**
     * PDF Title
     * 
     * @param string
     */
    private $title;

    /**
     * PDF Subject
     * 
     * @param string
     */
    private $subject;

    /**
     * PDF Output Name
     * 
     * @param string
     */
    private $outputName;
    const OUTPUT_NAME = 'merged.pdf';

    /**
     * PDF Output Path
     * 
     * @param string
     */
    private $outputPath;
    // ROOT/application/
    const APP_PATH = APPPATH;
    // ROOT/application/files
    const FILES_PATH = self::APP_PATH . 'files';

    /**
     * PDF Password
     * 
     * @param string
     */
    private $password = '';

    /**
     * Helpers
     *
     * @param object
     */
    private $str; // String
    private $fl; // File

    public function __construct()
    {
        $this->creator = self::CREATOR;
        $this->author = self::AUTHOR;
        $this->outputName = self::OUTPUT_NAME;
        $this->keywords = self::PDF_KEYWORDS;
        $this->outputPath = self::FILES_PATH;

        $this->str = new Str();
        $this->fl = new Fl();
    }

    /**
     * Merge files to a single PDF
     * 
     * @param array $files
     * 
     * @return string Output path
     */
    public function mergeToPDF($files)
    {
        $pdf = new TC();
        // PDF information
        $pdf->SetCreator($this->creator);
        $pdf->SetAuthor($this->author);
        $pdf->SetTitle($this->title);
        $pdf->SetSubject($this->subject);
        $pdf->SetKeywords(join(', ', $this->keywords));

        // Auto page break
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Font
        $pdf->SetFont('times', '', 12);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Generate pages from files
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            switch ($ext) {
                case 'doc':
                case 'docx':
                    $this->wordToPDF($pdf, $file);
                    break;

                case 'xls':
                case 'xlsx':
                    $this->excelToPDF($pdf, $file);
                    break;

                case 'jpg':
                case 'jpeg':
                case 'png':
                    $this->imageToPDF($pdf, $file);
                    break;

                case 'pdf':
                    $this->PDFToPDF($pdf, $file);
                    break;

                default:
                    throw new \Exception("Unsupported file type: $ext");
            }
        }

        $this->fl->folderPermission($this->outputPath);

        // Output the merged PDF
        $output = $this->getOutputFullPath();
        $pdf->SetProtection(
            ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'assemble', 'print-high'],
            $this->password,
            $this->password,
            0,
            null
        );
        $pdf->Output($output, 'F');

        return $output;
    }

    /**
     * Add word to PDF
     * 
     * @param object $pdf
     * @param string $file Path file
     * 
     * @return void
     */
    private function wordToPDF($pdf, $file)
    {
        $phpWord = WordIOFactory::load($file);

        foreach ($phpWord->getSections() as $section) {
            $orientation = 'P';
            $properties = $section->getStyle();
            if ($properties->getOrientation() == 'landscape') {
                $orientation = 'L';
            }
            $pdf->AddPage($orientation);

            foreach ($section->getElements() as $element) {
                if ($element instanceof TextRun) {
                    foreach ($element->getElements() as $textElement) {
                        if ($textElement instanceof Text) {
                            $fontStyle = $textElement->getFontStyle();
                            $pdf->SetFont(
                                $fontStyle->getName(),
                                $fontStyle->isBold() ? 'B' : '',
                                $fontStyle->getSize()
                            );
                            $pdf->Write(5, $textElement->getText());
                        } elseif ($textElement instanceof TextBreak) {
                            $pdf->Ln();
                        }
                    }
                    $pdf->Ln();
                }

                // **Updated Image Handling**
                if ($element instanceof Image) {
                    $imagePath = $element->getSource();

                    // Check if image path is valid and accessible
                    if (file_exists($imagePath)) {
                        $width = $element->getStyle()->getWidth() ? $element->getStyle()->getWidth() / 9525 : 40;
                        $height = $element->getStyle()->getHeight() ? $element->getStyle()->getHeight() / 9525 : 40;

                        // Adjust units to pixels
                        $width = $width * 0.75;
                        $height = $height * 0.75;

                        $pdf->Image($imagePath, $pdf->GetX(), $pdf->GetY(), $width, $height);
                        $pdf->Ln($height + 5);
                    } else {
                        // Log or print error if image path is not valid
                        error_log("Image path not found: " . $imagePath, 0, __DIR__);
                    }
                }
            }
        }
    }

    /**
     * Add excel to PDF
     * 
     * @param object $pdf
     * @param string $file Path file
     * 
     * @return void
     */
    private function excelToPDF($pdf, $file)
    {
        $spreadsheet = SpreadsheetIOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $pdf->AddPage();

        $html = '<table border="1" cellpadding="4" cellspacing="0">';

        foreach ($sheet->toArray() as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
    }

    /**
     * Add image to PDF
     * 
     * @param object $pdf
     * @param string $file Path file
     * 
     * @return void
     */
    private function imageToPDF($pdf, $file)
    {
        $bMargin = $pdf->getBreakMargin();
        $auto_page_break = $pdf->getAutoPageBreak();
        $pdf->SetAutoPageBreak(false, 0);

        list($width, $height) = getimagesize($file);
        $orientation = $width > $height ? 'L' : 'P';

        // Set page orientation based on section style
        $pdf->AddPage($orientation);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        $pdf->Image($file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);

        $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
        $pdf->setPageMark();
    }

    /**
     * Add PDF to PDF
     * 
     * @param object $pdf
     * @param string $file Path file
     * 
     * @return void
     */
    private function PDFToPDF($pdf, $file)
    {
        $pages = $pdf->setSourceFile($file);
        for ($i = 1; $i <= $pages; $i++) {
            $tplIdx = $pdf->importPage($i);
            $template = $pdf->getTemplateSize($tplIdx);

            // Set page orientation based on section style
            $pdf->AddPage($template['orientation']);
            $pdf->useTemplate($tplIdx);
        }
    }

    /**
     * Parse output file name
     * 
     * @return string
     */
    private function getOutputFullPath()
    {
        return $this->outputPath . str_replace('.pdf', '', $this->outputName) . '_' . time() . '.pdf';
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setAuthor($text = self::AUTHOR)
    {
        $this->author = $this->str->clean($text);
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setCreator($text = self::CREATOR)
    {
        $this->creator = $this->str->clean($text);
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setOutputPath($text = self::FILES_PATH)
    {
        $this->outputPath = $this->str->clean($text);
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setOutputName($text = self::OUTPUT_NAME)
    {
        $this->outputName = $this->str->clean($text);
    }

    /**
     * Set PDF properties
     * 
     * @param array $keywords
     * 
     * @return void
     */
    public function setKeywords($keywords = self::PDF_KEYWORDS)
    {
        $this->keywords = array_values(array_unique(array_merge($this->keywords, (array) $keywords)));
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setTitle($text)
    {
        $this->title = $this->str->clean($text);
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setSubject($text)
    {
        $this->subject = $this->str->clean($text);
    }

    /**
     * Set PDF properties
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setPassword($text)
    {
        $this->password = $this->str->clean($text);
    }
}
