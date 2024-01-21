<?php

class Product
{
    // Properties
    public $make; // required
    public $model; // required
    public $colour;
    public $capacity;
    public $network;
    public $grade;
    public $condition;
}

class UniqueProduct extends Product
{
    // Properties
    public $count;
}

class ProductParser
{
    private $indexHolder = [];
    private $productArr = [];
    private $uniqueArr = [];
    private $uniqueArrWithCount = [];
    private $fileSeparator;

    public function __construct($fileName, $combinationsFile = "combination_count.csv")
    {
        $this->parseFile($fileName);
        $this->createUniqueCombinationsFile($combinationsFile);
    }

    private function parseFile($fileName)
    {
        if (!file_exists($fileName)) {
            echo "Please provide a valid file to be parsed";
            exit();
        }

        $this->fileSeparator = $this->detectSeparator($fileName);

        $rowCount = 1;

        if (($handler = fopen($fileName, "r")) !== false) {
            while (($data = fgetcsv($handler, 0, $this->fileSeparator)) !== false) {
                $this->processRow($data, $rowCount);
                $rowCount++;
            }
            fclose($handler);
        }
    }

    private function detectSeparator($fileName)
    {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        switch ($fileExtension) {
            case 'csv':
                return ",";
            case 'tsv':
                return "\t";
            default:
                echo "Please provide a valid file (CSV or TSV) to be parsed";
                exit();
        }
        //more file extensions can be handled here
    }

    private function processRow($data, $rowCount)
    {
        $colCount = count($data);
        if ($rowCount == 1) {
            $this->processHeaderRow($data);
        } else {
            $this->processDataRow($data, $colCount);
        }
    }

    private function processHeaderRow($data)
    {
        for ($i = 0; $i < count($data); $i++) {
            $this->assignIndexHolder($data, $i);
        }
    }

    private function assignIndexHolder($data, $index)
    {
        switch ($data[$index]) {
            case 'brand_name':
                $this->indexHolder["make"] = $index;
                break;
            case 'model_name':
                $this->indexHolder["model"] = $index;
                break;
            case 'colour_name':
                $this->indexHolder["colour"] = $index;
                break;
            case 'gb_spec_name':
                $this->indexHolder["capacity"] = $index;
                break;
            case 'network_name':
                $this->indexHolder["network"] = $index;
                break;
            case 'grade_name':
                $this->indexHolder["grade"] = $index;
                break;
            case 'condition_name':
                $this->indexHolder["condition"] = $index;
                break;
        }
    }

    private function processDataRow($data, $colCount)
    {
        //return false and exit with error message if required fields are not found in file
        if ($colCount < 2) {
            echo "One or more required fields are missing. Please update your file and try again.";
            exit();
        }

        if (empty($data[0]) || empty($data[1])) {
            echo "One or more required fields are missing. Please update your file and try again.";
            exit();
        }

        $curArr = $this->createCurArr($data, $colCount);
        $curArrWithCount = $this->createCurArrWithCount($data, $colCount);

        $this->fillOptionalFields($curArr, $colCount);

        $product = $this->createProductObject($curArr);
        array_push($this->productArr, $product);

        $findKey = array_search($product, $this->uniqueArr);
        if ($findKey !== false) {
            $updatedCount = $this->uniqueArrWithCount[$findKey]->count + 1;
            unset($this->uniqueArr[$findKey]);
            unset($this->uniqueArrWithCount[$findKey]);

            $this->updateUniqueArrays($curArr, $curArrWithCount, $updatedCount);
        } else {
            $this->addToUniqueArrays($curArr, $curArrWithCount);
        }
    }

    private function createCurArr($data, $colCount)
    {
        $curArr = [];
        for ($i = 0; $i < $colCount; $i++) {
            array_push($curArr, $data[$i]);
        }
        return $curArr;
    }

    private function createCurArrWithCount($data, $colCount)
    {
        $curArrWithCount = $this->createCurArr($data, $colCount);
        array_push($curArrWithCount, 1);
        return $curArrWithCount;
    }

    private function fillOptionalFields(&$curArr, $colCount)
    {
        if ($colCount < 7) {
            for ($j = 0; $j < 7 - $colCount; $j++) {
                array_push($curArr, "");
            }
        }
    }

    private function createProductObject($curArr)
    {
        $product = new Product();
        $product->make = $curArr[$this->indexHolder['make']];
        $product->model = $curArr[$this->indexHolder['model']];
        $product->colour = $curArr[$this->indexHolder['colour']];
        $product->capacity = $curArr[$this->indexHolder['capacity']];
        $product->network = $curArr[$this->indexHolder['network']];
        $product->grade = $curArr[$this->indexHolder['grade']];
        $product->condition = $curArr[$this->indexHolder['condition']];
        return $product;
    }

    private function updateUniqueArrays($curArr, $curArrWithCount, $updatedCount)
    {
        unset($this->uniqueArr[$findKey]);
        unset($this->uniqueArrWithCount[$findKey]);

        $product = $this->createProductObject($curArr);
        $uproduct = $this->createUniqueProductObject($curArr, $updatedCount);

        array_push($this->uniqueArr, $product);
        array_push($this->uniqueArrWithCount, $uproduct);
    }

    private function addToUniqueArrays($curArr, $curArrWithCount)
    {
        $product = $this->createProductObject($curArr);
        $uproduct = $this->createUniqueProductObject($curArr, 1);

        array_push($this->uniqueArr, $product);
        array_push($this->uniqueArrWithCount, $uproduct);
    }

    private function createUniqueProductObject($curArr, $count)
    {
        $uproduct = new UniqueProduct();
        $uproduct->make = $curArr[$this->indexHolder['make']];
        $uproduct->model = $curArr[$this->indexHolder['model']];
        $uproduct->colour = $curArr[$this->indexHolder['colour']];
        $uproduct->capacity = $curArr[$this->indexHolder['capacity']];
        $uproduct->network = $curArr[$this->indexHolder['network']];
        $uproduct->grade = $curArr[$this->indexHolder['grade']];
        $uproduct->condition = $curArr[$this->indexHolder['condition']];
        $uproduct->count = $count;

        return $uproduct;
    }

    private function createUniqueCombinationsFile($combinationsFile)
    {
        $headingData = ["make", "model", "colour", "capacity", "network", "grade", "condition", "count"];

        $outStream = fopen($combinationsFile, 'w');
        fputcsv($outStream, $headingData);

        //assign to new array for proper display
        foreach ($this->uniqueArrWithCount as $outData) {
            $outArr = [
                "make" => $outData->make,
                "model" => $outData->model,
                "colour" => $outData->colour,
                "capacity" => $outData->capacity,
                "network" => $outData->network,
                "grade" => $outData->grade,
                "condition" => $outData->condition,
                "count" => $outData->count,
            ];

            fputcsv($outStream, $outArr);
        }

        fclose($outStream);

        if (count($this->uniqueArrWithCount) > 0) {
            echo "File with grouped count for unique combinations successfully created, file name = " . $combinationsFile;
        }
    }

    public function displayProductObjects()
    {
        echo "Products parsed successfully. <br><br> Product Objects Listed Below <br><br> ";

        foreach ($this->productArr as $item) {
            print_r($item);
        }
    }
}

//check for valid parameters from terminal and run the application
if (!isset($argv)) {
    echo "Please execute this script from the command terminal.";
    exit();
}

unset($argv[0]);
parse_str(implode('&', $argv), $_REQUEST);

if (!isset($_REQUEST["file"])) {
    echo "Please provide a file to be parsed";
    exit();
}

$file_name = $_REQUEST["file"]; // "test_products.csv";

if (empty($file_name)) {
    echo "Please provide a file to be parsed";
    exit();
}

$combinations_file = "";
if (isset($_REQUEST["unique-combinations"])) {
    if (!empty($_REQUEST["unique-combinations"]) && strpos($_REQUEST["unique-combinations"], ".csv") !== false) {
        $combinations_file = $_REQUEST["unique-combinations"];
    }
}

if (empty($combinations_file)) {
    $combinations_file = "combination_count.csv";
}

$productParser = new ProductParser($file_name, $combinations_file);
$productParser->displayProductObjects();

?>