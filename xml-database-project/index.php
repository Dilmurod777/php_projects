<?php
include 'database_constraints.php';

# create a new instnace of PDO class providing the host, database, charset, user and password
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $password);
# provide additional options to PDO instance
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

# set the value of $page, default is 1
$page = isset($_GET['page']) && is_int(intval($_GET['page'])) ? intval($_GET['page']) : 1;
# number of rows to display per page
$rowsPerPage = 20;
# create SQL query
$sql = "SELECT * FROM $table ORDER BY id DESC LIMIT $rowsPerPage OFFSET " . ($page - 1) * $rowsPerPage;
# execute SQL query
$queryResult = $pdo->query($sql, PDO::FETCH_ASSOC);

# determine the column names from query
$columns = [];
for ($i = 0; $i < $queryResult->columnCount(); $i++) {
  $col = $queryResult->getColumnMeta($i);
  $columns[] = $col['name'];
}

# fetch all products from database
$products = $queryResult->fetchAll();

# create SQL query to find out total number of rows
$sql = "SELECT COUNT(*) from $table";
# execute SQL query and get max number of rows
$maxRows = intval($pdo->query($sql)->fetch()[0]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>XML Parser</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>

<body>
  <div class="container-fluid mt-3">
    <h1>Products</h1>
    <table class="table table-bordered mt-3">
      <thead>
        <tr>
          <?php foreach ($columns as $column) : ?>
            <th scope="col"><?= $column ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product) : ?>
          <tr>
            <?php foreach ($columns as $column) : ?>
              <td><?= mb_strimwidth($product[$column], 0, 10, '...') ?></td>
            <?php endforeach; ?>

          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <nav class="d-flex justify-content-center">
      <ul class="pagination">
        <?php if ($page > 1) : ?>
          <li class="page-item"><a class="page-link" href="index.php?page=<?= $page - 1 ?>">Previous</a></li>
          <li class="page-item"><a class="page-link" href="index.php?page=<?= $page - 1 ?>"><?= $page - 1 ?></a></li>
        <?php endif; ?>
        <li class="page-item"><a class="page-link" href="index.php?page=<?= $page ?>"><?= $page ?></a></li>
        <?php if ($page*$rowsPerPage < $maxRows) : ?>
          <li class="page-item"><a class="page-link" href="index.php?page=<?= $page + 1 ?>"><?= $page + 1 ?></a></li>
          <li class="page-item"><a class="page-link" href="index.php?page=<?= $page + 1 ?>">Next</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>

  <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>

</html>