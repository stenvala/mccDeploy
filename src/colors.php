<?php

// Display on terminal with color

/* Source: http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 * 
 */

namespace mcc\deploy;

class colors {

  static private $fg = array();
  static private $bg = array();

  public static function __callStatic($name, $args) {
    // Set up shell colors
    self::$fg['black'] = '0;30';
    self::$fg['dark_gray'] = '1;30';
    self::$fg['blue'] = '0;34';
    self::$fg['light_blue'] = '1;34';
    self::$fg['green'] = '0;32';
    self::$fg['light_green'] = '1;32';
    self::$fg['cyan'] = '0;36';
    self::$fg['light_cyan'] = '1;36';
    self::$fg['red'] = '0;31';
    self::$fg['light_red'] = '1;31';
    self::$fg['purple'] = '0;35';
    self::$fg['light_purple'] = '1;35';
    self::$fg['brown'] = '0;33';
    self::$fg['yellow'] = '1;33';
    self::$fg['light_gray'] = '0;37';
    self::$fg['white'] = '1;37';

    self::$bg['black'] = '40';
    self::$bg['red'] = '41';
    self::$bg['green'] = '42';
    self::$bg['yellow'] = '43';
    self::$bg['blue'] = '44';
    self::$bg['magenta'] = '45';
    self::$bg['cyan'] = '46';
    self::$bg['light_gray'] = '47';

    if (count($args) != 2 && count($args) != 3) {
      die("Need to give at least string and text color.\n");
    } elseif (count($args) == 2) {
      return self::color_($args[0], $args[1]);
    } else {
      return self::color_($args[0], $args[1], $args[2]);
    }
  }

  static private function color_($string, $fg = null, $bg = null) {
    $str = "";

    if (isset(self::$fg[$fg])) {
      $str .= "\033[" . self::$fg[$fg] . "m";
    }
    // Check if given background color found
    if (isset(self::$bg[$bg])) {
      $str .= "\033[" . self::$bg[$bg] . "m";
    }

    // Add string and end coloring
    $str .= $string . "\033[0m";

    return $str;
  }

}
