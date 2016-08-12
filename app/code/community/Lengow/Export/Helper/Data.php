<?php

/**
 * Lengow Helper
 * @category   Lengow
 * @package    Lengow_Export
 * @author kassim belghait
 */
class Lengow_Export_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Returns the node and children as an array
     * Values ares trimed
     *
     * @param bool $isCanonical - whether to ignore attributes
     * @return array|string
     */
    public function asArray(SimpleXMLElement $xml, $isCanonical = true) {
        $result = array();
        if (!$isCanonical) {
            // add attributes
            foreach ($xml->attributes() as $attributeName => $attribute) {
                if ($attribute) {
                    $result['@'][$attributeName] = trim((string) $attribute);
                }
            }
        }
        // add children values
        if ($xml->hasChildren()) {
            foreach ($xml->children() as $childName => $child) {
                if (!$child->hasChildren())
                    $result[$childName] = $this->asArray($child, $isCanonical);
                else
                    $result[$childName][] = $this->asArray($child, $isCanonical);
            }
        } else {
            if (empty($result)) {
                // return as string, if nothing was found
                $result = trim((string) $xml);
            } else {
                // value has zero key element
                $result[0] = trim((string) $xml);
            }
        }
        return $result;
    }

    /**
     * Convert specials chars to html chars
     * Clean None utf-8 characters
     *
     * @param string $value The content
     * @param boolean $convert If convert specials chars
     * @param boolean $html Keep html
     * @return string $value
     */
    public function cleanData($value, $convert = false, $html = false) {
        if ($convert && $html)
            $value = htmlentities($value);
        if(is_array($value))
            return $value;
        $value = nl2br($value);
        $value = Mage::helper('core/string')->cleanString($value);
        // Reject overly long 2 byte sequences, as well as characters above U+10000 and replace with blank
        $value = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
                '|[\x00-\x7F][\x80-\xBF]+' .
                '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
                '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
                '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S', '', $value);
        // Reject overly long 3 byte sequences and UTF-16 surrogates and replace with blank
        $value = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]' .
                '|\xED[\xA0-\xBF][\x80-\xBF]/S', '', $value);
        if(!$html) {
            $pattern = '@<[\/\!]*?[^<>]*?>@si'; //nettoyage du code HTML
            $value = preg_replace($pattern, ' ', $value);
        }
        $value = preg_replace('/[\s]+/', ' ', $value); //nettoyage des espaces multiples
        $value = trim($value);
        $value = str_replace(
            array(
                '&nbsp;',
                '|',
                '"',
                '’',
                '&#39;',
                '&#150;',
                chr(9),
                chr(10),
                chr(13),
                chr(31),
                chr(30),
                chr(29),
                chr(28),
                "\n",
                "\r"
            ), array(
            ' ',
            ' ',
            '\'',
            '\'',
            ' ',
            '-',
            ' ',
            ' ',
            ' ',
            '',
            '',
            '',
            '',
            '',
            ''
        ), $value);
        return $value;
    }

    public function convertHTML($html) {
        $html = str_replace(array('"', "\r", "\n"),
                            array('"""', '', ''),
                            trim(nl2br($html)));
        return $html;
    }

    protected function _convert($content) {
        if (!mb_check_encoding($content, 'UTF-8') OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'))) {
            $content = mb_convert_encoding($content, 'UTF-8');
        }
        return $content;
    }
}
