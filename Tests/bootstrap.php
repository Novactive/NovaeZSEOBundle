<?php
/**
 * NovaeZSEOBundle Tests Bootstrap
 *
 * @package   Novactive\Bundle\eZSEOBundle
 * @author    Novactive <novaseobundle@novactive.com>
 * @copyright 2015 Novactive
 * @license   https://github.com/Novactive/NovaeZSEOBundle/blob/master/LICENSE MIT Licence
 */
$bootstrap = false;

if ( file_exists( __DIR__ . '/../../../ezpublish/bootstrap.php.cache' ) )
{
    $_SERVER['KERNEL_DIR'] = __DIR__ . '/../../../ezpublish';
    $bootstrap             = include __DIR__ . '/../../../ezpublish/bootstrap.php.cache';
}

if ( !$bootstrap )
{
    $_SERVER['KERNEL_DIR'] = __DIR__ . '/../../../../../ezpublish';
    $boostrap              = include __DIR__ . '/../../../../../ezpublish/bootstrap.php.cache';
}
