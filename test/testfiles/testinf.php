<?php
/**
  See if testing will survive an infinite loop in PHP.
*/
echo "Starting an infinite loop...\n";
$x = 0;
while (true) {
    $x = $x + 1;
    if ($x % 10000 == 0) {
        echo "Still going x=$x\n";
        fwrite(STDERR, "Standard error x=$x\n");
    }
}
?>
