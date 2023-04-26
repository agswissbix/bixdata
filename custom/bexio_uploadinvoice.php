<?php

require_once("controller.php");
$conn = connection();

if(array_key_exists('setInvoiced', $_POST))
{
    $setInvoiced=true;
}
else
{
    $setInvoiced=false;
}
    
if(array_key_exists('invoices', $_POST))
{
    $invoices=$_POST['invoices'];
    $date=$_POST['date'];
    foreach ($invoices as $key => $invoiceid) {
        echo "$invoiceid <br/>";
        $sql="
            UPDATE bix_invoices
            SET bexioupload=1,date='$date'
            WHERE bix_invoicesid='$invoiceid'

            ";
        echo "$sql <br/>";
        $conn->query($sql);
        if($setInvoiced)
        {
            set_vte_invoiced($conn, $invoiceid);
        }
    }
}

?>