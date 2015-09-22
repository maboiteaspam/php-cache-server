<?php
namespace PhpCache;

class Utils {
    public static function stderr ($message) {
        fwrite(fopen("php://stderr", "w+"), $message);
    }
    public static function stdout ($message) {
        fwrite(fopen("php://stdout", "w+"), $message);
    }
}