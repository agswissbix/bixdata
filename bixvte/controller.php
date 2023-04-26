<?php
require_once("helper.php");

function getProjectHours($projectno,$conn=null)
{
    if($conn==null)
    {
        $conn=connection();
    }
    
    $sql = "
    select *
    from v_vte_project
    where project_no='$projectno'
    ";
    $row=select_row($conn,$sql);
    $projectid=$row['projectid'];
    $totalhours=$row['total_hours'];
    if(isEmpty($totalhours))
    {
        $totalhours=0;
    }
    $usedhours=0;
    $residualhours=0;
    $progresshours=0;
    $sql="SELECT * FROM v_vte_timesheet WHERE projectid='$projectid'";
    $timesheet_rows=select($conn,$sql);
    foreach ($timesheet_rows as $key => $timesheet_row) {
        $worktimedecimal=$timesheet_row['worktimedecimal'];
        $traveltimedecimal=$timesheet_row['traveltimedecimal'];
        $usedhours=$usedhours+$worktimedecimal;
    }
    
    if(!isEmpty($totalhours))
    {
        $progresshours=(int)(($usedhours/$totalhours)*100);
        $residualhours=$totalhours-$usedhours;
    }
    $hours['usedhours']=$usedhours;
    $hours['residualhours']=$residualhours;
    $hours['progresshours']=$progresshours;
    return $hours;
}


function getServiceContractHours($contractno,$conn=null)
{
    if($conn==null)
    {
        $conn=connection();
    }
    
    $sql = "
    select *
    from v_vte_servicecontract
    where contract_no='$contractno'
    ";
    $row=select_row($conn,$sql);
    $servicecontractid=$row['servicecontractsid'];
    $totalhours=$row['totalhours'];
    $startusedhours=$row['startusedhours'];
    $startresidualhours=$row['startresidualhours'];
    $exclude_travel=$row['exclude_travel'];
	$start_date=$row['start_date'];
	$end_date=$row['end_date'];
	$start_date_current_year='';
	$end_date_next_year='';
	$contract_type=$row['contract_type'];
	
    if(isEmpty($startusedhours))
    {
        $startusedhours=0;
    }
    if(isEmpty($startresidualhours))
    {
        $startresidualhours=0;
    }
    if(isEmpty($totalhours))
    {
        $totalhours=0;
    }
    $usedhours=0;
	$usedhours_calendaryear=0;
	$usedhours_contractyear=0;
    $residualhours=0;
    $progresshours=0;
    $sql="SELECT * FROM v_vte_timesheet WHERE servicecontractid='$servicecontractid'";
    $timesheet_rows=select($conn,$sql);
	$current_year=date('Y');
	$next_year=$current_year+1;
    foreach ($timesheet_rows as $key => $timesheet_row) {
		$date=$timesheet_row['date'];
        $worktimedecimal=$timesheet_row['worktimedecimal'];
        $traveltimedecimal=$timesheet_row['traveltimedecimal'];
        if($exclude_travel)
        {
            $traveltimedecimal=0;
        }
		
        $usedhours=$usedhours+$worktimedecimal+$traveltimedecimal;
		
		if(($date>=$current_year."-01-01")&&($date<=$current_year."-12-31"))
		{
			$usedhours_calendaryear=$usedhours_calendaryear+$worktimedecimal+$traveltimedecimal;
		}	

		$start_date_current_year=$current_year."-".date("m-d", strtotime($start_date));
		$end_date_next_year=$next_year."-".date("m-d", strtotime($start_date_current_year));
		
		if(($date>=$start_date_current_year)&&($date<=$end_date_next_year))
		{
			$usedhours_contractyear=$usedhours_contractyear+$worktimedecimal+$traveltimedecimal;
		}
		
    }
    //$usedhours=$usedhours+$startusedhours;
    //$totalhours=$totalhours+$startresidualhours;
    if(!isEmpty($totalhours+$startresidualhours))
    {
		if(($contract_type=='Monte Ore IT')||($contract_type=='Monte Ore SW'))
		{
			$progresshours_usedhours=$usedhours;
		}
		else
		{
			$progresshours_usedhours=$usedhours_contractyear;
		}
		
        $progresshours=(int)((($progresshours_usedhours-$startresidualhours)/($totalhours))*100);
        if($progresshours<0)
        {
            $progresshours=0;
        }
        $residualhours=$totalhours+$startresidualhours-$progresshours_usedhours;
    }
    $hours['usedhours']=$usedhours;
	$hours['usedhours_calendaryear']=$usedhours_calendaryear;
	$hours['usedhours_contractyear']=$usedhours_contractyear;
    $hours['residualhours']=$residualhours;
    $hours['progresshours']=$progresshours;
	$hours['test']='test2';
	$hours['exclude_travel']=$exclude_travel;
	
    return $hours;
}


function getTicketHours($tickeno,$conn=null)
{
    if($conn==null)
    {
        $conn=connection();
    }
    
    $sql = "
    select *
    from v_vte_ticket
    where ticket_no='$tickeno'
    ";
    $row=select_row($conn,$sql);
    $ticketid=$row['ticketid'];
    $totalhours=0;
    $sql="SELECT * FROM v_vte_timesheet WHERE ticketid='$ticketid'";
    $timesheet_rows=select($conn,$sql);
    foreach ($timesheet_rows as $key => $timesheet_row) {
        $worktimedecimal=$timesheet_row['worktimedecimal'];
        $traveltimedecimal=$timesheet_row['traveltimedecimal'];
        $totalhours=$totalhours+$worktimedecimal+$traveltimedecimal;
    }
    
  
    $hours['hours']=$totalhours;
    return $hours;
}

function getTimesheetFields($timesheetid,$conn=null)
{
    $log='';
    $timesheetid=str_replace('50x','',$timesheetid);
    if($conn==null)
    {
        $conn=connection();
    }
    $sql = "
    select *
    from v_vte_timesheet
    where timesheetid='$timesheetid'
    ";
    $row=select_row($conn,$sql);
    
    $date=$row['date'];
    $print_date=date("d.m.Y",strtotime($date));
    $first_name=$row['first_name'];
    $last_name=$row['last_name'];
    $print_tech=substr($first_name, 0, 1).".".$last_name;
    $invoiceoption=$row['invoiceoption'];
    $timesheetdate=$row['date'];
    
    $serviceid=$row['serviceid'];
    $sql="SELECT * FROM v_vte_service where serviceid='$serviceid'";
    $service= select_row($conn, $sql);
    
    $accountid=$row['accountid'];
    $sql="SELECT * FROM v_vte_account where accountid='$accountid'";
    $account= select_row($conn, $sql);
    
    
    $servicecontractid=$row['servicecontractid'];
    
    
    $projectid=$row['projectid'];
    $sql="SELECT * FROM v_vte_project where projectid='$projectid' ";
    $project= select_row($conn, $sql);
    
    
    $ticketid=$row['ticketid'];
    $sql="SELECT * FROM v_vte_ticket where ticketid='$ticketid' ";
    $ticket= select_row($conn, $sql);
    
    $date=$row['date'];
    $timesheetdescription=$row['timesheetdescription'];
    
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

    $traveltime=$row['traveltime'];
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
    
    $totaltimedecimal=$worktimedecimal+$traveltimedecimal;
    $processstatus='To Process';
    $invoicestatus=$row['invoicestatus'];
    if($invoicestatus!='Invoiced')
    {
        $invoicestatus='To Process';
    }
    
    $servicecontractsid=null;
    $workprice=0;
    $travelprice=0;
    $totalprice=0;
    $fixedprice=$project['fixedprice'];
    $unit_price=$service['unit_price'];
    $itpbx_price=$account['itpbx_price'];
    $sw_price=$account['sw_price'];
    $account_travelprice=$account['travel_price'];
    
    //aggiornamento diretto del service contract per annullarlo sul timesheet - devo eliminarlo - inizio
            $sql = "
                update
                vte_timesheet
                set vcf_2_20=null
                where timesheetid='$timesheetid'			
                ";
            $result = $conn->query($sql);

    //aggiornamento diretto del service contract per annullarlo sul timesheet - devo eliminarlo - fine
    
    
    if(($serviceid=='220972')||($serviceid=='211304')||($serviceid=='211305')||($serviceid=='305940')||($serviceid=='305937'))
    {
        $invoicestatus='';
    }
    
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

        if($invoiceoption=='Under warranty')
        {
            $invoicestatus='Under warranty';
        }

        if($invoiceoption=='Out of contract')
        {
            $invoicestatus='Out of contract';
        }
        
        if($invoiceoption=='Commercial support')
        {
            $invoicestatus='Commercial support';
        }
        
        if($invoiceoption=='Swisscom ServiceNow')
        {
            $invoicestatus='Swisscom ServiceNow';
        }
    }
    
    if($invoicestatus=='To Process')
    {
        if($project != null)
        {
            //$invoicestatus='Project';
            $project_actualenddate=$project['actualenddate'];
            if(($fixedprice==1) &&( $timesheetdate<=$project_actualenddate || isEmpty($project_actualenddate)))
            {
               $invoicestatus='Fixed price Project'; 
            }
        }
    }
    
    if(($invoicestatus=='To Process'))
    {
        if(isEmpty($servicecontractid))
        {
            if($serviceid=='66466')
            {
                if(isEmpty($traveltimedecimal))
                {
                    $sql="SELECT * FROM v_vte_servicecontract where service_id='$serviceid' and account_id='$accountid' and contract_type='BeAll' and (contract_status is null or (contract_status<>'Complete' and contract_status<>'Archived'))  ";
                    $servicecontract= select_row($conn, $sql);
                    if($servicecontract==null)
                    {
                        $sql="SELECT * FROM v_vte_servicecontract where service_id='$serviceid' and account_id='$accountid' and (contract_status is null or (contract_status<>'Complete' and contract_status<>'Archived'))  ";
                    }
                    
                }
                else
                {
                    $sql="SELECT * FROM v_vte_servicecontract where service_id='$serviceid' and account_id='$accountid' and contract_type!='BeAll' and (contract_status is null or (contract_status<>'Complete' and contract_status<>'Archived'))  ";
                }
            }
            else
            {
                $sql="SELECT * FROM v_vte_servicecontract where service_id='$serviceid' and account_id='$accountid' and (contract_status is null or (contract_status<>'Complete' and contract_status<>'Archived'))  ";
            }
            
            $servicecontract= select_row($conn, $sql);
            
        }
        else
        {
            $sql="SELECT * FROM v_vte_servicecontract where servicecontractsid='$servicecontractid' ";
            $servicecontract= select_row($conn, $sql);
        }
            
        if($servicecontract != null)
        {
            $invoicestatus='Service Contract';
            $servicecontractsid=$servicecontract['servicecontractsid'];
            //aggiornamento diretto del service contract sul timesheet - devo eliminarlo - inizio
            $sql = "
                update
                vte_timesheet
                set vcf_2_20='$servicecontractsid'
                where timesheetid='$timesheetid'			
                ";
            $result = $conn->query($sql);

            //aggiornamento diretto del service contract sul timesheet - devo eliminarlo - fine
			
            $contracttype=$servicecontract['contract_type'];
			$log=$contracttype;
            if(($contracttype=='PBX')||($contracttype=='BeAll'))
            {
                $invoicestatus='Flat Service Contract';
                if(!isEmpty($traveltimedecimal))
                {
                    $invoicestatus='To Process';
                }

            }
			if($contracttype=='Manutenzione printing')
			{
				$invoicestatus='Flat Service Contract';
			}

        }
    }
        
    if(($invoicestatus=='To Process')||($invoicestatus=='Out of contract'))
    {

        if(($service != null)&&($account != null))
        {

            $invoicestatus='To Invoice';
            //parte da sistemare per renderla dinamica inizio


            if(($serviceid=='66469' )&&(($sw_price != null)&& ($sw_price != '') &&($sw_price != 0)))
            {
               $unit_price=$sw_price;
            }

            if(($serviceid=='66466' || $serviceid=='153460' )&&(($itpbx_price != null)&& ($itpbx_price != '') &&($itpbx_price != 0)))
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


            //parte da sistemare per renderla dinamica fine

            $workprice=$unit_price*$worktimedecimal;
            $totalprice=$workprice+$travelprice;

            if(!isEmpty($ticket))
            {
                $ticket_status=$ticket['status'];
                if($ticket_status!='Closed')
                {
                    $invoicestatus='To Invoice when Ticket Closed';
                }
            }

            if(!isEmpty($project))
            {
                $project_status=$project['projectstatus'];
                if($project_status!='Completed')
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
    
    $timesheetFields['print_date']=$print_date;
    $timesheetFields['print_tech']=$print_tech;
    $timesheetFields['invoicestatus']=$invoicestatus;
    $timesheetFields['servicecontractsid']=$servicecontractsid;
    $timesheetFields['worktimedecimal']=$worktimedecimal;
    $timesheetFields['workprice']=$workprice;
    $timesheetFields['traveltimedecimal']=$traveltimedecimal;
    $timesheetFields['travelprice']=$travelprice;
    $timesheetFields['totaltimedecimal']=$totaltimedecimal;
    $timesheetFields['totalprice']=$totalprice;
    $timesheetFields['ws version']="1.52";
    $timesheetFields['log']=$log;
    
    return $timesheetFields;
}

function set_vte_invoiced($conn,$invoiceid)
{
    echo "$invoiceid <br/>";
        $sql="
            SELECT * 
            FROM bix_invoicerows
            WHERE bix_invoicesid='$invoiceid'

            ";
        $invoicerows= select($conn, $sql);
        foreach ($invoicerows as $key => $invoicerow) {
            $timesheetid=$invoicerow['timesheetid'];
            if(!isEmpty($timesheetid))
            {
                $sql="
                    UPDATE vte_timesheetcf
                    SET cf_nit_1546='Invoiced'
                    WHERE timesheetid='$timesheetid'
                    ";
                $conn->query($sql);
                echo $sql."<br/>";
            }
        }
}

?>