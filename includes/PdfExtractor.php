<?php
/**
 * Pure PHP PDF Text Extractor
 * AI Study Assistant
 * Extracts text from standard PDF files without requiring external binary tools
 */

class PdfExtractor {
    /**
     * Extract plain text from a PDF file
     *
     * @param string $filePath Absolute path to PDF file
     * @return string Extracted text
     */
    public static function extractText(string $filePath): string {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return '';
        }

        $content = file_get_contents($filePath);
        if ($content === false || empty($content)) {
            return '';
        }

        $extractedText = '';

        // 1. Find all stream ... endstream blocks
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                // Try decompressing with gzuncompress if flate decoded
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed === false) {
                    $uncompressed = @gzinflate($stream);
                }
                $dataToParse = ($uncompressed !== false) ? $uncompressed : $stream;

                // Extract text operators (Tj, TJ, ')
                $textFromStream = self::parseTextOperators($dataToParse);
                if (!empty($textFromStream)) {
                    $extractedText .= $textFromStream . "\n";
                }
            }
        }

        // 2. Fallback or supplementary extraction for non-streamed text
        if (empty(trim($extractedText))) {
            $extractedText = self::parseTextOperators($content);
        }

        // Clean up excessive whitespace
        $cleaned = preg_replace('/[ \t]+/', ' ', $extractedText);
        $cleaned = preg_replace('/(\r?\n){3,}/', "\n\n", $cleaned);

        return trim($cleaned);
    }

    /**
     * Parse PDF text operators from uncompressed stream data
     *
     * @param string $data
     * @return string
     */
    private static function parseTextOperators(string $data): string {
        $text = '';

        // Match BT ... ET blocks (Begin Text ... End Text)
        if (preg_match_all('/BT[\r\n\s]+(.*?)[\r\n\s]+ET/s', $data, $textBlocks)) {
            foreach ($textBlocks[1] as $block) {
                // Match (string) Tj
                if (preg_match_all('/\((.*?)\)\s*Tj/s', $block, $tjMatches)) {
                    foreach ($tjMatches[1] as $match) {
                        $text .= self::unescapePdfString($match) . ' ';
                    }
                }

                // Match [(array)] TJ
                if (preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjArrayMatches)) {
                    foreach ($tjArrayMatches[1] as $arrayContent) {
                        if (preg_match_all('/\((.*?)\)/s', $arrayContent, $stringParts)) {
                            foreach ($stringParts[1] as $part) {
                                $text .= self::unescapePdfString($part);
                            }
                            $text .= ' ';
                        }
                    }
                }

                $text .= "\n";
            }
        }

        return $text;
    }

    /**
     * Unescape standard PDF string escape sequences
     *
     * @param string $str
     * @return string
     */
    private static function unescapePdfString(string $str): string {
        $str = str_replace(
            ['\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\'],
            ["\n", "\r", "\t", "\x08", "\x0c", '(', ')', '\\'],
            $str
        );
        // Replace octal escapes \ddd
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);

        return $str;
    }
}

