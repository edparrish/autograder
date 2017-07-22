<?php
if (file_exists('../ag-config.php')) include_once '../ag-config.php';
require_once ROOT_DIR.'/libs/Quick_CSV_import.php';

/**
  Database class that handles daabase connections and queries.
  Provides an abstraction layer to make changing databases easier.

  @author Ed Parrish
  @version 1.0 05/20/04
  @version 1.1 07/21/17
*/
class DB {
    var $_cnx = NULL; // Result of mysqli_connect()
    var $_errLevel = "halt"; //"ignore", "halt", "warn"
    var $result = NULL; // Result of most recent mysqli_query()

    /**
     * Constructor makes initial connection to the database
     */
    function DB($errorLevel = false) {
        if ($errorLevel) $this->_errLevel = $errorLevel;
        $this->connect();
    }

    /**
     * Connects to and selects a database using dbconvars.php variables.
     */
    function connect() {
        if ($this->_cnx === NULL) {
            require ROOT_DIR.'/includes/dbconvars.php';
            @$this->_cnx = mysqli_connect($dbhost, $dbuser, $dbpwd);
            if (!$this->_cnx) {
                $this->_handleError("Connect failed.");
                return false;
            }
            if (@!mysqli_select_db($this->_cnx, $dbname)) {
                $this->_handleError("Can not select database '$dbname'.");
                return false;
            }
        }
        return $this->_cnx;
    }

    /**
    * Perform a query based on the $sql argument.
    *
    * @param $sql The SQL statements to perform.
    * @return The result set from the query.
    */
    function query($sql) {
        $sql = trim($sql);
        if (strlen($sql) == 0) return NULL;
        if (!$this->connect()) return NULL;  // connection problems
        $this->result = @mysqli_query($this->_cnx, $sql);
        if (mysqli_errno($this->_cnx)) {
            $this->_handleError("Invalid SQL: ".$sql);
        }
        return $this->result;
    }

    /**
    * Loads a CSV (comma-separated-variable) file into the database.
    *
    * @param $csvFileName The file to load.
    * @param $tableName The table name to load the data into.
    * @return true if the load was successful, otherwise false.
    */
    function loadCSV($csvFileName, $tableName) {
        $this->connect();

        // Setup
        $csv = new Quick_CSV_import($this->_cnx, $csvFileName);
        $csv->encoding = "default";
        $csv->table_name = $tableName;

        // Start import
        $csv->import();

        // Verify load
        if (!empty($csv->error)) {
            $this->_handleError("Error loading CSV file $csvFileName");
            return false;
        }
        return true;
    }

    /**
    * Error handling
    */
    function _handleError($msg) {
        if ($this->_errLevel == "ignore") return;
        echo "</td></tr></table></div>
              <b>Database error:</b> $msg<br>\n
              <b>DB Error</b>: ".mysqli_errno($this->_cnx)
              ." (".mysqli_error($this->_cnx).")<br>\n";
        if ($this->_errLevel == "halt")  die ("Session halted.");
    }
}

// Uncomment the following to run unit tests
//testDB();
function testDB() {
    error_reporting(E_ALL | E_STRICT); // report all problems
    require ROOT_DIR.'/includes/dbconvars.php';
    $pass = true; // optimistic result
    echo "...testing DB constructor\n";
    $db = new DB();
    $pass &= assert('$db->_cnx !== NULL');
    $pass &= assert('$db->_errLevel === "halt"');
    $pass &= assert('$db->result === NULL');
    echo "...testing DB connect()\n";
    $cnx = $db->connect();
    $pass &= assert('$cnx !== NULL');
    echo "...testing DB query()\n";
    $sql = "SHOW DATABASES LIKE '$dbname'";
    $result = $db->query($sql);
    $pass &= assert('$result !== NULL');
    $pass &= assert('$result->num_rows === 1');
    $result->free();
    echo "...testing DB loadCSV()\n";
    $csvFileName = realpath("../test/testfiles/roster.csv");
    $tableName = "roster";
    $end = $db->loadCSV($csvFileName, $tableName);
    $pass &= assert('$end === true');
    $sql = "SHOW TABLES LIKE '$tableName'";
    $result = $db->query($sql);
    $pass &= assert('$result->num_rows === 1');
    $result->free();
    $sql = "SELECT * FROM `$tableName` LIMIT 1";
    $result = $db->query($sql);
    $pass &= assert('$result->num_rows === 1');
    $result->free();
    echo "...unit test successfully completed.\n";
    $result = $pass ? "passed" : "failed";
    echo "...unit test $result.\n";
}
?>
