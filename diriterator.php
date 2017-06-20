<?php
include_once("/Courses/tools/autograde5/listiterator.php");

/**
    Iterates through all the directories on the list.

    @author Edward Parrish
    @version 2 6/19/2017
*/
class DirIterator extends ListIterator {
    /**
     * Updates the location to the next directory on the list.
     *
     * @return The name of the next directory.
     */
    function next() {
        $dir = parent::next();
        chdir($dir) or die("Could not change to dir: $dir\n");
        return $dir;
    }
}

// Uncomment following line to run unit tests
//testDirIterator();
function testDirIterator() {
    include_once("/Courses/tools/autograde5/util.php");
    $dirs = globr("*", GLOB_ONLYDIR, "/Courses/tools/autograde5/testfiles");
    echo "Testing constructor, iterator, hasNext, next\n";
    $it = new DirIterator($dirs);
    $cntDirs = 0;
    while ($it->hasNext()) {
        $dir = $it->next();
        assert('!strcmp($dir, $dirs[$cntDirs])');
        //print_r($dir);
        //echo "\n";
        $cntDirs++;
    }
    assert('count($dirs) === $cntDirs');
    echo "...unit test complete\n";
}
?>
