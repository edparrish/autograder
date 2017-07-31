<?php
/**
  Common utility functions included in more than one file.
*/

/**
    Copies the $src file to $dest. Creates the destination directory
    if it does not exist.

    @param $src The file to copy from.
    @param $dest The file to copy to.
 */
function copyFile($src, $dest) {
    if (!file_exists(dirname($dest))) mkdir(dirname($dest));
    if (file_exists($src)) {
        copy($src, $dest) or die("Could not copy: $src\n");
    } else {
        echo "Warning: no file to copy: $src\n";
    }
}

/**
  Returns true if files specified by $glob exist, otherwise false.

  @param $glob The file glob to find.
  @param $recFlag Set true for recursive; defaults to false.
  @return true if the file exists, otherwise false.
 */
function fileExists($glob, $recFlag = false) {
    if ($recFlag and globr($glob, GLOB_BRACE)) return true;
    else if (glob($glob, GLOB_BRACE)) return true;
    return false;
}

/**
    Recursive version of glob. Find all files meeting the pattern in this
    folder and all subfolders.

    @param string $pattern Pattern to glob for.
    @param int $nFlags Flags sent to glob.
    @param string $sDir Directory to start with.
    @return an array containing all files matching the glob pattern.
*/
function globr($pattern, $nFlags = NULL, $sDir = ".") {
    $aFiles = glob("$sDir/$pattern", $nFlags);
    foreach (glob("$sDir/*", GLOB_ONLYDIR) as $sSubDir) {
        $aSubFiles = globr($pattern, $nFlags, $sSubDir);
        $aFiles = array_merge($aFiles, $aSubFiles);
    }
    // Remove leading ./
    for ($i = 0; $i < count($aFiles); $i++) {
        if (substr($aFiles[$i], 0, 2) === "./") {
            $aFiles[$i] = substr($aFiles[$i], 2);
        }
    }
    return $aFiles;
}

/**
    Removes all files meeting the pattern in this folder and all subfolders.

    @param string $pattern Pattern to glob for.
    @param string $folder Directory to start with.
*/
function deleteGlobRec($pattern, $folder) {
    $startDir = getcwd();
    if (!chdir($folder)) {
        debug_print_backtrace(); // shows caller line number
        die("Could not change to directory: $folder\n");
    }
    $fileList = glob($pattern, GLOB_NOSORT|GLOB_BRACE);
    foreach($fileList as $file) {
        //echo "-Removing $file\n";
        unlink($file);
    }
    // Recursively descend
    foreach (glob(dirname('*').'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        deleteGlobRec($pattern, $dir);
    }
    chdir($startDir);
}

/**
  Execute command via shell for a maximum time and return command output as a string, like using backticks.

  If program has output to stderr, such as when an error occurs of using cerr, need to add on 2>&1 at end of $cmd; ERROUT leaves program with infinite loop running until console closes.
  If you see an error of "The system cannot find the path specified.", the problem may be the starting directory of proc_open().
  @param $cmd The command that will be executed.
  @param $timeSec The maximum time to execute the command in seconds.
  @return The output from the executed command or empty string when an error occurs or there is no output.
  @see: https://seriesofexp.wordpress.com/tag/proc_open/
  @see: https://github.com/adoxa/errout
 */
function shell_exec_timed($cmd, $timeSec) {
    if (!is_numeric($timeSec)) {
        echo "ERROR: timeSec NOT numeric in shell_exec_timed()\n";
        echo "cmd=$cmd\n";
        echo "timeSec=$timeSec\n";
        return;
    }
    $end = time() + $timeSec;
    $description = array (
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    $pipes = array();
    $proc = proc_open ($cmd, $description, $pipes, getcwd());
    if (!is_resource($proc)) die("Process creation failed: $cmd\n");
    // stream_set_blocking() does NOT work on Windows
    //stream_set_blocking($proc, 0); // non-blocking mode
    $output = "";
    while (!feof($pipes[1]) && time() < $end) {
        $output .= fread($pipes[1] , 2048);
    }
    //$output .= fread($pipes[2] , 2048);
    // cleanup to stop infinite loops
    $pstatus = proc_get_status($proc);
    if ($pstatus['running'] && time() >= $end) {
        $pid = $pstatus['pid'];
        kill($pid); // instead of proc_terminate($proc);
        $output .= "\nKilling process $pid for timeout > $timeSec seconds\n";
    }
    $output .= fread($pipes[2] , 2048);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);
    return $output;
}
/**
  Forces the process to terminate on either Windows or Unix.

  @param $pid The process id to terminate.
  @see: http://php.net/manual/en/function.proc-terminate.php
 */
function kill($pid) {
    return stripos(php_uname('s'), 'win') > -1
      ? exec("taskkill /F /T /PID $pid")  // windows
      : exec("kill -9 $pid");             // unix
}

// Uncomment following line to run unit tests; recomment to use
//testUtils();
function testUtils() {
    error_reporting(E_ALL | E_STRICT); // report all problems
    $pass = true; // optimistic result
    echo "...testing fileExists\n";
    $pass &= assert(fileExists('util.php'));
    $pass &= assert(!fileExists('nonesuch.42'));
    echo "...testing copyFile\n";
    copyFile("util.php", "bogus.tmp");
    $pass &= assert(fileExists('bogus.tmp'));
    unlink('bogus.tmp');
    echo "...testing globr()\n";
    $pattern = "*";
    $startDir = ".";
    $files = glob($pattern);
    $filesRec = globr($pattern, GLOB_BRACE, $startDir);
    $pass &= assert('is_array($filesRec)');
    $pass &= assert('count($filesRec) >= count($files)');
    echo "...testing deleteGlobRec()\n";
    copy('util.php', '../test/util.bak');
    deleteGlobRec('util.bak', '../test/');
    $files = globr('util.bak');
    $pass &= assert('count($files) === 0');
    echo "...testing shell_exec_timed and kill\n";
    $ts = time();
    // Compile testinf.cpp with C++ to test following line.
    //$output = shell_exec_timed("../test/testfiles/testinf.exe 2>&1", 3);
    $output = shell_exec_timed("php ../test/testfiles/testinf.php 2>&1", 3);
    $pass &= assert($output !== "");
    $result = assert(strpos($output, 'Killing process') !== false);
    if (!$result) echo "output=$output\n";
    $pass &= $result;
    $ts = time() - $ts;
    $pass &= assert($ts === 3);
    echo "...unit test completed and ";
    echo $pass ? "passed.\n" : "failed.\n";
}
?>
