<?php
/*
----------------------------------------------------
Mpass - Multi-Process Socket Server for PHP

copyright (c) 2010 Laruence
http://www.laruence.com

If you have any questions or comments, please email:
laruence@yahoo.com.cn

*/

/**
 * A loger for Mpass simple use 
 * @package Mpass
 */
class Mpass_Log {

    private static $last_error = NULL;

    public static function record($str, $priority, $scope = "") {
		$pid = getmypid();

        if (!empty($scope))  {
            print "[" . date("Y-m-d H:i:s") . "]-[PID:" . $pid . "]-[". $priority ."][" . $scope . "]" . $str . "\n";
        } else {
            print "[" . date("Y-m-d H:i:s") . "]-[PID:" . $pid . "]-[". $proirity ."]" . $str . "\n";
        }
    }

	public static function log($str, $scope = "") {
        if (!MPASS_DEBUG) {
            return TRUE;
        }
        self::record($str, "DEBUG", $scope);
    }

	public static function warn($str,  $scope = "") {
        self::record($str, "WARN", $scope);
    }

    public static function err($str, $scope = "") {
        self::$last_error = $str;

        self::record($str, "ERROR", $scope);
    }


    public static function getLastError() {
        return self::$last_error;
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
