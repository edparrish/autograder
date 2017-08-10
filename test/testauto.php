<?php
// Simple test of automatic grading for Canvas.
// Need absolute paths in actual scripts for following three imports.
require_once("../grader.php");
require_once("../includes/foldermaker.php");
require_once("../includes/fileremover.php");
// Define required information
define("TEST_DIR", ROOT_DIR."/test/autofiles"); // path to student test folder
define("CID", "7193");  // Couse ID from Autograde Manager
define("AID", "76201"); // Assignment ID from Autograde Manager
if (!file_exists(TEST_DIR)) mkdir(TEST_DIR); // create TEST_DIR

$students = null;

/* Setup:
1. Set paths to three required system resources.
2. Set an absolute path to where to save student files and run tests.
3. Use Autograde Manager to find the course ID (CID) and assignment ID (AID)
   and enter these in the above defines.
4. Normally would schedule this script to execute every X minutes using cron (Linux, Mac), launchd (Mac) or Task Scheduler (Windows). However, not needed for this simple test.
*/

define("OVERALL_MSG", "
The above is your probable grade except for manually graded items.
The instructor reserves the right to adjust scores while manually
grading if an error is found in a robo grading script.
");

class GradeRunner extends Grader {
    // Setup test environment to run before any grading.
    function startTest() {
        echo "Deleting all prior folders in ".TEST_DIR.".\n";
        deleteAllFolders(TEST_DIR);
        downloadAssignments(CID, AID, TEST_DIR, false); // false=ungraded only
        makeFolders(TEST_DIR);
        removeFiles(TEST_DIR, array("*.[eE][xX][eE]", "*.o"));
        parent::startTest(); // make dirlist
    }

    // Put the tests here to grade students individually
    function test() {
        // Manually graded item like images or quizzes
        $score = $this->report(new ValueEvaluator(5),
            "Manually graded item (5):");
        $this->writeGradeLog(" -Assumed complete, will manually check.\n");

        // Test README.txt
        $this->run(new TestReadme($this));
        $readme = $this->getReadme();
        $score = $this->report(new ReadmeEvaluator(5), "README.txt Score (5):");

        // Subtotal score
        $subtotal = $this->getScore();
        $maxScore = $this->getMaxScore();
        $this->writeGradeLog("Subtotal Score: $subtotal of $maxScore\n");

        // Total score
        $this->reportOverall($maxScore, true, NULL, true);
        $this->writeGradeLog(OVERALL_MSG);
    }

    function postTest() {
        parent::postTest();
        assert(file_exists(TEST_DIR));
    }

    // Tear down test environment after all tests
    function finishTest() {
        uploadGrade(CID, AID, TEST_DIR, 'grade.log');
        echo "Finished uploading files.\n";
        parent::finishTest();
    }
}
// Run after file loaded
$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
