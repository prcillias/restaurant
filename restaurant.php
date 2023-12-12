<?php

require_once 'autoload.php';

$client = new MongoDB\Client();
$resto = $client->pricillia->restaurants;

$boroughDropdown = $resto->distinct('borough');
$data = $resto->find([], ['projection' => ['_id' => 0]]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedBorough = isset($_POST['borough']) ? $_POST['borough'] : null;
    $selectedCuisine = isset($_POST['cuisine']) ? $_POST['cuisine'] : null;
    $selectedScore = isset($_POST['score']) ? intval($_POST['score']) : null;

    $filter = [];

    if ($selectedBorough) {
        $filter[] = ['borough' => $selectedBorough];
    }

    if ($selectedCuisine) {
        $filter[] = ['cuisine' => ['$regex' => $selectedCuisine, '$options' => 'i']];
    }

    if ($selectedScore) {
        $filter[] = ['grades.0.score' => ['$lt' => $selectedScore]];
    }

    if (!empty($filter)) {
        $data = $resto->find(['$and' => $filter], ['projection' => ['_id' => 0]]);
    } else {
        $data = $resto->find([], ['projection' => ['_id' => 0]]);
    }

    echo json_encode(iterator_to_array($data));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricillia's Restaurant</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 50px;
        }

        h1 {
            text-align: center;
            color: #007bff;
        }

        .form-group {
            margin-bottom: 15px;
        }

        #filterBtn {
            width: 100%;
        }

        table {
            margin-top: 20px;
        }

        th, td {
            text-align: center;
        }

    </style>
</head>
<body>

    <div class="container">
        <h1>Restaurant</h1>

        <div class="row form-group">
            <div class="col-lg-4">
                <label for="filterBorough" class="form-label">Borough:</label>
                <select class="form-select" id="filterBorough" aria-label="Select Borough">
                <option selected>Select Borough</option>
                <?php foreach ($boroughDropdown as $borough): ?>
                    <option value="<?= $borough ?>"><?= $borough ?></option>
                <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-lg-4">
                <label for="filterCuisineInput" class="form-label">Cuisine:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="filterCuisineInput" placeholder="Ex: Bakery" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm">
                </div>
            </div>

            <div class="col-lg-4">
                <label for="filterScoreInput" class="form-label">Last Grade's Score:</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="filterScoreInput" placeholder="Ex: 5" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm">
                </div>
            </div>

            <div class="col-lg-12 mt-3">
                <button class="btn btn-primary" id="filterBtn" type="button">Go</button>
            </div>
        </div>            

        <table class="table table-bordered" id="restaurantTable" style="box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);">
            <thead>
                <tr>
                    <th scope="col">Restaurant ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Borough</th>
                    <th scope="col">Address</th>
                    <th scope="col">Cuisine</th>
                    <th scope="col">Grade</th>
                    <th scope="col">Score</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= $row['restaurant_id'] ?></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['borough'] ?></td>
                        <td><?= $row['address']['building'] . ' ' . $row['address']['street'] ?></td>
                        <td><?= $row['cuisine'] ?></td>
                        <td><?= isset($row['grades'][0]['grade']) ? $row['grades'][0]['grade'] : '' ?></td>
                        <td><?= isset($row['grades'][0]['score']) ? $row['grades'][0]['score'] : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

<script>
    $(document).ready(function() {
        $("#filterBtn").click(function() {
            var selectedBorough = $("#filterBorough").val();
            var selectedCuisine = $("#filterCuisineInput").val();
            var selectedScore = parseInt($("#filterScoreInput").val(), 10);
            $.ajax({
                method : 'POST',
                data : {
                    'borough' : selectedBorough,
                    'cuisine' : selectedCuisine,
                    'score' : selectedScore
                },
                success : function(response){
                    var newData = JSON.parse(response);
                    updateTableContent(newData)
                }
            })
        });

        function updateTableContent(newData) {
            var tableBody = $("#restaurantTable tbody");
            tableBody.empty();
            newData.forEach(function(row) {
                var newRow = "<tr>" +
                    "<td>" + row.restaurant_id + "</td>" +
                    "<td>" + row.name + "</td>" +
                    "<td>" + row.borough + "</td>" +
                    "<td>" + row.address.building + ' ' + row.address.street + "</td>" +
                    "<td>" + row.cuisine + "</td>" +
                    "<td>" + (row.grades[0] ? row.grades[0].grade : '') + "</td>" +
                    "<td>" + (row.grades[0] ? row.grades[0].score : '') + "</td>" +
                    "</tr>";
                tableBody.append(newRow);
            });
        }
    });
</script>
</html>
