<!DOCTYPE html>
<html>

<head>
  <title></title>
</head>

<body>
  <h3>Dear {{ $user->title . ' ' . $user->name }}</h3>
  <p>We are glad to have you onboard. Kindly confirm your registration by clicking or copying the link below to your
    browser</p>
  <p><a
      href="{{ env('FRONTEND_URL') }}/confirm-registration/{{ $user->confirm_hash }}">{{ env('FRONTEND_URL') }}/confirm-registration/{{ $user->confirm_hash }}</a>
  </p>
  <p>
    <font color="red">Please kindly ignore this message if you did not initiate this process.</font>
  </p>

</body>

</html>
