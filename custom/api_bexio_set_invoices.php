<?php

require '../vendor/autoload.php';
require_once("controller.php");
use Jumbojett\OpenIDConnectClient;


$conn = connection();
?>


        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <style>
        .btn{
            
            background-color: #e8eef1 !important;
            color: #16556F !important;
            border: 1px solid #16556F;
        }
    </style>
    <div class="container">
        <br/>
        <a class="waves-effect waves-light btn" onclick="$('#log').toggle()">Log</a>
        <br/>
        <div id="log" style="display: none">
<?php

    
    $sql="SELECT * FROM bix_invoices where bexioupload=1";
    $invoices= select($conn, $sql);
    
    $token="eyJraWQiOiI2ZGM2YmJlOC1iMjZjLTExZTgtOGUwZC0wMjQyYWMxMTAwMDIiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJmaW5hbmNlQHN3aXNzYml4LmNoIiwibG9naW5faWQiOiI0NDYxNTI1OS1jOTYwLTExZTktYjE2Mi1hNGJmMDExY2U4NzIiLCJjb21wYW55X2lkIjoicThqZWR2cmV0dmQ1IiwidXNlcl9pZCI6OTQ4NDMsImF6cCI6ImV2ZXJsYXN0LXRva2VuLW9mZmljZS1jbGllbnQiLCJzY29wZSI6Im9wZW5pZCBwcm9maWxlIGVtYWlsIGFsbCB0ZWNobmljYWwiLCJpc3MiOiJodHRwczpcL1wvaWRwLmJleGlvLmNvbSIsImV4cCI6MzE3MDg0MjYyOCwiaWF0IjoxNTk0MDQyNjI4LCJjb21wYW55X3VzZXJfaWQiOjEsImp0aSI6ImYxMTAxNDQwLWZlZjgtNGYyOS1hZjI4LWQyMWQ1MTRiMWRjOCJ9.bVGm_y-FZP13NqT0NdBIak5_nAqWM8Sa0Ggos10xc7nYblK-TB3O42cu7Me1mNGtN4zEckYHHwr1qItc49kSnppr8xuEdEIqqs-SpB0Cw3arxuBxU8-HodUraAtg_HhJalkeDHw0wk0qhLCAOk8mnJ3FLl_LF-LMeC2M3uobDKv-PCutWRP60kPpQ0EbRCdezbFKDrMav6-yqxF4l8IrdINt_W10o8ntWWhaUStY1I0z02FmjFoE0FsnczOITJsUvQMe7VckGsg_oU1GZ0HMipXLCYL7RsCOBhF_5M6G7bEXz0CXE0Z5tbpVlYFoeu074NcUO67lx1L8PUMVOEQ8GUvxUGOL8rbYDKa3Wz9jmmp81BUP0ENtXfjgZp-qG0QHglPWgw1aUekM9amFUYJgXdFyunXeLFtpghwpfHc6FgbkKcl2WGYPm-t4_aVlJACyifC_Gi8xrblze1ZbJY3gDxcLzpUyG3kJIHOsbQX_2Kau_btmZy9RSDuxEZ-x_ow3m1UfbPwz4c8lJb0p23Nwpbt8f_EG4gEZZ6TJvjP74-ikub_4ZxUaH1RiRICbJL6cazBozxxxxhLZ-8irbVnsUXDCLLgEhzjZ4ahFOMFUayL8ShhvVvL8SnRZW6YK-TtRP5Djv4UetoVJh-2JMihhp3NDtFGu2DV9axq2rs9eCTc";

    $headers = array(
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token.'',
    );
    
    $url = 'https://api.bexio.com/2.0/kb_invoice/';
    $client = new \GuzzleHttp\Client();
    
    $result=array();
    if($invoices!=null)
    {
        foreach ($invoices as $key => $invoice) {
            $request_body=array();
            $bix_invoicesid=$invoice['bix_invoicesid'];
            $accountid=$invoice['accountid'];
            $datefrom=$invoice['date'];
            $dateto=date('Y-m-d', strtotime($datefrom. ' + 20 days'));
            $sql="SELECT * FROM user_company WHERE recordid_='$accountid'";
            $company= select_row($conn, $sql);
            $accountname=$company['companyname'];
            $bexioid=$company['id_bexio'];
            echo "Caricamento....$accountname|$bexioid - $bix_invoicesid ....<br/>";

            $sql="select * from bix_invoicerows where bix_invoicesid='$bix_invoicesid' order by bix_invoicesid asc";
            echo $sql."<br/><br/>";
            $invoice_rows= select($conn, $sql);
            $positions=array();
            foreach ($invoice_rows as $key => $invoice_row) {
                $position=array();
                $type=$invoice_row['type'];
                $position['text']=$invoice_row['description'];
                
                
                if($type=='Timesheet')
                {
                    
                    $count=$invoice_row['count'];
                    $countid=null;
                    if($count=='3400')
                    {
                        $countid="154";
                    }
                    if($count=='3401')
                    {
                        $countid="155";
                    }
                    
                    $position['tax_id']="39";
                    $position['account_id']=$countid;  
                    $position['unit_id']=2;
                    $position['amount']=$invoice_row['quantity'];
                    $position['unit_price']=$invoice_row['unitprice'];
                    $position['type']="KbPositionCustom";
                    
                }
                if($type=='Travel')
                {
                    $position['tax_id']="39";
                    $position['account_id']="324";  
                    $position['unit_id']=2;
                    $position['amount']=1;
                    $position['unit_price']=$invoice_row['totalprice'];
                    $position['type']="KbPositionCustom";
                }
                if($type=='Product')
                {
                    
                    $count=$invoice_row['count'];
                    $position['tax_id']="39";
                    $position['account_id']=149;  
                    $position['unit_id']=2;
                    $position['amount']=$invoice_row['quantity'];
                    $position['unit_price']='0.00';
                    $position['type']="KbPositionCustom";
                    
                }
                if(($type=='Text')||($type=='Ticket')||($type=='Project'))
                {
                    $position['type']="KbPositionText";
                }
                $positions[]=$position;
            } 
            
            $request_body['title']="ICT: Supporto Cliente";
            $request_body['contact_id']=$bexioid;
            $request_body['user_id']=1;
            $request_body['logopaper_id']=1;
            $request_body['language_id']=3;
            $request_body['currency_id']=1;
            $request_body['payment_type_id']=1;
            $request_body['header']="";
            $request_body['footer']="Vi ringraziamo per la vostra fiducia, in caso di disaccordo, vi preghiamo di notificarcelo entro 7 giorni. <br/>Rimaniamo a vostra disposizione per qualsiasi domanda,<br/><br/>Con i nostri più cordiali saluti, Swissbix SA";
            $request_body['mwst_type']=0;
            $request_body['mwst_is_net']=true;
            $request_body['show_position_taxes']=false;
            $request_body['is_valid_from']=$datefrom;
            $request_body['is_valid_to']=$dateto;
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
                $bexio_invoice_nr=$response_array['document_nr'];
                $bexio_id=$response_array['id'];
                echo "<br/>$bexio_invoice_nr<br/>";
                foreach ($invoice_rows as $key => $invoice_row) {
                    
                    $timesheetid=$invoice_row['timesheetid'];
                    $sql="UPDATE user_timesheet SET invoicenr='$bexio_invoice_nr' WHERE recordid_='$timesheetid'";
                    $conn->query($sql);
                     
                }

            }
            catch (\GuzzleHttp\Exception\BadResponseException $e) {
                // handle exception or api errors.
                //print_r($e->getMessage());
                print_r('errore');
                print_r($e->getResponse()->getBody()->getContents());
            }
             
             
            $results[$accountid]['accountname']=$accountname;
            $results[$accountid]['bexio_invoice_nr']=$bexio_invoice_nr;
            $results[$accountid]['bexio_id']=$bexio_invoice_nr;
        }
    }
    else
    {
        echo "Nessuna fattura selezionata da caricare<br/>";
    }
    
    
     

?>
    </div>

<br/>
<div>
    <?php
    foreach ($results as $key => $result) {
    ?>
        
    <?php
    }
    ?>
    <a class="waves-effect waves-light btn" href="http://bixcrm01:8822/bixdata/custom/invoices.php" target="_self">Torna alle fatture</a>
</div>
    </div>