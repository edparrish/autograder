<?php
/**
    All the test cases as TestCase and subclasses of TestCase.
    @author Edward Parrish
    @version 1.0 07/15/04
    @version 1.4 05/15/16
    @version 1.5 06/19/17
Index to all test cases:
TestCase: Superclass of all tests.
TestCodeLab($tableName, $lastName, $firstName)
TestCompileCPP($pathName, $cmd = "")
TestCompileJava($cmd="javac *.java", $log="compile.log", $msg="", $clean=true)
TestCondition($condition, $msg = "", $value = 0)
TestFileExists($globList, $rec = true, $startDir = ".")
*TestMatch($patList, $fileList) //all patterns exists in every file
*TestMatchAny($patList, $fileList)//any pattern exists in any file
*TestMatchCount($patList, $fileList, $min=0, $max=0)
*TestCompareFiles($file1, $file2, $ignoreCase=false)
TestCompareSQL($sql1, $sql2, $dbName="test")
TestExtraCreditClaim(&$grader)
TestJavaUnit($testFile, $value=1, $logFile="unit.log", $clean=true)
TestLoadDB($dbFile, $log="DBload.log")
TestPairProgClaim(&$grader)
TestRunLogSQL($sql, $outFile="out.log", $dbName="test")
TestRunPage($pageName, $outFileName = "out.html")
*TestStyleCPP($fileList, $config=null, $summarize=true, $logFile="style.log")
TestStyleJava($configFile=CHECK_STD, $glob="*.java")--old CheckStyle
TestStylePHP($globList = "*.php", $log = "style.log")--minimal
*TestReadme(&$grader)
TestValidateHTML($globList="*.html", $log="validate.log")--old
Note: Parameter lists ordered to allow those with default values at end.
*=recently updated
TestCase ideas:
Separate by language type and common
TestCompareFilesLevenshtein
TestCompareFilesSimilarText: O(n^3)
TestDiff: returns % same? array_diff
*/
require_once 'ag-config.php';
require_once 'filecontents.php';
require_once ROOT_DIR.'/includes/util.php';
// see defines near each test case
/**
 * Superclass for all test-case classes.
 * Provides a common interface for the grader.
 */
class TestCase {
    var $testName = "TestCase";
    /**
        Call to run the test and collect results. Adds TestResult objects
        to the $testResultList.
        @param $testResult The container for storing test results.
        @param $sectionName The name of the section.
        @return true if the test passes, otherwise returns false.
     */
    function run(&$testResult, $sectionName) {
        assert(is_object($testResult));
        if (get_class($testResult) !== "TestResult") {
            die("testResult is not a TestResult:".get_class($testResult));
        }
        return $this->runTest($testResult, $sectionName);
    }
    /**
        Runs the actual test case and save test results.
        Implement the test in this function but call with function run().
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the test passes, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        echo "Override runTest() in subclasses";
        return false;
    }
    /**
        Return the name of the test.
        @param $newName The name of the test.
     */
    function getTestName() {
        return $this->testName;
    }
    /**
        Change the name of the test.
        @param $newName The name of the test.
     */
    function setTestName($newName) {
        if (!is_string($newName)) {
            die("newName is not a string!");
        }
        $this->testName = $newName;
    }
    /**
        Delete (unlink) all files listed in the $fileList.
        @param $fileList The file list to delete.
        NTR: A Util function?
     */
    function _deleteFiles($fileList) {
        foreach($fileList as $file) {
            unlink($file) or print "In TestCase could not delete $file\n";
        }
    }
}
/**
    Tests CodeLab exercise completions based on a roster previously
    loaded into the database.
 */
class TestCodeLab extends TestCase {
    var $tableName;
    var $lastName;
    var $firstName;
    /**
        Constructor with parameters for the test.
        @param $tableName Database table containing the exported data.
        @param $lastName Student's last name.
        @param $firstName Student's first name.
    */
    function TestCodeLab($tableName, $lastName, $firstName) {
        if (trim($tableName) === "") die("TestCodeLab: No tableName\n");
        $this->tableName = $tableName;
        if (trim($lastName) === "") echo "TestCodeLab: No lastName\n";
        $this->lastName = $lastName;
        if (trim($firstName) === "") echo "TestCodeLab: No firstName\n";
        $this->firstName = $firstName;
    }
    /**
        Queries the database of CodeLab exercise completions for an
        individual student.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the student has any CodeLab records, otherwise
        returns false
     */
    function runTest(&$tr, $sectionName) {
        $db = new DB();
        $tableName = $this->tableName;
        $firstName = $this->firstName;
        $lastName = $this->lastName;
        // Most specific query
        $sql = "
            SELECT * FROM $tableName
                WHERE LastName='$lastName'
                AND FirstName='$firstName'
            ";
        $result = $db->query($sql);
        // If people use a short name
        if ($result->num_rows === 0) {
            if ($firstName) {
                $sql = "
                    SELECT * FROM $tableName
                        WHERE LastName='$lastName'
                        AND FirstName LIKE '$firstName[0]%'
                    ";
            } else {
                $sql = "
                    SELECT * FROM $tableName
                        WHERE LastName='$lastName'
                    ";
            }
            $result = $db->query($sql);
        }
        // Missing data
        $missingCodeLab = 0;
        if ($result->num_rows === 0) {
            echo "TestCodeLab: no data for $firstName $lastName. ";
            $missingCodeLab = 1;
        }
        // Need human intervention
        if ($result->num_rows > 1) {
            echo "\nTestCodeLab: more than 1 row found in $tableName\n";
            while ($row = mysqli_fetch_assoc($result)) {
                foreach ($row as $field) {
                    echo "$field\n";
                }
            }
            die("Stopping so you can fix the problem in TestCodeLab\n");
        }
        $totalProblems = 0;
        $correctOnTime = 0;
        $correctLate = 0;
        $incorrect = 0;
        $prefix = "0";
        $row = mysqli_fetch_assoc($result);
        if ($row == false) {
            // No student data but can count number of exercises
            $sql = "DESCRIBE $tableName";
            $result = $db->query($sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $field = trim($row["Field"]);
                if (strpos($field, $prefix) === 0) {
                    $totalProblems++;
                }
            }
        } else {
            // Tabulate data
            foreach ($row as $name=>$value) {
                $name = trim($name);
                $value = strtoupper(trim($value));
                if (strpos($name, $prefix) === 0) {
                    $totalProblems++;
                    if ("C" == $value) {
                        $correctOnTime++;
                    } elseif ("L" == $value) {
                        $correctLate++;
                    } elseif ("X" == $value) {
                        $incorrect++;
                    }
                }
            }
        }
        //var_dump($totalProblems, $correctOnTime, $correctLate, $incorrect);
        $tr->setProperty("totalProblems", $totalProblems);
        $tr->setProperty("correctOnTime", $correctOnTime);
        $tr->setProperty("correctLate", $correctLate);
        $tr->setProperty("incorrect", $incorrect);
        $tr->setProperty("missingCodeLab", $missingCodeLab);
        return !$missingCodeLab;
    }
}
/**
    Compares two files line by line. Use FileContents methods to trim whitespace and filter text before comparison as needed.
    Returns true if all lines match exactly, otherwise returns false.
*/
class TestCompareFiles extends TestCase {
    private $file1;
    private $file2;
    private $ignoreCase;
    /**
        Constructor with parameters for the test.
        @param $file1 First file to compare.
        @param $file2 Second file to compare.
        @param $ignoreCase true for case-insensitive comparison. (strcasecmp)
     */
    function TestCompareFiles($file1, $file2, $ignoreCase=false) {
        $this->testName = get_class();
        $fileList = FileContents::toFileContents($file1);
        $this->file1 = $fileList[0];
        if (sizeof($fileList) > 1) {
            $name = $this->file1->getName();
            echo "TestCompareFiles: mutiple files found, choosing first: $name\n";
        }
        $fileList = FileContents::toFileContents($file2);
        $this->file2 = $fileList[0];
        if (sizeof($fileList) > 1) {
            $name = $this->file2->getName();
            echo "TestCompareFiles: mutiple files found, choosing first: $name\n";
        }
        $this->ignoreCase = $ignoreCase;
    }
    /**
        Runs the actual test and returns results in a TestResult.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if all lines match, otherwise returns false.
    */
    function runTest(&$tr, $sectionName) {
        if (!$this->file1->fileExists()) {
            $msg = "File 1 does not exist: ".$this->file1->getName();
            echo("TestCompareFiles--$msg\n");
            //if ($this->showErrors) { // 10/1/2017
                $tr->add($sectionName, $this->testName, $msg, 0);
            //}
            return false;
        }
        if (!$this->file2->fileExists()) {
            $msg = "File 2 does not exist: ".$this->file2->getName();
            echo("TestCompareFiles--$msg\n");
            //if ($this->showErrors) { // 10/1/2017
                $tr->add($sectionName, $this->testName, $msg, 0);
            //}
            return false;
        }
        $lines1 = $this->file1->toArray();
        $lines2 = $this->file2->toArray();
        $linesFile1 = count($lines1);
        $linesFile2 = count($lines2);
        if ($linesFile1 !== $linesFile2) {
            $msg = "Number of lines mismatch $linesFile1 vs $linesFile2";
            $tr->add($sectionName, $this->testName, $msg, 0); //9/2/17
            return false;
        } else {
            for ($i = 0; $i < $linesFile1; $i++) {
                $lineFile1 = $lines1[$i];
                $lineFile2 = $lines2[$i];
                if (!$this->ignoreCase && strcmp($lineFile1, $lineFile2)) {
                    $msg = "Line $i does not match";
                    $msg .= ": ".$lines1[$i]."|".$lines2[$i]; //11/28/16
                    $tr->add($sectionName, $this->testName, $msg, 0);
                    return false;
                } else if (strcasecmp($lines1[$i], $lines2[$i])) {
                    $msg = "Line $i does not match";
                    $msg .= ":".$lines1[$i]."|".$lines2[$i]; //11/28/16
                    $tr->add($sectionName, $this->testName, $msg, 0);
                    return false;
                }
            }
        }
        return true;
    }
}
/**
    Tests if the output of two SQL statements match.
*/
class TestCompareSQL extends TestCase {
    var $dbName;
    var $sql1;
    var $sql2;
    /**
        Constructor with parameters for the test.
        @param $sql1 One SQL statement to compare.
        @param $sql2 Another SQL statement to compare.
        @param $dbName The database on which to run both SQL statements.
    */
    function TestCompareSQL($sql1, $sql2, $dbName = NULL) {
        $this->testName = get_class();
        $this->sql1 = $sql1;
        $this->sql2 = $sql2;
        $this->dbName = $dbName;
    }
    /**
        Tests if the output of two SQL statements match.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if both outputs match, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        require ROOT_DIR.'/includes/dbconvars.php';
        if ($this->dbName) $dbname = $this->dbName; // replace dbconvars
        if (!$this->sql1) die("sql1 not specifed\n");
        if (!$this->sql2) die("sql2 not specifed\n");
        $info1 = "";
        $sql1 = preg_replace("/\s+/", " ", $this->sql1);
        if (preg_match("/DROP|CREATE|INSERT|ALTER|UPDATE|DELETE/i", $sql1)) {
            $msg = "SQL1 trying to alter the database";
            $tr->add($sectionName, $this->testName, $msg, 0);
            return false;
        }
        if ($this->sql1) {
            $cmd = "mysql -u$dbuser -p$dbpwd -t -e\"$sql1\" $dbname 2>&1";
            $info1 = `$cmd`;
            if (strpos($info1, 'ERROR') !== false) {  // 7/10/17
                $msg = "Error in first compare sql: ".strtok($info1, ":");
                $tr->add($sectionName, $this->testName, $msg, 0);
                return false;
            }
        }
        $info2 = "";
        $sql2 = preg_replace("/\s+/", " ", $this->sql2);
        if (preg_match("/DROP|CREATE|INSERT|ALTER|UPDATE|DELETE/i", $sql2)) {
            $msg = "SQL2 trying to alter the database";
            $tr->add($sectionName, $this->testName, $msg, 0);
            return false;
        }
        if ($this->sql2) {
            $info2 = `mysql -u$dbuser -p$dbpwd -t -e"$sql2" $dbname 2>&1`;
            if (strpos($info2, 'ERROR') !== false) {  // 7/10/17
                $msg = "Error in second compare sql: ".strtok($info2, ":");
                $tr->add($sectionName, $this->testName, $msg, 0);
                return false;
            }
        }
        if (strcasecmp($info1, $info2)) {
            return false;
        }
        return true;
    }
    /**
     * Normalizes output lines before comparison.
     *
     * @param $lines The array of strings to normalize.
     * @return The normalized array of strings.
     */
    function normalize($lines) {
        $result = array();
        foreach($lines as $line) {
            $line = trim($line);
            // convert muli-whitespace to single space
            $line = preg_replace("/\s+/", " ", $line);
            if ($line) {
                $result[] = $line;
            }
        }
        return $result;
    }
}
/**
    Compiles C++ programs using a specified command string and records
    errors and warnings in the list of TestResults. Records if the compile
    command succeeded as the property "compiles". Also records the number
    of warnings the compiler reported as as the property "warnings".
 */
class TestCompileCPP extends TestCase {
    private $cmd;
    private $log;
    private $msg;
    private $pathName;
    /**
        Constructor with parameters for the test.
        @param $pathName The path and name of the file to compile.
        @param $cmd The command string to execute, overridding the default. Use this parameter to invoke makefiles as well.
     */
    function TestCompileCPP($pathName, $cmd = "") {
        $this->testName = get_class();
        $this->pathName = $pathName;
        $this->cmd = $cmd;
        $this->path = dirname($pathName);
        if ($this->path == NULL) $this->path =".";
        $base = basename($pathName, ".cpp");
        $this->log = "compile$base.log";
        $this->msg = "Compiling $base.cpp";
    }
    /**
        Compiles C++ file using a default command or the one specified in the
        constructor.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the code compiles, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $tr->setProperty("compiles", false); // pessimistic
        // Open log file and write header
        if (!$handle = fopen($this->log, 'ab')) {
            die("Cannot open compile.log");
        }
        if ($this->msg) {
            fwrite($handle, "$this->msg\n");
        }
        //
        $testFile = $this->pathName;
        $testBase = basename($testFile, ".cpp"); // file of file.ext
        // Strip .cpp if student has multiple extensions
        $base = $testBase;
        while (strrpos($base, ".cpp")) {
            $base = basename($base, ".cpp");
        }
        // Remove compiled files in case rerunning script or student submitted
        $this->_deleteFiles(glob("$base*.exe"));
        $this->_deleteFiles(glob("$base*.o"));
        // Compile files
        $cmd = $this->cmd;
        if ($cmd === '') {
            $cmd = "g++ -Wall -Wextra -o $base $testBase.cpp";
        }
        fwrite($handle, "Compiled with: $cmd\n");
        // Compile sequence
        $cwd = getcwd();
        $path = dirname($testFile);
        if ($path == NULL) $path =".";
        chdir($path); // In case of spaces in dir names
        if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
            $errout = ERROUT;  // errout collects info from Windows
            $info = `$errout $cmd`; // run at Windows command line
        } else {
            $info = `$cmd 2>&1`; // run at command line
        }
        $info = trim($info);
        chdir($cwd); // return to original working dir
        // Collect errors and warnings
        fwrite($handle, "*Compiler Output\n");
        fwrite($handle, $info);
        $errors = substr_count($info, "error:");
        if (substr_count($info, "No such file or directory") != 0) {
            $msg = "Compiler cannot find your .cpp file (spaces in pathname or bad include?)";
            fwrite($handle, "\n\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if (substr_count($info, "no input files") != 0) {
            $msg = "Did not find the CPP file";
            //fwrite($handle, "\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if (substr_count($info, "argument to `-o' missing") != 0) {
            $msg = "No files to compile";
            fwrite($handle, "\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if (substr_count($info, "ld returned 1") != 0) {
            $msg = "Did not compile due to linker errors";
            fwrite($handle, "\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if (substr_count($info, "Stop.") != 0) {
            $msg = "Did not compile due to Makefile errors";
            fwrite($handle, "\n\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("compiles", false);
        } else if ($errors != 0) {
            $msg = "Errors found during compile (x$errors)";
            fwrite($handle, "\nTotal errors: $errors\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                && !fileExists("$path/$base.exe")) {
            $msg = "Did not create an executable file";
            fwrite($handle, "\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN'
                && !fileExists("$path/$base")) {
            $msg = "Did not create an executable file";
            fwrite($handle, "\n$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else {
            if ($info) fwrite($handle, "\n\n");
            fwrite($handle, "No errors during compile\n");
            $tr->setProperty("compiles", true);
        }
        $warnings = substr_count($info, "warning");
        $tr->setProperty("warnings", $warnings);
        if ($warnings) {
            $msg = "Warnings found during compile (x$warnings)";
            fwrite($handle, "Total warnings: $warnings\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        }
        // Clean up
        fclose($handle) or print "Could not close file: $this->log\n";
        return $tr->getProperty("compiles"); // If it compiles, it passed.
    }
}
/**
    Compiles Java programs using a specified command string and records
    errors and warnings in the list of TestResults. Also records if the
    compile command succeeded as the property "compiles". In addition,
    records the number of warnings the compiler reported as as the property
    "warnings".
    NTR: Need to allow passing a list of files to compile.
 */
class TestCompileJava extends TestCase {
    var $cmd;
    var $log;
    var $msg;
    var $clean;
    /**
        Constructor with parameters for the test.
        @param $cmd The command string to execute.
        @param $log The file in which to save the compiler results.
        @param $msg A message to write at the beginning of the log file.
        @param $clean Set true to delete any .exe and .o files first.
        NTR: Can replace many of these params by changing log writing methods
     */
    function TestCompileJava($cmd="javac *.java", $log="compile.log", $msg="", $clean=true) {
        $this->testName = get_class();
        if (!$cmd) die("No command specified!\n");
        if (substr($cmd, 0, 5) != "javac") die("Bad compile command: $cmd\n");
        $this->cmd = $cmd;
        $this->log = $log;
        $this->msg = $msg;
        $this->clean = $clean;
    }
    /**
        Compiles Java programs using the command specified in the constructor.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the code compiles, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $tr->setProperty("compiles", false); // pessimistic
        $logExists = file_exists("compile.log");
        // Open log file and write header
        if (!$handle = fopen($this->log, 'ab')) {
            die("Cannot open $this->log");
        }
        if ($this->msg) {
            fwrite($handle, "$this->msg\n");
        } else if (!$logExists) {
            fwrite($handle, "*javac Output\n");
        }
        // Remove old files in case student submitted compiled files
        if ($this->clean) {
            $this->_deleteFiles(glob("*.class"));
        }
        // Compile files
        fwrite($handle, "Command: $this->cmd\n");
        if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
            $errout = ERROUT;  // errout collects info for Windows
            $info = `$errout $this->cmd`;
        } else {
            $info = `$this->cmd 2>&1`;
        }
        $info = trim($info);
        fwrite($handle, $info."\n");
        if (substr_count($info, "Usage: javac") != 0) {
            //echo "Bad or incomplete compiler command: $this->cmd\n$info\n";
            fwrite($handle,"Bad or incomplete compiler command.\n");
        }
        // Collect errors and warnings
        preg_match("/(\d+)\s+error|command/", $info, $matches);
        $errors = 0;
        if (isset($matches[1])) $errors = $matches[1];
        if (substr_count($info, "file not found") != 0) {
            $msg = "No files to compile";
            fwrite($handle, "$msg.\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        } else if ($errors != 0) {
            $msg = "Errors found during compile (x$errors)";
            $tr->add($sectionName, $this->testName, $msg, 0);
//        } else if (strlen($info) != 0) { // removed 11/3/17
//            $msg = "Problems running $this->cmd\n$info";
//            $tr->add($sectionName, $this->testName, $msg, 0);
        } else {
            $msg = "No errors during compile\n";
            fwrite($handle, $msg);
            $tr->setProperty("compiles", true);
        }
        $warnings = substr_count($info, "warning");
        $tr->setProperty("warnings", $warnings);
        if ($warnings) {
            $msg = "Warnings found during compile (x$warnings)";
            fwrite($handle, "Total warnings: $warnings\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
        }
        $notices = substr_count($info, "Note:");
        $tr->setProperty("notices", $notices);
        if ($notices) {
            $msg = "Notes found during compile";
            $tr->add($sectionName, $this->testName, $msg, 0);
        }
        // Added following 11/3/17
        if (!$errors && !$warnings && !$notices && strlen($info) != 0) {
            $msg = "Problems running $this->cmd\n$info";
            $tr->add($sectionName, $this->testName, $msg, 0);
        }
        // var_dump($msg);
        // Clean up
        fclose($handle) or print "Could not close file: compile.log\n";
        return $tr->getProperty("compiles"); // If it compiles, it passed.
    }
}
/**
    Adds a message if the $condition evaluates to true and the message
    or value is not empty or 0 respectively.
 */
class TestCondition extends TestCase {
    private $condition;
    private $msg;
    private $value;
    /**
        Constructor with parameters for the test.
        @param $condition A value of true or false.
        @param $msg The message to add when the condition is true.
        @param $value The value to add when the condition is true.
     */
    function TestCondition($condition, $msg = "", $value = 0) {
        $this->testName = get_class();
        $this->condition = $condition;
        $this->msg = $msg;
        $this->value = $value;
    }
    /**
        Implements the test case functionality and logic.
        @param $testResult The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return the initial condition.
     */
    function runTest(&$testResult, $sectionName) {
        if ($this->condition and ($this->msg or $this->value)) {
            $testResult->add($sectionName, $this->testName,
                $this->msg, $this->value);
        }
        return $this->condition;
    }
}
/**
    Tests for claims of extra credit.
 */
class TestExtraCreditClaim extends TestCase {
    var $grader;
//    var $value;
    /**
        Constructor with parameters for the test.
        @param $grader The Grader object.
     */
    function TestExtraCreditClaim(&$grader) {
        if (!is_object($grader)) die("Missing Grader object!\n");
        $this->testName = get_class();
        $this->grader = $grader;
    }
    /**
        Tests if extra credit was claimed in the README.txt file.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if extra credit was claimed in the README.txt file,
        otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $readme = $this->grader->getReadme();
        return $readme->getExtraCreditClaim();
    }
}
/**
    Checks whether files specified by list of $globs exist. Returns true
    if the any of the globs exist and false if none of them were found.
    Updated 9/30/08 to make recursive for ease of use
 */
class TestFileExists extends TestCase {
    var $globList;
    var $rec;
    var $startDir;
    /**
        Constructor with parameters for the test.
        @param $globList The list of file patterns to search.
        @param $rec Set true to recursively descend from $startDir.
        @param $startDir The starting directory.
     */
    function TestFileExists($globList, $rec = true, $startDir = ".") {
        $this->testName = get_class();
        if (!is_array($globList)) {
            $globList = array($globList);
        }
        $this->globList = $globList;
        $this->rec= $rec;
        $this->startDir = $startDir;
    }
    /**
        Records an error if no file name in the directory or any
        subdirectory matches the $glob pattern.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the file exists, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $fileList = array();
        foreach($this->globList as $glob) {
            if ($this->rec) {
                $filesFound = globr($glob, GLOB_BRACE, $this->startDir);
            } else {
                $filesFound = glob($glob, GLOB_BRACE);
            }
            if ($filesFound) {
                $fileList = array_merge($fileList, $filesFound);
            }
        }
        $fileList = array_unique($fileList);
        if (!$fileList) {
            return false;
        }
        return true;
    }
}
/**
    Tests if all of a list of patterns exists in every file meeting $glob.
 */
class TestMatch extends TestCase {
    var $patList;
    var $fileList;
    /**
        Constructor with parameters for the test.
        @param $patList A list of regular expressions to match.
        @param $fileList The list of FileContent to search.
     */
    function TestMatch($patList, $fileList) {
        $this->testName = get_class();
        if (!is_array($patList)) {
            $patList = array($patList);
        }
        $this->patList = $patList;
        if (is_null($fileList)) {
            $this->fileList = NULL;
            return;
        }
        if (!is_array($fileList)) {
            $fileList = array($fileList);
        }
        $this->fileList = FileContents::toFileContents($fileList);
    }
    /**
        Tests if all of a list of patterns exists in every file of the $fileList.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if all the patterns are found in any file, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        if (!$this->fileList) {
            echo "No files to TestMatch: ";
            return false;
        }
        $numPatMatches = 0;
        $tr->setProperty("fileName", ""); //added 5/14/2016
        foreach($this->patList as $pattern) {
            foreach($this->fileList as $fc) {
                if ($fc->isMatch($pattern)) {
                    $numPatMatches++;
                    $tr->setProperty("fileName", $fc->getName());
                    break;
                //} else { // for debug
                //    $f = $fc->getName();
                //    echo "failed pattern: $pattern in file: $f\n";
                }
            }
        }
        if ($numPatMatches != count($this->patList) OR $numPatMatches == 0) {
            return false;
        }
        return true;
    }
}
/**
    Tests if any of a list of patterns exists in any file meeting $glob.
 */
class TestMatchAny extends TestCase {
    var $fileList;
    var $patList;
    /**
        Constructor with parameters for the test.
        @param $patList A list of regular expressions to match.
        @param $fileList The list of file globs or FileContents to search.
     */
    function TestMatchAny($patList, $fileList) {
        $this->testName = get_class();
        if (!is_array($patList)) {
            $patList = array($patList);
        }
        $this->patList = $patList;
        if (is_null($fileList)) {
            $this->fileList = NULL;
            return;
        }
        if (!is_array($fileList)) {
            $fileList = array($fileList);
        }
        $this->fileList = FileContents::toFileContents($fileList);
    }
    /**
        Tests if any of a list of patterns exists in every file.
        If no $pattern matches in any file, adds a TestResult with the
        $msg and $value.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if any pattern is found in any file, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        if (!$this->fileList) {
            echo "No files to TestMatchAny: ";
        }
        $tr->setProperty("fileName", ""); //added 5/14/2016
        foreach($this->fileList as $fc) {
            foreach($this->patList as $pattern) {
                //echo "Searching for $pattern in ".$fc->getName()."\n";
                if ($fc->fileExists()) {
                    if ($fc->isMatch($pattern)) {
                        //echo 'Found a match in '.$fc->getName()."!\n";
                        $tr->setProperty('fileName', $fc->getName());
                        return true; // match found so stop searching
                    //} else { // debug
                    //    echo 'No match found in '.$fc->getName()."\n";
                    }
                } else {
                    trigger_error("Warning: asked to test match folder: $fc->getPathname()\n");
                }
            }
        }
        return false; // No match found
    }
}
/**
    Counts how many of the patterns exists in any file meeting $fileList.
    Adds a TestResult with the $msg and $value if the count is less than
    $min or greater than $max.
 */
class TestMatchCount extends TestCase {
    var $fileList;
    var $patList;
    var $min;
    var $max;
    //var $value;
    /**
        Constructor with parameters for the test.
        @param $patList A list of regular expressions to match.
        @param $fileList The list of file patterns to search.
        @param $min The minimum count to not cause an error.
        @param $max The maximum count to not cause an error, or -1 for no limit
     */
    function TestMatchCount($patList, $fileList, $min=0, $max=-1) {
        $this->testName = get_class();
        if (!is_array($patList)) {
            $patList = array($patList);
        }
        $this->patList = $patList;
        if (is_null($fileList)) {
            $this->fileList = NULL;
            return;
        }
        if (!is_array($fileList)) {
            $fileList = array($fileList);
        }
        $this->fileList = FileContents::toFileContents($fileList);
        $this->min = $min;
        $this->max = $max;
    }
    /**
        Counts how many of the patterns exists in any file meeting $fileList.
        Adds a TestResult with the $msg and $value if the count is less than
        $min or greater than $max.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if any pattern is found in any file, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        if (!$this->fileList) {
            echo "No files to TestMatchCount: ";
            debug_print_backtrace();
        }
        $numItemMatches = 0;
        foreach($this->patList as $pattern) {
            // echo "pattern=$pattern\n";
            foreach($this->fileList as $fc) {
                //echo "In file: ".$fc->getName()."\n";
                $num = $fc->countMatches($pattern);
                //echo "num=$num\n";
                if ($num > 0) {
                    $numItemMatches += $num;
                } else { // for debug
//                    $f = $fc->getName();
//                    echo "TestMatchCount failed pattern: $pattern in file: $f\n";
//                    debug_print_backtrace(); // shows caller line number
                }
            }
        }
        $tr->setProperty("count", $numItemMatches);
        if ($numItemMatches < $this->min
                OR ($numItemMatches > $this->max AND $this->max > -1)) {
            return false;
        }
        return true;
    }
}
/**
    Compiles and runs a Java unit test, recording any test errors reported
    by the test code.
 */
// NTR: could add a points per error parameter msgValue
// NTR: does not return errors on an exception: see Mohamed A8
// NTR: Points per error should be moved to an evaluator?
class TestJavaUnit extends TestCase {
    var $testFile;
    var $outFile;
    var $value;
    var $clean;
    var $testDir;
    /**
        Constructor with parameters for the test.
        @param $testFile The test file to compile and run.
        @param $value The points for each error.
        @param $logFile The file in which to save the output.
        @param $clean Set true to delete any .class files first.
        @param $testDir The test directory to copy the file into
     */
    function TestJavaUnit($testFile, $value=-1, $logFile="unit.log", $clean=true, $testDir=".") {
        $this->testName = get_class();
        if (!$testFile) die("No testFile specified!\n");
        $this->value = $value;
        $this->testFile = $testFile;
        $this->outFile = $logFile;
        $this->clean = $clean;
        $this->testDir = $testDir;
    }
    /**
        Compiles and runs Java unit tests, recording any test errors reported
        by the test code.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if no errors are detected, otherwise returns false
    */
    function runTest(&$tr, $sectionName) {
        // Copy test file into current directory
        $testName = basename($this->testFile);
        $baseTestName = basename($testName, ".java");
        if ($this->testFile !== $testName) { // do not copy over existing file
            copy($this->testFile, "$this->testDir/$testName")
                or die("Could not copy file: $this->testFile\n");
        }
        // Remove old files in case student submitted compiled files
        if ($this->clean) {
            $this->_deleteFiles(glob("*.class"));
        }
        // Open log file and write header
        if (!$handle = fopen($this->outFile, 'a')) {
            die("Cannot open $testName");
        }
        fwrite($handle, "*Unit Test Results: $testName\n");
        // Compile with unit test in place
        $cp = $this->testDir != "." ? "-cp $this->testDir " : "";
        $compileCmd = "javac $cp$this->testDir/$baseTestName.java";
        //var_dump($compileCmd);
        if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
            $errout = ERROUT;  // errout collects info for Windows
            $info = `$errout $compileCmd`;
        } else {
            $info = `$compileCmd 2>&1`;
        }
        if ($this->testFile !== $testName) { // do not remove if the only file
            unlink("$this->testDir/$testName")
                or die("Could not delete test file: $testName\n");
        }
        $info = trim($info);
        if ($info) fwrite($handle, "$info\n");
        $tr->setProperty("testRuns", true);
        if ($info && preg_match("/error|Exception/", $info)) { // no compile
            $msg = "$this->testFile does not compile";
            fwrite($handle, "\n$msg\n");
            fclose($handle);
            //$tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("testRuns", false); // NTR: too limited
            return false; // failed to run in any way
        }
        // Run unit tests and record any errors
        $fileName = basename($testName, ".java");
        $runCmd = "java $cp$fileName";
        //var_dump($runCmd);
        if (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN') {
            $errout = ERROUT;  // errout collects info for Windows
            $info = `$errout $runCmd"`;
        } else {
            $info = `$runCmd 2>&1"`;
        }
        $info = trim($info);
        if (strlen($info) === 0) {
            fwrite($handle, "No errors during test\n\n");
        } else {
            fwrite($handle, $info);
            $count = 0;
            $errors = array();
            $infoArray = explode("\n", $info);
            foreach ($infoArray as $line) {
                if (preg_match("/error/i", $line)) {
                    $errors[] = $line;
                    $count++;
                // Following added 10/18/2009
                } else if (preg_match("/Exception/", $line)) {
                    $errors[] = "Exception found during unit test";
                    $count++;
                }
            }
            $errList = array_count_values($errors);
            // Save summary in TestResult
            foreach ($errList as $err=>$count) {
                $msg = $err;
                if ($count > 1) $msg .= " (x$count)";
                $tr->add($sectionName, $this->testName, $msg, $this->value);
            }
            $count = count($errList);
            fwrite($handle, "\nTotal test errors: $count\n\n");
        }
        // Clean up
        fclose($handle);
        if (file_exists("$baseTestName.class")) unlink("$baseTestName.class");
        if ($errList) return false;
        return true;
    }
}
/**
    Loads a MySQL database from a single file and records errors and warnings
    in the test result list.
 */
class TestLoadDB extends TestCase {
    var $dbFile;
    var $log;
    /**
        Constructor with parameters for the test.
        @param $dbFile The name of the database file to load.
        @param $log The name of the log file to save errors and warnings
        reported by MySQL.
    */
    function TestLoadDB($dbFile, $log = "dbload.log") {
        $this->testName = get_class();
        $this->dbFile = $dbFile;
        $this->log = $log;
    }
    /**
        Loads a MySQL database and records errors and warnings in $tr.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the database loaded without errors. Warnings will not
        cause this test to fail.
        NTR: update to use DB class.
     */
    function runTest(&$tr, $sectionName) {
        $errCount = 0;
        // Open log file and write header
        if (!$handle = fopen($this->log, 'w')) die("TestLoadDB cannot open $this->log");
        fwrite($handle, "*DB Load Results*\n");
        // Drop all the tables from the 'test' db
        require ROOT_DIR.'/includes/dbconvars.php';
        $dbCnx = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname)
            or die("Could not connect to mysql database $dbname");
        $sql = "SHOW TABLES FROM $dbname";
        $result = mysqli_query($dbCnx, $sql);
        while ($row = mysqli_fetch_row($result)) {
            $table = $row[0];
            mysqli_query($dbCnx, "DROP TABLE $table");
        }
        mysqli_free_result($result);
        $file = $this->dbFile;
        if (!$file) {
            $msg = "No SQL files to load";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $tr->setProperty("dbloaded", false);
            fwrite($handle, "$msg\n");
            fclose($handle) or print "Could not close file: $this->log\n";
            return false;
        } else if (!file_exists($file)) {
            $msg = "TestLoadDB: SQL file does not exist: $file";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $tr->setProperty("dbloaded", false);
            fwrite($handle, "$msg\n");
            fclose($handle) or print "Could not close file: $this->log\n";
            return false;
        }
        // Comment out all USE dbname; statements
        $contents = file_get_contents($file);
        $pattern = "/USE\s+\w+\s*\;/i";
        $pattern2 = "/# USE\s+\w+\s*\;/i";
        if (preg_match($pattern, $contents)
                and !preg_match($pattern2, $contents)) {
            echo  "Commenting out USE command...";
            $contents = preg_replace($pattern, "# \\0", $contents);
            if (!$fh = fopen($file, "w")) die("Cannot open: $file\n");
            fwrite($fh, $contents);
            fclose($fh);
        }
        // Load the database
        fwrite($handle, "Loading into database file: $file\n");
        $info = `mysql -u$dbuser -p$dbpwd $dbname < "$file" 2>&1`;
        // Remove unwanted warning
        $info = preg_replace("/mysql: \[Warning\] Using a password[^\.]*\./", "", $info);
        // Collect errors and warnings
        $pattern = "/ERROR\s+\d+[()0-9 ]+at\s+line\s+\d+/i";
        $errors = preg_match_all($pattern, $info, $matches);
        foreach ($matches as $error) {
            if (isset($error[0])) {
                $tr->add($sectionName, $this->testName, $error[0], -1);
                $errCount++;
            }
        }
        $result = mysqli_query($dbCnx, "SHOW TABLES FROM $dbname");
        $numTables = mysqli_num_rows($result);
        if ($numTables == 0) {
            $errCount++;
            fwrite($handle, "No tables loaded into database\n");
            fwrite($handle, "Total errors: $errCount\n");
            $tr->setProperty("dbloaded", false);
        } else if($errCount != 0) {
            fwrite($handle, "Total errors: $errCount\n");
            $tr->setProperty("dbloaded", false);
        } else {
            fwrite($handle, "No errors during database load\n");
            $tr->setProperty("dbloaded", true);
        }
        // Clean up
        fwrite($handle, $info);
        fclose($handle) or print "Could not close file: $this->log\n";
        return $tr->getProperty("dbloaded");
    }
}
/**
    Tests for claims of extra credit.
 */
class TestPairProgClaim extends TestCase {
    var $grader;
    /**
        Constructor with parameters for the test.
        @param $grader The Grader object.
     */
    function TestPairProgClaim(&$grader) {
        if (!is_object($grader)) die("Missing grader object!\n");
        $this->testName = get_class();
        $this->grader = &$grader;
    }
    /**
        Tests if pair programming was claimed in the README.txt file.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if pair programming was claimed in the README.txt file,
        otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $readme = $this->grader->getReadme();
        return $readme->getPairProgClaim();
    }
}
/**
    Tests for errors in the README.txt file.
 */
class TestReadme extends TestCase {
    var $grader;
    var $points;
    /**
        Constructor with parameters for the test.
        @param $grader The Grader object. Use of the grader object is deprecated?
     */
    function TestReadme($grader, $pts=2) {
        if (!is_object($grader)) die("Missing grader object!\n");
        $this->testName = get_class();
        $this->grader = $grader;
        $this->points = $pts;
    }
    /**
        Checks for errors in the README.txt file.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if no errors are detected, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $pts = $this->points;
        $readme = $this->grader->getReadme(); // NTR: does not always work
        $readmeName = $readme->getReadmeName();
        $rmfc = $readme->getReadmeFileContents();
        $pathInfo = pathinfo($readmeName);
        $base = "";
        $ext = "";
        if (isset($pathInfo["basename"])) $base = $pathInfo["basename"];
        if (isset($pathInfo["extension"])) {
            $ext = strtolower(trim($pathInfo["extension"]));
        }
        if (!$readme->isReadme()) {
            $msg = "README.txt file not submitted";
            $tr->add($sectionName, $this->testName, $msg, -$pts);
            $tr->setProperty("ReadmeExists", false);
            return false;
        }
        $tr->setProperty("ReadmeExists", true);
        $errorFree = true; // assume correct to start
        if (strtoupper(substr($base, 0, 6)) != "README") {
            $name = pathinfo($base, PATHINFO_FILENAME); // added 2/3/2017
            $msg="README file names must start with \"README\" not \"$name\"";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        if ($ext === "doc") {
            $msg = "README.txt must be a text file, not a Word file";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        if ($ext==="txt" && $rmfc->exists()) {
            $magicNum = bin2hex($rmfc->substr(0, 4));
            if (strtolower($magicNum) === "d0cf11e0") {
                $msg = "README.txt must be a text file, not MS Word\n --Must create as plain text and not simply rename doc files";
                $tr->add($sectionName, $this->testName, $msg, -1);
                $errorFree = false;
            }
        }
        if ($ext === "cpp") {
            $msg = "README.txt must be a text file, not a cpp file";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        if ($ext === "rtf") {
            $msg = "README.txt must be a text file, not an RTF file";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        if ($ext==="txt" && $rmfc->exists() && $rmfc->isMatch("/\b{\rtf/")) {
            $msg = "Please submit plain text and NOT RTF files\n --Must create as plain text and not simply rename RTF files";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        if ($ext === "" and $readmeName != "") {
            $msg = "Please add a .txt extension to README files";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        if ($readme->getHoursClaim() == 0) {
            $msg = "Hours not reported correctly";
            $tr->add($sectionName, $this->testName, $msg, -1);
            $errorFree = false;
        }
        return $errorFree;
    }
}
/**
    Runs the SQL statement against the specified database using mysql
    monitor and and saves the results in an output file.
 */
class TestRunLogSQL extends TestCase {
    var $dbName;
    var $sqlList;
    var $outFile;
    /**
        Constructor with parameters for the test.
        @param $sql The list of SQL commands to run.
        @param $outFile The file in which to save the output.
        @param $dbName The database on which to run the SQL.
    */
    function TestRunLogSQL($sql, $outFile = "out.log", $dbName = NULL) {
        $this->testName = get_class();
        if (!is_array($sql)) {
            $sql = array($sql);
        }
        $this->sqlList = $sql;
        $this->dbName = $dbName;
        $this->outFile = $outFile;
    }
    /**
        Runs the SQL statement against the specified database using mysql
        monitor and and saves the results in an output file.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if $sql produced an output, otherwise false.
     */
    function runTest(&$tr, $sectionName) {
        require ROOT_DIR.'/includes/dbconvars.php';
        if ($this->dbName) $dbname = $this->dbName; // overwrite dbconvars
        if (!$handle = fopen($this->outFile, "ab")) {
            die("Cannot open $this->outFile");
        }
        fwrite($handle, "*Run SQL: $this->outFile*\n");
        if (!$this->sqlList or !$this->sqlList[0]) {
            $msg = "Empty SQL statements";
            fwrite($handle, "$msg\n");
            $tr->setProperty("no_sql", true);
            fclose($handle);
            return false;
        }
        $info = "No SQL statement found";
        foreach($this->sqlList as $sql) {
            $sql = preg_replace("/\#.*\n/", "", $sql); // remove # comments
            $sql = preg_replace("/\"/", "'", $sql);  // double->single quotes
            $sql = preg_replace("/\s+/", " ", $sql); // remove extra whitespace
            $sql = trim($sql);
            if ($sql) {
                // -t is table format, -e is execute
                $info = `mysql -u$dbuser -p$dbpwd -t -e"$sql" $dbname 2>&1`;
            }
            // Remove unwanted warning
            $info = preg_replace("/mysql: \[Warning\] Using a password[^\.]*\./", "", $info);
            $sqlOut = wordwrap("sql: $sql\n", 75);
            fwrite($handle, "$sqlOut\n");
            if (!$info) {
                fwrite($handle, "No output from query\n");
                $tr->setProperty("queryok", false);  // 7/10/17
            } else if (strpos($info, 'ERROR') !== false) {  // 7/10/17
                $msg = "Error in SQL query: ".strtok($info, ":");
                $tr->add($sectionName, $this->testName, $msg, 0);
                fwrite($handle, $info);
                $tr->setProperty("queryok", false);
            } else {
                fwrite($handle, $info);
                $tr->setProperty("queryok", true);  // 7/10/17
            }
        }
        fclose($handle);
        //return (bool)$info;
        return $tr->getProperty("queryok", false);  // 7/10/17
    }
}
/**
    Runs a PHP web page and save the results as HTML in $outFileName.
 */
class TestRunPage extends TestCase {
    var $pageName;
    var $outFile;
    var $logFile;
    /**
        Constructor with parameters for the test.
        @param $pageName The name of the page to run.
        @param $outFile The name of the file to save the HTML output.
        @param $logFile The file in which to save the errors and warnings.
     */
    function TestRunPage($pageName, $outFile="out.html", $logFile="out.log") {
        $this->testName = get_class();
        $this->pageName = $pageName;
        $this->outFile = $outFile;
        $this->logFile = $logFile;
    }
    /**
        Runs a PHP web page and save the results as HTML in $outFile.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if no errors were found, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        // Check if any work to do
        if (!file_exists($this->pageName)) {
            $msg = "File does not exist: $this->pageName";
            $tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("no_files", true);
            $tr->setProperty("runs", false);
            return false;
        }
        $tr->setProperty("runs", true);
        // Compose the URL
        $url = "http://localhost/";
        $url .= substr(strtr(getcwd(), "\\", "/"), 11); // 11 is a hack
        $url .= '/'.$this->pageName;
        $url = urlencode($url);
        $url = str_replace('%2F', '/', $url); // fix urlencode
        $url = str_replace('%3A', ':', $url); // fix urlencode
        $url = str_replace('+', '%20', $url); // fix urlencode
        // Run the page from a server and save in a file
        $info = file_get_contents($url);
        if (!$info) {
            echo "Could not read file: $url\n";
            $tr->setProperty("runs", false);
        }
        if (!$fh = fopen($this->outFile, "w")) {
            die("Cannot open $this->outFile\n");
        }
        fwrite($fh, $info);
        fclose($fh);
        // Extract errors, warnings and notices reported by PHP
        $info = strip_tags($info);
        $info = str_replace(getcwd()."\\", "", $info);
        preg_match_all("/Error:.*line\s+\d+/i", $info, $errors);
        $count = count($errors[0]);
        if ($count > 0) {
            //$msg = "Found $count error";
            //if ($count !== 1) $msg .= 's';
            //$tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("errors", $count);
        }
        preg_match_all("/Warning:.*line\s+\d+/i", $info, $warnings);
        $count = count($warnings[0]);
        if ($count > 0) {
            //$msg = "Found $count warning";
            //if ($count !== 1) $msg .= 's';
            //$tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("warnings", $count);
        }
        preg_match_all("/Notice:.*line\s+\d+/i", $info, $notices);
        $count = count($notices[0]);
        if ($count > 0) {
            //$msg = "Found $count notice";
            //if ($count !== 1) $msg .= 's';
            //$tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("notices", $count);
        }
        $matches = array_merge($errors[0], $warnings[0], $notices[0]);
        // Save errors in log file
        if ($matches) { // log the errors
            if (!$fh = fopen($this->logFile, "a")) {
                die("Cannot open $this->logFile\n");
            }
            fwrite($fh, "*Page Run Results:\n");
            fwrite($fh,
                "I found these problems when I ran $this->pageName:\n");
            foreach($matches as $line) {
                fwrite($fh, "$line\n");
            }
            fclose($fh);
        }
        return count($errors) === 0;
    }
    /**
     * Adds an array of $matches to $testResult
     */
    function add(&$testResult, $matches, $value) {
        foreach ($matches as $msg) {
            $testResult->add("TestRunPage", $msg, $value);
        }
    }
}
/**
    Checks C++ code for programming style. Which items to test are controlled
    by a configuration properties file or list.
 */
 // NTR: need to add check for object names
class TestStyleCPP extends TestCase {
    private $errors = array();
    private $fileList;
    private $configList;
    private $logFile;
    private $summarize;
    private $hasFileCommentBlockError;
    private $varNameList; // store example issues for variable names
    private $constNameList; // store example issues for const names
    /**
        Constructor with parameters controlling the test.
        @param $fileList A list of FileContents to test.
        @param $config A configuration properties list or file name.
        @param $summarize Set true to save a summary in TestResult.
        @param $logFile File name of the log file; "" for no file.
     */
    function TestStyleCPP($fileList, $config=null, $summarize=true,
            $logFile="style.log") {
        require_once ROOT_DIR.'/libs/Properties.php';
        $this->testName = get_class();
        if (is_null($fileList)) {
            $this->fileList = NULL;
        } else {
            $this->fileList = FileContents::toFileContents($fileList);
        }
        if (null == $config) {
            // Default configuration
            $this->configList[FileCommentBlock] = -1;
            $this->configList[Indentation] = 0;
        } elseif (is_array($config)) {
            // Configuration from array
            $this->configList = $config;
        } elseif (is_string($config)) {
            // Load configuration from file
            $p = new Properties();
            $p->load(file_get_contents($config));
            $this->configList = $p->toArray();
        }
        $this->summarize = $summarize;
        $this->logFile = $logFile;
        $this->hasFileCommentBlockError = false;
    }
    /**
        Checks code for style in one or more files. Detected errors are
        written to a log and a summary saved in the TestResult.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the test passes, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $numFiles = 0;
        foreach($this->fileList as $fc) {
            if (!$fc->exists()) continue; // 11/22/2017
            $fc->reload(); // 8/10/2017
            $numFiles++;
            $contents = $fc->toString(); // 11/7/2016
            $lineList = $fc->toArray();
            $file = $fc->getName();
            $this->stripLines($lineList);
            $strippedLines = $this->stripLines($lineList);
            $strippedContents = "";
            foreach ($strippedLines as $line) {
                $strippedContents .= $line."\n";
            }
            if ($this->getProperty('FileCommentBlock') !== "skip") {
                $this->fileCommentBlock($lineList, $file);
            }
            if ($this->getProperty('AuthorTag') !== "skip") {
                $this->authorTag($lineList, $file);
            }
            if ($this->getProperty('VersionTag') !== "skip") {
                $this->versionTag($lineList, $file);
            }
            if ($this->getProperty('Indentation') !== "skip") {
                $this->indentation($lineList, $file);
            }
            if ($this->getProperty('FunctionCommentBlock') !== "skip") {
                $this->functionCommentBlock($contents, $file);
            }
            if ($this->getProperty('FileTabCharacter') !== "skip") {
                $this->fileTabCharacter($lineList, $file);
            }
            // Changed test to !== "skip" from > 0 on 2/13/2017
            if ($this->getProperty('LineLength') !== "skip") {
                $this->lineLength($lineList, $file);
            }
            if ($this->getProperty('ClassName') !== "skip") {
                $this->className($strippedContents, $file);
            }
            if ($this->getProperty('ConstantName') !== "skip") {
                $this->constantName($strippedContents, $file);
            }
            if ($this->getProperty('FunctionName') !== "skip") {
                $this->functionName($strippedContents, $file);
            }
            if ($this->getProperty('VariableName') !== "skip") {
                $this->variableName($strippedContents, $file);
            }
            if ($this->getProperty('MagicNumber') !== "skip") {
                $this->magicNumber($strippedLines, $file);
            }
            if ($this->getProperty('WhitespaceAfterComma') !== "skip") {
                $this->whitespaceAfterComma($strippedLines, $file);
            }
            if ($this->getProperty('WhitespaceAroundOperator') !== "skip") {
                $this->whitespaceAroundOperator($strippedLines, $file);
            }
        }
        if ($numFiles === 0) {
            $this->addError("No source code files found");
            $tr->setProperty("no_files", true);
        }
        //print_r($this->errors); // for debugging
        // Save errors in log file
        if ($this->logFile) {
            if (!$handle = fopen($this->logFile, "w")) die("Cannot open $testName");
            fwrite($handle, "*Programming Style Results*\n");
            foreach($this->errors as $item) {
                fwrite($handle, "$item[0]\n");
            }
            if (count($this->fileList) == 0) {
                fwrite($handle, "No source code files found\n\n");
                $this->addError("No source code files found");
            } else {
                fwrite($handle, "\nStyle errors: ".count($this->errors)."\n");
            }
            fclose($handle);
        }
        // Summarize error messages
        if ($this->summarize) {
            $errorList = array();
            $pointVal = array();
            $re = "/[\w\d:.]+:([^:(]+)/";
            foreach ($this->errors as $error) {
                preg_match($re, $error[0], $matches);
                if ($matches) {
                    $errorList[] = trim($matches[1]);
                    $pointVal[trim($matches[1])] = $error[1];
                }
            }
            $errList = array_count_values($errorList);
            // Add examples to error messages
            //print_r($errList);
            //print_r($this->errors);
            // Following if as of 2/17/2014
            if (isset($errList["Non-standard variable name"])) {
                $errList["Non-standard variable names like:$this->varNameList"] = $errList["Non-standard variable name"];
                unset($errList["Non-standard variable name"]);
                // storage of $pointVal
                $pointVal["Non-standard variable names like:$this->varNameList"] = $pointVal["Non-standard variable name"];
            }
            // Following if as of 3/16/2015
            if (isset($errList["Non-standard constant name"])) {
                $errList["Non-standard constant names like:$this->constNameList"] = $errList["Non-standard constant name"];
                unset($errList["Non-standard constant name"]);
                $pointVal["Non-standard constant names like:$this->constNameList"] = $pointVal["Non-standard constant name"];
            }
            // Following if as of 12/12/2016
            if (isset($errList["Magic number"])) {
                $magicNums = array();
                foreach($this->errors as $err) {
                    if (strpos($err[0], 'Magic number') !== false) {
                        // www.regular-expressions.info/floatingpoint.html
                        $re = "/number: ([-+]?\d*\.?\d+([eE]?[-+]?\d+)?)/";
                        preg_match($re, $err[0], $matches);
                        $magicNums[] = $matches[1];
                    }
                }
                $magicNums = array_unique($magicNums, SORT_NUMERIC);
                sort($magicNums);
                $magicNums = array_slice($magicNums, 0, 5); // limit size
                $examples = implode(", ", $magicNums);
                // Substitute new message
                $newKey = "Magic numbers like: $examples";
                $oldKey = "Magic number";
                $errList[$newKey] = $errList[$oldKey];
                unset($errList[$oldKey]);
                $pointVal[$newKey] = $pointVal[$oldKey];
            }
            // Save summary in TestResult
            $errorCount = 0;
            foreach ($errList as $err=>$count) {
                $msg = $err;
                if ($count > 1) $msg .= " (x$count)";
                $ptVal = $pointVal[$err];
                if ($ptVal != 0) {
                    $errorCount++;
                    $tr->add($sectionName, $this->testName, $msg, $ptVal);
                //} else {
                //    echo "Skipping $msg: $ptVal\n";
                }
            }
            // Process warnings (0)
            if (count($errList) > $errorCount) {
                $msg = "I did not take off for the following problems this time:";
                $tr->add($sectionName, $this->testName, $msg, 0);
                foreach ($errList as $err=>$count) {
                    $msg = $err;
                    if ($count > 1) $msg .= " (x$count)";
                    $ptVal = $pointVal[$err];
                    if (0 == $ptVal) {
                        $tr->add($sectionName, $this->testName, $msg, $ptVal);
                    }
                }
            }
        }
        return count($this->errors) === 0;
    }
    /**
        Add an error message to the list.
        @param $msg The message to add.
        @param $points The point value of the error.
     */
    function addError($msg, $points=0) {
        $data = array($msg, $points);
        $this->errors[] = $data;
    }
    /**
        Returns the value for the specified key.
        @param $key The key for the value to retrieve.
        @return The value or null if the key does not exist.
     */
    function getProperty($key) {
        if (isset($this->configList[$key])) {
            return $this->configList[$key];
        }
        return "skip";
    }
    /**
        Strips C and C++ style comments AND strings from $lines.
        Retains same number of lines for determining line numbers.
        @param $lines An array containing all the lines in the file.
        @return The same file with comments and strings removed.
     */
    function stripLines($lines) {
        $isInComment = false;
        $StrippedLines = array();
        foreach($lines as $line) {
            // Remove single line /*...*/
            // In following pattern, ! is delimiter and ? makes .* ungreedy
            // http://www.php.net/manual/en/regexp.reference.repetition.php
            $line = preg_replace('!/\*.*?\*/!', '', $line); // single
            // Remove C++ style // comments
            $line = preg_replace('!//.*!', '', $line);
            // Strip contents of strings
            $line = preg_replace('/".*?"/', '""', $line);
            // Handle multiline comments
            if (substr_count($line, "/*") > 0) {
                $isInComment = true;
                // Remove everything after the start of the comment
                $line = preg_replace('!/\*.*!', '', $line);
            } elseif (substr_count($line, "*/") > 0) {
                $isInComment = false;
                // Remove everything before the end of the comment
                $line = preg_replace('!.*?\*/!', '', $line);
            } elseif ($isInComment) {
                $line = "\n";
            }
            $StrippedLines[] = $line;
        }
        return $StrippedLines;
    }
    /**
        Checks for @version tag errors.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file.
     */
    function authorTag($lines, $file) {
        $value = (double) $this->getProperty('AuthorTag');
        if ($this->hasFileCommentBlockError) $value = 0;
        $lineNum = 0;
        foreach($lines as $line) {
            $lineNum++;
            if (preg_match("/@\s*author/i", $line)) {
                if (substr_count($line, "@author") > 0) {
                    return;
                } elseif (substr_count(strtolower($line), "@author") > 0) {
                    $this->addError("$file:$lineNum: Use all lower case for @author", $value);
                } else {
                    $this->addError("$file:$lineNum: Do NOT put spaces between @ and author", $value);
                }
                return;
            }
        }
        $this->addError("$file: Missing @author tag", $value);
    }
    /**
        Checks file comment block is present.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file.
     */
    function fileCommentBlock($lines, $file) {
        $value = (double) $this->getProperty('FileCommentBlock');
        $multiCommentStartRE = "/\/\*+/";
        $blockCommentStartRE = "/\/\*{2,}/";
        $commentEndRE = "/\*\//";
        $cppCommentRE = "/\/\//";
        $funHeaderRE = "/\b\w+(\s|\n)+\w+(\s|\n)*\(.*\)\s*({|;)/sU";
        $classNameRE = "/class\s+(\w+)\s*{/"; // Added 11/9/09
        $startComment = false;
        $endComment = false;
        $cppComment = false;
        for ($i = 0, $numLines = count($lines); $i < $numLines; $i++) {
            // Use short-circuit eval to skip preg_match when not needed
            if (!$cppComment && preg_match($cppCommentRE, $lines[$i])) {
                $cppComment = true;
            }
            if (!$endComment && preg_match($commentEndRE, $lines[$i])) {
                $endComment = true;
            }
            if (!$endComment && preg_match($multiCommentStartRE, $lines[$i])) {
                $startComment = true;
                if (!preg_match($blockCommentStartRE, $lines[$i])) {
                    $msg = "$file: Start file comments with /** instead of /*";
                    $this->addError($msg, $value);
                    $this->hasFileCommentBlockError = true;
                    return false;
                }
            }
            if ($startComment && preg_match("/@author/i", $lines[$i])) {
                return true;
            }
            if ($startComment && preg_match("/@version/i", $lines[$i])) {
                return true;
            }
            if ($endComment && preg_match("/#include/", $lines[$i])) {
                return true;
            }
            if ($endComment && preg_match("/#include/", $lines[$i])) {
                return true;
            }
            // Another comment before first function
            if ($endComment && preg_match($multiCommentStartRE, $lines[$i])) {
                return true;
            }
            if (!$endComment && preg_match($funHeaderRE, $lines[$i])
                    || preg_match($classNameRE, $lines[$i])) {
                if (!$cppComment) {
                    $this->addError("$file: Missing file comment block", $value);
                } else {
                    $this->addError("$file: Wrong file comment block", $value);
                }
                $this->hasFileCommentBlockError = true;
                return false;
            }
        }
        $this->addError("$file: Missing or incorrect file comment block", $value);
        $this->hasFileCommentBlockError = true;
        return false;
    }
    /**
        Checks file for presence of tab characters.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file.
     */
    function fileTabCharacter($lines, $file) {
        $value = (double) $this->getProperty('FileTabCharacter');
        $lineNum = 0;
        foreach($lines as $line) {
            $lineNum++;
            $numTabs = substr_count($line, "\t");
            if ($numTabs > 0) {
                $this->addError("$file:$lineNum: Use spaces instead of tabs (x$numTabs)", $value);
            }
        }
    }
    /**
        Checks the comment block of a function.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file.
     */
    // NTR: only checks prototypes
    function functionCommentBlock($contents, $file) {
        $value = (double) $this->getProperty('FunctionCommentBlock');
        // Get class name
        $classNameRE = "/class\s+(\w+)\s*{/";
        preg_match_all($classNameRE, $contents, $matches);
        $classNames = $matches[1];
        // Get function prototypes
        //$protoRE = '/(\n|;)\s*(\w+[ \t]+\w+\s*\([^)"]*\)\s*(const)?\s*;)/';
       //$protoRE = '/(\n|;)\s*(\w+[ \t]+\w+\s*\([^)"\/]*\)\s*(const)?\s*;)/';
        //$protoRE = '/[\n;]\s*(\w+[ \t]+\w+\s*\([^)"\/]*\)\s*(const)?\s*;)/';
        $protoRE = '/[\n;]\s*(\w+\s+\w+\s*\((?:[^)"\/,;]+\s+[^)"\/;]+)*\)\s*(const)?\s*;)/'; // 5/1/2017
        preg_match_all($protoRE, $contents, $matches);
        $prototypes = $matches[1];
        //print_r($prototypes);
        // remove return statements -- added 12/12/2016
        $size = count($prototypes);
        for ($i = 0; $i < $size; $i++) {
            if (substr($prototypes[$i], 0, 6) === "return") {
                unset($prototypes[$i]);
            }
        }
        //print_r($prototypes);
        // Get fun calls that might be confused with constructors
        //$funCallRE = '/\w+\s*\([^)"]*\)\s*;/';
        $funCallRE = '/\w+\s*\((?:[^)"\/,;]+\s+[^)"\/;]+)\)\s*;/'; //5/2/2017
        preg_match_all($funCallRE, $contents, $matches);
        $funCalls = $matches[0];
        //print_r($funCalls);
        // Extract lines that start with the class name
        $constructors = array();
        $size = count($funCalls);
        for ($i = 0; $i < $size; $i++) {
            foreach ($classNames as $className) {
                if (strlen($className) <= strlen($funCalls[$i])
                    && substr_compare($funCalls[$i], $className,
                        0, strlen($className)) === 0) {
                    $constructors[] = $funCalls[$i];
                }
            }
        }
        $constructProtoList = array_merge($constructors, $prototypes);
        //var_dump($constructProtoList);
        //if (!$constructProtoList) {
        //    $this->addError("$file: Missing function prototypes for comments", $value);
        //}
        // Check for end of a block comment before each prototype
        foreach ($constructProtoList as $proto) {
            $funCommentRE = "/\*\/\s+".preg_quote($proto)."/";
            if (!preg_match($funCommentRE, $contents)) {
                $this->addError("$file: Missing block comment before prototype: $proto", $value);
            }
        }
        //if ($constructProtoList) return;
/* NTR: need to check definitions if there are no prototypes
        // extract function definitions
        $funRE = "/\w+\s+([\w:]+)\s*\([^)]*\)\s*(const)?\s*{[^}]*}/";
        $count = preg_match_all($funRE, $contents, $matches);
        // need to exclude cppwords = ['if', 'while', 'do', 'for', 'switch']
        $funDefn = $matches[0];
        $funNames = $matches[1];
        $keyWords = array('if', 'while', 'do', 'for', 'switch');
        for ($i = 0; $i < count($funNames); $i++) {
            foreach ($keyWords as $word) {
                if ($funNames[$i] == $word) unset($funDefn[$i]);
            }
        }
//print_r(array_values($funDefn));
//print_r($funNames);
        // Check for end of a block comment before each definition
        foreach ($funDefn as $fun) {
var_dump($fun);
            $funCommentRE = "/\*\/\s+".preg_quote($fun)."/";
            if (!preg_match($funCommentRE, $contents)) {
                $this->addError("$file: Missing block comment before definition: $fun", $value);
            }
        }
*/
    }
    /**
        Checks code file for indentation of lines inside curly braces.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file (for messages).
     */
    function indentation($lines, $file) {
        $value = (double) $this->getProperty('Indentation');
        $level = 0;
        $lineNum = 0;
        foreach($lines as $line) {
            $lineNum++;
            //echo "$lineNum $level $line\n";  // for debugging
            if ($level > 0 && !preg_match('/^\s/', $line)) {
                preg_match('/^(\w+)/', $line, $matches);
                if (isset($matches[1])
                    && strpos($matches[1], 'public') === false
                    && strpos($matches[1], 'private') === false) {
                        $msg = "$file-$lineNum: Always indent statements inside braces";
                        //echo "$msg: $word\n"; // for debugging
                        $this->addError($msg, $value);
                }
            }
            $level += substr_count($line, '{');
            $level -= substr_count($line, '}');
        }
    }
    /**
        Checks for long lines.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file.
     */
    function lineLength($lines, $file) {
        $value = (double) $this->getProperty('LineLength');
        $length = $this->getProperty('LineLengthMaximum');
        $lineNum = 0;
        foreach($lines as $line) {
            $lineNum++;
            $line = rtrim($line); // remove \n
            if (strlen($line) > $length) { // +3 margin of error
                $this->addError("$file:$lineNum: Line too long (".strlen($line).")", $value);
            }
        }
    }
    /**
        Checks that there are no "magic numbers", where a magic number is a
        numeric literal that is not defined as a constant. The numbers
        -1, 0, 1, and 2 are not considered to be magic numbers.
        @param $strippedLines An array containing all the lines in the file
            but without any comments or strings.
        @param $file The name of the file.
     */
    function magicNumber($strippedLines, $file) {
        $value = (double) $this->getProperty('MagicNumber');
        // .001, 1.23, 1.2e-03
        $numRE = "/\b(\+|-)?(?<!'|\")[0-9]*\.?[0-9]+(e[+-]?[0-9]+)?\b/"; //'
        $constRE = "/\bconst\b|#define/";
        // Following RE must be mutually exclusive (no overlap)
        $oneRE = "/\b[+-]?1(\.0)?(?!\.)\b/";
        $twoRE = "/\b2(\.0)?(?!\.)\b/";
        // $zeroRE = "/\b0(\.0+)?(?!\.)\b/";
        $zeroRE = "/\b[-+]?0*\.?0+(?:[eE][-+]?0+)?\b/"; // changed 5/1/2017
        $countNums = 0;
        $countConst = 0;
        $countOkNums = 0;
        $lineNum = 0;
        foreach($strippedLines as $line) {
            $lineNum++;
            $countMagic = 0;
            $countOkNums = 0;
            $countConst = preg_match_all($constRE, $line, $matches);
            if (0 === $countConst) {
                $countOkNums = preg_match_all($oneRE, $line, $matches);
                $countOkNums += preg_match_all($twoRE, $line, $matches);
                $countOkNums += preg_match_all($zeroRE, $line, $matches);
            }
            if (0 === $countConst && 0 === $countOkNums) {
                $countMagic = preg_match_all($numRE, $line, $matches);
            }
            if ($countMagic > 0) {
                $magicNumList = $matches[0];
                foreach ($magicNumList as $num) {
                    $this->addError("$file:$lineNum: Magic number: $num", $value);
                }
            }
        }
    }
    /**
        Checks class naming conventions.
        @param $strippedContents A string containing all the lines in the
            file but without any comments or literal strings.
        @param $file The name of the file.
     */
    function className($strippedContents, $file) {
        $value = (double) $this->getProperty('ClassName');
        $regEx = $this->getProperty("ClassNameRegEx");
        if ("skip" == $regEx) $regEx = "^[A-Z][_a-zA-Z0-9]*$";
        $classNameRE = "/class\s+(\w+)\s*{/";
        preg_match_all($classNameRE, $strippedContents, $matches);
        $classNames = $matches[1];
        // Record errors
        foreach ($classNames as $name) {
            if (!preg_match("/$regEx/", $name)) {
                $this->addError("$file: Non-standard class name: $name", $value);
            }
        }
    }
    /**
        Checks function naming conventions.
        @param $strippedContents A string containing all the lines in the
            file but without any comments or literal strings.
        @param $file The name of the file.
     */
    function functionName($strippedContents, $file) {
        $value = (double) $this->getProperty('FunctionName');
        $regEx = $this->getProperty("FunctionNameRegEx");
        if ("skip" == $regEx) $regEx = "^[a-z][a-zA-Z0-9]*(_[a-zA-Z0-9]+)*$";
        //$funRE = '/\w+\s+(\w+)\s*\([^)"\']*\)\s*(const)?\s*[;{]/';
        $funRE = '/\w+\s+(\w+)\s*\((?:[^)"\/,;=]+\s+[^)"\/;=]+)*\)\s*(const)?\s*[;{]/'; // 5/2/2017
        preg_match_all($funRE, $strippedContents, $matches);
        $funNames = $matches[1];
        // Remove keywords with parenthesis
        $re = "/if|while|do|for|switch/";
        $funNames = preg_grep($re, $funNames, PREG_GREP_INVERT);
        // Record errors
        foreach ($funNames as $name) {
            if (!preg_match("/$regEx/", $name)) {
                $this->addError("$file: Non-standard function name: $name", $value);
            }
        }
    }
    /**
        Checks constant variable naming conventions.
        @param $strippedContents A string containing all the lines in the
            file but without any comments or literal strings.
        @param $file The name of the file.
     */
    function constantName($strippedContents, $file) {
        $value = (double) $this->getProperty('ConstantName');
        $regEx = $this->getProperty("ConstantNameRegEx");
        if ("skip" == $regEx) $regEx = "^[A-Z][A-Z0-9]*(_[A-Z0-9]+)*$";
        // Find all variable names
        // NTR: Does not allow multiple assignment in declaration like
        // const int A = 0, B = 0;
        $variableRE = "/(\w+\s+)+(\s*[a-zA-Z_]\w*(\[\])?\s*,?\s*)+[;=]/";
        preg_match_all($variableRE, $strippedContents, $matches);
        $declaration = $matches[0];
        // remove items without const
        $constDeclarations = array();
        for ($i = 0; $i < count($declaration); $i++) {
            if (substr_count($declaration[$i], "const") > 0) {
                $constDeclarations[] = $declaration[$i];
            }
        }
        // Filter extraneous data
        $constNames = array();
        $re = "/(\w+\s+)*([a-zA-Z_]\w*)/";
        for ($i = 0; $i < count($constDeclarations); $i++) {
            preg_match_all($re, $constDeclarations[$i], $matches);
            $item = trim($matches[2][0]);
            if ($item) $constNames[] = $item;
        }
        $constNames = array_values($constNames);
        $constNames = array_unique($constNames);
        // Record errors
        $constCount = 0;
        foreach ($constNames as $name) {
            if (!preg_match("/$regEx/", $name)) {
                $this->addError("$file: Non-standard constant name: $name", $value);
                // following if-else-if as of 3/16/2015
                if ($constCount == 0) {
                    $this->constNameList .= " $name";
                } else if ($constCount < 3) {
                    $this->constNameList .= ", $name";
                }
                $constCount++;
            }
        }
    }
    /**
        Checks variable naming conventions.
        @param $strippedContents A string containing all the lines in the
            file but without any comments or literal strings.
        @param $file The name of the file.
     */
    function variableName($strippedContents, $file) {
        $value = (double) $this->getProperty('VariableName');
        $regEx = $this->getProperty("VariableNameRegEx");
        if ("skip" == $regEx) $regEx = "^[a-z][a-zA-Z0-9]*(_[a-zA-Z0-9]+)*$";
        // Find all variable names
        // NTR: Does not allow multiple assignment in declaration like
        // int a = 0, b = 0;
        //$variableRE = "/((std::)?\w+\s+)+(\s*[a-zA-Z_]\w*(\[\])?\s*,?\s*)+[\[;,=)]/";
        // In following pattern, ?: makes non-capturing group
        //$variableRE = "/(?:(?:std::)?\w+\s+)+(?:\s*[a-zA-Z_]\w*(?:\[\])?\s*,?\s*)+[\[;,=)]/";
        $variableRE = "/(?:(?:\w+::)?\w+\s+)+(?:\s*[a-zA-Z_]\w*(?:\[\])?\s*,?\s*)+[\[;,=)]/"; // 5/14/2017
        preg_match_all($variableRE, $strippedContents, $matches);
        //print_r($matches);
        $declaration = $matches[0];
        //print_r($declaration);
        // Remove items with class, const, namespace, return
        $re = "/class|const|namespace|return/"; // added class 5/15/2016
        $declaration = preg_grep($re, $declaration, PREG_GREP_INVERT);
        // Split on commas
        $splitDeclaration = array();
        foreach ($declaration as $line) {
            $splitList = preg_split("/,/", $line);
            $splitDeclaration = array_merge($splitDeclaration, $splitList);
        }
        // Filter extraneous data
        $varNames = array();
        $re = "/(\w+\s+)*([a-zA-Z_]\w*)/";
        for ($i = 0; $i < count($splitDeclaration); $i++) {
            preg_match_all($re, $splitDeclaration[$i], $matches);
            isset($matches[2][0]) ? $item = trim($matches[2][0]) : $item = "";
            if ($item) $varNames[] = $item;
        }
        $varNames = array_values($varNames);
        $varNames = array_unique($varNames);
        //print_r($varNames);
        // Record errors
        $varCount = 0;
        foreach ($varNames as $name) {
            if (!preg_match("/$regEx/", $name)) {
                $this->addError("$file: Non-standard variable name: $name", $value);
                // Save examples
                if ($varCount == 0) {
                    $this->varNameList .= " $name";
                } else if ($varCount < 3) {
                    $this->varNameList .= ", $name";
                }
                $varCount++;
            }
        }
    }
    /**
        Checks for @version tag errors.
        @param $lines An array containing all the lines in the file.
        @param $file The name of the file.
     */
    function versionTag($lines, $file) {
        $value = (double) $this->getProperty('VersionTag');
        if ($this->hasFileCommentBlockError) $value = 0;
        $lineNum = 0;
        foreach($lines as $line) {
            $lineNum++;
            if (preg_match("/@\s*version/i", $line)) {
                if (substr_count($line, "@version") > 0) {
                    return;
                } elseif (substr_count(strtolower($line), "@version") > 0) {
                    $this->addError("$file:$lineNum: Use all lower case for @version", $value);
                } else {
                    $this->addError("$file:$lineNum: Do NOT put spaces between @ and version", $value);
                }
                return;
            }
        }
        $this->addError("$file: Missing @version tag", $value);
    }
    /**
        Checks that a comma is followed by whitespace.
        @param $strippedLines An array containing all the lines in the file
            but without any comments or strings.
        @param $file The name of the file.
     */
    function whitespaceAfterComma($strippedLines, $file) {
        $value = (double) $this->getProperty('WhitespaceAfterComma');
        $lineNum = 0;
        foreach($strippedLines as $line) {
            $lineNum++;
            if (preg_match("/,[^\s]/", $line)) {
                $this->addError("$file:$lineNum: Put space after a comma", $value);
            }
        }
    }
    /**
        Checks that a comma is followed by whitespace.
        @param $strippedLines An array containing all the lines in the file
            but without any comments or strings.
        @param $file The name of the file.
     */
    function whitespaceAroundOperator($strippedLines, $file) {
        $value = (double) $this->getProperty('WhitespaceAroundOperator');
        $lineNum = 0;
        foreach($strippedLines as $line) {
            $lineNum++;
            // Allow exponential notation like: 1e-7
            $re = "/[a-df-zA-DF-Z0-9_][-+*\/=]{1,2}\w/";
            if (preg_match($re, $line)) {
                $this->addError("$file:$lineNum: Put space around operators", $value);
            }
        }
    }
}
// Uncomment the following to run unit tests
//unitTestStyleCPP();
function unitTestStyleCPP() {
    require_once ROOT_DIR.'/libs/Properties.php';
    require_once ROOT_DIR.'/testresult.php';
    $cppFiles = array('test1.cpp', 'test2.cpp', 'test3.cpp');
    $configPath = 'C:/Courses/tools/autograde5/testfiles/style.txt';
    $p = new Properties();
    $p->load(file_get_contents($configPath));
    $config = $p->toArray();
    $result = new TestResult();
    $t = new TestStyleCPP($cppFiles, $config, true, "testfiles/style.log");
    $pass = $t->run($result, "UnitTestStyleCPP");
//    assert('is_array($config)');
    var_dump($result);
    echo "...unit test complete\n";
}
define("CHECK_TOOL", "C:/Courses/tools/checkstyle-4.4/checkstyle-all-4.4.jar");
define("CHECK_TEST", "com.puppycrawl.tools.checkstyle.Main");
define("CHECK_STD", "C:/Courses/common/supplements/grade_checks.xml");
/**
    Checks Java code for programming style.
 */
class TestStyleJava extends TestCase {
    var $cfg;
    var $globList;
    /**
        Constructor with parameters for the test.
        @param $globList The list of file patterns to search. (added 11/23/08)
        // NTR: check to ensure .java file extension
    */
    function TestStyleJava($configFile=CHECK_STD, $globList="*.java") {
        $this->testName = get_class();
        $this->cfg = $configFile;
        if (!is_array($globList)) {
            $globList = array($globList);
        }
        $this->globList = $globList;
    }
    /**
        Checks code for style in one or more files. Detected errors are
        written to a log and a summary saved in the TestResult.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the test passes, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $fileList = array();
        foreach($this->globList as $glob) {
            $fileGlob = globr($glob, GLOB_BRACE);
            if ($fileGlob) {
                $fileList = array_merge($fileList, $fileGlob);
            }
        }
        $fileList = array_unique($fileList);
        // Open log file and write header
        $logExists = file_exists("style.log");
        if (!$handle = fopen("style.log", "a")) {
            die("Cannot open style!log\n");
        }
        if (!$logExists) {
            fwrite($handle, "*CheckStyle Results\n");
        }
        // Run the tests
        $errors = array();
        if (!$fileList) {
            $msg = "No source code files found";
            fwrite($handle, "$msg\n\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
            $info = "";
        } else {
            $tool = CHECK_TOOL;
            $main = CHECK_TEST;
            $allInfo = "";
            foreach($fileList as $file) {
                fwrite($handle, "Checking style: $file\n");
                $info = `java -jar $tool $main -c $this->cfg "$file"`;
                fwrite($handle, $info);
                // Count each error and report totals
                $info = preg_split('/\\n/', $info);
                array_pop($info); // remove blank line (not an error)
                array_pop($info); // remove "Audit done."
                array_shift($info); // remove "Starting audit..."
                if (isset($errors[0]) and $errors[0]
                        !== "*.java:0: File not found!") {
                    fwrite($handle, "\nStyle errors: ".count($errors)."\n");
                }
                $errors = array_merge($errors, $info);
            }
        }
        fclose($handle);
        // Clean up error messages and return error summary
        for ($i = 0; $i < count($errors); $i++) {
            $errors[$i] = substr($errors[$i], strrpos($errors[$i], ": ") + 2);
            if (strstr($errors[$i], "^[a-z][a-zA-Z0-9]*$")) {
                $errors[$i] = "Variable names start with lower case";
            } else if (strstr($errors[$i], "^[A-Z](_?[A-Z0-9]+)*$")) {
                $errors[$i] = "Constants must be all uppercase";
            } else if (strstr($errors[$i], "magic number")) {
                $errors[$i] = "Use constants rather than magic numbers";
            } else if (preg_match("/,' is not followed by white/", $errors[$i])) {
                $errors[$i] = "Put spaces after commas";
            } else if (preg_match("/private and have accessor methods/", $errors[$i])) {
                $errors[$i] = "public static variables must be final";
            } else if (preg_match("/[+-\/*]\' is not preceeded with white/", $errors[$i])) {
                $errors[$i] = "Put spaces before and after math operators";
            } else if (preg_match("/[+-\/*]\' is not followed by white/",
                    $errors[$i])) {
                $errors[$i] = "Put spaces before and after math operators";
            } else if (preg_match("/indentation level/", $errors[$i])) {
                $errors[$i] = "Wrong indentation level";
            } else if (preg_match("/Expected @param tag/", $errors[$i])) {
                $errors[$i] = "Missing @param tag";
            } else if (preg_match("/Unused import/", $errors[$i])) {
                $errors[$i] = "Remove unused import statements";
            }
        }
        $errList = array_count_values($errors);
        // Save summary in TestResult
        foreach ($errList as $err=>$count) {
            $msg = $err;
            if ($count > 1) $msg .= " (x$count)";
            $tr->add($sectionName, $this->testName, $msg, 0);
        }
        return count($errors) === 0;
    }
}
/**
    Checks code for programming style.
 */
class TestStylePHP extends TestCase {
    var $globList;
    var $log;
    /**
        Constructor with parameters for the test.
        @param $glob The file pattern to test.
     */
    function TestStylePHP($globList="*.php", $log="style.log") {
        $this->testName = get_class();
        if (!is_array($globList)) {
            $globList = array($globList);
        }
        $this->globList = $globList;
        $this->log = $log;
    }
    /**
        Checks code for style in one or more files. Detected errors are
        written to a log and a summary saved in the TestResult.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if the test passes, otherwise returns false.
     */
    function runTest(&$tr, $sectionName) {
        $errors = array();
        $fileList = array();
        foreach($this->globList as $glob) {
            $fileGlob = globr($glob, GLOB_BRACE);
            if ($fileGlob) {
                $fileList = array_merge($fileList, $fileGlob);
            }
        }
        $fileList = array_unique($fileList);
        if (!$fileList) {
            //echo "Warning: No files for PHP style: $this->glob\n";
            $errors[] = "No files to test";
            if (!$fileList) $tr->setProperty("no_files", true); //not tested
        }
        foreach($fileList as $file) {
            $contents = file_get_contents($file);
            if (!preg_match("/\/\*.*\*\//s", $contents)) {
                $errors[] = "Missing the specified PHP comment block in $file";
            } else if (!preg_match("/\/\*\*/i", $contents)) {
                $errors[] = "Missing page comment style of /** in $file"; //*/
            } else {
                if (!preg_match("/@\s*author/i", $contents)) {
                    $errors[] = "Missing or incorrect @author field in $file";
                }
                if (!preg_match("/@\s*version/i", $contents)) {
                    $errors[] = "Missing or incorrect @version field in $file";
                }
            }
        }
        // Save errors in log file
        if ($errors) {
            if (!$handle = fopen($this->log, "w")) {
                die("Cannot open $testName");
            }
            fwrite($handle, "*Programming Style Results*\n");
            foreach($errors as $line) {
                fwrite($handle, "$line\n");
            }
            fwrite($handle, "\nStyle errors: ".count($errors)."\n");
            fclose($handle);
        }
        // Clean up error messages and remove duplicates
        for ($i = 0; $i < count($errors); $i++) {
            if (substr_count($errors[$i], ":") > 0) {
                $errors[$i] = substr($errors[$i], 0,
                                     strrpos($errors[$i], ": "));
                $errors[$i] = preg_replace("/\([0-9]+\)/", "", $errors[$i]);
            }
        }
        $errList = array_count_values($errors);
        // Save summary in TestResult
        foreach ($errList as $err=>$count) {
            $msg = $err;
            if ($count > 1) $msg .= " (x$count)";
            $tr->add($sectionName, $this->testName, $msg, 0);
        }
        return count($errors) === 0;
    }
}
/**
    Validates HTML code for conformance using the W3C Markup Validator Web
    Service.
    @see http://validator.w3.org/docs/api.html
    @see http://pear.php.net/package/Services_W3C_HTMLValidator
*/
// NTR: Need to add a CSS checker using W3C
// http://pear.php.net/package/Services_W3C_CSSValidator
class TestValidateHTML extends TestCase {
    var $globList;
    var $log;
    /**
        Constructor with parameters for the test.
        @param $glob The file pattern to test.
        @param $log The file in which to save the validation results.
     */
    function TestValidateHTML($globList="*.html", $log="validate.log") {
        $this->testName = get_class();
        if (!is_array($globList)) {
            $globList = array($globList);
        }
        $this->globList = $globList;
        $this->log = $log;
    }
    /**
        Validates HTML for for conformance using the W3C Markup Validator
        Web Service. Detected errors are written to a log and a summary
        saved in the test results list.
        @param $tr The container for storing test results.
        @param $sectionName The section name for each TestResult.
        @return true if no errors are detected, otherwise returns false.
        Warnings will not cause this test to fail.
     */
    function runTest(&$tr, $sectionName) {
        if (!$this->globList) {
            $msg = "No input files";
            echo("$msg\n");
            $tr->setProperty("no_files", true);
            return false;
        }
        require_once 'Services/W3C/HTMLValidator.php';
        // Open log file and write header
        $logExists = file_exists($this->log);
        if (!$handle = fopen($this->log, 'ab')) {
            die("Cannot open $this->log\n");
        }
        if (!$logExists) { // in case test run multiple times
            fwrite($handle, "*Validation Using W3C Service*\n");
        }
        $fileList = array();
        foreach($this->globList as $glob) {
            $fileList = array_merge($fileList, glob($glob, GLOB_BRACE));
        }
        $fileList = array_unique($fileList);
        if (!$fileList) {
            if ($this->globList[0]) {
                $msg = $this->globList[0].' not found';
            } else {
                $msg = "No files to validate";
            }
            fwrite($handle, "$msg\n");
            $tr->add($sectionName, $this->testName, $msg, 0);
            $tr->setProperty("no_files", true);
            fclose($handle) or print "Could not close file: $this->log\n";
            return false;
        }
        foreach($fileList as $file) {
            $v = new Services_W3C_HTMLValidator();
            $r = $v->validateFile($file);
            if ($r and $r->isValid()) {
                $msg = "$file validates with no errors or warnings";
                fwrite($handle, "$msg.\n");
                //$tr->add($sectionName, $this->testName, $msg, 0);
            } else if ($r) {
                fwrite($handle, "$file had the following problems:\n");
                $this->writeList($handle, $r->warnings, "Warning");
                $this->writeList($handle, $r->errors, "Error");
                if ($r->errors) {
                    $msg = "Found ".count($r->errors)." errors in $file";
                    $tr->add($sectionName, $this->testName, $msg, 0);
                }
                if ($r->warnings) {
                    $msg = "Found ".count($r->warnings)." warnings in $file";
                    $tr->add($sectionName, $this->testName, $msg, 0);
                }
            } else {
                echo "Could not connect to W3C validator service.\n";
            }
        }
        fclose($handle) or print "Could not close file: $this->log\n";
        return count($r->errors) === 0; // Valid if no errors
    }
    function writeList($handle, $list, $type) {
        foreach ($list as $item) {
            $msg = " -$type: ";
            $startSize = strlen($msg);
            if ($item->line) $msg .= 'Line '.$item->line;
            if ($item->col) $msg .= ', Col '.$item->col;
            if (strlen($msg) > $startSize) $msg .= ': ';
            $message = preg_replace("/\s+/", " ", $item->message);
            $msg .= $message;
            $msg = wordwrap("$msg\n", 75, "\n  ", true);
            fwrite($handle, $msg);
        }
    }
}
?>
