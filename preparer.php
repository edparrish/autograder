<?php
include_once("grader.php");

define("TEST_DIR", "C:/Courses/tools/autograde5/testfiles");
$students = null;
//$students = array("ROKN0221");

/**
    Utility script prepares each student submission for grading.

    @author Ed Parrish
    @version 1.2 10/14/2006
    @version 1.3 02/26/2008
    @version 1.4 05/16/2016
    @version 1.5 06/19/2017

// NTR: need a way to specify deletion of certain files during prep
// NTR: need a way to specify deletion of empty directories
// NTR: Make more like foldermaker.php without the class overhead.
// NTR: Need way to copy files into each folder
*/
class Preparer extends Grader {
  var $emptyDirs = array(); // keep track of empty directories

  function startTest() { echo "\n";} // skip summary.log
  function preTest() {} // skip grade.log

  function test() {
    echo "Preparing ".basename($this->dir)."\n";
    // Do not need following for Canvas
/*    if (file_exists("submission.html")) {
      echo "- Removing submission.html\n";
      unlink("submission.html");
    }
*/
    $fileList = glob("*");
    if (!$fileList) {
      $this->emptyDirs[] = $this->dir;
    }
    unzipFolder($this->dir);
    deleteGlobRec("*.[eE][xX][eE]", $this->dir);
    deleteGlobRec("*.o", $this->dir);
    deleteGlobRec("*.class", $this->dir);

    if (file_exists("compile.log")) {
        echo "-Removing compile.log\n";
        unlink("compile.log");
    }
  }

  function postTest() {} // skip grade.log

  // skip summary.log
  function finishTest() {
    foreach($this->emptyDirs as $dir) {
      echo "Empty folder NOT removed in preparer.php: $dir\n";
      //rmdir($dir);
    }
  }
}

// Recursive unzip
function unzipFolder($folder) {
    $startDir = getcwd();
    if (!chdir($folder)) {
        $cwd = getcwd();
        die("Could not change to directory: $cwd\n");
    }
    $zipFileList = glob("*.[zZ][iI][pP]");
    $zipFileList = array_merge($zipFileList, glob("*.7[zZ]"));
    $zipFileList = array_merge($zipFileList, glob("*.gfar"));
    if ($zipFileList) {
        foreach($zipFileList as $file) {
            echo "-Unzipping file: $file\n";
            `7z x -y "$file"`; // unzip
        }
    }
    // clean up __MACOSX
    if (file_exists("__MACOSX")) {
        deleteFolder("__MACOSX");
    }
    if (file_exists(".DS_Store")) {
        deleteFolder(".DS_Store");
    }
    // Recursively descend
    foreach (glob(dirname('*').'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        unzipFolder($dir);
        // Change space to _ for TextPad
        if (strpos($dir, " ") !== false) {
            //echo "found a space in: $dir\n";
            $newDir = str_replace(" ", "_", $dir);
            if (file_exists($newDir)) {
                echo "--Deleting old folder before renaming: $newDir\n";
                deleteFolder($newDir);
            }
            if (!rename("$dir", "$newDir")) {
                print "*****>> Manually rename $dir to $newDir\n";
            }
        }
    }
    chdir($startDir);
}

/*
    Delete folder and all files in the folder.

    @see: http://stackoverflow.com/questions/1334398/how-to-delete-a-folder-with-contents-using-php
*/
function deleteFolder($path) {
    //echo "deleting: $path\n";
    if (!file_exists($path)) return false;
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file) {
            deleteFolder(realpath($path) . '/' . $file);
        }
        return rmdir($path);
    } else if (is_file($path) === true || is_link($path) === true) {
        return unlink($path);
    }
    return false;
}

if ($argc == 2) {
  $testDir = $argv[1];
} else if (defined('TEST_DIR')) {
  $testDir = TEST_DIR;
} else {
  showUsage();
}

function showUsage() {
?>
This script preapres each student submission for grading.

  Usage:
  <?php echo $argv[0]; ?>

  <option> path\to\test\directory
  With the --help, -help, -h, or -? options, you can get this help.

<?php
}
$preper = new Preparer($testDir, $students);
$preper->runTest();

?>
