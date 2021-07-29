define(['jquery', 'core/config', 'local_newsvnr/initkendoexam', 'alertjs', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/notification', 'core/fragment', 'core/ajax', 'core/yui', 'kendo.all.min'], 
	function($, Config, kendo, alertify, Str, ModalFactory, ModalEvents, Notification, Fragment, Ajax, Y, kendoControl) {
    "use strict";
    var gridEmailManagement = $('#email_management_grid');
    var script = Config.wwwroot + '/local/newsvnr/ajax/email/emailmanagement.php';
    var action = '';
    var kendoConfig = {};
  	
	var Template = function(dataItem) {
        Template.prototype.init(dataItem);
    };

    /**
     * @var {Modal} modal
     * @private
     */
    Template.prototype.modal = null;

    /**
     * @var {int} contextid
     * @private
     */
    Template.prototype.contextid = 1;

    /**
     * Cấu hình email content 
     * 
     * @param  {[Object]} dataItem [description]
     * @return {[Object]} modal [description]
     */
    Template.prototype.init = function(dataItem) {
        // Fetch the title string.
        return Str.get_string('emailcontentconfig', 'local_newsvnr').then(function(title) {
            // Create the modal.
            return ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: title,
                body: this.getBody({templateid: dataItem.id})
            });
        }.bind(this)).then(function(modal) {
            // Keep a reference to the modal.
            this.modal = modal;

            // Forms are big, we want a big modal.
            this.modal.setLarge();

            // We want to reset the form every time it is opened.
            this.modal.getRoot().on(ModalEvents.hidden, function() {
                this.modal.destroy();
                kendoControl.ui.progress($('body'), false);
            }.bind(this));

            // We want to hide the submit buttons every time it is opened.
            this.modal.getRoot().on(ModalEvents.shown, function() {
                this.modal.getRoot().append('<style>[data-fieldtype=submit] { display: none ! important; }</style>');
            }.bind(this));


            // We catch the modal save event, and use it to submit the form inside the modal.
            // Triggering a form submission will give JS validation scripts a chance to check for errors.
            this.modal.getRoot().on(ModalEvents.save, this.submitForm.bind(this));
            // We also catch the form submit event and use it to submit the form with ajax.
            this.modal.getRoot().on('submit', 'form', this.submitFormAjax.bind(this));
            
            this.modal.show();

            return this.modal;
        }.bind(this));
    };

    /**
     * @method getBody
     * @private
     * @return {Promise}
     */
    Template.prototype.getBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }
        // Get the content of the modal.
        var params = {jsonformdata: JSON.stringify(formdata)};
        
        return Fragment.loadFragment('local_newsvnr', 'create_email_template_form', this.contextid, params);
    };

    /**
     * Private method
     *
     * @method submitFormAjax
     * @private
     * @param {Event} e Form submission event.
     */
    Template.prototype.submitFormAjax = function(e) {
        // We don't want to do a real form submission.
        e.preventDefault();

        var _this = this;

        // Convert all the form elements values to a serialised string.
        var formData = this.modal.getRoot().find('form').serialize();
        
        // Now we can continue...
        Ajax.call([{
            methodname: 'local_newsvnr_submit_create_email_template_form',
            args: {contextid: this.contextid, jsonformdata: JSON.stringify(formData)},
        }])[0].then(function(data) {
        	if(data) {
        		_this.modal.hide();
		       	alertify.success(M.util.get_string('add_success', 'local_newsvnr'), 'success', 3);
		       	kendoControl.ui.progress($('body'), false);
        	} else {
        		_this.modal.setBody(this.getBody(data));
        	}
        }).catch(Notification.exception);;
    };

    /**
     * This triggers a form submission, so that any mform elements can do final tricks before the form submission is processed.
     *
     * @method submitForm
     * @param {Event} e Form submission event.
     * @private
     */
    Template.prototype.submitForm = function(e) {
        e.preventDefault();
        this.modal.getRoot().find('form').submit();
    };


    var prepareEmailTemplateForm = function(form, action) {
        function clearForm() {
            $('#name').val("");
            $('#code').val("");
            $('#emailtype').val("");
            $('#description').val("");
        }
        if (form.getKendoForm() == undefined) {

            var initFrm = form.kendoForm({
                orientation: "vertical",
                formData: {
                    templateid: "",
                    name: "",
                    code: "",
                    emailtype: "",
                    description: "",
                },
                buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="emailtemplate_add">' + M.util.get_string('addnew', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>',
                items: [{
                    field: "name",
                    label: M.util.get_string('name', 'local_newsvnr'),
                    validation: {
                        // required: true,
                    }
                }, {
                    field: "code",
                    label: {
                        text: M.util.get_string('code', 'local_newsvnr'),
                        // optional: 'true',
                        // encoded: true,   
                    },
                    validation: {
                        // required: true
                    }
                }, {
                    field: "emailtype",
                    label: M.util.get_string('emailtype', 'local_newsvnr'),
                    validation: {
                        // required: true
                    }
                }, {
                    field: "description",
                    label: M.util.get_string('description', 'local_newsvnr'),
                    editor: function(container, options) {
                        $("<textarea class='k-textarea' id='" + options.field + "' name='" + options.field + "' data-bind='value: " + options.field + "'></textarea>").appendTo(container);
                    }
                }],
                submit: function(e) {
                    e.preventDefault();
                   	if(validateEmailTemplateForm()) {
                   		var getAction = $('#form-emailtemplate .k-form-submit').attr('data-action');
	                    var settings = {
	                        type: 'GET',
	                        dataType: 'json',
	                        contentType: "application/json",
	                        data: {
	                            action: getAction,
	                            template: e.model
	                        },
	                    }
	                    $.ajax(script, settings).then(function(resp) {
	                        if (resp.success) {
	                            var grid = gridEmailManagement.data('kendoGrid');
	                            grid.dataSource.read();
	                            if (settings.data.action == 'emailtemplate_add')
	                                clearForm();
	                            alertify.success(resp.success, 'success', 3);
	                        }
	                    });
                   	} else {
                        //Form validation failed
                        e.preventDefault(); //So prevent form submission
                        kendoControl.ui.progress($("body"), false);
                    }
                    
                   
                }
            });
            function validateEmailTemplateForm() {
                var validator = form.kendoValidator({
                    rules: {
                        ruleRequired: function(input) {
                            if (input.is("[name=description]")) {
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
            if (action == 'emailtemplate_add') {
                form.getKendoForm().setOptions({
                    buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="emailtemplate_add">' + M.util.get_string('addnew', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>'
                });
            } else if (action == 'emailtemplate_edit') {
                form.getKendoForm().setOptions({
                    buttonsTemplate: '<button class="k-button k-primary k-form-submit" type="submit" data-action="emailtemplate_edit">' + M.util.get_string('edit', 'local_newsvnr') + '</button><button class="k-button k-form-clear">' + M.util.get_string('clear', 'local_newsvnr') + '</button>'
                });
            }
        }
    }

    // Tạo lưới kendo của email template
    var initEmailTemplate = function() {

        var form = $("#form-emailtemplate"),
            dialog = $('#modal-emailtemplate'),
            undo = $("#btn-emailtemplate");

        undo.click(function() {
            dialog.data("kendoWindow").open();
            $('#name').val("");
            $('#code').val("");
            $('#emailtype').val("");
            $('#description').val("");

        });

        function onClose() {
        	action = 'emailtemplate_add';
            undo.fadeIn();
        }

        function onOpen(e) {
            prepareEmailTemplateForm(form, action);
            e.sender.center();
        }
        dialog.kendoWindow({
            width: "450px",
            title: M.util.get_string('emailcontentconfig', 'local_newsvnr'),
            modal: true,
            close: onClose,
            visible: false,
            open: onOpen,
        });
        dialog.data("kendoWindow").close();
	    var columns = [
	        {
	            field: "name",
	            title: M.util.get_string('name', 'local_newsvnr'),
	            width: "200px"
	        }, {
	            field: "code",
	            title: M.util.get_string('code', 'local_newsvnr'),
	            width: "150px"
	        }, {
	            field: "emailtype",
	            title: M.util.get_string('emailtype', 'local_newsvnr'),
	            width: "150px"
	        }, {
	            field: "description",
	            title: M.util.get_string('description', 'local_newsvnr'),
	            width: "200px"
            }, {
            	title: M.util.get_string('templateemailconfig', 'local_newsvnr'),
            	command: {
            		click: function(e) {
            			e.preventDefault();
                		var dataItem = this.dataItem($(e.currentTarget).closest("tr"));
                		kendoControl.ui.progress($('body'), true);
                		Template(dataItem)
            		},
            		name: M.util.get_string('emailcontentconfig', 'local_newsvnr'),
            		iconClass: 'fa fa-paper-plane text-primary mr-1',
            	},
            	width: 200
            }];

        var settings = {
            url: script,
            type: 'GET',
            dataType: 'json',
            contentType: "application/json",
            data: {
                action: 'emailtemplate_grid',
            },
        }
	    kendoConfig.columns = columns;
	    kendoConfig.apiSettings = settings;
	    kendoConfig.toolbar = ["search"];
	    kendoConfig.editEvent = function(dataItem) {
	    	console.log(dataItem);
	        action = 'emailtemplate_edit';
	        var form = $('#form-emailtemplate');
	       
	        prepareEmailTemplateForm(form, action);
	        form.getKendoForm().setOptions({
	            formData: {
	                templateid: dataItem.id,
	                name: dataItem.name,
	                code: dataItem.code,
	                emailtype: dataItem.emailtype,
	                description: dataItem.description,
	            },
	        });

	        $('#modal-emailtemplate').data("kendoWindow").open();

	    }


	    kendoConfig.deleteEvent = function(dataItem) {
	        var settings = {
	            type: 'GET',
	            dataType: "json",
	            contentType: 'application/json; charset=utf-8',
	            data: {
	                action: 'emailtemplate_delete',
	                templateid: dataItem.id
	            }
	        }
	        $.ajax(script, settings).then(function(resp) {
	            if (resp.success) {
	                var grid = gridEmailManagement.data('kendoGrid');
	                grid.dataSource.read();
	                alertify.success(resp.success, 'success', 3);
	            }
	        });
        }
        var gridData = kendo.initGrid(kendoConfig);
        gridEmailManagement.kendoGrid(gridData);
    }
  	return {
  		initEmailTemplate: initEmailTemplate
  	}
});