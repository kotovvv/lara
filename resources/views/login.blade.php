<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="shortcut icon" href="img/favicon.ico">
  <title>Login</title>
  <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>

<body>
  <div id="app">
    <login-component />
  </div>

  <script src="{{ mix('js/app.js') }}"></script>
  @env('local')
  <script src="http://localhost:35729/livereload.js"></script>
  @endenv
</body>

</html>
