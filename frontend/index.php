<html>
    <head>
        <title>perfboard</title>
        <meta http-equiv="refresh" content="10"/>
        <style type="text/css">
            body {
                background: black;
                font-family: Arial, sans-serif;
            }
            th {
                color: white;
                font-size: 150%;
            }
            td {
                text-align: center;
                vertical-align: middle;
                font-size: 300%;
            }
        </style>
    </head>
    <body>
        <?php
        require_once '../config.php';

        $width = floor(90 / count($objects));
        $height = floor(90 / count($sensors));
        ?>
        <table style="width: 100%; height: 100%">
            <tr>
                <th>&nbsp;</th>
                <?php
                foreach ($sensors as $sensor_name => $sensor_data) {
                    ?>
                    <th height="10%"><?php echo $sensor_name; ?></th>
                    <?php
                }
                ?>
            </tr>
            <?php
            foreach ($objects as $object_name => $object_data) {
                ?>
                <tr>
                    <th width="10%"><?php echo $object_name; ?></th>
                    <?php
                    foreach ($sensors as $sensor_name => $sensor_data) {
                        $sensor_file = sha1($sensor_name . $object_name);
                        $perfdata = (int) file_get_contents('/tmp/perfboard_' . $sensor_file);
                        $object_stats[$object_name][] = $perfdata;
                        $sensor_stats[$sensor_name][] = $perfdata;
                        if ($perfdata == 0 || $perfdata >= $object_data['crit']) {
                            $color = 'red';
                        } else if ($perfdata >= $object_data['warn']) {
                            $color = 'yellow';
                        } else {
                            $color = 'green';
                        }
                        ?>
                        <td style="width: <?php echo $height; ?>%; background-color: <?php echo $color; ?>"><?php echo $perfdata; ?></td>
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
