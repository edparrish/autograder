<?php
require_once 'ag-config.php';
require_once ROOT_DIR.'/includes/util.php';

/**
    Finds a file using globs and filters.

    @author Edward Parrish
    @version 1.0 07/29/08
    @version 1.2 11/07/16
    @version 1.3 06/19/17
    TODO: add a findLatestFile() method.
    TODO: add a findFiles($re) that returns a list of files matching a pattern.
*/
class FileFinder {
    private $files = array();

    /**
        Constructs a FileFinder and finds all the files matching the list
        of supplied glob patterns.

        @param $globList The list of globs to use for file name matching.
        @param $rec Set true to recursively descend from $startDir.
        @param $startDir The starting directory.
     */
    public function __construct($globList="*", $rec=true, $startDir=".") {
        if (!is_array($globList)) {
            $globList = array($globList);
        }
        $this->files = $this->findFiles($globList, $rec, $startDir);
    }

    /**
        Utility function that finds all the files matching the list of
        supplied glob patterns and returns the list as an array. Does not affect this object.

        @param $globList The list of globs to use for file name matching.
        @param $rec Set true to recursively descend from $startDir.
        @param $startDir The starting directory.
        @return The array of files matching the globs.
        @since: 11/05/2012
     */
    private function findFiles($globList="*", $rec=true, $startDir=".") {
        $fileList = array();
        foreach($globList as $glob) {
            if ($rec) {
                $filesFound = globr($glob, GLOB_BRACE, $startDir);
            } else {
                //if (!is_string($glob)) {
                //    debug_print_backtrace();
                //    die;
                //}
                $filesFound = glob($glob, GLOB_BRACE);
            }
            if ($filesFound) {
                $fileList = array_merge($fileList, $filesFound);
            }
        }
        return $fileList;
    }

    /**
        Adds an array of files to this FileFinder if the files to be
        added are not already present in this FileFinder.

        @param $fileList The files to add to this FileFinder.
     */
    public function addFiles($fileList) {
        if (!is_array($fileList)) {
            $patList = array($patList);
        }
        foreach($fileList as $file) {
            if (!in_array($file, $this->files)) {
                $this->files[] = $file;
            }
        }
    }

    /**
        Test if any file in the list is a directory (folder).
        @return true if any file in the list is a directory, else false.
        @since: 2/9/2014
     */
    public function hasDirs() {
        foreach ($this->files as $index=>$file) {
            if (is_dir($file)) {
                return true;
            }
        }
        return false;
    }

    /**
        Remove all the directories (folders) in the file list.
        *Updated 2/9/2014 to use $this->files instead of $fileList*
     */
    public function removeDirs() {
        foreach ($this->files as $index=>$file) {
            if (is_dir($file)) {
                unset($this->files[$index]);
            }
        }
        $this->files = array_values($this->files);
    }

    /**
        Remove a filename if $regex has a match in filepath.

        @param $regex The regular expression to use for filepath matching.
        @param $invert Set true to keep filepaths that match $regex and
        delete those that do not match.
     */
    public function filterName($regex, $invert=false) {
        if (!$regex) {
            echo "Missing regex in FileFinder.filterName()\n";
            return;
        }
        $newList = array();
        foreach ($this->files as $fileName) {
            $isMatch = preg_match($regex, $fileName);
            if ($isMatch === 0 and  !$invert) {
                $newList[] = $fileName;
            } else if ($isMatch > 0 and $invert) {
                $newList[] = $fileName;
            }
        }
        $this->files = $newList;
    }

    /**
        Remove a filename if the file contents matches $regex.

        @param $relist A list of regular expressions to use for file content matching.
        @param $invert Set true to keep files whose content matches $regex and
        delete those that do not match.
     */
    public function filterContents($relist, $invert=false) {
        if (!$relist) {
            echo "Empty relist in FileFinder.filterContents()\n";
            return;
        }
        if (!is_array($relist)) {
            $relist = array($relist);
        }
        $newList = array();
        foreach ($this->files as $fileName) {
            if (is_file($fileName)) {
                $contents = file_get_contents($fileName);
            }
            foreach($relist as $regex) {
                $numMatches = preg_match($regex, $contents/*, $matches*/);
                //var_dump($matches);
                if ($numMatches === 0 and !$invert) {
                    $newList[] = $fileName;
                } else if ($numMatches > 0 and $invert) {
                    $newList[] = $fileName;
                }
            }
        }
        $this->files = array_unique($newList);
    }

    /**
        Returns the current number of filepaths matching the glob and filters.

        @return the current number of filepaths matching the glob and filters.
     */
    public function count() {
        return count($this->files);
    }

    /**
        Returns the current list of filepaths matching the glob and filters.

        @return the current list of filepaths matching the glob and filters.
     */
    public function files() { // was find() before 11/3/08
        return $this->files;
    }

    /**
        Returns true if a $fileName string matches any part of a filepath
        currently on the list.

        @param $fileName The file name for which to search.
        @param $caseMatters Set true if case matters.
    */
    public function fileExists($fileName, $caseMatters = false) {
        if (!$caseMatters) $fileName = strtolower($fileName);
        $found = false;
        $size =  count($this->files);
        $i = 0;
        while ($i < $size and !$found) {
            $file = $this->files[$i];
            if (!$caseMatters) $file = strtolower($file);
            if (substr_count($file, $fileName) != 0) {
                $found = true;
            }
            $i++;
        }
        return $found;
    }

    /**
        Returns the file-path of the specified fileName. If the file
        is not in the list, then returns NULL.

        @param $fileName The file name for which to search.
        @param $caseMatters Set true if case matters.
        @return the file or NULL if the file is not in the list.
        @since: 12/15/2011
    */
    public function findFilePath($fileName, $caseMatters = false) {
        if ($this->files == NULL or count($this->files) == 0) {
            return NULL;
        }
        if (!$caseMatters) $fileName = strtolower($fileName);
        foreach ($this->files as $file) {
            if (!$caseMatters && substr_count(strtolower($file), $fileName)) {
                return $file;
            } else if (substr_count($file, $fileName) != 0) {
                return $file;
            }
        }
        return NULL; // not found
    }

    /**
        Returns an array of all file-paths of the matching fileNameRE, or
        an empty array if no matches are found.

        @param $fileNameRE The regular expression to use for filepath matching.
        @return an array of any files matching the $fileNameRE.
        @since: 05/11/2019
     */
    public function findFilePathRE($fileNameRE) {
        if ($this->files == NULL) {
            return array();
        }
        $matchList = array();
        foreach ($this->files as $filePath) {
            $isMatch = preg_match($fileNameRE, $filePath);
            if ($isMatch > 0) {
                $matchList[] = $filePath;
            }
        }
        return $matchList;
    }

    /**
        Returns the first filepath on the current list matching the glob and
        filters applied so far.

        @return the first filepath matching the glob and filters.
     */
    public function findFirstFile() {
        if ($this->files == NULL or count($this->files) == 0) {
            return NULL;
        }
        return $this->files[0];
    }

    /**
        Returns the filename on the current list that requires the fewest
        number of characters to replace, insert or delete to transform the
        filepath into the target string using the Levenshtein distance.

        @param $targetName The file name for which to search.
        @param $onlyBasename Only use file basename during search.
        @return the filepath of the largest file matching the glob and filters.
     */
    public function findClosestFile($targetName, $onlyBasename = true) {
        $numFiles = count($this->files);
        if ($this->files == NULL or $numFiles == 0) {
            return NULL;
        }
        if ($onlyBasename) $targetName = basename($targetName);
        $closestName = $this->files[0];
        $name = $closestName;  // 11/26/2012
        if ($onlyBasename) $name = basename($name); // 11/26/2012
        $shortestDistance = levenshtein($targetName, $name);
        for ($i = 1; $i < $numFiles; $i++) {
            $name = $this->files[$i];
            if ($onlyBasename) $name = basename($name);
            $distance = levenshtein($targetName, $name);
            if ($shortestDistance > $distance) {
                //$closestName = $name;
                $closestName = $this->files[$i]; // 11/4/2012
                $shortestDistance = $distance;
            }
        }
        return $closestName;
    }

    /**
        Returns the largest file on the current list matching the glob and
        filters applied so far.

        @return the filepath of the largest file remaining.
     */
    public function findLargestFile() {
        $numFiles = count($this->files);
        if ($this->files == NULL or $numFiles == 0) {
            return NULL;
        }
        $largestName = $this->files[0];
        $largestSize = filesize($largestName);
        for ($i = 1; $i < $numFiles; $i++) {
            $name = $this->files[$i];
            $size = filesize($name);
            if ($largestSize < $size) {
                $largestName = $name;
                $largestSize = $size;
            }
        }
        return $largestName;
    }

    /**
        Returns an array of files on the remaining list with the
        latest (newest) modification time.

        @return an array of filepaths of the newest files remaining.
        @since: 01/17/2017
     */
    public function findNewestFiles() {
        //clearstatcache(); // Otherwise it may select wrong name
        $numFiles = count($this->files);
        if ($this->files == NULL or $numFiles == 0) {
            return NULL;
        }
        $newestNameList = array($this->files[0]);
        $newestTime = filemtime($this->files[0]);
        //echo $this->files[0].": $newestTime\n";
        for ($i = 1; $i < $numFiles; $i++) {
            $name = $this->files[$i];
            $mtime = filemtime($name);
            //echo "$name: $mtime\n";
            if ($newestTime < $mtime) { //then start fresh
                $newestNameList = array($name);
                $newestTime = $mtime;
            } else if($newestTime === $mtime) {
                $newestNameList[] = $name;
            }
        }
        return $newestNameList;
    }
}

// Uncomment following line to run unit tests.
//testFileFinder();
function testFileFinder() {
    $glob = "*.php";
    echo "Testing constructor\n";
    $ff = new FileFinder($glob, false);
    assert('$ff !== NULL');
    $count = count($ff->files());
    assert('$count > 0');
    $ff = new FileFinder($glob, true);
    assert('$ff !== NULL');
    $count = count($ff->files());
    assert('$count > 0');
    $ff = new FileFinder($glob);
    assert('$ff !== NULL');
    $count = count($ff->files());
    assert('$count > 0');

    // Apply name filters
    echo "Testing name filters\n";
    $ff = new FileFinder($glob);
    $count = count($ff->files());
    assert('$count > 0');
    $ff->filterName("/grader.php/");
    assert('$count - count($ff->files()) === 1');
    $ff->filterName("/filefinder.php/", true);
    assert('count($ff->files()) === 1');
    assert('$ff->findFirstFile() == "filefinder.php"');

    // Apply content filters
    echo "Testing content filters\n";
    $ff = new FileFinder($glob, true);
    $count = count($ff->files());
    assert('$count > 0');
    $pat = "/class"." "."Grader/"; // avoid detection in this file
    $ff->filterContents($pat);
    assert('$count - count($ff->files()) === 1');
    $relist = array("/class FileFinder/", "/function filterContents/");
    $ff->filterContents($relist, true);
    assert('count($ff->files()) === 1');
    assert('$ff->findFirstFile() == "filefinder.php"');

    echo "Testing accessor functions\n";
    // Create newest file to find later
    $handle = fopen("./newestfile.php", "w+");
    fclose($handle);
    $ff = new FileFinder("*.php");
    $count = count($ff->files());
    assert('$ff->count() == $count');
    assert('$ff->findFirstFile() === "ag-config.php"');
    assert('$ff->findClosestFile("filefinger.php") === "filefinder.php"');
    assert('$ff->fileExists("testcase.php")');
    assert('!$ff->fileExists("bogus.php")');
    assert('$ff->findFilePath("filefinder.php") === "filefinder.php"');
    assert('$ff->findFilePath("fileFinder.php", true) === NULL');
    assert('$ff->findFilePathRE("/filefinder\.php/")[0] === "filefinder.php"');
    assert('$ff->findFilePathRE("/fileFinder\.php/") === array()');
    assert('$ff->findFilePathRE("/fileFinder\.php/i")[0] === "filefinder.php"');
    $ff->filterName("/phpQuery-onefile.php/");
    //var_dump($ff->findLargestFile());
    assert('$ff->findLargestFile() === "testcase.php"');
    assert('in_array("newestfile.php", $ff->findNewestFiles())');
    // Clean up
    assert('unlink("./newestfile.php")');
    echo "...unit test complete\n";
    //var_dump($ff->files());
}
?>
