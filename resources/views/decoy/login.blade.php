<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Log In</title>
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; background: #f0f0f1; margin: 0; padding: 48px 0; color: #3c434a; }
        .box { width: 320px; margin: 0 auto; }
        h1 { text-align: center; font-size: 20px; font-weight: 600; margin: 0 0 24px; }
        form { background: #fff; border: 1px solid #c3c4c7; padding: 26px 24px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        label { display: block; font-size: 14px; margin: 0 0 6px; }
        input[type=text], input[type=password] { width: 100%; box-sizing: border-box; padding: 8px; margin: 0 0 18px; border: 1px solid #8c8f94; border-radius: 3px; }
        button { width: 100%; padding: 9px; background: #2271b1; color: #fff; border: 0; border-radius: 3px; font-size: 14px; cursor: pointer; }
        .err { background: #fff; border-left: 4px solid #d63638; padding: 12px; margin: 0 0 18px; font-size: 13px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
    </style>
</head>
<body>
    <div class="box">
        <h1>Site Administration</h1>
        @if (!empty($failed))
            <div class="err"><strong>Error:</strong> The username or password you entered is incorrect.</div>
        @endif
        <form method="post" action="">
            <label for="log">Username or Email</label>
            <input type="text" name="log" id="log" autocomplete="off">
            <label for="pwd">Password</label>
            <input type="password" name="pwd" id="pwd" autocomplete="off">
            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>
