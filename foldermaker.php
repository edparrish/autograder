<?php
/**
    Utility file to makes folders for each student using the name from Canvas for the folder name.
    Canvas file name download formats differ by application:
    asn: studentname_15191_154796_Snow Chain Manager-1.zip
    asn late: studentname_late_11156_154924_Sampler-1.zip
    quiz: student+name15191_question_180110_158662_curio.cpp

    Skips files when names are not in the Canvas download format.
    Unzips zip, 7z and gfar files recursively.
    --preparer does the same thing, need to extract but not recursively?
    Removes __MACOSX folders which are a potential source of problems.
    //Renames folders based on student names read from a file.

    @author Edward Parrish
    @version 1 2/15/16
*/
define("NAME_FILE", "../studentnames.csv");

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
                if ($nameList[1] == "late") {
                    // remove word late for late asn
                    unset($nameList[1]);
                    $nameList = array_values($nameList);
                //} else if (!ctype_digit($nameList[1])) {
                } else if (strpos($entry, '_question_') !== false) {
                    // quiz file upload type
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
                if (!file_exists($folder)) mkdir($folder);
                copy($entry, "$folder/$fileName");
                unlink($entry);
                if ($ext == "zip" || $ext == "7z" || $ext == "gfar") {
                    unzipFolder($folder);
                }
                addCommentsFile($folder, $stuName);
            } else /*if ($comp != 0)*/ {
                echo "Did not move file: $entry\n";
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
    $nameList = array();
    $row = 1;
    $path = realpath($fileName);
    if ($path && ($handle = fopen($path, "r")) !== FALSE) {
        echo "Reading file names: $fileName\n";
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

/*
  Delete folder and all files in the folder.

  @see http://stackoverflow.com/questions/1334398/how-to-delete-a-folder-with-contents-using-php
*/
function deleteFolder($path) {
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file) {
            deleteFolder(realpath($path) . '/' . $file);
        }
        return rmdir($path);
    } else if (is_file($path) === true) {
        return unlink($path);
    }
    return false;
}

/* function:  returns a file's extension */
function getFileExtension($fileName) {
    return substr(strrchr($fileName, '.'), 1);
}

function showUsage() {
?>
This script makes folders for each student and places their files
into those folders.

  Usage:
  <?php echo "$argv[0] path\to\test\directory"; ?>

  <option> path\to\test\directory
  With the --help, -help, -h, or -? options, you can get this help.

<?php
  exit(1);
}

// Following handles args
if ($argc == 2) {
  $testDir = $argv[1];
} else if (defined('TEST_DIR')) {
  $testDir = TEST_DIR;
} else {
  showUsage();
}

// Call the function
makeFolders($testDir);
?>
