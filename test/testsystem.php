<?php
// Test all aspects of system
include_once("../grader.php");
define("TEST_DIR", ROOT_DIR."/test/testfiles");
define("STYLE_CPP", ROOT_DIR."/test/style-cpp.txt");
define("STYLE_JAVA", ROOT_DIR."/test/style-java.xml"); // common/supplements
define("CODELAB_TABLE", "roster");

$students = null;
$students = array("studentGood"/*, "studentPoor"*/);

// Put the tests here but call runTest() to run tests on all students
class GradeRunner extends Grader {
    private $codeLabFile = "roster.csv";
    private $glob;
    private $cppFile;
    private $baseName;
    private $javaFile;
    private $phpFile;
    private $htmlFile;
    private $dbFile;
    private $query3File;

    // Load CodeLab roster.csv files into database.
    // rostertweaks.sql has updates for students who signup with wrong name
    // Must repeat using preTest() for every student because of database tests.
    //function startTest() {
    function preTest() {
        //parent::startTest();
        parent::preTest();
        $this->codeLabFile = realpath("../roster.csv");
        $codeLabTweaks = realpath("../rostertweaks.sql");
        fileExists($this->codeLabFile) or
            die("Missing CodeLab results file: $this->codeLabFile\n");
        $this->loadCodeLab($this->codeLabFile, CODELAB_TABLE, $codeLabTweaks);
    }

    function test() {
        // Setup
        $this->glob = "prod*.cpp";
        $ff = new FileFinder($this->glob);
        $this->cppFile = $ff->findFirstFile();
        if (!assert(fileExists($this->cppFile))) echo 'Missing C++ File'."\n";
        $this->baseName = basename($this->cppFile, ".cpp"); // file of file.ext
        $this->javaFile = "ArrayUtil.java";
        if (!assert(fileExists($this->javaFile))) echo 'Missing javaFile'."\n";
        $ff = new FileFinder("*.sql");
        $this->dbFile = $ff->findLargestFile();
        if (!assert(fileExists($this->dbFile))) echo 'Missing dbFile'."\n";
        $ff = new FileFinder("query3.txt");
        $this->query3File = $ff->findFirstFile();
        if (!assert(fileExists($this->query3File))) echo 'Missing query File'."\n";
        $ff = new FileFinder("about.php");
        $this->phpFile = $ff->findFirstFile();
        $this->htmlFile = "out.html";
        if (!assert(fileExists($this->phpFile))) echo 'Missing phpFile'."\n";
        // Solving the includes path problem
        $webapproot = dirname($this->phpFile);
        if ($webapproot) $webapproot .="/";
        $dbconvars = ROOT_DIR.'/includes/dbconvars.php';
        copyFile($dbconvars, $webapproot."includes/dbconvars.php");
        if (!assert(fileExists($dbconvars))) echo 'Missing dbconvars'."\n";
        assert(fileExists($webapproot."includes/dbconvars.php"));
        echo "\n";

        // tests
        $this->testBasics();
        $this->testFileExists();
        $this->testCodeLab();
        $this->testLoadDB();
        $this->testRunLogSQL();
        $this->testCompileCPP();
        $this->testRunMatch();
        $this->testStyleCPP();
        $this->testCompileJava();
        $this->testJavaUnit();
        $this->testStyleJava();
        $this->testStylePHP();
        $this->testHTML();
        $this->testReadme();

        // Subtotal score
        $subtotal = $this->getScore();
        $maxScore = $this->getMaxScore();
        $this->writeGradeLog("Subtotal Score: $subtotal of $maxScore\n");

        $this->testExtraCredit();
        $this->testReportOverall($maxScore);

        echo "Testing completed!\n";
        //var_dump($this->getResults());
    }

    function testBasics() {
        echo "...testing properties with testResultList\n";
        $this->setProperty("testProp", false);
        assert('$this->getProperty("testProp") === false');

        echo "...testing section name\n";
        $sectionName = "testBasics";
        $this->setSectionName($sectionName);
        assert('$this->getSectionName() === $sectionName');

        echo "...testing run() with TestCondition\n";
        $pass = $this->run(new TestCondition(true,
            "TestCondition msg run", -2));
        assert('$pass === true');
        assert('$this->isErrorMessage("TestCondition msg run")');
        $pass = $this->run(new TestCondition(false,
            "TestCondition msg run2", -2));
        assert('$pass === false');
        assert('!$this->isErrorMessage("TestCondition msg run2")');
        $this->run(new TestCondition(true, "Did an extra thing", 2));

        echo "...testing pass() with TestCondition\n";
        $pass = $this->pass(new TestCondition(true,
            "TestCondition msg true pass", 1), -2, "pass() message");
        assert('$pass === true');
        assert('$this->isErrorMessage("pass() message")');
        $pass = $this->pass(new TestCondition(false,
            "TestCondition msg false pass", 0), -2, "pass() message2");
        assert('$pass === false');
        assert('!$this->isErrorMessage("pass() message2")');

        echo "...testing fail() with TestCondition\n";
        $pass = $this->fail(new TestCondition(true,
            "TestCondition msg true fail", 1), -2, "fail() message");
        assert('$pass === true');
        assert('!$this->isErrorMessage("fail() message")');
        $pass = $this->fail(new TestCondition(false,
            "TestCondition msg false fail", 0), -2, "fail() message");
        assert('$pass === false');
        assert('$this->isErrorMessage("fail() message")');

        echo "...testing passFail() with TestCondition\n";
        $pass = $this->passFail(new TestCondition(true), 2, "pass msg",
            -2, "fail msg");
        assert('$pass === true');
        assert('$this->isErrorMessage("pass msg")');
        assert('!$this->isErrorMessage("fail msg")');
        $pass = $this->passFail(new TestCondition(false), 2, "pass msg2",
            -2, "fail msg2");
        assert('$pass === false');
        assert('!$this->isErrorMessage("pass msg2")');
        assert('$this->isErrorMessage("fail msg2")');

        echo "...testing ValueEvaluator\n";
        $score = $this->report(new ValueEvaluator(10, 10, 0, .9, 1),
            "ValueEvaluator Score:", true, $sectionName);
    }

    function testFileExists() {
        $this->setSectionName("testFileExists");

        echo "...testing TestFileExists\n";
        $pass = $this->run(new TestFileExists($this->glob));
        assert('$pass === true');
        $pass = $this->run(new TestFileExists("bogus.txt"));
        assert('$pass === false');
        $pass = $this->pass(new TestFileExists($this->glob), 2,
            "Found file: $this->cppFile");
        assert('$pass === true');
        assert('$this->isErrorMessage("Found file: $this->cppFile")');
        $pass = $this->pass(new TestFileExists("bogus.txt"), -2,
            "Found file: bogus.txt");
        assert('$pass === false');
        assert('!$this->isErrorMessage("Found file: bogus.txt")');
        $pass = $this->fail(new TestFileExists($this->glob), -2,
            "Missing file: $this->cppFile");
        assert('$pass === true');
        assert('!$this->isErrorMessage("Missing file: $this->cppFile")');
        $pass = $this->fail(new TestFileExists("bogus.txt"), -2,
            "Missing file: bogus.txt");
        assert('$pass === false');
        assert('$this->isErrorMessage("Missing file: bogus.txt")');
        $score = $this->report(new ValueEvaluator(2, 2),
            "TestFileExists Score:", true);
        assert('is_numeric($score)');
        assert('$score >= 0');
        assert('2 >= $score');
    }

    function testLoadDB() {
        $this->setSectionName("testLoadDB");
        echo "...testing TestLoadDB\n";
        // This should fail
        $pass = $this->run(new TestLoadDB(""));
        assert('$pass === false');
        assert('$this->getProperty("dbloaded") === false');
        // This should fail
        $pass = $this->run(new TestLoadDB("bogus.sql"));
        assert('$pass === false');
        assert('$this->getProperty("dbloaded") === false');
        // This should pass if the student did it correctly
        $pass = $this->run(new TestLoadDB($this->dbFile));
        assert('is_bool($pass)');
        assert('is_bool($this->getProperty("dbloaded"))');
        $score = $this->report(new LoadDBEvaluator(4),
            "Database Export Score:");
        assert('is_numeric($score)');
        assert('$score >= 0');
        assert('4 >= $score');
    }

    function testRunLogSQL() {
        $this->setSectionName("testRunLogSQL");
        echo "...testing TestRunLogSQL\n";
        // This should fail
        $pass =  $this->fail(new TestRunLogSQL("", "bogusempty.log"), -1,
            "Empty query produced no output");
        assert('$pass === false');
        // This should fail
        $pass =  $this->fail(new TestRunLogSQL("bogus sql", "bogusql.log",
            "artzy"), -1, "Bogus query failed");
        assert('$pass === false');
        // This should pass if the student did it correctly
        $studentSql = file_get_contents($this->query3File);
        $studentSql = trim($studentSql);
        $pass =  $this->run(new TestRunLogSQL($studentSql, "bonusquery.log",
            "artzy"), $studentSql);
        assert('is_bool($pass)');
        echo "...testing TestCompareSQL\n";
        // This should fail
        $sql = "SELECT SupplierName, ProductName
                FROM suppliers LEFT JOIN products
                ON suppliers.ID = products.SupplierID;";
        $pass = $this->fail(new TestCompareSQL($sql, "bogus", "artzy"), -1,
            "Error in bogus query", $studentSql);
        assert('$pass === false');
        // This should pass if the student did it correctly
        $pass = $this->fail(new TestCompareSQL($sql, $studentSql, "artzy"), -1,
            "Error in query 3", $studentSql);
        assert('is_bool($pass)');
        $score = $this->report(new ValueEvaluator(4),
            "SQL Queries Score:");
        assert('is_numeric($score)');
        assert('$score >= 0');
        assert('4 >= $score');
    }

    function testCodeLab() {
        $this->setSectionName("testCodeLab");
        echo "...testing TestCodeLab\n";
        flush();
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();
        $this->run(new TestCodeLab(CODELAB_TABLE, $lastName, $firstName));
        $score = $this->report(new CodeLabEvaluator(1.75, 1.75, 0, 10),
            "CodeLab Score:");
        assert('is_numeric($score)');
        assert('$score >= 0');
        assert('10 >= $score');
    }

    function testCompileCPP() {
        $this->setSectionName("testCompileCPP");
        echo "...testing TestCompileCPP\n";
        // This should fail since there is no bogus.cpp
        $cmd = "g++ -W -Wall --pedantic -o $this->baseName bogus.cpp";
        $path = dirname($testFile);
        //$pass = $this->run(new TestCompileCPP($cmd));
        $pass = $this->run(new TestCompileCPP("bogus.cpp"));
        assert('$pass === false');
        assert('$this->getProperty("compiles") === false');
        $this->resetTestResults();
        // This should fail because there is no makefile
        $pass = $this->run(new TestCompileCPP(".", "make"));
        assert('$pass === false');
        assert('$this->getProperty("compiles") === false');
        $this->resetTestResults();
        // This should pass if the file is present
        //$cmd = "g++ -W -Wall --pedantic -o $this->baseName $this->cppFile";
        $this->pass(new TestCompileCPP($this->cppFile), 1, "No errors during compile");
        assert('$this->getProperty("compiles") !== NULL');
        $this->report(new CompileEvaluator(4, 4), "CPP Compilation Score:");
    }

    function testRunMatch() {
        $this->setSectionName("testRunMatch");
        $compiles = $this->getProperty("compiles");
        echo "...testing runLogCmd()\n";
        if (fileExists("out1.log")) unlink("out1.log");
        $info = $this->runLogCmd($this->baseName, "out1.log");
        assert(fileExists("out1.log"));
        if (fileExists("out2.log")) unlink("out2.log");
        $this->runLogCmd($this->baseName, "out2.log", $compiles);
        assert(fileExists("out2.log"));

        $fc1 = new FileContents("out1.log");
        $fc2 = new FileContents("out2.log");

        echo "...testing TestMatch\n";
        // This should fail since there is no bogus word in the output
        $pass = $this->fail(new TestMatch("/bogus/", $fc2), -1,
            "A TestMatch bogus error to report");
        assert('$pass === false');
        assert('$this->isErrorMessage("A TestMatch bogus error to report")');
        // This should pass since a dot matches anything
        $pass = $this->pass(new TestMatch("/./", $fc2), 1,
            "A dot matches anything", $fc2->exists());
        assert('$pass === true');
        assert('$this->isErrorMessage("A dot matches anything")');
        $patList = array("/Milk/", "/3.95/", "/40/");
        $this->fail(new TestMatch($patList, $fc2), -2,
            "Did not list the Milk product correctly");

        echo "...testing TestMatchAny\n";
        // This should fail since there is no bogus word in the output
        $pass = $this->fail(new TestMatchAny("/bogus/", $fc2), -1,
            "A TestMatchAny bogus error to report");
        assert('$pass === false');
        assert('$this->isErrorMessage("A TestMatchAny bogus error to report")');

        echo "...testing TestMatchCount\n";
        // This should fail since there is no bogus word in the output
        $pass = $this->fail(new TestMatch("/bogus/", $fc2, 2, 4), -1,
            "A TestMatchCount bogus error to report");
        assert('$pass === false');
        assert('$this->isErrorMessage("A TestMatchCount bogus error to report")');
        // This should pass if the program ran
        $pat = "/\s*\w+\s+[0-9.]+\s+[0-9]+\s+[0-9.]+/i";
        $this->fail(new TestMatchCount($pat, $fc2, 1, 3), 1,
            "Does not display at least 3 objects");

        echo "...testing TestCompareFiles\n";
        // Comparing file to itself should pass if the file exists
        $cppFC = new FileContents($this->cppFile);
        $javaFC = new FileContents($this->javaFile);
        $pass = $this->pass(new TestCompareFiles($cppFC, $cppFC, true), 2,
            "$this->cppFile compares to itself");
        assert('$pass === true');
        assert('$this->isErrorMessage("$this->cppFile compares to itself")');
        $pass = $this->fail(new TestCompareFiles($cppFC, $javaFC, true), -2,
            "$this->cppFile does not compare to $this->javaFile");
        assert('$pass === false');
        assert('$this->isErrorMessage("$this->cppFile does not compare to $this->javaFile")');

        echo "...testing PointMapEvaluator\n";
        $pointScoreMap = array(10=>5, 9=>4, 6=>3, 4=>2, 1=>1);
        $score = $this->report(new PointMapEvaluator($pointScoreMap, 10),
            "Run and Match Score:");
        assert('is_numeric($score)');
        assert('$score >= 0');
        assert('5 >= $score');
    }

    function testStyleCPP() {
        $this->setSectionName("testStyleCPP");
        echo "...testing TestStyleCPP\n";
        $cppFC = new FileContents($this->cppFile);
        $pass = $this->run(new TestStyleCPP($cppFC, STYLE_CPP));
        assert('is_bool($pass)');
        $score = $this->report(new StyleEvaluator(4),
            "CPP Style Score:");
        assert('is_numeric($score)');
    }

    function testCompileJava() {
        $this->setSectionName("testCompileJava");
        echo "...testing TestCompileJava\n";
        $this->removeProperty("compiles");
        assert('$this->getProperty("compiles") === NULL');
        // This should fail since there is no bogus.java
        $cmd = "javac bogus.java";
        $pass = $this->run(new TestCompileJava($cmd));
        assert('$pass === false');
        assert('$this->getProperty("compiles") === false');
        $this->resetTestResults();
        // This should pass if a syntactically correct Java file is present
        $this->pass(new TestCompileJava(), 1, "No errors during compile");
        $compiles = $this->getProperty("compiles");
        assert('$compiles !== NULL');
        $this->report(new CompileEvaluator(4, 4), "Java Compilation Score:");
    }

    function testJavaUnit() {
        $this->setSectionName("testJavaUnit");
        echo "...testing TestJavaUnit\n";
        // This should fail since there is no Bogus.java file
        $pass = $this->fail(new TestJavaUnit("Bogus.java"), -2,
            "Bogus.java failed");
        assert('$pass === false');
        assert('$this->isErrorMessage("Bogus.java failed")');
        // This will pass if done correctly
        $pass = $this->pass(new TestJavaUnit("ArrayUtilTest.java"), 1,
            "ArrayUtilTest passed");
        if (file_exists("ArrayUtilTest.java")) assert('$pass === true');
        assert('$this->isErrorMessage("ArrayUtilTest passed")');
        $score = $this->report(new ValueEvaluator(10),
            "Java Unit Score:", true);
    }

    // NTR: need to have a badly commented Java file and check the messages
    function testStyleJava() {
        $this->setSectionName("testStyleJava");
        echo "...testing TestStyleJava\n";
        $pass = $this->run(new TestStyleJava(STYLE_JAVA, $this->javaFile));
        assert('is_bool($pass)');
        $score = $this->report(new StyleEvaluator(4), "Java Style Score:");
        assert('is_numeric($score)');
    }

    function testStylePHP() {
        $this->setSectionName("testStylePHP");
        echo "...testing TestStylePHP\n";
        $pass = $this->run(new TestStylePHP($this->phpFile));
        assert('is_bool($pass)');
        $score = $this->report(new StyleEvaluator(2),
            "PHP Documentation Score:");
        assert('is_numeric($score)');
    }

    function testHTML() {
        $this->setSectionName("testHTML");
        echo "...testing TestRunPage\n";
        // This should fail
        $pass = $this->run(new TestRunPage("bogus.php", "bogus.html"));
        assert('$pass === false');
        assert('!file_exists("bogus.html")');
        // This may pass if the file is good
        $pass = $this->run(new TestRunPage($this->phpFile, $this->htmlFile));
        assert('is_bool($pass)');
        assert('file_exists($this->htmlFile)');

        echo "...testing TestValidateHTML\n";
        // This should fail
        $pass = $this->run(new TestValidateHTML("bogus.html"));
        assert('$pass === false');
        // This may pass if the file is good
        $pass = $this->run(new TestValidateHTML($this->htmlFile));
        assert('is_bool($pass)');
        // This may pass if the file is good
        $pass = $this->run(new TestValidateHTML("mypage.html"));
        assert('is_bool($pass)');
//var_dump($this->results);

        $score = $this->report(new StyleEvaluator(4),
            "HTML Validation Score:");
        assert('is_numeric($score)');
        assert('$score >= 0');
        assert('2 >= $score');
    }

    function testReadme() {
        $this->setSectionName("testReadme");
        echo "...testing TestReadme\n";
        $pass = $this->run(new TestReadme($this));
        assert('is_bool($pass)');
        $score = $this->report(new ReadmeEvaluator(2),
            "README.txt Score:");
    }

    function testExtraCredit() {
        $this->setSectionName("testExtraCredit");
        echo "...testing TestExtraCreditClaim\n";
        // NTR: should I keep TestExtraCreditClaim
        $pass = $this->run(new TestExtraCreditClaim($this));
        assert('is_bool($pass)');
        $pass = $this->fail(new TestExtraCreditClaim($this), -1,
            "None claimed in README.txt");
        assert('is_bool($pass)');
        $pass = $this->pass(new TestExtraCreditClaim($this), 1,
            "Claimed extra credit in README.txt");
        assert('is_bool($pass)');
        $pass = $this->fail(new TestCondition($this->isExtraCredit()), -1,
            "None claimed in README.txt (tc)");
        assert('is_bool($pass)');
        $pass = $this->pass(new TestCondition($this->isExtraCredit()), 1,
            "Claimed extra credit in README.txt (tc)");
        assert('is_bool($pass)');

        echo "...testing TestPairProgClaim\n";
        // NTR: should I keep TestPairProgClaim
        $pass = $this->pass(new TestPairProgClaim($this), 1,
            "Used pair programming");
        assert('is_bool($pass)');
        $pass = $this->fail(new TestPairProgClaim($this), 0,
            "No pair programming claimed in README");
        assert('is_bool($pass)');
        $pass = $this->pass(new TestCondition($this->isPairProg()), 1,
            "Used pair programming (tc)");
        assert('is_bool($pass)');
        $pass = $this->fail(new TestCondition($this->isPairProg()), -1,
            "No pair programming claimed in README (tc)");
        assert('is_bool($pass)');

        echo "...testing for points added (like extra credit)\n";
        $pass = $this->pass(new TestCondition(true), 2,
            "TestCondition message xc");
        assert('$pass === true');
        assert('$this->isErrorMessage("TestCondition message xc")');
        $cppFC = new FileContents($this->cppFile);
        $pass = $this->pass(new TestMatch("/toString\s*\(/i", $cppFC), 2,
            "Added function toString()");
        $score = $this->report(new ValueEvaluator(4, 4),
            "Extra Credit Score:", true);
    }

    function testReportOverall($maxScore) {
        echo "...testing reportOverall and commentFromPercentage\n";
        $gradePercentage = $this->reportOverall($maxScore, true);
        //assert('is_int($gradePercentage)');
        assert('$gradePercentage >= 0');
        // Manually calulating the overall comment
        $percentage = intval($this->score / $maxScore * 100);
        $comments = array(
            100=>"$gradePercentage% is awesome!",
            90=>"$gradePercentage%! Way to go, dude!",
            85=>"$gradePercentage%: Pretty good with a few minor probs",
            80=>"Overall, $gradePercentage% is good work.",
            70=>"Squeaking by at $gradePercentage%, dude.",
            60=>"$gradePercentage% -- Barely making it.",
            0=>"$gradePercentage%. Time to work harder, dude."
        );
        $overallComment = $this->commentFromPercentage($percentage, $comments);
        $this->writeGradeLog("$overallComment\n");
        assert('is_string($overallComment)');
    }
}

$grader = new GradeRunner(TEST_DIR, $students);
$grader->runTest();
?>
