<?php function_exists('add_action') or die; ?>
<html>
<head>
    <title><?php _e('Site is offline', 'autoupdater'); ?></title>
    <style type="text/css">
        body {
            background: #f1f1f1;
            color: #444;
            font-family: "Open Sans", sans-serif;
            font-size: 14px;
        }

        #content {
            width: 400px;
            padding: 8% 0 0;
            margin: auto;
        }

        #wrapper {
            padding: 20px 10px 25px;
            border-left: 4px solid #00a0d2;
            background-color: #fff;
            -webkit-box-shadow: 0 1px 1px 0 rgba(0, 0, 0, .1);
        }
    </style>
</head>
<body>
<div id="content">
    <div id="wrapper">
        <h2 style="text-align: center"><?php _e('This site is currently undergoing scheduled maintenance.', 'autoupdater'); ?></h2>
        <p style="text-align: center"><?php _e('Please try back soon!', 'autoupdater'); ?></p>
    </div>
</div>
</body>
</html>