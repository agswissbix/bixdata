<?php

class Rest_controller extends CI_Controller {
    
    function __construct()
    {
        parent::__construct();
    }
    
    public function get_tables_menu()
    {
        $fissi=$this->Sys_model->get_archive_menu();
        echo json_encode($fissi);
    }
    
    public function get_records()
    {
        $post=$_POST;
        $table=$post['tableid'];
        $searchTerm='';
        $where='TRUE';
        
        $columns=  $this->Sys_model->get_results_columns($table, 1);
        $return['columns']= $columns; 
        $sql="";
        $summary=array();
        $sum_fields='';
        $sum_query='';
        foreach ($columns as $key => $column) {
            $column_id=$column['id'];
            
            if(($column_id!='recordid_')&&($column_id!='recordstatus_')&&($column_id!='recordcss_'))
            {
                if($sql=='')
                {
                    $sql="select risultati.* FROM (SELECT recordid_, recordstatus_,  '' as recordcss_,";
                }
                else
                {
                    $sql=$sql.",";
                }
                $sql=$sql.$column_id;
                if($where=='TRUE')
                {
                    $where=$where." AND ($column_id like '%$searchTerm%'";
                }
                else
                {
                    $where=$where." OR $column_id like '%$searchTerm%'";
                }
                
            }
            
            if($column['fieldtypeid']=='Numero')
            {
                if($sum_fields!='')
                {
                    $sum_fields=$sum_fields.",";
                }
                $sum_fields=$sum_fields." SUM($column_id)";
            }
        }
        if($sum_fields!='')
        {
            $sum_query="SELECT $sum_fields FROM user_$table";
        }
        $where=$where.")";
        $sql=$sql." FROM user_$table WHERE $where AND (recordstatus_ is null OR recordstatus_!='temp') ) AS risultati LEFT JOIN user_".$table."_owner ON risultati.recordid_=user_".$table."_owner.recordid_ where ownerid_ is null OR ownerid_=1 ";
        $return['records']=$this->Sys_model->get_records($table,$sql,'recordid_','desc');
        echo json_encode($return);
    }
    
    public function get_records_chart()
    {
        $post=$_POST;
        $table=$post['table'];
        $searchTerm=$post['searchTerm'];
        $where='TRUE';
        $sql="";
        $sql="select risultati.recordid_,risultati.recordid_ as id, risultati.description as name, risultati.startdate as start, risultati.duedate as end FROM (SELECT *";
        $sql=$sql." FROM user_$table WHERE $where AND (recordstatus_ is null OR recordstatus_!='temp') ) AS risultati LEFT JOIN user_".$table."_owner ON risultati.recordid_=user_".$table."_owner.recordid_ where ownerid_ is null OR ownerid_=1 ";
        $return['records']=$this->Sys_model->get_records($table,$sql,'start','asc');
        echo json_encode($return);
    }
    
    public function get_records_gantt()
    {
        $post=$_POST;
        $table=$post['table'];
        $searchTerm=$post['searchTerm'];
        $where='TRUE';
        $sql="";
        $sql="select risultati.recordid_,risultati.recordid_ as id, risultati.description as name, risultati.startdate as start, risultati.duedate as end FROM (SELECT *";
        $sql=$sql." FROM user_$table WHERE $where AND (recordstatus_ is null OR recordstatus_!='temp') ) AS risultati LEFT JOIN user_".$table."_owner ON risultati.recordid_=user_".$table."_owner.recordid_ where ownerid_ is null OR ownerid_=1 ";
        $return['records']=$this->Sys_model->get_records($table,$sql,'start','asc');
        echo json_encode($return);
    }
    
    public function get_records_kanban()
    {
        $post=$_POST;
        $table=$post['table'];
        $searchTerm=$post['searchTerm'];
        $where='TRUE';
        $sql="";
        $sql="select risultati.recordid_,risultati.recordid_ as id, risultati.description as name, risultati.startdate as start, risultati.duedate as end FROM (SELECT *";
        $sql=$sql." FROM user_$table WHERE $where AND (recordstatus_ is null OR recordstatus_!='temp') ) AS risultati LEFT JOIN user_".$table."_owner ON risultati.recordid_=user_".$table."_owner.recordid_ where ownerid_ is null OR ownerid_=1 ";
        $return['records']=$this->Sys_model->get_records($table,$sql,'start','asc');
        foreach ($return as $key => $value) {
            
        }
        echo json_encode($return);
    }
    
    public function get_fissi()
    {
        $post=$_POST;
        $tableid=$post['tableid'];
        $recordid=$post['recordid'];
        $fissi=$this->Sys_model->get_fissi($tableid, $recordid);
        echo json_encode($fissi);
    }
    
    public function get_record_labels()
    {
        $post=$_POST;
        $tableid=$post['tableid'];
        $recordid=$post['recordid'];
        $labels=$this->Sys_model->get_labels_table($tableid, 'scheda', $recordid, 1);
        echo json_encode($labels);
    }
    
    public function get_record_fields()
    {
        $post=$_POST;
        if(array_key_exists('recordid', $post))
        {
            $recordid=$post['recordid'];
        }
        else
        {
            $recordid='null';
        }
        
        $tableid=$post['tableid'];
        $fields=$this->Sys_model->get_fields_table($tableid,'null',$recordid,'visualizzazione');
        echo json_encode($fields);
    }
    
    public function get_views()
    {
        $post=$_POST;
        $tableid=$post['tableid'];
        $userid=$post['userid'];
        $views=$this->Sys_model->get_saved_views($tableid,$userid);
        echo json_encode($views);
    }
    
    public function set_record()
    {
        $post=$_POST;
        $tableid=$post['tableid'];
        $recordid=$post['recordid'];
        $fields_jsonstring=$post['fields'];
        $fields=json_decode($fields_jsonstring, true);
        if($recordid=='None')
        {
            $fields['id']=$this->Sys_model->generate_seriale($tableid, 'id');
            $this->Sys_model->insert_record($tableid,1,$fields);
        }
        else
        {
            $this->Sys_model->update_record($tableid,1,$fields,"recordid_='$recordid'");
        }
        
    }
}
?>