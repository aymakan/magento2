define([
    'ko',
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    "jquery/ui"
], function (ko, $, Abstract) {
    'use strict';


    return Abstract.extend({
        defaults: {
            isAutocomplete: true,
            imports: {
                toggleMethod: '${ $.parentName }.city:value'
            }
        },

        initialize: function () {
            this._super();
            return this;
        },

        selectedCity: ko.observable(''),

        toggleMethod: function (cityValue) {

        },

        cityAutoComplete: function (element) {
            var self = this;
            $(element).autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: '/aymakanshipping/autocomplete/cities',
                        dataType: 'json',
                        data: {
                            term: request.term
                        },
                        success: function (data) {
                            response(data.slice(0, 6));
                        }
                    });
                },
                appendTo: '#aymakan_cities',
                minLength: 2,
                select: function (event, ui) {
                    self.handleAutocompleteSelection(element, ui.item.label);
                }
            });
        },

        handleAutocompleteSelection: function (element, selectedItem) {
            var el = $(element);
            el.val(selectedItem)
            el.trigger('change');
        }
    });
});
