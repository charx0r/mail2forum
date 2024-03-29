<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Arpad Ray <arpad@php.net>                                   |
// +----------------------------------------------------------------------+
//
// $Id: array_product.php,v 1.1 2005/12/05 14:49:08 aidan Exp $


/**
 * Replace array_product()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/time_sleep_until
 * @author      Arpad Ray <arpad@php.net>
 * @version     $Revision$
 * @since       PHP 5.1.0
 * @require     PHP 4.0.1 (trigger_error)
 */
if (!function_exists('array_product')) {
    function array_product($array)
    {
        if (!is_array($array)) {
            trigger_error('The argument should be an array', E_USER_WARNING);
            return;
        }

        if (empty($array)) {
            return 0;
        }

        $r = 1;
        foreach ($array as $v) {
            $r *= $v;
        }

        return $r;
    }
}
    
?>