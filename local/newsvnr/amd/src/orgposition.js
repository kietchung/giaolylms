define(['jquery', 'kendo.all.min', 'core/config', 'core/notification', 'dttable', 'core/str'], function($, kendo, Config, Notification, dttable, Str) {

    return {  
        orgposition: function(){
            var clickparent_orgposition = true;
            //chọn parentid cho phòng ban orgstructuer - trang orgstructure
            $('#id_orgstructureid').on('click',function() {
                if(clickparent_orgposition == true){
      
              var settings = {
                  type: 'GET',
                  processData: true,
                  data:{
                    org_struct: 1
                  },
                  // cache:false,
                  contentType: "application/json"
              };
              var script = Config.wwwroot + '/local/newsvnr/ajax.php';
              var tvajaxx = $.ajax(script, settings)
              .then(function(response) {
                  if (response.error) {
                    
                      
                  } else {
                      // Reload the page, don't show changed data warnings.
                      if (typeof window.M.core_formchangechecker !== "undefined") {
                          window.M.core_formchangechecker.reset_form_dirty_state();
                      }
                     
                      // window.location.reload();
                      var data = JSON.parse(response);
                      var tv = $("#treeview-orgposition").kendoTreeView({
                          dataSource: data,
                          
                      }).data('kendoTreeView');
                      
                      $('#treeview-orgposition').on('click','.k-item',function(){

                          var selected = tv.select(),item;
                         
                          if(selected.length >0)
                            item = tv.dataItem(selected);
                          if (item) {
                              // document.getElementById('id_orgstructureid').value = item.id;
                              document.getElementById('id_orgstructureid').value = item.text;
                             
                          } else {
                              $('#log').text('Nothing selected');
                          }
                           $('#treeview-orgposition').mouseleave(function(){ 
                              $(this).hide(500);
                           });
                      });
                     
                  }

                  clickparent_orgposition = false;
                  return;
              })
            } else {
               $('#id_orgstructureid').click(function() {
                   $('#treeview-orgposition').show(500);
               });
            }
            });
        }
        
    };
});