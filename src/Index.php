<?php

require __DIR__ . '/bootstrap/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new stdClass();
    $user->username = $_POST["username"];
    $user->password = $_POST["password"];
    $result = Auth::login($user);
    if ($result) {
        header(null, null, 200);
    } else {
        header(null, null, 401);
    }
    exit();
} else {
    if (!Auth::guest()) Auth::logout();
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
                <form id="formLogin" method="post" class="card-body p-lg-5">
                    <div class="text-center">
                        <img src="public/images/srcds_logo.svg" class="img-fluid w-50 my-3" alt="">
                    </div>
                    <div class="mb3">
                        <h1>Login</h1>
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" id="username" aria-describedby="emailHelp" placeholder="Email"/>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" id="password" placeholder="Password"/>
                    </div>
                    <div class="text-center">
                        <button id="btnLogin" class="btn btn-primary w-100" type="button" onclick="return onLogin()">Login</button>
                    </div>
                    <div id="loader" class="mb3">
                        <label>Uploading...</label>
                        <div class="progress">
                            <div id="progress" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                        </div>
                    </div>
                </form>
            </div>
            <small>Copyright &copy; 2022 - <a href="https://www.ennerperez.dev/" target="_blank">Enner Pérez</a></small>
        </div>
    </div>
</div>

<script src="public/js/app.js"></script>
<script>

    function onLoad() {
        document.querySelector("#loader").style.display = 'none';
        document.querySelector("#username").disabled = false;
        document.querySelector("#password").disabled = false;
        document.querySelector("#formLogin").reset();
    }

    function onLogin() {

        document.querySelector("#username").disabled = true;
        document.querySelector("#password").disabled = true;

        let action = document.querySelector("#formLogin").action;
        let method = document.querySelector("#formLogin").method;
        let username = document.querySelector("#username").value;
        let password = document.querySelector("#password").value;

        let formData = new FormData();
        formData.append("username", username);
        formData.append("password", password);

        let request = new XMLHttpRequest();

        request.addEventListener("load", onSuccess);
        request.addEventListener("error", onError);

        request.open(method, action);

        try {
            request.send(formData);
        } catch (e) {
            console.log(e);
        }
    }

    function onSuccess(e) {
        if (e.currentTarget.status === 200) {
            window.location.href = "Upload.php";
        }
        onLoad();
    }

    function onError(e) {
        swal.fire('Error', 'Unable to login, check username and password', 'error');
        onLoad();
    }

    /* INIT */
    onLoad();
</script>

</body>
</html>