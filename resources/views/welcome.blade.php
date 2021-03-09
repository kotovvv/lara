<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<<<<<<< HEAD

=======
>>>>>>> f96ff8dd67f1b7f7957ed11c393b86b769c611a1
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel</title>
<<<<<<< HEAD
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@4.x/css/materialdesignicons.min.css" rel="stylesheet">
    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

</head>

<body>
    <div id="app">
        <test-component></test-component>
    </div>
    <script src="{{asset('js/app.js')}}"></script>
</body>

</html>
=======
</head>
<body>
<div class="flex-center position-ref full-height">
    @if (Route::has('login'))
        <div class="top-right links">
            @if(Auth::user())
                <a href="{{ url('/home') }}">Home</a>
                <a href="{{ url('/logout') }}">Logout</a>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endif
        </div>
    @endif
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-success">
                    <div class="panel-heading">List of Game of Thrones Characters</div>
                @if(Auth::user())
                    <!-- Table -->
                        <table class="table">
                            <tr>
                                <th>Character</th>
                                <th>Real Name</th>
                            </tr>
                            @foreach($characters as $key => $value)
                                <tr>
                                    <td>{{ $key }}</td><td>{{ $value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </div>
                @if(Auth::guest())
                    <a href="/login" class="btn btn-info"> You need to login to see the list 😜😜 >></a>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
>>>>>>> f96ff8dd67f1b7f7957ed11c393b86b769c611a1
