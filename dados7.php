<?php
// Configurações do banco de dados

$host = 'localhost';
$db = 'crm_teste';
$user = 'root';
$pass = '';

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);


// Conexão com o banco de dados usando mysqli


// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
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
       count(1) Total,
       (sum(duration_hours)/60) + sum(duration_minutes) duration_minute
FROM (
    SELECT vtiger_activity.activityid,
           vtiger_activity.subject,
           vtiger_activity.activitytype,
           vtiger_activity.date_start,
           vtiger_activity.due_date,
           vtiger_activity.time_start,
           vtiger_activity.time_end,
           vtiger_activity.sendnotification,
           IFNULL(vtiger_activity.duration_hours, 0) duration_hours,
           IFNULL(vtiger_activity.duration_minutes, 0) duration_minutes,
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
) AS activity_data
GROUP BY activityid, subject, activitytype, date_start, due_date, time_start, time_end, 
         sendnotification, duration_hours, duration_minutes, status, eventstatus, 
         priority, location, notime, visibility, Responsavel, Status_user, Tema, 
         Organizacao_Relacionada, Chamado_Relacionado, Projeto_Relacionado;
";

// Executa a consulta
$result = $conn->query($query);

if (!$result) {
    die("Erro na consulta: " . $conn->error);
}

// Recupera os resultados como array associativo
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Fecha a conexão
$conn->close();

// Passa os dados para o JavaScript
$data_json = json_encode($data);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PivotGrid Example</title>
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/22.1.7/css/dx.light.css" />
    <script src="https://cdn3.devexpress.com/jslib/22.1.7/js/jquery.min.js"></script>
    <script src="https://cdn3.devexpress.com/jslib/22.1.7/js/dx.all.js"></script>
</head>
<body>
    <h1>PivotGrid Example</h1>
    <div id="pivotgrid"></div>

    <script>
        $(function() {
            // Dados recebidos do PHP
            var data = <?php echo $data_json; ?>;
            
            // Inicializa o PivotGrid
            $("#pivotgrid").dxPivotGrid({
                dataSource: {
                    fields: [
                        { dataField: "activityid", area: "row" },
                        { dataField: "subject", area: "row" },
                        { dataField: "activitytype", area: "row" },
                        { dataField: "date_start", area: "column" },
                        { dataField: "due_date", area: "column" },
                        { dataField: "duration_minute", area: "data", summaryType: "sum" }
                    ],
                    store: data
                },
                allowSortingBySummary: true,
                showBorders: true
            });
        });
    </script>
</body>
</html>
