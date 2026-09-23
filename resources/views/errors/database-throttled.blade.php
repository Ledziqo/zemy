<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Temporarily unavailable · ZemTab</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#11131a;color:#f5f5f5;font:16px/1.55 system-ui,sans-serif}.card{width:min(100%,620px);padding:32px;border:1px solid #343743;border-radius:18px;background:#191c25}h1{margin:0 0 12px;font-size:clamp(25px,6vw,36px)}p{color:#c5c8d0}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}a{display:inline-block;padding:12px 18px;border-radius:9px;background:#d22630;color:white;text-decoration:none;font-weight:700}.secondary{background:transparent;border:1px solid #555968}
    </style>
</head>
<body>
    <main class="card">
        <h1>Orders are temporarily delayed</h1>
        <p>The database is briefly limiting new connections. We could not verify whether this request finished. Your cart is saved on this device; retrying is safe because duplicate order submissions are detected.</p>
        <p>Please wait about a minute, then try again. Existing orders remain recorded and the Work Board will refresh when the database is available.</p>
        <div class="actions"><a href="{{ $retryUrl }}">Return and retry</a><a class="secondary" href="/">ZemTab home</a></div>
    </main>
</body>
</html>
