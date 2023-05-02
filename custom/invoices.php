
<?php
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '1024');
require_once("controller.php");
?>
<div style="display: none">
<?php
require_once("createBexioDrafInvoices.php");
$totaltotal=0;
?>
</div>

<?php
$conn_bixdata = connection();

$sql="
    SELECT *
    FROM bix_invoices
    ";
$invoices=$conn_bixdata->query($sql);
?>


        
  <!DOCTYPE html>
  <html>
    <head>
      <!--Import Google Icon Font-->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <!--Import materialize.css-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

      <!--Let browser know website is optimized for mobile-->
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>

    <body style="background-color: #f6f8f9;">

      <!--JavaScript at end of body for optimized loading-->
      <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    
    <style>
        .collapsible-body{
            padding-top: 0px !important;
        }
        
        .btn{
            
            background-color: #e8eef1 !important;
            color: #16556F !important;
            border: 1px solid #16556F;
        }
        
        .modalpopup{
            max-height: none;
            width: 90%;
            height: 90%;
        }
    </style>
    <script type="text/javascript">

        $(document).ready(function(){
          $('.collapsible').collapsible();
          
          
            $(".collapsible-header").click(function() {
                $(".collapsible-header").css("background-color","white");
                $(".collapsible-header").css("color","black");
                $('.ragionesociale').css("color","black");
                $(this).css("background-color","rgb(22, 85, 111)");
                $(this).css("color","white");
                $(this).find('.ragionesociale').css("color","white");
            })
            
            $(".collapsible-header").on({
                mouseenter: function () {
                    $(this).css('border-bottom','1px solid rgb(22, 85, 111)');
                },
                mouseleave: function () {
                    $(this).css('border-bottom','1px solid #ddd');
                }
            });
            
            $('.modal').modal({
                onOpenStart() {
                    console.log("Open Start");
                },
                onOpenEnd() {
                    console.log("Open End");
                },
                onCloseStart(){
                    console.log("Close Start");
                },
                onCloseEnd(){
                    console.log("Close End");
                },
            });
            
            $('#caricamento').hide();
            $('#content').show();
            $('.datepicker').datepicker({
                format:"yyyy-mm-dd"
            });
        });
        
        function vte_setinvoiced(el)
        {
            
                var serialized=$('#invoiceform').serializeArray();
                 $.ajax( {
                    type: "POST",
                    url: "controller/vte_setinvoiced.php",
                    data: serialized,
                    success: function( response ) {
                        //$('#content').html(response);
                        //refresh_risultati_ricerca();
                        window.open('https://bixvte01.dc.swissbix.ch/swissbix/bixvte/invoices.php', '_self');

                    },
                    error:function(){
                        alert('errore');
                    }
                } );
            
            
        }
        
        function bexio_uploadinvoice(el)
        {
            var serialized=$('#invoiceform').serializeArray();
            serialized.push({name: 'test', value: "test"});
             $.ajax( {
                type: "POST",
                url: "bexio_uploadinvoice.php",
                data: serialized,
                success: function( response ) {
                    //$('#content').html(response);
                    //refresh_risultati_ricerca();
                    window.open('http://bixcrm01:8822/bixdata/custom/api_bexio_set_invoices.php', '_self'); 

                },
                error:function(){
                    alert('errore');
                }
            } );
        }
        
    </script>
    <div class="container">
        <div id="caricamento">
            Caricamento
        </div>
        <div id="content" style="display: none">
            
            <form id="invoiceform" method="post" action="api_bexio_set_invoices.php">
            <div class="menu" style="margin-top: 20px;margin-bottom: 20px">
                <!--<a class="waves-effect waves-light btn" onclick="vte_setinvoiced(this)">Segna come fatturato</a>-->
                <a class="waves-effect waves-light btn" onclick="bexio_uploadinvoice(this);">Carica in Bexio</a>
                <label>
                    <input type="checkbox" name="setInvoiced" value="true" checked/>
                    <span class="ragionesociale" style="color: black">Segna come fatturato in Vte</span>
                 </label>
            </div>
                <div class="row">
                    <div class="col s3">
                        <?php
                        $today= date("Y-m-d");
                        ?>
                        Data fattura <input type="text" class="datepicker" name="date" placeholder="Data fattura" value="<?=$today?>">
                    </div>
                </div>
      
    
            <ul class="collapsible">
                <?php
                foreach ($invoices as $key => $invoice) {
                    $totaltotal=$totaltotal+$invoice['total'];
                    $bix_invoicesid=$invoice['bix_invoicesid'];
                    $accountid=$invoice['accountid'];
                    $sql="
                        SELECT *
                        FROM user_company
                        WHERE recordid_='$accountid'
                        ";
                    $account= select_row($conn_bixdata, $sql);
                    
                    $sql="
                    SELECT *
                    FROM bix_invoicerows
                    where bix_invoicesid='$bix_invoicesid'
                    order by bix_invoicesid asc
                    ";
                    $invoice_rows=$conn_bixdata->query($sql);

                ?>
                   
                    <li>
                        <div class="collapsible-header">
                            <div style="width: 100%;">
                                <div class="row">
                                    <div class="col s6">
                                        <label>
                                            <input type="checkbox" name="invoices[]" value="<?=$invoice['bix_invoicesid']?>" />
                                            <span class="ragionesociale" style="color: black"><?= $account['companyname']?></span>
                                         </label>
                                    </div>
                                    <div class="col s6">
                                        <div style="width: 100px;text-align: right"><?=$invoice['total']?></div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="collapsible-body">
                            <div class="card">
                                <table>    
                                    <thead style="background-color: #8cb4c7;">
                                        <th>Conto</th>
                                        <th>Descrizione</th>
                                        <th>Quantità</th>
                                        <th>Prezzo</th>
                                        <th>Totale</th>
                                        <th></th>
                                    </thead>
                                <?php
                                    foreach ($invoice_rows as $key => $invoice_row) {
                                ?>
                                    <tr>
                                        <td><?=$invoice_row['count']?></td>
                                        <td><?=$invoice_row['description']?></td>
                                        <td><?=$invoice_row['quantity']?></td>
                                        <td><?=$invoice_row['unitprice']?></td>
                                        <td><?=$invoice_row['totalprice']?></td>
                                        <td>
                                            <?php
                                            if($invoice_row['type']=='Progetto')
                                            {
                                            ?>
                                                <button data-target="modal1" class="btn modal-trigger">Apri</button>
                                            <?php
                                            }
                                            ?>
                                            <?php
                                            if($invoice_row['type']=='Ticket')
                                            {
                                            ?>
                                                <button data-target="modal1" class="btn modal-trigger" onclick="$('.modal-content').find('iframe').attr('src','')">Apri ticket</button>
                                            <?php
                                            }
                                            ?>
                                                
                                            <?php
                                            if($invoice_row['type']=='Timesheet')
                                            {
                                            ?>
                                                <button data-target="modal1" class="btn modal-trigger" onclick="$('.modal-content').find('iframe').attr('src','http://localhost:8000/get_record_path/timesheet/<?=$invoice_row['timesheetid']?>/')">Apri</button>
                                            <?php
                                            }
                                            ?>    
                                                
                                        </td>
                                    </tr>

                                <?php
                                    }
                                ?>
                                </table>
                            </div>
                        </div>
                    </li>
                <?php
                }
                ?>

                    <li>
                        <div class="collapsible-header">
                            <div style="width: 100%;">
                                <div class="row">
                                    <div class="col s6">
                                        Totale
                                    </div>
                                    <div class="col s6">
                                        <div style="width: 100px;text-align: right"><b><?=$totaltotal?></b></div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        
                            
                    </li>
            </ul>
            
            </form>
        </div>
    </div>
    
    <div id="modal1" class="modal modalpopup  modal-fixed-footer" style="height: 100%;margin-top: 0%;">
       <div class="modal-content">
         <iframe style="height:100%;width:100%" src="" ></iframe>
       </div>
       <div class="modal-footer">
         <a href="#!" class="modal-close waves-effect waves-green btn-flat">Chiudi</a>
       </div>
     </div>
    
    <div id="modal2" class="modal modalpopup  modal-fixed-footer">
       <div class="modal-content">
       </div>
       <div class="modal-footer">
         <a href="#!" class="modal-close waves-effect waves-green btn-flat">Chiudi</a>
       </div>
     </div>
    </body>
  </html>