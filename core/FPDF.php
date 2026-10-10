<?php
/**
 * Lightweight FPDF PDF Generation Engine
 * ApexFinance - PDF Receipt & Report Generator
 */

if (!class_exists('FPDF')) {
class FPDF {
    protected int $page = 0;
    protected int $n = 2;
    protected array $offsets = [];
    protected string $buffer = '';
    protected array $pages = [];
    protected string $state = '0';
    protected string $k;
    protected string $defOrientation = 'P';
    protected string $curOrientation = 'P';
    protected array $PageSizes = [];
    protected float $wPdf;
    protected float $hPdf;
    protected float $w;
    protected float $h;
    protected float $lMargin;
    protected float $tMargin;
    protected float $rMargin;
    protected float $bMargin;
    protected float $cMargin;
    protected float $x;
    protected float $y;
    protected float $lasth;
    protected string $LineWidth;
    protected string $fontFamily = 'Helvetica';
    protected string $fontStyle = '';
    protected float $fontSizePt = 12;
    protected float $fontSize = 12;
    protected string $DrawColor = '0 G';
    protected string $FillColor = '0 g';
    protected string $TextColor = '0 g';
    protected bool $ColorFlag = false;

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4') {
        $this->k = ($unit === 'pt') ? 1 : (($unit === 'mm') ? 72/25.4 : (($unit === 'in') ? 72 : 72/25.4));
        $this->PageSizes = ['a4' => [595.28, 841.89], 'letter' => [612, 792]];
        $size = strtolower($size);
        $p = $this->PageSizes[$size] ?? $this->PageSizes['a4'];
        $this->defOrientation = strtoupper($orientation);
        $this->wPdf = ($this->defOrientation === 'P') ? $p[0] : $p[1];
        $this->hPdf = ($this->defOrientation === 'P') ? $p[1] : $p[0];
        $this->curOrientation = $this->defOrientation;
        $this->w = $this->wPdf / $this->k;
        $this->h = $this->hPdf / $this->k;
        $margin = 28.35 / $this->k;
        $this->setMargins($margin, $margin);
        $this->cMargin = $margin / 10;
        $this->LineWidth = sprintf('%.3F', 0.567 / $this->k);
    }

    public function setMargins(float $left, float $top, float $right = -1): void {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = ($right == -1) ? $left : $right;
        $this->bMargin = $top;
    }

    public function addPage(string $orientation = ''): void {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = '2';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }

    public function setFont(string $family, string $style = '', float $size = 0): void {
        $family = strtolower($family);
        if ($family === 'arial' || $family === 'helvetica') $family = 'Helvetica';
        if ($family === 'times') $family = 'Times';
        if ($family === 'courier') $family = 'Courier';
        $this->fontFamily = $family;
        $this->fontStyle = strtoupper($style);
        if ($size > 0) {
            $this->fontSizePt = $size;
            $this->fontSize = $size / $this->k;
        }
    }

    public function setTextColor(int $r, int $g = -1, int $b = -1): void {
        if (($r == 0 && $g == 0 && $b == 0) || $g == -1) {
            $this->TextColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
    }

    public function setFillColor(int $r, int $g = -1, int $b = -1): void {
        if (($r == 0 && $g == 0 && $b == 0) || $g == -1) {
            $this->FillColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
    }

    public function setDrawColor(int $r, int $g = -1, int $b = -1): void {
        if (($r == 0 && $g == 0 && $b == 0) || $g == -1) {
            $this->DrawColor = sprintf('%.3F G', $r / 255);
        } else {
            $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        }
    }

    public function rect(float $x, float $y, float $w, float $h, string $style = ''): void {
        $op = match(strtoupper($style)) {
            'F' => 'f',
            'FD', 'DF' => 'B',
            default => 'S'
        };
        $this->out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void {
        $this->out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
    }

    public function cell(float $w, float $h = 0, string $txt = '', $border = 0, int $ln = 0, string $align = '', bool $fill = false): void {
        $k = $this->k;
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $s = '';
        if ($fill) {
            $op = ($border == 1) ? 'B' : 'f';
            $s .= sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        } elseif ($border == 1) {
            $s .= sprintf('%.2F %.2F %.2F %.2F re S ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k);
        }

        if ($txt !== '') {
            $txt2 = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $txt);
            $dx = match(strtoupper($align)) {
                'R' => $w - $this->cMargin - (strlen($txt) * $this->fontSizePt * 0.5 / $this->k),
                'C' => ($w - (strlen($txt) * $this->fontSizePt * 0.5 / $this->k)) / 2,
                default => $this->cMargin
            };
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + 0.75 * $h)) * $k, $txt2);
        }

        if ($s) $this->out($s);
        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln == 1) $this->x = $this->lMargin;
        } else {
            $this->x += $w;
        }
    }

    public function ln(?float $h = null): void {
        $this->x = $this->lMargin;
        $this->y += ($h !== null) ? $h : $this->lasth;
    }

    protected function out(string $s): void {
        if ($this->state === '2') {
            $this->pages[$this->page] .= $s . "\n";
        } else {
            $this->buffer .= $s . "\n";
        }
    }

    public function output(string $dest = 'I', string $name = 'document.pdf') {
        // Simple PDF stream builder
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        $kids = '';
        for ($i = 1; $i <= $this->page; $i++) {
            $kids .= (3 + ($i - 1) * 2) . " 0 R ";
        }
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [" . trim($kids) . "] /Count " . $this->page . " >>\nendobj\n";

        for ($i = 1; $i <= $this->page; $i++) {
            $pObj = 3 + ($i - 1) * 2;
            $cObj = $pObj + 1;
            $content = $this->pages[$i];
            $len = strlen($content);
            $pdf .= "{$pObj} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . ($this->wPdf) . " " . ($this->hPdf) . "] /Contents {$cObj} 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> >> >> >>\nendobj\n";
            $pdf .= "{$cObj} 0 obj\n<< /Length {$len} >>\nstream\n{$content}\nendstream\nendobj\n";
        }

        $pdf .= "xref\n0 " . (3 + $this->page * 2) . "\n";
        $pdf .= "trailer\n<< /Size " . (3 + $this->page * 2) . " /Root 1 0 R >>\nstartxref\n%%EOF\n";

        if ($dest === 'D' || $dest === 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . ($dest === 'D' ? 'attachment' : 'inline') . '; filename="' . $name . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $pdf;
            exit();
        }
        return $pdf;
    }
}
}
