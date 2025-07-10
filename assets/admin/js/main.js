(function ($, document) {
  const ajax = {
    cache() {
      ajax.vars = {};
      ajax.els = {};
      ajax.vars.count = [];
      ajax.vars.index_settings = {};
      ajax.els.process_overlay = $('.process-overlay');
      ajax.els.process = $('.process');
      ajax.els.process_content = $('.process__content--processing');
      ajax.els.process_loading = $('.process__content--loading');
      ajax.els.process_complete = $('.process__content--complete');
      ajax.els.process_from = $('.process__count-from');
      ajax.els.process_to = $('.process__count-to');
      ajax.els.process_total = $('.process__count-total');
      ajax.els.process_loading_bar_fill = $('.process__loading-bar-fill');
    },
    on_ready() {
      ajax.cache();
      ajax.watch_triggers();
      const options = {
        ...jckWssvVars.select_woo_options.categories,
        ajax: {
          ...jckWssvVars.select_woo_options.categories.ajax,
          data(params) {
            return {
              action: 'iconic_wssv_product_taxonomy_search',
              taxonomy: 'product_cat',
              term: params.term,
              nonce: jckWssvVars?.index_nonce
            };
          }
        },
        multiple: true
      };
      $('.iconic-wssv-categories-to-apply-visibility-settings__categories').selectWoo(options);
    },
    /**
     * Watch AJAX triggers.
     */
    watch_triggers() {
      $('[data-iconic-wssv-ajax]').on('click', function () {
        const action = $(this).data('iconic-wssv-ajax');
        if (!ajax?.[action]) {
          return false;
        }
        ajax[action].run();
      });
      $('.process__close').on('click', function () {
        ajax.process.hide();
        if ($(this).data('reload')) {
          location.reload();
        }
      });
      $(document.body).on('click', '[data-iconic-wssv-process-screen]', function (e) {
        e.preventDefault();
        const type = $(this).data('iconic-wssv-process-screen');
        ajax.process.show(type);
        $(document.body).trigger('iconic_wssv_trigger_process_' + type);
      });
      $(document.body).on('iconic_wssv_trigger_process_start', function () {
        ajax.process.show('loading');
        ajax.process_product_visibility.start();
      });
      $('.iconic-wssv-categories-to-apply-visibility-settings__checkbox').on('click', function () {
        if ($(this).is(':checked')) {
          $(this).parents('.iconic-wssv-categories-to-apply-visibility-settings').find('.iconic-wssv-categories-to-apply-visibility-settings__categories-wrapper').show();
        } else {
          $(this).parents('.iconic-wssv-categories-to-apply-visibility-settings').find('.iconic-wssv-categories-to-apply-visibility-settings__categories-wrapper').hide();
        }
      });
    },
    /**
     * Process product visibility.
     */
    process_product_visibility: {
      run() {
        ajax.process.show('open');
      },
      /**
       * Start indexing products.
       */
      start() {
        const limit = 10,
          options = ajax.process_product_visibility.get_settings();
        ajax.process_product_visibility.clear_settings();
        ajax.get_count('product', function (count) {
          ajax.process.update_count(1, limit, count);
          ajax.process.show('processing');
          ajax.batch('process_product_visibility', count, limit, 0, function (processing, newOffset) {
            if (!processing) {
              ajax.process.show('complete');
              ajax.process.set_percentage(count, count);
            } else {
              let to = newOffset + limit;
              to = to >= count ? count : to;
              ajax.process.update_count(newOffset, to, count);
              ajax.process.set_percentage(newOffset, count);
            }
          }, options);
        });
      },
      /**
       * CLear settings.
       */
      clear_settings() {
        ajax.vars.index_settings = {};
      },
      /**
       * Get process settings.
       *
       * @return {Object} Index settings
       */
      get_settings() {
        if (!$.isEmptyObject(ajax.vars.index_settings)) {
          return ajax.vars.index_settings;
        }
        const $forms = $('.process__form'),
          fields = $forms.serializeArray();
        if (fields.length <= 0) {
          return ajax.vars.index_settings;
        }
        $.each(fields, function (index, fieldData) {
          if ('' === fieldData.value) {
            return;
          }
          const fieldType = typeof ajax.vars.index_settings[fieldData.name];
          if ('undefined' === fieldType) {
            ajax.vars.index_settings[fieldData.name] = fieldData.value;
          } else if ('object' === fieldType) {
            ajax.vars.index_settings[fieldData.name].push(fieldData.value);
          } else {
            const currentValue = ajax.vars.index_settings[fieldData.name];
            ajax.vars.index_settings[fieldData.name] = [];
            ajax.vars.index_settings[fieldData.name].push(currentValue);
            ajax.vars.index_settings[fieldData.name].push(fieldData.value);
          }
        });
        return ajax.vars.index_settings;
      }
    },
    /**
     * Process modal.
     */
    process: {
      /**
       * Show.
       *
       * @param {string} type
       */
      show(type) {
        type = typeof type === 'undefined' ? 'content' : type;
        ajax.els.process_overlay.show();
        ajax.els.process.show();
        $('.process__content').hide();
        $('.process__content--' + type).show();
        $(document.body).trigger('iconic_wssv_show_process_' + type);
      },
      /**
       * Hide.
       */
      hide() {
        ajax.els.process_overlay.hide();
        ajax.els.process.hide();
        ajax.els.process_loading.show();
        ajax.els.process_complete.hide();
        ajax.els.process_content.hide();
        ajax.process.reset_percentage();
        $(document.body).trigger('iconic_wssv_hide_process');
      },
      /**
       * Update count.
       *
       * @param {number} countFrom
       * @param {number} countTo
       * @param {number} countTotal
       */
      update_count(countFrom, countTo, countTotal) {
        ajax.els.process_from.text(countFrom);
        ajax.els.process_to.text(countTo);
        ajax.els.process_total.text(countTotal);
      },
      /**
       * Set percentage.
       *
       * @param {number} complete
       * @param {number} total
       */
      set_percentage(complete, total) {
        const percentage = complete / total * 100;
        ajax.els.process_loading_bar_fill.css('width', percentage + '%');
      },
      /**
       * Reset percentage.
       */
      reset_percentage() {
        ajax.els.process_loading_bar_fill.css('width', '0%');
      }
    },
    /**
     * Batch process.
     *
     * @param {string}   action
     * @param {number}   total
     * @param {number}   limit
     * @param {number}   offset
     * @param {Function} callback
     * @param {Object}   options
     */
    batch(action, total, limit, offset, callback, options) {
      options = options || {};
      let processing = true,
        data = {
          action: 'iconic_wssv_' + action,
          iconic_wssv_limit: limit,
          iconic_wssv_offset: offset,
          nonce: jckWssvVars?.index_nonce,
          ...jckWssvVars?.process_product_visibility_request_data
        };
      $.extend(data, options);
      $.post(ajaxurl, data, function () {
        const newOffset = offset + limit;
        if (newOffset < total) {
          ajax.batch(action, total, limit, newOffset, callback, options);
        } else {
          processing = false;
        }
        if (typeof callback === 'function') {
          callback(processing, newOffset);
        }
      });
    },
    /**
     * Get count of products.
     *
     * @param {string}   type
     * @param {Function} callback
     */
    get_count(type, callback) {
      if (ajax?.vars?.count?.[type]) {
        if (typeof callback === 'function') {
          callback(ajax.vars.count[type]);
        }
        return;
      }
      const data = {
        action: 'iconic_wssv_get_' + type + '_count',
        iconic_wssv_categories_to_apply_visibility_settings_to_variations: $('.iconic-wssv-categories-to-apply-visibility-settings--variations .iconic-wssv-categories-to-apply-visibility-settings__categories').val(),
        iconic_wssv_categories_to_apply_visibility_settings_to_variables: $('.iconic-wssv-categories-to-apply-visibility-settings--variables .iconic-wssv-categories-to-apply-visibility-settings__categories').val(),
        nonce: jckWssvVars?.index_nonce,
        ...jckWssvVars?.get_product_count_request_data
      };
      jQuery.post(ajaxurl, data, function (response) {
        if (typeof callback === 'function') {
          callback(response.count);
        }
        ajax.vars.count[type] = response.count;
      });
    }
  };
  $(document).ready(ajax.on_ready());
})(jQuery, document);
(function ($) {
  const metaBoxesProductVariation = {
    init() {
      $('#variable_product_options').on('change', 'input.jck_wssv_variable_manage_product_cat', this.variation_manage_categories).on('change', 'input.jck_wssv_variable_manage_product_tag', this.variation_manage_tags);
      $('#woocommerce-product-data').on('woocommerce_variations_loaded', this.variations_loaded);
    },
    /**
     * Check if variation manage categories and show/hide elements
     */
    variation_manage_categories() {
      if ($(this).is(':checked')) {
        $(this).closest('.woocommerce_variation').find('.iconic-wssv-variation-product_cat').show();
      } else {
        $(this).closest('.woocommerce_variation').find('.iconic-wssv-variation-product_cat').hide();
      }
    },
    /**
     * Check if variation manage tags and show/hide elements
     */
    variation_manage_tags() {
      if ($(this).is(':checked')) {
        $(this).closest('.woocommerce_variation').find('.iconic-wssv-variation-product_tag').show();
      } else {
        $(this).closest('.woocommerce_variation').find('.iconic-wssv-variation-product_tag').hide();
      }
    },
    /**
     * Run actions when variations is loaded
     *
     * @param {Object} event
     * @param {number} needsUpdate
     */
    variations_loaded(event, needsUpdate) {
      needsUpdate = needsUpdate || false;
      const wrapper = $('#woocommerce-product-data');
      if (!needsUpdate) {
        $('input.jck_wssv_variable_manage_product_cat, input.jck_wssv_variable_manage_product_tag', wrapper).trigger('change');
      }
      $('.iconic-wssv-variation-product_cat select').selectWoo(metaBoxesProductVariation.prepare_select_woo_options('categories', 'product_cat'));
      $('.iconic-wssv-variation-product_tag select').selectWoo(metaBoxesProductVariation.prepare_select_woo_options('tags', 'product_tag'));
    },
    /**
     * Return the data structure expected by selectWoo/Select2
     *
     * @see https://select2.org/configuration/options-api
     * @param {string} optionName
     * @param {string} taxonomy
     * @return {Object} The selectWoo options
     */
    prepare_select_woo_options(optionName, taxonomy) {
      function prepareQuery(params, taxonomy) {
        const query = {
          action: 'iconic_wssv_product_taxonomy_search',
          taxonomy,
          term: params.term
        };
        return query;
      }
      let options = {};
      if (!optionName) {
        return options;
      }
      if (!jckWssvVars || !jckWssvVars.select_woo_options) {
        return options;
      }
      let filteredOptions = jckWssvVars.select_woo_options[optionName];
      if (filteredOptions) {
        let ajaxOptions = {
          data(params) {
            return prepareQuery(params, taxonomy);
          }
        };
        ajaxOptions = Object.assign(ajaxOptions, filteredOptions.ajax);
        filteredOptions = Object.assign({}, filteredOptions, {
          ajax: ajaxOptions
        });
        options = filteredOptions;
      }
      return options;
    }
  };
  metaBoxesProductVariation.init();
})(jQuery);