<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 30. 4. 2015
 * Time: 14:01
 */




# PRIPOJENI K DB
mysql_connect('localhost', 'root', 'root');
mysql_select_db("oznamkuj");

# NASTAVENI CHOVANI PHP
error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');
$GLOBALS['config']['error']['enable'] = TRUE;


# DEFINOVANI PRIMARNICH PROMENNYCH
//$tabulka = "dbo.molson_coors_backup";


$cesta = "./data";
#If false just echo sql, if true go import
$import = 0;


/**
 * Klíč je pozice sloupce
 * 0 > jmeno sloupce v db
 * 1 > jmeno tabulky
 */
$definice_sloupcu = [
    0 => []
];

require_once "simple_html_dom.php";


importSouboru("C.xls");



function skoly($data) {
    $sloupce = [
        0 => "red_izo",
        1 => ""
        ];



}


function importSouboru($soubor)
{
    GLOBAL $cesta;
    GLOBAL $import;
    GLOBAL $sql;
    GLOBAL $link;

    $soce = $cesta."/".$soubor;
    echo PHPExcel_IOFactory::identify($soce);
    $objReader = PHPExcel_IOFactory::createReader('HTML');
    $objPHPExcel = $objReader->load($soce);

    $importovano = 0;



        $html = file_get_html('./data/C.xls');

        foreach($html->find('tr') as $radek => $tr) {
            echo "<br><hr><br>";
            $data = [];
           foreach ($tr->find('td') as $key => $td) {
               if($radek == 0) echo $key . " => ";

               $data[] = $td->plaintext;
           }

        }





    /*

                $sql = "INSERT INTO " . $tabulka . " (";
                $i = 0;
                foreach ($sloupce as $sloupec) {

                    if ($i != 0) {
                        $sql .= ",";
                    }
                    $sql .= $sloupec;

                    $i++;
                }
                $sql .= ",rok, id) VALUES (";
                $y = 0;
                # PROCHAZIME BUNKY
                foreach ($data as $dat) {

                    $dat = iconv("UTF-8", "Windows-1250", $dat);
                    if ($y != 0) {
                        $sql .= ",";
                    }

                    if ($dat != '' && $dat != ' ') {
                        if (is_numeric($dat)) {
                            $sql .= $dat;
                        } else {
                            $sql .= "'" . $dat . "'";
                        }
                    } else {
                        $sql .= "NULL";
                    }

                    $y++;
                }
                $sql .= "," . $rok . "," . $primaryId . ");";
                $primaryId++;
                #SHEET == 2
            } */



            if ($import) {
                if (mssql_query($sql, $link)) {
                    $importovano++;
                } else {
                    echo "<span style='color:red'>" . $sql . "</span><br><hr><br>";
                }
            } else {
                echo $sql . "<br><hr><br>";
            }





    echo "Importov�no <b>" . ($importovano / 2) . "</b> z�znam�";

}