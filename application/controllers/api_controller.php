<?php

class Api_controller extends CI_Controller {
    
    function __construct()
    {
        parent::__construct();
    }
    
   public function create_project()
   {
       
        $fields['id']= $this->Sys_model->generate_seriale('project', 'id');
        $fields['projectname']='test';
        $this->Sys_model->insert_record('project',1, $fields);
   }
}
?>