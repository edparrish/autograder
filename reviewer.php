<?php
/**
    Utility script lets the human grader review all or some submissions.

    @author Ed Parrish
    @version 1.2 10/14/2006
    @version 1.3 02/26/2008
    @version 1.4 05/16/2016
    @version 1.5 06/19/2017

To make this work:
1. Copy the following PHP 4 folders into the xampp/php folder
- browsecap
- extensions
- php4
- zendOptimizer
2. Make sure TextPad is set to open multiple files at once
   Configure->Preferences->Allow multiple files on command line
*/

define("TEST_DIR", "C:/Courses/tools/autograde5/testcpp");
$students = null;
//$students = array("VEAL9657");

include("grader.php");

// Put the tests here but call grade() to run tests on all students
class Reviewer extends Grader {
  // skip summary.log
  function startTest() {
    $this->timestamp = mktime();
  }

  // skip grade.log
  function preTest() { }

  function test() {
    echo "Reviewing ".basename($this->dir)."\n";

    // Prevent unwanted file types from loading into the text editor
    $files = $this->scanFilesRec();
    foreach ($files as $filename) {
      $pathParts = pathinfo($filename);
      $ext = strtolower($pathParts["extension"]);
      $dir = $pathParts["dirname"];
      if ($ext != "bmp"
          AND $ext != "7z"
          AND $ext != "au"
          AND $ext != "class"
          AND $ext != "ctxt"
          AND $ext != "db"
          AND $ext != "exe"
          AND $ext != "gif"
          AND $ext != "greenfoot"
          AND $ext != "jar"
          AND $ext != "jpg"
          AND $ext != "jpeg"
          AND $ext != "ico"
          AND $ext != "mp3"
          AND $ext != "o"
          AND $ext != "png"
          AND $ext != "rar"
          AND $ext != "tif"
          AND $ext != "tiff"
          AND $ext != "tws"
          AND $ext != "war"
          AND $ext != "wav"
          AND $ext != "zip"
         ) {
        $readFiles[$ext] = "$dir\\*.$ext";
      }
    }

    $fileString = "";
    // To get away from using PHP 4, could make a batch file to load files
    // into an editor with a pause command between each set of loads.
    foreach ($readFiles as $file) {
      $fileString .= "$file ";
    }
    $readme = new Readme();
    $readmeName = $readme->getReadmeName();
    if ($readmeName) {
      $fileString .= $readmeName;
    }
    //echo "fileString=$fileString\n";

    // Load files into text editor
    `"C:\Program Files (x86)\TextPad 7\TextPad.exe" $fileString`;
  }

  // skip grade.log
  function postTest() {}

  // skip summary.log
  function finishTest() {
    $elapsedTime = mktime() - $this->timestamp;
    $hours = (int) ($elapsedTime / 3600);
    $min = (int) ($elapsedTime % 3600 / 60);
    $sec = $elapsedTime % 60;
    $msg = "Elapsed time: ";
    if ($hours > 0) $msg .= $hours.":";
    if ($hours > 0 and $min < 9) $msg .= "0";
    $msg .= $min.":";
    if ($sec < 9) $msg .= "0";
    $msg .= $sec;
    $msg .= "\nStudents reviewed: ".($this->dl->count())."\n";
    $msg .= "\n";
    echo $msg;
  }
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
This script reviews student files one student at a time.

  Usage:
  <?php echo $argv[0]; ?>

  <option> path\to\test\directory
  With the --help, -help, -h, or -? options, you can get this help.

<?php
}

echo "\nClose your text editor and press any key to continue...\n";
`pause`; // Give user a chance to close their text editor

$r = new Reviewer($testDir, $students);
$r->runTest();

?>
