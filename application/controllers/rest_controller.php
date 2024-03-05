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
        $searchTerm=$post['searchTerm'];
        $filters=array();
        if(array_key_exists('filters', $post))
        {
            if(isnotempty($filters))
            {
               $filters=json_decode($filters, true); 
            }
        }
        
        
        $viewid=$post['viewid'];
        $currentpage=$post['currentpage'];
        $userid=$post['userid'];
        $from="FROM user_$table";
        $where='TRUE';
        
        $master_tableid='';
        if(array_key_exists("master_tableid", $post))
        {
            $master_tableid=$post['master_tableid'];
        }
        
        $columns=  $this->Sys_model->get_results_columns($table, 1, $master_tableid);
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
                    $sql="select risultati.* FROM (SELECT user_$table.recordid_, user_$table.recordstatus_,  '' as recordcss_,";
                }
                else
                {
                    $sql=$sql.",";
                }
                $sql=$sql."user_$table.$column_id";
                if($searchTerm!='')
                {
                    if($column['fieldtypeid']=='linked')
                    {
                        $linkedtableid=$column['linkedtableid'];
                        $keyfieldlink=$column['keyfieldlink'];
                        $column_id="user_$linkedtableid".".$keyfieldlink";
                        $from=$from." LEFT JOIN user_$linkedtableid ON user_$table.recordid$linkedtableid"."_=user_$linkedtableid.recordid_ ";
                    }
                    elseif($column['fieldtypeid']=='Utente')
                    {
                        $from=$from." LEFT JOIN sys_user as sys_user_$column_id ON sys_user_$column_id.id=user_$table.$column_id ";
                    }
                    else
                    {
                        $column_id="user_$table.$column_id";
                    }
                    if($where=='TRUE')
                    {
                        $where=$where." AND (";
                    }
                    else
                    {
                        $where=$where." OR ";
                    } 
                    if($column['fieldtypeid']=='Utente')
                    {
                        $where=$where." sys_user_$column_id.firstname like '%$searchTerm%' OR sys_user_$column_id.lastname like '%$searchTerm%' ";
                    }
                    else
                    {
                        $where=$where."$column_id like '%$searchTerm%'";
                    }
                    
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
        if($searchTerm!='')
        {
            $where=$where.")";
        }
        
        if(array_key_exists("master_tableid", $post))
        {
            $master_tableid=$post['master_tableid'];
            $master_recordid=$post['master_recordid'];
            $where=$where." AND (recordid".$master_tableid."_='$master_recordid') ";
        }
        
        //view
        $view= $this->Sys_model->db_get_row('sys_view',"*","id='$viewid'");
        
        
        $order_field=$columns[3]['id'];
        $order_ascdesc='desc';
        
        if($table=='deal')
        {
            $order_field='opendate';
            $order_ascdesc='desc';
        }
        if($table=='projectmilestone')
        {
            $order_field='expecteddate';
            $order_ascdesc='asc';
        }
        
        if($view!=null)
        {
            $view_condition=$view['query_conditions'];
            $view_condition=str_replace('$userid$', $userid, $view_condition);
            $where=$where." AND ".$view_condition;
            
            if(isnotempty($view['order_field']))
            {
                $order_field=$view['order_field'];
            }
            if(isnotempty($view['order_ascdesc']))
            {
                $order_ascdesc=$view['order_ascdesc'];
            }
        }
        
        
    
        
        $sql=$sql." $from WHERE $where  AND user_$table.deleted_<>'Y' ) AS risultati  ";
        $limit=50;
        $offset=$currentpage*$limit-$limit;
        if($currentpage==0)
        {
            $offset=0;
            $limit=1000000000000000;
        }
        $return['records']=$this->Sys_model->get_records($table,$sql,$order_field,$order_ascdesc,$offset,$limit);
        
        $reports_return=array();
        /*
        $reports= $this->Sys_model->db_get('sys_report',"*","tableid='$table' and layout='table'");
        foreach ($reports as $key => $report) {
            $fieldid=$report['fieldid'];
            if($report['operation']=='somma')
            {
                $sql="SELECT SUM($fieldid) as $fieldid  FROM user_$table WHERE $where  AND user_$table.deleted_<>'Y' ";
            }
            $result=$this->Sys_model->select($sql);
            if($result!=null)
            {
                $reports_return[$key]['description']=$report['name'];
                $reports_return[$key]['value']=$result[0][$fieldid];
            }
            
        }
         * */
         
        $return['reports']=$reports_return;
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
        $groupby_field='dealstage';
        $searchTerm=$post['searchTerm'];
        $where='TRUE';
        $sql="";
        $sql="select risultati.recordid_ as recordid,dealname as title,closedate as date, dealuser1 as user, amount as field1, amount as field2, amount as field3, amount as field4,'tag' as tag, dealstage FROM (SELECT *";
        $sql=$sql." FROM user_$table WHERE $where AND deleted_='N' AND (recordstatus_ is null OR recordstatus_!='temp') ) AS risultati LEFT JOIN user_".$table."_owner ON risultati.recordid_=user_".$table."_owner.recordid_ where ownerid_ is null OR ownerid_=1 order by recordid desc LIMIT 100 ";
        //$records=$this->Sys_model->get_records($table,$sql,'recordid_','desc',0,10);
        

        $records= $this->Sys_model->select($sql);
        $return['groups']=array();
        $groups= $this->Sys_model->select("SELECT * FROM sys_lookup_table_item WHERE lookuptableid='dealstage_deal' order by itemorder asc");
        foreach ($groups as $key => $group) {
            $return['groups'][$group['itemcode']]['description']=$group['itemdesc'];
            $return['groups'][$group['itemcode']]['records']=array();
        }
        foreach ($records as $key => $record) {
            $groupby_field_value=$record[$groupby_field];
            if(array_key_exists($groupby_field_value, $return['groups']))
            {
                $return['groups'][$groupby_field_value]['description']=$groupby_field_value;
                $return['groups'][$groupby_field_value]['records'][]=$record;
            }
            
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
        $userid=$post['userid'];
        $context=$post['context'];
        $fields=$this->Sys_model->get_fields_table($tableid,'null',$recordid,$context,'null',array(),'',$userid);
        $return_fields=array();
        $labels=$this->Sys_model->db_get("sys_table_label","*","tableid='$tableid'","ORDER BY labelorder asc");
        foreach ($labels as $key => $label) {
            $return_fields[$label['labelname']]=array();
        }
        foreach ($fields as $key => $field) {
            $label=$field['label'];
            $substring=substr($key, -1);
            if($substring=='_')
            {
                unset($fields[$key]);
            }
            if(substr($key, 0, 1)=='_')
            {
                $fieldid=$field['fieldid'];
                $fieldid=substr($fieldid, 1)."_";
                $field['fieldid']=$fieldid;
                $field['fieldtypeid']='linkedmaster';
                $master_tableid=$field['tablelink'];
                $master_recordid=$this->Sys_model->db_get_value("user_$tableid","recordid$master_tableid"."_","recordid_='$recordid'");
                $sql="SELECT keyfieldlink FROM sys_field WHERE tableid='$tableid' AND tablelink='$master_tableid'" ;
                $result=  $this->Sys_model->select($sql);
                if(count($result)>0)
                {
                    $keyfieldlink=$result[0]['keyfieldlink'];
                    $keyfieldlink= strtolower($keyfieldlink);
                    $value=$this->Sys_model->db_get_value("user_$master_tableid",$keyfieldlink,"recordid_='$master_recordid'");
                    $field['value']=$value;
                    $field['recordid']=$master_recordid;
                    $field['valuecode'][0]['value']=$value;
                    $field['valuecode'][0]['code']=$master_recordid;
                }
                
                

                $return_fields[$label][$key]=$field;
            }
            else
            {
                $return_fields[$label][$key]=$field;
            }
            
            
            if($field['lookuptableid']!='')
            {
                
                $field['lookupitems']=$this->Sys_model->get_lookuptable($field['lookuptableid'],$field['fieldid']);
                $return_fields[$label][$key]=$field;
            }
            
            if($field['fieldtypeid']=='Utente')
            {
                $field['lookupitems']=$this->Sys_model->get_users();
                $return_fields[$label][$key]=$field;
                $default=$userid;
            }
            
            $value=$field['valuecode'][0]['value'];
            $code=$field['valuecode'][0]['code'];
            $default=$field['settings']['default'];
            if($field['fieldtypeid']=='Utente')
            {
                $default=$userid;
            }
            if((isempty($value))&&(isnotempty($default)))
            {
               $return_fields[$label][$key]['value']=$default;
               $return_fields[$label][$key]['valuecode'][0]['value']=$default;
               $return_fields[$label][$key]['valuecode'][0]['code']=$default;
            }
        
            if(($field['fieldid']=='date')&&(isempty($field['valuecode'][0]['value'])))
            {
                $date=date("Y-m-d");
                $return_fields[$label][$key]['value']= $date;
                $return_fields[$label][$key]['valuecode'][0]['value']=$date;
                $return_fields[$label][$key]['valuecode'][0]['code']=$date;
            }
        }
        
        if((array_key_exists('master_tableid', $post))&&(array_key_exists('master_recordid', $post)))
        {
            
            $master_tableid=$post['master_tableid'];
            $master_recordid=$post['master_recordid'];
            $master_fieldid="_recordid".$master_tableid;
            if(array_key_exists($master_fieldid, $fields))
            {
                $sql="SELECT keyfieldlink,label FROM sys_field WHERE fieldid='_recordid$master_tableid' AND tableid='$tableid' " ;
                $result=  $this->Sys_model->select($sql);
                if(count($result)>0)
                {
                    $keyfieldlink=$result[0]['keyfieldlink'];
                    $label=$result[0]['label'];
                }
                $keyfieldlink= strtolower($keyfieldlink);
                $value=$this->Sys_model->db_get_value("user_$master_tableid",$keyfieldlink,"recordid_='$master_recordid'");
                $return_fields[$label][$master_fieldid]['value']=$value;
                $return_fields[$label][$master_fieldid]['recordid']=$master_recordid;
                $return_fields[$label][$master_fieldid]['valuecode'][0]['value']=$value;
                $return_fields[$label][$master_fieldid]['valuecode'][0]['code']=$master_recordid;
            }
            
            
            
            $tableid_linked_tables=$this->Sys_model->db_get("sys_table_link","*","tablelinkid='$tableid'");
            foreach ($tableid_linked_tables as $key => $tableid_linked_table) {
                $tableid_linked_tableid=$tableid_linked_table['tableid'];
                $rows=$this->Sys_model->db_get("sys_table_link","*","tablelinkid='$master_tableid' AND tableid='$tableid_linked_tableid'");
                if(count($rows)>0)
                {
                    $tableid_linked_tableid_recordid=$this->Sys_model->db_get_value("user_$master_tableid","recordid$tableid_linked_tableid"."_","recordid_='$master_recordid'");
                    $master_fieldid="_recordid".$tableid_linked_tableid;
                    if(array_key_exists($master_fieldid, $fields))
                    {
                        $sql="SELECT keyfieldlink,label FROM sys_field WHERE fieldid='_recordid$tableid_linked_tableid' AND tableid='$tableid' " ;
                        $result=  $this->Sys_model->select($sql);
                        if(count($result)>0)
                        {
                            $keyfieldlink=$result[0]['keyfieldlink'];
                            $label=$result[0]['label'];
                        }
                        $keyfieldlink= strtolower($keyfieldlink);
                        $value=$this->Sys_model->db_get_value("user_$tableid_linked_tableid",$keyfieldlink,"recordid_='$tableid_linked_tableid_recordid'");
                        $return_fields[$label][$master_fieldid]['value']=$value;
                        $return_fields[$label][$master_fieldid]['recordid']=$tableid_linked_tableid_recordid;
                        $return_fields[$label][$master_fieldid]['valuecode'][0]['value']=$value;
                        $return_fields[$label][$master_fieldid]['valuecode'][0]['code']=$tableid_linked_tableid_recordid;
                    }
                }
            }
             
             
            
            
        }
        
        
        echo json_encode($return_fields);
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
        foreach ($fields as $fields_key => $field) {
            if(is_array($field))
            {
                $field_value_new='';
                foreach ($field as $field_key => $field_value) {
                    if($field_value_new!='')
                    {
                        $field_value_new=$field_value_new."|##|";
                    }
                    $field_value_new=$field_value_new.$field_value;
                    
                }
                $fields[$fields_key]=$field_value_new;
            }
        }
        if($recordid=='None')
        {
            $fields['id']=$this->Sys_model->generate_seriale($tableid, 'id');
            $recordid=$this->Sys_model->insert_record($tableid,1,$fields);
        }
        else
        {
            $this->Sys_model->update_record($tableid,1,$fields,"recordid_='$recordid'");
        }
        
        $this->custom_update($tableid, $recordid);
        $this->custom_update($tableid, $recordid);
        $return['recordid']=$recordid;
        echo json_encode($return);
    }
    
    public function custom_update($tableid,$recordid)
    {
        $row= $this->Sys_model->db_get_row("user_$tableid","*","recordid_='$recordid'");
        $fields=array();
        
//------TIMESHEET---------------------------------------------------------------------------------------
        if($tableid=='timesheet')
        {
            $date=$row['date'];
            $print_date=date("d.m.Y",strtotime($date));
            $first_name="test";//$row['first_name'];
            $last_name="test";//$row['last_name'];
            $print_tech=substr($first_name, 0, 1).".".$last_name;
            $invoiceoption=$row['invoiceoption'];
            $timesheetdate=$row['date'];

            $service=$row['service'];

            $recordid_company=$row['recordidcompany_'];
            $company=$this->Sys_model->db_get_row("user_company","*","recordid_='$recordid_company'");


            $recordid_servicecontract=$row['recordidservicecontract_'];


            $recordid_project=$row['recordidproject_'];
            $project=$this->Sys_model->db_get_row("user_project","*","recordid_='$recordid_project'");


            $recordid_ticket=$row['recordidticket_'];
            $ticket=$this->Sys_model->db_get_row("user_ticket","*","recordid_='$recordid_ticket'");

            $date=$row['date'];
            $timesheetdescription=$row['description'];
    
    
            $worktime=$row['worktime'];
            $worktime_array=explode(":",$worktime);
            $hours_decimal=(string)((int)($worktime_array[0]));
            $minutes=$worktime_array[1];
            $minutes_decimal='00';
            if($minutes=='00')
                    $minutes_decimal='00';
            if($minutes=='15')
                    $minutes_decimal='25';
            if($minutes=='30')
                    $minutes_decimal='50';
            if($minutes=='45')
                    $minutes_decimal='75';
            $worktimedecimal=$hours_decimal.".".$minutes_decimal;
            $fields['worktime_decimal']=$worktimedecimal;
            
            $traveltime=$row['traveltime'];
            $traveltimedecimal=null;
            $totaltimedecimal=null;
            if(isnotempty($traveltime))
            {
                $traveltime_array=explode(":",$traveltime);
                $hours_decimal=(string)((int)($traveltime_array[0]));
                $minutes=$traveltime_array[1];
                $minutes_decimal='00';
                if($minutes=='00')
                        $minutes_decimal='00';
                if($minutes=='15')
                        $minutes_decimal='25';
                if($minutes=='30')
                        $minutes_decimal='50';
                if($minutes=='45')
                        $minutes_decimal='75';
                $traveltimedecimal=$hours_decimal.".".$minutes_decimal;
                $fields['traveltime_decimal']=$traveltimedecimal;

                
            }
            $totaltimedecimal=$worktimedecimal+$traveltimedecimal;
            $fields['totaltime_decimal']=$totaltimedecimal;
            
            
            $invoicestatus=$row['invoicestatus'];
            if($invoicestatus!='Invoiced')
            {
                $invoicestatus='To Process';
            }
            $fields['recordidservicecontract_']='';
                
            
            
            $workprice=0;
            $travelprice=0;
            $totalprice=0;
            $fixedprice=$project['fixedprice'];
            $unit_price=140;//$service['unit_price'];
            $itpbx_price=$company['ictpbx_price'];
            $sw_price=$company['sw_price'];
            $account_travelprice=$company['travel_price'];


            
            if($invoicestatus=='To Process')
            {
                if($invoiceoption=='To check')
                {
                    $invoicestatus='To check';
                }

                if($invoiceoption=='Swisscom incident')
                {
                    $invoicestatus='Swisscom incident';
                    $workprice=0;
                    $travelprice=0;
                }

                if($invoiceoption=='Mauro incident')
                {
                    $invoicestatus='Mauro incident';
                }

                if($invoiceoption=='Under Warranty')
                {
                    $invoicestatus='Under warranty';
                }

                if($invoiceoption=='Commercial support')
                {
                    $invoicestatus='Commercial support';
                }

                if($invoiceoption=='Swisscom ServiceNow')
                {
                    $invoicestatus='Swisscom ServiceNow';

                }

                if($invoiceoption=='Out of contract')
                {
                    $invoicestatus='Out of contract';
                }



                if($service=='Amministrazione')
                {
                    $invoicestatus='Amministrazione';
                }

                if($service=='Commerciale')
                {
                    $invoicestatus='Commerciale';
                }

                if($service=='Riunione')
                {
                    $invoicestatus='Riunione';
                }

                if($service=='Interno')
                {
                    $invoicestatus='Interno';
                }

                if($service=='Formazione e Test')
                {
                    $invoicestatus='Formazione e Test';
                }

                if($service=='Formazione Apprendista')
                {
                    $invoicestatus='Formazione Apprendista';
                }
            }

            
                
                
            

            if($invoicestatus=='To Process')
            {
                if($project != null)
                {
                    if($fixedprice=='Si') 
                    {
                       $invoicestatus='Fixed price Project'; 
                    }
                }
            }

            if(($invoicestatus=='To Process'))
            {
                $servicecontract=null;
                
                    // cerca service contract
                    if($service=='Assistenza IT')
                    {
                        if(isempty($traveltimedecimal)) 
                        {
                            // Cerca contratto be all all-inclusive
                            $condition="recordidcompany_='$recordid_company' and  (type='BeAll (All-inclusive)') ";
                            $servicecontract=$this->Sys_model->db_get_row("user_servicecontract","*","$condition and status='In Progress'  AND deleted_='N'");
                        }
         
                    }

                    if($service=='Assistenza PBX')
                    {
                        if(isempty($traveltimedecimal)&&($totaltimedecimal==0.25))
                        {
                            //cerca contratto pbx
                            $condition="recordidcompany_='$recordid_company' and (type='Manutenzione PBX') ";
                            $servicecontract=$this->Sys_model->db_get_row("user_servicecontract","*","$condition and status='In Progress' AND deleted_='N'");
                        }
 

                    }

                    if($service=='Assistenza SW')
                    {
                        $condition="recordidcompany_='$recordid_company' and (service='Assistenza SW' OR services like '%Software%' )";
                        $servicecontract=$this->Sys_model->db_get_row("user_servicecontract","*","$condition and status='In Progress' AND deleted_='N'");
                    }

                    //Hosting
                    if($service=='Assistenza Web Hosting')
                    {
                        $condition="recordidcompany_='$recordid_company' and (service='Assistenza Web Hosting' OR services like '%Hosting%' )";
                        $servicecontract=$this->Sys_model->db_get_row("user_servicecontract","*","$condition and status='In Progress' AND deleted_='N'");
                    }

                    //Printing
                    if($service=='Printing')
                    {
                        $condition="recordidcompany_='$recordid_company' and (service='Printing' OR services like '%Printing%' )";
                        $servicecontract=$this->Sys_model->db_get_row("user_servicecontract","*","$condition and status='In Progress' AND deleted_='N'");
                    }
                    
                    
                    
                    if($servicecontract==null)
                    {
                        // Cerca contratto monte ore
                        if($service=='Assistenza IT')
                        {
                            $condition="recordidcompany_='$recordid_company' and (service='Assistenza IT' OR services like '%ICT%' )  ";
                        }
                        if($service=='Assistenza PBX')
                        {
                            $condition="recordidcompany_='$recordid_company' and (service='Assistenza PBX' OR services like '%PBX%' )  ";
                        }
                        
                        $servicecontract=$this->Sys_model->db_get_row("user_servicecontract","*","$condition and (type='Monte Ore') and status='In Progress' AND deleted_='N'");
                    }
                    
                    if($servicecontract != null)
                    {
                        $invoicestatus='Service Contract';
                        $recordid_servicecontract=$servicecontract['recordid_'];

                        $fields['recordidservicecontract_']=$recordid_servicecontract;
                        $contracttype=$servicecontract['type'];
                        if(($contracttype=='PBX')||($contracttype=='BeAll'))
                        {
                            $invoicestatus='Flat Service Contract';
                        }
                        
                        $this->custom_update('servicecontract', $recordid_servicecontract);

                    }

                
               

                
            }

            if(($invoicestatus=='To Process')||($invoicestatus=='Out of contract'))
            {

                if(($service != null)&&($company != null))
                {

                    $invoicestatus='To Invoice';
                    $fields['recordidservicecontract_']='';

                    if(($service=='Assistenza SW' )&&(($sw_price != null)&& ($sw_price != '') &&($sw_price != 0)))
                    {
                       $unit_price=$sw_price;
                    }

                    if(($service=='Assistenza PBX' || $service=='Assistenza IT' )&&(($itpbx_price != null)&& ($itpbx_price != '') &&($itpbx_price != 0)))
                    { 
                       $unit_price=$itpbx_price;
                    }

                    if(!isEmpty($traveltimedecimal))
                    {

                        if(isEmpty($account_travelprice))
                        {
                            $travelprice=$traveltimedecimal*$unit_price;
                        }
                        else
                        {
                            $travelprice=$account_travelprice;
                        }
                    }

                    $workprice=$unit_price*$worktimedecimal;
                    $totalprice=$workprice+$travelprice;

                    if(!isEmpty($ticket))
                    {
                        $ticket_status=$ticket['vtestatus'];
                        if($ticket_status!='Closed')
                        {
                            $invoicestatus='To Invoice when Ticket Closed';
                        }
                    }

                    if(!isEmpty($project))
                    {
                        $completed=$project['completed'];
                        if($completed!='Si')
                        {
                            $invoicestatus='To Invoice when Project completed';
                        }
                    }
                }
                else
                {
                    $invoicestatus='To Check';
                }
            }

            $fields['invoicestatus']=$invoicestatus;
            $fields['worktime_decimal']=$worktimedecimal;
            $fields['workprice']=$workprice;
            $fields['traveltime_decimal']=$traveltimedecimal;
            $fields['travelprice']=$travelprice;
            $fields['totaltime_decimal']=$totaltimedecimal;
            $fields['totalprice']=$totalprice;
            
            if(($service=='Assistenza IT')||($service=='Assistenza PBX')||($service=='Assistenza SW')||($service=='Assistenza Web Hosting')||($service=='Printing'))
            {
                if($row['validated']!='Si')
                {
                    $fields['validated']='No';
                }
                
            }
            else
            {
                $fields['validated']='';
            }
            
            if(isnotempty($recordid_project))
            {
                $this->custom_update('project', $recordid_project);
            }
            
            
        }
        
        
//------PROJECT---------------------------------------------------------------------------------------------
        if($tableid=='project')
        {
            $completed=$row['completed'];
            $recordid_deal=$row['recordiddeal_'];
            $fieldsupdate_deal=array();
            $usedhours=0;
            
            // aggiornamento ore totali in base ai timesheet
            $timesheets= $this->Sys_model->db_get("user_timesheet","*","recordidproject_='$recordid'");
            foreach ($timesheets as $key => $timesheet) {
                $usedhours=$usedhours+$timesheet['totaltime_decimal'];
            }
            $fields['usedhours']=$usedhours;
            $fieldsupdate_deal['usedhours']=$usedhours;

            // aggiornamento del relativo deal con lo stato completed
            $fieldsupdate_deal['projectcompleted']=$completed;
            $this->Sys_model->update_record("deal",1,$fieldsupdate_deal,"recordid_='$recordid_deal'");
            $this->custom_update('deal', $recordid_deal);
        }
        
        
//------SERVICECONTRACT--------------------------------------------------------------------------------------
        if($tableid=='servicecontract')
        {
            $contracthours=$row['contracthours'];
            $previousresidual=$row['previousresidual'];
            $excludetravel=$row['excludetravel'];
            $usedhours=0;
            $residualhours=$contracthours;
            $progress=0;
            $fields['usedhours']=$usedhours;
            
            $timesheets= $this->Sys_model->db_get("user_timesheet","*","recordidservicecontract_='$recordid' AND deleted_='N'"); 
            foreach ($timesheets as $key => $timesheet) {
                $usedhours=$usedhours+$timesheet['worktime_decimal'];
                if(($excludetravel!='1')&&($excludetravel!='Si'))
                {
                    $usedhours=$usedhours+$timesheet['traveltime_decimal'];
                }
            }
            $fields['usedhours']=$usedhours;
            $fields['residualhours']=$contracthours+$previousresidual-$usedhours;
            if($contracthours+$previousresidual!=0)
            {
                $fields['progress']=($fields['usedhours']/($contracthours+$previousresidual))*100;
            }
            
            if(isEmpty($row['type']))
            {
                $fields['type']='Monte Ore';
            }
            if(isEmpty($row['status']))
            {
                $fields['status']='In Progress';
            }
        }
        
        //------TASK--------------------------------------------------------------------------------------
        if($tableid=='task')
        {
           if($row['completed']=='Si')
           {
              $fields['status']='Chiuso'; 
              $today=  date('Y-m-d');
              $fields['closedate']=$today;
           }
           else
           {
                $fields['status']='Aperto';
                if(isnotempty($row['planneddate']))
                {
                    $fields['status']='Pianificato';
                }
               
                $today = new DateTime(); 
                $today->setTime(0, 0);
                $duedate = new DateTime($row['duedate']); 
 
                  
                if ($duedate >= $today) {
                    $diff = $duedate->diff($today); // Calculate the difference between the two dates
                    if ($diff->days < 2) {
                        $fields['status']='In Scadenza';
                    }
                } else {
                    $fields['status']='Scaduto';
                }
           }
           
        }
        
//------DEALLINE---------------------------------------------------------------------------------------------
        if($tableid=='dealline')
        {
            $this->custom_update('deal', $row['recordiddeal_']);
        }
        
//------DEAL---------------------------------------------------------------------------------------------
        if($tableid=='deal')
        {
            //data creazione trattativa
            $creationdate=$this->Sys_model->db_get_value("user_deal","creation_","recordid_='$recordid'");
            $fields['opendate']=date("Y-m-d", strtotime($creationdate));
            
            //venditore trattativa con aggiornamento del venditore per adiuto
            $dealuser1=$row['dealuser1'];
            $bixdata_dealuser= $this->Sys_model->db_get_row("sys_user","*","id='$dealuser1'"); 
            if($bixdata_dealuser!=null)
            {
                $fields['adiuto_dealuser']=$bixdata_dealuser['adiutoid'];
            }
            
            //eventuale progetto da associare alle righe di dettaglio
            $recordid_project=$this->Sys_model->db_get_value("user_project","recordid_","recordiddeal_='$recordid'");
            if(isempty($recordid_project))
            {
                $recordid_project='';
            }
            
            //importo che potrebbe essere impostato anche senza righe di dettaglio
            $deal_price=$row['amount'];
            $deal_price_sum=0;
            $deal_expectedcost=$row['expectedcost'];
            $deal_expectedcost_sum=0;
            $deal_actualcost=0;
            $deal_expectedhours=0;
            $deal_usedhours=$row['usedhours'];
            $deal_expectedmargin=0;
            $deal_actualmargin=0;
            $deal_annualprice=0;
            $deal_annualcost=0;
            $deal_annualmargin=0;
            
            $fields['fixedprice']='No';
            // aggiornamento prezzo costo e margine totale
            $deallines= $this->Sys_model->db_get("user_dealline","*","recordiddeal_='$recordid' AND deleted_='N'");
            foreach ($deallines as $key => $dealline) {
                $recordid_dealline=$dealline['recordid_'];
                $recordid_product=$dealline['recordidproduct_'];
                $dealline_quantity=$dealline['quantity'];
                $dealline_price=$dealline['price'];
                $dealline_expectedcost=$dealline['expectedcost'];
                $dealline_expectedmargin=$dealline['expectedmargin'];
                $dealline_unitactualcost=$dealline['uniteffectivecost'];
                $dealline_frequency=$dealline['frequency'];
                $multiplier=1;
                if($dealline_frequency=='Annuale')
                    $multiplier=1;
                if($dealline_frequency=='Semestrale')
                    $multiplier=2;
                if($dealline_frequency=='Trimestrale')
                    $multiplier=3;
                if($dealline_frequency=='Bimestrale')
                    $multiplier=6;
                if($dealline_frequency=='Mensile')
                    $multiplier=12;
                        
                $deal_price_sum=$deal_price_sum+$dealline_price;
                $deal_expectedcost_sum=$deal_expectedcost_sum+$dealline_expectedcost;
                
                
                $fieldsupdate_dealline=array();
                $fieldsupdate_dealline['recordidproject_']=$recordid_project;
              
                
                $dealline_actualcost=$dealline_unitactualcost*$dealline_quantity;
                
                $product_fixedprice=$this->Sys_model->db_get_value("user_product","fixedprice","recordid_='$recordid_product' AND deleted_='N'");
                $deal_expectedhours=$deal_expectedhours+$dealline['expectedhours'];
                if($product_fixedprice=='Si')
                {
                    $fields['fixedprice']='Si';
                    
                    if(isempty($dealline['expectedhours']))
                    {
                        $fieldsupdate_dealline['expectedhours']=$dealline_price/140;
                    }
                    
                    if($deal_usedhours!=0)
                    {
                        $fieldsupdate_dealline['usedhours']=$deal_usedhours;
                        $dealline_actualcost=$deal_usedhours*60;
                        //lo azzero perchè le ore usate sul progetto vengano messe solo su una riga che prevede ore lavoro
                        $deal_usedhours=0;
                       
                    }

                }
                
                if($dealline_actualcost!=0)
                {
                    $dealline_actualmargin=$dealline_price-$dealline_actualcost;
                }
                else
                {
                    $dealline_actualmargin=$dealline_expectedmargin;
                }
                
                $fieldsupdate_dealline['effectivecost']=$dealline_actualcost;
                $fieldsupdate_dealline['margin_actual']=$dealline_actualmargin;
                
                if(isnotempty($dealline_frequency))
                {
                    $fieldsupdate_dealline['annualprice']=$dealline_price*$multiplier;
                    if($dealline_actualcost!=0)
                    {
                        $fieldsupdate_dealline['annualcost']=$dealline_actualcost*$multiplier;
                    }
                    else
                    {
                        $fieldsupdate_dealline['annualcost']=$dealline_expectedcost*$multiplier;
                    }
                    
                    $fieldsupdate_dealline['annualmargin']=$fieldsupdate_dealline['annualprice']-$fieldsupdate_dealline['annualcost'];
                    
                    $deal_annualprice=$deal_annualprice+$fieldsupdate_dealline['annualprice'];
                    $deal_annualcost=$deal_annualcost+$fieldsupdate_dealline['annualcost'];
                    $deal_annualmargin=$deal_annualmargin+$fieldsupdate_dealline['annualmargin'];
                }
                
                
                $this->Sys_model->update_record("dealline",1,$fieldsupdate_dealline,"recordid_='$recordid_dealline'");
                
                
                $deal_actualcost=$deal_actualcost+$dealline_actualcost;
                $deal_actualmargin=$deal_actualmargin+$dealline_actualmargin;

                
                
            }   
            
            // fine aggiornamento righe dettaglio
            
            if($deal_price_sum!=0)
            {
               $deal_price=$deal_price_sum;
            }
            if($deal_expectedcost_sum!=0)
            {
               $deal_expectedcost=$deal_expectedcost_sum;
            }
            $deal_expectedmargin=$deal_price-$deal_expectedcost;
            if($deal_actualcost==0)
            {
               $deal_actualmargin= $deal_expectedmargin;
            }
            $fields['amount']=sprintf("%.2f", $deal_price);
            $fields['expectedcost']=sprintf("%.2f", $deal_expectedcost);
            $fields['expectedmargin']= sprintf("%.2f", $deal_expectedmargin);
            $fields['expectedhours']=$deal_expectedhours;
            $fields['actualcost']=$deal_actualcost;
            $fields['effectivemargin']=$deal_actualmargin;
            $fields['margindifference']=$deal_actualmargin-$deal_expectedmargin;
            $fields['annualprice']=$deal_annualprice;
            $fields['annualcost']=$deal_annualcost;
            $fields['annualmargin']=$deal_annualmargin;
        }
        
        
        
        
        $this->Sys_model->update_record($tableid,1,$fields,"recordid_='$recordid'");
        
    }
    
    public function get_autocomplete_data()
    {
        $post=$_POST;
        $term=$post['term'];
        $tableid=$post['tableid'];
        $mastertableid=$post['mastertableid'];
        
        $return=array();
        $records_linkedmaster=$this->Sys_model->get_records_linkedmaster2($tableid, $mastertableid,$term);
        foreach ($records_linkedmaster as $key => $record_linkedmaster) {
            $record_linkedmaster=array_values($record_linkedmaster);
            $record['id']=$record_linkedmaster[0];
            $record_value='';
            foreach ($record_linkedmaster as $record_linkedmaster_key => $record_linkedmaster_value) {
                if($record_linkedmaster_key!=0)
                {
                    if($record_value!='')
                        $record_value=$record_value.'-';
                    $record_value=$record_value.$record_linkedmaster_value;
                }
                
            }
            $record['value']=$record_value;
            $return[]=$record;
        }
        
        
        echo json_encode($return);
    }
    
    
    public function adiuto_sync_deal($recordid_deal)
    {
        $serverName = "BIXCRM01";
        $connectionInfo = array( "Database"=>"adibix_data", "UID"=>"sa", "PWD"=>"SB.s.s.21");
        $conn = sqlsrv_connect( $serverName, $connectionInfo); 
        $rows=array();
        $stmt = sqlsrv_query($conn, "SELECT * FROM A1029 WHERE F1061='$recordid_deal'");
        while($row = sqlsrv_fetch_array($stmt)) {
            $rows[]=$row;
            $recordid_dealline=$row['F1062'];
            $unitcost_actual=$row['F1043'];
            $fields['uniteffectivecost']=$unitcost_actual;
            $this->Sys_model->update_record("dealline",1,$fields,"recordid_='$recordid_dealline'");
        }
        $this->update_deal($recordid_deal);
    }
    
    
    public function update_deals()
    {
        // aggiornamento stato da adiuto
        
        $serverName = "BIXCRM01";
        $connectionInfo = array( "Database"=>"adibix_data", "UID"=>"sa", "PWD"=>"SB.s.s.21");
        $conn = sqlsrv_connect( $serverName, $connectionInfo); 
        
        $deals= $this->Sys_model->db_get("user_deal","*","sync_adiuto='Si' and dealstatus='Vinta' and dealstage!='Progetto fatturato'  and dealstage!='Invoiced' ","ORDER BY recordid_ desc");
        $deals_counter=count($deals);
        echo "Trattative da aggiornare: $deals_counter <br/>";
        foreach ($deals as $key => $deal) {
            $fields=array();
            echo $deal['id']." - ".$deal['dealname']."<br/>";
            $recordid_deal=$deal['recordid_'];
            
            
            
            $stmt = sqlsrv_query($conn, "SELECT * FROM VA1028 WHERE F1052='$recordid_deal' AND FENA=-1");
            while($row = sqlsrv_fetch_array($stmt)) {
                if($row!=null)
                {
                    $updated_status=$row['F1033'];
                    $tech_adiutoid=$row['F1067'];
                    $fields['adiuto_tech']=$tech_adiutoid;
                    $bixdata_tech= $this->Sys_model->db_get_row("sys_user","*","adiutoid='$tech_adiutoid'"); 
                    if($bixdata_tech!=null)
                    {
                        $fields['project_assignedto']=$bixdata_tech['id'];
                    }
                    
                    $fields['dealstage']=$updated_status;
                    if(($updated_status=='Progetto in corso')||($updated_status=='Ordine materiale'))
                    {
                        $fields['sync_project']='Si';
                    }
                    
                    echo "<b>".$updated_status."</b><br/>";
                    
                    
                }
            }
            
            
                    
            // aggiornamento dealline
            echo "Righe dettaglio:<br/>";
            $deal_lines= $this->Sys_model->db_get("user_dealline","*","recordiddeal_='$recordid_deal' AND deleted_='N'");
            $deal_price=0;
            $deal_cost_expected=0;
            $deal_cost_actual=0;
            $deal_margin_expected=0;
            $deal_margin_actual=0;
            
            foreach ($deal_lines as $key => $deal_line) {
                $fields_dealline=array();
                $recordid_dealline=$deal_line['recordid_'];
                $dealline_name=$deal_line['name'];
                echo "$dealline_name <br/>";
                $stmt = sqlsrv_query($conn, "SELECT * FROM VA1029 WHERE F1062='$recordid_dealline' AND FENA=-1");
                while($row = sqlsrv_fetch_array($stmt)) {
                    if($row!=null)
                    {
                        $dealline_uniteffectivecost=$row['F1043'];
                        $fields_dealline['uniteffectivecost']=$dealline_uniteffectivecost;
                    }
                }
                
                echo "UPDATE dealline:<br/>";
                var_dump($fields_dealline);
                $this->Sys_model->update_record("dealline",1,$fields_dealline,"recordid_='$recordid_dealline'");
            }
            
            echo "UPDATE deal:<br/>";
            var_dump($fields);
            $this->Sys_model->update_record('deal',1,$fields,"recordid_='$recordid_deal'");
            echo "<br/><br/>";
            $this->custom_update('deal', $recordid_deal);
        }
        
        
    }
    
    
    public function api_bexio_create_order($recordid_deal)
    {
        $record_deal=$this->Sys_model->get_record('deal',$recordid_deal);
        $dealname=$record_deal['dealname'];

        $token="eyJraWQiOiI2ZGM2YmJlOC1iMjZjLTExZTgtOGUwZC0wMjQyYWMxMTAwMDIiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJmaW5hbmNlQHN3aXNzYml4LmNoIiwibG9naW5faWQiOiI0NDYxNTI1OS1jOTYwLTExZTktYjE2Mi1hNGJmMDExY2U4NzIiLCJjb21wYW55X2lkIjoicThqZWR2cmV0dmQ1IiwidXNlcl9pZCI6OTQ4NDMsImF6cCI6ImV2ZXJsYXN0LXRva2VuLW9mZmljZS1jbGllbnQiLCJzY29wZSI6Im9wZW5pZCBwcm9maWxlIGVtYWlsIGFsbCB0ZWNobmljYWwiLCJpc3MiOiJodHRwczpcL1wvaWRwLmJleGlvLmNvbSIsImV4cCI6MzE3MDg0MjYyOCwiaWF0IjoxNTk0MDQyNjI4LCJjb21wYW55X3VzZXJfaWQiOjEsImp0aSI6ImYxMTAxNDQwLWZlZjgtNGYyOS1hZjI4LWQyMWQ1MTRiMWRjOCJ9.bVGm_y-FZP13NqT0NdBIak5_nAqWM8Sa0Ggos10xc7nYblK-TB3O42cu7Me1mNGtN4zEckYHHwr1qItc49kSnppr8xuEdEIqqs-SpB0Cw3arxuBxU8-HodUraAtg_HhJalkeDHw0wk0qhLCAOk8mnJ3FLl_LF-LMeC2M3uobDKv-PCutWRP60kPpQ0EbRCdezbFKDrMav6-yqxF4l8IrdINt_W10o8ntWWhaUStY1I0z02FmjFoE0FsnczOITJsUvQMe7VckGsg_oU1GZ0HMipXLCYL7RsCOBhF_5M6G7bEXz0CXE0Z5tbpVlYFoeu074NcUO67lx1L8PUMVOEQ8GUvxUGOL8rbYDKa3Wz9jmmp81BUP0ENtXfjgZp-qG0QHglPWgw1aUekM9amFUYJgXdFyunXeLFtpghwpfHc6FgbkKcl2WGYPm-t4_aVlJACyifC_Gi8xrblze1ZbJY3gDxcLzpUyG3kJIHOsbQX_2Kau_btmZy9RSDuxEZ-x_ow3m1UfbPwz4c8lJb0p23Nwpbt8f_EG4gEZZ6TJvjP74-ikub_4ZxUaH1RiRICbJL6cazBozxxxxhLZ-8irbVnsUXDCLLgEhzjZ4ahFOMFUayL8ShhvVvL8SnRZW6YK-TtRP5Djv4UetoVJh-2JMihhp3NDtFGu2DV9axq2rs9eCTc";
        $headers = array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token.'',
        );
        $url = 'https://api.bexio.com/2.0/kb_order/';
        $client = new \GuzzleHttp\Client();
        
        $request_body=array();
        $request_body['title']="TEST-".$dealname;
        $request_body['contact_id']=297;
        $request_body['user_id']=1;
        $request_body['language_id']=3;
        $request_body['currency_id']=1;
        $request_body['payment_type_id']=1;
        $request_body['header']="";
        $request_body['footer']="Vi ringraziamo per la vostra fiducia, in caso di disaccordo, vi preghiamo di notificarcelo entro 7 giorni. <br/>Rimaniamo a vostra disposizione per qualsiasi domanda,<br/><br/>Con i nostri più cordiali saluti, Swissbix SA";
        $request_body['mwst_type']=0;
        $request_body['mwst_is_net']=true;
        $request_body['show_position_taxes']=false;
        $request_body['is_valid_from']="2023-03-01";
        
        $positions=array();
             
        $deal_lines= $this->Sys_model->db_get("user_dealline","*","recordiddeal_='$recordid_deal'");
         foreach ($deal_lines as $key => $deal_line) {
            $position['text']=$deal_line['name'];
            $position['tax_id']="16";
            $position['account_id']=154;  
            $position['unit_id']=2;
            $position['amount']=$deal_line['quantity'];
            $position['unit_price']=$deal_line['unitprice'];
            $position['type']="KbPositionCustom";
            $positions[]=  $position; 
         }        
        $request_body['positions']=$positions;



       $request_body_json= json_encode($request_body);
       echo $request_body_json."<br/><br/>";



        try {
            $response = $client->request('POST', $url, array(
                'headers' => $headers,
                'body' => $request_body_json
            ));

            $response_json=$response->getBody()->getContents();
            print_r($response_json);
            $response_array= json_decode($response_json,true);


        }
        catch (\GuzzleHttp\Exception\BadResponseException $e) {
            // handle exception or api errors.
            //print_r($e->getMessage());
            print_r('errore');
            print_r($e->getResponse()->getBody()->getContents());
        }
         // CARICAMENTO FATTURE IN BEXIO fine
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
    
    
    
    function api_hubspot_sync_deals()
    {
        $deals= $this->Sys_model->db_get("user_deal","*","syncstatus='Si'","ORDER BY recordid_ desc");
        foreach ($deals as $key => $deal) {
            echo $deal['id']." - ".$deal['dealname']."<br/>";
            $type=$deal['type'];
            $hubspot_id=$deal['hubspot_id'];
            $dealstage=$deal['dealstage'];
            if((isnotempty($type))&&(isnotempty($hubspot_id))&&(isnotempty($dealstage)))
            {
                $dealstage= str_replace(" ", "", $dealstage);
                $this->api_hubspot_update_dealstage($type,$hubspot_id,$dealstage);
            }
        }
        
        
    }
    
    function api_hubspot_update_dealstage($type,$dealid,$dealstage)
    {
        $url="http://10.0.0.23:8822/jdocweb/index.php/sys_viewcontroller/api_hubspot_update_dealstage/$type/$dealid/$dealstage";
        echo $url."<br/><br/>";
        var_dump(file($url));
    }
    
    
    function rinnova_contratto()
    {
        $post=$_POST;
        $old_recordid=$post['recordid'];
        $update_fields=array();
        $update_fields['status']='Complete';
        $this->Sys_model->update_record('servicecontract',1,$update_fields,"recordid_='$old_recordid'");
        
        $new_recordid=$this->Sys_model->duplica_record('servicecontract', $old_recordid);
        
        $old_record=$this->Sys_model->get_record('servicecontract', $old_recordid);
        $new_record=$this->Sys_model->get_record('servicecontract', $new_recordid);
        
        $update_fields=array();
        $update_fields['previousinvoiceno']=$old_record['invoiceno'];
        $update_fields['previousresidual']=$old_record['residualhours'];
        $update_fields['contracthours']=$post['contracthours'];
        $update_fields['residualhours']=$post['contracthours']+$old_record['residualhours'];
        $update_fields['invoiceno']=$post['invoiceno'];
        $update_fields['startdate']=$post['startdate'];
        $update_fields['status']='In progress';
        $update_fields['progress']=0;
        $update_fields['recordidcompany_']=$old_record['recordidcompany_'];
        
        $this->Sys_model->update_record('servicecontract',1,$update_fields,"recordid_='$new_recordid'");
        
        
    }
    
    
    
            
}
?>