<?php

if (!extension_loaded('ftp')) {
    throw new RuntimeException("FTP extension not loaded.");
}

if (!extension_loaded('bz2')) {
    throw new RuntimeException("BZ2 extension not loaded.");
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__ . '/../vendor/autoload.php';

class App
{
    protected static $dotenv = null;

    public static function Environment(): ?array
    {
        if (App::$dotenv == null)
            App::$dotenv = Dotenv\Dotenv::create(__DIR__ . '/..')->load();
        return self::$dotenv;
    }

}

