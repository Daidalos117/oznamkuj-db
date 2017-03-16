<?php
/**
 * Created by PhpStorm.
 * User: Roman
 * Date: 30. 4. 2015
 * Time: 14:01
 */




# PRIPOJENI K DB
$link = mysqli_connect('localhost', 'root', 'root');
mysqli_select_db($link, "oznamkuj");

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
$import = 1;

CONST TABULKA_SKOLY = 'skoly',
    TABULKA_SKOLY_KONTAKTY = 'skola_kontakty',
    TABULKA_TYPY_SKOL = 'typy_skol',
    TABULKA_TYPY_SKOLY = 'typy_skoly',
    TABULKA_TYP_KONTAKTU = "typ_kontaktu";

require_once "simple_html_dom.php";


importSouboru("C.xls");

$importovano = [];


function skoly($data) {
    $sloupce = [
        0 => "red_izo",
        1 => "ico",
        2 => "zrizovatel",
        3 => "uzemi",
        4 => "orp",
        5 => "plny_nazev",
        6 => "zkraceny_nazev",
        7 => "ulice",
        8 => "cislo_popisne",
        9 => "cislo_orientacni",
        10 => "cast_obce",
        11 => "psc",
        12 => "misto",
        18 => "reditel",

        ];

    $novaData = vyberData($sloupce,$data);
    $skolaExistuje = vratZaznam(array_values($sloupce),$novaData, TABULKA_SKOLY);
    if(empty($skolaExistuje)){
        $skolaId = udelejImport(array_values($sloupce), $novaData, TABULKA_SKOLY);
    }else {
        $skolaId = $skolaExistuje[0];
    }


    return $skolaId;
}


function kontakty($data, $kontrolaId) {
    GLOBAL $posledniId;
    $sloupce = [
        0 => $posledniId,
        13 => "telefon",
        14 => "fax",
        15 => "email_1",
        16 => "email_2",
        17 => "www"
    ];

    $novaData = vyberData($sloupce, $data);
    $existuje = vratZaznam($sloupce + [100 => "skola_id"],$novaData + [100=>$kontrolaId],  TABULKA_SKOLY_KONTAKTY);
    if(empty($existuje)){
        udelejImport(array_values($sloupce), $novaData, TABULKA_SKOLY_KONTAKTY);
    }else {

    }
}

function typy($data, $skolaId)
{
    $sloupceTyp = [
        20 => "typ_kod",
        21 => "typ_jmeno",
    ];

    $dataKontrolaTypu = vyberData($sloupceTyp,$data);
    $hodnotySloupce = array_values($sloupceTyp);
    $existujeTyp = vratZaznam($hodnotySloupce, $dataKontrolaTypu, TABULKA_TYPY_SKOL);
    //pokud typ existuje vezmeme jeho ID, pokud ne tak ho zalozime
    if(empty($existujeTyp)) {

       $typId = udelejImport($hodnotySloupce,$dataKontrolaTypu, TABULKA_TYPY_SKOL);
    }else {
        $typId = $existujeTyp[0];
    }

    $sloupce = [
        19 => "izo",
        22 => "ulice",
        23 => "cislo_popisne",
        24 => "cislo_orientacni",
        25 => "cast_obce",
        26 => "psc",
        27 => "misto"
    ];



    $kontrolaSkolySloupce = $sloupce;
    unset($kontrolaSkolySloupce[19]);
    $dataTyp = vyberData($kontrolaSkolySloupce, $data);
    //pokud je adresa podrizene skoly stejna jako hlavni skoly
    $adresaSkoly = vratZaznam(array_values($kontrolaSkolySloupce), $dataTyp, TABULKA_SKOLY, true);

    $dataNova = vyberData($sloupce, $data);
    if($adresaSkoly > 0) {
        // importujeme pouze typ zarizeni
        $noveId =  udelejImport(["typ_skoly_id","izo"], [$typId,$data[19]], TABULKA_TYPY_SKOLY);
    } else {
        // jinak importujeme i jeho adresu
        $sloupce = $sloupce + [100 => "typ_skoly_id"];
        $dataTyp = $dataNova + [100 => $typId];
        $noveId = udelejImport($sloupce, $dataTyp, TABULKA_TYPY_SKOLY);
    }

    return $noveId;

}

function vyberData($sloupce, $data) {
    return array_values(
            array_intersect_key($data, array_flip(
                array_keys($sloupce)
            ))
    );
}


function importSouboru($soubor)
{
    GLOBAL $cesta;
    GLOBAL $import;
    GLOBAL $link;
    GLOBAL $importovano;
    $soce = $cesta."/".$soubor;


    $html = file_get_html('./data/C.xls');

    foreach($html->find('tr') as $radek => $tr) {
        if($radek == 0) {
            //preskocime nazvy sloupcu
            continue;
            echo $key . " => ";
        }

        echo "<br><hr><br>";
        $data = [];

        foreach ($tr->find('td') as $key => $td) {

           $data[] = $td->plaintext;
        }



        $skolaId =  skoly($data);
        $typyId = typy($data, $skolaId);

        kontakty($data, $skolaId);
    }

    echo "<table>";
    echo "<tr>";
    echo "<th>tabulka</th>";
    echo "<th>importovano</th>";
    echo "</tr>";
    foreach ($importovano as $tabulka => $imprt) {
        echo "<tr>";
        echo "<td>".$tabulka."</td>";
        echo "<td>".$imprt."</td>";
        echo "</tr>";
    }
    echo "</table>";
}






function vratZaznam($sloupce, $data, $tabulka, $vracejPocet = false) {
    GLOBAL $link;
    $select = ($vracejPocet) ? "COUNT(*)" : "*";
    $sql = "SELECT  ".$select." FROM ".$tabulka." WHERE ";
    $x = 0;
    foreach ($sloupce as $klic => $sloupec) {
        if($x != 0) $sql .= " and ";
        $sql .= $sloupec."= '".$data[$klic]."' ";
        $x++;
    }
    $sql .= ";";



    $query = mysqli_query($link, $sql);
    if(!$query){
        echo $sql;
        die();
    }
    $fetch = mysqli_fetch_row($query);
    if($vracejPocet) {
        return $fetch[0];
    }else {
        return $fetch;
    }

}




function udelejImport($sloupce, $data, $tabulka) {
    GLOBAL $import;
    GLOBAL $link;
    GLOBAL $importovano;
    GLOBAL $posledniId;

    $sql = "INSERT INTO " . $tabulka . " (";
    $i = 0;
    foreach ($sloupce as $sloupec) {

        if ($i != 0) {
            $sql .= ",";
        }
        $sql .= $sloupec;

        $i++;
    }
    $sql .= ") VALUES (";
    $y = 0;
    # PROCHAZIME BUNKY
    foreach ($data as $dat) {

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
    $sql .= ");";

    if ($import) {
        if (mysqli_query($link, $sql)) {
            $importovano[$tabulka]++;
            return mysqli_insert_id($link);
        } else {
            echo "<span style='color:red'>" . $sql . "</span><br><hr><br> IMPORT";
            throw new Exception();
        }
    } else {
        echo $sql . "<br><hr><br>";
    }
}
