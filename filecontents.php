<?php
/**
    In memory copy of the contents of a file with methods for altering the in memory contents.

    For conversion of scripts see: static function toFileContents

    @author Edward Parrish
    @version 1.0 07/25/16
    @version 1.2 11/07/16
    @version 1.3 06/19/17
*/
class FileContents {
    private $pathname = "";
    private $contents = "";

    /**
        Constructs a FileContents object and loads the $pathname.

        @param $pathname The path to and name of the file to load.
     */
    public function __construct($pathname) {
        if ($pathname) {
            if (!is_string($pathname)) {
                $msg = 'FileContents: pathname must be string, not '.gettype($pathname).'.';
                throw new InvalidArgumentException($msg);
            } else if (trim($pathname) === '') {
                $msg = 'FileContents: pathname is a blank string.';
                throw new InvalidArgumentException($msg);
            } else if (strpbrk($pathname, '*?') !== FALSE) {
                $msg = "FileContents: pathname is a glob.";
                throw new InvalidArgumentException($msg);
            }
            if (is_dir($pathname)) {
                $msg = "FileContents: pathname is a directory, not a file.";
                throw new UnexpectedValueException($msg);
            }
            $this->pathname = realpath($pathname);
            if (is_file($this->pathname)) {
                $this->contents = file_get_contents($pathname);
            } else if(!file_exists($this->pathname)) { //11/7/16
                echo "\nFileContents: File does not exist: $pathname\n";
            } else {
                echo "\nFileContents: Not a file: $pathname\n";
            }
        } else {
            $msg= "In FileContents constructor bad pathname: ($pathname)\n";
            //throw new InvalidArgumentException($msg);
        }
    }

    /**
        Returns the name of the file that has these contents, that is the
        trailing name component of pathname.

        @return the name of the file or an empty string if no pathname.
     */
    public function getName() {
        if ($this->pathname) {
            return basename($this->pathname);
        } else {
            return "";
        }
    }

    /**
        Returns the absolute pathname to the file.

        @return the absolute pathname.
     */
    public function getPathname() {
        return $this->pathname;
    }

    /**
        Returns whether or not the contents of the associated file exist.

        @return true of the contents exists; else false.
     */
    public function exists() {
        return strlen($this->contents) > 0;
    }

    /**
        Returns the length of the file contents.

        @return The length of the string; 0 if the string is empty.
        @see: http://php.net/manual/en/function.strlen.php
     */
    public function length() {
        return strlen($this->contents);
    }

    /**
        Returns whether or not the contents of the associated file is in ASCII format.

        @return true if the contents are ASCII format; else false.
     */
    public function isASCII() {
        return strlen($this->contents) === strlen(utf8_decode($this->contents));
    }

    /**
        Returns whether or not the contents of the associated file is in RTF format.

        @return true of the contents are RTF format; else false.
     */
    public function isRTF() {
        return substr($this->contents, 2, 3) === "rtf";
    }

    /**
        Returns whether or not the contents of the associated file is UTF-8 format.

        @return true of the contents are UTF-8 format; else false.
     */
    public function isUTF8() {
        if (preg_match('!!u', $this->contents)) return true;
        return false;
    }

    /**
        Converts contents from $inCharset to $outCharset character encoding.

        @param $inCharset The current character set to convert from.
        @param $inCharset The desired character set to convert to.
        @eturn true on successful conversion; otherwise false;
        @see http://php.net/manual/en/function.iconv.php
     */
    public function convCharset($inCharset="UTF-8", $outCharset="CP1252//IGNORE") {
        $retval = iconv($inCharset, $outCharset, $this->contents);
        if ($retval !== FALSE) {
            $this->contents = $retval;
            return true;
        }
        return false;
    }

    /**
        Returns part of the contents of this object.

        @param $start The index of the first character.
        @param $length The number of characters to return.
        @return The extracted string; or an empty string.
        @see: http://php.net/manual/en/function.substr.php
     */
    public function substr($start, $length) {
        return substr($this->contents, $start, $length);
    }

    /**
        Returns whether or not the underlying file exists.

        @return true of the file currently exists; otherwise returns false.
        @see: http://php.net/manual/en/function.file-exists.php
     */
    public function fileExists() {
        return file_exists($this->pathname);
    }

    /**
        Returns the current content as a string.

        @return the file contents as a string.
     */
    public function toString() {
        return $this->contents;
    }

    /**
        Returns the current file content as an array, one line per element.

        Newlines not returned at end of each line.
        @return the current file content as an array, one line per element
     */
    public function toArray() {
        return preg_split("/\r?\n/", $this->contents);
    }

    /**
        Searches for all matches to the pattern and returns the count.

        @param $pat The regular expression pattern to search with.
        @return the number of full pattern matches.
     */
    public function countMatches($pat) {
        $num = preg_match_all($pat, $this->contents, $matches);
        //var_dump($matches); // debug patterns
        return $num;
    }

    /**
        Searches contents for a match to the pattern.

        @param $pat The regular expression pattern to search with.
        @return 1 if the pattern matches given subject, 0 if it does not.
        @see: http://php.net/manual/en/function.preg-match.php
     */
    public function isMatch($pat) {
        $result = preg_match($pat, $this->contents /*, $matches*/);
        //var_dump($pat); // debug patterns
        //if ($pat == NULL) debug_print_backtrace();
        //var_dump($matches); // debug patterns
        //var_dump($result); // debug patterns
        //if ($matches == NULL) debug_print_backtrace();
        //if ($result == 0) debug_print_backtrace();
        return $result;
    }

    /**
      Extract the first pattern found from a file with subpatterns.

      @return Returns an array where $matches[0] will contain the text that matched the full pattern, $matches[1] will have the text that matched the first captured parenthesized subpattern, and so on.
      @see: http://php.net/manual/en/function.preg-match.php
    */
    public function extractFirst($pat) {
        preg_match($pat, $this->contents, $matches);
        return $matches;
    }

    /**
      Extract all of a pattern from a file with subpatterns.

      @return Returns a 2D array where the first dimension is the match for each text found. $matches[x][0] will contain the text that matched the full pattern, $matches[x][1] will have the text that matched the first captured parenthesized subpattern, and so on.
      @see: http://php.net/manual/en/function.preg-match-all.php
    */
    public function extractAll($pat) {
        preg_match_all($pat, $this->contents, $matches);
        return $matches;
    }

    /**
        Return array of entries that match the pattern.

        @param $pat The regular expression pattern to search with.
        @return An array of entries that match the pattern.
     */
    public function grep($pat) {
        return preg_grep($pat, $this->toArray());
    }

    /**
        Search and replace all text in the file matching the pattern.
     */
    public function filter($pat, $replacement="") {
        $this->contents = preg_replace($pat, $replacement, $this->contents);
        //$this->contents = preg_filter($pat, $replacement, $this->contents);
    }

    /**
        Search and replace all text matching the pattern line by line,
        allowing the use of anchors like ^ and $ for each line.
     */
    public function filterByLine($pat, $replacement="") {
        $arr = $this->toArray();
        $newContents = "";
        foreach($arr as $line) {
            $newContents .= preg_replace($pat, $replacement, $line)."\n";
        }
        // Following removes extra blank line -- why needed?
        $newContents = substr_replace($newContents, "", -1);
        $this->contents = $newContents;
    }

    /**
        Remove one or more lines from the file, with 0 being the first line.
     */
    public function removeLines($start, $numLines=1) {
        $arr = $this->toArray();
        array_splice($arr, $start, $numLines);
        $this->contents = implode("\n", $arr);
    }

    /**
        Remove C and C++ style comments from the entire file contents.

        @see: http://ostermiller.org/findcomment.html
     */
    public function stripComments() {
        // Strip comments: http://ostermiller.org/findcomment.html
        $re = '/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/';
        $this->filter($re, "");
    }

    /**
        Compress extra spaces into one and strip leading and trailing spaces.
     */
    public function trimSpaces() {
        $this->contents = preg_replace('/ {2,}/', ' ', trim($this->contents));
    }

    /**
      Line by line, compress all whitespace into a single space and strip leading and trailing spaces. Also removes extra blank lines.

      Whitespace characters are: tab (9), linefeed (10), form feed (12), carriage return (13), and space (32).
    */
    public function trimWhitespace() {
        $arr = $this->toArray();
        $newContents = "";
        foreach($arr as $line) {
            $newLine = preg_replace('/\s\s+/', ' ', trim($line))."\n";
            if ($newLine != "\n") $newContents .= $newLine;
        }
        $this->contents = $newContents;
    }

    /**
        Reload the contents from the original file.
     */
    public function reload() {
        if ($this->pathname) {
            $this->contents = file_get_contents($this->pathname);
        }
    }

    /**
        Write the current contents to the specified file.
     */
    public function save($filename) {
        file_put_contents($filename, $this->contents);
    }

    /**
        Convert a glob to an array of FileContents objects.

        @param $pathList The list of path names or FileContents
     */
    public static function toFileContents($pathList) {
        $isFF = $pathList instanceof FileFinder;
        if ($isFF) $pathList = $pathList->files();
        if (!is_array($pathList)) $pathList = array($pathList);
        $fcList = array();
        foreach ($pathList as $file) {
            if ($file instanceof FileContents) {
                $fcList[] = $file;
            } else if (is_string($file)) {
                if (!$isFF) {
                    echo "Deprecated: not FileContents obj: $file\n"; // debug
                    //debug_print_backtrace(); // shows caller line number
                }
                // Changed following to globr from glob on 9/14/2016
                $fileList = globr($file, GLOB_BRACE); // trim($file)?
                foreach ($fileList as $pathname) {
                    $fcList[] = new FileContents($pathname);
                }
            } else {
                $type = gettype($file); // changed 10/1/2017 $pathname=>$file
                $msg = "pathList must be string or FileContents, not $type.";
                throw new InvalidArgumentException($msg);
            }
        }
        return $fcList;
    }
}

// Uncomment following line to run unit tests.
//testFileContents();
function testFileContents() {
    require_once 'ag-config.php';
    require_once ROOT_DIR.'/includes/util.php';
    echo "Testing constructor\n";
    $fc = new FileContents('filecontents.php');
    assert('$fc !== NULL');
    $count = count($fc->toArray());
    assert('$count > 0');
    try {
        $fc = new FileContents($fc);
    } catch (InvalidArgumentException $iae) {
        echo 'Caught exception: ',  $iae->getMessage(), "\n";
    }
    try {
        $fc = new FileContents(' ');
    } catch (InvalidArgumentException $iae) {
        echo 'Caught exception: ',  $iae->getMessage(), "\n";
    }
    try {
        $fc = new FileContents('*.php');
    } catch (InvalidArgumentException $iae) {
        echo 'Caught exception: ',  $iae->getMessage(), "\n";
    }
    try {
        $fc = new FileContents(getcwd());
    } catch (UnexpectedValueException $uve) {
        echo 'Caught exception: ',  $uve->getMessage(), "\n";
    }

    echo "Testing accessor functions\n";
    $fc = new FileContents('filecontents.php');
    assert('strcmp($fc->getPathname(), __FILE__)');
    assert('strcmp($fc->getName(), basename(__FILE__))');
    assert('$fc->exists()');
    assert('$fc->fileExists()');
    assert('$fc->length() == strlen(file_get_contents("filecontents.php"))');

    echo "Testing filters and mutators\n";
    $fc = new FileContents('filecontents.php');
    $size = strlen($fc->toString());
    assert('$fc->isMatch("/@author\s+Ed\w*\s+Parrish/i")');
    $fc->stripComments();
    assert('strlen($fc->toString()) < $size'); //'>
    assert('$fc->isMatch("/\/\*/") == false');
    assert('$fc->isMatch("/\/\//") == false');
    //print_r($fc->toString());

    $fc->reload();
    assert('strlen($fc->toString()) === $size'); //'>
    $fc->removeLines(0);
    $arr = $fc->toArray();
    assert('$arr[0] === "/**"');
    $numLines = count($arr);
    assert('$count === ($numLines + 1)');
//    print_r($fc->toArray());

    echo "Testing static method toFileContents()\n";
    $arr = FileContents::toFileContents('*.php');
    assert('$arr !== NULL');
    assert('count($arr) == count(globr("*.php"))');
    foreach($arr as $fc) {
        assert('$fc instanceof FileContents');
    }

    // Visual tests
//    $fc = new FileContents('filecontents.php');
//    $fc->filterByLine('/^(\s*)function(.*{)$/', '\1fun\2');
//    print_r($fc->toArray());
//    print_r($fc->toString());

/*
    $fc->filter('/fun/', 'function');
    $fc->trimWhitespace();
    print_r($arr);
*/
    echo "...unit test successfully completed.\n";
}
?>
