<?php
/*
	欢迎使用DM企业建站系统，本系统由www.demososo.com开发。
*/
if(!defined('IN_DEMOSOSO')) {
	exit('this is wrong page,please back to homepage');
}
?>
<?php
if($act=="del"){   

// if(LANG=='en') $dellang = '';
$sql = "SHOW TABLES"; 
//$result = $mysqli->query('DESCRIBE table_name');//这是得到列名。//table_name 换成你对应的表名

$result = getall($sql);
$result = array_values($result);
 
$tables=array();
foreach($result as $v){
   foreach($v as $vv){
    $tables[] = $vv;
   }  
}
 
foreach($tables as $v) {

//echo $v.'<br />';

// id not in （1，2，3）
//$ss = "delete from zzz_node  where lang not in ('cn','en')";
//iquehy($ss);



$ss = "delete from $v  where lang='$type'";
 // echo '<p>'.$ss.'</p>'; 
  iquery($ss);
}

  
alert('已成功删除站点。'); 
 jump($jumpv);

}
 
 
  $dellink = $jumpv.'&file=del&type='.$type;
  
//javascript:delid('del_block','$tid','$jumpvt','$jsname') 

?> 



 <div style="padding:50px;font-size:16px">

 <?php 
    $sitename = get_fieldnolang(TABLE_LANG,'sitename',$type,'lang');
   // pre($sitename);
    
 ?>
 正在删除站点：  <span class="cred" style="font-size:18px"><?php echo $sitename;?></span><br /> 
 提醒：删除站点，请谨慎操作。删除前<span class="cred"  style="font-size:18px">请备份数据库</span>，删除后不能恢复。
 <br />  <br /> 

  <a style="color:#999;font-size:16px"     href="javascript:delid('del','','<?php echo $dellink;?>','')">确定删除吗？</a>
  <br />  <br /> 
  
 </div>
 
