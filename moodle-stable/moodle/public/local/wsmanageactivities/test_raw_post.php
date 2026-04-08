<?php
// TESTE ISOLADO - SEM MOODLE
echo "<h1>Teste de Receção de Dados</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>DADOS RECEBIDOS!</h2>";
    echo "<pre>POST: " . print_r($_POST, true) . "</pre>";
    echo "<pre>FILES: " . print_r($_FILES, true) . "</pre>";
    exit;
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="teste_campo" value="Funciona">
    <input type="file" name="teste_ficheiro">
    <button type="submit">SUBMETER TESTE BRUTO</button>
</form>
