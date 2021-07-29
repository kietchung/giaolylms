define(['jquery', 'core/config', 'validatefm', 'local_newsvnr/initkendoexam', 'alertjs', 'core/str', 'kendo.all.min'], function($, Config, Validatefm, kendo, alertify, Str, kendoControl) {
    "use strict";
    // Kì thi bắt buộc = 0
    // Kì thi tư do = 1
    const EXAMTYPEREQUIRED = 0;
    const EXAMTYPEFREEDOM = 1;
    var gridSubjectExam = '#subjectexam-grid';
    var gridExam = '#exam-grid';
    var gridEnrollExamUsers = '#enrollexamusers-grid';
    var script = Config.wwwroot + '/local/newsvnr/exam/ajax/exam.php';
    var scriptExamView = Config.wwwroot + '/local/newsvnr/exam/ajax/exam_view.php';
    var actionExamSubjectForm = '',
        actionExamForm = '';
    var fristClick = true

    function getSelectRow(gridname) {
        var myGrid = $(gridname).getKendoGrid();
        var selectedRows = myGrid.select();
        var arrObject = [];
        for (var i = 0; i < selectedRows.length; i++) {
            arrObject.push(myGrid.dataItem(selectedRows[i]));
        }
        return arrObject;
    }

    // Convert string to func js
    function looseJsonParse(obj){
        return Function('"use strict";return (' + obj + ')')();
    }

    // Convert datetime to unix
    function convertDateToUnix(datetime) {
        var date = new Date(datetime).toUTCString();
        var result = (new Date(date).getTime() / 1000);
        return result;
    }

    // Set lại dataSource cho kendoControl
    var newDataSource = function(settings) {
        return new kendoControl.data.DataSource({
            transport: {
                read: settings,
                parameterMap: function(options, operation) {
                    if (operation == "read") {
                        if (options["filter"]) {
                            if (options["filter"]["filters"][0])
                                options["q"] = options["filter"]["filters"][0].value;
                        }
                        return options;
                    }
                }
            },
            schema: {
                model: {
                    fields: {
                        id: {
                            type: "number"
                        },
                        fullname: {
                            type: "string"
                        },
                    }
                }
            },
            pageSize: 30,
            serverPaging: true,
            serverFiltering: true
        });
    };

    var validate_form = function() {
        $("#md-form-subjectexam").validate({
            onfocusout: false,
            onkeyup: false,
            onclick: false,
            rules: {
                "examname": {
                    required: true,
                },
                "examcode": {
                    required: true,
                }
            },
            messages: {
                "examname": {
                    required: "Bắt buộc nhập tên kì thi",
                },
                "examcode": {
                    required: "Bắt buộc nhập mã kì thi",
                },
            },
            submitHandler: function(form) {
                var id = $('#subjectexam-id').val();
                var name = $('#subjectexam-name').val();
                var code = $('#subjectexam-code').val();
                var shortname = $('#subjectexam-shortname').val();
                var description = $('#subjectexam-description').val();
                if ($('#subjectexam-visible').is(':checked') === true) var visible = 1;
                else var visible = 0;
                var settings = {
                    type: 'GET',
                    contentType: "application/json",
                    data: {
                        subjectexam: {
                            id: id,
                            name: name,
                            code: code,
                            shortname: shortname,
                            visible: visible,
                            description: description
                        }
                    },
                }
                if (id == '') {
                    settings.data.action = 'subjectexam';
                } else if (id && $.isNumeric(id)) {
                    settings.data.action = 'subjectexam_edit';
                }
                $.ajax(script, settings).then(function(resp) {
                    var res = JSON.parse(resp);
                    if (res.success) {
                        var grid = $(gridSubjectExam).data("kendoGrid");
                        grid.dataSource.read();
                    }
                })
            }
        });
    }

    // Chuẩn bị Init form tạo môn thi
    var prepareSubjectExamForm = function(form, action) {
        function clearForm() {
            $('#sxname').val("");
            $('#sxcode').val("");
            $('#sxshortname').val("");
            $('#sxdescription').val("");
            $('#sxvisible').data('kendoSwitch').value(true);
        }
        if (form.getKendoForm() == undefined) {


            var initFrm = form.kendoForm({
                orientation: "vertical",
                formData: {
                    sxid: "",
                    sxname: "",
                    sxcode: "",
                    sxshortname: "",
                    sxdescription: "",
                    sxvisible: true
                },
                buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="subjectexam_add">' + M.util.get_string('addnew', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>',
                items: [{
                    field: "sxname",
                    label: M.util.get_string('name', 'local_newsvnr'),
                    validation: {
                        // required: true,
                    }
                }, {
                    field: "sxcode",
                    label: {
                        text: M.util.get_string('code', 'local_newsvnr'),
                        // optional: 'true',
                        // encoded: true,   
                    },
                    validation: {
                        // required: true
                    }
                }, {
                    field: "sxshortname",
                    label: M.util.get_string('shortname', 'local_newsvnr'),
                    validation: {
                        // required: true
                    }
                }, {
                    field: "sxdescription",
                    label: M.util.get_string('description', 'local_newsvnr'),
                    editor: function(container, options) {
                        $("<textarea class='k-textarea' id='" + options.field + "' name='" + options.field + "' data-bind='value: " + options.field + "'></textarea>").appendTo(container);
                    }
                }, {
                    field: "sxvisible",
                    label: M.util.get_string('examvisible', 'local_newsvnr'),
                    editor: "Switch",
                }],
                submit: function(e) {
                    kendoControl.ui.progress($("#modal-subjectexam"), true);
                    e.preventDefault();
                    if (validateSubjectExamForm()) {
                        var getAction = $('#form-subjectexam .k-form-submit').attr('data-action');
                        var settings = {
                            type: 'GET',
                            dataType: 'json',
                            contentType: "application/json",
                            data: {
                                action: getAction,
                                exam: e.model
                            },
                        }
                        $.ajax(script, settings).then(function(resp) {
                            if (resp.success) {
                                var grid = $(gridSubjectExam).data('kendoGrid');
                                grid.dataSource.read();
                                if (settings.data.action == 'subjectexam_add')
                                    clearForm();
                                alertify.success(resp.success, 'success', 3);
                                kendoControl.ui.progress($("#modal-subjectexam"), false);
                            }
                        });
                    } else {
                        //Form validation failed
                        e.preventDefault(); //So prevent form submission
                        kendoControl.ui.progress($("#modal-subjectexam"), false);
                    }
                }
            });
            function validateSubjectExamForm() {
                var validator = form.kendoValidator({
                    rules: {
                        ruleRequired: function(input) {
                            if (input.is("[name=sxdescription]")) {
                                return true
                            } else {
                                return $.trim(input.val()) !== "";
                            }

                            return true;
                        },
                    },
                    messages: {
                        ruleRequired: M.util.get_string('fieldrequired', 'local_newsvnr'),
                    }
                }).data("kendoValidator");
                if (validator.validate()) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if (action == 'subjectexam_add') {
                form.getKendoForm().setOptions({
                    buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="subjectexam_add">' + M.util.get_string('addnew', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>'
                });
            } else if (action == 'subjectexam_edit') {
                form.getKendoForm().setOptions({
                    buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="subjectexam_edit">' + M.util.get_string('edit', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>'
                });
            }
        }
    }

    // Chuẩn bị Init form tạo kì thi
    var prepareExamForm = function(form, action) {
        function clearForm() {
            $('#examname').val("");
            $('#examcode').val("");
            $('#subjectexam').data('kendoMultiSelect').value([]);
            $('#examdatestart').data('kendoDateTimePicker').value(new Date());
            $('#examdateend').data('kendoDateTimePicker').value(new Date());
            $('#examdescription').val("");
            $('#examvisible').data('kendoSwitch').value(true);
        }
        if (form.getKendoForm() == undefined) {

            var settings = {
                url: script,
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'list_subjectexam'
                }
            }
            var initFrm = form.kendoForm({
                orientation: "vertical",
                formData: {
                    examid: "",
                    examname: "",
                    examcode: "",
                    examtype: 0,
                    examdatestart: new Date(),
                    examdateend: new Date(),
                    examdescription: "",
                    examvisible: true
                },
                buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="exam_add">' + M.util.get_string('addnew', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>',
                items: [{
                    field: "examtype",
                    label: M.util.get_string('examtype', 'local_newsvnr'),
                    validation: {
                        required: true,
                    },
                    editor: "DropDownList",
                    editorOptions: {
                        optionLabel: M.util.get_string('selectexamtype', 'local_newsvnr'),
                        dataSource: [{
                            text: M.util.get_string('required', 'local_newsvnr'),
                            value: 0
                        }, {
                            text: M.util.get_string('free', 'local_newsvnr'),
                            value: 1
                        }],
                        dataTextField: "text",
                        dataValueField: "value"
                    }

                }, {
                    field: "examname",
                    label: M.util.get_string('name', 'local_newsvnr'),
                    validation: {
                        // required: true
                    }
                }, {
                    field: "examcode",
                    label: M.util.get_string('code', 'local_newsvnr'),
                    validation: {
                        // required: true
                    }
                }, {
                    field: "subjectexam",
                    label: M.util.get_string('subjectexam', 'local_newsvnr'),
                    validation: {
                        required: true
                    },
                    editor: "MultiSelect",
                    editorOptions: {
                        placeholder: M.util.get_string('selectsubjectexam', 'local_newsvnr'),
                        itemTemplate: '<span>#= code # - #= name #</span>',
                        dataTextField: "name",
                        dataValueField: "id",
                        height: 520,

                        dataSource: {
                            transport: {
                                read: settings,
                                parameterMap: function(options, operation) {
                                    if (operation == "read") {
                                        if (options["filter"]) {
                                            if (options["filter"]["filters"][0])
                                                options["q"] = options["filter"]["filters"][0].value;
                                        }
                                        return options;
                                    }
                                }
                            },
                            schema: {
                                model: {
                                    fields: {
                                        id: {
                                            type: "number"
                                        },
                                        name: {
                                            type: "string"
                                        },
                                        code: {
                                            type: "string"
                                        },
                                    }
                                }
                            },
                            pageSize: 30,
                            serverPaging: true,
                            serverFiltering: true
                        },
                    }
                }, {
                    field: "examdatestart",
                    label: M.util.get_string('examdatestart', 'local_newsvnr'),
                    editor: "DateTimePicker",
                    validation: {
                        required: true
                    },
                    editorOptions: {
                        format: "dd/MM/yyyy hh:mm tt"
                    }
                }, {
                    field: "examdateend",
                    label: M.util.get_string('examdateend', 'local_newsvnr'),
                    editor: "DateTimePicker",
                    validation: {
                        required: true,

                    },
                    editorOptions: {
                        format: "dd/MM/yyyy hh:mm tt"
                    }
                }, {
                    field: "examdescription",
                    label: M.util.get_string('description', 'local_newsvnr'),
                    editor: function(container, options) {
                        $("<textarea class='k-textarea' id='" + options.field + "' name='" + options.field + "' data-bind='value: " + options.field + "'></textarea>").appendTo(container);
                    }
                }, {
                    field: "examvisible",
                    label: M.util.get_string('examvisible', 'local_newsvnr'),
                    editor: "Switch"
                }],

                submit: function(e) {
                    kendoControl.ui.progress($("#modal-exam"), true);
                    e.preventDefault();
                    if (validateExamForm()) {
                        
                        if (e.model.examdatestart) {
                            e.model.examdatestart = convertDateToUnix(e.model.examdatestart);
                        }
                        if (e.model.examdateend) {
                            e.model.examdateend = convertDateToUnix(e.model.examdateend);
                        }
                        if (e.model.subjectexam) {
                            e.model.subjectexam = $('#subjectexam').data("kendoMultiSelect").value().toString();
                        }
                        if (e.model.examtype) {
                            e.model.examtype = $('#examtype').data("kendoDropDownList").value();
                        }

                        var action = $('#form-exam .k-form-submit').attr('data-action');
                        var settings = {
                            type: 'GET',
                            dataType: 'json',
                            contentType: "application/json",
                            data: {
                                action: action,
                                exam: e.model
                            },
                        }
                        $.ajax(script, settings).then(function(resp) {
                            if (resp.success) {
                                var grid = $(gridExam).data('kendoGrid');
                                if (grid != undefined) {
                                    grid.dataSource.read();
                                    if (settings.data.action == 'exam_add') {
                                        clearForm();
                                    }
                                }

                                alertify.success(resp.success, 'success', 3);
                                kendoControl.ui.progress($("#modal-exam"), false);
                            }
                        });
                    } else {
                        //Form validation failed
                        e.preventDefault(); //So prevent form submission
                        kendoControl.ui.progress($("#modal-exam"), false);
                    }
                }


            });

            function validateExamForm() {
                var validator = form.kendoValidator({
                    rules: {
                        ruleRequired: function(input) {
                            if (input.is("[name=examdescription]") || input.is("[title=subjectexam]")) {
                                return true
                            } else {
                                return $.trim(input.val()) !== "";
                            }

                            return true;
                        },
                        checkDateStart: function(input) {
                            if(input.is("[name=examdatestart]")) {
                                var examdatestart = convertDateToUnix($("[name=examdatestart]").data('kendoDateTimePicker').value());
                                var now = convertDateToUnix(new Date());
                                if(examdatestart <= now - 120) {
                                    return false;
                                } else {
                                    return true;
                                }
                            }
                            return true;
                            
                        },
                        checkDateEnd: function(input) {
                           
                            if(input.is("[name=examdateend]")) {
                                var examdatestart = convertDateToUnix($("[name=examdatestart]").data('kendoDateTimePicker').value());
                                var examdateend = convertDateToUnix($("[name=examdateend]").data('kendoDateTimePicker').value());
                                if(examdateend < examdatestart) {
                                    return false;
                                } else {
                                    return true;
                                }
                            }
                            return true;
                        }
                       
                    },
                    messages: {
                        ruleRequired: M.util.get_string('fieldrequired', 'local_newsvnr'),
                        checkDateStart: M.util.get_string('checkdatestart', 'local_newsvnr'),
                        checkDateEnd: M.util.get_string('checkdateend', 'local_newsvnr')

                    }
                }).data("kendoValidator");
                if (validator.validate()) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            if (action == 'exam_add') {
                form.getKendoForm().setOptions({
                    buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="exam_add">' + M.util.get_string('addnew', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>'
                });
                clearForm();
            } else if (action == 'exam_edit') {
                form.getKendoForm().setOptions({
                    buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="exam_edit">' + M.util.get_string('edit', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>'
                });
                clearForm();
            }
        }
    }
    
    // Chuẩn bị Init form ghi danh học viên vào kì thi
    var prepareEnrollExamUsersForm = function(form, dataItem) {
        if(!form.getKendoForm()) {
            var settingsUsers = {
                    url: script,
                    type: 'GET',
                    dataType: "json",
                    contentType: 'application/json; charset=utf-8',
                    data: {
                        action: 'list_users',
                        examid: dataItem.id
                    }
            }
            var settingsCohort = {
                url: script,
                type: 'GET',
                dataType: "json",
                contentType: 'application/json; charset=utf-8',
                data: {
                    action: 'list_cohort'
                }
            }
            var initFrm = form.kendoForm({
                formData: {
                    examid: dataItem.id,
                    examusers: "",
                    examcohort: "",
                    examusersrole: 5
                },
                operation: 'vertical',
                buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit">' + M.util.get_string('enrol', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>',
                items: [{
                    field: "examusers",
                    label: M.util.get_string('examuserfullname', 'local_newsvnr'),
                    // validation: {
                    //  required: true
                    // },
                    editor: "MultiSelect",
                    editorOptions: {
                        placeholder: M.util.get_string('selectusers', 'local_newsvnr'),
                        itemTemplate: '<span>#= fullname #</span>',
                        dataTextField: "fullname",
                        dataValueField: "id",
                        dataSource: newDataSource(settingsUsers)
                    }
                }, {
                    field: "examcohort",
                    label: M.util.get_string('cohort', 'local_newsvnr'),
                    // validation: {
                    //  required: true
                    // },
                    editor: "MultiSelect",
                    editorOptions: {
                        placeholder: M.util.get_string('selectcohort', 'local_newsvnr'),
                        itemTemplate: '<span>#= fullname #</span>',
                        dataTextField: "fullname",
                        dataValueField: "id",
                        dataSource: newDataSource(settingsCohort)
                    }
                }, {
                    field: "examusersrole",
                    label: M.util.get_string('role', 'local_newsvnr'),
                    editor: "DropDownList",
                    validation: {
                        required: true
                    },
                    editorOptions: {
                        dataSource: [{
                            text: M.util.get_string('studentrole', 'local_newsvnr'),
                            value: 5
                        }, {
                            text: M.util.get_string('teacherrole', 'local_newsvnr'),
                            value: 4
                        }],
                        dataTextField: "text",
                        dataValueField: "value"
                    }
                }],
                submit: function(e) {
                    e.preventDefault();
                    kendoControl.ui.progress($("#modal-enrollexamusers"), true);
                    e.model.examusers = $('#examusers').data("kendoMultiSelect").value().toString();
                    e.model.examcohort = $('#examcohort').data("kendoMultiSelect").value().toString();
                    if (e.model.examusersrole) {
                        e.model.examusersrole = $('#examusersrole').data("kendoDropDownList").value();
                    }
                    var settingsEnrollExamUsers = {
                        type: 'GET',
                        dataType: "json",
                        contentType: 'application/json; charset=utf-8',
                        data: {
                            action: 'examusers_enroll',
                            exam: e.model
                        }
                    }
                    $.ajax(script, settingsEnrollExamUsers).then(function(resp) {
                        if (resp.success) {
                            var grid = $(gridEnrollExamUsers).data('kendoGrid');
                            var gridEnrollView = $("#exam-users-grid").data('kendoGrid');
                            grid.dataSource.read();
                            if(gridEnrollView != undefined) {
                                gridEnrollView.dataSource.read();
                            }
                            $("#examusers").data("kendoMultiSelect").value("");
                            $("#examcohort").data("kendoMultiSelect").value("");
                            $("#examusers").data("kendoMultiSelect").dataSource.read();
                            alertify.success(resp.success, 'success', 3);
                            kendoControl.ui.progress($("#modal-enrollexamusers"), false);
                        }
                    })
                }
            })
        
        }
    }

    // Chuẩn bị Init Form edit học viên đã ghi danh vào kì thi
    var prepareEditEnrollExamUsersForm = function(form) {
        if (form.getKendoForm() == undefined) {
            var initFrm = form.kendoForm({
                orientation: "vertical",
                formData: {
                    enrolluserid: "",
                    examid: "",
                    examuserid: "",
                    examuserfullname: "",
                    examusersrole: 5,
                },
                buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit">' + M.util.get_string('edit', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>',
                items: [{
                    field: "examuserfullname",
                    label: M.util.get_string('examuserfullname', 'local_newsvnr'),
                    validation: {
                        required: true
                    },
                    attributes: {
                        enable: false
                    }
                }, {
                    field: "examusersrole",
                    label: M.util.get_string('role', 'local_newsvnr'),
                    validation: {
                        required: true,
                    },
                    editor: "DropDownList",
                    editorOptions: {
                        dataSource: [{
                            text: M.util.get_string('studentrole', 'local_newsvnr'),
                            value: 5
                        }, {
                            text: M.util.get_string('teacherrole', 'local_newsvnr'),
                            value: 4
                        }],
                        dataTextField: "text",
                        dataValueField: "value"
                    }
                }],

                submit: function(e) {
                    kendoControl.ui.progress($("#modal-enrollexamusers-edit"), true);
                    e.preventDefault();

                    if (e.model.examtype) {
                        e.model.examtype = $('#examusersrole').data("kendoDropDownList").value();
                    }

                    var settings = {
                        type: 'GET',
                        dataType: 'json',
                        contentType: "application/json",
                        data: {
                            action: 'examusers_enroll_edit',
                            exam: e.model
                        },
                    }
                    $.ajax(script, settings).then(function(resp) {
                        if (resp.success) {
                            var grid = $(gridEnrollExamUsers).data('kendoGrid');
                            var gridEnrollView = $("#exam-users-grid").data('kendoGrid');
                            grid.dataSource.read();
                            if(gridEnrollView != undefined) {
                                gridEnrollView.dataSource.read();
                            }
                            alertify.success(resp.success, 'success', 3);
                            kendoControl.ui.progress($("#modal-enrollexamusers-edit"), false);
                        }
                    });
                },

            });
        }
    }

    // Tạo modal form môn thi
    var initExamSubjectForm = function() {

        var validationSuccess = $("#validation-success");
        var form = $("#form-subjectexam"),
            dialog = $('#modal-subjectexam'),
            undo = $("#btn-subjectexam");

        undo.click(function() {
            dialog.data("kendoWindow").open();
            $('#sxname').val("");
            $('#sxcode').val("");
            $('#sxshortname').val("");
            $('#sxdescription').val("");
            $('#sxvisible').data('kendoSwitch').value(true);

        });

        function onClose() {
            actionExamSubjectForm = 'subjectexam_add';
            undo.fadeIn();
        }

        function onOpen(e) {
            prepareSubjectExamForm(form, actionExamSubjectForm);
            e.sender.center();
        }
        dialog.kendoWindow({
            width: "450px",
            title: M.util.get_string('createsubjectexam', 'local_newsvnr'),
            modal: true,
            close: onClose,
            visible: false,
            open: onOpen,
        });
        dialog.data("kendoWindow").close();
    }

    // Tạo moda form kỳ thi
    var initExamForm = function() {
        var form = $("#form-exam"),
            dialog = $('#modal-exam'),
            undo = $("#btn-exam");

        undo.click(function() {
            dialog.data("kendoWindow").open();
        });

        function onClose() {
            actionExamForm = 'exam_add';
            undo.fadeIn();
        }

        function onOpen(e) {
            prepareExamForm(form, actionExamForm);
            e.sender.center();
        }

        dialog.kendoWindow({
            width: "450px",
            title: M.util.get_string('createexam', 'local_newsvnr'),
            modal: true,
            close: onClose,
            open: onOpen
        });
        dialog.data("kendoWindow").close();
    }

    // Tạo modal form ghi danh học viên vào kỳ thi
    var initEnrollExamUsers = function(dataItem) {
        var kendoConfig = {};
        var settingsExamUserGrid = {
            url: script,
            type: 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data: {
                action: 'examusers_grid',
                examid: dataItem.id
            }
        };
        var examUsersColumns = [
            {
                field: "examname",
                title: M.util.get_string('name', 'local_newsvnr'),
                width: "200px"
            }, {
                template: function(e) {
                    return e.fullname;
                },
                field: "fullname",
                title: M.util.get_string('examuserfullname', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "rolename",
                title: M.util.get_string('role', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "usercreate",
                title: M.util.get_string('usercreate', 'local_newsvnr'),
                width: "200px"
            }];
        kendoConfig.columns = examUsersColumns;
        kendoConfig.apiSettings = settingsExamUserGrid;
        kendoConfig.toolbar = [{
            template: '<a class="btn btn-secondary" id="btn-enrollexamusers-delete" href="javascript:;"><i class="fa fa-trash-o mr-2" aria-hidden="true"></i>' + M.util.get_string('deleteall', 'local_newsvnr') + '</a>'
        }, "search"];

        // Chỉnh sửa ghi danh  học viên đã được ghi danh
        kendoConfig.editEvent = function(enrollExamUsersDataItem) {
            var editEnrollDialog = $('#modal-enrollexamusers-edit'),
                editEnrollForm = $('#form-enrollexamusers-edit');
            initEditEnrollExamUsers(editEnrollForm, editEnrollDialog);
            editEnrollForm.getKendoForm().setOptions({
                formData: {
                    enrolluserid: enrollExamUsersDataItem.id,
                    examid: dataItem.id,
                    examuserid: enrollExamUsersDataItem.userid,
                    examuserfullname: enrollExamUsersDataItem.fullname_raw,
                    examusersrole: enrollExamUsersDataItem.roleid,
                },
            });
            editEnrollDialog.data("kendoWindow").open();;

        }
        kendoConfig.deleteEvent = function(enrollExamUsersDataItem) {

            var settings = {
                type: 'GET',
                dataType: "json",
                contentType: 'application/json; charset=utf-8',
                data: {
                    action: 'examusers_enroll_delete',
                    id: enrollExamUsersDataItem.id
                }
            }
            $.ajax(script, settings).then(function(resp) {
                if (resp.success) {
                    var grid = $(gridEnrollExamUsers).data('kendoGrid');
                    var gridEnrollView = $("#exam-users-grid").data('kendoGrid');
                    grid.dataSource.read();
                    if(gridEnrollView != undefined) {
                        gridEnrollView.dataSource.read();
                    }
                    // alertify.success(resp.success, 'success', 3);
                }
            });
        }
        
        var gridData = kendo.initGrid(kendoConfig);
        if($(gridEnrollExamUsers).data('kendoGrid')) {
            $(gridEnrollExamUsers).data('kendoGrid').destroy();
        }
        $(gridEnrollExamUsers).kendoGrid(gridData);

        if(fristClick == true) {
            $("#btn-enrollexamusers-delete").click(function() {
                var selectedRows = getSelectRow(gridEnrollExamUsers);
                if (selectedRows.length > 0) {
                    var settingsSelectedRows = {
                        type: "POST",
                        processData: true,
                        // dataType: "json",
                        // contentType: 'application/json; charset=utf-8',
                        data: {
                            action: 'enrolexamusers_delete_all',
                            rowselected: JSON.stringify(selectedRows)
                        }
                    }
                    $.ajax(script, settingsSelectedRows).then(function(resp) {
                        var resp = JSON.parse(resp);
                        if (resp.success) {
                            $(gridEnrollExamUsers).data("kendoGrid").dataSource.read();
                            alertify.success(resp.success, 'success', 3);
                        }
                    })
                } else {
                    alertify.error(M.util.get_string('error_norowselectd', 'local_newsvnr'), 'error', 3);
                }
            })
            fristClick = false;
        }


        
    }

    // Tạo modal form chỉnh sửa học viên đã ghi danh vào kỳ thi
    var initEditEnrollExamUsers = function(form, dialog) {
        function onOpen(e) {
            prepareEditEnrollExamUsersForm(form);
            e.sender.center();
        }
        dialog.kendoWindow({
            width: "768px",
            title: M.util.get_string('editenrollexamuser', 'local_newsvnr'),
            modal: true,
            open: onOpen,
        });

        dialog.data("kendoWindow").close();
    }

    // Tạo màn hình môn thi
    var initExamSubject = function() {
        var kendoConfig = {};
        initExamSubjectForm();

        var settings = {
            url: script,
            type: 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data: {
                action: 'subjectexam_grid'
            }
        };
        var subjectExamColumns = [
            {
                field: "name",
                title: M.util.get_string('name', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "code",
                title: M.util.get_string('code', 'local_newsvnr'),
                width: "150px"
            }, {
                field: "shortname",
                title: M.util.get_string('shortname', 'local_newsvnr'),
                width: "150px"
            }, {
                field: "description",
                title: M.util.get_string('description', 'local_newsvnr'),
                width: "200px"
            }, {
                template: function(e) {
                    return e.tempvisible;
                },
                field: "tempvisible",
                title: M.util.get_string('examvisible', 'local_newsvnr'),
                width: "100px"
            }];
        kendoConfig.columns = subjectExamColumns;
        kendoConfig.apiSettings = settings;
        kendoConfig.toolbar = [{
            template: '<a class="btn btn-secondary" id="btn-subjectexam-delete" href="javascript:;"><i class="fa fa-trash-o mr-2" aria-hidden="true"></i>' + M.util.get_string('deleteall', 'local_newsvnr') + '</a>'
        }, "search"];
        kendoConfig.editEvent = function(dataItem) {
            actionExamSubjectForm = 'subjectexam_edit';
            var form = $('#form-subjectexam');
            if (dataItem.visible == 1) {
                dataItem.visible = true;
            } else {
                dataItem.visible = false;
            }

            prepareSubjectExamForm(form, actionExamSubjectForm);
            form.getKendoForm().setOptions({
                formData: {
                    sxid: dataItem.id,
                    sxname: dataItem.name,
                    sxcode: dataItem.code,
                    sxshortname: dataItem.shortname,
                    sxdescription: dataItem.description,
                    sxvisible: dataItem.visible
                },
            });

            $('#modal-subjectexam').data("kendoWindow").open();

        }
        kendoConfig.deleteEvent = function(dataItem) {
            var settings = {
                type: 'GET',
                dataType: "json",
                contentType: 'application/json; charset=utf-8',
                data: {
                    action: 'subjectexam_delete',
                    id: dataItem.id
                }
            }
            $.ajax(script, settings).then(function(resp) {
                if (resp.success) {
                    var grid = $(gridSubjectExam).data('kendoGrid');
                    grid.dataSource.read();
                    alertify.success(resp.success, 'success', 3);
                }
            });
        }
      
        var gridData = kendo.initGrid(kendoConfig);
        $(gridSubjectExam).kendoGrid(gridData);
        $("#btn-subjectexam-delete").click(function() {
            var selectedRows = getSelectRow(gridSubjectExam);

            if (selectedRows.length > 0) {
                var settingsSelectedRows = {
                    type: "POST",
                    processData: true,
                    // dataType: "json",
                    // contentType: 'application/json; charset=utf-8',
                    data: {
                        action: 'subjectexam_delete_all',
                        rowselected: JSON.stringify(selectedRows)
                    }
                }
                $.ajax(script, settingsSelectedRows).then(function(resp) {
                    var resp = JSON.parse(resp);
                    if (resp.success) {
                        $(gridSubjectExam).data("kendoGrid").dataSource.read();
                        alertify.success(resp.success, 'success', 3);
                    }
                })
            } else {
                alertify.error(M.util.get_string('error_norowselectd', 'local_newsvnr'), 'error', 3);
            }
        })
    }

    // Tạo màn hình kỳ thi
    var initExam = function() {
        var kendoConfig = {};
        initExamForm();

        var settings = {
            url: script,
            type: 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data: {
                action: 'exam_grid'
            }
        };
        var examColumns = [
            {
                field: "name",
                title: M.util.get_string('name', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "code",
                title: M.util.get_string('code', 'local_newsvnr'),
                width: "150px"
            }, {
                field: "type",
                title: M.util.get_string('type', 'local_newsvnr'),
                width: "100px"
            },  {
                field: "datestart",
                title: M.util.get_string('examdatestart', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "dateend",
                title: M.util.get_string('examdateend', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "usercreate",
                title: M.util.get_string('usercreate', 'local_newsvnr'),
                width: "200px"
            }, {
                field: "description",
                title: M.util.get_string('description', 'local_newsvnr'),
                width: "200px"
            }, {
                template: function(e) {
                    return e.tempvisible;
                },
                field: "tempvisible",
                title: M.util.get_string('examvisible', 'local_newsvnr'),
                width: "100px"
            }];
        kendoConfig.columns = examColumns;
        kendoConfig.apiSettings = settings;
        kendoConfig.toolbar = [{
            template: '<a class="btn btn-secondary" id="btn-exam-delete" href="javascript:;"><i class="fa fa-trash-o mr-2" aria-hidden="true"></i>' + M.util.get_string('deleteall', 'local_newsvnr') + '</a>'
        }, "search"];
        kendoConfig.editEvent = function(dataItem) {
            var form = $('#form-exam');
            actionExamForm = 'exam_edit';
            if (dataItem.type == M.util.get_string('required', 'local_newsvnr')) {
                dataItem.type = 0;
            } else {
                dataItem.type = 1;
            }
            if (dataItem.visible == 1) {
                dataItem.visible = true;
            } else {
                dataItem.visible = false;
            }
            prepareExamForm(form, actionExamForm);
            var settings = {
                type: 'GET',
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: {
                    action: 'list_subjectexam_by_examid',
                    examid: dataItem.id
                }
            }
            var formData = {
                examid: dataItem.id,
                examname: dataItem.name,
                examcode: dataItem.code,
                examtype: dataItem.type,
                examdatestart: new Date(dataItem.datestart_raw),
                examdateend: new Date(dataItem.dateend_raw),
                examdescription: dataItem.description,
            }
            form.getKendoForm().setOptions({
                formData: formData,
            });
            $.ajax(script, settings).then(function(resp) {
                formData.subjectexam = resp;
                formData.examvisible = dataItem.visible;
                form.getKendoForm().setOptions({
                    formData: formData,
                })
            });

            $('#modal-exam').data("kendoWindow").open();
        }
        kendoConfig.enrollExamUsers = function(dataItem) {
            var form = $("#form-enrollexamusers"),
                dialog = $("#modal-enrollexamusers");
            initEnrollExamUsers(dataItem);
            function onOpen(e) {
                if(form.getKendoForm()) {
                    form.getKendoForm().destroy();
                    form.empty();
                }
                prepareEnrollExamUsersForm(form, dataItem);
                e.sender.center();
            }
            function onClose() {
                dialog.fadeIn();
            }
          
            dialog.kendoWindow({
                width: "1200px",
                height: "768px",
                title: M.util.get_string('enrollexamuser', 'local_newsvnr'),
                modal: true,
                open: onOpen,
                close: onClose
            });
            dialog.data('kendoWindow').open(); 

        }
        kendoConfig.deleteEvent = function(dataItem) {
            alertify.confirm(M.util.get_string("confirm_datadelete", "local_newsvnr"), M.util.get_string("warning_examdelete", "local_newsvnr"),
                function() {
                    var settings = {
                        type: 'GET',
                        dataType: "json",
                        contentType: 'application/json; charset=utf-8',
                        data: {
                            action: 'exam_delete',
                            id: dataItem.id
                        }
                    }
                    $.ajax(script, settings).then(function(resp) {
                        if (resp.success) {
                            var grid = $(gridExam).data('kendoGrid');
                            grid.dataSource.read();
                            alertify.success(resp.success, 'success', 3);
                        }
                    });
                },
                function() {

                }
            ).set('labels', {ok:M.util.get_string('accept', 'local_newsvnr'), cancel:M.util.get_string('cancel', 'local_newsvnr')});
        }
        var gridData = kendo.initGrid(kendoConfig);
        $(gridExam).kendoGrid(gridData);
        $("#btn-exam-delete").click(function() {
            var selectedRows = getSelectRow(gridExam);
            if (selectedRows.length > 0) {
                alertify.confirm(M.util.get_string("confirm_datadelete", "local_newsvnr"), M.util.get_string("warning_examdelete", "local_newsvnr"),
                    function() {
                        var settingsSelectedRows = {
                            type: "POST",
                            processData: true,
                            // dataType: "json",
                            // contentType: 'application/json; charset=utf-8',
                            data: {
                                action: 'exam_delete_all',
                                rowselected: JSON.stringify(selectedRows)
                            }
                        }
                        $.ajax(script, settingsSelectedRows).then(function(resp) {
                            var resp = JSON.parse(resp);
                            if (resp.success) {
                                $(gridExam).data("kendoGrid").dataSource.read();
                                alertify.success(resp.success, 'success', 3);
                            }
                        })
                    },
                    function() {

                    }
                ).set('labels', {ok:M.util.get_string('accept', 'local_newsvnr'), cancel:M.util.get_string('cancel', 'local_newsvnr')});
            } else {
                alertify.error(M.util.get_string('error_norowselectd', 'local_newsvnr'), 'error', 3);
            }
        })
    }

    // JS cho màn hình quản lý kỳ thi view //

    // Grid cho danh sách môn thi màn hình quản lý kỳ thi 
    var prepareListSubjectExamGrid = function(examId) {
        var kendoConfig = {};
        var settingsListSbujectExam = {
            url: scriptExamView,
            type: 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data: {
                action: 'exam_listsubjectexam_grid',
                examid: examId
            }
        }
        var listSubjectExamColumns = [{
            field: "name",
            title: M.util.get_string('name', 'local_newsvnr'),
            width: "200px"
        }, {
            field: "numberquiz",
            title: M.util.get_string('numberquiz', 'local_newsvnr'),
            width: "200px"
        }, ];
        kendoConfig.columns = listSubjectExamColumns;
        kendoConfig.apiSettings = settingsListSbujectExam;

        kendoConfig.listSubjectExamDetailEvent = function(dataItem) {
            var dialog = $("#modal-listsubjectexam");
            var settingsListSbujectExamDetail = {
                type: 'GET',
                dataType: "json",
                contentType: 'application/json; charset=utf-8',
                data: {
                    action: 'exam_listsubjectexam_detail',
                    subjectexamid : dataItem.subjectexamid
                }
            }
            function onOpen(e) {
                kendoControl.ui.progress($("#modal-listsubjectexam"), true);
                 $.ajax(scriptExamView, settingsListSbujectExamDetail).then(function(resp) {
                    dialog.data('kendoWindow').content(resp.success);
                    kendoControl.ui.progress($("#modal-listsubjectexam"), false);
                });
                e.sender.center();
            }
           
            dialog.kendoWindow({
                width: "450px",
                title: M.util.get_string('listsubjectexam', 'local_newsvnr'),
                modal: true,
                open: onOpen,
            });
            dialog.data('kendoWindow').open();
            
        }


        var gridData = kendo.initGrid(kendoConfig);
        if($("#exam-list-grid").data('kendoGrid')) {
            $("#exam-list-grid").data('kendoGrid').destroy();
        }
        $("#exam-list-grid").kendoGrid(gridData);

    }

    // Grid cho danh sách học viên trong kỳ thi màn hình quản lý kỳ thi 
    var prepareListUsersGrid = function(examId) {
        var kendoConfig = {};
        var settingsListSbujectExam = {
            url: scriptExamView,
            type: 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data: {
                action: 'exam_listusersexam_grid',
                examid: examId
            }
        }
        var listSubjectExamColumns = [{
            template: function(e) {
                return e.fullname;
            },
            field: "fullname",
            title: M.util.get_string('examuserfullname', 'local_newsvnr'),
            width: "200px"
        }, {
            field: "email",
            title: M.util.get_string('email', 'local_newsvnr'),
            width: "200px"
        }, {
            field: "enrolltime",
            title: M.util.get_string('enrolltime', 'local_newsvnr'),
            width: "200px"
        }];
        kendoConfig.columns = listSubjectExamColumns;
        kendoConfig.toolbar = [{
            template: '<a class="btn btn-secondary" id="btn-enrollexamusers" href="javascript:;"><i class="fa fa-user-plus mr-2" aria-hidden="true"></i>' + M.util.get_string('enrol', 'local_newsvnr') + '</a>'
        }, "search"];
        kendoConfig.apiSettings = settingsListSbujectExam;
        var gridData = kendo.initGrid(kendoConfig);
        if($("#exam-users-grid").data('kendoGrid')) {
            $("#exam-users-grid").data('kendoGrid').destroy();
        }
        $("#exam-users-grid").kendoGrid(gridData);


    }

    // Tạo màn hình quản lý kỳ thi 
    var initViewExam = function() {

        $('#btn-exam').click(function(e) {
            e.preventDefault();
            initExamForm();
            $('#modal-exam').data("kendoWindow").open();
        })

        $('#exam-type').kendoDropDownList({
            dataSource: [{
                text: M.util.get_string('listexamrequired', 'local_newsvnr'),
                value: 0
            }, {
                text: M.util.get_string('listexamfree', 'local_newsvnr'),
                value: 1
            }],
            dataTextField: "text",
            dataValueField: "value",
            change: function(e) {
                var examType = this.value();
                var settingsExamCategory = {
                    type: 'GET',
                    dataType: "json",
                    contentType: 'application/json; charset=utf-8',
                    data: {
                        action: 'exam_category',
                        examtype: examType
                    }
                }
                kendoControl.ui.progress($('#menu-tree-exam'), true);
                $.ajax(scriptExamView, settingsExamCategory).then(function(resp) {
                    if(resp.category) {
                        $('#exam-category').empty();
                        $('#exam-category').html(resp.category);
                    } else {
                        $('#exam-category').html('<div class="alert-danger d-flex justify-content-center p-2">' + resp.nocategory + '!</div>');
                    }
                    kendoControl.ui.progress($('#menu-tree-exam'), false);
                });
            }

        })
        var examType = $('#exam-type').data('kendoDropDownList').value();
        var settingsExamCategory = {
            type: 'GET',
            dataType: "json",
            contentType: 'application/json; charset=utf-8',
            data: {
                action: 'exam_category',
                examtype: examType
            }
        }
        kendoControl.ui.progress($('#menu-tree-exam'), true);
        $.ajax(scriptExamView, settingsExamCategory).then(function(resp) {
            if(resp.category) {
                $('#exam-category').empty();
                $('#exam-category').html(resp.category);
            } else {
                $('#exam-category').html('<div class="alert-danger d-flex justify-content-center p-2">' + resp.nocategory + '!</div>');
            }
            kendoControl.ui.progress($('#menu-tree-exam'), false);
        })

        // Click cây kỳ thi để load dữ liệu của kỳ thi đó
        $('#exam-category').on('click', 'li', function(e) {
            // kendoControl.ui.progress($('#infoexam-content'), true);

            $(this).addClass('active').siblings().removeClass('active');
            var examId = $(this).attr('data-exam');
            var settingsExamDescription = {
                type: 'GET',
                dataType: "json",
                contentType: 'application/json; charset=utf-8',
                data: {
                    action: 'exam_description',
                    examid: examId
                }
            }
            $.ajax(scriptExamView, settingsExamDescription).then(function(resp) {
                $('#ed-name').val(resp.examdescription.name);
                $('#ed-code').val(resp.examdescription.code);
                $('#ed-type').val(resp.examdescription.type);
                $('#ed-datestart').val(resp.examdescription.datestart);
                $('#ed-dateend').val(resp.examdescription.dateend);
                $('#ed-description').val(resp.examdescription.description);
                $('#ed-teacher').html(resp.examdescription.teacher);
                $('#ed-numnberstudent').html(resp.examdescription.numberstudent);
                // kendoControl.ui.progress($('#infoexam-content'), false);
            });
        
            prepareListSubjectExamGrid(examId);
            prepareListUsersGrid(examId);
            if (examId != undefined) {
                var settingsListSbujectExam = {
                    url: scriptExamView,
                    type: 'GET',
                    dataType: "json",
                    contentType: 'application/json; charset=utf-8',
                    data: {
                        action: 'exam_listsubjectexam',
                        examid: examId
                    }
                }
                if($('#list-subjectexam').data('kendoDropDownList'))
                    $('#list-subjectexam').data('kendoDropDownList').destroy();
                $('#list-subjectexam').kendoDropDownList({
                    dataSource: newDataSource(settingsListSbujectExam),
                    dataTextField: "name",
                    dataValueField: "id",
                    placeholder: M.util.get_string('selectsubjectexam', 'local_newsvnr'),
                    filter: "contains",
                    change: function(e) {
                        var subjectId = this.value();
                        if($("#exam-result-grid").data('kendoGrid')) {
                            $("#exam-result-grid").data('kendoGrid').destroy();
                        }
                        if(subjectId < 0) {
                            alertify.error(M.util.get_string('requiredselectsubjectexam', 'local_newsvnr'), 'error', 3);
                            return true;
                        }
                        var settings = {
                            url: scriptExamView,
                            type: 'GET',
                            dataType: "json",
                            contentType: 'application/json; charset=utf-8',
                            data: {
                                action: 'exam_userresult_grid',
                                examid: examId,
                                subjectid: subjectId
                            }
                        }
                        kendoControl.ui.progress($('#exam-result-grid'), true)
                        $.ajax(scriptExamView, settings).then(function(resp) {
                            if(!resp.data_columns) {
                                $('#exam-result-grid').css('height', '25px');
                                $('#exam-result-grid').html('<div class="alert-danger d-flex justify-content-center p-2">' + M.util.get_string('noquizforsubjectexam', 'local_newsvnr')+ '!</div>');
                                return false;
                            } else {
                                $('#exam-result-grid').empty();
                            }
                            resp.data_columns.forEach(function(columns) {
                                if(columns.template) {
                                    columns.template = looseJsonParse(columns.template);
                                }
                            })
                           
                            var dataSource = new kendoControl.data.DataSource({
                                transport: {
                                    read: settings,
                                    parameterMap: function(options, operation) {
                                        if (operation == "read") {
                                            if (options["filter"]) {
                                                options["q"] = options["filter"]["filters"][0].value;
                                            }
                                            return options;
                                        }
                                    }
                                },
                                pageSize: 10,
                                serverPaging: true,
                                serverFiltering: true,
                                serverSorting: true,
                                schema: {
                                    model: {
                                        id : "id",
                                        fields: {
                                            id : { type: "number" },
                                            name: { type: "string" },
                                        }
                                    },
                                    data: 'data_grid',
                                    // total: function(data) {
                                    //     if (data != null && data.length > 0) return data[0].total;
                                    // },
                                },
                            });
                            kendoControl.ui.progress($('#exam-result-grid'), false)
                            $("#exam-result-grid").kendoGrid({
                                dataSource: dataSource,
                                persistSelection: true,
                                groupable: false,
                                resizable: true,
                                toolbar: ['search'],
                                search: {
                                    fields: ["name"]
                                },
                                pageable: {
                                    refresh: true,
                                    pageSizes: true,
                                    pageSizes: [10, 20, 50, 100],
                                    buttonCount: 5
                                },
                                height: 450,
                                columns: resp.data_columns,
                                noRecords: {
                                    template: '<span class="grid-empty">' + M.util.get_string('emptydata', 'local_newsvnr') + '</span>'
                                }
                            });
                        });
                    }
                });
            }
        });
        var examId = $('#exam-category li.active').attr('data-exam');
        prepareListUsersGrid(examId);

        // Click vào ghi danh trong tab danh sách học viên để ghi danh
        $('#btn-enrollexamusers').click(function() {
            var form = $("#form-enrollexamusers"),
                dialog = $("#modal-enrollexamusers");
            var dataItem = {};
            dataItem.id = $('#exam-category li.active').attr('data-exam');
            initEnrollExamUsers(dataItem);
            function onOpen(e) {
                if(form.getKendoForm()) {
                    form.getKendoForm().destroy();
                    form.empty();
                }
                prepareEnrollExamUsersForm(form, dataItem);
                e.sender.center();
            }
            dialog.kendoWindow({
                width: "1368px",
                height: "768px",
                title: M.util.get_string('enrollexamuser', 'local_newsvnr'),
                modal: true,
                // visible: true,
                open: onOpen
            });
            dialog.data('kendoWindow').open();
        })

    }

    return {
        initExamSubject: initExamSubject,
        initExam: initExam,
        initViewExam: initViewExam
    }
});