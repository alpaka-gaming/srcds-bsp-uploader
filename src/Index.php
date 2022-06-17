<?php

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

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__)->load();

use Lazzard\FtpClient\Connection\FtpConnection;
use Lazzard\FtpClient\Connection\FtpSSLConnection;
use Lazzard\FtpClient\Config\FtpConfig;
use Lazzard\FtpClient\FtpClient;
use phpseclib3\Net\SFTP;


if (!extension_loaded('ftp')) {
    throw new RuntimeException("FTP extension not loaded.");
}

if (!extension_loaded('bz2')) {
    throw new RuntimeException("BZ2 extension not loaded.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($dotenv["DB_CONNECTION"] == "sqlite") {
        $database = new PDO("sqlite:{$dotenv["DB_CONNECTION"]}");
    }

    try {

        /* UPLOADER */
        $target_dir = 'public/data/maps';
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = $_FILES["bspFile"]["name"];
        $target_file = $target_dir . "/" . basename($file_name);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $map_name = strtolower(pathinfo($file_name, PATHINFO_FILENAME));

        if ($file_type != "bsp" && $file_type != ".bsp.bz2") throw new Exception("Invalid File Format");

        move_uploaded_file($_FILES["bspFile"]["tmp_name"], $target_file);

        /* BZIPING */
        $bzip2file = $target_dir . "/$file_name.bz2";
        $data = file_get_contents($target_file);
        file_put_contents("compress.bzip2://$bzip2file", $data);

        /* SFTP */
        $ftp_host = $dotenv["FTP_SERVER"];
        $ftp_username = $dotenv["FTP_USERNAME"];
        $ftp_password = $dotenv["FTP_PASSWORD"];
        $ftp_path = $dotenv["FTP_PATH"];

        $sftp = new SFTP($ftp_host);
        $sftp->login($ftp_username, $ftp_password);

        /* MAP */
        $remoteFile = $ftp_path . "/maps/" . $file_name;
        $localFile = $target_file;
        $success = false;
        if (!$sftp->file_exists($remoteFile)) {
            $success = $sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
        }

        /* MAP CYCLE */
        if ($success) {

            $target_dir = 'public/data/cfg';
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            $file_name = "mapcycle.txt";
            $remoteFile = $ftp_path . "/cfg/" . $file_name;

            $localFile = $target_dir . "/" . $file_name;
            $output = $sftp->get($remoteFile);
            $output = $output . PHP_EOL . $map_name;
            file_put_contents($localFile, $output);
            $success = $sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
        }

    } catch (Throwable $ex) {
        var_dump($ex);
    }

}

?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="public/css/app.css"/>

    <title>SRCDS BSP Uploader</title>
</head>
<body>

<div class="m-4">
    <div class="d-flex justify-content-center">
        <img src="public/images/source_engine_logo.svg" width="192" alt=""/>
    </div>
    <div class="d-flex justify-content-center">
        <form method="post" action="/" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="bspFile" class="form-label">Map Uploader</label>
                <input name="bspFile" class="form-control" type="file" id="bspFile">
            </div>
            <div class="mb-3">
                <button class="btn btn-primary" type="submit">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Optional JavaScript; choose one of the two! -->

<!-- Option 1: Bootstrap Bundle with Popper -->
<script src="public/js/app.js"></script>

<!-- Option 2: Separate Popper and Bootstrap JS -->
<!--
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>
-->
</body>
</html>

