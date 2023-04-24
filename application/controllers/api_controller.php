<?php

class Api_controller extends CI_Controller {
    
    function __construct()
    {
        parent::__construct();
    }
    
   public function crea_progetto($recordid_trattativa)
   {
        header("Access-Control-Allow-Methods: POST, GET");
        header("Access-Control-Allow-Origin: *");
        $record_trattativa=$this->Sys_model->get_record('deal',$recordid_trattativa);
        $dealname=$record_trattativa['dealname'];
        $fields['id']= $this->Sys_model->generate_seriale('project', 'id');
        $fields['projectname']=$dealname;
        $this->Sys_model->insert_record('project',1, $fields);
   }
    
    
    
    public function api_bexio_crea_fattura_acconto_progetto($recordid_deal)
    {
         $record_deal=$this->Sys_model->get_record('deal',$recordid_deal);
         $dealname=$record_deal['dealname'];

         $token="eyJraWQiOiI2ZGM2YmJlOC1iMjZjLTExZTgtOGUwZC0wMjQyYWMxMTAwMDIiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJmaW5hbmNlQHN3aXNzYml4LmNoIiwibG9naW5faWQiOiI0NDYxNTI1OS1jOTYwLTExZTktYjE2Mi1hNGJmMDExY2U4NzIiLCJjb21wYW55X2lkIjoicThqZWR2cmV0dmQ1IiwidXNlcl9pZCI6OTQ4NDMsImF6cCI6ImV2ZXJsYXN0LXRva2VuLW9mZmljZS1jbGllbnQiLCJzY29wZSI6Im9wZW5pZCBwcm9maWxlIGVtYWlsIGFsbCB0ZWNobmljYWwiLCJpc3MiOiJodHRwczpcL1wvaWRwLmJleGlvLmNvbSIsImV4cCI6MzE3MDg0MjYyOCwiaWF0IjoxNTk0MDQyNjI4LCJjb21wYW55X3VzZXJfaWQiOjEsImp0aSI6ImYxMTAxNDQwLWZlZjgtNGYyOS1hZjI4LWQyMWQ1MTRiMWRjOCJ9.bVGm_y-FZP13NqT0NdBIak5_nAqWM8Sa0Ggos10xc7nYblK-TB3O42cu7Me1mNGtN4zEckYHHwr1qItc49kSnppr8xuEdEIqqs-SpB0Cw3arxuBxU8-HodUraAtg_HhJalkeDHw0wk0qhLCAOk8mnJ3FLl_LF-LMeC2M3uobDKv-PCutWRP60kPpQ0EbRCdezbFKDrMav6-yqxF4l8IrdINt_W10o8ntWWhaUStY1I0z02FmjFoE0FsnczOITJsUvQMe7VckGsg_oU1GZ0HMipXLCYL7RsCOBhF_5M6G7bEXz0CXE0Z5tbpVlYFoeu074NcUO67lx1L8PUMVOEQ8GUvxUGOL8rbYDKa3Wz9jmmp81BUP0ENtXfjgZp-qG0QHglPWgw1aUekM9amFUYJgXdFyunXeLFtpghwpfHc6FgbkKcl2WGYPm-t4_aVlJACyifC_Gi8xrblze1ZbJY3gDxcLzpUyG3kJIHOsbQX_2Kau_btmZy9RSDuxEZ-x_ow3m1UfbPwz4c8lJb0p23Nwpbt8f_EG4gEZZ6TJvjP74-ikub_4ZxUaH1RiRICbJL6cazBozxxxxhLZ-8irbVnsUXDCLLgEhzjZ4ahFOMFUayL8ShhvVvL8SnRZW6YK-TtRP5Djv4UetoVJh-2JMihhp3NDtFGu2DV9axq2rs9eCTc";
         $headers = array(
             'Accept' => 'application/json',
             'Authorization' => 'Bearer '.$token.'',
         );
         $url = 'https://api.bexio.com/2.0/kb_invoice/';
         $client = new \GuzzleHttp\Client();

         $request_body=array();
         $request_body['title']="TEST-".$dealname."-ACCONTO";
         $request_body['contact_id']=297;
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
         $request_body['is_valid_from']="2023-05-01";
         $request_body['is_valid_to']="2023-05-21";

         $positions=array();
         
         $deal_lines= $this->Sys_model->db_get("user_dealline","*","recordiddeal_='$recordid_deal'");
         foreach ($deal_lines as $key => $deal_line) {
            $position['text']=$deal_line['name'];
            $position['tax_id']="16";
            $position['account_id']=154;  
            $position['unit_id']=2;
            $position['amount']=1;
            $position['unit_price']=140;
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
       
       
    public function api_bexio_crea_fattura_saldo_progetto($recordid_trattativa)
    {
        
        $record_trattativa=$this->Sys_model->get_record('deal',$recordid_trattativa);
        $dealname=$record_trattativa['dealname'];
        
        $token="eyJraWQiOiI2ZGM2YmJlOC1iMjZjLTExZTgtOGUwZC0wMjQyYWMxMTAwMDIiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiJmaW5hbmNlQHN3aXNzYml4LmNoIiwibG9naW5faWQiOiI0NDYxNTI1OS1jOTYwLTExZTktYjE2Mi1hNGJmMDExY2U4NzIiLCJjb21wYW55X2lkIjoicThqZWR2cmV0dmQ1IiwidXNlcl9pZCI6OTQ4NDMsImF6cCI6ImV2ZXJsYXN0LXRva2VuLW9mZmljZS1jbGllbnQiLCJzY29wZSI6Im9wZW5pZCBwcm9maWxlIGVtYWlsIGFsbCB0ZWNobmljYWwiLCJpc3MiOiJodHRwczpcL1wvaWRwLmJleGlvLmNvbSIsImV4cCI6MzE3MDg0MjYyOCwiaWF0IjoxNTk0MDQyNjI4LCJjb21wYW55X3VzZXJfaWQiOjEsImp0aSI6ImYxMTAxNDQwLWZlZjgtNGYyOS1hZjI4LWQyMWQ1MTRiMWRjOCJ9.bVGm_y-FZP13NqT0NdBIak5_nAqWM8Sa0Ggos10xc7nYblK-TB3O42cu7Me1mNGtN4zEckYHHwr1qItc49kSnppr8xuEdEIqqs-SpB0Cw3arxuBxU8-HodUraAtg_HhJalkeDHw0wk0qhLCAOk8mnJ3FLl_LF-LMeC2M3uobDKv-PCutWRP60kPpQ0EbRCdezbFKDrMav6-yqxF4l8IrdINt_W10o8ntWWhaUStY1I0z02FmjFoE0FsnczOITJsUvQMe7VckGsg_oU1GZ0HMipXLCYL7RsCOBhF_5M6G7bEXz0CXE0Z5tbpVlYFoeu074NcUO67lx1L8PUMVOEQ8GUvxUGOL8rbYDKa3Wz9jmmp81BUP0ENtXfjgZp-qG0QHglPWgw1aUekM9amFUYJgXdFyunXeLFtpghwpfHc6FgbkKcl2WGYPm-t4_aVlJACyifC_Gi8xrblze1ZbJY3gDxcLzpUyG3kJIHOsbQX_2Kau_btmZy9RSDuxEZ-x_ow3m1UfbPwz4c8lJb0p23Nwpbt8f_EG4gEZZ6TJvjP74-ikub_4ZxUaH1RiRICbJL6cazBozxxxxhLZ-8irbVnsUXDCLLgEhzjZ4ahFOMFUayL8ShhvVvL8SnRZW6YK-TtRP5Djv4UetoVJh-2JMihhp3NDtFGu2DV9axq2rs9eCTc";

        $headers = array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token.'',
        );

        $url = 'https://api.bexio.com/2.0/kb_invoice/';
        $client = new \GuzzleHttp\Client();

        $request_body=array();


        $request_body['title']="TEST-".$dealname."-SALDO";
        $request_body['contact_id']=297;
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
        $request_body['is_valid_from']="2023-03-01";
        $request_body['is_valid_to']="2023-03-21";
        
        $positions=array();
        $position['text']="riga di test";
        $position['tax_id']="16";
        $position['account_id']=154;  
        $position['unit_id']=2;
        $position['amount']=1;
        $position['unit_price']=140;
        $position['type']="KbPositionCustom";
        $positions[]=  $position;      
                
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
       
       
       public function aggiorna_dati_trattativa($recordid_trattativa)
       {
           
       }
}
?>