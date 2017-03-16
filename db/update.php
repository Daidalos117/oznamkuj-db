<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 30. 4. 2015
 * Time: 14:01
 */


require_once "../libs/PHPExcel/PHPExcel.php";

# PRIPOJENI K DB
mysql_connect('172.28.4.12', 'importi', 'sYHdGxpSA5WZmWP7');
mysql_select_db("import");

$link = mssql_connect('172.28.4.6','CATI_READER','Mikronet');
$database = "CATI1_DB";
mssql_select_db($database, $link);

# NASTAVENI CHOVANI PHP
error_reporting(E_ALL);
ini_set('display_errors',1);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');
$GLOBALS['config']['error']['enable'] = TRUE;


# DEFINOVANI PRIMARNICH PROMENNYCH
$tabulka = "dbo.molson_coors_backup";

$rok = 2015;
#If false just echo sql, if true go import
$import = 0;

$soubor = "Data_M6a+M5_1_Serbia_kontrola.xlsx";
$cesta = "/srv/www/htdocs/reporting/molson-coors/import/data";


$soce = $cesta."/".$soubor;
$objReader = PHPExcel_IOFactory::createReader('Excel2007');
$objPHPExcel = $objReader->load($soce);

$jazyk = $soubor[5].$soubor[6];
$importovano = 0;
$errors = [];

for ($x = 0; $x < 1; $x++) {

    $objPHPExcel = PHPExcel_IOFactory::load($soce);
    $jmeno = $x."-tmp".rand(0,999).".csv";
    # PRELOZIME XLS NA CSV
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV')
        ->setDelimiter(';')
        ->setEnclosure('"')
        ->setLineEnding("\n")
        ->setSheetIndex($x)
        ->save( $cesta."/".$jmeno);
    $soubor = fopen($cesta."/".$jmeno,"r");

    $xo = 0;
    # PROCHAZIME DATA
    while ( (($data = fgetcsv($soubor,0,";",'"',"\n"))) != FALSE) {


        if( $xo == 0 ) {
            $sloupce = $data;
            # NAJDEME CISLO - TEDY ID
            $id = array_search("intnr",$sloupce);
            #$country = array_search("country",$sloupce);
            $country = 8;

            $xo++;
            continue;

        }

        $data = str_replace("'","",$data);

        # SHEET == 1
        if(1){

            $sql = " UPDATE ".$tabulka." SET ";
            $y = 0;
            # PROCHAZIME BUNKY
            foreach($data as $key => $dat){
                if($y == 0 or $y == 1) {
                    $y++;
                    continue;
                }

                $dat = iconv("UTF-8","Windows-1250",$dat);
                if($y != 2){
                    $sql .= ", ";
                }
                $sql .= $sloupce[$key]."=";
                if($dat != '' && $dat != ' '){
                    if(is_numeric($dat) and $dat != 99){
                        $sql .= $dat;
                    }else{
                        $sql .= "'".$dat."'";
                    }
                }
                else{
                    $sql .= "NULL";
                }

                $y++;
            }
            $sql .= " WHERE interviewnumber=".$data[$id]." AND country=".$country." AND rok=$rok;";
            #SHEET == 2

        }
        elseif(0){

            $sql = "UPDATE ".$tabulka." SET ";

            $y = 0;

            foreach($data as $dat){

                # PRESKOCIME NAZVY SLOUPCU

                if($y == 0){
                    $y++;
                    continue;
                }


                $dat = trim($dat);

                # CUSTOM PODMINKY
                if(empty($sloupce[$y]))
                    continue;

                if($sloupce[$y] == "cislo" )
                    continue;



                if($y !== 1)
                    $sql .= ", ";

                if(!in_array($sloupce[$y],$hqVyjimky)){
                    $sql .= $sloupce[$y]."_label=";
                }else{
                    $sql .= $sloupce[$y]."=";
                }


                if($dat != '' && $dat != ' '){


                    $dat = iconv("UTF-8","Windows-1250",$dat);


                        $sql .= "'".$dat."'";


                }
                else{
                    $sql .= "NULL";
                }

                $y++;
            }

            $sql .= " WHERE interviewnumber=".$data[$id]." AND rok=".$rok;
            $sql .= ";";

        }

        if($import){
            if(mssql_query($sql,$link)){
                $importovano++;
            }else{
                echo "<span style='color:red'>".$sql."</span><br><hr><br>";
                $errors[] = $sql;
            }
        }else{
            echo $sql."<br><hr><br>";
        }

        $xo++;
    }
    fclose($soubor);
    unlink($cesta."/".$jmeno);
}

echo "Importov�no <b>".($importovano)."</b> z�znam�,<br> zem�: ".$jazyk."<br>";
echo "Počet errorů: ".count($errors)."<br>";
if(!empty($errors)){
    var_dump($errors);
}
