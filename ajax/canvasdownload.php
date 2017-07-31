<?php
/**
    Download Canvas files for a single assignment for all students.

    Downloaded files are saved with the same names as files in submission.zip,
    which is the manual download for assignments.
    zip name style: studentName_userId_fileId_attachmentDisplayname
    Examples:
    asn: studentone_15191_154796_assignment-1.zip
    asn late: studenttwo_late_11156_154924_project.zip

    @author Edward Parrish
    @version 1.0 06/27/16
    @version 1.1 07/28/17 Update cert path
*/
require_once 'canvasAPI.php';

/**
  Dowload all the assignment files for a given course and assignment id.

  Get course ids from: https://cabrillo.instructure.com/api/v1/courses
  Get assignment ids from: https://cabrillo.instructure.com/api/v1/courses/$courseId/assignments

  @param $cid The number of the course.
  @param $asnId The number of the assignment.
  @param $targetFolder The path to the destination folder.
  @param $all Set true to download all assignments; false for only ungraded.
*/
function downloadAssignments($cid, $asnId, $targetFolder, $all) {
    echo "Downloading from course $cid and asn $asnId to $targetFolder.\n";
    if (substr($targetFolder, -1) !== '/') $targetFolder .= '/';
    //startCURLLogging();
    $studentList = listStudentUsers($cid, true);
    foreach($studentList as $student) {
        $stuName = str_replace(',', '', $student->sortable_name);
        $stuName = str_replace('-', '', $stuName);
        $stuName = str_replace(' ', '', $stuName);
        $stuName = strtolower($stuName);
        downloadStudentFiles($cid, $asnId, $student->id, $stuName, $targetFolder, $all);
    }
    //stopCURLLogging();
}

/**
  Dowload all the assignment files for a given assignment and student.

  @param $cid The number of the course.
  @param $asnId The number of the assignment.
  @param $stuId The student id number.
  @param $stuName The student name to use in the target file name.
  @param $tgtFlr The path to the destination folder.
  @param $all Set true to download all assignments; false for only ungraded.
*/
function downloadStudentFiles($cid, $asnId, $stuId, $stuName, $tgtFlr, $all) {
    $apiUrl = 'courses/'.$cid.'/assignments/'.$asnId.'/submissions/'.$stuId;
    $studentAssignments = curlGet($apiUrl);
    //var_dump($studentAssignments);
    if (!isset($studentAssignments->attachments)) {
        echo "No student files submitted for $stuName.\n";
        return;
    }
    //echo "all=";var_dump($all);
    //echo "grade=";var_dump($studentAssignments->grade);
    //echo "score=";var_dump($studentAssignments->score);
    if ($all == false && $studentAssignments->grade != NULL) {
        echo "Already graded assignment for $stuName.\n";
        return;
    }
    echo "Downloading file(s) for $stuName.\n";
    foreach($studentAssignments->attachments as $asn) {
        $file = $stuName.'_'.$stuId.'_'.$asn->id.'_'.$asn->display_name;
        $filePath = $tgtFlr.$file;
        downloadFile($asn->url, $filePath);
    }
}

/**
  Dowload a file from the given URL and save to the specified file and path.

  @param $url The URL from which to download.
  @param $filePath The file name and path to the file.
  To CanvasAPI?
*/
function downloadFile($url, $filePath) {
    //echo "\n\n*********************************************************\n";
    //echo "From url=$url\n";
    //echo "To filePath=$filePath\n";
    set_time_limit(0); // infinity
    $fp = fopen ($filePath, 'w+');
    $ch = curl_init($url);
    //curl_setopt($ch, CURLOPT_VERBOSE, true); // output verbose information
    curl_setopt($ch, CURLOPT_CAINFO, CACERT_PATH); // Set cert
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_exec($ch);
    if(curl_errno($ch)) {
        echo "***Download error #".curl_errno($ch). ": ".curl_error($ch)."\n";
    }
    curl_close($ch);
    fclose($fp);
}

function showCanvasDownloadUsage($argv) {
?>
This script downloads files for each student from Canvas.

Usage:
<?php echo "php canvasdownload.php courseId asnId targetFolder all"; ?>

<option> courseId: The course number.
<option> asnId: The assignment number.
<option> targetFolder: The destination folder of the downloaded files.
<option> all: Set true to download all assignments; false for only ungraded.
With the --help, -help, -h, or -? options, you get these instructions.

<?php
  exit(1);
}

// Following invokes script in various ways
if ($_POST['do']=='download') {
    // Browser mode
    $cid = $_POST['cid'];
    $asnId = $_POST['asnid'];
    $targetFolder = $_POST['folder'];
    $all = true ? $_POST['all'] == "true" : false;
    downloadAssignments($cid, $asnId, $targetFolder, $all);
} else if ($argc == 5) {
    // Command line mode
    $cid = $argv[1];
    $asnId = $argv[2];
    $targetFolder = $argv[3];
    $all = $argv[4];
    downloadAssignments($cid, $asnId, $targetFolder, $all);
} else if ($argc == 1) {
    // File is included -- do nothing
    // Also can be test mode with hard-coded parameters:
    //$cid = 3113;
    //$asnId = 27803;
    //$targetFolder = '/Courses/cs11/homework/A01';
    //$all = true;
    //downloadAssignments($cid, $asnId, $targetFolder, $all);
} else if (isset($argc)) {
    showCanvasDownloadUsage($argv);
}
?>
