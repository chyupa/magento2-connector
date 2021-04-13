<?php

namespace EasySales\Integrari\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SETTINGS = 'settings/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneralConfig($code, $group = "general", $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SETTINGS . $group .'/' . $code, $storeId);
    }

    public function replaceSpecialChars($string)
    {
        $rem = [
            'ă', 'Ă', 'ş', 'Ş', 'ţ', 'Ţ', 'à', 'á', 'â', 'ã',
            'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ð', 'ì',
            'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø',
            '§','ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'À', 'Á', 'Â',
            'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', '€',
            'Ð', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ',
            'Ö', 'Ø', '§', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Ÿ',
            '&agrave;', '&aacute;', '&acirc;', '&atilde;', '&auml;',
            '&aring;', '&aelig;', '&ccedil;', '&egrave;', '&eacute;',
            '&ecirc;', '&euml;', '&eth;', '&igrave;', '&iacute;',
            '&icirc;', '&iuml;', '&ntilde;', '&ograve;', '&oacute;',
            '&ocirc;', '&otilde;', '&ouml;', '&oslash;', '&sect;',
            '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&yacute;',
            '&yuml;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;',
            '&Auml;', '&Aring;', '&AElig;', '&Ccedil;', '&Egrave;',
            '&Eacute;', '&Ecirc;', '&Euml;', '&euro;', '&ETH;', '&Igrave;',
            '&Iacute;', '&Icirc;', '&Iuml;', '&Ntilde;', '&Ograve;',
            '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&Oslash;', '&sect;',
            '&Ugrave;', '&Uacute;', '&Ucirc;', '&Uuml;', '&Yacute;', '&Yuml;'
        ];

        $add = [
            'a', 'A', 's', 'S', 't', 'T', 'a', 'a', 'a', 'a',
            'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'ed', 'i',
            'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o',
            's', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'A', 'A',
            'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'EUR',
            'ED', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O',
            'O', 'O', 'S', 'U', 'U', 'U', 'U', 'Y', 'Y', 'a', 'a',
            'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'ed',
            'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 's',
            'u', 'u', 'u', 'u', 'y', 'y', 'A', 'A', 'A', 'A', 'A', 'A',
            'AE', 'C', 'E', 'E', 'E', 'E', 'EUR', 'ED', 'I', 'I', 'I',
            'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'S', 'U', 'U', 'U',
            'U', 'Y', 'Y'
        ];

        return str_replace($rem, $add, $string);
    }
}
