<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title></title>
    <link rel='shortcut icon' href='/images/favicon.ico' type='image/x-icon' />
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="/images/favicon-16x16.png" sizes="16x16" />
    <!-- <link href="https://fonts.googleapis.com/css?family=Manrope:400,500,700,800&display=swap" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body, html {
            margin: 0;
        }

        #root {
            visibility: hidden;
        }

        #loading {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
            font-display: swap;
            height: 100vh;
            margin: 0;
            color: #1a3b68;
            font-size: 14px;
            font-variant: tabular-nums;
            line-height: 1.5;
            background-color: #fff;
            font-feature-settings: 'tnum';
            position: fixed;
            width: 100%;
        }
    </style>
</head>

<body>

    <div id="loading">{{ env("APP_NAME") }} is loading...</div>

    <noscript>You need to enable JavaScript to run this app.</noscript>

    <div id="root"></div>

    <script>
        window.onload = function () {
            document.getElementById('root').style.visibility = 'visible';
            document.getElementById('loading').style.display = 'none';
        }
    </script>

<script type="text/javascript" src="/dist/bundle.js"></script></body>

</html>