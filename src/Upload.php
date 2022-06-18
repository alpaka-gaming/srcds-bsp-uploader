<?php

require __DIR__ . '/bootstrap/app.php';

use phpseclib3\Net\SFTP;

$dotenv = App::Environment();
$username = "Enner Pérez";

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

    <!-- favicon -->
    <link rel="apple-touch-icon" sizes="57x57" href="/public/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/public/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/public/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/public/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/public/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/public/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/public/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/public/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/public/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/public/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/public/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/public/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/public/favicon/favicon-16x16.png">
    <link rel="manifest" href="/public/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/public/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="public/css/app.css"/>

    <title>SRCDS</title>
</head>
<body>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card shadow my-2">
                <form method="post" action="/" enctype="multipart/form-data" class="card-body p-lg-5">
                    <div class="text-center">
                        <img src="public/images/srcds_logo.svg" class="img-fluid w-50 my-3" alt="">
                    </div>
                    <div class="mb3">
                        <h5>Welcome, <?= $username ?></h5>
                        <h1>Map Uploader</h1>
                    </div>
                    <div class="mb-3">
                        <input id="file" name="file" class="form-control" type="file">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input id="checkCycle" class="form-check-input" type="checkbox" value="" name="cycle" checked>
                            <label class="form-check-label" for="cycle">
                                Include in mapcycle.txt file
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button id="btnUpload" onclick="onUpload" class="btn btn-primary w-100" type="submit">Upload</button>
                    </div>
                    <div id="loader" class="mb3">
                        <label>Uplading...</label>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="public/js/app.js"></script>
<script>
    function onLoad() {
        document.querySelector("#loader").style.display = 'none';
    }

    function onUpload() {
        document.querySelector("#loader").style.display = 'block';
        document.querySelector("#file").disabled = true;
        document.querySelector("#checkCycle").disabled = true;
        document.querySelector("#btnUpload").disabled = true;
    }

    /* INIT */
    onLoad();

</script>

</body>
</html>
