<?php
// Configurações de conexão com o banco de dados
$host = 'localhost'; // Altere para o host do seu banco de dados
$dbname = 'crm_teste'; // Nome do schema
$username = 'root'; // Usuário do banco de dados
$password = ''; // Senha do banco de dados


// Conexão com o banco de dados usando mysqli
$mysqli = new mysqli($host, $username, $password, $dbname);

// Verifica a conexão
if ($mysqli->connect_error) {
    die('Erro de Conexão (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Consulta SQL
$query = "
SELECT activityid,
       subject,
       activitytype,
       date_start,
       due_date,
       time_start,
       time_end,
       sendnotification,
       duration_hours,
       duration_minutes,
       status,
       eventstatus,
       priority,
       location,
       notime,
       visibility,
       Responsavel,
       Status_user,
       Tema,
       Organizacao_Relacionada,
       Chamado_Relacionado,
       Projeto_Relacionado,
       count(1) AS Total,
       (sum(duration_hours)/60) + sum(duration_minutes) AS duration_minute
FROM (
    SELECT vtiger_activity.activityid,
           vtiger_activity.subject,
           vtiger_activity.activitytype,
           vtiger_activity.date_start,
           vtiger_activity.due_date,
           vtiger_activity.time_start,
           vtiger_activity.time_end,
           vtiger_activity.sendnotification,
           IFNULL(vtiger_activity.duration_hours, 0) AS duration_hours,
           IFNULL(vtiger_activity.duration_minutes, 0) AS duration_minutes,
           vtiger_activity.status,
           vtiger_activity.eventstatus,
           vtiger_activity.priority,
           vtiger_activity.location,
           vtiger_activity.notime,
           vtiger_activity.visibility,
           CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) AS Responsavel,
           CONCAT(vtiger_activity.activitytype, ' - ', 
		          vtiger_users.first_name, ' ', vtiger_users.last_name, ' - ', 
				  IFNULL(vtiger_activity.status, vtiger_activity.eventstatus)) AS Status_user,
           CONCAT(IFNULL(CONCAT(vtiger_account.accountname,' - Ref.:Org. '), ''), ' ',
                  IFNULL(CONCAT(vtiger_troubletickets.title,' - Ref.:Ticket '), ''), ' ',
                  IFNULL(CONCAT(vtiger_project.projectname,' - Ref.:Projeto '), '')) AS Tema,
           vtiger_account.accountname AS Organizacao_Relacionada,
           vtiger_troubletickets.title AS Chamado_Relacionado,
           vtiger_project.projectname AS Projeto_Relacionado
    FROM vtiger_activity
    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
    LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
    LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
    LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_seactivityrel.crmid
    LEFT JOIN vtiger_troubletickets ON vtiger_troubletickets.ticketid = vtiger_seactivityrel.crmid
    LEFT JOIN vtiger_project ON vtiger_project.projectid = vtiger_seactivityrel.crmid
    WHERE vtiger_crmentity.deleted = 0
    AND vtiger_activity.activitytype IN ('Task', 'Meeting', 'Call')
) AS subquery
GROUP BY activityid, subject, activitytype, date_start, due_date, time_start, time_end, status, eventstatus, priority, Responsavel, Tema
";

// Executa a query
$result = $mysqli->query($query);

// Verifica se há resultados
if ($result->num_rows > 0) {
    // Armazena os resultados em um array para uso no JavaScript
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo "Nenhum dado encontrado.";
}

// Fecha a conexão com o banco de dados
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban - CRM</title>
    <style>
        /* Estilos básicos para o Kanban */
        body {
            font-family: Arial, sans-serif;
        }
        .kanban-board {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin: 20px auto;
        }
        .kanban-column {
            width: 30%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f4f4f4;
        }
        .kanban-column h2 {
            text-align: center;
        }
        .kanban-card {
            background-color: white;
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<h1>Atividades do CRM - Kanban</h1>

<div class="kanban-board">
    <div class="kanban-column" id="task-column">
        <h2>Tarefas</h2>
        <!-- As atividades serão inseridas aqui -->
    </div>
    <div class="kanban-column" id="meeting-column">
        <h2>Reuniões</h2>
        <!-- As atividades serão inseridas aqui -->
    </div>
    <div class="kanban-column" id="call-column">
        <h2>Chamadas</h2>
        <!-- As atividades serão inseridas aqui -->
    </div>
</div>

<script type="text/javascript">
// Transforma os dados PHP em formato JSON para uso no JavaScript
var data = <?php echo json_encode($data); ?>;

// Função para criar e adicionar cartões ao Kanban
function createKanbanCard(activity) {
    var card = document.createElement("div");
    card.className = "kanban-card";
    card.innerHTML = "<strong>" + activity.subject + "</strong><br>"
        + "Responsável: " + activity.Responsavel + "<br>"
        + "Data de Início: " + activity.date_start + "<br>"
        + "Data de Término: " + activity.due_date + "<br>"
        + "Duração: " + activity.duration_minute + " minutos";
    return card;
}

// Função para classificar atividades no Kanban
function addActivitiesToKanban() {
    data.forEach(function(activity) {
        var card = createKanbanCard(activity);
        
        // Classifica as atividades de acordo com o tipo
        if (activity.activitytype === "Task") {
            document.getElementById("task-column").appendChild(card);
        } else if (activity.activitytype === "Meeting") {
            document.getElementById("meeting-column").appendChild(card);
        } else if (activity.activitytype === "Call") {
            document.getElementById("call-column").appendChild(card);
        }
    });
}

// Inicializa o Kanban
document.addEventListener("DOMContentLoaded", addActivitiesToKanban);
</script>

</body>
</html>
