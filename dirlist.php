<?php
include_once("/Courses/tools/autograde5/diriterator.php");

/**
    Iterates through all the subdirectories of a given path. Optionally,
    will iterate through only those subdirectories specified by a list.

    @author Edward Parrish
    @version 2 6/19/2017
*/
class DirList {
    var $index = OFF_LIST;
    var $list = array();
    var $path;

    /**
     * Constructs a DirList using the path and an optional subdirectory list.
     */
    function DirList($path, $subdirs = false) {
        $this->path = realpath($path);
        chdir($path) or die("Could not change to path: $path\n");
        if (is_array($subdirs)) {
            if (count($subdirs) == 0) echo "Empty subdirs list!\n";
            foreach ($subdirs as $dir) {
                $dir = $this->path."/".$dir;
                // Check subdirectory is valid
                chdir($dir) or die("Could not change to directory: $dir\n");
                $this->list[] = $dir;
            }
            chdir($path) or die("Could not change to path: $path\n");
        } else if ($dh = opendir($path)) {
            while (false !== ($dir = readdir($dh))) {
                if ($dir !== "." and $dir !== ".." and is_dir($dir)) {
                    $this->list[] = $this->path."/".$dir;
                }
            }
            closedir($dh);
        } else {
            die("Could not open test directory");
        }
    }

    /**
     * Returns the path leading to the current directory.
     */
    function getPath() {
        return $this->path;
    }

    /**
     * Returns the number of directories in the list.
     */
    function count() {
        return count($this->list);
    }

    /**
     * Returns an iterator over the subdirectories of the path.
     */
    function iterator() {
        return new DirIterator($this->list);
    }
}

// Uncomment following line to run unit tests
//testDirList();
function testDirList() {
    echo "Testing constructor, iterator, hasNext, next with all directories\n";
    $path = "/Courses/tools/autograde5/testfiles";
    $dl = new DirList($path);
    $it = $dl->iterator();
    $subdirs = array();
    while ($it->hasNext()) {
        $dir = $it->next();
        $subdirs[] = basename($dir);
        print_r($dir);
        echo "\n";
    }
    assert('count($subdirs) >= 0');
    echo "Testing constructor, iterator, hasNext, next with select subdirs\n";
    if (count($subdirs) > 2) array_shift($subdirs); // remove first element
    if (count($subdirs) > 2) array_pop($subdirs); // remove last element
    $cntDirs = 0;
    $dl = new DirList($path, $subdirs);
    $it = $dl->iterator();
    while ($it->hasNext()) {
        $cntDirs++;
        $it->next();
    }
    assert('count($subdirs) === $cntDirs');
    echo "Testing getPath\n";
    assert('!strcmp($dl->getPath(), realpath($path))');
    echo "Testing count\n";
    assert('$dl->count() === $cntDirs');
    echo "...unit test complete\n";
}
?>
