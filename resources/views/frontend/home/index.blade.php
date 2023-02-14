<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Chinese translator</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite('resources/css/frontend/app.css')
    @vite('resources/js/frontend/index.jsx')
    <script>
        var _token = '{{ csrf_token() }}'
    </script>
</head>

<body>
    <div id="app-root"></div>
</body>
</html>
