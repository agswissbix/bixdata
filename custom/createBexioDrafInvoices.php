<?php
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '1024');
require_once("controller.php");


function insert_product_row($insert_conn,$bix_invoicesid,$timesheet)
{
    $workprice=$timesheet['workprice'];
    
    
    $fields_details=array();
    
    // TODO
    
    if(($timesheet['service']=='Assistenza PBX')||($timesheet['service']=='Assistenza IT'))
    {
        $fields_details['count']=3400;
    }
    if($timesheet['service']=='Assistenza SW')
    {
        $fields_details['count']=3401;
    }
    if($timesheet['service']=='Printing')
    {
        $fields_details['count']=3505;
    }
    
    $data= date('d/m/Y', strtotime($timesheet['date']));
    $userid=$timesheet['user'];
    $sql="SELECT * FROM sys_user WHERE id='$userid'";
    $user= select_row($insert_conn, $sql);
    $lastname=$user['firstname'];
    $firstname=$user['lastname'];
    $description=$timesheet['description'];
    //$notes="test";//$timesheet['notes'];
    
    if(!isEmpty($notes))
    {
        $notes="$notes <br/>";
    }
    $fields_details['description']="$description <br/> $notes <br/> <span style='font-size:smaller;'><b>$data $firstname $lastname </b></span>";
    
    
    $fields_details['totalprice']=$workprice;
    $fields_details['bix_invoicesid']=$bix_invoicesid;
    $fields_details['timesheetid']=$timesheet['recordid_'];
    $fields_details['quantity']=$timesheet['worktime_decimal'];
    $fields_details['unitprice']=$workprice/$timesheet['worktime_decimal'];
    $fields_details['type']='Timesheet';
    
    $bix_invoicerowsid= insert($insert_conn, "bix_invoicerows",$fields_details);
    echo $bix_invoicerowsid."<br/>";
    
    
    // trasferta
    $fields_details=array();
    $travelprice=$timesheet['travelprice'];
    if(!isEmpty($travelprice))
    {
        $fields_details['count']=3780;
        $fields_details['description']='Trasferta';
        $fields_details['totalprice']=$travelprice;
        $fields_details['bix_invoicesid']=$bix_invoicesid;
        $fields_details['type']='Travel';
        
        $bix_invoicerowsid= insert($insert_conn, "bix_invoicerows",$fields_details);
        echo $bix_invoicerowsid."<br/>";
    }
    
    /*
    // materiale
    $fields_details=array();
    $timesheetid=$timesheet['timesheetid'];
    $sql="SELECT * FROM vte_modlight11 WHERE parent_id='$timesheetid'";
    $timesheet_products=select($insert_conn,$sql);
    foreach ($timesheet_products as $key => $timesheet_product) {
        $product_description=$timesheet_product['f70'];
        $product_quantity=$timesheet_product['f71'];
        if($product_quantity>0)
        {
            $fields_details['count']=3200;
            $fields_details['description']=$product_description;
            $fields_details['quantity']=round($product_quantity,2);
            $fields_details['bix_invoicesid']=$bix_invoicesid;
            $fields_details['type']='Product';

            $bix_invoicerowsid= insert($insert_conn, "bix_invoicerows",$fields_details);
            echo $bix_invoicerowsid."<br/>";
        }
    }
   */
        
    
    
}

function insert_text_row($insert_conn,$bix_invoiceid,$text,$type='',$refid='')
{
    $fields_details=array();
    $fields_details['description']=$text;
    $fields_details['bix_invoicesid']=$bix_invoiceid;
    $fields_details['type']=$type;
    if($type=='Ticket')
    {
        $fields_details['ticketid']=$refid;
    }
    if($type=='Project')
    {
        $fields_details['projectid']=$refid;
    }
    $bix_invoicerowsid= insert($insert_conn, "bix_invoicerows",$fields_details);
    echo $bix_invoicerowsid."<br/>";
    
}


$conn_bixdata = connection();

$sql="delete from bix_invoices";
$conn_bixdata->query($sql);

$sql="delete from bix_invoicerows";
$conn_bixdata->query($sql);

$sql = "
select *
from user_timesheet
where invoicestatus='To Invoice' and deleted_='N'
order by date asc
";
$timesheets=select($conn_bixdata,$sql);


$timesheetsgrouped=array();
foreach ($timesheets as $key => $timesheet) {
    $accountid=$timesheet['recordidcompany_'];
    $projectid=$timesheet['recordidproject_'];
    $ticketid=$timesheet['recordidticket_'];
    
    if(!isEmpty($ticketid))
    {
        $timesheetsgrouped[$timesheet['recordidcompany_']]['ticket'][$ticketid][]=$timesheet;
    }
    else
    {
        if(!isEmpty($projectid))
        {
            $timesheetsgrouped[$timesheet['recordidcompany_']]['project'][$projectid][]=$timesheet;
        }
        else
        {
            $timesheetsgrouped[$timesheet['recordidcompany_']]['timesheet'][]=$timesheet;
        }
    }
    
  
}  


$sql = "
    
select uc.recordid_,uc.companyname
from user_timesheet as ut JOIN user_company uc on ut.recordidcompany_=uc.recordid_
where invoicestatus='To Invoice' and ut.deleted_='N'
GROUP BY uc.recordid_,uc.companyname
ORDER BY uc.companyname

";
$accounts=select($conn_bixdata,$sql);

foreach ($accounts as $account_key => $account) {
    $accountid=$account['recordid_'];
    $account_timesheets=$timesheetsgrouped[$accountid];
    $sql="select * from user_company where recordid_='$accountid'";
    $account= select_row($conn_bixdata, $sql);
    $fields['accountid']=$accountid;
    $bix_invoicesid= insert($conn_bixdata, "bix_invoices", $fields);
    echo "bixinvoices:".$bix_invoicesid."<br/>";
    $total=0;
    
    echo "Righe di dettaglio: <br/>";
    
    
    if(array_key_exists('project', $account_timesheets))
    {
        foreach ($account_timesheets['project'] as $projectid => $project_timesheets) {
            $sql="
                select *
                from user_project
                where recordid_='$projectid'
                ";
            $project= select_row($conn_bixdata, $sql);
            if($project!=null)
            {
                insert_text_row($conn_bixdata, $bix_invoicesid, "<b>Progetto ".$project['projectname']."</b>",'Project',$project['recordid_']);
            }
            foreach ($project_timesheets as $projectid => $timesheet) {
                insert_product_row($conn_bixdata, $bix_invoicesid, $timesheet);
                $total=$total+$timesheet['totalprice'];
            }
        }
    }
    
    
    if(array_key_exists('ticket', $account_timesheets))
    {
        foreach ($account_timesheets['ticket'] as $ticketid => $ticket_timesheets) {
            $sql="
                select *
                from user_ticket
                where recordid_='$ticketid'
                ";
            $ticket= select_row($conn_bixdata, $sql);
            if($ticket!=null)
            {
                insert_text_row($conn_bixdata, $bix_invoicesid, "<b>Ticket ".$ticket['freshdeskid']."</b> <br/>".$ticket['customer'].": ".$ticket['subject'],'Ticket',$ticket['recordid_']);
            }
            foreach ($ticket_timesheets as $timesheet_key => $timesheet) {
                insert_product_row($conn_bixdata, $bix_invoicesid, $timesheet);
                $total=$total+$timesheet['totalprice'];
            }
        }
    }
    
    
    if(array_key_exists('timesheet', $account_timesheets))
    {
        if(count($account_timesheets['timesheet'])>0)
        {
            echo 'Interventi<br/>';
            insert_text_row($conn_bixdata, $bix_invoicesid, "<b>Interventi</b>","Text");
            foreach ($account_timesheets['timesheet'] as $timesheet_key => $timesheet) {
                insert_product_row($conn_bixdata, $bix_invoicesid, $timesheet);
                $total=$total+$timesheet['totalprice'];
            }
        }
    }
    
    
  
    echo $total."<br/>";
    $sql="UPDATE bix_invoices SET total='$total' where bix_invoicesid='$bix_invoicesid'";
    $conn_bixdata->query($sql);
    
    
}


  

?>