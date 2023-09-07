<?php
//$mi_sensor=$array_nombres[$sensor];

$table_name='sensores';
$cid=$_REQUEST['s'];

$sql="SELECT * FROM sensores WHERE id_sensor='$cid'"; 
$res=$db_cms->select_query_with_row($sql);

?>
<div class="row">
    <div class="col-sm-7">
        <div class="card">
            <div class="card-header" style='display: contents;'>
                <h2 style='margin-left: 10px;'>Información del sensor <?php echo $res['nombre']; ?></h2>
                <div class="btn-actions-pane-right">
                    <a href="?module=13&page=4" class="mb-2 mr-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-primary"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                    <a href="index.php?module=13&page=9&s=<?=$cid?>"><button class="mb-2 mr-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success"><i class="fa fa-pen"></i> Editar</button></a>
                </div>
            </div>
            <div class="card-body">
                <form class="">
                    <div class="form-row" style="margin-bottom: 10px;">
                        <div class="col-md-4">
                            <div class="position-relative form-group"><label for="exampleEmail11" class="">ID</label><span class="form-control"><?php echo $res['id_sensor'];?></span></div>
                        </div>
                        <div class="col-md-4">
                            <div class="position-relative form-group"><label for="examplePassword11" class="">Nombre del sensor</label><span class="form-control"><?php echo $res['nombre'];?></span></div>
                        </div>
                        <div class="col-md-4">
                            <div class="position-relative form-group"><label for="examplePassword11" class="">Serie</label><span class="form-control"><?php echo $res['serie'];?></span></div>
                        </div>
                    </div>
                    <div class="form-row" style="margin-bottom: 10px;">
                        <div class="col-md-6">
                            <div class="position-relative form-group"><label for="exampleEmail11" class="">Tipo</label><span class="form-control"><?php echo $res['tipo'];?></span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="position-relative form-group"><label for="examplePassword11" class="">País</label><span class="form-control"><?php echo $res['pais'];?></span></div>
                        </div>
                    </div>

                    <!-- <div class="form-row" style="margin-bottom: 10px;">
                        <div class="col-md-6">
                            <div class="position-relative form-group"><label for="exampleEmail11" class="">Estado</label><span class="form-control"><?php echo $res['estado'];?></span></div>
                        </div>
                         
                    </div> -->
                </form>
                <br><br>
                <h3>Histórico de certificados</h3>
                <div class="col-md-12">
                    <div id="accordion" class="accordion-wrapper mb-3">
                        <?php
// Fetch related certificates for the sensor
$sensor_id = $res['id_sensor'];
$sql_certificates = "SELECT * FROM sensores_certificados WHERE id_sensor = '$sensor_id'";
$result_certificates = $db_cms->select_query($sql_certificates);

if (!empty($result_certificates)) {
    $i = 0;

    foreach ($result_certificates as $certificate_data) {
        $ides = $certificate_data['id_certificado'];
    $certif = $certificate_data['certificado'];
    $emision = $certificate_data['fecha_emision'];
    $vencimiento = $certificate_data['fecha_vencimiento'];
    $en_sistema_desde = date('Y-m-d', strtotime($certificate_data['fecha_registro']));


    $expand = "";
    if ($i == 0) {
        $expand = "show";
    }
    $baseurl = getBaseURL();
    $pdfPath = "templates/certificados/{$cid}/{$certif}.pdf";
            $pdfURL = $baseurl. '/' . $pdfPath;
$n=$i+1;
        echo "
        <div class='card'>
        <div id='headingOne' class='card-header' >
        <button type='button' data-toggle='collapse' data-target='#collapseOne_$ides' aria-expanded='true' aria-controls='collapseOne' class='text-left m-0 p-0 btn btn-link btn-block'>
            
            <h5 class='m-0 p-0'>$n - $certif</h5>
            </button>
    </div>
            <div data-parent='#accordion' id='collapseOne_$ides' aria-labelledby='headingOne' class='collapse $expand'>
                <div class='card-body'>
                    <div class='form-row' style='margin-bottom: 10px;'>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Fecha de emisión:</label>
                        <span class='form-control' style='font-size: 14px;'> </span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Fecha de vencimiento:</label>
                        <span class='form-control' style='font-size: 14px;' > </span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>En sistema desde:</label>
                        <span class='form-control' style='font-size: 14px;'> </span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Cargar PDF:</label>
                        <a href='index.php?module=13&page=11&s=$cid&k=0'>
                        <button class='mb-2 mr-2 btn-icon btn-square btn btn-primary form-control'>
                            <i class='fa-solid fa-cloud-arrow-up btn-icon-wrapper'></i>
                            <i class='fa-regular fa-file-pdf btn-icon-wrapper'></i>
                        </button></a>
                    </div>
                </div> </div>
                
                            ";


$certificate_id = $certificate_data['id_certificado'];
//$sql_pdf_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificate_id'";
$sql_pdf_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificate_id' ORDER BY
CASE WHEN tipo = 'Primario' THEN 1
     WHEN tipo = 'Secundario' THEN 2
     WHEN tipo = 'Vencido' THEN 3 
END,
nombre_archivo";

$result_pdf_files = $db_cms->select_query($sql_pdf_files);

  $existurl="templates/certificados/{$cid}/{$certif}.pdf";

if (!empty($result_pdf_files)) {
    foreach ($result_pdf_files as $pdf_file) {
        $pdfFileName = $pdf_file['nombre_archivo'];
        $pdfURL = "templates/certificados/{$pdf_file['id_sensor']}/$pdfFileName";
        
        // Display the PDF URL as a link
        echo "
        <div class='form-row' style='margin-bottom: 10px;'>
                         
                        <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Fecha de emisión:</label>
                        <span class='form-control' style='font-size: 14px;'>$emision</span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Fecha de vencimiento:</label>
                        <span class='form-control' style='font-size: 14px;' >$vencimiento</span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>En sistema desde:</label>
                        <span class='form-control' style='font-size: 14px;'>$en_sistema_desde</span>
                    </div>
                </div>
                        <div class='col-md-3'>
        
        <a href='$pdfURL' target='_blank'>Link Certificado</a><br></div></div>";
    }
} else if (file_exists($existurl)) {
    echo "
    <div class='form-row' style='margin-bottom: 10px;'>
                         
                        <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Fecha de emisión:</label>
                        <span class='form-control' style='font-size: 14px;'>$emision</span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>Fecha de vencimiento:</label>
                        <span class='form-control' style='font-size: 14px;' >$vencimiento</span>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='position-relative form-group'>
                        <label for='exampleEmail11'>En sistema desde:</label>
                        <span class='form-control' style='font-size: 14px;'>$en_sistema_desde</span>
                    </div>
                </div>
    
    <div class='col-md-3'><a href='$existurl' target='_blank'>Link Certificado</a><br></div></div> ";
}
else {
    echo "No hay archivos PDF asociados.";
}
 




                            
        //                     // Fetch associated PDF files for this certificate
        // $certificate_id = $certificate_data['id_certificado'];
        // $sql_pdf_files = "SELECT * FROM sensores_certificados WHERE id_certificado = '$certificate_id'";
        // $result_pdf_files = $db_cms->select_query($sql_pdf_files);

        // if (!empty($result_pdf_files)) {
             

        //     foreach ($result_pdf_files as $pdf_file) {
        //         // Display the PDF URL as a link
        
        // echo "<a href='$pdfURL' target='_blank'>Link Certificado</a><br>";

        //     }
        // } else {
        //     echo "No associated PDF files.";
        // }
        echo "         
                   
                </div>
            </div>
        </div>";

        $i++;
    }
} else {
    echo "<div class='card'>
            <div id='headingOne' class='card-header'>
                <button type='button' data-toggle='collapse' data-target='#collapseOne1' aria-expanded='true' aria-controls='collapseOne' class='text-left m-0 p-0 btn btn-link btn-block'>
                    <h5 class='m-0 p-0'>Sin certificados</h5>
                </button>
            </div>
            <div data-parent='#accordion' id='collapseOne1' aria-labelledby='headingOne' class='collapse show'>
                <div class='card-body'>1. Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa
                </div>
            </div>
        </div>";
}
                        ?>
                    </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-sm-5">
    <div class="card">
        <div class="card-header">
            <h2>Audit Log</h2>
        </div>
        <div class="card-body">
            <div id="accordion2">
                <!-- Data will be dynamically loaded here -->
            </div>
            <div id="pagination2">
                <!-- Pagination links will be dynamically loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        loadPage(1); // Load initial page on page load

        // Function to load data and pagination links
        function loadPage(page) {
            $.ajax({
                url: 'templates/metrologia/includes/get_audit_log.php',
                type: 'GET',
                data: { page: page },
                success: function (data) {
                    $('#accordion2').html(data.entries);
                    $('#pagination2').html(data.pagination);
                },
                error: function () {
                    alert('Error loading data.');
                }
            });
        }

        // Attach event listener to accordion buttons
        $(document).on('click', '.accordion-button', function () {
            $(this).toggleClass('collapsed');
            var target = $(this).attr('data-bs-target');
            $(target).toggleClass('show');
        });

        // Attach event listener to pagination links
        $(document).on('click', '#pagination2 a', function (e) {
            e.preventDefault();
            var page = $(this).attr('data-page');
            loadPage(page);
        });
    });
</script>



</div></div>
    </div>
</div>
</div>

<style>
/* Style for accordion entries */
.accordion1 {
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.accordion-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 10px 15px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.accordion-button {
    color: #333;
    font-weight: 600;
}

.accordion-body {
    padding: 15px;
}

/* Style for pagination links */
#pagination2 {
    margin-top: 20px;
}

.page-item {
    display: inline-block;
    margin-right: 5px;
}

.page-link {
    color: #007bff;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 5px 10px;
    border-radius: 0.25rem;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.page-link:hover {
    background-color: #e9ecef;
}

.page-item.active .page-link {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
}

    </style>