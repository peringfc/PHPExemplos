<!DOCTYPE html>
<html lang="en">
<head>
  <title>Atividades de Capacidade e Performance</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>


<?php
// Configurações do banco de dados
$host = '127.0.0.1';
$db = 'banco1';
$user = 'xlogin';
$pass = 'gtymOcL4o1hmQ1!p';

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Inicializar variáveis de filtro
$nome_pessoa = '';
$tipo_servico = '';
$orgao = '';
$data_inicio = '';
$data_termino = '';

// Processar formulário de filtros
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_pessoa = $_POST['nome_pessoa'];
    $tipo_servico = $_POST['tipo_servico'];
    $orgao = $_POST['orgao'];
    $data_inicio = $_POST['data_inicio'];
    $data_termino = $_POST['data_termino'];

    // Construir query com filtros
    $query = "SELECT id_atividade, id_chamado, Nome_Pessoa, Orgao, titulo, tipo_servico, data_inicio, data_termino, data_estimada, status
              FROM atividades WHERE 1=1";

    if (!empty($nome_pessoa)) {
        $query .= " AND Nome_Pessoa = '" . $conn->real_escape_string($nome_pessoa) . "'";
    }

    if (!empty($tipo_servico)) {
        $query .= " AND tipo_servico = '" . $conn->real_escape_string($tipo_servico) . "'";
    }

    if (!empty($orgao)) {
        $query .= " AND Orgao = '" . $conn->real_escape_string($orgao) . "'";
    }

    if (!empty($data_inicio) && !empty($data_termino)) {
        $query .= " AND data_inicio BETWEEN '" . $conn->real_escape_string($data_inicio) . "' AND '" . $conn->real_escape_string($data_termino) . "'";
    }

    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Filtrar Atividades</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>

    <h2>Filtrar Atividades</h2>
    <form method="post" action="">
	
	<table class="table table-bordered">
	  <tr>
	  <td>
        <label for="nome_pessoa">Nome Pessoa:</label>
        <select name="nome_pessoa" id="nome_pessoa">
            <option value="">Todos</option>
            <?php
            $pessoas = $conn->query("SELECT DISTINCT Nome_Pessoa FROM atividades");
            while ($row = $pessoas->fetch_assoc()) {
                $selected = $row['Nome_Pessoa'] === $nome_pessoa ? 'selected' : '';
                echo "<option value='{$row['Nome_Pessoa']}' $selected>{$row['Nome_Pessoa']}</option>";
            }
            ?>
        </select><br><br>
      </td>
      <td>
        <label for="tipo_servico">Tipo Serviço:</label>
        <select name="tipo_servico" id="tipo_servico">
            <option value="">Todos</option>
            <?php
            $tipos = $conn->query("SELECT DISTINCT tipo_servico FROM atividades");
            while ($row = $tipos->fetch_assoc()) {
                $selected = $row['tipo_servico'] === $tipo_servico ? 'selected' : '';
                echo "<option value='{$row['tipo_servico']}' $selected>{$row['tipo_servico']}</option>";
            }
            ?>
        </select><br><br>
      </td>
      <td>

        <label for="orgao">Orgão:</label>
        <select name="orgao" id="orgao">
            <option value="">Todos</option>
            <?php
            $orgaos = $conn->query("SELECT DISTINCT Orgao FROM atividades");
            while ($row = $orgaos->fetch_assoc()) {
                $selected = $row['Orgao'] === $orgao ? 'selected' : '';
                echo "<option value='{$row['Orgao']}' $selected>{$row['Orgao']}</option>";
            }
            ?>
        </select><br><br>
      </td>
      <td>

        <label for="data_inicio">Data Início:</label>
        <input type="date" name="data_inicio" id="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>"><br><br>
      </td>
      <td>

        <label for="data_termino">Data Término:</label>
        <input type="date" name="data_termino" id="data_termino" value="<?php echo htmlspecialchars($data_termino); ?>"><br><br>
      </td>
      <td>
        <button type="submit" class="btn btn-warning">Aplicar Filtros</button>
</tr>
</table>

    </form>

<?php if (isset($result) && $result->num_rows > 0): ?>
    <h2>Resultados</h2>
    <table border="1">
        <tr>
            <th>Nome Pessoa</th>
            <th>Tipo Serviço</th>
            <th>Quantidade</th>
        </tr>
        <?php
        // Inicializar dados para gráficos
        $graficoTabela = [];
        $graficoComb = [];
        $graficoPizza = [];
        $graficoTimeline = [];
        $graficoTreeMap = [];
        $graficoCombOrgaoTitulo = [];
        $graficoGantt = [];

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['Nome_Pessoa']}</td>
                    <td>{$row['tipo_servico']}</td>
                    <td>1</td>
                  </tr>";

            // Dados para o gráfico de tabela
            $graficoTabela[] = [
                'Nome_Pessoa' => $row['Nome_Pessoa'],
                'Tipo_Servico' => $row['tipo_servico'],
                'Quantidade' => 1
            ];

            // Dados para o gráfico de pizza
            $key = $row['Nome_Pessoa'] . " - " . $row['status'] . " - " . $row['tipo_servico'];
            if (!isset($graficoPizza[$key])) {
                $graficoPizza[$key] = 0;
            }
            $graficoPizza[$key]++;

            // Dados para gráfico de linha (cronograma)
            $graficoTimeline[] = [
                'Orgao' => $row['Orgao'],
                'data_inicio' => $row['data_inicio'],
                'data_termino' => $row['data_termino']
            ];

            // Dados para gráfico de árvore
            $graficoTreeMap[] = [
                'Nome_Pessoa' => $row['Nome_Pessoa'],
                'status' => $row['status'],
                'tipo_servico' => $row['tipo_servico'],
                'Quantidade' => 1
            ];

            // Dados para gráfico de combinação (Nome_Pessoa)
            $graficoComb[] = [
                'Nome_Pessoa' => $row['Nome_Pessoa'],
                'status' => $row['status'],
                'tipo_servico' => $row['tipo_servico'],
                'Quantidade' => 1
            ];

            // Dados para gráfico de combinação (Orgao + Título)
            $orgaoTitulo = $row['Orgao'] . " - " . $row['titulo'];
            $graficoCombOrgaoTitulo[] = [
                'Orgao_Titulo' => $orgaoTitulo,
                'status' => $row['status'],
                'tipo_servico' => $row['tipo_servico'],
                'Quantidade' => 1
            ];

            // Dados para gráfico de Gantt
            $duration = (strtotime($row['data_termino']) - strtotime($row['data_inicio'])) / 3600; // Duração em horas
            $graficoGantt[] = [
                'id_atividade' => $row['id_atividade'],
                'id_chamado' => $row['id_chamado'],
                'data_inicio' => $row['data_inicio'],
                'data_termino' => $row['data_termino'],
                'Duration' => $duration,
                'Percent_Complete' => 0, // Exemplificação, ajustar conforme necessário
                'Dependencies' => $row['id_chamado']
            ];
        }
        ?>
    </table>

    <!-- Gráfico de Tabela -->
    <div id="tablechart"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['table']});
        google.charts.setOnLoadCallback(drawTable);

        function drawTable() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Nome Pessoa');
            data.addColumn('string', 'Tipo Serviço');
            data.addColumn('number', 'Quantidade');
            
            data.addRows([
                <?php
                foreach ($graficoTabela as $linha) {
                    echo "['{$linha['Nome_Pessoa']}', '{$linha['Tipo_Servico']}', {$linha['Quantidade']}],";
                }
                ?>
            ]);

            var table = new google.visualization.Table(document.getElementById('tablechart'));
            table.draw(data, {showRowNumber: true, width: '100%', height: '100%'});
        }
    </script>

    <!-- Gráfico de Combinação (data_termino) -->
    <div id="combchart_termino"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCombChartTermino);

        function drawCombChartTermino() {
            var data = new google.visualization.DataTable();
            data.addColumn('date', 'Data Término');
            data.addColumn('number', 'Quantidade');

            data.addRows([
                <?php
                $countDataTermino = [];
                foreach ($graficoComb as $linha) {
                    $dateTerm = $linha['data_termino'];
                    if (!isset($countDataTermino[$dateTerm])) {
                        $countDataTermino[$dateTerm] = 0;
                    }
                    $countDataTermino[$dateTerm]++;
                }
                foreach ($countDataTermino as $date => $count) {
                    echo "[new Date('$date'), $count],";
                }
                ?>
            ]);

            var options = {
                title: 'Quantidade por Data de Término',
                hAxis: { title: 'Data' },
                vAxis: { title: 'Quantidade' },
                height: 400
            };

            var chart = new google.visualization.ComboChart(document.getElementById('combchart_termino'));
            chart.draw(data, options);
        }
    </script>

    <!-- Gráfico de Pizza -->
    <div id="piechart"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawPieChart);

        function drawPieChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Categoria');
            data.addColumn('number', 'Quantidade');

            data.addRows([
                <?php
                foreach ($graficoPizza as $categoria => $quantidade) {
                    echo "['$categoria', $quantidade],";
                }
                ?>
            ]);

            var options = {
                title: 'Distribuição por Categoria',
                pieSliceText: 'label',
                height: 400
            };

            var chart = new google.visualization.PieChart(document.getElementById('piechart'));
            chart.draw(data, options);
        }
    </script>

    <!-- Gráfico de Cronograma -->
    <div id="timelinechart"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['timeline']});
        google.charts.setOnLoadCallback(drawTimelineChart);

        function drawTimelineChart() {
            var dataTable = new google.visualization.DataTable();
            dataTable.addColumn('string', 'Orgao');
            dataTable.addColumn('date', 'Início');
            dataTable.addColumn('date', 'Término');

            dataTable.addRows([
                <?php
                foreach ($graficoTimeline as $linha) {
                    $startDate = date('Y, m, d', strtotime($linha['data_inicio']));
                    $endDate = date('Y, m, d', strtotime($linha['data_termino']));
                    echo "['{$linha['Orgao']}', new Date($startDate), new Date($endDate)],";
                }
                ?>
            ]);

            var container = document.getElementById('timelinechart');
            var chart = new google.visualization.Timeline(container);

            var options = {
                height: 400
            };

            chart.draw(dataTable, options);
        }
    </script>

    <!-- Gráfico de Árvore -->
    <div id="treemapchart"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['treemap']});
        google.charts.setOnLoadCallback(drawTreeMapChart);

        function drawTreeMapChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Nome_Pessoa');
            data.addColumn('string', 'Tipo_Servico');
            data.addColumn('number', 'Quantidade');

            data.addRows([
                <?php
                foreach ($graficoTreeMap as $linha) {
                    echo "['{$linha['Nome_Pessoa']}', '{$linha['status']}', {$linha['Quantidade']}],";
                }
                ?>
            ]);

            var options = {
                title: 'Distribuição por Nome Pessoa',
                height: 400
            };

            var chart = new google.visualization.TreeMap(document.getElementById('treemapchart'));
            chart.draw(data, options);
        }
    </script>

    <!-- Gráfico de Combinação (Nome_Pessoa) -->
    <div id="combchart_person"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCombChartPerson);

        function drawCombChartPerson() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Nome Pessoa');
            data.addColumn('string', 'Status');
            data.addColumn('string', 'Tipo Serviço');
            data.addColumn('number', 'Quantidade');

            data.addRows([
                <?php
                foreach ($graficoComb as $linha) {
                    echo "['{$linha['Nome_Pessoa']}', '{$linha['status']}', '{$linha['tipo_servico']}', {$linha['Quantidade']}],";
                }
                ?>
            ]);

            var options = {
                title: 'Combinação por Nome Pessoa',
                height: 400,
                seriesType: 'bars'
            };

            var chart = new google.visualization.ComboChart(document.getElementById('combchart_person'));
            chart.draw(data, options);
        }
    </script>

    <!-- Gráfico de Combinação (Orgao + Título) -->
    <div id="combchart_orgao_title"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawCombChartOrgaoTitle);

        function drawCombChartOrgaoTitle() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Orgao + Título');
            data.addColumn('string', 'Status');
            data.addColumn('string', 'Tipo Serviço');
            data.addColumn('number', 'Quantidade');

            data.addRows([
                <?php
                foreach ($graficoCombOrgaoTitulo as $linha) {
                    echo "['{$linha['Orgao_Titulo']}', '{$linha['status']}', '{$linha['tipo_servico']}', {$linha['Quantidade']}],";
                }
                ?>
            ]);

            var options = {
                title: 'Combinação por Orgao + Título',
                height: 400,
                seriesType: 'bars'
            };

            var chart = new google.visualization.ComboChart(document.getElementById('combchart_orgao_title'));
            chart.draw(data, options);
        }
    </script>

    <!-- Gráfico de Gantt -->
    <div id="ganttchart"></div>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['gantt']});
        google.charts.setOnLoadCallback(drawGanttChart);

        function drawGanttChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'ID Atividade');
            data.addColumn('string', 'Nome Atividade');
            data.addColumn('date', 'Data Início');
            data.addColumn('date', 'Data Término');
            data.addColumn('number', 'Duração (horas)');
            data.addColumn('number', 'Percentual Completo');
            data.addColumn('string', 'Dependências');

            data.addRows([
                <?php
                foreach ($graficoGantt as $linha) {
                    $startDate = date('Y, m, d', strtotime($linha['data_inicio']));
                    $endDate = date('Y, m, d', strtotime($linha['data_termino']));
                    echo "['{$linha['id_atividade']}', '{$linha['id_chamado']}', new Date($startDate), new Date($endDate), {$linha['Duration']}, {$linha['Percent_Complete']}, '{$linha['Dependencies']}'],";
                }
                ?>
            ]);

            var options = {
                height: 400,
                gantt: { trackHeight: 30 }
            };

            var chart = new google.visualization.Gantt(document.getElementById('ganttchart'));
            chart.draw(data, options);
        }
    </script>

<?php endif; ?>

<?php
// Fechar conexão
$conn->close();
?>

</body>
</html>
