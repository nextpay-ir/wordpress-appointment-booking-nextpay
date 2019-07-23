jQuery(function ($) {

    var Services = function($container, options) {
        var obj  = this;
        jQuery.extend(obj.options, options);

        // Load services form
        if (!$container.children().length) {
            $container.html('<div class="bookly-loading"></div>');
            $.ajax({
                type        : 'POST',
                url         : ajaxurl,
                data        : obj.options.get_staff_services,
                dataType    : 'json',
                xhrFields   : { withCredentials: true },
                crossDomain : 'withCredentials' in new XMLHttpRequest(),
                success     : function (response) {
                    $container.html(response.data.html);
                    var $services_form = $('form', $container);
                    $(document.body).trigger('special_hours.tab_init', [$container, obj.options.get_staff_services.staff_id, obj.options.booklyAlert]);
                    var autoTickCheckboxes = function () {
                        // Handle 'select category' checkbox.
                        $('.bookly-services-category .bookly-category-checkbox').each(function () {
                            $(this).prop(
                                'checked',
                                $('.bookly-category-services .bookly-service-checkbox.bookly-category-' + $(this).data('category-id') + ':not(:checked)').length == 0
                            );
                        });
                        // Handle 'select all services' checkbox.
                        $('#bookly-check-all-entities').prop(
                            'checked',
                            $('.bookly-service-checkbox:not(:checked)').length == 0
                        );
                    };
                    var checkCapacityError = function ($form_group) {
                        if (parseInt($form_group.find('.bookly-js-capacity-min').val()) > parseInt($form_group.find('.bookly-js-capacity-max').val())) {
                            $form_group.addClass('has-error');
                        } else {
                            $form_group.removeClass('has-error');
                        }
                        var has_errors = false;
                        $services_form.find('.bookly-js-capacity').closest('.form-group').each(function () {
                            if ($(this).hasClass('has-error')) {
                                has_errors = true;
                            }
                        });
                        if (has_errors) {
                            $services_form.find('.bookly-js-services-error').html(obj.options.l10n.capacity_error);
                            $services_form.find('#bookly-services-save').prop('disabled', true);
                        } else {
                            $services_form.find('.bookly-js-services-error').html('');
                            $services_form.find('#bookly-services-save').prop('disabled', false);
                        }
                    };

                    $services_form
                    // Select all services related to chosen category
                        .on('click', '.bookly-category-checkbox', function () {
                            $('.bookly-category-services .bookly-category-' + $(this).data('category-id')).prop('checked', $(this).is(':checked')).change();
                            autoTickCheckboxes();
                        })
                        // Check and uncheck all services
                        .on('click', '#bookly-check-all-entities', function () {
                            $('.bookly-service-checkbox', $services_form).prop('checked', $(this).is(':checked')).change();
                            $('.bookly-category-checkbox').prop('checked', $(this).is(':checked'));
                        })
                        // Select service
                        .on('click', '.bookly-service-checkbox', function () {
                            autoTickCheckboxes();
                        })
                        // Save services
                        .on('click', '#bookly-services-save', function (e) {
                            e.preventDefault();
                            var ladda = Ladda.create(this);
                            ladda.start();
                            $.ajax({
                                type       : 'POST',
                                url        : ajaxurl,
                                data       : $services_form.serialize(),
                                dataType   : 'json',
                                xhrFields  : {withCredentials: true},
                                crossDomain: 'withCredentials' in new XMLHttpRequest(),
                                success    : function (response) {
                                    ladda.stop();
                                    if (response.success) {
                                        obj.options.booklyAlert({success: [obj.options.l10n.saved]});
                                    }
                                }
                            });
                        })
                        // After reset auto tick group checkboxes.
                        .on('click', '#bookly-services-reset', function () {
                            setTimeout(function () {
                                autoTickCheckboxes();
                                $('.bookly-js-capacity-form-group', $services_form).each(function () {
                                    checkCapacityError($(this));
                                });
                                $('.bookly-service-checkbox', $services_form).trigger('change');
                            }, 0);
                        });

                    $('.bookly-service-checkbox').on('change', function () {
                        var $this = $(this);
                        var $inputs = $this.closest('li').find('input:not(:checkbox)');
                        $inputs.attr('disabled', !$this.is(':checked'));
                    });

                    $('.bookly-js-capacity').on('keyup change', function () {
                        checkCapacityError($(this).closest('.form-group'));
                    });
                    autoTickCheckboxes();
                }
            });
        }

    };

    Services.prototype.options = {
        get_staff_services: {
            action  : 'bookly_get_staff_services',
            staff_id: -1,
            csrf_token: ''
        },
        booklyAlert: window.booklyAlert,
        l10n: {}
    };

    window.BooklyStaffServices = Services;
});