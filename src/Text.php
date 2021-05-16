<?php

namespace PanduanVIP\TextManipulation;

class Text{
    
    /*----------------------------------------------------------------
        Make a sentence from a spintax
    ----------------------------------------------------------------*/
    
    public static function spintax($string) {
        preg_match('#{(.+?)}#is', $string, $m);
      
        if(empty($m)) return $string;
        
        $t = $m[1];
        if(strpos($t,'{')!==false){
          $t = substr($t, strrpos($t,'{') + 1);
        }
      
        $parts = explode("|", $t);
        $string = preg_replace("+{".preg_quote($t)."}+is", $parts[array_rand($parts)], $string, 1);
        return self::spintax($string);
    }


    /*----------------------------------------------------------------
        Converts string to array based on newlines
    ----------------------------------------------------------------*/

    public static function string_to_array($string)
    {
        $array = preg_split('/\r\n|\r|\n/', $string);
        $array = array_map('trim', $array);
        $array = array_filter($array);
        return $array;
    }
    

    /*----------------------------------------------------------------
        Take excerpt from a paragraph
    ----------------------------------------------------------------*/

    public static function excerpt($string, $max_length = 140, $cut_off = '...', $keep_word = true)
    {
        if (strlen($string) <= $max_length) {
            return $string;
        }
    
        if (strlen($string) > $max_length) {
            if ($keep_word) {
                $string = substr($string, 0, $max_length + 1);
    
                if ($last_space = strrpos($string, ' ')) {
                    $string = substr($string, 0, $last_space);
                    $string = rtrim($string);
                    $string .=  $cut_off;
                }
            } else {
                $string = substr($string, 0, $max_length);
                $string = rtrim($string);
                $string .=  $cut_off;
            }
        }
    
        return $string;
    }


    /*----------------------------------------------------------------
        Remove double space
    ----------------------------------------------------------------*/

    public static function remove_double_space($string){
        while(strpos($string, "  ")!==false){
            $string = str_replace("  ", " ", $string);
        }
        return trim($string);
    }


    /*-----------------------------------------------------------------------
        Get string between two strings
    -----------------------------------------------------------------------*/

    public static function string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }


    
    /*-----------------------------------------------------------------------
        Sanitize title with dash for creating url slug
        This code is taken from wordpress in wp-includes/formatting.php
        with minimum modifications
    -----------------------------------------------------------------------*/

    public static function slug($title)
    {
        $title = strip_tags($title);
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        $title = str_replace('%', '', $title);
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

        if (self::seems_utf8($title)) {
            if (function_exists('mb_strtolower')) {
                $title = mb_strtolower($title, 'UTF-8');
            }
            $title = self::utf8_uri_encode($title, 200);
        }
        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title);
        $title = str_replace('.', '-', $title);
        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');
        return urldecode($title);
    }

    private static function seems_utf8($str)
    {
        self::mbstring_binary_safe_encoding();
        $length = strlen($str);
        self::reset_mbstring_encoding();
        for ($i = 0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) $n = 0; # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) $n = 1; # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) $n = 2; # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) $n = 3; # 11110bbb
            elseif (($c & 0xFC) == 0xF8) $n = 4; # 111110bb
            elseif (($c & 0xFE) == 0xFC) $n = 5; # 1111110b
            else return false; # Does not match any model
            for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                    return false;
            }
        }
        return true;
    }

    private static function utf8_uri_encode($utf8_string, $length = 0)
    {
        $unicode = '';
        $values = array();
        $num_octets = 1;
        $unicode_length = 0;

        self::mbstring_binary_safe_encoding();
        $string_length = strlen($utf8_string);
        self::reset_mbstring_encoding();

        for ($i = 0; $i < $string_length; $i++) {

            $value = ord($utf8_string[$i]);

            if ($value < 128) {
                if ($length && ($unicode_length >= $length))
                    break;
                $unicode .= chr($value);
                $unicode_length++;
            } else {
                if (count($values) == 0) $num_octets = ($value < 224) ? 2 : 3;

                $values[] = $value;

                if ($length && ($unicode_length + ($num_octets * 3)) > $length)
                    break;
                if (count($values) == $num_octets) {
                    if ($num_octets == 3) {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                        $unicode_length += 9;
                    } else {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                        $unicode_length += 6;
                    }

                    $values = array();
                    $num_octets = 1;
                }
            }
        }

        return $unicode;
    }

    private static function mbstring_binary_safe_encoding($reset = false)
    {
        static $encodings = array();
        static $overloaded = null;

        if (is_null($overloaded))
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);

        if (false === $overloaded)
            return;

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }

    private static function reset_mbstring_encoding()
    {
        self::mbstring_binary_safe_encoding(true);
    }

}