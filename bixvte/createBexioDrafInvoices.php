<?php
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '1024');
require_once("controller.php");


function insert_product_row($insert_conn,$bix_invoicesid,$timesheet)
{
    $workprice=$timesheet['workprice'];
    
    
    $fields_details=array();
    
    if(($timesheet['serviceid']=='153460')||($timesheet['serviceid']=='66466'))
    {
        $fields_details['count']=3400;
    }
    if($timesheet['serviceid']=='66469')
    {
        $fields_details['count']=3401;
    }
    $data= date('d/m/Y', strtotime($timesheet['date']));
    $lastname=$timesheet['last_name'];
    $firstname=$timesheet['first_name'];
    $description=$timesheet['timesheetdescription'];
    $notes=$timesheet['notes'];
    
    if(!isEmpty($notes))
    {
        $notes="$notes <br/>";
    }
    $fields_details['description']="$description <br/> $notes <br/> <span style='font-size:smaller;'><b>$data $firstname $lastname </b></span>";
    
    
    $fields_details['totalprice']=$workprice;
    $fields_details['bix_invoicesid']=$bix_invoicesid;
    $fields_details['timesheetid']=$timesheet['timesheetid'];
    $fields_details['quantity']=$timesheet['worktimedecimal'];
    $fields_details['unitprice']=$workprice/$timesheet['worktimedecimal'];
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


$conn_vte = connection();

$sql="delete from bix_invoices";
$conn_vte->query($sql);

$sql="delete from bix_invoicerows";
$conn_vte->query($sql);

$sql = "
select *
from v_vte_timesheet left join vte_crmentity ON vte_crmentity.crmid=v_vte_timesheet.timesheetid left join vte_users ON vte_users.id=vte_crmentity.smownerid
where invoicestatus='To Invoice'
order by date asc
";
$timesheets=select($conn_vte,$sql);
$timesheetsgrouped=array();
foreach ($timesheets as $key => $timesheet) {
    $accountid=$timesheet['accountid'];
    $projectid=$timesheet['projectid'];
    $ticketid=$timesheet['ticketid'];
    
    if(!isEmpty($ticketid))
    {
        $timesheetsgrouped[$timesheet['accountid']]['ticket'][$ticketid][]=$timesheet;
    }
    else
    {
        if(!isEmpty($projectid))
        {
            $timesheetsgrouped[$timesheet['accountid']]['project'][$projectid][]=$timesheet;
        }
        else
        {
            $timesheetsgrouped[$timesheet['accountid']]['timesheet'][]=$timesheet;
        }
    }
    
  
}  
$sql = "
select v_vte_account.accountid,v_vte_account.accountname
from v_vte_timesheet join v_vte_account on v_vte_timesheet.accountid=v_vte_account.accountid 
where invoicestatus='To Invoice'
group by v_vte_account.accountid,v_vte_account.accountname
order by v_vte_account.accountname
";
$accounts=select($conn_vte,$sql);

foreach ($accounts as $account_key => $account) {
    $accountid=$account['accountid'];
    $account_timesheets=$timesheetsgrouped[$accountid];
    $sql="select * from v_vte_account where accountid='$accountid'";
    $account= select_row($conn_vte, $sql);
    $fields['accountid']=$accountid;
    $bix_invoicesid= insert($conn_vte, "bix_invoices", $fields);
    echo "bixinvoices:".$bix_invoicesid."<br/>";
    $total=0;
    echo "Righe di dettaglio: <br/>";
    
    if(array_key_exists('project', $account_timesheets))
    {
        foreach ($account_timesheets['project'] as $projectid => $project_timesheets) {
            $sql="
                select *
                from v_vte_project
                where projectid='$projectid'
                ";
            $project= select_row($conn_vte, $sql);
            if($project!=null)
            {
                insert_text_row($conn_vte, $bix_invoicesid, "<b>Progetto ".$project['projectname']."</b>",'Project',$project['projectid']);
            }
            foreach ($project_timesheets as $projectid => $timesheet) {
                insert_product_row($conn_vte, $bix_invoicesid, $timesheet);
                $total=$total+$timesheet['totalprice'];
            }
        }
    }
    
    if(array_key_exists('ticket', $account_timesheets))
    {
        foreach ($account_timesheets['ticket'] as $ticketid => $ticket_timesheets) {
            $sql="
                select *
                from v_vte_ticket
                where ticketid='$ticketid'
                ";
            $ticket= select_row($conn_vte, $sql);
            if($ticket!=null)
            {
                insert_text_row($conn_vte, $bix_invoicesid, "<b>Ticket ".$ticket['ticketid']."</b> <br/>".$ticket['contact'].": ".$ticket['title'],'Ticket',$ticket['ticketid']);
            }
            foreach ($ticket_timesheets as $timesheet_key => $timesheet) {
                insert_product_row($conn_vte, $bix_invoicesid, $timesheet);
                $total=$total+$timesheet['totalprice'];
            }
        }
    }
    
    
    if(array_key_exists('timesheet', $account_timesheets))
    {
        if(count($account_timesheets['timesheet'])>0)
        {
            echo 'Interventi<br/>';
            insert_text_row($conn_vte, $bix_invoicesid, "<b>Interventi</b>","Text");
            foreach ($account_timesheets['timesheet'] as $timesheet_key => $timesheet) {
                insert_product_row($conn_vte, $bix_invoicesid, $timesheet);
                $total=$total+$timesheet['totalprice'];
            }
        }
    }
    
    
  
    echo $total."<br/>";
    $sql="UPDATE bix_invoices SET total='$total' where bix_invoicesid='$bix_invoicesid'";
    $conn_vte->query($sql);
}

  

?>