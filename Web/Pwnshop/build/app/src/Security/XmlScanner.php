<?php
namespace App\Security;
use Exception;

class XmlScanner
{
    private const ENCODING_PATTERN = '/encoding\\s*=\\s*(["\'])(.+?)\\1/s';
    private const ENCODING_UTF7 = '/encoding\\s*=\\s*(["\'])UTF-7\\1/si';
    
    const DEFAULT_FALLBACK_ENCODING = 'CP1252';
    const GUESS_ENCODING = 'guess';
    const UTF8_BOM = "\xEF\xBB\xBF";
    const UTF8_BOM_LEN = 3;
    const UTF16BE_BOM = "\xfe\xff";
    const UTF16BE_BOM_LEN = 2;
    const UTF16BE_LF = "\x00\x0a";
    const UTF16LE_BOM = "\xff\xfe";
    const UTF16LE_BOM_LEN = 2;
    const UTF16LE_LF = "\x0a\x00";
    const UTF32BE_BOM = "\x00\x00\xfe\xff";
    const UTF32BE_BOM_LEN = 4;
    const UTF32BE_LF = "\x00\x00\x00\x0a";
    const UTF32LE_BOM = "\xff\xfe\x00\x00";
    const UTF32LE_BOM_LEN = 4;
    const UTF32LE_LF = "\x0a\x00\x00\x00";

    private string $pattern;

    public function __construct(string $pattern = '<!DOCTYPE')
    {
        $this->pattern = $pattern;
    }

    private static function forceString(mixed $arg): string
    {
        return is_string($arg) ? $arg : '';
    }

    private function toUtf8(string $xml): string
    {
        $charset = $this->findCharSet($xml);
        $foundUtf7 = $charset === 'UTF-7';
        if ($charset !== 'UTF-8') {
            $testStart = '/^.{0,4}\\s*<?xml/s';
            $startWithXml1 = preg_match($testStart, $xml);
            $xml = self::forceString(mb_convert_encoding($xml, 'UTF-8', $charset));
            if ($startWithXml1 === 1 && preg_match($testStart, $xml) !== 1) {
                throw new Exception('Double encoding not permitted');
            }
            $foundUtf7 = $foundUtf7 || (preg_match(self::ENCODING_UTF7, $xml) === 1);
            $xml = preg_replace(self::ENCODING_PATTERN, '', $xml) ?? $xml;
        } else {
            $foundUtf7 = $foundUtf7 || (preg_match(self::ENCODING_UTF7, $xml) === 1);
        }
        if ($foundUtf7) {
            throw new Exception('UTF-7 encoding not permitted');
        }

        return $xml;
    }
    private static function guessEncodingTestBom(string &$encoding, string $first4, string $compare, string $setEncoding): void
    {
        if ($encoding === '') {
            if (str_starts_with($first4, $compare)) {
                $encoding = $setEncoding;
            }
        }
    }

    private static function guessEncodingBom(string $convertString): string
    {
        $encoding = '';
        $first4 = $convertString;   
        self::guessEncodingTestBom($encoding, $first4, self::UTF8_BOM, 'UTF-8');
        self::guessEncodingTestBom($encoding, $first4, self::UTF16BE_BOM, 'UTF-16BE');
        self::guessEncodingTestBom($encoding, $first4, self::UTF32BE_BOM, 'UTF-32BE');
        self::guessEncodingTestBom($encoding, $first4, self::UTF32LE_BOM, 'UTF-32LE');
        self::guessEncodingTestBom($encoding, $first4, self::UTF16LE_BOM, 'UTF-16LE');

        return $encoding;
    }
    private function findCharSet(string $xml): string
    {
        if (substr($xml, 0, 4) === "\x4c\x6f\xa7\x94") {
            throw new Exception('EBCDIC encoding not permitted');
        }
        $encoding = self::guessEncodingBom($xml);
        if ($encoding !== '') {
            return $encoding;
        }
        $xml = str_replace("\0", '', $xml);
        if (preg_match(self::ENCODING_PATTERN, $xml, $matches)) {
            return strtoupper($matches[2]);
        }

        return 'UTF-8';
    }

    public function scan($xml): string
    {
        $pattern = '/\\0*' . implode('\\0*', str_split($this->pattern)) . '\\0*/';

        $xml = "$xml";
        if (preg_match($pattern, $xml)) {
            throw new Exception('Before UTF-8 conversion, detected use of ENTITY in XML, spreadsheet file load() aborted to prevent XXE/XEE attacks');
        }

        $xml = $this->toUtf8($xml);
        if (preg_match($pattern, $xml)) {
            var_dump($xml);
            throw new Exception('After UTF-8 conversion, detected use of ENTITY in XML, spreadsheet file load() aborted to prevent XXE/XEE attacks');
        }

        return $xml;
    }
} 