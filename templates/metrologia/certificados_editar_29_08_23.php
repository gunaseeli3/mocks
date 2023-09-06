<?php
 $sensor_existid = '';
 if (isset($_REQUEST["s"]) && $_REQUEST["s"] != '') {
     $sensor_existid = $_REQUEST["s"];
     $sql3 = "SELECT * FROM sensores WHERE id_sensor='$sensor_existid'";
     $res3 = $db_cms->select_query_with_row($sql3);
 
     $mi_sensor = $res3['nombre'];
 }
 error_reporting(E_ALL);
  ini_set('display_errors', 1);
 $idcert=$_REQUEST['idcert'];
 $certificateId= $idcert;

function renameExistingFile($existingFilePath, $newFilePath) {
    return rename($existingFilePath, $newFilePath);
}

function updateDatabaseAndBacktrack($certificateId, $sensorId, $fileTypeSuffix) {
    global $db_cms;
    // Update the database and create a backtrack record
    $table_name = 'sensores_certificados';
    $field = array();
    $fields_to_check = array("id_sensor", "certificado", "fecha_emision", "fecha_vencimiento", "pais", "estado");



    foreach ($fields_to_check as $field_name) {
        if (!empty($_POST[$field_name])) {
            $field[$field_name] = $_POST[$field_name];
        }
    }

    $field['id_sensor'] = $sensorId;
    $sql3 = "SELECT * FROM sensores_certificados WHERE id_certificado = '$certificateId'";
    $res_bf = $db_cms->select_query_with_row($sql3);


    // Update the database with new file information
    $fieldToUpdate = "primary_url";
    $fileType = $_POST['certificate_type'];
    if ($fileTypeSuffix === '_2') {
        $fieldToUpdate = "sec_url";
    } elseif ($fileTypeSuffix === '_3') {
        $fieldToUpdate = "exp_url";
    }

    //$field[$fieldToUpdate] =  "templates/certificados/{$sensorId}/{$certificateId}{$fileTypeSuffix}.pdf";


    $update_condition = " WHERE id_certificado = '{$certificateId}'";
   
    $res = $db_cms->update_query_new($field, $table_name, $update_condition);
    //echo "<pre>"; print_r($field); echo "</pre>";
    if ($res !== FALSE) {
        // Construct the description for the backtrack record
        $user = isset($_COOKIE['user']) ? $_COOKIE['user'] : "";
        $action = "Modificó"; // Example action
        $date_time_action = date('Y-m-d H:i:s'); // Current date and time

        // Add more fields and values as needed
        $field1 = "Sensor ID";
        $field1_value = $_POST['id_sensor'];
        $field2 = "Nombre del certificado";
        $field2_value = $_POST['certificado'];
        $field3 = "Fecha de calibración";
        $field3_value = $_POST['fecha_emision'];
        $field4 = "Fecha de vencimiento";
        $field4_value = $_POST['fecha_vencimiento'];
        $field5 = "País de emisión";
        $field5_value = $_POST['pais'];
        $field6 = "Estado";
        $field6_value = $_POST['estado'];
        $field7 = "ID";
        $field7_value = $certificateId;
        $field8 = "página";
        $field8_value = "EDITAR CERTIFICADO";
        $url = "templates/certificados/{$field1_value}/{$field2_value}.pdf";

        $description = "$user ha $action el $date_time_action<br>"
            . "$field1 cambio de {$res_bf['id_sensor']} a $field1_value<br>"
            . "$field2 cambio de {$res_bf['certificado']} a $field2_value<br>"
            . "$field3 cambio de {$res_bf['fecha_emision']} a $field3_value<br>"
            . "$field4 cambio de {$res_bf['fecha_vencimiento']} a $field4_value<br>"
            . "$field5 cambio de {$res_bf['pais']} a $field5_value<br>"
            . "$field6 cambio de {$res_bf['estado']} a $field6_value<br>"
            . "$field7 cambio de {$res_bf['id_certificado']} a $field7_value<br>"
            . "URL - $url<br>"
            . "$field8 - $field8_value<br>";

        $description_base64 = base64_encode($description);

        $backtrack_data = array(
            'fecha' => $date_time_action,
            'persona' => $_COOKIE['myid'],
            'movimiento' => $action,
            'modulo' => "Metrologia",
            'descripcion' => $description_base64
        );

        $res_backtrack = $db_cms->add_query($backtrack_data, 'backtrack');

        if (!empty($_POST["edit_action"])) {
            $_SESSION["cms_status"] = "sucess";
            $_SESSION["cms_msg"] = "Datos modificados con éxito";
        } else {
            header('Location:' . $current_page);
            exit();
        }
    } else {
        $_SESSION["cms_status"] = "error";
        $_SESSION["cms_msg"] = "No se pudo insertar el registro en la base de datos.";
    }
}

$table_name = 'sensores_certificados';
if (!empty($_POST["edit_action"])) {
    $field = array();
    $pais = $_REQUEST['pais'];

    $sensorId = $_POST['selected_sensor_id'];
    $certificateId = $_POST['idcert']; // Assuming you have this variable defined somewhere

    $certificateName = $_POST['certificado'];
    $tipo = $_POST['tipo'];

    // Step 1: Checking Existing Combination
    $sql2 = "SELECT * FROM sensores_certificados WHERE id_sensor='$sensorId' AND certificado='$certificateName' AND id_certificado != '$certificateId'";
    $res_cnt = $db_cms->count_query($sql2);  

    if ($res_cnt > 0) {
        $_SESSION["cms_status"] = "error";
        $_SESSION["cms_msg"] = "La combinación de Sensor y certificado ya existe en la base de datos.";
        header('Location:' . $current_page);
        exit();
    }

    $field = array();
    $fields_to_check = array("id_sensor", "certificado", "fecha_emision", "fecha_vencimiento", "pais", "estado");

    foreach ($fields_to_check as $field_name) {
        if (!empty($_POST[$field_name])) {
            $field[$field_name] = $_POST[$field_name];
        }
    }
    $field['id_sensor'] = $sensorId;
    $update_condition = " WHERE id_certificado = '$certificateId'";
    $db_cms->update_query_new($field, $table_name, $update_condition);

    $originalCertificateName = $_POST['certificado_bk'];
    $certificateNameChanged = ($_POST['certificado'] !== $originalCertificateName);

    $pdfFiles = $_FILES['pdf_file']['tmp_name'];
    $fileCount = count($pdfFiles);

    if ($fileCount > 0) {
        $movedFileUrls = array();

        $uploadDir = dirname(__FILE__) . '/../../templates/certificados/' . $sensorId;
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $_SESSION["cms_status"] = "error";
                $_SESSION["cms_msg"] = "No se pudo crear la carpeta {$sensorId}.";
                header('Location:' . $current_page);
                exit();
            }
        }

         // Fetch existing files for a specific certificate from the database
     $sql_existing_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificateId' and tipo='$tipo'";
     $existing_files = $db_cms->select_query($sql_existing_files);
        
        // Step 3: Handle uploading and renaming new PDF files
        for ($i = 0; $i < $fileCount; $i++) {
            $index = ($fileCount > 1) ? "_$i" : ""; // Use index for Secundario and Vencido files

             // Determine the filename based on tipo
             if ($tipo === 'Primario') {

// Get the existing files for the certificate
$sql_existing_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificateId' ORDER BY id ASC";
$existing_files = $db_cms->select_query($sql_existing_files);

if ($existing_files !== false) {
    $next_index = 3; // Starting index for updating Secundario filenames

    foreach ($existing_files as $existing_file) {
        $existing_id = $existing_file['id'];
        $existing_nombre_archivo = $existing_file['nombre_archivo'];
        $existing_tipo = $existing_file['tipo'];

        if ($existing_tipo === 'Secundario') {
            $new_nombre_archivo = "{$certificateName}_{$next_index}.pdf";
            $next_index++;
        } elseif ($existing_tipo === 'Vencido') {
            // Find the highest index used in Secundario filenames
            $highest_secundario_index = 0;
            foreach ($existing_files as $file) {
                if ($file['tipo'] === 'Secundario') {
                    $index = intval(substr($file['nombre_archivo'], strrpos($file['nombre_archivo'], '_') + 1, -4));
                    if ($index > $highest_secundario_index) {
                        $highest_secundario_index = $index;
                    }
                }
            }
        
            // Update the Vencido filenames based on the next index after the highest Secundario index
            $new_index = $highest_secundario_index + 2;
            $new_nombre_archivo = "{$certificateName}_{$new_index}.pdf";
        } else {
            // Keep the same filename for Primario and other types
            $new_nombre_archivo = $existing_nombre_archivo;
        }

        // Update the record with the new filename and tipo
        $update_sql = "UPDATE sensores_certicados_ficheros SET nombre_archivo = '$new_nombre_archivo', tipo = '$existing_tipo' WHERE id = '$existing_id'";
        $db_cms->update_query($update_sql);
    }
}

                if ($existing_files !== false) {
                    foreach ($existing_files as $existing_file) {
                        if ($existing_file['tipo'] === 'Primario') {
                            $existingPrimaryFileName = $existing_file['nombre_archivo'];
                            $newSecondaryFileName = str_replace('.pdf', '_2.pdf', $existingPrimaryFileName);
            
                            // Rename the file in the file system
                            $oldFilePath = $uploadDir . '/' . $existingPrimaryFileName;
                            $newFilePath = $uploadDir . '/' . $newSecondaryFileName;
            
                            if (rename($oldFilePath, $newFilePath)) {
                                // Update the filename and tipo in the database
                                $update_sql = "UPDATE sensores_certicados_ficheros SET nombre_archivo = '$newSecondaryFileName', tipo = 'Secundario' WHERE id = '{$existing_file['id']}'";
                                $db_cms->update_query($update_sql);
                            }
                        }
                    }
                }
                $fileName = "{$certificateName}.pdf";
            }else if ($tipo === 'Secundario') {
                // Fetch the maximum index for Secundario files
                $sql_max_secundario_index = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre_archivo, '_', -1), '.', 1)) AS max_index FROM sensores_certicados_ficheros WHERE tipo = 'Secundario' and id_certificado='$certificateId'";
                $result_max_secundario_index = $db_cms->select_query($sql_max_secundario_index);
                $max_secundario_index = intval($result_max_secundario_index[0]['max_index']);
        
                // Calculate the next index for Secundario files
                if ($max_secundario_index == 0) {
                    $next_secundario_index = $max_secundario_index + 2;
                } else {
                    $next_secundario_index = $max_secundario_index + 1;
                }
        
                // Create the filename
                $fileName = "{$certificateName}_{$next_secundario_index}.pdf";
            } else if ($tipo === 'Vencido') {
                // Fetch the maximum index for Secundario files
                $sql_max_secundario_index = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre_archivo, '_', -1), '.', 1)) AS max_index FROM sensores_certicados_ficheros WHERE tipo = 'Secundario' and id_certificado='$certificateId'";
                $result_max_secundario_index = $db_cms->select_query($sql_max_secundario_index);
                $max_secundario_index = intval($result_max_secundario_index[0]['max_index']);
            
                // Fetch the maximum index for Vencido files
                $sql_max_vencido_index = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre_archivo, '_', -1), '.', 1)) AS max_index FROM sensores_certicados_ficheros WHERE tipo = 'Vencido' and id_certificado='$certificateId'";
                $result_max_vencido_index = $db_cms->select_query($sql_max_vencido_index);
                $max_vencido_index = intval($result_max_vencido_index[0]['max_index']);
            
                // Calculate the next index for Vencido files (starting from next number after highest Secundario index)
                $next_vencido_index = max($max_secundario_index + 1, $max_vencido_index + 1);
            
                // Create the filename
                $fileName = "{$certificateName}_{$next_vencido_index}.pdf";
            }

            $destination = $uploadDir . '/' . $fileName;

            $movefileResult = move_uploaded_file($pdfFiles[$i], $destination);

            if ($movefileResult) {
                $movedFileUrls[] = $destination;

                // Insert or update the database entry for the uploaded file
                $insert_data = array(
                    'id_sensor' => $sensorId,
                    'id_certificado' => $certificateId,
                    'nombre_archivo' => $fileName,
                    'tipo' => $tipo
                );

                $db_cms->add_query1($insert_data, 'sensores_certicados_ficheros');
            }
        }
    }
}
 




 $sql="SELECT * FROM sensores_certificados WHERE id_certificado='$idcert'"; 
 $res=$db_cms->select_query_with_row($sql);

require_once 'includes/sensorlists.php';

?>
   <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                  <h4>CONFIGURACION DEL SENSOR <?php echo $mi_sensor; ?></h4>  
                 
                <div class="btn-actions-pane-right">
                
                    <a href="index.php?module=13&page=4&s=-" class="mb-2 mr-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-danger"><i class="fa-solid fa-x"></i> Cancelar</a>
                    <a href="index.php?module=13&page=11&s=<?php echo $sensor_existid; ?>&k=0" class="mb-2 mr-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-primary"><i class="fa-solid fa-cloud-arrow-up btn-icon-wrapper"></i> Carga masiva</a>
                   

                </div>
            </div>
            <div class="card-body">
            <div class="err">
            <?php
if (!empty($_SESSION["cms_status"]) && !empty($_SESSION["cms_msg"])) {
    $statusClass = ($_SESSION["cms_status"] === 'error') ? 'error' : 'success';
    ?>
    <div class="status_msg_<?php echo $statusClass; ?>">
        <?php
        echo $_SESSION["cms_msg"];
        ?>
    </div>
    <?php
    unset($_SESSION["cms_status"]);
    unset($_SESSION["cms_msg"]);
}

$baseurl = getBaseURL();
    $pdfPath = "templates/certificados/{$sensor_existid}/{$res['certificado']}.pdf";
            $pdfURL = $baseurl. '/' . $pdfPath;
?>

                        </div>
            <form method="post" enctype="multipart/form-data" id="form2" name="form2" onsubmit="return validateForm()">
                <div class="form-row" style="margin-bottom: 10px;">
                        <div class="col-md-8">
                            <div class="position-relative form-group"><label for="exampleEmail11" class="">Nombre del Sensor</label>
                            <input type="hidden" id="selected_sensor_id" name="selected_sensor_id" value="<?php echo $sensor_existid?>">
                            <input  type="text" id="sensor_dropdown" name="id_sensor" list="sensor_list" placeholder="Search for a sensor" required class="form-control" value="<?php echo $mi_sensor?>">
                            <datalist id="sensor_list"></datalist>

                        </div>
                        </div>                        
                    </div>
                    <div class="form-row" style="margin-bottom: 10px;">
                        <div class="col-md-4">
                            <div class="position-relative form-group">
                                <label for="exampleEmail11" class="">Nombre del certificado</label>
                                <input type="text" class="form-control" name="certificado" id="certificado" required value="<?php echo $res['certificado'];?>">
                                <input type="hidden" class="form-control" name="certificado_bk" id="certificado_bk"  value="<?php echo $res['certificado'];?>"></div>
                        
                        </div>
                        <div class="col-md-4">
                            <div class="position-relative form-group"><label for="examplePassword11" class="">Fecha de calibración</label><input type="date" class="form-control" name="fecha_emision"  value="<?php echo $res['fecha_emision'];?>"   required>
</div>
                        </div>
                        <div class="col-md-4">
                            <div class="position-relative form-group">
                                <label for="examplePassword11" class="">Fecha de vencimiento</label><input type="date" class="form-control" name="fecha_vencimiento"  value="<?php echo $res['fecha_vencimiento'];?>"   required>
                            </div>
                        </div>
                    </div>
                    <div class="form-row" style="margin-bottom: 10px;">
                        <div class="col-md-4">
                            <div class="position-relative form-group"><label for="exampleEmail11" class="">País de emisión</label><input type="text" class="form-control" name="pais" value="<?php echo $res['pais'];?>"   required></div>
                        </div>
                        <div class="col-md-4">
                            <div class="position-relative form-group">
                                <label for="examplePassword11" class="" >Estado</label>
                                <select class="form-control" name="estado" required>
                                    <option value="Vigente">Vigente</option>
                                    <option value="Vencido">Vencido</option>
                                </select>
                            </div>
                        </div>
<?php
           

            
               $existurl="templates/certificados/{$sensor_existid}/{$res['certificado']}.pdf";

              ?>
            

                        <div class="col-md-4">
                            <div class="position-relative form-group"><label for="File" class="">Archivo del certificado</label>
 
                            <input type="file" class="form-control" name="pdf_file[]" id="pdf_file" multiple accept=".pdf" <?php if(!file_exists($existurl)) { echo 'required'; } ?>  >
                            <div  >
    <p>Choose the type of certificate:</p>
    <input type="radio" name="tipo" value="Primario" > Primario
    <input type="radio" name="tipo" value="Secundario"> Secundario
    <input type="radio" name="tipo" value="Vencido"> Vencido
    
</div>
                            
                           
            <br>
            <?php
$sql_pdf_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$idcert'";
$result_pdf_files = $db_cms->select_query($sql_pdf_files);

if (!empty($result_pdf_files)) {
    $n = 1;
    
    // Separate the files based on tipo and store them in different arrays
    $primario_files = array();
    $secundario_files = array();
    $vencido_files = array();
    
    foreach ($result_pdf_files as $pdf_file) {
        $pdfFileName = $pdf_file['nombre_archivo'];
        $pdfURL = "templates/certificados/{$pdf_file['id_sensor']}/$pdfFileName";
        $tipo = $pdf_file['tipo']; // Get the tipo value
        
        // Store files in different arrays based on tipo
        if ($tipo === 'Primario') {
            $primario_files[] = $pdfFileName;
        } elseif ($tipo === 'Secundario') {
            $secundario_files[] = $pdfFileName;
        } elseif ($tipo === 'Vencido') {
            $vencido_files[] = $pdfFileName;
        }
    }
    
    // Display the Primario files at the top
    if (!empty($primario_files)) {
        echo "<strong>Primario</strong><br>";
        foreach ($primario_files as $pdfFileName) {
            echo "<a href='$pdfURL' target='_blank' style='margin-bottom:1px;'><i class='fa fa-file'></i> Link Certificado$n</a><br>";
            $n++;
        }
    }
    
    // Display the Secundario files
    if (!empty($secundario_files)) {
        echo "<br><strong>Secundario</strong><br>";
        foreach ($secundario_files as $pdfFileName) {
            echo "<a href='$pdfURL' target='_blank' style='margin-bottom:1px; '><i class='fa fa-file'></i> Link Certificado$n</a><br>";
            $n++;
        }
    }
    
    // Display the Vencido files
    if (!empty($vencido_files)) {
        echo "<br><strong>Vencido</strong><br>";
        foreach ($vencido_files as $pdfFileName) {
            echo "<a href='$pdfURL' target='_blank' style='margin-bottom:1px; '><i class='fa fa-file'></i> Link Certificado$n</a><br>";
            $n++;
        }
    }
}
?>


                            

 

                        </div>
                    </div>
                    <div style="text-align:center;    margin: auto;">
                    <input type="hidden" name="edit_action" value="1"/>
<input type="hidden" name="idcert" value="<?= $idcert ?>"/>

<button type="submit" name="submit_edit_action" value="Actualizar" class="mb-2 mr-2 btn-icon btn-shadow btn-outline-2x btn btn-outline-success">Actualizar</button>

                    
                    </div>
                </form>
                <br><br>
                </div>
            </div>
            
        </div>
        </div>
    </div>
    
</div>

 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script>
function validateForm() {
    var tipoGroup = document.getElementById("tipoGroup");
    var tipoInputs = tipoGroup.getElementsByTagName("input");
    
    for (var i = 0; i < tipoInputs.length; i++) {
        if (tipoInputs[i].type === "radio" && tipoInputs[i].checked) {
            return true; // At least one option is selected, allow form submission
        }
    }
    
    alert("Please choose a certificate type.");
    return false; // Prevent form submission
}


 $(document).ready(function() {
    $('#form2').submit(function(e) {
        var certificateName = $('#certificado').val();
        var originalCertificateName = $('#certificado_bk').val();
        var pdfFiles = $('#pdf_file')[0].files;
        
        // Check if certificate name is changed
        var certificateNameChanged = (certificateName !== originalCertificateName);
        
        // Check if PDF file is uploaded
        var pdfFileUploaded = (pdfFiles.length > 0);
        
        if (certificateNameChanged || pdfFileUploaded) {
            // If certificate name changed or PDF uploaded, handle the logic
            
            if (certificateNameChanged && !pdfFileUploaded) {
                // Certificate name changed, enforce PDF upload
                alert("Cargue el nuevo archivo PDF para el certificado.");
                return false;
            }  
            
        }
        if (!$('input[name="tipo"]:checked').length) {
                        alert("Seleccione el tipo de certificado.");
                        return false;
                    }
                    return true;
    });
    $('input[name="tipo"]').on('change', function() {
    var fileInput = $('#pdf_file');
    if ($(this).val() === 'Primario') {
        fileInput.attr('multiple', false);
        fileInput.attr('accept', '.pdf');
    } else {
        fileInput.attr('multiple', true);
        fileInput.attr('accept', '.pdf');
    }
});
});
</script>



<!-- Similar structure for secondary and historical alerts -->


<style>
 

.modal-backdrop.show, .show.blockOverlay, .modal-backdrop, .blockOverlay {   
    position: inherit;
}
.modal {
     
    top: 105px;
    padding-top: 15px;
}

 
  </style>

 
 

   