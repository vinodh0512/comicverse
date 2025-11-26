<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-red: #ec1d24;
            --bg-light: #ffffff;
            --text-dark: #222222;
            --border-grey: #cccccc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { 
            background-color: var(--bg-light); 
            color: var(--text-dark); 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            text-align: center;
        }

        .error-container {
            max-width: 500px;
            padding: 40px;
        }

        .error-icon {
            font-size: 80px;
            color: var(--primary-red);
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .error-code {
            font-size: 60px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-red);
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .error-message {
            color: var(--text-dark);
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background-color: var(--primary-red);
            color: white;
            padding: 12px 25px;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 13px;
            border-radius: 4px;
            transition: 0.2s;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            background-color: #ff333b;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: var(--bg-light);
            border: 1px solid var(--border-grey);
            color: var(--text-dark);
            padding: 12px 25px;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 13px;
            border-radius: 4px;
            transition: 0.2s;
            text-decoration: none;
        }

        .btn-secondary:hover {
            border-color: var(--primary-red);
            color: var(--primary-red);
        }
    </style>
</head>
<body>

    <div class="error-container">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <div class="error-message">This page does not exist. You will be redirected to home.</div>
        <div class="action-buttons">
            <a href="../index.php" class="btn-primary"><i class="fas fa-home"></i> Go to Home</a>
        </div>
    </div>

    <script>
        setTimeout(function(){ window.location.href = '../index.php'; }, 5000);
    </script>

</body>
</html>