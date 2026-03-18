<?php
$cfg=require __DIR__.'/../config/database.php';
$pdo=new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',$cfg['host'],$cfg['port'],$cfg['database'],$cfg['charset']),$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$q='%paracetamol%';
$st=$pdo->prepare("SELECT COUNT(*) c FROM databarang WHERE status='1' AND (nama_brng LIKE :q OR letak_barang LIKE :q OR kode_brng LIKE :q)");
$st->execute(['q'=>$q]);
echo 'COUNT='.($st->fetchColumn()?:0)."\n";
$st=$pdo->prepare("SELECT kode_brng,nama_brng,letak_barang FROM databarang WHERE status='1' AND (nama_brng LIKE :q OR letak_barang LIKE :q OR kode_brng LIKE :q) ORDER BY nama_brng LIMIT 100");
$st->execute(['q'=>$q]);
foreach($st as $r){echo $r['kode_brng'].' | '.$r['nama_brng'].' | '.$r['letak_barang']."\n";}
