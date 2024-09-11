<?php
// Configurações da API Vtiger
$api_url = "https://seu_vtiger.com/webservice.php"; // URL da API Vtiger
$username = "seu_usuario"; // Nome de usuário do Vtiger
$access_key = "sua_senha"; // Senha de API (ou chave de acesso)

function vtiger_login($api_url, $username, $access_key) {
    // Fase 1: Solicitação do token de desafio
    $challenge_url = $api_url . "?operation=getchallenge&username=" . $username;
    $challenge_response = file_get_contents($challenge_url);
    $challenge = json_decode($challenge_response, true);

    if (!$challenge['success']) {
        die("Erro ao obter o desafio: " . $challenge['error']['message']);
    }

    // Fase 2: Gerar chave de autenticação usando o token de desafio
    $token = $challenge['result']['token'];
    $generated_key = md5($token . $access_key); // Senha encriptada

    // Fase 3: Login
    $login_url = $api_url . "?operation=login";
    $post_data = array(
        'username' => $username,
        'accessKey' => $generated_key,
    );

    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($post_data),
        ),
    );

    $context  = stream_context_create($options);
    $login_response = file_get_contents($login_url, false, $context);
    $login_result = json_decode($login_response, true);

    if (!$login_result['success']) {
        die("Erro ao efetuar login: " . $login_result['error']['message']);
    }

    // Retornar o id da sessão
    return $login_result['result']['sessionName'];
}

// Função para obter as atividades (agendas, calendários, eventos) do Vtiger
function get_activities($api_url, $session_name) {
    $query = "SELECT subject, assigned_user_id, activitytype, date_start, due_date FROM Events;";

    $params = array(
        'operation' => 'query',
        'sessionName' => $session_name,
        'query' => $query
    );

    $url = $api_url . '?' . http_build_query($params);
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// Login na API do Vtiger
$session_name = vtiger_login($api_url, $username, $access_key);

// Obter atividades
$activities = get_activities($api_url, $session_name);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Agendas, Calendários e Eventos - Vtiger</title>
</head>
<body>

<h2>Filtrar por Responsável</h2>

<!-- Combobox para filtrar pelo responsável -->
<form method="POST">
    <label for="assigned_user">Responsável:</label>
    <select name="assigned_user" id="assigned_user">
        <option value="">Todos</option>
        <?php
        // Criar combobox com responsáveis
        $responsaveis = array();
        foreach ($activities['result'] as $activity) {
            $responsavel = $activity['assigned_user_id'];
            if (!in_array($responsavel, $responsaveis)) {
                echo "<option value='" . $responsavel . "'>" . $responsavel . "</option>";
                $responsaveis[] = $responsavel;
            }
        }
        ?>
    </select>
    <input type="submit" name="filtrar" value="Filtrar">
</form>

<h2>Lista de Atividades</h2>
<table border="1">
    <thead>
        <tr>
            <th>Assunto</th>
            <th>Responsável</th>
            <th>Tipo de Atividade</th>
            <th>Data de Início</th>
            <th>Data de Término</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Aplicar o filtro, se selecionado
        if (isset($_POST['assigned_user']) && $_POST['assigned_user'] !== '') {
            $filtro_responsavel = $_POST['assigned_user'];
            $filtered_activities = array_filter($activities['result'], function($activity) use ($filtro_responsavel) {
                return $activity['assigned_user_id'] == $filtro_responsavel;
            });
        } else {
            $filtered_activities = $activities['result'];
        }

        // Exibir os dados em uma tabela
        foreach ($filtered_activities as $activity) {
            echo "<tr>";
            echo "<td>" . $activity['subject'] . "</td>";
            echo "<td>" . $activity['assigned_user_id'] . "</td>";
            echo "<td>" . $activity['activitytype'] . "</td>";
            echo "<td>" . $activity['date_start'] . "</td>";
            echo "<td>" . $activity['due_date'] . "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>
