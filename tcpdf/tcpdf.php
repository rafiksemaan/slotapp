<?php
//============================================================+
// File name   : tcpdf.php
// Version     : 6.4.1
// Begin       : 2002-08-03
// Last update : 2021-01-12
// Author      : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (http://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2002-2021 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with TCPDF.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
//============================================================+

/**
 * @file
 * This is a simple implementation of TCPDF for the Slot Management System
 * It contains only the basic functionality needed for PDF generation
 */

// TCPDF basic class
class TCPDF {
    // Page format
    protected $page_format;
    // Page orientation (P=portrait, L=landscape)
    protected $cur_orientation;
    // Document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
    protected $unit;
    // Current page number
    protected $page;
    // Current object number
    protected $n;
    // Array of object offsets
    protected $offsets;
    // Buffer holding in-memory PDF
    protected $buffer;
    // Current document state
    protected $state;
    // Compression flag
    protected $compress;
    // Current page orientation
    protected $CurOrientation;
    // Scale factor (number of points in user unit)
    protected $k;
    // Page dimensions
    protected $fwPt;
    protected $fhPt;
    protected $fw;
    protected $fh;
    // Page margins (mm)
    protected $lMargin;
    protected $tMargin;
    protected $rMargin;
    protected $bMargin;
    // Current position (mm)
    protected $x;
    protected $y;
    // Font info
    protected $fonts;
    protected $FontFamily;
    protected $FontStyle;
    protected $FontSizePt;
    protected $FontSize;
    protected $underline;
    protected $CurrentFont;
    // Document info
    protected $title;
    protected $subject;
    protected $author;
    protected $keywords;
    protected $creator;
    // Auto page break
    protected $AutoPageBreak;
    protected $PageBreakTrigger;
    // Color flag
    protected $color_flag;
    // Current text color
    protected $TextColor;
    // Current draw color
    protected $DrawColor;
    // Current fill color
    protected $FillColor;
    // Line width (mm)
    protected $LineWidth;
    // Array of pages
    protected $pages;
    // Current document style
    protected $style;
    // Automatic page breaking
    protected $AutoPageBreak;
    // Page break margin
    protected $bMargin;
    // Print header flag
    protected $print_header;
    // Print footer flag
    protected $print_footer;
    
    /**
     * Class constructor
     * @param $orientation (string) page orientation. Possible values are (case insensitive):<ul><li>P or Portrait (default)</li><li>L or Landscape</li></ul>
     * @param $unit (string) User measure unit. Possible values are:<ul><li>pt: point</li><li>mm: millimeter (default)</li><li>cm: centimeter</li><li>in: inch</li></ul>
     * @param $format (mixed) The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters.
     * @param $unicode (boolean) TRUE means that the input text is unicode (default = true)
     * @param $encoding (string) Charset encoding; default is UTF-8
     * @param $diskcache (boolean) If TRUE reduce the RAM memory usage by caching temporary data on filesystem (slower).
     * @param $pdfa (boolean) If TRUE set the document to PDF/A mode.
     * @public
     */
    public function __construct($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false) {
        // Set internal variables
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->state = 0;
        $this->fonts = array();
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->title = '';
        $this->subject = '';
        $this->author = '';
        $this->keywords = '';
        $this->creator = '';
        
        // Page format
        if (is_string($format)) {
            $this->page_format = $format;
        } else {
            $this->page_format = 'A4';
        }
        
        // Page orientation
        $orientation = strtoupper($orientation);
        if ($orientation == 'P' || $orientation == 'PORTRAIT') {
            $this->CurOrientation = 'P';
            $this->w = 210;
            $this->h = 297;
        } else {
            $this->CurOrientation = 'L';
            $this->w = 297;
            $this->h = 210;
        }
        
        // Scale factor
        $this->k = 1;
        
        // Page margins (mm)
        $this->lMargin = 10;
        $this->tMargin = 10;
        $this->rMargin = 10;
        
        // Interior cell margin (mm)
        $this->cMargin = 0;
        
        // Line width (mm)
        $this->LineWidth = 0.2;
        
        // Automatic page break
        $this->SetAutoPageBreak(true, 15);
        
        // Full width
        $this->SetMargins(10, 10, 10);
        
        // Set default display mode
        $this->SetDisplayMode('default');
        
        // Enable compression
        $this->SetCompression(true);
        
        // Set default PDF version number
        $this->PDFVersion = '1.7';
        
        $this->print_header = false;
        $this->print_footer = false;
    }
    
    /**
     * Set page orientation.
     * @param $orientation (string) page orientation. Possible values are (case insensitive):<ul><li>P or Portrait</li><li>L or Landscape</li></ul>
     * @param $autopagebreak (boolean) Boolean indicating if auto-page-break mode should be on or off.
     * @param $bottommargin (float) bottom margin of the page.
     * @public
     */
    public function setPageOrientation($orientation, $autopagebreak='', $bottommargin='') {
        $orientation = strtoupper($orientation);
        if (($orientation == 'P') || ($orientation == 'PORTRAIT')) {
            $this->CurOrientation = 'P';
            $this->w = 210;
            $this->h = 297;
        } elseif (($orientation == 'L') || ($orientation == 'LANDSCAPE')) {
            $this->CurOrientation = 'L';
            $this->w = 297;
            $this->h = 210;
        } else {
            $this->Error('Incorrect orientation: '.$orientation);
        }
        $this->cur_orientation = $this->CurOrientation;
        if (empty($autopagebreak)) {
            $autopagebreak = $this->AutoPageBreak;
        }
        if (empty($bottommargin)) {
            $bottommargin = $this->bMargin;
        }
        $this->SetAutoPageBreak($autopagebreak, $bottommargin);
    }
    
    /**
     * Enable or disable automatic page breaking mode.
     * @param $auto (boolean) Boolean indicating if auto-page-break mode should be on or off.
     * @param $margin (float) Distance from the bottom of the page.
     * @public
     */
    public function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }
    
    /**
     * Set the PDF document margins.
     * @param $left (float) Left margin.
     * @param $top (float) Top margin.
     * @param $right (float) Right margin.
     * @param $bottom (float) Bottom margin.
     * @public
     */
    public function SetMargins($left, $top, $right=-1) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right == -1) {
            $right = $left;
        }
        $this->rMargin = $right;
    }
    
    /**
     * Set the display mode.
     * @param $zoom (mixed) The zoom factor (int) or 'fullpage', 'fullwidth', 'real', 'default' or a string with comma-separated values.
     * @param $layout (string) The page layout. Possible values are: 'SinglePage', 'OneColumn', 'TwoColumnLeft', 'TwoColumnRight', 'TwoPageLeft', 'TwoPageRight'.
     * @public
     */
    public function SetDisplayMode($zoom, $layout='SinglePage') {
        // Nothing to do, just a stub
    }
    
    /**
     * Enable or disable document compression.
     * @param $compress (boolean) Boolean indicating if compression must be enabled.
     * @public
     */
    public function SetCompression($compress) {
        $this->compress = (boolean) $compress;
    }
    
    /**
     * Set document information.
     * @param $title (string) The title.
     * @param $subject (string) The subject.
     * @param $author (string) The author.
     * @param $keywords (string) The keywords.
     * @param $creator (string) The creator.
     * @public
     */
    public function SetCreator($creator) {
        $this->creator = $creator;
    }
    
    public function SetAuthor($author) {
        $this->author = $author;
    }
    
    public function SetTitle($title) {
        $this->title = $title;
    }
    
    public function SetSubject($subject) {
        $this->subject = $subject;
    }
    
    public function SetKeywords($keywords) {
        $this->keywords = $keywords;
    }
    
    /**
     * Enable or disable header printing.
     * @param $val (boolean) set to true to print the header.
     * @public
     */
    public function setPrintHeader($val) {
        $this->print_header = $val;
    }
    
    /**
     * Enable or disable footer printing.
     * @param $val (boolean) set to true to print the footer.
     * @public
     */
    public function setPrintFooter($val) {
        $this->print_footer = $val;
    }
    
    /**
     * Add a new page to the document.
     * @param $orientation (string) page orientation. Possible values are (case insensitive):<ul><li>P or PORTRAIT (default)</li><li>L or LANDSCAPE</li></ul>
     * @param $format (mixed) The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters.
     * @param $keepmargins (boolean) if true overwrites the default page margins with the current margins
     * @param $tocpage (boolean) if true set the tocpage state to true (the added page will be used to display Table Of Content).
     * @public
     */
    public function AddPage($orientation='', $format='', $keepmargins=false, $tocpage=false) {
        if ($this->state == 0) {
            $this->Open();
        }
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
    }
    
    /**
     * Begin document.
     * @public
     */
    public function Open() {
        $this->state = 1;
    }
    
    /**
     * Terminate document.
     * @param $dest (string) Destination where to send the document. It can be one of the following:<ul><li>I: send the file inline to the browser (default).</li><li>D: send to the browser and force a file download with the name given by name.</li><li>F: save to a local server file with the name given by name.</li><li>S: return the document as a string (name is ignored).</li><li>FI: equivalent to F + I option</li><li>FD: equivalent to F + D option</li><li>E: return the document as base64 mime multi-part email attachment (RFC 2045)</li></ul>
     * @public
     */
    public function Output($name='doc.pdf', $dest='I') {
        // Output PDF to some destination
        if ($this->state < 3) {
            $this->Close();
        }
        
        // Simple implementation for inline display
        if ($dest == 'I') {
            // Send PDF to the standard output
            if (ob_get_contents()) {
                $this->Error('Some data has already been output, can\'t send PDF file');
            }
            if (php_sapi_name() != 'cli') {
                // We send to a browser
                header('Content-Type: application/pdf');
                if (headers_sent()) {
                    $this->Error('Some data has already been output, can\'t send PDF file');
                }
                header('Content-Length: '.strlen($this->buffer));
                header('Content-Disposition: inline; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
            } else {
                echo $this->buffer;
            }
        } else {
            // Just return the buffer for now
            return $this->buffer;
        }
        return '';
    }
    
    /**
     * Close document.
     * @public
     */
    public function Close() {
        if ($this->state == 3) {
            return;
        }
        if ($this->page == 0) {
            $this->AddPage();
        }
        $this->state = 3;
        
        // For now, just generate a simple PDF
        $this->buffer = "%PDF-1.7\n";
        $this->buffer .= "1 0 obj\n";
        $this->buffer .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $this->buffer .= "endobj\n";
        $this->buffer .= "2 0 obj\n";
        $this->buffer .= "<< /Type /Pages /Kids [";
        for ($i=1; $i <= $this->page; $i++) {
            $this->buffer .= (2 + $i)." 0 R ";
        }
        $this->buffer .= "] /Count ".$this->page." >>\n";
        $this->buffer .= "endobj\n";
        
        // Pages
        for ($i=1; $i <= $this->page; $i++) {
            $this->buffer .= (2 + $i)." 0 obj\n";
            $this->buffer .= "<< /Type /Page /Parent 2 0 R /Contents ".($this->n + $i)." 0 R >>\n";
            $this->buffer .= "endobj\n";
        }
        
        // Page content
        for ($i=1; $i <= $this->page; $i++) {
            $this->buffer .= ($this->n + $i)." 0 obj\n";
            $this->buffer .= "<< /Length ".strlen($this->pages[$i])." >>\n";
            $this->buffer .= "stream\n".$this->pages[$i]."\nendstream\n";
            $this->buffer .= "endobj\n";
        }
        
        // EOF
        $this->buffer .= "xref\n";
        $this->buffer .= "0 ".($this->n + $this->page * 2 + 1)."\n";
        $this->buffer .= "0000000000 65535 f \n";
        $this->buffer .= "trailer\n";
        $this->buffer .= "<< /Size ".($this->n + $this->page * 2 + 1)." /Root 1 0 R >>\n";
        $this->buffer .= "startxref\n";
        $this->buffer .= strlen($this->buffer)."\n";
        $this->buffer .= "%%EOF";
    }
    
    /**
     * Set font.
     * @param $family (string) Family font. It can be either a name defined by AddFont() or one of the standard families. It is also possible to pass an empty string, in that case, the current family is kept.
     * @param $style (string) Font style. Possible values are (case insensitive):<ul><li>empty string: regular</li><li>B: bold</li><li>I: italic</li><li>U: underline</li><li>D: line-through</li><li>O: overline</li></ul> or any combination. The default value is regular.
     * @param $size (float) Font size in points. The default value is the current size.
     * @param $fontfile (string) The font definition file. By default, the name is built from the family and style, in lower case with no spaces.
     * @param $subset (mixed) if true embedd only a subset of the font (stores only the information related to the used characters); if false embedd full font; if 'default' uses the default value set using setFontSubsetting(). This option is valid only for TrueTypeUnicode fonts. If you want to enable users to change the document, set this parameter to false. If you subset the font, the person who receives your PDF would need to have your same font in order to make changes to your PDF. The file size of the PDF would also be smaller because you are embedding only a subset.
     * @public
     */
    public function SetFont($family, $style='', $size=0) {
        // Just store the font info
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        if ($size > 0) {
            $this->FontSizePt = $size;
            $this->FontSize = $size / $this->k;
        }
        
        // Add content to current page
        $this->pages[$this->page] .= "BT /F1 ".$this->FontSizePt." Tf ET\n";
    }
    
    /**
     * Set text color.
     * @param $r (int) Red color value
     * @param $g (int) Green color value
     * @param $b (int) Blue color value
     * @public
     */
    public function SetTextColor($r, $g=-1, $b=-1) {
        // Store text color
        if (($r==0 and $g==0 and $b==0) or $g==-1) {
            $this->TextColor = sprintf('%.3F g', $r/255);
        } else {
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        }
        $this->color_flag = ($this->FillColor != $this->TextColor);
        
        // Add content to current page
        $this->pages[$this->page] .= $this->TextColor."\n";
    }
    
    /**
     * Set fill color.
     * @param $r (int) Red color value
     * @param $g (int) Green color value
     * @param $b (int) Blue color value
     * @public
     */
    public function SetFillColor($r, $g=-1, $b=-1) {
        // Store fill color
        if (($r==0 and $g==0 and $b==0) or $g==-1) {
            $this->FillColor = sprintf('%.3F g', $r/255);
        } else {
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        }
        $this->color_flag = ($this->FillColor != $this->TextColor);
        
        // Add content to current page
        $this->pages[$this->page] .= $this->FillColor."\n";
    }
    
    /**
     * Set line width.
     * @param $width (float) Line width.
     * @public
     */
    public function SetLineWidth($width) {
        $this->LineWidth = $width;
        
        // Add content to current page
        $this->pages[$this->page] .= sprintf('%.2F w', $width*$this->k)."\n";
    }
    
    /**
     * Output a cell.
     * @param $w (float) Cell width. If 0, the cell extends up to the right margin.
     * @param $h (float) Cell height. Default value: 0.
     * @param $txt (string) String to print. Default value: empty string.
     * @param $border (mixed) Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)))
     * @param $ln (int) Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right (or left for RTL languages)</li><li>1: to the beginning of the next line</li><li>2: below</li></ul> Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param $align (string) Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param $fill (boolean) Indicates if the cell background must be painted (true) or transparent (false).
     * @param $link (mixed) URL or identifier returned by AddLink().
     * @param $stretch (int) font stretch mode: <ul><li>0 = normal</li><li>1 = horizontal scaling only if text is larger than cell width</li><li>2 = forced horizontal scaling to fit cell width</li><li>3 = character spacing only if text is larger than cell width</li><li>4 = forced character spacing to fit cell width</li></ul> General font stretching and scaling values will be preserved when possible.
     * @param $ignore_min_height (boolean) if true ignore automatic minimum height value.
     * @param $calign (string) cell vertical alignment relative to the specified Y value. Possible values are:<ul><li>T : cell top</li><li>C : center</li><li>B : cell bottom</li><li>A : font top</li><li>L : font baseline</li><li>D : font bottom</li></ul>
     * @param $valign (string) text vertical alignment inside the cell. Possible values are:<ul><li>T : top</li><li>C : center</li><li>B : bottom</li></ul>
     * @public
     */
    public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M') {
        // Add text to current page
        $this->pages[$this->page] .= "BT\n";
        $this->pages[$this->page] .= sprintf('%.2F %.2F Td', $this->x, $this->h - $this->y - $this->FontSize)."\n";
        $this->pages[$this->page] .= "(".$txt.") Tj\n";
        $this->pages[$this->page] .= "ET\n";
        
        // Update position
        if ($ln == 1) {
            // Go to the beginning of the next line
            $this->x = $this->lMargin;
            $this->y += $h;
        } elseif ($ln == 0) {
            // Go to the right
            $this->x += $w;
        } elseif ($ln == 2) {
            // Go below
            $this->y += $h;
        }
    }
    
    /**
     * Line feed and carriage return.
     * @param $h (float) The height of the break. By default, the value equals the height of the last printed cell.
     * @param $cell (boolean) if true add a cMargin to the x coordinate
     * @public
     */
    public function Ln($h='', $cell=false) {
        // Line feed; default value is the last cell height
        if ($h == '') {
            $h = $this->FontSize * 1.25;
        }
        $this->x = $this->lMargin;
        $this->y += $h;
    }
    
    /**
     * Simplified error handling function
     */
    protected function Error($msg) {
        // Fatal error
        throw new Exception('TCPDF ERROR: '.$msg);
    }
}
?>