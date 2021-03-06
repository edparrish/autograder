<?php
/**
    Utility file to makes folders for each student using the name from Canvas for the folder name.
    Canvas file name download formats differ by application:
    asn: studentname_15191_154796_Snow Chain Manager-1.zip
    asn late: studentname_late_11156_154924_Sampler-1.zip
    quiz: student+name15191_question_180110_158662_curio.cpp

    Skips files when names are not in the Canvas download format.
    Unzips zip, 7z and gfar files recursively.
    Removes __MACOSX folders which are a potential source of problems.
    Renames folders based on student names read from a file.

    @author Edward Parrish
    @version 1.0 06/19/2016
    @version 1.1 07/30/2017 Added units test and minor updates.
    @version 1.2 09/18/2019 Expanded unit test and updated late submit.
*/
require_once 'util.php';
define("NAME_FILE", "../studentnames.csv");

/**
    Make folders for each student from downloaded Canvas files.

    @param $testDir The directory with download files where folders are made.
*/
function makeFolders($testDir) {
    echo "Making folders for each student in $testDir\n";
    $studentNames = readStudentNames(NAME_FILE);
    chdir($testDir); // needed for is_file() to work
    $dirList = scandir($testDir); // alphabetical order
    foreach ($dirList as $entry) {
        if (is_file($entry)) {
            //$comp = substr_compare($entry, "submission", 0, 10); //skip zip dl
            $num_Bars = substr_count($entry, "_");
            if ($num_Bars >= 3 /* && $comp !== 0*/) { // student file
                $nameList = preg_split("/_/", $entry); // parse file name
                // Adjust nameList to extract folder and file name
                if (strtolower($nameList[1]) == "late") {
                    // remove word late for late asn
                    unset($nameList[1]);
                    $nameList = array_values($nameList);
                //} else if (!ctype_digit($nameList[1])) {
                } else if (strpos($entry, '_question_') !== false) {
                    // quiz "file upload" question type
                    if (strpos($nameList[3], 'question') !== false) {
                        // Do something about extra name
                        $nameList[0] .= $nameList[1];
                        unset($nameList[1]);
                        $nameList = array_values($nameList);
                    }
                    // remove trailing digits
                    $nameList[0] .= preg_replace("/\d+$/", "", $nameList[1]);
                    // remove extra entries to match like asn
                    unset($nameList[1]);
                    unset($nameList[2]);
                    $nameList = array_values($nameList);
                }
                $folder = $nameList[0];
                $stuName = $folder;
                if (isset($studentNames[$folder])) {
                    $stuName = $studentNames[$folder];
                }
                $fileName = $nameList[3];
                // Build $fileName if students used _ in file names
                for ($i = 4; $i < count($nameList); $i++) {
                    $fileName .= "_".$nameList[$i];
                }
                $ext = strtolower(getFileExtension($fileName));
                if (!file_exists($folder)) {
                    mkdir($folder);
                }
                copy($entry, "$folder/$fileName");
                unlink($entry);
                if ($ext == "zip" || $ext == "7z" || $ext == "gfar") {
                    unzipFolder($folder);
                }
                addCommentsFile($folder, $stuName);
            } else /*if ($comp != 0)*/ {
                //echo "Did not move file: $entry\n";
            }
        }
    }
}

function addCommentsFile($folder, $studentName) {
    $startDir = getcwd();
    chdir($folder) or die("Could not change to directory: $folder\n");
    $commentsFile = "SubmissionComments.txt";
    if (!file_exists($commentsFile)) {
        file_put_contents($commentsFile, "Name: $studentName\n");
    }
    chdir($startDir);
}

// Returns an array of student names for canvas dir
function readStudentNames($fileName) {
    file_exists($fileName) or die ("Missing $fileName, exiting...");
    $nameList = array();
    $row = 1;
    $path = realpath($fileName);
    if ($path && ($handle = fopen($path, "r")) !== FALSE) {
        //echo "Reading file names: $fileName\n";
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $nameList[$data[0]] = $data[1];
        }
        fclose($handle);
    } else {
        echo "File not found: $fileName\n";
    }
    return $nameList;
}

// Recursive unzip
function unzipFolder($folder) {
    $startDir = getcwd();
    chdir($folder) or die("Could not change to directory: $folder\n");
    $zipFileList = glob("*.[zZ][iI][pP]");
    $zipFileList = array_merge($zipFileList, glob("*.7[zZ]"));
    $zipFileList = array_merge($zipFileList, glob("*.gfar"));
    $zipFileList = array_merge($zipFileList, glob("*.rar"));
    if ($zipFileList) {
        foreach($zipFileList as $file) {
            echo "- Unzipping file: $file\n";
            `7z x -y "$file"`; // unzip
        }
    }
    // clean up __MACOSX
    if (file_exists("__MACOSX")) {
        deleteFolder("__MACOSX");
    }
    // Recursively descend
    foreach (glob(dirname('*').'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        unzipFolder($dir);
    }
    chdir($startDir);
}

/* function:  returns a file's extension */
function getFileExtension($fileName) {
    return substr(strrchr($fileName, '.'), 1);
}

// Uncomment function call in args below to run unit tests
function testMakeFolders() {
    error_reporting(E_ALL | E_STRICT); // report all problems
    require_once 'util.php';
    $pass = true; // optimistic result
    echo "...creating files in current folder matching Canvas pattern\n";
    $filename = "studenttest_30473_511771_test.txt";
    file_put_contents($filename, "testing makeFolders\n");
    $filename = "studenttest2_LATE_10624_1474040_test.txt";
    file_put_contents($filename, "testing makeFolders when late\n");
    echo "...testing makeFolders()\n";
    makeFolders(__DIR__);
    // Verify new folder and files exist
    $pass &= assert(file_exists('studenttest'));
    $pass &= assert(file_exists('studenttest/SubmissionComments.txt'));
    $pass &= assert(file_exists('studenttest/test.txt'));
    $pass &= assert(file_exists('studenttest2'));
    $pass &= assert(file_exists('studenttest2/SubmissionComments.txt'));
    $pass &= assert(file_exists('studenttest2/test.txt'));
    echo "...removing new studenttest folder\n";
    deleteFolder("studenttest");
    deleteFolder("studenttest2");
    $pass &= assert(!file_exists('studenttest'));
    $pass &= assert(!file_exists('studenttest2'));
    echo "...unit test completed and ";
    echo $pass ? "passed.\n" : "failed.\n";
}

function showFolderMakerUsage() {
?>
This script makes folders for each student and places their files
into those folders.

Usage:
php foldermaker.php path/to/test/directory"; ?>

<option> path/to/directory: path to directory with folders to make.
With the -h, or -? options, you can get this help.

<?php
  exit(1);
}

// Following handles args
if ($argc == 1) {
    // Code is included in another script -- do nothing
    //testMakeFolders(); // Uncomment to unit test, recomment to use
} else if ($argc == 2 && $argv[1] == "-h" || $argv[1] == "-?") {
    showFolderMakerUsage();
} else if ($argc == 2) {
    // Command line mode
    $testDir = $argv[1];
    makeFolders($testDir);
} else {
    showFolderMakerUsage();
}
?>

