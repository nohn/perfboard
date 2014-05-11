<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * frontend/index.php
 *
 * This file is part of perfboard.
 *
 * perfboard is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * perfboard is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with perfboard.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @category  Nagios
 * @package   perfboard
 * @author    Sebastian Nohn <sebastian@nohn.net>
 * @copyright 2013-2014 Sebastian Nohn <sebastian@nohn.net>
 * @license   http://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link      https://github.com/nohn/perfboard
 */
?>
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
