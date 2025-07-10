(function ($, document) {
  const jckWssv = {
    cache() {
      jckWssv.els = {};
      jckWssv.vars = {};

      // common vars
      jckWssv.vars.add_to_cart_class = '.jck_wssv_add_to_cart';

      // common elements
      jckWssv.els.document = $(document);
      jckWssv.els.add_to_cart = $(jckWssv.vars.add_to_cart_class);
    },
    on_ready() {
      // on ready stuff here
      jckWssv.cache();
      jckWssv.setup_add_to_cart();
    },
    /**
     * =============================
     *
     * Setup add to cart button for variations
     *
      =============================
     */

    setup_add_to_cart() {
      jckWssv.els.document.on('click', jckWssv.vars.add_to_cart_class, function () {
        const $thisbutton = $(this);
        if (!$thisbutton.attr('data-variation_id')) {
          return true;
        }
        $thisbutton.removeClass('added');
        $thisbutton.addClass('loading');
        const data = {
          action: 'jck_wssv_add_to_cart'
        };
        $.each($thisbutton.data(), function (key, value) {
          data[key] = value;
        });

        // Trigger event
        $(document.body).trigger('adding_to_cart', [$thisbutton, data]);

        // Ajax action
        $.post(jckWssvVars.ajaxurl, data, function (response) {
          if (!response) {
            return;
          }
          let thisPage = window.location.toString();
          thisPage = thisPage.replace('add-to-cart', 'added-to-cart');
          if (response.error && response.product_url) {
            window.location = response.product_url;
            return;
          }

          // Redirect to cart option
          if (wc_add_to_cart_params.cart_redirect_after_add ===
          // eslint-disable-line camelcase
          'yes') {
            window.location = wc_add_to_cart_params.cart_url; // eslint-disable-line camelcase
          } else {
            $thisbutton.removeClass('loading');
            const fragments = response.fragments;
            const cartHash = response.cart_hash;

            // Block fragments class
            if (fragments) {
              $.each(fragments, function (key) {
                $(key).addClass('updating');
              });
            }

            // Block widgets and fragments
            $('.shop_table.cart, .updating, .cart_totals').fadeTo('400', '0.6').block({
              message: null,
              overlayCSS: {
                opacity: 0.6
              }
            });

            // Changes button classes
            $thisbutton.addClass('added');

            // View cart text
            if (!wc_add_to_cart_params.is_cart &&
            // eslint-disable-line camelcase
            $thisbutton.parent().find('.added_to_cart').length <= 0) {
              $thisbutton.after(' <a href="' + wc_add_to_cart_params.cart_url +
              // eslint-disable-line camelcase
              '" class="added_to_cart wc-forward" title="' + wc_add_to_cart_params.i18n_view_cart +
              // eslint-disable-line camelcase
              '">' + wc_add_to_cart_params.i18n_view_cart +
              // eslint-disable-line camelcase
              '</a>');
            }

            // Replace fragments
            if (fragments) {
              $.each(fragments, function (key, value) {
                $(key).replaceWith(value);
              });
            }

            // Unblock
            $('.widget_shopping_cart, .updating').stop(true).css('opacity', '1').unblock();

            // Cart page elements
            $('.shop_table.cart').load(thisPage + ' .shop_table.cart:eq(0) > *', function () {
              $('.shop_table.cart').stop(true).css('opacity', '1').unblock();
              $(document.body).trigger('cart_page_refreshed');
            });
            $('.cart_totals').load(thisPage + ' .cart_totals:eq(0) > *', function () {
              $('.cart_totals').stop(true).css('opacity', '1').unblock();
            });

            // Trigger event so themes can refresh other areas
            $(document.body).trigger('added_to_cart', [fragments, cartHash, $thisbutton]);
          }
        });
        return false;
      });
    }
  };
  $(document).ready(jckWssv.on_ready());
})(jQuery, document);