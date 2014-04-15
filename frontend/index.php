<html>
    <head>
        <title>perfboard</title>
        <meta http-equiv="refresh" content="10"/>
        <style type="text/css">
            body {
                background: #222222;
                font-family: 'Open Sans', "Helvetica Neue", Helvetica, Arial, sans-serif;
                padding: 0;
                margin: 0;
            }
            table {
                border-spacing: 5px;
                border-collapse: separate;
            }
            th {
                color: white;
                font-size: 150%;
                background-color: #4b4b4b;
            }
            td {
                text-align: center;
                color: white;
                vertical-align: middle;
                font-size: 300%;
            }
        </style>
    </head>
    <body>
        <?php
        require_once '../config.php';

        $width = floor(95 / count($sensors));
        $height = floor(100 / count($objects));
        ?>
        <table style="width: 100%; height: 100%">
            <tr>
                <th>response time (ms)</th>
                <?php
                foreach ($sensors as $sensor_name => $sensor_data) {
                    ?>
                    <th style="height: 1em;"><?php echo $sensor_name; ?></th>
                    <?php
                }
                ?>
            </tr>
            <?php
            foreach ($objects as $object_name => $object_data) {
                ?>
                <tr>
                    <th style="width: 10%;"><?php echo $object_name; ?></th>
                    <?php
                    foreach ($sensors as $sensor_name => $sensor_data) {
                        $sensor_file = sha1($sensor_name . $object_name);
                        $perfdata = (int) file_get_contents('/tmp/perfboard_' . $sensor_file);
                        $object_stats[$object_name][] = $perfdata;
                        $sensor_stats[$sensor_name][] = $perfdata;
                        if ($perfdata == 0 || $perfdata >= $object_data['crit']) {
                            $color = '#ec663c;';
                        } else if ($perfdata >= $object_data['warn']) {
                            $color = '#ff9618;';
                        } else {
                            $color = '#96bf48';
                        }
                        ?>
                        <td style="width: <?php echo $width; ?>%; background-color: <?php echo $color; ?>"><?php echo $perfdata; ?></td>
                        <?php
                    }
                    ?>
                </tr>
                <?php
            }
            ?>
        </table>
    </body>
</html>
