<?php
/**
    Post score and grading comments from a file to Canvas for all students from a source folder.

    Grading comments and score are read from a grading log file placed in a student folder.

    @author Edward Parrish
    @version 1.0 06/27/16
*/
require_once 'canvasAPI.php';

/**
  Post score and grading comments from gradeFile to Canvas for all students from a source folder.

  Get course ids from: https://cabrillo.instructure.com/api/v1/courses
  Get assignment ids from: https://cabrillo.instructure.com/api/v1/courses/$courseId/assignments
  Students are matched via folder names.

  @param $cid The canvas id number of the course.
  @param $asnId The canvas id number of the assignment.
  @param $sourceFolder The path to the student grade files.
  @param $gradeFile The name of the file containing comments and the score.
  @param $type Type of upload: feedback, score, both
*/
function uploadGrade($cid, $asnId, $sourceFolder, $gradeFile, $type="both") {
    echo "Posting to course $cid and asn $asnId from $gradeFile files in $sourceFolder.\n";
    if (substr($sourceFolder, -1) !== '/') $sourceFolder .= '/';
    $studentList = listStudentUsers($cid, true);
    chdir($sourceFolder);
    //startCURLLogging();
    foreach($studentList as $student) {
        $folderName = str_replace(',', '', $student->sortable_name);
        $folderName = str_replace('-', '', $folderName);
        $folderName = str_replace(' ', '', $folderName);
        $folderName = strtolower($folderName);
        if (is_dir($folderName)) { // student turned in work
            echo "Uploading from $folderName.\n";
            $pathName = $folderName.'/'.$gradeFile;
            if (file_exists($pathName)) { // file was graded
                putFeedback($cid, $asnId, $student->id, $pathName, $type);
            } else {
                echo "Error: missing file $gradeFile in folder $folderName\n";
            }
        } else {
            //echo "Missing folder for $folderName.\n";
        }
    }
    //stopCURLLogging();
    if (sizeof($studentList) === 0) {
        echo "No students to process.\n";
    }
}

/**
  Upload score and/or comments.

  Rerunning posts a new comment. Can ungrade by posting a blank score.
  @param $cid The canvas id number of the course.
  @param $asnId The canvas id number of the assignment.
  @param $stuId The canvas student id number.
  @param $pathName The path to and filename of the student grade file.
  @param $type Type of upload: feedback, score, both
  @see https://canvas.instructure.com/doc/api/all_resources.html#method.submissions_api.update
*/
function putFeedback($cid, $asnId, $stuId, $pathName, $type) {
    $contents = file_get_contents($pathName);
    // Encode problem characters
    $contents = str_replace('%', '%25', $contents);
    $contents = str_replace('&', '%26', $contents);
    $score = getScore($contents); // posted_grade
    $apiUrl = 'courses/'.$cid.'/assignments/'.$asnId.'/submissions/'.$stuId;
    $data = "";
    if ($type != "feedback") $data = "submission[posted_grade]=$score";
    if ($type == "both") $data .= "&";
    if ($type != "score") $data .= "comment[text_comment]=$contents";
    $response = curlPut($apiUrl, $data, true);
    if ($response == false) {
        $pattern = "/\bName:\s*([\w ]+)/";
        preg_match($pattern, $contents, $matches);
        $name = $matches[1]; // posted_grade
        echo "**Failed to put feedback for student $name.\n";
    }
}

/**
  Extract score from grading log file contents.

  @param $contents The contents of the grading log file as a string.
*/
function getScore($contents) {
    $pattern = "/\bTotal Score:\s*(\d+)/";
    preg_match($pattern, $contents, $matches);
    if (isset($matches[1])) {
        $score = $matches[1]; // posted_grade
    } else {
        $score = "0";
        echo "Error: could not find \"Total Score\", setting to 0\n";
    }
    return $score;
}


function showCanvasPutgradeUsage($argv) {
?>
This script makes folders for each student and places their files
into those folders.

Usage:
<?php echo "php canvasputgrade.php courseId asnId targetFolder gradelog type";?>

<option> courseId: The course ID number.
<option> asnId: The assignment ID number.
<option> targetFolder: The destination folder of the downloaded files.
<option> gradelog: Name of the log file with grade and grading comments.
<option> type: Type of upload: feedback, score, both; defaults to both
With the --help, -help, -h, or -? options, you get these instructions.

<?php
  exit(1);
}

// Following invokes script in various ways
if (isset($_POST['do']) && $_POST['do']=='upload') {
    // Browser mode
    $cid = $_POST['cid'];
    $asnId = $_POST['asnid'];
    $sourceFolder = $_POST['folder'];
    $gradeLog = $_POST['log'];
    $type = $_POST['type'];
    uploadGrade($cid, $asnId, $sourceFolder, $gradeLog, $type);
} else if (isset($argc) && ($argc == 5 || $argc == 6)) {
    // Command line mode
    $cid = $argv[1];
    $asnId = $argv[2];
    $targetFolder = $argv[3];
    $gradeFile = $argv[4];
    $type = "both";
    if ($argc == 6) $type = $argv[5];
    uploadGrade($cid, $asnId, $sourceFolder, $gradeLog, $type);
} else if (isset($argc) && $argc == 1) {
    // Invoked when included -- do nothing
    // Also can be test mode with hard-coded parameters:
    //$cid = 3113;
    //$asnId = 27803;
    //$sourceFolder = '/Courses/cs11/homework/A01';
    //$gradeFile = "grade.log";
    //$type = "both";
    //uploadGrade($cid, $asnId, $sourceFolder, $gradeFile, $type);
} else if (isset($argc)) {
    showCanvasPutgradeUsage($argv);
}
?>
