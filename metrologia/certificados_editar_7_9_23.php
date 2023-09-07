<?php
 $sensor_existid = '';
 if (isset($_REQUEST["s"]) && $_REQUEST["s"] != '') {
     $sensor_existid = $_REQUEST["s"];
     $sql3 = "SELECT * FROM sensores WHERE id_sensor='$sensor_existid'";
     $res3 = $db_cms->select_query_with_row($sql3);
 
     $mi_sensor = $res3['nombre'];
 }
 //error_reporting(E_ALL);
  //ini_set('display_errors', 1);
 $idcert=$_REQUEST['idcert'];
 $certificateId= $idcert;
 $_SESSION["status"] ='';
 $_SESSION["cms_msg"] ='';

 
    function updateDatabaseAndBacktrack($certificateId, $sensorId, $tipo, $movedFileUrls) {
 
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
    $field9 = "Tipo";
    $field9_value = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $url = "URL Primary - templates/certificados/{$field1_value}/{$field2_value}.pdf";
  

    $description = "$user ha $action el $date_time_action<br>"
        . "$field1 cambio de {$res_bf['id_sensor']} a $field1_value<br>"
        . "$field2 cambio de {$res_bf['certificado']} a $field2_value<br>"
        . "$field3 cambio de {$res_bf['fecha_emision']} a $field3_value<br>"
        . "$field4 cambio de {$res_bf['fecha_vencimiento']} a $field4_value<br>"
        . "$field5 cambio de {$res_bf['pais']} a $field5_value<br>"
        . "$field6 cambio de {$res_bf['estado']} a $field6_value<br>"
        . "$field7 cambio de {$res_bf['id_certificado']} a $field7_value<br>"
        . "$field9 : $field9_value<br>"
        . "Archivos cargados:<br>" . implode('<br>', $movedFileUrls) . "<br>"
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

    if ($res_backtrack) {
        $_SESSION["cms_status"] = "success";
        $_SESSION["cms_msg"] = "Datos modificados con éxito";
    } else {
        header('Location:' . $current_page);
        exit();
    }
}



$table_name = 'sensores_certificados';
if (!empty($_POST["edit_action"])) {  

    $field = array();
    $pais = $_REQUEST['pais'];
    $_SESSION["cms_status"]='';

    $sensorId = $_POST['selected_sensor_id'];
    $certificateId = $_POST['idcert']; // Assuming you have this variable defined somewhere

    $certificateName = $_POST['certificado'];
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'Primario';




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
     $resl=$db_cms->update_query_new($field, $table_name, $update_condition);  

    $originalCertificateName = $_POST['certificado_bk'];
    $certificateNameChanged = ($_POST['certificado'] !== $originalCertificateName);

     // $pdfFiles = $_FILES['pdf_file']['tmp_name']; 
      $pdfFiles = array_filter($_FILES['pdf_file']['tmp_name']);
      $movedFileUrls = array();
     
   

    if (!empty($pdfFiles)) {  
       
          $fileCount = count($pdfFiles);
         
    if ($fileCount > 0) {
        

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
          $sql_existing_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificateId' ";
     $existing_files = $db_cms->select_query($sql_existing_files);
      $existing_files_cnt = $db_cms->count_query($sql_existing_files); 


      // Check if the certificate name is changed
    if ($certificateNameChanged) {
        if ($existing_files_cnt>0) {
        // Rename all associated PDF files based on the new certificate name
        foreach ($existing_files as $existing_file) {
            $existingFileName = $existing_file['nombre_archivo'];
            $existingTipo = $existing_file['tipo'];
        
            // Extract the index from the existing filename
            $index = 1; // Default index if not found
            if (preg_match('/_(\d+)\.pdf$/', $existingFileName, $matches)) {
                $index = intval($matches[1]);
            }
        
            // Determine the new filename based on tipo and index
            if ($existingTipo === 'Primario') {
                $newFileName = "{$certificateName}.pdf";
            } else {
                $newFileName = "{$certificateName}_{$index}.pdf";
            }
        
            // Rename the file in the file system
            $oldFilePath = $uploadDir . '/' . $existingFileName;
            $newFilePath = $uploadDir . '/' . $newFileName;
        
            if (rename($oldFilePath, $newFilePath)) {
                // Update the filename in the database
                $existingId = $existing_file['id'];
                $update_sql = "UPDATE sensores_certicados_ficheros SET nombre_archivo = '$newFileName' WHERE id = '$existingId'";
                $db_cms->update_query($update_sql);
            } else {
                // Handle error if rename fails
              //  echo "Error renaming file: $existingFileName";
            }
        }
    }
    }
         
        // Step 3: Handle uploading and renaming new PDF files
        for ($i = 0; $i < $fileCount; $i++) {
            $index = ($fileCount > 1) ? "_$i" : ""; // Use index for Secundario and Vencido files

             // Determine the filename based on tipo
             if ($tipo === 'Primario') {
        // Determine the new filename based on tipo and index
        $newFileName = "{$certificateName}.pdf";
     

// Get the existing files for the certificate
$sql_existing_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificateId' ORDER BY id ASC";
$existing_files = $db_cms->select_query($sql_existing_files);

if ($existing_files !== false) {
    //$next_index = 2; // Starting index for updating Secundario filenames

  

     // Fetch the maximum index for Secundario files
     $sql_max_secundario_index = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre_archivo, '_', -1), '.', 1)) AS max_index FROM sensores_certicados_ficheros WHERE tipo = 'Secundario' and id_certificado='$certificateId'";
     $result_max_secundario_index = $db_cms->select_query($sql_max_secundario_index);
     $max_secundario_index = intval($result_max_secundario_index[0]['max_index']);


     // Check if a primary file already exists for the certificate
    $sql_primary_file_exists = "SELECT COUNT(*) AS primary_file_count FROM sensores_certicados_ficheros WHERE tipo = 'Primario' AND id_certificado='$certificateId'";
    $result_primary_file_exists = $db_cms->select_query($sql_primary_file_exists);
    $primary_file_count = intval($result_primary_file_exists[0]['primary_file_count']);


    // Calculate the next index for Secundario files
    if ($primary_file_count > 0) {
        if ($max_secundario_index == 0) {
            $next_index = $max_secundario_index + 2;
        } else {
            $next_index = $max_secundario_index + 1;
        }
    } else {
        if ($max_secundario_index == 0) {
            $next_index = $max_secundario_index + 2;
        } else {
            $next_index = $max_secundario_index;
        }
    }

     
 

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

        // Rename the file in the file system
        $oldFilePath = $uploadDir . '/' . $existing_nombre_archivo;
        $newFilePath = $uploadDir . '/' . $new_nombre_archivo;

        if (rename($oldFilePath, $newFilePath)) {

        // Update the record with the new filename and tipo
        $update_sql = "UPDATE sensores_certicados_ficheros SET nombre_archivo = '$new_nombre_archivo', tipo = '$existing_tipo' WHERE id = '$existing_id'";
        $db_cms->update_query($update_sql);
        }
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
            
               // Calculate the next index for Vencido files
if ($max_secundario_index == 0 && $max_vencido_index == 0) {
    $next_vencido_index = 2; // Start from 2 if no Secundario or Vencido files exist
} else {
    $next_vencido_index = max($max_secundario_index, $max_vencido_index) + 1;
}
                
                


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
            else {
                $_SESSION["cms_status"] = "error";
                $_SESSION["cms_msg"] = "Hubo un error al subir el archivo PDF.";
                 header('Location:' . $current_page);
                exit();
            }
        }

        if ($tipo === 'Secundario' ) {
            // Fetch the existing Vencido files for this certificate
    $sql_vencido_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$certificateId' AND tipo = 'Vencido'";
    $vencido_files = $db_cms->select_query($sql_vencido_files);
    
            // Fetch the maximum index for Secundario files
              $sql_max_secundario_index = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre_archivo, '_', -1), '.', 1)) AS max_index FROM sensores_certicados_ficheros WHERE tipo = 'Secundario' and id_certificado='$certificateId'";
            $result_max_secundario_index = $db_cms->select_query($sql_max_secundario_index);
            $max_secundario_index = intval($result_max_secundario_index[0]['max_index']);
            
            // Calculate the next index for Secundario files
            if ($max_secundario_index == 0) {
                $next_secundario_index = 2;
            } else {
                $next_secundario_index = $max_secundario_index + 1;
            }
        
            // Calculate the next index for Vencido files
            $sql_max_vencido_index = "SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(nombre_archivo, '_', -1), '.', 1)) AS max_index FROM sensores_certicados_ficheros WHERE tipo = 'Vencido' and id_certificado='$certificateId'";
            $result_max_vencido_index = $db_cms->select_query($sql_max_vencido_index);
            $max_vencido_index = intval($result_max_vencido_index[0]['max_index']);
        
            $next_vencido_index = $next_secundario_index;
            // Calculate the new index for Vencido files
            $new_vencido_index = $next_vencido_index ; 
                    
        
            // Update existing Vencido files if there are any
            if ($vencido_files !== false) {
                foreach ($vencido_files as $vencido_file) {
                    $existingVencidoFileName = $vencido_file['nombre_archivo'];
                    $vencido_index = intval(substr($existingVencidoFileName, strrpos($existingVencidoFileName, '_') + 1, -4));
        
                    
                    $newVencidoFileName = str_replace("_{$vencido_index}.pdf", "_{$new_vencido_index}.pdf", $existingVencidoFileName);
        
                    // Rename the file in the file system
                      $oldVencidoFilePath = $uploadDir . '/' . $existingVencidoFileName;
                      $newVencidoFilePath = $uploadDir . '/' . $newVencidoFileName;
        
                    if (rename($oldVencidoFilePath, $newVencidoFilePath)) {
                        // Update the filename in the database
                       $update_sql = "UPDATE sensores_certicados_ficheros SET nombre_archivo = '$newVencidoFileName' WHERE id = '{$vencido_file['id']}'";
                        $db_cms->update_query($update_sql);
                    }  
        
                    $new_vencido_index += 1;
                }
            }
        }


       



       
        

    }
}
if (isset($_POST['tipo'])) {
    updateDatabaseAndBacktrack($certificateId, $sensorId, $tipo,  $movedFileUrls);
} else {
    updateDatabaseAndBacktrack($certificateId, $sensorId, 'Primario',  $movedFileUrls); 
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
            <form method="post" enctype="multipart/form-data" id="form2" name="form2" >
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
 
                            <input type="file" class="form-control" name="pdf_file[]" id="pdf_file" multiple accept=".pdf"    >
                            <div  >
    <p>Elija el tipo de certificado:</p>
    <input type="radio" name="tipo" value="Primario" > Primario
    <input type="radio" name="tipo" value="Secundario"> Secundario
    <input type="radio" name="tipo" value="Vencido"> Vencido
    
</div>
                            
                           
            <br>
            <?php
$sql_pdf_files = "SELECT * FROM sensores_certicados_ficheros WHERE id_certificado = '$idcert' ORDER BY
CASE WHEN tipo = 'Primario' THEN 1
     WHEN tipo = 'Secundario' THEN 2
     WHEN tipo = 'Vencido' THEN 3 
END,
nombre_archivo";
$result_pdf_files = $db_cms->select_query($sql_pdf_files);

if (!empty($result_pdf_files)) {
    $n = 1;
    
    // Separate the files based on tipo and store them in different arrays
    $primario_files = array();
    $secundario_files = array();
    $vencido_files = array();
    //echo "<pre>"; print_r($result_pdf_files);echo "</pre>";
    
    foreach ($result_pdf_files as $pdf_file) {
        $pdfFileName = $pdf_file['nombre_archivo'];       
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
            $pdfURL = "templates/certificados/{$pdf_file['id_sensor']}/$pdfFileName";
            echo "<a href='$pdfURL' target='_blank' style='margin-bottom:1px;'><i class='fa fa-file'></i> Link Certificado$n</a><br>";
            $n++;
        }
    }
    
    // Display the Secundario files
    if (!empty($secundario_files)) {
        echo "<br><strong>Secundario</strong><br>";
        foreach ($secundario_files as $pdfFileName) {
            $pdfURL = "templates/certificados/{$pdf_file['id_sensor']}/$pdfFileName";
            echo "<a href='$pdfURL' target='_blank' style='margin-bottom:1px; '><i class='fa fa-file'></i> Link Certificado$n</a><br>";
            $n++;
        }
    }
    
    // Display the Vencido files
    if (!empty($vencido_files)) {
        echo "<br><strong>Vencido</strong><br>";
        foreach ($vencido_files as $pdfFileName) {
            $pdfURL = "templates/certificados/{$pdf_file['id_sensor']}/$pdfFileName";
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



<!-- Add this modal HTML at the end of your body tag -->
<div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Confirmar carga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">¿Está seguro de que desea actualizar este 
            
                <span id="certificateType"></span>
                certificado?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                <button type="button " class="btn btn-success" id="confirmUploadButton">Confirmar</button>
            </div>
        </div>
    </div>
</div>


 <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
 <script>
$(document).ready(function () {

    var sensorData = <?php echo json_encode($sensor_data); ?>;
        var sensorList = $("#sensor_list");

       


         // Populate the dropdown with sensor data
         sensorData.forEach(function (sensor) {
            var option = $("<option>").attr("data-value", sensor.id_sensor).text(sensor.nombre);
            sensorList.append(option);
        });


        $("#sensor_dropdown").on("input", function () {
            var searchText = $(this).val().toLowerCase();
            sensorList.find("option").each(function () {
                var optionText = $(this).text().toLowerCase();
                // Use 'nombre' for searching, but keep the value as 'id_sensor'
                $(this).toggle(optionText.indexOf(searchText) > -1);
            });
        });

        // Handle selection change to update the hidden field
        $("#sensor_dropdown").change(function () {
            var selectedValue = $(this).val();
            var selectedIdSensor = sensorList.find("option:contains('" + selectedValue + "')").attr("data-value");
            $("#selected_sensor_id").val(selectedIdSensor);
        });
    

     

    $('#form2').submit(function (e) {
         
        var certificateName = $('#certificado').val();
        var originalCertificateName = $('#certificado_bk').val();
        var pdfFiles = $('#pdf_file')[0].files;

        // Check if certificate name is changed
        var certificateNameChanged = certificateName !== originalCertificateName;

        // Check if PDF file is uploaded
        var pdfFileUploaded = pdfFiles.length > 0;

        if (certificateNameChanged || pdfFileUploaded) {
            if (certificateNameChanged && !pdfFileUploaded) {
                // Certificate name changed, enforce PDF upload
                alert("Cargue el nuevo archivo PDF para el certificado.");
                e.preventDefault();
                return;
            }
        }

        if ($('#pdf_file').val() !== '') { // Check if a PDF file is selected
    if (!$('input[name="tipo"]:checked').length) {
        alert("Seleccione el tipo de certificado.");
        return false;
    }
}

        // Show the confirmation modal
        $('#confirmationModal').modal('show');
        e.preventDefault();
    });

    $('input[name="tipo"]').on('change', function () {
        var fileInput = $('#pdf_file');
        if ($(this).val() === 'Primario') {
            fileInput.attr('multiple', false);
            fileInput.attr('accept', '.pdf');
        } else {
            fileInput.attr('multiple', true);
            fileInput.attr('accept', '.pdf');
        }
    });

    // Handle user's choice in confirmation modal
    $('#confirmUploadButton').click(function () {
        alert('Certificado primario elegido.');
        $('#form2').unbind('submit').submit(); // Allow form submission
    });

    // Reset the file input value when modal is closed
    $('#confirmationModal').on('hidden.bs.modal', function () {
        $('input[type="file"]').val('');
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

 
 

   
