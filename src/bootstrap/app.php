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

error_reporting(E_ALL ^ E_WARNING);

class App
{
    protected static $dotenv = null;

    public static function Environment(): object
    {
        if (App::$dotenv == null)
            App::$dotenv = Dotenv\Dotenv::create(__DIR__ . '/..')->load();
        return (object)self::$dotenv;
    }
}

class Auth
{
    protected static $user = null;

    public static function User()
    {
        if (isset($_COOKIE["user_token"])) {
            $cookie = $_COOKIE["user_token"];
            $content = base64_decode($cookie);
            self::$user = unserialize($content);
        }
        return self::$user;
    }

    //TODO:
    public static function login($credentials): bool
    {
        $stmt = DB::Database()->prepare("SELECT id, username, email, name, password FROM users WHERE active = 1 AND username = ?");
        $stmt->execute([$credentials->username]);
        $row = $stmt->fetch();
        if (isset($row)) {

            //$hash = password_hash($credentials->password, PASSWORD_DEFAULT);
            $verify = password_verify($credentials->password, $row[4]);
            if (!$verify) return false;

            $user = new stdClass();
            $user->session_id = uniqid();
            $user->username = $row[1];
            $user->email = $row[2];
            $user->name = $row[3];
            $user_id = $row[0];

            $stmt = DB::Database()->prepare("UPDATE sessions SET active = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = DB::Database()->prepare("INSERT INTO sessions(key,created_at,expires_at,user_id) values (?,?,?,?)");
            $created_at = date("c");
            $expires_at = time() + (86400 * 30);

            $stmt->execute([$user->session_id,$created_at,$expires_at,$user_id]);

            $serialized = serialize($user);
            $encoded = base64_encode($serialized);

            setcookie("user_token", $encoded, $expires_at, "/");
            return true;
        }
        return false;
    }

    public static function logout(): bool
    {
        unset($_COOKIE["user_token"]);
        setcookie("user_token", null, 0, "/");
        return true;
    }

    public static function guest(): bool
    {
        return Auth::User() == null;
    }
}

class DB
{
    protected static $connection = null;

    public static function Database(): PDO
    {
        $connectionString = App::Environment()->DB_DATABASE;
        if (App::Environment()->DB_CONNECTION == "sqlite") {
            if (DB::$connection == null)
                DB::$connection = new PDO("sqlite:{$connectionString}");
        }
        return self::$connection;
    }
}

