<!DOCTYPE html>
<html>
<head>
    <title>Payment Processing</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f7f7f9; }
        .card { background: #fff; padding: 40px; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,.05); max-width: 400px; }
        .status { color: #6E3FE7; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: .05em; }
        h1 { margin: 12px 0 8px; }
        p { color: #555; margin: 0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="status">{{ ucfirst($provider) }}</div>
        <h1>Payment received</h1>
        <p>Your payment is being processed. You can close this window.</p>
    </div>
</body>
</html>