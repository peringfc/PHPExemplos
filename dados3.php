
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Cronograma</title>
 <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

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
       ROUND((SUM(duration_hours)/60) + SUM(duration_minutes), 2) AS minutes,
       IFNULL(Responsavel, ' ') AS Responsavel,
       status_c, count(1) TotalRows
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
if (!empty($responsavel)){$query_tasks .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) = '$responsavel'";}
if (!empty($activitytype)) {$query_tasks .= " AND vtiger_activity.activitytype = '$activitytype'";}
if (!empty($date_start) && !empty($date_end)) { $query_tasks .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'"; }
$query_tasks .= ") AS tb GROUP BY activitytype, Responsavel, status_c ORDER BY Responsavel, status_c,activitytype";


// Query para o Totais
$query_total = "SELECT count(DISTINCT(activitytype)) activitytype,
       count(DISTINCT(Tema)) tema,
	   min(date_start) as date_start_i, max(date_start) as date_start_e,
	   min(due_date) as due_date_i, max(due_date) as due_date_e,
       ROUND((SUM(duration_hours)/60) + SUM(duration_minutes), 2) AS minutes,
       ROUND(ROUND((SUM(duration_hours)/60) + SUM(duration_minutes), 2)/60, 2) AS hours,	   
       count(DISTINCT(date_start)) TotalRows,
       count(1) TotalProg
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
    WHERE vtiger_crmentity.deleted = 0 
";
if (!empty($responsavel)){$query_total .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) = '$responsavel'";}
if (!empty($activitytype)) {$query_total .= " AND vtiger_activity.activitytype = '$activitytype'";}
if (!empty($date_start) && !empty($date_end)) { $query_total .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'"; }
$query_total .= ") AS tb";



$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gráficos e Filtros</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
    </script>
</head>
<body>
    <form method="post" action="">

  	    <div class="alert .alert-success">
	    <h4>Atividades</h4>		
        <label for="responsavel">Responsável:</label>
        <select name="responsavel" id="responsavel">
            <option value="">-- Todos --</option>
            <?php foreach ($responsavelOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option['name']); ?>" <?php echo isset($_POST['responsavel']) && $_POST['responsavel'] === $option['name'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($option['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="activitytype">Tipo de Atividade:</label>
        <select name="activitytype" id="activitytype">
            <option value="">-- Todos --</option>
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
        <button type="submit" class="btn btn-warning">Filtrar</button>
		</div>
    </form>
	<div class="alert .alert-success">
		<div class="row">
		<?php 
		
				$mysqlit = new mysqli($host, $user, $pass, $db);
				$resultt = $mysqlit->query($query_total);
				// Monta a tabela HTML para exibir os resultados
				if ($resultt->num_rows > 0) {
				while($row = $resultt->fetch_assoc()) {
					echo "<div class='col-lg-1 col-12'>
							<div class='small-box bg-secondary'>
								<div class='inner'>
									 <center>
									 </br>
										<h4 style='color: white;'>" . htmlspecialchars($row["TotalProg"]) ."</h4>								
										<p style='color: white;'>Tipo Programadas</p>
									 <center>
								</div>
								<div class='icon'><i class='ion ion-bag'></i></div>
							</div>
						</div>
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-secondary'>
								<div class='inner'>
									 <center>
									 </br>
										<h4 style='color: white;'>" . htmlspecialchars($row["activitytype"]) ."</h4>								
										<p style='color: white;'>Tipo Atividades</p>
									 <center>
								</div>
								<div class='icon'><i class='ion ion-bag'></i></div>
							</div>
						</div>
					
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-danger'>
								<div class='inner'>
									 <center>
									 </br>
										<h4 style='color: white;'>" . htmlspecialchars($row["tema"]) ."</h4>																		 
										<p style='color: white;'>Assunto(s)</p>
									 <center>
								</div>
								<div class='icon'><i class='ion ion-warning'></i></div>
							</div>
						</div>
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-warning'>
								<div class='inner'>
									 <center>
									 </br>
										<h4 style='color: white;'>" . htmlspecialchars($row["hours"]) ."</h4>																		 
										<p style='color: white;'>Horas</p>
									 <center>
								</div>
								<div class='icon'><i class='ion ion-warning'></i></div>
							</div>
						</div>
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-warning'>
								<div class='inner'>
									 <center>
									 </br>
										<h4 style='color: white;'>" . htmlspecialchars($row["minutes"]) ."</h4>																		 
										<p style='color: white;'>Minuto(s)</p>
									 <center>
								</div>
								<div class='icon'><i class='ion ion-warning'></i></div>
							</div>
						</div>
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-warning'>
								<div class='inner'>
									 <center>
										</br>
										<h4 style='color: white;'>" . htmlspecialchars($row["TotalRows"]) ."</h4>																		 
										<p style='color: white;'>Dia(s)</p>
									 <center>
								</div>
								<div class='icon'><i class='ion ion-bag'></i></div>
							</div>
						</div>
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-info'>
								<div class='inner'>
									 <center>
										<p style='color: white;'>Inicio entre</p>											
										<b style='color: white;'>" . htmlspecialchars($row["date_start_i"]) ."</b></br>
										<b style='color: white;'>" . htmlspecialchars($row["date_start_e"]) ."</b></br>																	 											
									 <center>
								</div>
								<div class='icon'><i class='ion ion-bag'></i></div>
							</div>
						</div>
						<div class='col-lg-1 col-12'>
							<div class='small-box bg-success'>
								<div class='inner'>
									 <center>
										<p style='color: white;'>Termino entre</p>
										<b style='color: white;'>" . htmlspecialchars($row["due_date_i"]) ."</b></br>
										<b style='color: white;'>" . htmlspecialchars($row["due_date_e"]) ."</b></br>																		 											
									 <center>
								</div>
								<div class='icon'><i class='ion ion-bag'></i></div>
							</div>
						</div>
						
						";
				}
				} else {
				echo "Nenhum resultado encontrado.";
				}
		?>
		
		</div>
	</div>			
	<div class="container mt-1">
	   <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#ListTask">Lista Atividades</button>
	   <div id="ListTask" class="collapse">
	 <?php 
			$mysqlix = new mysqli($host, $user, $pass, $db);
			$resultX = $mysqlix->query($query_tasks);
			// Monta a tabela HTML para exibir os resultados
			if ($resultX->num_rows > 0) {
			echo "<table  class='table table-bordered'>
					<thead class='table-dark'>
					<tr><center>
						<th><center>Responsável</center></th>
						<th><center>Atividades</center></th>
						<th><center>Tipo</center></th>
						<th><center>Minutos</center></th>
						<th><center>Status</center></th>
					</tr>
					</thead> ";
					 

			// Itera sobre os resultados da query
			while($row = $resultX->fetch_assoc()) {
				echo "<tr>
						<td>" . htmlspecialchars($row["Responsavel"]) . "</td>
						<td><center>" . htmlspecialchars($row["TotalRows"]) . "</center></td>					
						<td><center>" . htmlspecialchars($row["activitytype"]) . "</center></td>
						<td><center>" . htmlspecialchars($row["minutes"]) . "<center></td>" ;
				
							if ($row["status_c"] == "Completed") {
								echo "<td class='table-success'>". htmlspecialchars($row["status_c"]) . "</td>";
							} elseif ($row["status_c"] == "In Progress") {
								echo "<td class='table-warning'><center><b>". htmlspecialchars($row["status_c"]) . "</center></b></td>";
							} elseif ($row["status_c"] == "Not Started") {
								echo "<td class='table-danger'><center><b>". htmlspecialchars($row["status_c"]) . "</b></center></td>";
							} else {
								// Caso não seja nenhum desses, exibe o status como texto
								echo "<td><center>". htmlspecialchars($row["status_c"]) . "</center></td>";
							}
					  echo "</td></tr>";
			}
			echo "</table>";
			} else {
			echo "Nenhum resultado encontrado.";
			}
	?>
	  </div>
	</div>
  
	<?php
		// Executa a query

		$mysqli = new mysqli($host, $user, $pass, $db);
		
		$result = $mysqli->query($query);


		// Checa se há resultados
		if ($result->num_rows > 0) {
			// Início do bloco de script do Google Charts
			echo '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
			echo '<script type="text/javascript">';
			echo 'google.charts.load("current", {"packages":["timeline"]});';
			echo 'google.charts.setOnLoadCallback(drawChart);';
			echo 'function drawChart() {';
			echo 'var container = document.getElementById("timeline");';
			echo 'var chart = new google.visualization.Timeline(container);';
			echo 'var dataTable = new google.visualization.DataTable();';

			// Definindo colunas conforme o de-para
			echo "dataTable.addColumn({ type: 'string', id: 'ActivityID' });";
			echo "dataTable.addColumn({ type: 'string', id: 'Tema' });";
			echo "dataTable.addColumn({ type: 'date', id: 'Start' });";
			echo "dataTable.addColumn({ type: 'date', id: 'End' });";

			echo "dataTable.addRows([";
			
			// Iterando sobre os resultados e montando os dados do gráfico
			while ($row = $result->fetch_assoc()) {
				// Convertendo data para o formato adequado para o Google Charts
				$start = strtotime($row['date_start']);
				$end = strtotime($row['due_date']);
				
				// Formatando datas para o Google Charts (ano, mês-1, dia)
				echo "['" . $row['activityid'] . "', '" . htmlspecialchars($row['Tema']) . "', new Date(" . date('Y, m-1, d', $start) . "), new Date(" . date('Y, m-1, d', $end) . ")],";
			}

			echo "]);";
			echo "chart.draw(dataTable);";
			echo '}';
			echo '</script>';

			// Exibindo o container para o gráfico
			echo '<div id="timeline" style="height: 600px;"></div>';
		} else {
			echo "Nenhum resultado encontrado.";
		}

		// Fechar a conexão
		$mysqli->close();
		?>

</body>
</html>
