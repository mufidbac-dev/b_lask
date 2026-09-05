<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password</title>
</head>
<body>
    <main>
        <h1>Reset password</h1>
        <form method="post" action="{{ url('/api/v1/auth/reset-password') }}">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">
            <input type="hidden" name="email" value="{{ request()->query('email') }}">

            <label>
                New password
                <input type="password" name="password" minlength="12" required>
            </label>
            <label>
                Confirm password
                <input type="password" name="password_confirmation" minlength="12" required>
            </label>
            <button type="submit">Reset password</button>
        </form>
    </main>
</body>
</html>
