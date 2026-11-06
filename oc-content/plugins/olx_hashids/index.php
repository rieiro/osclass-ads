<?php
/*
Plugin Name: OLX Hashids Anonymizer
Plugin URI: https://github.com/osclass/
Description: Anonymizes item IDs in URLs using fast bidirectional Hashids, hiding database IDs from visitors and scrapers.
Version: 1.1.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_hashids
Plugin update URI:
*/

class OlxHashids {
    // Base-54 alphabet with confusable characters omitted (0, O, 1, I, l)
    private static $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    // Offset that pushes smallest integer (1) to 8-character Base-54 length
    // 54^7 = 1,338,925,209,984
    private static $offset = 1338925209984;

    public static function encode($id) {
        $id = (int)$id;
        if ($id <= 0) return '';
        
        $num = $id + self::$offset;
        $hash = '';
        $base = strlen(self::$alphabet);

        while ($num > 0) {
            $rem = $num % $base;
            $hash = self::$alphabet[$rem] . $hash;
            $num = (int)($num / $base);
        }
        return $hash;
    }

    public static function decode($hash) {
        if (empty($hash) || !is_string($hash)) return 0;
        if (is_numeric($hash)) return (int)$hash;

        $num = 0;
        $base = strlen(self::$alphabet);
        $len = strlen($hash);

        for ($i = 0; $i < $len; $i++) {
            $pos = strpos(self::$alphabet, $hash[$i]);
            if ($pos === false) return 0;
            $num = $num * $base + $pos;
        }

        $id = $num - self::$offset;
        return ($id > 0) ? $id : 0;
    }
}

// 1. Decode incoming hash before controllers execute
function olx_hashids_decode_request() {
    $param_id = Params::getParam('id');
    if (!empty($param_id) && !is_numeric($param_id)) {
        $real_id = OlxHashids::decode($param_id);
        if ($real_id > 0) {
            Params::setParam('id', $real_id);
            $_GET['id'] = $real_id;
            $_REQUEST['id'] = $real_id;
        }
    }
}

// 2. Start output buffering immediately on frontend requests
if (!defined('OC_ADMIN') || OC_ADMIN !== true) {
    if (!defined('OLX_OB_STARTED')) {
        define('OLX_OB_STARTED', true);
        ob_start(function($buffer) {
            if (empty($buffer)) return $buffer;

            // Rewrite non-friendly URLs: page=item&id=123 -> page=item&id=HASH
            $buffer = preg_replace_callback(
                '/(page=item(?:&|&amp;)id=)(\d+)/',
                function($matches) {
                    return $matches[1] . OlxHashids::encode((int)$matches[2]);
                },
                $buffer
            );

            // Rewrite friendly URLs if any: -i123 -> -iHASH
            $buffer = preg_replace_callback(
                '/(-i)(\d+)(\.html|\/|\?|"|\')/',
                function($matches) {
                    return $matches[1] . OlxHashids::encode((int)$matches[2]) . $matches[3];
                },
                $buffer
            );

            return $buffer;
        });
    }
}

// Hooks
osc_add_hook('before_init', 'olx_hashids_decode_request');
osc_add_hook('init', 'olx_hashids_decode_request');
olx_hashids_decode_request();
