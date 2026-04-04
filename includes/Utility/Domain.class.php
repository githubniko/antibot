<?php

namespace Utility;

class Domain {
    public static function normalizeDomain($domain) {
        // Если расширение intl не установлено, возвращаем как есть
        if (!function_exists('idn_to_utf8')) {
            return $domain;
        }
        
        // Проверяем, содержит ли домен Punycode
        if (strpos($domain, 'xn--') !== false) {
            // Выбираем правильную константу для версии PHP
            $variant = defined('INTL_IDNA_VARIANT_UTS46') 
                ? INTL_IDNA_VARIANT_UTS46 
                : INTL_IDNA_VARIANT_2003;
            
            $result = idn_to_utf8($domain, IDNA_DEFAULT, $variant);
            
            if ($result !== false) {
                return $result;
            }
        }
        
        // Если это не Punycode или преобразование не удалось, возвращаем исходный
        return $domain;
    }
}