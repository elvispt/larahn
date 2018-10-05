<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="{{ mix('/css/app.css') }}">

  <title>LaraHN</title>
</head>
<body>
  <div class="container">
    <p class="font-weight-bold text-center py-2"><a href="/">LaraHN</a></p>

    @yield('content')
  </div>
</body>
</html>
