<?php
$host = 'localhost';
$db = 'crm_teste';
$user = 'root';
$pass = '';

// Conectar ao banco de dados
$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Erro de conexão: ' . $mysqli->connect_error);
}

// Função para preencher as opções do combobox
function getOptions($mysqli, $query) {
    $options = [];
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }
    return $options;
}

// Consultar opções para os comboboxes
$responsavelOptions = getOptions($mysqli, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM vtiger_users");
$activitytypeOptions = getOptions($mysqli, "SELECT DISTINCT activitytype FROM vtiger_activity");
$temaOptions = getOptions($mysqli, "SELECT CONCAT(IFNULL(vtiger_account.accountname, ''), ' ', IFNULL(vtiger_troubletickets.title, ''), ' ', IFNULL(vtiger_project.projectname, '')) AS tema FROM vtiger_activity LEFT JOIN vtiger_seactivityrel ON vtiger_activity.activityid = vtiger_seactivityrel.activityid LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_seactivityrel.crmid LEFT JOIN vtiger_troubletickets ON vtiger_troubletickets.ticketid = vtiger_seactivityrel.crmid LEFT JOIN vtiger_project ON vtiger_project.projectid = vtiger_seactivityrel.crmid GROUP BY tema");

// Verificar filtros do formulário
$responsavel = isset($_POST['responsavel']) ? $mysqli->real_escape_string($_POST['responsavel']) : '';
$activitytype = isset($_POST['activitytype']) ? $mysqli->real_escape_string($_POST['activitytype']) : '';
$tema = isset($_POST['tema']) ? $mysqli->real_escape_string($_POST['tema']) : '';
$date_start = isset($_POST['date_start']) ? $mysqli->real_escape_string($_POST['date_start']) : '';
$date_end = isset($_POST['date_end']) ? $mysqli->real_escape_string($_POST['date_end']) : '';

// Construir a query principal com os filtros
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
           CONCAT(vtiger_activity.activitytype, ' - ', vtiger_users.first_name, ' ', vtiger_users.last_name, ' - ', IFNULL(vtiger_activity.status, vtiger_activity.eventstatus)) AS Status_user,
           CONCAT(IFNULL(vtiger_account.accountname, ''), ' ',
                  IFNULL(vtiger_troubletickets.title, ''), ' ',
                  IFNULL(vtiger_project.projectname, '')) AS Tema,
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
    WHERE vtiger_activity.activitytype IN ('Task', 'Meeting', 'Call')
    AND vtiger_crmentity.deleted = 0
";

if (!empty($responsavel)) {
    $query .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) = '$responsavel'";
}
if (!empty($activitytype)) {
    $query .= " AND vtiger_activity.activitytype = '$activitytype'";
}
if (!empty($tema)) {
    $query .= " AND CONCAT(IFNULL(vtiger_account.accountname, ''), ' ', IFNULL(vtiger_troubletickets.title, ''), ' ', IFNULL(vtiger_project.projectname, '')) LIKE '%$tema%'";
}
if (!empty($date_start) && !empty($date_end)) {
    $query .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'";
}

$query .= ") AS td GROUP BY activityid, subject, activitytype, date_start, due_date, time_start, time_end, sendnotification, duration_hours, duration_minutes, status, eventstatus, priority, location, notime, visibility, Responsavel, Status_user, Tema, Organizacao_Relacionada, Chamado_Relacionado, Projeto_Relacionado";

$result = $mysqli->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'Tema' => $row['Tema'],
        'date_start' => $row['date_start'],
        'due_date' => $row['due_date']
    ];
}

// Query para o gráfico de total de tarefas
$query_tasks = "
SELECT activitytype,
       ROUND((SUM(duration_hours)/60) + SUM(duration_minutes), 3) AS minutes,
       IFNULL(Responsavel, ' ') AS Responsavel,
       status_c
FROM (
    SELECT vtiger_activity.activityid,
           vtiger_activity.subject,
           vtiger_activity.activitytype,
           vtiger_activity.date_start,
           vtiger_activity.due_date,
           vtiger_activity.time_start,
           vtiger_activity.time_end,
           IFNULL(vtiger_activity.duration_hours, 0) AS duration_hours,
           IFNULL(vtiger_activity.duration_minutes, 0) AS duration_minutes,
           CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) AS Responsavel,
           IFNULL(vtiger_activity.status, vtiger_activity.eventstatus) AS status_c,
           CONCAT(IFNULL(vtiger_account.accountname, ''), ' ',
                  IFNULL(vtiger_troubletickets.title, ''), ' ',
                  IFNULL(vtiger_project.projectname, '')) AS Tema
    FROM vtiger_activity
    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
    LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
    LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
    LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_seactivityrel.crmid
    LEFT JOIN vtiger_troubletickets ON vtiger_troubletickets.ticketid = vtiger_seactivityrel.crmid
    LEFT JOIN vtiger_project ON vtiger_project.projectid = vtiger_seactivityrel.crmid
    WHERE vtiger_activity.activitytype IN ('Task', 'Meeting', 'Call')
    AND vtiger_crmentity.deleted = 0
";

if (!empty($responsavel)) {
    $query_tasks .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) = '$responsavel'";
}
if (!empty($activitytype)) {
    $query_tasks .= " AND vtiger_activity.activitytype = '$activitytype'";
}
if (!empty($date_start) && !empty($date_end)) {
    $query_tasks .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'";
}

$query_tasks .= ") AS tb GROUP BY activitytype, Responsavel, status_c ORDER BY Responsavel";

$result_tasks = $mysqli->query($query_tasks);

$tasks_data = [];
while ($row = $result_tasks->fetch_assoc()) {
    $tasks_data[] = [
        'activitytype' => $row['activitytype'],
        'minutes' => $row['minutes'],
        'status_c' => $row['status_c']
    ];
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gráficos e Filtros</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', { packages: ['timeline', 'table'] });
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            drawTimelineChart();
            drawTableChart();
        }

        function drawTimelineChart() {
            var container = document.getElementById('timeline');
            var chart = new google.visualization.Timeline(container);
            var dataTable = new google.visualization.DataTable();

            dataTable.addColumn('string', 'Tema');
            dataTable.addColumn('date', 'Início');
            dataTable.addColumn('date', 'Fim');
            dataTable.addRows([
                <?php foreach ($data as $row): ?>
                ['<?php echo addslashes($row['Tema']); ?>', new Date('<?php echo $row['date_start']; ?>'), new Date('<?php echo $row['due_date']; ?>')],
                <?php endforeach; ?>
            ]);
            var options = {
                timeline: { showRowLabels: true }
            };
            chart.draw(dataTable, options);
        }

        function drawTableChart() {
            var container = document.getElementById('table');
            var chart = new google.visualization.Table(container);
            var dataTable = new google.visualization.DataTable();
            dataTable.addColumn('string', 'Tipo de Atividade');
            dataTable.addColumn('number', 'Minutos');
            dataTable.addColumn('string', 'Responsável');
            dataTable.addColumn('string', 'Status');
            dataTable.addRows([
                <?php foreach ($tasks_data as $row): ?>
                ['<?php echo addslashes($row['activitytype']); ?>', <?php echo $row['minutes']; ?>, '<?php echo addslashes($row['status_c']); ?>'],
                <?php endforeach; ?>
            ]);
            chart.draw(dataTable, { showRowNumber: true, width: '100%', height: '100%' });
        }
    </script>
</head>
<body>
    <form method="post" action="">
        <label for="responsavel">Responsável:</label>
        <select name="responsavel" id="responsavel">
            <option value="">-- Selecione --</option>
            <?php foreach ($responsavelOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option['name']); ?>" <?php echo isset($_POST['responsavel']) && $_POST['responsavel'] === $option['name'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="activitytype">Tipo de Atividade:</label>
        <select name="activitytype" id="activitytype">
            <option value="">-- Selecione --</option>
            <?php foreach ($activitytypeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option['activitytype']); ?>" <?php echo isset($_POST['activitytype']) && $_POST['activitytype'] === $option['activitytype'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option['activitytype']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="tema">Tema:</label>
        <input type="text" name="tema" id="tema" value="<?php echo htmlspecialchars(isset($_POST['tema']) ? $_POST['tema'] : ''); ?>">
        <label for="date_start">Data Início:</label>
        <input type="date" name="date_start" id="date_start" value="<?php echo htmlspecialchars(isset($_POST['date_start']) ? $_POST['date_start'] : ''); ?>">
        <label for="date_end">Data Fim:</label>
        <input type="date" name="date_end" id="date_end" value="<?php echo htmlspecialchars(isset($_POST['date_end']) ? $_POST['date_end'] : ''); ?>">
        <button type="submit">Filtrar</button>
    </form>
    <h2>Total de Atividades: <?php echo isset($result) ? $result->num_rows : '0'; ?></h2>
    <div id="timeline" style="height: 600px; width: 100%;"></div>
    <div id="table" style="width: 100%;"></div>
</body>
</html>
