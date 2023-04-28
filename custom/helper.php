<?php
function connection()
{
    $servername = "10.0.0.23";
    $username = "vtenext";
    $password = "Jbt$5qNbJXg";
    $database= "bixdata";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $database);
    return $conn;
}

function connection_jdoc()
{
    $servername = "10.0.0.23";
    $username = "vtenext";
    $password = "Jbt$5qNbJXg";
    $database= "jdoc";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $database);
    return $conn;
}

function select($conn,$sql) {
    $result = $conn->query($sql);
    $rows = array();
    while($row = mysqli_fetch_array($result))
    {
            $rows[] = $row;
    }
    return $rows;
}

function select_row($conn,$sql) {
    $result = $conn->query($sql);
    $rows = array();
    while($row = mysqli_fetch_array($result))
    {
            return $row;
    }
    return null;
}

function isEmpty($var)
{
    if(($var == null) || ($var == '') || ($var == 0))
    {
        return true;
    }
    else
    {
        return false;
    }
}

function jdoc_generate_recordid($conn,$table){
        $sql="SELECT recordid_ FROM $table WHERE recordid_ NOT LIKE '1%' ORDER BY recordid_ DESC LIMIT 1";
        $result=  select($conn,$sql);
        if(count($result)>0)
        {
        $recordid=$result[0]['recordid_'];
        $intrecordid=  intval($recordid);
        $new_intrecordid=$intrecordid+1;
        $new_recordid_short=  strval($new_intrecordid);
        }
        else
        {
            $new_recordid_short='1';
        }
        $new_recordid_short_lenght=  strlen($new_recordid_short);
        $new_recordid='';
        for($i=0;$i<(32-$new_recordid_short_lenght);$i++)
        {
            $new_recordid=$new_recordid.'0';
        }
        $new_recordid=$new_recordid.$new_recordid_short;;
        return $new_recordid;
    }
    
    function jdoc_generate_seriale($conn,$table,$fieldid){
        $sql="SELECT $fieldid FROM $table ORDER BY $fieldid DESC LIMIT 1";
        $result=  select($conn,$sql);
        if(count($result)>0)
        {
            $seriale=$result[0][$fieldid];
        }
        else
        {
            $seriale=0;
        }
        $new_seriale=$seriale+1;
        return $new_seriale;
    }
    
function jdoc_insert_record($conn,$table,$fields)
{
    $userid=1;
    $new_recordid = jdoc_generate_recordid($conn,$table);
    $now = date('Y-m-d H:i:s');
    $insert = "INSERT INTO $table (recordid_,creatorid_,creation_,lastupdaterid_,lastupdate_,totpages_,deleted_";
    $values = " VALUES ('$new_recordid',$userid,'$now',$userid,'$now',0,'N'";
    foreach ($fields as $field_key => $field_value) {
        $field_value=  str_replace("'", "''", $field_value);
        $insert=$insert.",$field_key";
        if(($field_value==null)||($field_value=='null'))
        {
            $values=$values.",null";
        }
        else
        {
            $values=$values.",'$field_value'";
        }
    }
    $insert=$insert.")";
    $values=$values.")";
    $sql=$insert." ".$values;
    echo $sql."<br/>";
    $result=$conn->query($sql);
    return $new_recordid;
}

function insert($conn,$table,$fields)
{
    $tableid=$table."id";
    $sql="select $tableid from $table order by $tableid desc limit 1";
    $result= select_row($conn, $sql);
    if($result==null)
    {
        $fields[$tableid]=1;
    }
    else
    {
        $fields[$tableid]=$result[$tableid]+1;
    }
    
    $insert = "INSERT INTO $table (";
    $values = " VALUES (";
    $counter=0;
    foreach ($fields as $field_key => $field_value) {
        if($counter>0)
        {
            $insert=$insert.",";
            $values=$values.",";
        }
        $field_value=  str_replace("'", "''", $field_value);
        $insert=$insert."$field_key";
        if(($field_value==null)||($field_value=='null'))
        {
            $values=$values."null";
        }
        else
        {
            $values=$values."'$field_value'";
        }
        $counter++;
    }
    $insert=$insert.")";
    $values=$values.")";
    $sql=$insert." ".$values;
    //$sql= utf8_encode($sql);
    echo $sql."<br/>";
    $result=$conn->query($sql);
    if($result)
    {
        return $fields[$tableid];
    }
    else
    {
        return false;
    }
}


    




function bixlog($conn,$subject,$text)
{
    $date=date('Y-m-d');
    $hours=date('H:m');
    $sql="
        INSERT INTO bix_log
        (date,hours,subject,text)
        VALUES
        ('$date','$hours','$subject','$text')
        ";
    $result = $conn->query($sql);
}


?>
