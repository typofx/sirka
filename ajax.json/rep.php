<?

do {
    
$plt_usd = round('0.000004'.rand(1, 5000).'0', 8);
$plt_mkc_usd = round($plt_usd * 11299000992, 4);

$myfile = fopen("PLTUSD.json", "w") or die("Unable to open file!");

$text = '{'.'"PLTUSD" :'.$plt_usd.",\n ".
           '"MKCUSD"  :'.$plt_mkc_usd.
        '}';

fwrite($myfile, $text);
fclose($myfile);
sleep(1);

} while(true);

?>