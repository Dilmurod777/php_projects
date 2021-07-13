<?php
# print to console
echo "Initializing variables...\n";
# path to xml files
const path = './data';
# city codes for storing specific data into database
$city_codes = [
  'Москва' => 'moscow',
  'Санкт-Петербург' => 'peterburg',
  'Самара' => 'samara',
  'Саратов' => 'saratov',
  'Казань' => 'kazan',
  'Новосибирск' => 'novosibirsk',
  'Челябинск' => 'chelyabinsk',
  'Деловые линии Челябинск' => 'dl_chelyabinsk',
];
# create array of columns of database table
$colNames = [
  'id' => 'INT AUTO_INCREMENT',
  'code' => 'VARCHAR(20) UNIQUE',
  'name' => 'VARCHAR(200)',
  'weight' => 'VARCHAR(20)',
  'usage' => 'TEXT'
];
# final array of products
$s_products = [];

# function to parse xml files and store the result to $s_products
function parse_xml()
{
  # print to console
  echo "Starting parsing XML files...\n";
  # get all files in the provided path to xml files
  $files = scandir(path);
  # iterate through all files in the provided path
  foreach ($files as $file) {
    # if file is '.' or '..' which means current and previous directories, ignore them
    if ($file == '.' || $file == '..') continue;

    # print to console
    echo "$file is in process...\n";

    if (strpos($file, 'import') !== false) {
      # if xml file is import one
      import_parser($file);
    } else if (strpos($file, 'offer') !== false) {
      # if xml file is offer one
      offer_parser($file);
    }
  }
}

# parse import xml files
function import_parser($file)
{
  # use global variables
  global $s_products, $city_codes;
  # convert the xml format file into object file using simplexml_load_file()
  # then convert objects to arrays using get_object_vars()
  $xml = get_object_vars(simplexml_load_file(path . "/" . $file)) or die("Error: Cannot create object");
  # extract city name from xml by finding tag 'Классификатор' and then 'Наименование' which provides city name
  $cityName = trim(rtrim(explode('(', get_object_vars($xml['Классификатор'])['Наименование'])[1], ') '));
  # get city code from the city name
  $city = $city_codes[$cityName];
  # extract 'Каталог' tag from xml
  $catalog = get_object_vars($xml['Каталог']);
  # extract 'Товары' from 'Каталог' and then get the only element 'Товар' which provides object of all products
  # convert object to array of products
  $products = get_object_vars($catalog['Товары'])['Товар'];

  # iterate through each product
  foreach ($products as $product) {
    # convert object to array of product information
    $product = get_object_vars($product);
    # extract 'Код' of product
    $code = $product['Код'];
    # extract 'Наименование' from product, double all backslashes to avoid futher errors with MySQL queries
    $name = str_replace('\\', '\\\\', $product['Наименование']);
    # replace all single and double quates to double quotes to avoid further errors with MySQL queries
    $name = preg_replace('(\'|\")', '"', $name);
    # extract 'Вес' of product
    $weight = $product['Вес'];
    # extract usage of product
    $usage = '';

    if (array_key_exists('Взаимозаменяемости', $product)) {
      # if product has 'Взаимозаменяемости'
      # extract 'Взаимозаменяемость' from 'Взаимозаменяемости'
      $extras = get_object_vars($product['Взаимозаменяемости'])['Взаимозаменяемость'];
      # if type of 'Взаимозаменяемость' is array, then there multiple elements
      if (gettype($extras) == 'array') {
        # iterate through each of the inner elements of 'Взаимозаменяемость'
        foreach ($extras as $extra) {
          # convert object to array of 'Взаимозаменяемость'
          $extra = get_object_vars($extra);
          # extract 'Марка' of product, double all backslashes to avoid futher errors with MySQL queries
          $mark = str_replace('\\', '\\\\', $extra['Марка']);
          # replace all single and double quates to double quotes to avoid further errors with MySQL queries
          $mark = preg_replace('(\'|\")', '"', $mark);
          # extract 'Модель' of product, double all backslashes to avoid futher errors with MySQL queries
          $model = str_replace('\\', '\\\\', $extra['Модель']);
          # replace all single and double quates to double quotes to avoid further errors with MySQL queries
          $model = preg_replace('(\'|\")', '"', $model);
          # extract 'КатегорияТС' of product, double all backslashes to avoid futher errors with MySQL queries
          $categoryTC = str_replace('\\', '\\\\', $extra['КатегорияТС']);
          # replace all single and double quates to double quotes to avoid further errors with MySQL queries
          $categoryTC = preg_replace('(\'|\")', '"', $categoryTC);
          # join $mark, $model and $categoryTC into one $usage variable
          $usage .= $mark . '-' . $model . '-' . $categoryTC . '|';
        }
      } else if (gettype($extras) == 'object') {
        # if type of 'Взаимозаменяемость' is object, then there a single element
        # convert object to array of 'Взаимозаменяемость'
        $extras = get_object_vars($extras);
        # extract 'Марка' of product, double all backslashes to avoid futher errors with MySQL queries
        # extract 'Марка' of product, double all backslashes to avoid futher errors with MySQL queries
        $mark = str_replace('\\', '\\\\', $extras['Марка']);
        # replace all single and double quates to double quotes to avoid further errors with MySQL queries
        $mark = preg_replace('(\'|\")', '"', $mark);
        # extract 'Модель' of product, double all backslashes to avoid futher errors with MySQL queries
        $model = str_replace('\\', '\\\\', $extras['Модель']);
        # replace all single and double quates to double quotes to avoid further errors with MySQL queries
        $model = preg_replace('(\'|\")', '"', $model);
        # extract 'КатегорияТС' of product, double all backslashes to avoid futher errors with MySQL queries
        $categoryTC = str_replace('\\', '\\\\', $extras['КатегорияТС']);
        # replace all single and double quates to double quotes to avoid further errors with MySQL queries
        $categoryTC = preg_replace('(\'|\")', '"', $categoryTC);
        # join $mark, $model and $categoryTC into one $usage variable
        $usage .= $mark . '-' . $model . '-' . $categoryTC . '|';
      }
    }

    # remove trailing | symbol from the right end of the $usage
    $usage = rtrim($usage, '| ');
    # store extracted variables to the global array of products, wraping into single quotes to avoid further errors with MySQL queries
    $s_products[$code]['code'] = '\'' . trim($code) . '\'';
    $s_products[$code]['name'] = '\'' . trim($name) . '\'';
    $s_products[$code]['weight'] = '\'' . trim($weight) . '\'';
    $s_products[$code]['usage'] = '\'' . trim($usage) . '\'';
    $s_products[$code]['quantity_' . $city] = '\'' . '0' . '\'';
    $s_products[$code]['price_' . $city] = '\'' . '0' . '\'';
  }
}

# parse offer xml files
function offer_parser($file)
{
  # use global variables
  global $s_products, $city_codes;
  # convert the xml format file into object file using simplexml_load_file()
  # then convert objects to arrays using get_object_vars()
  $xml = get_object_vars(simplexml_load_file(path . "/" . $file)) or die("Error: Cannot create object");
  # extract city name from xml by finding tag 'Классификатор' and then 'Наименование' which provides city name
  $cityName = trim(rtrim(explode('(', get_object_vars($xml['Классификатор'])['Наименование'])[1], ') '));
  # get city code from the city name
  $city = $city_codes[$cityName];
  # extract 'ПакетПредложений' tag from xml
  $offerPackets = get_object_vars($xml['ПакетПредложений']);
  # extract 'Предложения' from 'ПакетПредложений' and then get the only element 'Предложение' which provides object of all offers
  # convert object to array of offers
  $offers = get_object_vars($offerPackets['Предложения'])['Предложение'];
  # iterate through each offer
  foreach ($offers as $offer) {
    # convert object to array of offer information
    $offer = get_object_vars($offer);
    # extract 'Код' of offer
    $code = $offer['Код'];
    # extract 'Количество' of offer otherwise equal to 0
    $quantity = $offer['Количество'] or '0';
    # extract 'Цены' of offer, which contains object 'Цена'
    $prices = get_object_vars($offer['Цены'])['Цена'];
    # extract the first 'ЦенаЗаЕдиницу' of offer otherwise equal to 0
    $firstPrice = get_object_vars($prices[0])['ЦенаЗаЕдиницу'] or '0';

    # store extracted variables to the global array of products, wraping into single quotes to avoid further errors with MySQL queries
    $s_products[$code]['quantity_' . $city] = '\'' . trim($quantity) . '\'';
    $s_products[$code]['price_' . $city] = '\'' . trim($firstPrice) . '\'';
  }
}

# initialize all columns for table in database
function initalize_column_names()
{
  # use global variables
  global $city_codes, $colNames;

  # iterate through each city code and create columns quantity_CITY and price_CITY
  foreach ($city_codes as $city) {
    $colNames['quantity_' . $city] = 'VARCHAR(10)';
    $colNames['price_' . $city] = 'VARCHAR(10)';
  }
}

# initialize initial connection with database using PDO
function initialize_connection($host, $db, $table, $user, $password)
{
  # use global vriables
  global $colNames;
  # variable to store SQL queries
  $sql = '';

  # print to console
  echo "Initialize connection with database...\n"; # print to console
  # create a new instnace of PDO class providing the host, charset, user and password
  $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $password);
  # provide additional options to PDO instance
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  # create SQL query to create database
  $sql = "CREATE DATABASE IF NOT EXISTS `$db`";
  # execute SQL query and create database
  $pdo->query($sql);
  # execute SQL query and switch to created database
  $pdo->query("use `$db`");

  # create SQL query to create table if it does not exist already
  $sql = "CREATE TABLE IF NOT EXISTS `$table` (";
  # define primary key column
  $pk  = 'id';

  foreach ($colNames as $column => $type) {
    $sql .= "`$column` $type,";
  }
  // $sql = rtrim($sql, ', ');
  $sql .= " PRIMARY KEY (`$pk`)";
  $sql .= ") DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";

  # execute SQL query and create table with specified columns
  $pdo->query($sql);

  # return created PDO instance
  return $pdo;
}

# store XMl data to MySQL database
function store_db()
{
  # use global variables
  global $s_products;

  include 'database_constraints.php';
  # create variable to store MySQL queries
  $sql = '';

  # wrap further part of the program in order to catch any database connection errors
  try {
    # call function to initialize columns of database table
    initalize_column_names();
    # call function to establish connection with the database using PDO class
    $pdo = initialize_connection($host, $db, $table, $user, $password);

    # print to console
    echo "Storing data to database...\n";

    # iterate through each product
    foreach ($s_products as $product) {
      # extract all columns required for filling into the database
      $cols = [];
      foreach ($product as $column => $value) {
        # skip if the column is 'id' as it has AUTO_INCREMENT attribute
        if ($column == 'id') continue;
        # store the wrapped by single quotes column name into array
        $cols[] = '`' . $column . '`';
      }

      # create SQL query to insert all the products to database
      $insert_sql = "INSERT INTO $table (" . implode(', ', $cols) .
        ") VALUES (" . implode(',', $product) . ')';
      # create SQL query to update the existing rows if the UNIQUE KEY (code) is the same
      $update_sql = "UPDATE ";
      # iterate through each product to create a correct SQL query
      foreach ($product as $column => $value) {
        if ($column == 'id') continue;

        $update_sql .= "`$column`=$value, ";
      }
      # remove the trailing comman from the right end of update SQL query
      $update_sql = rtrim($update_sql, ', ');

      # join all queries into on single SQL query
      $sql = "$insert_sql ON DUPLICATE KEY $update_sql;";

      # execute SQL query and store all products to database table
      $pdo->exec($sql);
    }
  } catch (PDOException $e) {
    # in case any error is thrown, print to console
    echo "Connection Error occurred\n";
    die('Connection error: ' . $e->getMessage());
  }
}

# call function to parse the XML files
parse_xml();
# call function to store the extracted products to MySQL database
store_db();
# print to console
echo "Process finished.\n";
