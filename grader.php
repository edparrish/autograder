<?php
set_time_limit(0); // keep scripts from timing out

require_once 'ag-config.php';
require_once ROOT_DIR.'/includes/db.php';
require_once ROOT_DIR.'/includes/dirlist.php';
require_once ROOT_DIR.'/evaluator.php';
require_once ROOT_DIR.'/filefinder.php';
require_once ROOT_DIR.'/filecontents.php';
require_once ROOT_DIR.'/includes/readme.php';
require_once ROOT_DIR.'/includes/testresult.php';
require_once ROOT_DIR.'/includes/util.php';
require_once ROOT_DIR.'/testcase.php';

define("ERROUT", ROOT_DIR.'/libs/errout.exe');
define("DEFAULT_LOG", "grade.log");
define("SUMMARY_LOG", "summary.log");
if (!defined("DEBUG")) define("DEBUG", false); // true to echo debug statements otherwise false

/**
    Runs tests on each student and creates a grade log.

    @author Edward Parrish
    @version 1.5 8/01/16
*/
class Grader {
    private $gradeLogName;
    private $gradeLogHandle;
    private $summaryLogHandle;
    private $timestamp;
    private $dir;
    private $hours = 0;
    private $countHours = 0;
    private $totalHours = 0;
    private $score = 0;
    private $maxScore = 0;
    private $countScore = 0;
    private $totalScore = 0;
    private $dl;  // directory list
    private $dit; // directory iterator
    private $sectionName;
    private $sectionNumber = 1; // For automatic section naming
    private $results;
    private $readme;
    private $firstName; // student's first name
    private $lastName;  // student's last name
    private $reportedName; // name student reported

    /**
     * Constructor
     */
    function Grader($testDir, $students = false, $gradeLogName = null) {
        $this->dl = new DirList($testDir, $students);
        chdir($testDir) or die("Could not change to directory: $testDir\n");
        if ($gradeLogName === null) {
            $this->gradeLogName = DEFAULT_LOG;
        } else {
            $this->gradeLogName = $gradeLogName;
        }
    }

    //***** Test Framework (Template) *****

    // Run test() in each student directory
    function runTest() {
        $this->startTest();
        $this->dit = $this->dl->iterator();
        while ($this->dit->hasNext()) {
            $this->dir = $this->dit->next();
            $this->preTest();
            $this->test();
            $this->postTest();
        }
        $this->finishTest();
    }

    // Setup test conditions
    function startTest() {
        $this->timestamp = time();
        if (file_exists(SUMMARY_LOG)) copy(SUMMARY_LOG, "summary.bak");
        if (!$this->summaryLogHandle = fopen(SUMMARY_LOG, "w")) {
            die("Cannot open ".SUMMARY_LOG);
        }
        fwrite($this->summaryLogHandle, "Test conditions:\n");
        fwrite($this->summaryLogHandle, "Path = ".$this->dl->getPath()."\n");
        fwrite($this->summaryLogHandle, "gradeLogName = $this->gradeLogName\n\n");
        echo $this->dl->count()." students:\n";

        $this->totalScore = 0;
        $this->countScore = 0;
        $this->totalHours = 0;
        $this->countHours = 0;
    }

    // Execute in each directory before test()
    function preTest() {
        $this->score = 0;
        $this->maxScore = 0;
        $this->sectionName = "Section $this->sectionNumber";
        $this->results = new TestResult();

        $dir = substr(strrchr($this->dit->current(), "/"), 1);
        echo ($this->countScore + 1)." $dir: ";
        fwrite($this->summaryLogHandle,
              ($this->countScore + 1)." $dir: ");

        if (file_exists($this->gradeLogName)) {
            copy($this->gradeLogName, "grade.bak");
        }
        if (!$this->gradeLogHandle = fopen($this->gradeLogName, "w")) {
            die("Cannot open $this->gradeLogName");
        }
        //Blackboard needs pre tags
        //fwrite($this->gradeLogHandle, "<pre>\n");
        $this->readme = new Readme(); // NTR: reconsider how to do this
        $studentName = $this->lookupStudentName();
        fwrite($this->gradeLogHandle, "Name: $studentName\n");
        $this->hours = $this->readme->getHoursClaim(); // move to post test?
    }

    // Test function to override in subclasses
    function test() {
        echo "Override the test function in subclasses ";
    }

    // Execute in each directory after test()
    function postTest() {
        $this->concatLogs();
        //fwrite($this->gradeLogHandle, "</pre>\n");
        fclose($this->gradeLogHandle);

        // Record statistics
        echo "$this->score in $this->hours hours\n";
        fwrite($this->summaryLogHandle, "$this->score in $this->hours hours\n");
        $this->totalScore += $this->score;
        $this->countScore++;
        $this->totalHours += $this->hours;
        if ($this->hours > 0) $this->countHours++;
    }

    // Tear down test environment
    function finishTest() {
        if ($this->countScore === 0) $this->countScore = 1;
        $msg = "\nAverage score: ".($this->totalScore / $this->countScore)."\n";
        echo $msg;
        fwrite($this->summaryLogHandle, $msg);
        $msg = "Average hours: ";
        if ($this->countHours !== 0) {
            $msg .= ($this->totalHours / $this->countHours);
        } else {
            $msg .= 0;
        }
        $msg .= " ($this->countHours students)\n";
        echo $msg;
        fwrite($this->summaryLogHandle, $msg);
        $msg = "Elapsed time: ".(time() - $this->timestamp)." seconds\n";
        echo $msg;
        fwrite($this->summaryLogHandle, $msg);
        fclose($this->summaryLogHandle);
    }


    //***** Accessor Functions *****

    /**
        Returns the current directory.
    */
    function getDir() {
        return $this->dir;
    }

    /**
        Returns the first name of the student.
    */
    function getFirstName() {
        return $this->firstName;
    }

    /**
        Returns the last name of the student.
    */
    function getLastName() {
        return $this->lastName;
    }

    /**
        Returns the reported name of the student.
    */
    function getReportedName() {
        return $this->reportedName;
    }

    /**
        Returns the Readme object for current directory.
    */
    function getReadme() {
        return $this->readme;
    }

    /**
        Returns the Readme file name.
     */
    function getReadmeName() {
        return $this->readme->getReadmeName();
    }

    /**
        Returns the accumulated score for the student.
     */
    function getScore() {
        return $this->score;
    }

    /**
        Returns the accumulated maximum possible score for the student.
     */
    function getMaxScore() {
        return $this->maxScore;
    }

    /**
        Returns true if the student claims extra credit, otherwise false.
     */
    function isExtraCredit() {
        return $this->readme->getExtraCreditClaim();
    }

    /**
        Returns true if the student claims pair programming, otherwise false.
     */
    function isPairProg() {
        return $this->readme->getPairProgClaim();
    }


    //***** Processing Functions (Tools) *****

    /**
        Runs $testCmd and logs the output in $outFile.

        @param $testCmd The test command to run.
        @param $outFile The file in which to save the output.
        @param $cond Whether or not to run the $testCmd.
        @param $timeout The max number of seconds to run before terminating.
        @return the output written to the log file
        NTR: does not timeout if held by cin statement.
     */
    function runLogCmd($testCmd, $outFile="out.log", $cond=true, $timeout=5) {
        if ($cond) {
            $info = "";
            $info = shell_exec_timed("$testCmd 2>&1", $timeout);
            if (!$handle = fopen($outFile, "w")) die("Cannot open $outFile");
            $label = "Results: ($outFile)";
            fwrite($handle, "*Command: $testCmd\n");
            fwrite($handle, "$label\n");
            //fwrite($handle, str_repeat("-", strlen($label)));
            fwrite($handle, "\n");
            fwrite($handle, $info);
            if (!trim($info)) fwrite($handle, "(No output)\n");
            fclose($handle);

            return $info;
        }
    }

    /**
    * Loads a CSV (comma-separated-variable) file into the database.
    *
    * @param $csvFileName The file to load.
    * @param $tableName The table name to load the data into.
    * @param $eraseData Set true to remove table and all data prior to loading.
    * @return true if the load was successful, otherwise false.
    */
    function loadCSV($csvFileName, $tableName, $eraseData=true) {
        $db = new DB();
        if ($eraseData) {
            $sql = "DROP TABLE IF EXISTS $tableName";
            $db->query($sql);
        }
        $end = $db->loadCSV($csvFileName, $tableName);
        return $end;
    }


    /**
        Load a CodeLab roster CSV file into a database table. Allows
        adjustment of the database after loading because students may use
        nicknames, etc.

        @param $csvFileName CodeLab roster CSV file pathname.
        @param $tableName Database table containing the exported data.
        @param $sqlFile Optional file of SQL statements to adjust the
               database table after loading.
     */
    function loadCodeLab($csvFileName, $tableName, $sqlFile="") {
        $this->loadCSV($csvFileName, $tableName);
        if ($sqlFile) {
            if (!file_exists($sqlFile)) {
                die("Error: $sqlFile does not exist (aborting)\n");
            }
            require(ROOT_DIR.'/includes/dbconvars.php');
            $info = `mysql -u$dbuser -p$dbpwd $dbname < $sqlFile`;
            if ($info) {
                die("Error loading sqlFile=$sqlFile: $info (aborting)\n");
            }
        }
    }

    /**
        Load a properties file of key = value pairs.

        @param $fileName The file to load from.
        @return a map (array) of key-value pairs
        NTR: never used!
     */
    function loadProperties($fileName) {
        require_once ROOT_DIR.'/lib/Properties.php';
        $p = new Properties();
        $p->load(file_get_contents($fileName));
        $config = $p->toArray();
        return $config;
    }

    /**
        Get all the plain files in a directory and its subdirectories.

        @return An array of the plain files.
        @deprecated. Use FileFinder instead.
     */
    function scanFilesRec($baseDir = ".") {
        $fileList = globr("*");
        for ($i = 0; $i < count($fileList); $i++) {
            if (is_dir($fileList[$i])) {
                unset($fileList[$i]);
            }
        }
        return array_values($fileList);
    }

    /**
        Writes the $message string to the grade log.
     */
    function writeGradeLog($message, $condition = true) {
        if ($condition) {
            fwrite($this->gradeLogHandle, $message);
        }
    }

    //***** File handling functions *****

    /**
        Finds a single file in the current directory that most closely
        matches the $fileName, $glob and $contentRegEx. If multiple files
        match, then the file within the closest distance (minimum number of
        character changes in the name) is selected.

        @param $fileName The name of the file.
        @param $glob The glob file pattern to search within.
        @param $contentREList A list of regex patterns of file contents.
        @return the closest matching file name.
     */
    function findClosestFile($fileName, $glob = "*", $contentREList = "") {
        $fileName = basename($fileName); // remove slashes
        $ff = new Filefinder($glob);
        $ff->removeDirs(); // added 3/14/2016
        $testFile = "";
        // No files from glob
        if ($ff->count() == 0) {
            return "";
        }
        // Only 1 file from glob
        if ($ff->count() == 1) {
            return $ff->findFirstFile();
        }
        // glob > 1 file but only 1 $fileName
        if ($fileName AND $ff->fileExists($fileName)) {
            $ff->filterName("/$fileName/i", true);
            if ($ff->count() == 1) return $ff->findFirstFile();
        }
        // More than one file with same name so filter for content
        $fileList = $ff->files();
        if ($contentREList) $ff->filterContents($contentREList, true);
        if ($ff->count() == 0) {
            $ff->addFiles($fileList);
        }
        // Only 1 file after filtering for content
        if ($ff->count() == 1) {
            return $ff->findFirstFile();
        }
        // Still multiple files
        $testFile = $ff->findClosestFile($fileName);
        echo "Selecting closest file: $testFile for $fileName\n";
        //echo "Looking for $fileName and found '$testFile'\n";
        return $testFile;
    }

    /**
        Finds a single file in the current directory that most closely
        matches the $fileName, $glob and $contentREList. If multiple files
        match, then the file with the largest size is selected.

        @param $fileName The name of the file.
        @param $glob The glob file pattern to search within.
        @param $contentREList A list of regex patterns of file contents.
        @return the largest matching file name.
     */
    // NTR: would be nice to have an excluded filenames list.
    function findLargestFile($fileName, $glob = "*", $contentREList = "") {
        $fileName = basename($fileName); // remove slashes
        $ff = new Filefinder($glob);
        $testFile = "";
        if ($ff->count() == 1) {
            $testFile = $ff->findFirstFile();
        } else if ($fileName AND $ff->fileExists($fileName)) {
            $ff->filterName("/$fileName/i", true);
            $testFile = $ff->findFirstFile();
        } else if ($ff->count() >= 1) {
            $fileList = $ff->files();
            if ($contentREList) $ff->filterContents($contentREList, true);
            if ($ff->count() == 0) {
                $ff->addFiles($fileList);
            }
            if ($ff->count() == 1) {
                return $ff->findFirstFile();
            }
            $testFile = $ff->findLargestFile();
            echo "Selecting largest file: $testFile for $fileName\n";
        }
        //echo "Looking for $fileName and found '$testFile'\n";
        return $testFile;
    }

    //***** TestCase control functions *****

    /**
        Run a TestCase and record test items in $this->results.

        @param $testCase The TestCase to run.
        @param $condition Whether or not to run the TestCase.
        @return true if the TestCase ran and passed, otherwise false.
     */
    function run($testCase, $condition = true) {
        $pass = false;
        if ($condition) {
            $pass = $testCase->run($this->results, $this->sectionName);
        }
        return $pass;
    }

    /**
        Run a TestCase and add points plus a message if the test passes.

        @param $testCase The TestCase to run.
        @param $value The points to add when the TestCase passes.
        @param $msg The message to add when the TestCase passes.
        @param $condition Whether or not to run the TestCase.
        @return true if the TestCase ran and passed, otherwise false.
     */
    function pass($testCase, $value, $msg = "", $condition = true) {
        $pass = false;
        if ($condition) {
            $pass = $testCase->run($this->results, $this->sectionName);
            if ($pass) {
                $name = "pass".$testCase->getTestName();
                $this->results->add($this->sectionName, $name, $msg, $value);
            }
        }
        return $pass;
    }

    /**
        Run a TestCase and add points plus a message if the test fails.

        @param $testCase The TestCase to run.
        @param $value The points to add when the TestCase fails.
        @param $msg The message to add when the TestCase fails.
        @param $condition Whether or not to run the TestCase.
        @return true if the TestCase ran and passed, otherwise false.
     */
    function fail($testCase, $value, $msg = "", $condition = true) {
        $pass = false;
        if ($condition) {
            $pass = $testCase->run($this->results, $this->sectionName);
            if (!$pass) {
                $name = "fail".$testCase->getTestName();
                $this->results->add($this->sectionName, $name, $msg, $value);
            }
        }
        return $pass;
    }

    /**
        Run a TestCase and add points and a message if the test passes.

        @param $testCase The TestCase to run.
        @param $passPts The points to apply when the TestCase passes.
        @param $passMsg The message to post when the TestCase passes.
        @param $failPts The points to apply when the TestCase fails.
        @param $failMsg The message to post when the TestCase fails.
        @param $condition Whether or not to run the TestCase.
        @return true if the TestCase ran and passed, otherwise false.
     */
    function passFail($testCase, $passPts, $passMsg, $failPts, $failMsg, $condition = true) {
        if ($condition) {
            $pass = $testCase->run($this->results, $this->sectionName);
            if ($pass) {
                $name = "pass".$testCase->getTestName();
                $this->results->add($this->sectionName, $name, $passMsg, $passPts);
            } else {
                $name = "fail".$testCase->getTestName();
                $this->results->add($this->sectionName, $name, $failMsg, $failPts);
            }
        }
        return $pass;
    }


    /**
        Set the name of the section.

        @param $newName The new section name.
    */
    function setSectionName($newName) {
        $this->sectionName = $newName;
    }

    /**
        Get the name of the current section.

        @return The current section name.
    */
    function getSectionName() {
        return $this->sectionName;
    }

    /**
        Get the count of the current section.

        @return The current section count.
    */
    function getSectionResultsCount() {
        return $this->results->count($this->sectionName);
    }

    /**
        Get the sum of the values of the current section.

        @return The current section value sum.
    */
    function getSectionValueSum() {
        return $this->results->sumValues($this->sectionName);
    }

    /**
        Remove test results for the $sectionName or the entire list if
        no $section name is specified.

        @param $sectionName The optional section name.
     */
    function resetTestResults($sectionName = "") {
        if ($sectionName === "") $sectionName = $this->sectionName;
        $this->results->reset($sectionName);
    }

    /**
        Returns the value of a property from test results or NULL if not set.

        @param $name The name of the property.
        @param $default The value to return if not set.
        @return the value of a property or $default if not set.
     */
    function getProperty($name, $default = NULL) {
        $result = $this->results->getProperty($name);
        if ($result === NULL) $result = $default;
        return $result;
    }

    /**
        Sets the value of a property in $this->results.

        @param $name The name of the property.
        @param $value The value of the property.
     */
    function setProperty($name, $value) {
        $this->results->setProperty($name, $value);
    }

    /**
        Removes a property and its value from test results.

        @param $name The name of the property.
     */
    function removeProperty($name) {
        $this->results->removeProperty($name);
    }

    /**
        Returns true if a message matches the $pattern regular expression.

        @param $pattern The regular expression to use in the search.
        @since 5/14/2017
     */
    function isErrorMessageRE($pattern) {
        return $this->results->messageExistsRE($pattern);
    }

    /**
        Returns true if an error message matches any part of the $message string.

        @param $message The string to use in the search.
        @param $caseMatters Set true if case matters.
        @since 5/14/2017
     */
    function isErrorMessage($message, $caseMatters = false) {
        return $this->results->messageExists($message, $caseMatters);
    }

    protected function getResults() {
        return $this->results;
    }

    //***** Reporting Functions *****

    /**
        Run an evaluator and report the results in the log file along with
        a list of comments from the list of test results.

        @param $evaluator The Evaluator for generating the score.
        @param $label A brief description of the score.
        @param $points Set true to list points with each TestResult message.
        @param $sectionName The name of the section to evaluate.
        @return the score.
    */
    function report($evaluator, $label, $points = false, $sectionName = "") {
        if (!$sectionName) $sectionName = $this->sectionName;
        $score = $evaluator->score($this->results, $sectionName);
        $this->score += $score;
        $this->maxScore += $evaluator->getMaxScore();
        $report = "$label $score\n";
        $count = 0;

        $it = $this->results->iterator();
        while ($it->hasNext()) {
            $item = $it->next();
            if ($item->getSectionName() === $sectionName) {
                $count++;
                $msg = $item->getMessage();
                $value = "";
                if ($points and $item->getValue() !== 0) {
                    $value = abs($item->getValue()).":";
                }
                if ($msg) { // Ignore empty messages
                    if ($item->getValue() <= 0) {
                        $report .= " -$value$msg\n";
                    } else {
                        $report .= " +$value$msg\n";
                    }
                }
            }
        }
        if (DEBUG == "true" and $count === 0) {
            echo "\nDEBUG WARNING: No TestResults for section: $sectionName\n";
        }
        fwrite($this->gradeLogHandle, $report);
        $this->sectionNumber++;
        $this->sectionName = "Section $this->sectionNumber";
        return $score;
    }

    /**
        Returns a comment from a list based on a percentage.

        @param $percentage The percentage used to select the comment.
        @param $comments A sparse array where the integer index is the minimum
        percentage and the string value is a comment for the percentage. See
        reportOverall() code for an example.
        @return the comment from the list of comments.
    */
    function commentFromPercentage($percentage, $comments) {
        krsort($comments);
        foreach ($comments as $key => $comment) {
            if ($percentage >= $key) {
                return $comment;
            }
        }
    }

    /**
        Writes the total score and a comment to the grade log based on the
        percentage derived from the current score divided by the $maxScore.

        @param $maxScore The percentage used to select the comment.
        @param $showPercent: Set true to show percentage
        @param $comments A sparse array where the integer index is the minimum
        percentage and the string value is a comment for the percentage. If
        no comments are supplied then uses a default set.
        @return the calculated percentage as an integer value.
    */
    function reportOverall($maxScore, $showPercent = false, $comments = NULL, $showMax = false) {
        if ($comments === NULL) {
            $comments = array(
                100=>"Truly superior work!",
                90=>"Excellent work!",
                85=>"Very good work with a few minor problem areas.",
                80=>"Overall good work with some problems.",
                85=>"Very good work with a few minor problem areas",
                70=>"Satisfactory work with some problem areas.",
                60=>"Passable work with problems.",
                0=>"Please see me for help on assignments."
            );
        }
        $percentage = round($this->score / $maxScore * 100);
        $overallComment = $this->commentFromPercentage($percentage, $comments);
        $msg = "Total Score: $this->score";
        if ($showPercent) {
            $msg .= " ($percentage%)";
        }
        if ($showMax) $msg .= " out of $maxScore";
        $msg .= "\n\n$overallComment\n";
        fwrite($this->gradeLogHandle, $msg);
        return $percentage;
    }


    //***** Utility Functions (Not directly called) *****

    // Add all log files to the main log file
    function concatLogs() {
        $fileList = glob("*.log");
        if (count($fileList) > 1) { // do not count the grade.log
            fwrite($this->gradeLogHandle,
                "\n\n*** More Information ***\n");
        }
        foreach($fileList as $file) {
            $path = pathinfo($file);
            if (strtolower($file) !== $this->gradeLogName) {
                $contents = file_get_contents($file);
                fwrite($this->gradeLogHandle, "\n".$contents);
                // sometimes unlink gives warning for compile.log in cpp
                if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
                    `del $file`; // windows only workaround
                } else {
                    unlink($file) or print "Could not unlink: $file\n";
                }
            }
        }
    }

    /**
     * Lookup the student's name reported by the system.
     *
     * @return The student's full name as reported by the system.
     */
    // Note: Canvas concatenates last+first so cannot cleanly extract
    function getSystemNameForStudent() {
        // Since WebCT 6, registered name is embedded in the directory
        $dirName = basename($this->dir);
        //$pat = "/.*(\w+\s+\w+)\s+-\s+\d+$/iU";
        //preg_match($pat, $dirName, $matches);
        //$fullName = $matches[1];
        //return $fullName;
        return $dirName;
    }

    /**
     * Lookup the students name.
     *
     * @return The student's name or ID if no name is found.
     */
    function lookupStudentName() {
        // Clear prior names
        $this->firstName = "";
        $this->lastName = "";
        $this->reportedName = "";
        $baseName = basename($this->dir);

        // Since WebCT 6, registered name is embedded in the directory
        $pat = "/.*(\w+)\s+([\w-]+)\s+-\s+\d+$/iU";
        $matches = "";
        preg_match($pat, $baseName, $matches);
        if ($matches) {
            $this->firstName = trim($matches[1]);
            $this->lastName = trim($matches[2]);
            if ($this->lastName) {
                $this->reportedName = trim($this->firstName." ".$this->lastName);
            }
        }

        // Since Blackboard 9, registered name is in the comments file
        // Added 2/20/2012
        $commentsFile = "SubmissionComments.txt";
        if (fileExists("SubmissionComments.txt")) {
            $contentList = file($commentsFile);
            //$pat = '/Name:\s*(\w+)\s+(\w+)\s+\(/i'; // Blackboard 9.1
            $pat = '/Name:\s*(\w+)\s+([ \w-]+)/i'; // Canvas 2/8/16, 2/24/16
            if (preg_match($pat, $contentList[0], $matches)) {
                $this->firstName = trim($matches[1]);
                $this->lastName = trim($matches[2]);
                if ($this->lastName) {
                    $this->reportedName = trim($this->firstName." ".$this->lastName);
                }
            }
        } else {
            echo " (No $commentsFile found) ";
        }

        // Student reported name in their style
        $studentName = "";
        if (fileExists("[Rr][Ee][Aa][Dd][Mm][Ee]*")) {
            $studentName = trim($this->readme->getStudentName());
        }
        if ($studentName == ":") $studentName = ""; // added 12/20/2013

        // Look in code files for reported name
        if (!$studentName) {
            $cppFiles = glob("*.cpp");
            $javaFiles = glob("*.java");
            $fileList = array_merge($cppFiles, $javaFiles);
            $pattern = '/\@\s*author(:)?\s+(\S+.*$)/i';
            foreach($fileList as $file) {
                $contentList = file($file);
                foreach ($contentList as $line) {
                    if (preg_match($pattern, $line, $parts)) {
                        if (isset($parts[2])
                        // added following condition 5/17/2016
                        && strpos($parts[2], 'Your name') === false) {
                            $studentName = trim($parts[2]);
                            break;
                        } else if (isset($parts[1]) and $parts[1] != ":") {
                            $studentName = trim($parts[1]);
                            break;
                        }
                    }
                }
            }
        }

        // End of highly likely reported names, so save it
        if ($studentName && !strcmp($studentName, "(Your name here)")) {
            $this->reportedName = $studentName;
        }

        // Return first possible line of README
        if (!$studentName) {
            $readmeName = $this->readme->getReadmeName();
            if ($readmeName) {
                $contentList = file($readmeName);
                foreach ($contentList as $line) {
                    $line = trim($line);
                    $line = substr($line, 0, 80);
                    if (preg_match("/^[a-z ,.]{2,}/i", $line)) {
                        if (strpos($this->lastName, $line) !== FALSE) {
                            $studentName = $line;
                            break;
                        }
                    }
                }
            }
        }

        if (!$studentName) {
            if (trim($this->reportedName) !== "") {
                $studentName = $this->reportedName;
            } else {
                // No reported name so use directory name
                $studentName = $baseName;
            }
        }

        // Derive first and last names if not already available
        // Uses directory name as last name if nothing else available
        if ($studentName and !$this->lastName) {
            $names = preg_split("/[\s,_]+/", $studentName);
            $count = count($names);
            if (!$this->firstName and $count > 1) $this->firstName = $names[0];
            if (!$this->lastName) $this->lastName = $names[$count - 1];
        }

        // Last chance to save something
        if(trim($this->reportedName) === "") {
            $this->reportedName = $studentName;
        }

        // Return what is hopefully the best reported name
        return $this->reportedName;
    }
}
?>
