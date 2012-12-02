<?php
include_once('../config/app.php');

$position_id = $_REQUEST['position_id'];

$data = file_get_contents($pos_file_name);

# find position in the JSON file and send all positions after it
# if it's not founds, we send the whole JSON file

# we do a simple text search, which should be fast enough
# it could be improved with a lookup table...

$index = 0;
if ($position_id) {
        $index = strpos($data, "\"position_id\":$position_id,");
        if ($index === FALSE) {
                $index = 0;
        } else {
                # we found the position_id string with our position
                # now find the matching closing brace
                $count = 1;
                for($index++; $index < strlen($data) && $count != 0; $index++) {
                        $c = $data{$index};
                        if ($c == '{') {
                                $count++;
                        } else if ($c == '}') {
                                $count--;
                        }
                }
                $index++;
        }
}

$positions = substr($data, $index);

header('Content-Type: application/json');
echo '{"positions":{"position":[' . $positions . ']}}';
?>
