<?php
/**
    Utility file to remove unwanted files from student folders.

    @author Edward Parrish
    @version 1.0 07/29/2017
*/
require_once 'includes/util.php';


/**
    Remove files from each student folder in the testDir.

    @param $testDir The directory with student folders.
    @param $delGlobList The array of file globs to delete.
*/
function removeFiles($testDir, $delGlobList = array()) {
    $it = new DirectoryIterator($testDir);
    foreach ($it as $fileinfo) {
        if ($fileinfo->isDir() && !$fileinfo->isDot()) {
            // Remove globs on list
            foreach ($delGlobList as $glob) {
                //echo $fileinfo->getPathname().": removing $glob\n";
                deleteGlobRec($glob, $fileinfo->getPathname());
            }
        }
    }
}

function showFileRemoverUsage() {
?>
This script deletes unwanted files from student folders.

  Usage:
  <?php echo "php fileremover.php path/to/directory fileGlobs"; ?>

  <option> path/to/directory
  With the -h, or -? options, you can get this help.

<?php
  exit(1);
}


// Following handles args
if ($argc == 1) {
    // Code is included in another script -- do nothing
} else if ($argc == 2 && $argv[1] == "-h" || $argv[1] == "-?") {
    showFileRemoverUsage();
} else if ($argc >= 2) {
    // Command line mode
    $testDir = $argv[1];
    $delGlobList = array();
    for ($i = 2; $i < $argc; $i++) {
        $delGlobList[] = $argv[$i];
    }
    removeFiles($testDir, $delGlobList);
}
?>
