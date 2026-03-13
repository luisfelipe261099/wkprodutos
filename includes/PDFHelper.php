<?php
/**
 * PDFHelper - Classe auxiliar para gera��o padronizada de PDFs no sistema
 * 
 * Esta classe encapsula a l�gica de gera��o de PDFs, fornecendo m�todos
 * que substituem as fun��es depreciadas e garantem a compatibilidade
 * entre diferentes vers�es do PHP.
 */
class PDFHelper {
    /**
     * Converte UTF-8 para ISO-8859-1 (Latin1) sem usar utf8_decode
     * 
     * @param string $str String em UTF-8
     * @return string String convertida para ISO-8859-1
     */
    public static function utf8ToLatin1($str) {
        // Se a extens�o mbstring estiver dispon�vel, usamos mb_convert_encoding
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
        }
        
        // Fallback para iconv se dispon�vel
        if (function_exists('iconv')) {
            $result = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
            return $result !== false ? $result : $str;
        }
        
        // Se nenhuma fun��o de convers�o estiver dispon�vel, usamos um fallback simplificado
        // (n�o � perfeito, mas � melhor que usar utf8_decode depreciado)
        $chars = array(
            // Acentos
            '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a',
            '�' => 'e', '�' => 'e', '�' => 'e', '�' => 'e',
            '�' => 'i', '�' => 'i', '�' => 'i', '�' => 'i',
            '�' => 'o', '�' => 'o', '�' => 'o', '�' => 'o', '�' => 'o',
            '�' => 'u', '�' => 'u', '�' => 'u', '�' => 'u',
            '�' => 'c', '�' => 'n',
            // Mai�sculas
            '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'A', '�' => 'A',
            '�' => 'E', '�' => 'E', '�' => 'E', '�' => 'E',
            '�' => 'I', '�' => 'I', '�' => 'I', '�' => 'I',
            '�' => 'O', '�' => 'O', '�' => 'O', '�' => 'O', '�' => 'O',
            '�' => 'U', '�' => 'U', '�' => 'U', '�' => 'U',
            '�' => 'C', '�' => 'N'
        );
        
        return strtr($str, $chars);
    }
    
    /**
     * Adiciona texto a um PDF, tratando codifica��o UTF-8 para ISO-8859-1
     * 
     * @param FPDF $pdf Objeto FPDF
     * @param float $x Posi��o X
     * @param float $y Posi��o Y
     * @param string $txt Texto UTF-8
     * @return void
     */
    public static function addText($pdf, $x, $y, $txt) {
        $pdf->Text($x, $y, self::utf8ToLatin1($txt));
    }
    
    /**
     * Cria uma c�lula em um PDF, tratando codifica��o UTF-8 para ISO-8859-1
     * 
     * @param FPDF $pdf Objeto FPDF
     * @param float $w Largura da c�lula
     * @param float $h Altura da c�lula
     * @param string $txt Texto UTF-8
     * @param int $border Borda (0: sem borda, 1: borda completa, etc.)
     * @param int $ln Posi��o ap�s a c�lula (0: � direita, 1: in�cio da pr�xima linha, 2: abaixo)
     * @param string $align Alinhamento ('L', 'C', 'R')
     * @param bool $fill Preenchimento (true/false)
     * @param string $link URL do link
     * @return void
     */
    public static function addCell($pdf, $w, $h, $txt, $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        $pdf->Cell($w, $h, self::utf8ToLatin1($txt), $border, $ln, $align, $fill, $link);
    }
    
    /**
     * Cria uma c�lula de m�ltiplas linhas em um PDF, tratando codifica��o UTF-8 para ISO-8859-1
     * 
     * @param FPDF $pdf Objeto FPDF
     * @param float $w Largura da c�lula
     * @param float $h Altura da c�lula
     * @param string $txt Texto UTF-8
     * @param int $border Borda
     * @param string $align Alinhamento ('L', 'C', 'R', 'J')
     * @param bool $fill Preenchimento
     * @return void
     */
    public static function addMultiCell($pdf, $w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        $pdf->MultiCell($w, $h, self::utf8ToLatin1($txt), $border, $align, $fill);
    }
    
    /**
     * Verifica e garante que um valor num�rico existe
     * 
     * @param array $arr Array a ser verificado
     * @param string $key Chave a ser verificada
     * @param array $alternateKeys Chaves alternativas a verificar
     * @param float $default Valor padr�o
     * @return float Valor existente ou padr�o
     */
    public static function ensureNumericValue($arr, $key, $alternateKeys = [], $default = 0) {
        if (isset($arr[$key]) && is_numeric($arr[$key])) {
            return $arr[$key];
        }
        
        foreach ($alternateKeys as $altKey) {
            if (isset($arr[$altKey]) && is_numeric($arr[$altKey])) {
                return $arr[$altKey];
            }
        }
        
        return $default;
    }
    
    /**
     * Verifica e garante que um valor string existe
     * 
     * @param array $arr Array a ser verificado
     * @param string $key Chave a ser verificada
     * @param array $alternateKeys Chaves alternativas a verificar
     * @param string $default Valor padr�o
     * @return string Valor existente ou padr�o
     */
    public static function ensureStringValue($arr, $key, $alternateKeys = [], $default = '') {
        if (isset($arr[$key]) && !empty($arr[$key])) {
            return $arr[$key];
        }
        
        foreach ($alternateKeys as $altKey) {
            if (isset($arr[$altKey]) && !empty($arr[$altKey])) {
                return $arr[$altKey];
            }
        }
        
        return $default;
    }
    
    /**
     * Inicia o buffer de sa�da e prepara cabe�alhos para PDF
     * 
     * @param string $filename Nome do arquivo PDF
     * @return void
     */
    public static function startPdfOutput($filename = 'documento.pdf') {
        // Limpa qualquer sa�da anterior
        if (ob_get_length()) ob_end_clean();
        
        // Inicia novo buffer
        ob_start();
        
        // Define cabe�alhos para PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
    }
}
?>
