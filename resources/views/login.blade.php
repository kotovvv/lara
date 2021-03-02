<!-- app/views/login.blade.php -->

<!doctype html>
<html>
<head>
    <title>Look at me Login</title>
</head>
<body>

{{ Form::open(array('url' => 'login','method'=>'post')) }}
<h1>Login</h1>

<!-- if there are login errors, show them here -->

@error('error')
<p>{{ $message }}</p>
@enderror

<p>
    {{ Form::label('Name', 'Name') }}
    {{ Form::text('name') }}
</p>
<p>
    {{ Form::label('password', 'Password') }}
    {{ Form::password('password') }}
</p>

<p>{{ Form::submit('Submit!') }}</p>
{{ Form::close() }}
