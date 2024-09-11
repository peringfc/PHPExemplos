<?php
// Configurações de conexão ao banco de dados
$hostname = '10.0.1.100';
$database = 'banco1';
$username = 'xlogin';
$password = 'senhanova';

// Função para tratar e exibir erros de conexão
function mostrarErro($mensagem) {
    echo "<div style='color: red; font-weight: bold;'>Erro: $mensagem</div>";
    exit;
}

// Tentar se conectar ao banco de dados
$conn = new mysqli($hostname, $username, $password, $database);

// Verificar se houve erro na conexão
if ($conn->connect_error) {
    mostrarErro("Falha na conexão: " . $conn->connect_error);
}

// Se conectar com sucesso
echo "<div style='color: green; font-weight: bold;'>Conexão bem-sucedida ao banco de dados!</div>";

// Fechar a conexão quando terminar
$conn->close();
?>
