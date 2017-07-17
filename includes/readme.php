<?php
if (file_exists('../ag-config.php')) include_once '../ag-config.php';
require_once ROOT_DIR.'/filecontents.php';
require_once ROOT_DIR.'/filefinder.php';
require_once ROOT_DIR.'/includes/util.php';

/**
    Extracts data from a README.txt file.

    @author Edward Parrish
    @version 1.0 07/15/04
    @version 1.4 05/15/16
    @version 1.5 06/19/17
*/
class Readme {
    private $fileName = "";
    private $studentName = "";
    private $isExtraCredit = false;
    private $isPairProg = false;
    private $partnerName = "n/a";
    private $hoursClaim = 0;
    private $fileContents = "";

    /**
        Constructs a Readme object and discovers all the data from the
        README.txt file.
     */
    public function __construct() {
        $this->fileName = $this->findReadmeName();
        //echo "*Choosen README=$this->fileName\n";
        $this->fileContents = new FileContents($this->fileName);
        if ($this->fileName != "") {
            $this->studentName = $this->findStudentName();
            //echo "*author=".$this->studentName."\n";
            $this->hoursClaim = $this->findHoursClaim();
            //echo "*hours=".$this->hoursClaim."\n";
            $this->isExtraCredit = $this->findExtraCreditClaim();
            //echo "*extra credit=";var_dump($this->isExtraCredit);
            $this->isPairProg = $this->findPairProgClaim();
            //echo "pair program=";var_dump($this->isPairProg);
        }
    }

    // Does a README file exist? Added 1/17/2017
    public function isReadme() {
        return trim($this->fileName) !== "";
    }

    public function getExtraCreditClaim() {
        return $this->isExtraCredit;
    }

    public function getPairProgClaim() {
        return $this->isPairProg;
    }

    public function getReadmeName() {
        return $this->fileName;
    }

    public function getStudentName() {
        return $this->studentName;
    }

    public function getHoursClaim() {
        return $this->hoursClaim;
    }

    // Added 1/17/2017
    public function getReadmeFileContents() {
        return $this->fileContents;
    }

    /**
        Finds the most likely name used for the README file.

        @return The file name or "" if no name is found.
     */
    function findReadmeName() {
        $rmf = new FileFinder("*");
        $rmf->removeDirs();
        $unwantedFiles = array("/.*\~$/",
            "/SubmissionComments.txt/",
            "/DocumentIdentifier./",
            "/.*\.class/",
            "/.*\.exe/i",
            "/.*\.zip/i",
            "/.*\.7z/i",
            "/.*\.rar/i",
            "/.*\.iwa/i",
            "/.*\.plist/i",
            "/.*\.bak/i",
            "/.*\.log/i",
            "/.*\.BMP/i",
            "/.*\.JPE/i",
            "/.*\.JPG/i",
            "/.*\.JPEG/i",
            "/.*\.JFIF/i",
            "/.*\.GIF/i",
            "/.*\.INK/i",
            "/.*\.PNG/i",
            "/.*\.TIF/i",
            "/.*\.TIFF/i"
        );
        foreach ($unwantedFiles as $file) {
            $rmf->filterName($file);
        }
        if ($rmf->fileExists("README")) {
            $rmf->filterName("/README/i", true);
        } elseif ($rmf->fileExists("READ")) { //common error: read me
            $rmf->filterName("/READ.*ME/i", true);
        } else {
            $file = $rmf->findClosestFile("README.txt"); //read me.txt
            $strength = $this->readmeStrength($file);
            if ($strength > 1) return $file;
        }
        if ($rmf->count() == 0) return "";
        if ($rmf->count() == 1) {
            $file = $rmf->findFirstFile();
            $strength = $this->readmeStrength($file);
            if ($strength > 1) return $file;
        }
        // For debug, list all possible README files underconsideration
        if (DEBUG) {
            echo "\nMultiple possible README files found:\n";
            $this->listFiles($rmf->files());
        }
        // If one file is the exact name, use it?
        $count = 0;
        $exactName = "";
        foreach ($rmf->files() as $file) {
            if ("readme.txt" === strtolower(basename($file))) {
                $count++;
                $exactName = $file;
            }
        }
        if ($count === 1) {
            $strength = $this->readmeStrength($exactName);
            if ($strength > 1) {
                if (DEBUG) echo "Single exact name README file chosen ($strength): $exactName\n";
                return $exactName;
            }
        }
        // Look for the most recent files
        $fileList = $rmf->findNewestFiles();
        if (count($fileList) === 1) {
            $file = $fileList[0];
            $strength = $this->readmeStrength($file);
            if ($strength > 1) {
                if (DEBUG) echo "Single newest README file chosen ($strength): $file\n";
                return $file;
            }
        }
        // For debug, list all recent README files underconsideration
        //echo "Most recent README files found:\n";
        //$this->listFiles($fileList);
        // Choose the most likely candidate based on "strength"
        $maxStrength = 0;
        $maxFile = $fileList[0];
        foreach ($fileList as $file) {
            $strength = $this->readmeStrength($file);
            //echo "file=$file strength=$strength\n";
            if ($strength >= $maxStrength) {
                $maxStrength = $strength;
                $maxFile = $file;
            }
        }
        if ($maxStrength > 3) { // was 1 until 2/13/17
            if (DEBUG) echo "Strongest ($maxStrength) newest README file chosen: $maxFile\n";
            return $maxFile;
        }
        if (DEBUG) echo "No appropriate README found\n";
        return "";
    }

    // For debug
    private function listFiles($fileList) {
        $count = 1;
        foreach ($fileList as $file) {
            echo " $count: $file\n";
            $count++;
        }
    }

    /**
        Determine the relative probability of the file being a README file
        based on expected features.

        @return The count of relative strength, with 0 being unlikely and a
        higher number being more likely.
     */
    public function readmeStrength($fileName) {
        $rmfc = new FileContents($fileName);
        $count = 0;
        if (!$rmfc->exists()) return $count;
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (preg_match("/read.?me[\d-]*.txt/i", $fileName)) $count += 2;
        if ($ext !== "txt" && $ext !== "") $count--;
        if ($rmfc->isMatch("/$\s*Author/im")) $count++;
        if ($rmfc->isMatch("/\bEmail\b/i")) $count++;
        if ($rmfc->isMatch("/\b\S+@\S+\.\S+/i")) $count++; // actual email
        if ($rmfc->isMatch("/\bPartner\b/i")) $count++;
        if ($rmfc->isMatch("/\bOS\b/i")) $count++;
        if ($rmfc->isMatch("/\bAsn\b/i")) $count++;
        if ($rmfc->isMatch("/\bStatus\b/i")) $count++;
        if ($rmfc->isMatch("/\bFiles\b/i")) $count++; // 20q
        if ($rmfc->isMatch("/\bREADME\b/i")) $count++; // 20q
        if ($rmfc->isMatch("/\bHours\b/i")) $count++; // 20q
        if ($rmfc->isMatch("/\bHours\s*Total\b/i")) $count++;
        if ($rmfc->isMatch("/\bTITLE\b/i")) $count++;
        if ($rmfc->isMatch("/\bPURPOSE|PROJECT\b/i")) $count++;
        if ($rmfc->isMatch("/\bUSER\b/i")) $count++;
        if ($rmfc->isMatch("/\bVERSION|DATE\b/i")) $count++;
        return $count;
    }

    /**
        Lookup the students name in README.txt file.

        @return The student's name or "" if no name is found.
     */
    function findStudentName() {
        $pat="/(?<!Partner)[^A]*(AUTHOR[\(S\)]*|name):[\n\s]*([^}{\\\n\r]{2,50})/i"; //"
        $parts = $this->fileContents->extractFirst($pat);
        if (isset($parts[2])) return trim($parts[2]);
        $firstLine = $this->fileContents->extractFirst("/\S+\s+[^\n]+/");
        if (isset($firstLine[0])) return $firstLine[0];
        return "Name not found";
    }

    /**
        Finds the total number of hours claimed in the README file.

        @return the total number of hours claimed or 0 if not found.
     */
    function findHoursClaim() {
        $pat = "/hour[^\d]*total[^\d.]*([\d.]+)/i";
        $rmfc = $this->fileContents;
        $parts = $rmfc->extractFirst($pat);
        if (isset($parts[1])) return floatval($parts[1]);
        $pat = "/\b\d*\.?\d+[\r\n]/";
        $parts = $rmfc->extractAll($pat);
        $numbers = $parts[0];
        // find largest and filter out improbable numbers
        $max = -1;
        foreach ($numbers as $num) {
            if (substr($num, 0, 2) !== "00" && floatval($num) < 99
            && $num > $max) {
                $max = $num;
            }
        }
        if ($max > -1) return floatval($max);
        return 0;
    }

    /**
        Finds whether or not extra credit was claimed in the README file.

        @return true if extra-credit was claimed, otherwise false.
     */
    function findExtraCreditClaim() {
        $rmfc = $this->fileContents;
        $negPat = "/\bnone\b|\bnot\b|\bno\b|\bn\/a\b|\b0\b/i"; // neg answer
        $pat="/\b\d*\.?\d+[\r\n]+[^exh]*Extr[a -]*Credit:?([^\r\n]*)[\r\n][^\r\n\w]*([\w. -]*)/i";
        $parts = $rmfc->extractFirst($pat);
        if (isset($parts[1]) && trim($parts[1]) !== "") { //same line
            if (preg_match($negPat, $parts[1])) return false;
            return true; // same line listing
        }
        if (!isset($parts[2])) return false; // no next line
        $xclist = $parts[2]; // next line list of extra credit
        if (preg_match($negPat, $xclist)) return false;
        if (!preg_match("/\w|\./", $xclist)) return false;
        return true;
    }

    /**
        Finds whether or not a partner was claimed in the README file and
        records partner name if found.

        @return true if partner was claimed, otherwise false.
     */
    function findPairProgClaim() {
        $rmfc = $this->fileContents;
        $pat = "/Parte?ner:?\s*(\b\w{3,}\b)/i"; // updated 9/30/15
        $parts = $rmfc->extractFirst($pat);
        if (isset($parts[1])
        && strtolower($parts[1]) != "none"
        && strtolower($parts[1]) != "n/a"
        && strtolower($parts[1]) != "no"
        && strtolower($parts[1]) != "not"
        && strtolower($parts[1]) != "self"
        && strtolower($parts[1]) != "os") {
            $this->partnerName = $parts[1];
            return true;
        }
        // Look for hours working with partner > 0
        $pat = "/with\s*parte?ner[: ]*([0-9.]+).*\n/i"; // updated 10/2/15
        $parts = $rmfc->extractFirst($pat);
        if (isset($parts[1]) && floatval($parts[1]) > 0) return true;
        // Look for a claim of pair programming
        if ($rmfc->extractFirst("/Pair(\s+|-)?program/i")) return true;
        return false;
    }
}

// Uncomment the following to run unit tests
//testReadme();
function testReadme() {
    define("DEBUG", true);
    error_reporting(E_ALL | E_STRICT); // report all problems
    $path = ROOT_DIR.'/test/testfiles/studentGood';
    chdir($path) or die("Could not change to path: $path\n");
    echo "Testing Readme functions in $path:\n";

    echo "Testing constructor\n";
    $r = new Readme();
    if ($r->getReadmeName() != "README.txt") {
        echo "Wrong file name!".$r->getReadmeName()."\n";
    }
    echo "Testing getStudentName()\n";
    if ($r->getStudentName() != "Ed Parrish") {
        echo "Wrong student name!\n";
        var_dump($r->getStudentName());
    }
    echo "Testing getHoursClaim()\n";
    if ($r->getHoursClaim() != 2) {
        echo "Wrong hours claim!\n";
        var_dump($r->getHoursClaim());
    }
    echo "Testing getExtraCreditClaim()\n";
    if (!$r->getExtraCreditClaim()) {
        echo "Wrong extra-credit claim!\n";
        var_dump($r->getExtraCreditClaim());
    }
    echo "Testing getPairProgClaim()\n";
    if (!$r->getPairProgClaim()) {
        echo "Wrong pair programming claim!\n";
        var_dump($r->getPairProgClaim());
    }

    echo "...unit test successfully completed.\n";
}
?>
