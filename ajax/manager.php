<?php
// PHP script for manager.html AJAX calls
require_once 'canvasAPI.php';
require_once 'canvasdownload.php';
require_once 'canvasputgrade.php';

//print_r($_POST);
if ($_POST['do']=='getSelect') {
    courseSelect();
} else if ($_POST['do']=='getAsn') {
    $cid = $_POST['cid'];
    getAssignments($cid);
} else if ($_POST['do']=='exec') {
    $filePath = $_POST['path'];
    execScript($filePath);
}

function courseSelect() {
echo <<<EOD
<p>Select the course to grade:
<select id="courseID" name="courseID" onchange="assignments(this)">
EOD;
    echo "<option value=\"none\">Select...</option>\n";
    $resp = getCourseList();
    foreach ($resp as $course) {
        $type = $course->enrollments[0]->type;
        if ($type == "teacher") {
            echo '<option value="'.$course->id.'"'; //'
            echo ">$course->name</option>\n";
        }
    }
    echo "</select></p>\n";
}
function getAssignments($cid) {
    //startCURLLogging();
    $resp = listAssignments($cid);
    //stopCURLLogging();
    if (sizeof($resp) == 0) {
        echo "<p>No assignments with online-upload files.</p>\n";
    } else {
        showAssignments($cid, $resp);
    }
    //stopCURLLogging();
}

function showAssignments($cid, $resp) {
    date_default_timezone_set(TIMEZONE);
echo <<<EOD
<table>
<caption>Assignments with online-upload files</caption>
<tr>
  <th>##</th>
  <th title="Assignment name. Hover over links below for more information. Click the link to open the assignment in Canvas.">Name &#x24D8;</th>
  <th title="Assignment due date. Hover over date below for more detail.">Due Date &#9432;</th>
  <th title="Number of assignments needing grading. Deleting a grade leaves the status as graded. Students submitting new files after receiving a grade changes their status to ungraded.">Ungraded &#9432;</th>
  <!--th title="Have Canvas download the assignment files as a single zip file. Requires Canvas login and manual unzip after download.">Zip &#9432;</th-->
  <th colspan="3" title="Download student assignment files to the download folder. Hover over buttons and links below for more information.">Download &dArr; &#9432;</th>
  <th title="Select a grading script to invoke automatic grading. The script file name is appended to the script loation folder specified above. Scripts are run immediately. Multiple scripts may be run, but only one at a time.">Grade &#9432;</th>
  <th colspan="3" title="Automatically upload assignment comments or scores from a grade.log file in each student's folder, extracing and assigning the score from the log file. You can manually review each assignment before uploading the grade.">Upload &#9432;</th>
</tr>
EOD;
    $num = 0;
    foreach ($resp as $asn) {
      foreach($asn->submission_types as $type) {
        if ($type == "online_upload") {
            $num++;
            showSingleAssignment($cid, $asn, $num);
            break;
        }
      }
    }
    echo "\n</table>\n";
}

function showSingleAssignment($cid, $asn, $num) {
    $timeLong = date("D n-d-y @ g:i a", strtotime($asn->due_at));
    $timeShort = date("m-d-y @ H:i", strtotime($asn->due_at));
    $locked = $asn->locked_for_user ? 'locked' : 'unlocked';
    $muted = $asn->muted ? 'muted' : 'unmuted';
    $published = $asn->published ? 'published' : 'unpublished';
echo <<<EOD
<tr>
  <td style="text-align:right;">$num.</td>
  <td style="text-align:left;"><a href="$asn->html_url" target="blank" title="Follow link to see assignment $asn->id in Canvas. ($locked, $muted, $published)">$asn->name</a></td>
  <td title="$timeLong">$timeShort</td>
  <td>$asn->needs_grading_count</td>
  <td><input type="button" value="All" onclick="download($cid, $asn->id, true)" title="Download all student files from Canvas (not zipped)."></td>
  <td><input type="button" value="New" onclick="download($cid, $asn->id, false)" title="Download ungraded student files from Canvas."></td>
  <td><a href="$asn->submissions_download_url" target="blank" title="Download all student files from Canvas packaged in a single zip archive. Requires login to Canvas.">Zip</a></td>
  <td><input type="file" onchange="runscript(this)" title="Select a script to execute."></td>
  <td><input type="button" value="Report" onclick="upload($cid, $asn->id, 'feedback')" title="Comments only for $asn->name"></td>
  <td><input type="button" value="Score" onclick="upload($cid, $asn->id, 'score')" title="Score points only for $asn->name"></td>
  <td><input type="button" value="Both" onclick="upload($cid, $asn->id, 'both')" title="Both comments and score for $asn->name."></td>
</tr>
EOD;
}

function execScript($filePath) {
    echo "Script: $filePath\n";
    if (!file_exists($filePath)) {
      echo "Script file does not exist!\nVerify Script location path matches selected file.\n";
      return; // ????? No function to return from
    }
    //Assume good script?
    if (substr($filePath, -4) === ".php") {
        // Execute PHP scripts
        $dir = dirname($filePath);
        $file = basename($filePath);
        $cwd = getcwd();
        $cmd = "/xampp/php/php.exe $filePath";
        //echo "Command: $cmd\n";
        $result = passthru($cmd);
    } else {
        // Execute shell scripts
        $result = passthru($filePath);
    }
    echo "$result\n";
}
function saveSettings($assoc_arr, $path) {

}
function getSettings($path) {
    return array(); // $assoc_arr
}
?>
