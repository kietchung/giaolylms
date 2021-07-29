define(['jquery', 'kendo.all.min', 'core/config', 'core/notification', 'dttable', 'core/str'], function($, kendo, Config, Notification, dttable, Str) {

    return {
        orgcomp_position: function() {
            $('.list-group .comp').click(function() {
                var tv;
                var getID = this.id;
                var script = Config.wwwroot + '/local/newsvnr/ajax.php';

                var settings2 = {
                    type: 'GET',
                    data: {
                        org_competency: 1,
                        frameid: getID
                    },
                    processData: true,
                    contentType: "application/json"
                };

                $.ajax(script, settings2)
                    .then(function(response) {
                        if (response.error) {
                            Notification.addNotification({
                                message: response.error,
                                type: "error"
                            });
                        } else {
                            // Reload the page, don't show changed data warnings.
                            if (typeof window.M.core_formchangechecker !== "undefined") {
                                window.M.core_formchangechecker.reset_form_dirty_state();
                            }
                            // window.location.reload();
                            var data = JSON.parse(response);


                            if (!$("#tree-competency").getKendoTreeView()) {
                                tv = $("#tree-competency").kendoTreeView({
                                    dataSource: data,

                                }).data('kendoTreeView');
                            } else {
                                tv = $("#tree-competency").getKendoTreeView();
                                var dataSourceTV = new kendo.data.HierarchicalDataSource({

                                    data: data
                                });
                                tv.setDataSource(dataSourceTV);
                            }


                            $('#value-item').on('keyup', function() {
                                var treeView = $("#tree-competency").getKendoTreeView();
                                treeView.dataSource.data(data);

                                // ignore if no search term
                                if ($.trim($(this).val()) == '') {
                                    return;
                                }

                                var term = this.value.toUpperCase();
                                var tlen = term.length;

                                $('#tree-competency span.k-in').each(function(index) {
                                    var text = $(this).text();

                                    var html = '';
                                    var q = 0;
                                    while ((p = text.toUpperCase().indexOf(term, q)) >= 0) {
                                        html += text.substring(q, p) + '<span class="highlight">' + text.substr(p, tlen) + '</span>';
                                        q = p + tlen;
                                    }

                                    if (q > 0) {
                                        html += text.substring(q);

                                        var dataItem = treeView.dataItem($(this));
                                        dataItem.set("text", html);

                                        $(this).parentsUntil('.k-treeview').filter('.k-item').each(

                                            function(index, element) {
                                                $('#tree-competency ').data('kendoTreeView').expand($(this));
                                                $(this).data('value-item', term);
                                            });
                                    }
                                });

                                $('#tree-competency .k-item').each(function() {
                                    if ($(this).data('value-item') != term) {
                                        $('#tree-competency ').data('kendoTreeView').collapse($(this));
                                    }
                                });
                            });



                            $('#tree-competency').click(function() {
                                var selected = tv.select(),
                                    item = tv.dataItem(selected);

                                if (item) {
                                    document.getElementById("value-item").value = item.id;

                                } else {
                                    $('#log').text('Nothing selected');
                                }
                            });


                        }

                        return;
                    }).fail(Notification.exception);

            });

            // ADD COMPETENTCY POSITION

            $('#save-add').click(function() {

                var comp_id = $('#value-item').val();
                var ordernumber = $('#ordernumber').val();
                var orgstructure_position = $('#orgstructure_position').val();
                if (!comp_id) {
                    alert("Dữ liệu trống!");
                } else {
                    var getURL = Config.wwwroot + '/local/newsvnr/ajax.php';
                    $.ajax({
                        url: getURL,
                        method: 'POST',
                        data: {
                            action: "add",
                            comp_id: comp_id,
                            ordernumber: ordernumber,
                            orgstructure_position: orgstructure_position
                        },
                        success: function(result) {

                            var data = JSON.parse(result);
                            if (data == "0") {
                                alert("Năng lực này đã tồn tại");

                            } else {
                                $('.orgcomp-position .table tbody').append(data.html);
                                $('.orgcomp-position .group-modal').append(data.modal);
                                alert("Thêm thành công!");
                            }


                        }
                    });

                }

            });

            // FILTER COMPETENTCY 


            $('#orgstructure').change(function() {

                var org_struct = $(this).val();

                if (org_struct != '') {
                    var getURL = Config.wwwroot + '/local/newsvnr/ajax.php';

                    $.ajax({
                        url: getURL,
                        method: 'GET',
                        data: {
                            action: "get_orgstruct_position",
                            org_struct: org_struct
                        },
                        success: function(result) {

                            $('#orgstructure_position').html(result);

                        }
                    });

                }
                $('#add_competency').prop('disabled', true);

            });

            $('#orgstructure_position').change(function() {

                var org_struct_position = $(this).val();
                var getURL = Config.wwwroot + '/local/newsvnr/ajax.php';
                if (org_struct_position != null) {
                    $('#add_competency').prop('disabled', false);

                    $.ajax({
                        url: getURL,
                        method: 'GET',
                        data: {
                            action: "load_comp_postion",
                            org_struct_position: org_struct_position
                        },

                        success: function(result) {
                            var data = JSON.parse(result);

                            if (data.check_exist_comp_template != true) {
                                $('#add_planning').prop('disabled', true);
                            } else if (data.check_exist_position_template == true) {

                                $('#add_planning').prop('disabled', true);
                            } else {
                                $('#add_planning').prop('disabled', false);
                            }
                            $('[data-region="orgcomp-position"]').html(data.comp_table);

                            $('[data-region="orgcomp-plan"]').html(data.plan_table);

                            $('.orgcomp-position .group-modal').html(data.modal);



                            var table1, table2;

                            if ($.fn.dataTable.isDataTable('#orgcomp-position')) {
                                table1 = $('#orgcomp-position').DataTable();
                            } else {
                                table1 = $('#orgcomp-position').DataTable({
                                    // "lengthMenu": [ [2, 25, 50, -1], [2, 25, 50, "All"] ],
                                    "scrollX": true,
                                });
                            }


                            if ($.fn.dataTable.isDataTable('#orgcomp-plan ')) {
                                table2 = $('#orgcomp-plan').DataTable();
                            } else {
                                table2 = $('#orgcomp-plan').DataTable({
                                    // "lengthMenu": [ [2, 25, 50, -1], [2, 25, 50, "All"] ],


                                });
                            }

                        }
                    });
                }

            });


            $(function() {

                var hours = [];
                checkTimeOut = false;
                for (var i = 0; i <= 24; i++) {

                    if (i < 10) {
                        hours.push('<option value="' + '0' + i + '">' + '0' + i + '</option');
                    } else {
                        hours.push('<option value="' + i + '">' + i + '</option');
                    }
                }

                var minutes = [];
                for (var i = 0; i < 60; i++) {
                    if (i < 10) {
                        minutes.push('<option value="' + '0' + i + '">' + '0' + i + '</option');
                    } else {
                        minutes.push('<option value="' + i + '">' + i + '</option');
                    }
                }

                $('#hour').html(hours);
                $('#minute').html(minutes);

                $('#show-timeout').click(function() {
                    $('#time-out').show();
                    checkTimeOut = true;
                });

                $('#hidden-timeout').click(function() {
                    $('#time-out').hide();
                    checkTimeOut = false;
                });

            });


            $('#save-planning').click(function() {

                var name = $('#name').val();

                var descript = $('#descript').val();

                var visible = $('#visible').val();

                var date = ($('#date-picker-2').val() != '') ? $('#date-picker-2').val() : 0;


                var time = $('#hour').val() + ':' + $('#minute').val();

                if (checkTimeOut == true) {
                    var getDateTime = new Date(date + ' ' + time).getTime() / 1000;
                } else {
                    getDateTime = 0;
                }

                var orgstructure_position = $('#orgstructure_position').val();

                var userid = $('#userid').val();

                if (name == '') {
                    alert('Name không được bỏ trống');
                } else {


                    var getURL = location.origin + '/local/newsvnr/ajax.php';


                    $.ajax({
                        url: getURL,
                        method: 'POST',
                        data: {
                            action: "save-planning",
                            name: name,
                            descript: descript,
                            visible: visible,
                            userid: userid,
                            getDateTime: getDateTime,
                            orgstructure_position: orgstructure_position
                        },
                        success: function(result) {

                            if (result == 1) {
                                alert("Thêm kế hoạch thành công!");
                                $('#name').val('');
                                $('#descript').val('');
                                $('#myModal1').modal('hide');
                                $('#add_planning').prop('disabled', true);
                            }

                        }
                    });
                }
            });

            $('.list-group li').click(function() {
                $(this).addClass('active').siblings().removeClass('active');
            });
        }


    };
});