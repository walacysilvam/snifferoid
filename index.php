<?php
// BUSCANDO ASTEROIDES USANDO
// A API DA NASA.

function getAsteroids($date, $api_key) {
    $url = "https://api.nasa.gov/neo/rest/v1/feed";
    
    $params = http_build_query([
        "start_date" => $date,
        "end_date" => $date,
        "api_key" => $api_key
    ]);
    
    $full_url = $url . "?" . $params;
    
    // Configuração do contexto para melhor tratamento de erros
    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'timeout' => 10 // Timeout de 10 segundos
        ]
    ]);
    
    $response = @file_get_contents($full_url, false, $context);
    
    if ($response === false) {
        error_log("Erro ao acessar a API da NASA. URL: $full_url");
        return [
            'error' => "Erro ao acessar a API da NASA. Verifique sua conexão ou tente mais tarde."
        ];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erro ao decodificar JSON: " . json_last_error_msg());
        return [
            'error' => "Erro ao processar os dados da API."
        ];
    }
    
    if (isset($data['error'])) {
        error_log("Erro da API NASA: " . print_r($data['error'], true));
        return [
            'error' => "Erro da API: " . $data['error']['message'] ?? 'Erro desconhecido'
        ];
    }
    
    // Verifica se a estrutura de dados está correta
    if (!isset($data['near_earth_objects']) || !is_array($data['near_earth_objects'])) {
        error_log("Estrutura de dados inesperada: " . print_r($data, true));
        return [
            'error' => "Estrutura de dados da API inesperada."
        ];
    }
    
    
    return $data['near_earth_objects'][$date] ?? [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snifferoid</title>
    <!-- <link rel="stylesheet" href="init.css"> -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plaster&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }
        header {
            background-color: #2d343fff;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        nav ul {
            list-style-type: none;
            padding: 0;
        }
        nav ul li {
            display: inline;
            margin: 0 10px;
        }
        nav a {
            color: white;
            text-decoration: none;
        }
        main {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        footer {
            text-align: center;
            padding: 1rem;
            background-color: #f4f4f4;
            margin-top: 20px;
        }
        a {
            color: #26282bff;
            text-decoration: none;
        }
        .logo-title {
            font-family: 'Plaster', cursive;
            font-size: 2.5rem;
            margin: 0;
        }
        .error {
            color: #d9534f;
            background-color: #f2dede;
            padding: 10px;
            border-radius: 5px;
        }
        .asteroid-list {
            list-style-type: none;
            padding: 0;
        }
        .asteroid-list li {
            background: #f4f4f4;
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <header>
        <h1 class="logo-title">Snifferoid</h1>
        <p>Sniffing the asteroids.</p>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </header>
    <div id="detalhes" style="display:none; background:#fff; border:2px solid #ccc; padding:15px; margin: 20px auto; border-radius:8px; width: 70%;"></div>
    <main>
        <h2>Welcome to Snifferoid</h2>
        <p>This is a simple web application to show asteroids data from NASA's API. Enter a date to see the asteroids that were near Earth on that day.<br>Note: The data is fetched from NASA's API, and the results may vary based on the date selected.</p>
        <form method="get" action="">
            <label for="date"><strong>Select a date:</strong></label>
            <input type="date" id="date" name="date" 
                   value="<?php echo htmlspecialchars($_GET['date'] ?? date('Y-m-d')); ?>"
                   max="<?php echo date('Y-m-d'); ?>">
            <button type="submit">Search Asteroids</button>
        </form>
        
        <?php
        // Usando a data do formulário ou a data atual
        $date = $_GET['date'] ?? date('Y-m-d');
        $api_key = "DEMO_KEY";  // usando a chave de API de demonstração da NASA, 
                                // é necessario substituir pela chave da API. Usado apenas para fim de demonstração
        $asteroids = getAsteroids($date, $api_key);
        
        if (isset($asteroids['error'])) {
            echo "<div class='error'>{$asteroids['error']}</div>";
        } elseif (empty($asteroids)) {
            echo "<div class='warning'>No asteroids found for $date.</div>";
        } else {
            echo "<h3>Asteroids on $date</h3>";
            echo "<ul class='asteroid-list'>";
            foreach ($asteroids as $asteroid) {
                $name = htmlspecialchars($asteroid['name']);
                $diameter = $asteroid['estimated_diameter']['kilometers']['estimated_diameter_max'] ?? 0;
                $hazardous = $asteroid['is_potentially_hazardous_asteroid'] ? '⚠️ Hazardous' : '✅ Safe';
                $approach_date = $asteroid['close_approach_data'][0]['close_approach_date_full'] ?? 'Unknown';
                $id = $asteroid['id'];
                
                echo "<li>";
                echo "<strong>$name</strong><br>";
                echo "Diameter: " . number_format($diameter, 3) . " km<br>";
                echo "Status: $hazardous<br>";
                echo "Approach: $approach_date" . "<br>";
                echo "<button onclick=\"mostrarDetalhes('$id')\">Details</button>";
                echo "</li>";
            }
            echo "</ul>";
        }
        ?>
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> Snifferoid. All rights reserved.</p>
        <span> Snifferoid by <strong><a href="https://github.com/walacysilvam">Walacy Silva</a></strong></span>
    </footer>

<script>
function mostrarDetalhes(id) {
    const div = document.getElementById('detalhes');
    div.style.display = 'block';
    div.innerHTML = "<p><em>Loading details...</em></p>";

    fetch(`https://api.nasa.gov/neo/rest/v1/neo/${id}?api_key=DEMO_KEY`)
        .then(res => res.json())
        .then(data => {
            const diametroMin = data.estimated_diameter.meters.estimated_diameter_min.toFixed(1);
            const diametroMax = data.estimated_diameter.meters.estimated_diameter_max.toFixed(1);
            const magnitude = data.absolute_magnitude_h;
            const perigoso = data.is_potentially_hazardous_asteroid ? "⚠️ Sim" : "✅ Não";

            div.innerHTML = `
                <h3>Detalhes de ${data.name}</h3>
                <p><strong>Magnitude Absoluta:</strong> ${magnitude}</p>
                <p><strong>Diâmetro Estimado:</strong> ${diametroMin}m - ${diametroMax}m</p>
                <p><strong>Potencialmente Perigoso:</strong> ${perigoso}</p>
                <p><strong>Data de Observação:</strong> ${data.orbital_data.first_observation_date ?? 'Desconhecida'}</p>
                <p><a href="${data.nasa_jpl_url}" target="_blank">Ver mais na NASA (JPL)</a></p>
            `;
        })
        .catch(err => {
            div.innerHTML = "<p style='color:red;'>Erro ao buscar detalhes do asteroide.</p>";
            console.error(err);
        });
}
</script>
</body>
</html>
