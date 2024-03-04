<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM Frequentie Checker</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .geschikt {
            color: green;
        }
        .ongeschikt {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>FM Frequentie Checker</h2>
        <form method="post">
            <div class="form-group">
                <label for="afstand">Afstand rondom Zundert (in km):</label>
                <input type="number" class="form-control" id="afstand" name="afstand" value="<?php echo isset($_POST['afstand']) ? $_POST['afstand'] : '20'; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Bereken</button>
        </form>
<div class="alert alert-info alert-dismissible fade show position-sticky" role="alert" style="margin-top: 20px;">
    Data op basis van fmscan.org tussen 105.0 - 107.9mhz in omgeving Zundert <a href="freqs_filter.csv" class="alert-link">(Bekijk brondata)</a>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['afstand'])) {
            $maxAfstand = intval($_POST['afstand']);
            $bestandsnaam = 'freqs_filter.csv'; // Pas dit aan naar het pad van je CSV-bestand

            $frequenties = leesFrequenties($bestandsnaam);
            echo "<h3 class='mt-5'>Geschikte frequenties binnen {$maxAfstand}km</h3>";
            vindGeschikteFrequenties($frequenties, $maxAfstand);
        }

        function leesFrequenties($bestandsnaam) {
            $frequenties = [];
            if (($handle = fopen($bestandsnaam, 'r')) !== FALSE) {
                fgetcsv($handle); // Sla de header over
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    $freq = sprintf("%.1f", floatval($data[0]));
                    $afstand = intval(preg_replace('/[^0-9]/', '', $data[4]));
                    $frequenties["$freq"] = $frequenties["$freq"] ?? [];
                    $frequenties["$freq"][] = ['zender' => $data[2], 'afstand' => $afstand];
                }
                fclose($handle);
            }
            return $frequenties;
        }

        function vindGeschikteFrequenties($inGebruik, $maxAfstand) {
            echo "<div class='table-responsive'><table class='table'><thead><tr><th>Frequentie (MHz)</th><th>Geschikt</th><th>Details</th></tr></thead><tbody>";
            for ($freq = 105.0; $freq <= 107.9; $freq += 0.1) {
                $freqStr = sprintf("%.1f", $freq);
                $isGeschikt = true;
                $dichtstbijzijndeStations = [];
                
                foreach ([0, -0.1, 0.1] as $offset) {
                    $checkFreq = sprintf("%.1f", $freq + $offset);
                    if (isset($inGebruik[$checkFreq])) {
                        foreach ($inGebruik[$checkFreq] as $zender) {
                            if ($zender['afstand'] <= $maxAfstand) {
                                $isGeschikt = false;
                                $dichtstbijzijndeStations[] = $zender['zender'] . " op " . $checkFreq . " MHz (" . $zender['afstand'] . "km)";
                            }
                        }
                    }
                }

                if ($isGeschikt) {
                    echo "<tr class='geschikt'><td>$freqStr</td><td>✅</td><td>Geen stations binnen {$maxAfstand}km.</td></tr>";
                } else {
                    echo "<tr class='ongeschikt'><td>$freqStr</td><td>❌</td><td>Station(s) binnen {$maxAfstand}km: " . implode(', ', $dichtstbijzijndeStations) . ".</td></tr>";
                }
            }
            echo "</tbody></table></div>";
        }
        ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
