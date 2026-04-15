<?php
namespace local_wsmanageactivities;

defined('MOODLE_INTERNAL') || die();

class FileUploader {
    public function handle_upload_simple($form_field) {
        if (!isset($_FILES[$form_field]) || $_FILES[$form_field]['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Erro no upload.");
        }

        $tmp_name = $_FILES[$form_field]['tmp_name'];
        $original_name = $_FILES[$form_field]['name'];
        
        // Criar pasta temporária no moodledata
        $temp_dir = make_temp_directory('local_wsmanageactivities/' . uniqid());
        $dest_file = $temp_dir . '/' . $original_name;

        if (move_uploaded_file($tmp_name, $dest_file)) {
            return [
                'file_path' => $dest_file,
                'original_name' => $original_name
            ];
        }
        
        throw new \Exception("Falha ao mover ficheiro temporário.");
    }
}