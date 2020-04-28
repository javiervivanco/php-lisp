<?php

class Interop{
    static function to_php($obj) {
        if (is::list($obj) || is::vector($obj) || is::hash_map($obj)) {
            $ret = array();
            foreach ($obj as $k => $v) {
                $ret[self::to_php($k)] = self::to_php($v);
            }
            return $ret;
        } elseif (is_string($obj)) {
            if (strpos($obj, chr(0x7f)) === 0) {
                return ":".substr($obj,1);
            } else {
                return $obj;
            }
        } elseif (is::symbol($obj)) {
            return ${$obj->value};
        } elseif (is::atom($obj)) {
            return $obj->value;
        } else {
            return $obj;
        }
    }

    static function to_language($obj) {
        switch (gettype($obj)) {
            case "object":
            return self::to_language(get_object_vars($obj));
            case "array":
                $obj_conv = array();
                foreach ($obj as $k => $v) {
                    $obj_conv[self::to_language($k)] = self::to_language($v);
                }
                if ($obj_conv !== array_values($obj_conv)) {
                    $new_obj = create::hash_map();
                    $new_obj->exchangeArray($obj_conv);
                    return $new_obj;
                } else {
                    return call_user_func_array('create::list', $obj_conv);
                }
            default:
                return $obj;
            }
    }

    static function to_native($name, $env) {
      if (is_callable($name)) {
        return create::function(function() use ($name) {
          $args = array_map("Interop::to_php", func_get_args());
          $res = call_user_func_array($name, $args);
          return self::to_language($res);
        });
      // special case for language constructs
      } else if ($name == "print") {
        return create::function(function($value) {
          print(self::to_php($value));
          return null;
        });
      } else if ($name == "exit") {
        return create::function(function($value) {
          exit(self::to_php($value));
          return null;
        });
      } else if ($name == "require") {
        return create::function(function($value) {
          require(self::to_php($value));
          return null;
        });
      } else if (in_array($name, ["_SERVER", "_GET", "_POST", "_FILES", "_REQUEST", "_SESSION", "_ENV", "_COOKIE"])) {
          $val = $GLOBALS[$name];
      } else if (defined($name)) {
          $val = constant($name);
      } else {
          $val = ${$name};
      }
      return self::to_language($val);
    }
}