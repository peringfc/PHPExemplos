
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Cronograma de Atividades por responsavel</title>
  <meta http-equiv='refresh' content='60'>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
 <style>
        /* Definindo o tamanho da fonte do parágrafo */
        p {
            font-size: 10px; /* Define o tamanho da fonte para 20 pixels */
			text-align: center; /* Centraliza o texto */
        }
	    h4 {
            font-size: 15px; /* Define o tamanho da fonte para 25 pixels */
			text-align: center; /* Centraliza o texto */
        }
	    h5 {
            font-size: 15px; /* Define o tamanho da fonte para 25 pixels */
			text-align: center; /* Centraliza o texto */
        }

		.table-container {
            width: 100%;
            max-height: 220px; /* Altura suficiente para exibir 10 linhas */
            overflow-y: scroll; /* Adiciona a barra de rolagem vertical */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }	
    </style>  
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
$responsavelOptions = getOptions($mysqli, "SELECT CONCAT(first_name, ' ', last_name) AS name FROM vtiger_users order by 1");
$activitytypeOptions = getOptions($mysqli, "SELECT DISTINCT activitytype FROM vtiger_activity order by 1 ");
$temaOptions = getOptions($mysqli, "SELECT DISTINCT CONCAT(IFNULL(vtiger_account.accountname, ''), ' ', IFNULL(vtiger_troubletickets.title, ''), ' ', IFNULL(vtiger_project.projectname, '')) AS tema FROM vtiger_activity LEFT JOIN vtiger_seactivityrel ON vtiger_activity.activityid = vtiger_seactivityrel.activityid LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_seactivityrel.crmid LEFT JOIN vtiger_troubletickets ON vtiger_troubletickets.ticketid = vtiger_seactivityrel.crmid LEFT JOIN vtiger_project ON vtiger_project.projectid = vtiger_seactivityrel.crmid GROUP BY tema");

// Verificar filtros do formulário
$responsavel = isset($_POST['responsavel']) ? $mysqli->real_escape_string($_POST['responsavel']) : '';
$activitytype = isset($_POST['activitytype']) ? $mysqli->real_escape_string($_POST['activitytype']) : '';
$tema = isset($_POST['tema']) ? $mysqli->real_escape_string($_POST['tema']) : '';
$date_start = isset($_POST['date_start']) ? $mysqli->real_escape_string($_POST['date_start']) : '';
$date_end = isset($_POST['date_end']) ? $mysqli->real_escape_string($_POST['date_end']) : '';

//if (empty($date_start) && empty($date_end)) {
//    $date_start = date('Y-m-01'); 
//    $date_end = date('Y-m-t'); 
//}

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
";
if (!empty($responsavel) || $responsavel !== '') 
    {
	    $query .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) = '$responsavel'";
    }
	else
	{
        $query .= " AND vtiger_users.first_name like '% %'";
	}
if (!empty($activitytype)|| $activitytype !== '') {
    $query .= " AND vtiger_activity.activitytype = '$activitytype'";
}
if (!empty($tema)|| $tema !== '') {
    $query .= " AND CONCAT(IFNULL(vtiger_account.accountname, ''), ' ', IFNULL(vtiger_troubletickets.title, ''), ' ', IFNULL(vtiger_project.projectname, '')) LIKE '%$tema%'";
}
if (!empty($date_start) && !empty($date_end)) {
    $query .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'";
}


$query .= ") AS td GROUP BY activityid, subject, activitytype, date_start, due_date, time_start, time_end, 
        sendnotification, duration_hours, duration_minutes, status, eventstatus, priority, location, notime, visibility, 
		Responsavel, Status_user, Tema, Organizacao_Relacionada, Chamado_Relacionado, Projeto_Relacionado
		order by tema ";

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
if (!empty($tema) || $tema !== ''){$query_tasks .= " AND CONCAT(IFNULL(vtiger_account.accountname, ''), ' ', IFNULL(vtiger_troubletickets.title, ''), ' ', IFNULL(vtiger_project.projectname, '')) LIKE '%$tema%'";}
if (!empty($responsavel)|| $responsavel !== ''){$query_tasks .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) = '$responsavel'";}
if (!empty($activitytype)|| $activitytype !== '') {$query_tasks .= " AND vtiger_activity.activitytype = '$activitytype'";}
if (!empty($date_start) && !empty($date_end)) { $query_tasks .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'"; }
$query_tasks .= ") AS tb GROUP BY activitytype, Responsavel, status_c 
                       ORDER BY Responsavel, status_c,activitytype";


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
if (!empty($tema) || $tema !== ''){$query_total .= " AND CONCAT(IFNULL(vtiger_account.accountname, ''), ' ', IFNULL(vtiger_troubletickets.title, ''), ' ', IFNULL(vtiger_project.projectname, '')) LIKE '%$tema%'";}

  if (!empty($responsavel) || $responsavel !== '')
	        {$query_total .= " AND CONCAT(vtiger_users.first_name, ' ', vtiger_users.last_name) like '%$responsavel%'";}
if (!empty($activitytype) || $activitytype !== '') {$query_total .= " AND vtiger_activity.activitytype = '$activitytype'";}
if (!empty($date_start) && !empty($date_end)) { $query_total .= " AND vtiger_activity.date_start BETWEEN '$date_start' AND '$date_end'"; }
$query_total .= ") AS tb";



$mysqli->close();
?>
<body>
    <form method="post" action="">
	<div class="container-fluid mt-12">
	   <h3>Atividades</h3>		
	  <div class="row">
		<div class="col-sm-2 p-1" style="width: 16%" >
			<label for="responsavel">Responsável:</label>
			<select name="responsavel" id="responsavel" style="width: 100%" >
				<option value="">Todos</option>
				<?php foreach ($responsavelOptions as $option): ?>
					<option value="<?php echo htmlspecialchars($option['name']); ?>" <?php echo isset($_POST['responsavel']) && $_POST['responsavel'] === $option['name'] ? 'selected' : ''; ?>>
						<?php echo htmlspecialchars($option['name']); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="col-sm-2 p-1" style="width: 16%" >
			<label for="activitytype">Tipo de Atividade:</label>
			<select name="activitytype" id="activitytype" style="width: 100%" >
				<option value="">Todos</option>
				<?php foreach ($activitytypeOptions as $option): ?>
					<option value="<?php echo htmlspecialchars($option['activitytype']); ?>" <?php echo isset($_POST['activitytype']) && $_POST['activitytype'] === $option['activitytype'] ? 'selected' : ''; ?>>
						<?php echo htmlspecialchars($option['activitytype']); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	
		
		<div class="col-sm-2 p-1" style="width: 16%" >
			<label for="tema">Tema ou assunto:</label>
			<input type="text" name="tema" id="tema" style="width: 100%" value="<?php echo htmlspecialchars(isset($_POST['tema']) ? $_POST['tema'] : ''); ?>">
		</div>
		
		<div class="col-sm-2 p-1" style="width: 16%" >
			<label for="date_start">Data de início:</label>
			<input type="date" name="date_start" id="date_start" value="<?php echo htmlspecialchars(isset($_POST['date_start']) ? $_POST['date_start'] : ''); ?>">
		</div>
		<div class="col-sm-2 p-1" style="width: 16%" >
			<label for="date_end">data até</label>
			<input type="date" name="date_end" id="date_end" value="<?php echo htmlspecialchars(isset($_POST['date_end']) ? $_POST['date_end'] : ''); ?>">
		</div>
		
        <div class="col-sm-2 p-1" style="width: 16%" >
			<button type="submit" class="btn btn-warning">Aplicar Filtro</button>
		</div>
		
		
	  </div>
	</div>
			 <?php $dataHoraAtual = date("d/m/Y H:i:s");?>
			 <label for="dataHora" style="font-weight: bold;">Atualizacao: </label>
			<span id="dataHora"><?php echo $dataHoraAtual; ?></span>
			 <label for="Filtro_date_start" style="font-weight: bold;"> Filtro aplicado para o Periodo inicial: </label>			
			<span id="Filtro_date_start"><?php echo $date_start; ?></span>
			<label for="Filtro_date_end" style="font-weight: bold;">Final: </label>
			<span id="Filtro_date_end"><?php echo $date_end; ?></span>

    </form>

		<div class="container-fluid mt-12" style='width: 100%' >
			<div class="row">
			<?php 
					$mysqlit = new mysqli($host, $user, $pass, $db);
					$resultt = $mysqlit->query($query_total);
					// Monta a tabela HTML para exibir os resultados
					if ($resultt->num_rows > 0) {
					while($row = $resultt->fetch_assoc()) {
						echo "<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-success' style='width: 100%' >
								 <center>
									<h4 style='color: white;'>" . htmlspecialchars($row["TotalProg"]) ."</h4>								
									<p style='color: white;'>Atividades</p>
								 </center>
								 </div>
							</div>

							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-info' style='width: 100%' >
								 <center>
									<h4 style='color: white;'>" . htmlspecialchars($row["activitytype"]) ."</h4>								
								<p style='color: white;'>Tipos</p>
								 </center>
								 </div>
							</div>
					
							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-primary' style='width: 100%' >
								 <center>
										<h4 style='color: white;'>" . htmlspecialchars($row["tema"]) ."</h4>																		 
									<p style='color: white;'>Assunto</p>
								 </center>
								 </div>
							</div>
							 
							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-warning' style='width: 100%' >
								 <center>
									<h4 style='color: white;'>" . htmlspecialchars($row["TotalRows"]) ."</h4>																		 
									<p style='color: white;'>Dia(s)</p>
								 </center>
								 </div>
							</div>

							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-secondary' style='width: 100%' >
								 <center>
										<h4 style='color: white;'>" . htmlspecialchars($row["hours"]) ."</h4>																		 
									<p style='color: white;'>Horas</p>
								 </center>
								 </div>
							</div>
							
							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-dark' style='width: 100%' >
								 <center>
										<h4 style='color: white;'>" . htmlspecialchars($row["minutes"]) ."</h4>																		 
									<p style='color: white;'>Minuto</p>
								 </center>
								 </div>
							</div>
							
										 
							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-secondary' style='width: 100%' >
								 <center>
										<p style='color: white;'>Inicio</p>											
										<h5 style='color: white;'>" . htmlspecialchars($row["date_start_i"]) ."</h5>
								 </center>
								 </div>
							</div>
							
							<div class='col-sm-2 p-1' style='width: 12%' >
								 <div class='alert bg-secondary' style='width: 100%' >
								 <center>
									<p style='color: white;'>Ultima entrega</p>
									<h5 style='color: white;'>" . htmlspecialchars($row["due_date_e"]) ."</h5>																 											
								  <center>
							</div>
							";
					}
					} else {
									    echo "<div class='alert alert-dark alert-dismissible fade show'>
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                 <strong>Not Found!</strong> Nenhum resultado encontrado.
                                </div>";

					}
			?>
			
			</div>
		</div>		

	<div class="container mt-1">
	   <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#ListTask">Atividades por responsável</button>
	   <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#ListTaskGraficao">Grafico por responsável</button>
	   <div id="ListTask" class="collapse">
	 <?php 
			$mysqlix = new mysqli($host, $user, $pass, $db);
			$resultX = $mysqlix->query($query_tasks);
			// Monta a tabela HTML para exibir os resultados
			if ($resultX->num_rows > 0) {
			echo "<table  class='table table-striped'>
					<thead class='table-dark'>
					<tr><center>
						<th><center>Responsável</center></th>
						<th><center>Atividades</center></th>
						<th><center>Tipo</center></th>
						<th><center>Minutos</center></th>
						<th><center>Status</center></th>
					</tr>
					</thead> 
                    <tbody>
     					";
					 

			// Itera sobre os resultados da query
			while($row = $resultX->fetch_assoc()) {
				echo "<tr>
						<td>" . htmlspecialchars($row["Responsavel"]) . "</td>
						<td><center>" . htmlspecialchars($row["TotalRows"]) . "</center></td>					
						<td><center>" . htmlspecialchars($row["activitytype"]) . "</center></td>
						<td><center>" . htmlspecialchars($row["minutes"]) . "<center></td>" ;
				
							if ($row["status_c"] == "Completed") {
								echo "<td><center><button type='button' class='btn btn-success'>". htmlspecialchars($row["status_c"]) . "</button></center></td>";
							} elseif ($row["status_c"] == "Planned") {
								echo "<td><center><button type='button' class='btn btn-info'>". htmlspecialchars($row["status_c"]) . "</button></center></td>";
							} elseif ($row["status_c"] == "In Progress") {
								echo "<td><center><button type='button' class='btn btn-warning'>". htmlspecialchars($row["status_c"]) . "</button></center></td>";
							} elseif ($row["status_c"] == "Not Started") {
								echo "<td><center><button type='button' class='btn btn-danger'>". htmlspecialchars($row["status_c"]) . "</button></center></td>";
							} else {
								// Caso não seja nenhum desses, exibe o status como texto
								echo "<td><center>". htmlspecialchars($row["status_c"]) . "</center></td>";
							}
					  echo "</td></tr>";
			}
			  echo " </tbody>
					 </table>
 					</div>";
			} else {
			 				    echo "<div class='alert alert-dark alert-dismissible fade show'>
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                 <strong>Not Found!</strong> Nenhum resultado encontrado.
                                </div>";

			}
			
	?>
	</div>
  
  	<div class="container mt-1">

	   <div id="ListTaskGraficao" class="collapse">
		 <?php 
				$mysqlix = new mysqli($host, $user, $pass, $db);
				$result = $mysqlix->query($query_tasks);

				// Checa se há resultados
				if ($result->num_rows > 0) {
					// Início do bloco de script do Google Charts
					echo '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
					echo '<script type="text/javascript">';
					echo 'google.charts.load("current", {"packages":["corechart"]});';
					echo 'google.charts.setOnLoadCallback(drawChart);';
					echo 'function drawChart() {';
					echo 'var data = new google.visualization.DataTable();';

					// Definindo colunas: Label e Value para o gráfico de pizza
					echo "data.addColumn('string', 'Atividade');";
					echo "data.addColumn('number', 'TotalRows');";

					// Iterando sobre os resultados e montando os dados do gráfico
					echo "data.addRows([";
					while ($row = $result->fetch_assoc()) {
						// Concatenando activitytype, Responsavel e status_c
						$label = htmlspecialchars($row['activitytype'] . " - " . $row['Responsavel'] . " - " . $row['status_c']);
						echo "['" . $label . "', " . $row['TotalRows'] . "],";
					}
					echo "]);";

					// Definições do gráfico de pizza
					echo 'var options = {';
					echo "'title': 'Distribuição de Atividades por Tipo, Responsável e Status',";
					echo "'pieHole': 0.4,"; // Para gráfico de rosca
					echo "'is3D': true"; // Deixa o gráfico em 3D (opcional)
					echo '};';

					echo 'var chart = new google.visualization.PieChart(document.getElementById("piechart"));';
					echo 'chart.draw(data, options);';
					echo '}';
					echo '</script>';

					// Exibindo o container para o gráfico
					echo '<div id="piechart" style="width:100%; height: 100%;"></div>';
				} else {
				    echo "<div class='alert alert-danger alert-dismissible fade show'>
                                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                 <strong>Not Found!</strong> Nenhum resultado encontrado.
                                </div>";

					
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
			echo "dataTable.addColumn({ type: 'string', id: 'Responsavel' });";
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
				echo "['" . $row['Tema'] . "', '" . htmlspecialchars($row['Status_user']) . "', new Date(" . date('Y, m-1, d', $start) . "), new Date(" . date('Y, m-1, d', $end) . ")],";
			}

			echo "]);";
			echo "chart.draw(dataTable);";
			echo '}';
			echo '</script>';

			// Exibindo o container para o gráfico
			echo '<div id="timeline" style="height: 600px;"></div>';
		} else {
			echo "<div class='alert alert-danger alert-dismissible fade show'>
			<button type='button' class='btn-close' data-bs-dismiss='alert'></button>
			 <strong>Not Found!</strong> Nenhum resultado encontrado.
			</div>";

		}

		// Fechar a conexão
		$mysqli->close();
		?>

</body>
</html>