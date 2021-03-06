layui.use('form', function(){
  // 三级省市县
    var form = layui.form;
    layer = layui.layer,
    $ = layui.jquery;
        // 默认值
       // setting = {
            // url:"", //数据地址
            // prov:'', //12
            // city:'', //186
            // dist:'', //2225
            // nodata:null, 
            // required:false //三级显示 是否必选  true 或者false
        // };
      
        var box_obj = $('#area');
        var prov_obj=box_obj.find(".prov");
        var city_obj=box_obj.find(".city");
        var dist_obj=box_obj.find(".dist");
        
        if(setting.required != true){
             $("#city-box").hide();
             $("#dist-box").hide();
        }
       // 设置省市json数据
        if(typeof(setting.url)=="string"){
            $.getJSON(setting.url,function(json){
                area_json = json;         
                init(area_json);
            });
        }else{
            area_json = setting.url;

        };
 

   // 选择省份时发生事件     
      form.on('select(prov)',function(data){
// console.log(data.value); 
          
          cityStart(area_json,data.value);
          $("#city-box").show();
           
           form.render();
      });  
      
   // 选择市级时发生事件
        form.on('select(city)',function(data){
          var prov_id =     $(".prov").val();
         distStart(area_json,prov_id,data.value);
           $("#dist-box").show();
             form.render();
      }); 

  // 选择地区发生的事件
        form.on('select(dist)',function(data){
            form.render();
        }); 

//初始化
function init(area_json){
          // console.log(area_json);
            if(setting.prov!=''){
               provStart(area_json,setting.prov);
            }else{
               provStart(area_json);
            }
           if(setting.prov!='' && setting.city !=''){
               cityStart(area_json,setting.prov,setting.city);
           }
           if(setting.prov!='' && setting.city !='' && setting.dist !=''){
                distStart(area_json,setting.prov,setting.city,setting.dist);
           }

}
//省份
function provStart(area_json,prov_id){
        var html = '<option value="0" >请选择</option>';
            $.each(area_json,function(k,prov){
                    html +='<option value="'+prov.id+'" ';
                    if(prov_id == prov.id){
                     html +=' selected ';
                    }
                    html +=' >'+prov.p+'</option>'            
            });
          prov_obj.html(html);
         form.render();

}

//城市
    function cityStart(area_json,prov_id,city_id){
      var    html ='<select class="city"  lay-filter="city" >'; 
         html +='<option value="0" >请选择</option>';

        $.each(area_json,function(k,prov){  

            if(area_json[k].id == prov_id){
                                // console.log();
                $.each(area_json[k].c,function(i,city){
                       
                        html +='<option value="'+city.id+'"';
                        if(city_id == city.id){

                             html +=' selected ';
                            }
                        html +='>'+city.n+'</option>';
                   
                    }); 
            }
 
            });

       html +='</select>';
       city_obj.html(html);
        $("#city-box").show();  
       form.render();
}
//乡镇
  function distStart(area_json,prov_id,city_id,dist_id){
  var   html ='<select class="dist"  lay-filter="dist">';
     html +='<option value="0" >请选择</option>';
            $.each(area_json,function(p,prov){      
                if(area_json[p].id == prov_id){ 
                   $.each(area_json[p].c,function(k,city){
                      if(city.id == city_id){
                             $.each(city.a,function(d,dist){
                                           html +='<option value="'+dist.id+'"';
                                        if(dist_id == dist.id){
                                             html +=' selected ';
                                            }
                                          html +='>'+dist.s+'</option>';

                                       });
                                  }
                    });
                }
      
            });
    html +='</select>';
    dist_obj.html(html);
    $("#dist-box").show();
//渲染页面
    form.render(); 
  }

    
});

