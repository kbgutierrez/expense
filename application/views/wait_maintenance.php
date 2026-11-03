<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Maintenance | <?= html_escape(config_item('app_name') . ' - ' . config_item('app_long_name')); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/helpdesk-favicon.png">
    <style>
        body {
            background: linear-gradient(135deg, #f6f3ff 0%, #ddd6fe 100%);
            color: #222;
            font-family: 'Segoe UI', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .maintenance-container {
            background: #fff;
            padding: 56px 40px 40px 40px;
            border-radius: 14px;
            box-shadow: 0 8px 40px rgba(91, 43, 214, 0.10), 0 2px 8px rgba(0,0,0,0.04);
            text-align: center;
            max-width: 420px;
            width: 100%;
        }
        .maintenance-logo {
            width: 64px;
            margin-bottom: 18px;
        }
        .spinner {
            margin: 0 auto 22px auto;
            width: 54px;
            height: 54px;
            border: 6px solid #ede9fe;
            border-top: 6px solid #5b2bd6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg);}
            100% { transform: rotate(360deg);}
        }
        .maintenance-container h1 {
            font-size: 2em;
            margin-bottom: 12px;
            color: #5b2bd6;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .maintenance-container p {
            font-size: 1.08em;
            color: #444;
            margin-bottom: 0;
            line-height: 1.7;
        }
        .maintenance-footer {
            margin-top: 32px;
            font-size: 0.97em;
            color: #888;
        }
        .loading-bar-bg {
            width: 100%;
            height: 10px;
            background: #ede9fe;
            border-radius: 6px;
            overflow: hidden;
            margin: 28px 0 18px 0;
            box-shadow: 0 1px 4px rgba(91, 43, 214,0.07);
        }
        .loading-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #5b2bd6 0%, #8b5cf6 100%);
            border-radius: 6px;
            transition: width 0.4s cubic-bezier(.4,0,.2,1);
        }
        @media (max-width: 500px) {
            .maintenance-container {
                padding: 32px 10px 24px 10px;
            }
            .maintenance-logo {
                width: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <h1>Scheduled Maintenance</h1>
        <!-- <div class="loading-bar-bg">
            <div class="loading-bar" id="loadingBar"></div>
        </div> -->
        <p>
            <?= html_escape(config_item('app_name')); ?> (<?= html_escape(config_item('app_long_name')); ?>) is currently undergoing a scheduled update to serve you better.<br>
            <strong>We expect to be back online shortly.</strong><br>
            Thank you for your patience and understanding.
        </p>
    </div>
    <script>
        // Animate loading bar infinitely
        const bar = document.getElementById('loadingBar');
        let width = 0;
        let direction = 1;
        setInterval(() => {
            if (direction === 1) {
                width += 2;
                if (width >= 100) direction = -1;
            } else {
                width -= 2;
                if (width <= 0) direction = 1;
            }
            bar.style.width = width + '%';
        }, 20);
    </script>
</body>
</html>