<?php

require __DIR__ . '/bootstrap/app.php';

use phpseclib3\Net\SFTP;

$dotenv = App::Environment();

if (Auth::guest()) {
    header("Location: Index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /* UPLOADER */
        $target_dir = 'public/data/maps';
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $file_name = $_FILES["file"]["name"];
        $target_file = $target_dir . "/" . basename($file_name);
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $map_name = strtolower(pathinfo($file_name, PATHINFO_FILENAME));

        if ($file_type != "bsp" && $file_type != "bz2") throw new Exception("Invalid File Format");

        move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);

        /* BZIPING */
        if ($file_type != "bz2") {
            $bzip2file = $target_dir . "/$file_name.bz2";
            $data = file_get_contents($target_file);
            file_put_contents("compress.bzip2://$bzip2file", $data);
        } else {
            $newFile = str_replace('.bz2', '', $target_file);
            $bz = bzopen($target_file, 'r');
            $uncompressed = '';
            if (file_exists($newFile)) unlink($newFile);
            $handle = fopen($newFile, 'w');
            while (!feof($bz)) {
                $chunk = bzread($bz, 4096);
                fwrite($handle, $chunk);
            }
            bzclose($bz);
        }

        $bsp_filename = $map_name . ".bsp";
        $bsp_filepath = $target_dir . "/" . $bsp_filename;

        $bz2_filename = $map_name . ".bsp.bz2";
        $bz2_filepath = $target_dir . "/" . $bz2_filename;

        /* SERVER */
        if ($dotenv->SERVER_FTP_SERVER != null) {

            $server_sftp = new SFTP($dotenv->SERVER_FTP_SERVER);
            $server_sftp->login($dotenv->SERVER_FTP_USERNAME, $dotenv->SERVER_FTP_PASSWORD);

            /* MAP */
            $remoteFile = $dotenv->SERVER_FTP_PATH . "/maps/" . $bsp_filename;
            $localFile = $bsp_filepath;

            if ($dotenv->MAP_OVERRIDE) {
                $file_exists = $server_sftp->file_exists($remoteFile);
                if ($file_exists) {
                    $server_sftp->delete($remoteFile);
                }
            }
            $file_exists = $server_sftp->file_exists($remoteFile);
            if (!$file_exists) {
                $success = $server_sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
            } else {
                $success = true;
            }
            if ($success) unlink($localFile);

            /* MAP CYCLE */
            if ($success && $_POST["cycle"] == "true") {

                $target_dir = 'public/data/cfg';
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

                $file_name = "mapcycle.txt";
                $remoteFile = $dotenv->SERVER_FTP_PATH . "/cfg/" . $file_name;

                $localFile = $target_dir . "/" . $file_name;
                $output = $server_sftp->get($remoteFile);
                file_put_contents($localFile, $output);

                $inCycle = false;
                foreach (file($localFile) as $line) {
                    if ($line == $map_name) {
                        $inCycle = true;
                        break;
                    }
                }
                if (!$inCycle) {
                    $output = $output . PHP_EOL . $map_name;
                    file_put_contents($localFile, $output);
                    $success = $server_sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
                    if ($success) unlink($localFile);
                }
            }
        }

        /* FASTDL */
        if ($dotenv->FASTDL_FTP_SERVER != null) {

            $fastdl_sftp = new SFTP($dotenv->FASTDL_FTP_SERVER);
            $fastdl_sftp->login($dotenv->FASTDL_FTP_USERNAME, $dotenv->FASTDL_FTP_PASSWORD);

            /* BZ2 */
            $remoteFile = $dotenv->FASTDL_FTP_PATH . "/maps/" . $bz2_filename;
            $localFile = $bz2_filepath;

            if ($dotenv->BZ2_OVERRIDE) {
                $file_exists = $fastdl_sftp->file_exists($remoteFile);
                if ($file_exists) {
                    $fastdl_sftp->delete($remoteFile);
                }
            }
            $file_exists = $fastdl_sftp->file_exists($remoteFile);
            if (!$file_exists) {
                $success = $fastdl_sftp->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE);
            } else {
                $success = true;
            }

            if ($success) unlink($localFile);
        }

        $stmt = DB::Database()->prepare("INSERT INTO uploads(session_id,created_at,map_name) values (?,?,?)");
        $created_at = date("c");
        $session_id = Auth::User()->session_id;

        $stmt->execute([$session_id, $created_at, $map_name]);

        header($map_name, null, 201);

    } catch (Throwable $ex) {
        $message = $ex->getMessage();
        header($message, null, 500);
        exit();
    }
} else {
    $username = Auth::User()->name;
}
?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- favicon -->
    <link rel="apple-touch-icon" sizes="57x57" href="public/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="public/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="public/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="public/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="public/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="public/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="public/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="public/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="public/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="public/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="public/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="public/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="public/favicon/favicon-16x16.png">
    <link rel="manifest" href="public/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="public/favicon/ms-icon-144x144.png">
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
                <form id="formUpload" method="post" enctype="multipart/form-data" class="card-body p-lg-5">
                    <div class="d-flex justify-content-end">
                        <a href="Index.php" class="btn-close " aria-label="Close"></a>
                    </div>
                    <div class="text-center">
                        <img src="public/images/srcds_logo.svg" class="img-fluid w-50 my-3" alt="">
                    </div>
                    <div class="mb3">
                        <h5>Welcome back, <?= $username ?></h5>
                        <h1>Map Uploader</h1>
                    </div>
                    <div class="mb-3">
                        <input id="file" name="file" class="form-control" type="file" accept=".bsp, .bz2">
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
                        <button id="btnUpload" class="btn btn-primary w-100" type="button" onclick="return onUpload()">Upload</button>
                    </div>
                    <div id="loader" class="mb3">
                        <label>Uploading...</label>
                        <div class="progress">
                            <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                        </div>
                    </div>
                </form>
            </div>
            <small>Copyright &copy; 2022 - <a href="https://www.ennerperez.dev/" target="_blank">Enner P??rez</a></small>
        </div>
    </div>
</div>

<script src="public/js/app.js"></script>
<script>
    function onLoad() {
        document.querySelector("#loader").style.display = 'none';
        document.querySelector("#file").disabled = false;
        document.querySelector("#checkCycle").disabled = false;
        document.querySelector("#btnUpload").disabled = false;
        document.querySelector("#formUpload").reset();
    }

    function onUpload() {

        document.querySelector("#loader").style.display = 'block';
        document.querySelector("#file").disabled = true;
        document.querySelector("#checkCycle").disabled = true;
        document.querySelector("#btnUpload").disabled = true;
        //document.querySelector("#formUpload").submit();

        let action = document.querySelector("#formUpload").action;
        let method = document.querySelector("#formUpload").method;
        let file = document.querySelector("#file").files[0];
        let cycle = document.querySelector("#checkCycle").checked;

        let formData = new FormData();
        formData.append("file", file);
        formData.append("cycle", cycle);

        let request = new XMLHttpRequest();

        request.addEventListener("progress", onProgress);
        request.addEventListener("load", onSuccess);
        request.addEventListener("error", onError);

        request.open(method, action);

        try {
            request.send(formData);
        } catch (e) {
            console.log(e);
        }
    }

    function onProgress(e) {
        if (e.lengthComputable) {
            let percentComplete = e.loaded / e.total * 100;
            document.querySelector("#formUpload").style.width = percentComplete + "%";
            console.log("The upload is " + percentComplete + "% completed")
        } else {
            // Unable to compute progress information since the total size is unknown
        }
    }

    function onSuccess(e) {
        if (e.currentTarget.status === 201 || e.currentTarget.statusText === "OK") {
            swal.fire('Done', 'The upload is complete.', 'success');
        } else {
            swal.fire('Error', 'An error occurred while transferring the file.', 'error');
        }
        onLoad();
    }

    function onError(e) {
        swal.fire('Error', 'An error occurred while transferring the file.', 'error');
        onLoad();
    }

    /* INIT */
    onLoad();

</script>

</body>
</html>


