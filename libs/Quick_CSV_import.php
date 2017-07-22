<?php

/*
 Quick_CSV_import class provides interface to a quick CSV file to MySQL database import. Much quicker (10-100 times) that line by line SQL INSERTs.
 version: 1.5
 author: Skakunov Alexander <i1t2b3@gmail.com>
 date: 23.8.2006
 description:
   1. Before importing, you MUST:
     - establish connection with MySQL database and select database;
     - define CSV filename to import from.
   2. You CAN define several additional import attributes:
     - use CSV header or not: if yes, first line of the file will be recognized as CSV header, and all database columns will be called so, and this header line won't be imported in table content. If not, the table columns will be calles as "column1", "column2", etc
     - separate char: character to separate fields, comma [,] is default
     - enclose char: character to enclose those values that contain separate char in text, quote ["] is default
     - escape char: character to escape special symbols like enclose char, back slash [\] is default
     - encoding: string value which represents MySQL encoding table to parse files with. It's strongly recomended to use known values, not manual typing! Use get_encodings() method (or "SHOW CHARACTER SET" query) to ask the server for the encoding tables list
   3. You can read "error" property to get the text of the error after import. If import has been finished successfully, this property is empty.
@author Ed Parrish
Added LOCAL to LOAD DATA LOCAL INFILE to resolve --secure-file-priv option
@see: https://stackoverflow.com/questions/32737478/how-should-i-tackle-secure-file-priv-in-mysql
Converted to mysqli
*/


class Quick_CSV_import
{
  var $table_name; //where to import to
  var $file_name;  //where to import from
  var $link;  //database connection
  var $use_csv_header; //use first line of file OR generated columns names
  var $field_separate_char; //character to separate fields
  var $field_enclose_char; //character to enclose fields, which contain separator char into content
  var $field_escape_char;  //char to escape special symbols
  var $error; //error message
  var $arr_csv_columns; //array of columns
  var $table_exists; //flag: does table for import exist
  var $encoding; //encoding table, used to parse the incoming file. Added in 1.5 version

  function Quick_CSV_import($link, $file_name="")
  {
    $this->link = $link;
    $this->file_name = $file_name;
    $this->arr_csv_columns = array();
    $this->use_csv_header = true;
    $this->field_separate_char = ",";
    $this->field_enclose_char  = "\"";
    $this->field_escape_char   = "\\";
    $this->table_exists = false;
  }

  function import()
  {
    if($this->table_name=="")
      $this->table_name = "temp_".date("d_m_Y_H_i_s");

    $this->table_exists = false;
    $this->create_import_table();

    if(empty($this->arr_csv_columns))
      $this->get_csv_header_fields();

    /* change start. Added in 1.5 version */
    if("" != $this->encoding && "default" != $this->encoding)
      $this->set_encoding();
    /* change end */

    if($this->table_exists)
    {
      $sql = "LOAD DATA LOCAL INFILE '".
             @mysqli_real_escape_string($this->link, $this->file_name).
             "' INTO TABLE `".$this->table_name.
             "` FIELDS TERMINATED BY '".
             @mysqli_real_escape_string($this->link, $this->field_separate_char).
             "' OPTIONALLY ENCLOSED BY '".
             @mysqli_real_escape_string($this->link, $this->field_enclose_char).
             "' ESCAPED BY '".
             @mysqli_real_escape_string($this->link, $this->field_escape_char).
             "' ".
             ($this->use_csv_header ? " IGNORE 1 LINES " : "")
             ."(`".implode("`,`", $this->arr_csv_columns)."`)";
//var_dump($sql);
      $res = @mysqli_query($this->link, $sql);
      $this->error = mysqli_error($this->link);
    }
  }

  //returns array of CSV file columns
  function get_csv_header_fields()
  {
    $this->arr_csv_columns = array();
    $fpointer = fopen($this->file_name, "r");
    if ($fpointer)
    {
      $arr = fgetcsv($fpointer, 10*1024, $this->field_separate_char);
      if(is_array($arr) && !empty($arr))
      {
        if($this->use_csv_header)
        {
          foreach($arr as $val)
            if(trim($val)!="")
              $this->arr_csv_columns[] = $val;
        }
        else
        {
          $i = 1;
          foreach($arr as $val)
            if(trim($val)!="")
              $this->arr_csv_columns[] = "column".$i++;
        }
      }
      unset($arr);
      fclose($fpointer);
    }
    else
      $this->error = "file cannot be opened: ".(""==$this->file_name ? "[empty]" : @mysqli_real_escape_string($this->link, $this->file_name));
    return $this->arr_csv_columns;
  }

  function create_import_table()
  {
    $sql = "CREATE TABLE IF NOT EXISTS ".$this->table_name." (";

    if(empty($this->arr_csv_columns))
      $this->get_csv_header_fields();

    if(!empty($this->arr_csv_columns))
    {
      $arr = array();
      for($i=0; $i<sizeof($this->arr_csv_columns); $i++)
          $arr[] = "`".$this->arr_csv_columns[$i]."` TEXT";
      $sql .= implode(",", $arr);
      $sql .= ")";
      $res = @mysqli_query($this->link, $sql);
      $this->error = mysqli_error($this->link);
      $this->table_exists = ""==mysqli_error($this->link);
    }
  }

  /* change start. Added in 1.5 version */
  //returns recordset with all encoding tables names, supported by your database
  function get_encodings()
  {
    $rez = array();
    $sql = "SHOW CHARACTER SET";
    $res = @mysqli_query($this->link, $sql);
    if(mysqli_num_rows($res) > 0)
    {
      while ($row = mysqli_fetch_assoc ($res))
      {
        $rez[$row["Charset"]] = ("" != $row["Description"] ? $row["Description"] : $row["Charset"]); //some MySQL databases return empty Description field
      }
    }
    return $rez;
  }

  //defines the encoding of the server to parse to file
  function set_encoding($encoding="")
  {
    if("" == $encoding)
      $encoding = $this->encoding;
    $sql = "SET SESSION character_set_database = " . $encoding; //'character_set_database' MySQL server variable is [also] to parse file with rigth encoding
    $res = @mysqli_query($this->link, $sql);
    return mysqli_error($this->link);
  }
  /* change end */

}

?>
