<?php

namespace Virdiggg\MergeFiles;

use Virdiggg\MergeFiles\Helpers\FileHelper as Fl;
use Virdiggg\MergeFiles\Helpers\StrHelper as Str;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Element\{TextRun, Image, Text, TextBreak};
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use Mpdf\Mpdf;

defined('APPPATH') or define('APPPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);

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
     * @throws Exception
     *
     * @return string Output path
     */
    public function mergeToPDF($files = [])
    {
        if (count((array) $files) < 1) {
            throw new \Exception('No files to merge.');
        }

        $this->fl->folderPermission($this->outputPath);

        $pdf = new Mpdf(['tempDir' => $this->outputPath]);
        $this->configurePDF($pdf);

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
                    $filepdf = fopen($file, "r");
                    if ($filepdf) {
                        // Try to read the first line of the file
                        $firstLine = fgets($filepdf);
                        fclose($filepdf);

                        preg_match_all('!\d+!', $firstLine, $matches);
                        $pdfversion = implode('.', $matches[0]);

                        // Check if the PDF version is greater than 1.4.
                        // If so, use Ghostscript to convert it to a compatible version.
                        // PDF version 1.4 is the maximum version supported by mPDF.
                        if ($pdfversion > "1.4") {
                            try {
                                // Check if Ghostscript is installed.
                                shell_exec("gs --version");
                            } catch (\Exception $e) {
                                throw new \Exception("Ghostscript is not installed. Please install Ghostscript to merge PDF files with version greater than 1.4.");
                            }

                            $oldFileName = basename($file);
                            $newFile = $this->str->before($file, $oldFileName) . time() .'_v14.pdf';

                            // Convert the PDF to version 1.4
                            shell_exec('gs -dBATCH -dNOPAUSE -dCompatibilityLevel=1.4 -q -sDEVICE=pdfwrite -sOutputFile="' . $newFile . '" "' . $file . '"');

                            unset($oldFileName, $firstLine, $matches, $pdfversion);

                            // Use the new file
                            $file = $newFile;
                        }

                        $this->PDFToPDF($pdf, $file);

                        // Remove the temporary file
                        @unlink($newFile);
                    } else {
                        throw new \Exception("Error reading file: $file");
                    }
                    break;
                default:
                    throw new \Exception("Unsupported file type: $ext");
            }
        }

        // Set PDF password (if provided)
        if (!empty($this->password)) {
            $pdf->SetProtection([], $this->password);
        }

        // Save the merged PDF
        $outputFilePath = $this->getOutputFullPath();
        $pdf->Output($outputFilePath, \Mpdf\Output\Destination::FILE);

        return $outputFilePath;
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
            // Determine the page orientation
            $orientation = 'P';
            $properties = $section->getStyle();
            if ($properties->getOrientation() === 'landscape') {
                $orientation = 'L';
            }
            $pdf->AddPage($orientation);

            foreach ($section->getElements() as $element) {
                if ($element instanceof TextRun) {
                    $lineContent = ''; // Accumulate text with inline spacing
                    $paragraphStyle = $element->getParagraphStyle();
                    $alignment = $paragraphStyle ? ($paragraphStyle->getAlignment() ?: 'justify') : 'justify';

                    // Map alignment styles to mPDF's alignment options
                    switch ($alignment) {
                        case 'center':
                            $pdfAlignment = 'C'; // Center
                            break;
                        case 'right':
                            $pdfAlignment = 'R'; // Right
                            break;
                        case 'justify':
                            $pdfAlignment = 'J'; // Justify
                            break;
                        default:
                            $pdfAlignment = 'L'; // Left
                            break;
                    }

                    foreach ($element->getElements() as $textElement) {
                        if ($textElement instanceof Text) {
                            // Retrieve font style
                            $fontStyle = $textElement->getFontStyle();
                            $fontName = $fontStyle ? ($fontStyle->getName() ?: 'timesnewroman') : 'timesnewroman';
                            $fontSize = $fontStyle ? $fontStyle->getSize() : 12;
                            $fontWeight = ($fontStyle && $fontStyle->isBold()) ? 'B' : '';
                            $fontItalic = ($fontStyle && $fontStyle->isItalic()) ? 'I' : '';

                            // Set font dynamically before adding text
                            $pdf->SetFont($fontName, $fontWeight . $fontItalic, $fontSize);

                            // Add text with a space
                            $lineContent .= $textElement->getText() . ' ';
                        } elseif ($textElement instanceof TextBreak) {
                            // Write accumulated line and reset
                            $pdf->MultiCell(0, 5, $lineContent, 0, $pdfAlignment);
                            $lineContent = ''; // Reset line content after a line break
                        }
                    }

                    // Write any remaining content
                    if (!empty($lineContent)) {
                        $pdf->MultiCell(0, 5, $lineContent, 0, $pdfAlignment);
                        $pdf->Ln(); // End the TextRun with a line break
                    }
                }

                // Image
                if ($element instanceof Image) {
                    $imagePath = $element->getSource();

                    // Check if image path is valid and accessible
                    if (file_exists($imagePath)) {
                        // Convert EMU (Word units) to pixels (1 EMU = 1/9525 inch)
                        $width = $element->getStyle()->getWidth() ? $element->getStyle()->getWidth() / 9525 : 40;
                        $height = $element->getStyle()->getHeight() ? $element->getStyle()->getHeight() / 9525 : 40;

                        // Adjust units to match mPDF's pixel scaling (0.75 factor for DPI adjustment)
                        $width = $width * 0.75;
                        $height = $height * 0.75;

                        // Add the image to the PDF
                        $pdf->Image($imagePath, $pdf->GetX(), $pdf->GetY(), $width, $height);
                        $pdf->Ln($height + 5); // Adjust cursor position
                    } else {
                        // Log or print error if image path is not valid
                        throw new \Exception("Image path not found: " . $imagePath);
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

        $html = '<table border="1" cellpadding="4" cellspacing="0">';
        foreach ($sheet->toArray() as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($this->str->clean($cell ?: '')) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        // Determine orientation based on sheet dimensions
        $columnCount = count($sheet->getColumnDimensions());
        $rowCount = count($sheet->toArray());
        $orientation = ($columnCount > $rowCount) ? 'L' : 'P';

        $pdf->AddPage($orientation);
        $pdf->WriteHTML($html);
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
        // Get image dimensions
        list($width, $height) = getimagesize($file);

        // Check if the image is landscape or portrait
        $orientation = ($width > $height) ? 'L' : 'P';

        // Add the page and use the image with the new dimensions
        $pdf->AddPage($orientation);

        // Get the page size
        $pageWidth = $pdf->w;
        $pageHeight = $pdf->h;

        // Calculate aspect ratio
        $aspectRatio = $width / $height;

        // Calculate the new dimensions to fit the image to the page
        if ($pageWidth / $pageHeight > $aspectRatio) {
            // If the page is more square-like or taller, fit the image height to the page height
            $newHeight = $pageHeight;
            $newWidth = $pageHeight * $aspectRatio;
        } else {
            // If the page is wider, fit the image width to the page width
            $newWidth = $pageWidth;
            $newHeight = $pageWidth / $aspectRatio;
        }

        // Calculate X and Y to center the image on the page
        $x = ($pageWidth - $newWidth) / 2;  // Center horizontally
        $y = ($pageHeight - $newHeight) / 2; // Center vertically

        $pdf->Image($file, $x, $y, $newWidth, $newHeight, '', '', true, false);
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
        $pageCount = $pdf->setSourceFile($file);
        for ($i = 1; $i <= $pageCount; $i++) {
            $templateId = $pdf->importPage($i);
            $templateSize = $pdf->getTemplateSize($templateId);

            $widthMm = $templateSize['width'];
            $heightMm = $templateSize['height'];

            // Determine orientation based on template dimensions
            if ($widthMm > $heightMm) {
                // Swap width and height if landscape
                $widthMm = $widthMm + $heightMm;
                $heightMm = $widthMm - $heightMm;
                $widthMm = $widthMm - $heightMm;

                $orientation = 'L';
            } else {
                $orientation = 'P';
            }

            $pdf->AddPageByArray([
                'orientation' => $orientation,
                'newformat' => [$widthMm, $heightMm],
            ]);
            $pdf->useTemplate($templateId);
        }
    }

    /**
     * Set PDF Metadata
     *
     * @param object $pdf
     *
     * @return void
     */
    private function configurePDF($pdf)
    {
        // Metadata
        $pdf->SetTitle($this->title);
        $pdf->SetAuthor($this->author);
        $pdf->SetCreator($this->creator);
        $pdf->SetSubject($this->subject);
        $pdf->SetKeywords(join(', ', $this->keywords));

        $pdf->fontdata['timesnewroman'] = [
            'R' => __DIR__ . '/fonts/times.ttf',   // Regular
            'B' => __DIR__ . '/fonts/timesb.ttf',  // Bold
            'I' => __DIR__ . '/fonts/timesi.ttf',  // Italic
            'BI' => __DIR__ . '/fonts/timesbi.ttf', // Bold Italic
        ];

        // Page settings
        $pdf->autoPageBreak = true;

        // Font settings
        // $pdf->SetFont('times', '', 12);
        $pdf->SetDefaultFont('timesnewroman');
        $pdf->defaultheaderfontsize = 10;
        $pdf->defaultheaderfontstyle = 'B';
        $pdf->defaultfooterfontsize = 8;
        $pdf->defaultfooterfontstyle = 'I';
        $pdf->SetDefaultFont('courier');
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
