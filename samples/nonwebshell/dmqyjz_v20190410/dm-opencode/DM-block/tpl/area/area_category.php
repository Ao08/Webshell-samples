<?php
if(!defined('IN_DEMOSOSO')) {
  exit('this is wrong page,please back to homepage');
}

?>
   <?php if($pagetop<>'')  {
            echo '<div class="c pagetop">';
                block($pagetop);
            echo '</div>';
      }

    ?>

   <?php
   if($breadposi=='t') require_once BLOCKROOT.'tpl/bread.php';
  ?>
  <?php 
 $sidebar_attop='n';
 if(isdmmobile()) {
   if($sidebarposi=='l' || $sidebarposi=='r') $sidebar_attop='y';
 }

    if($sidebarposi=='t' ||  $sidebar_attop=='y'){
                   
                        if($cateofpagemenu=='')   require  BLOCKROOT.'tpl/sidebar/sidebar_cate_top.php';
                       else{
                           $main_menuid= get_field(TABLE_MENU,'pid',$cateofpagemenu,'pidname');
                           require_once BLOCKROOT.'tpl/sidebar/sidebar_page_top.php';
                         }
      } 


 if($sidebarposi=='l') {
    echo '<div  class="content fr cntwidth">';
    require BLOCKROOT.'tpl/content/content_category.php';
    echo '</div>';

    echo '<div  class="sidebar fl sdwidth">';
    require BLOCKROOT.'tpl/sidebar/sidebar_cate_lr.php';
    echo '</div>';



 }
if($sidebarposi=='r') {
    echo '<div  class="content fl cntwidth">';
    require BLOCKROOT.'tpl/content/content_category.php';
    echo '</div>';

    echo '<div  class="sidebar fr sdwidth">';
    require BLOCKROOT.'tpl/sidebar/sidebar_cate_lr.php';
    echo '</div>';


  
 }
if($sidebarposi=='t' || $sidebarposi=='n') {  
    
    echo '<div  class="content cntper perwidth">';
    require BLOCKROOT.'tpl/content/content_category.php';
    echo '</div>';
 
  
 }
 


  ?> 
<div class="c"></div>
 <?php if($pagebot<>'')  {
        echo '<div class="c pagebot">';
              block($pagebot);
        echo '</div>';
        }
 ?>
