**v1.25.0** (14 May 2025)  
[new] Compatibility with Meta Box plugin  
[new] Compatibility with Product Filters for WooCommerce  
[new] Compatibility with Goya theme  
[new] Compatibility with Astra theme  
[new] Admin settings field: `Bypass variation saving for these AJAX events`  
[fix] Product variations' categories and tags getting empty when WooCommerce handles the start and end of scheduled sales  
[fix] WooCommerce Filter by stock status when a product variation is out-of-stock  
[fix] Product variation visibility when updating via WooCommerce REST API  
[fix] WooCommerce Categories report not including variation-level categories  
[fix] Categories and tags added at the variation level being removed when duplicating a variable product  
[fix] WooCommerce Filter by Rating to show product variations in the filtered result  
[fix] Variation title when duplicating a variable product via the WPML Duplicate feature  
[fix] Importing products via WP All Import when FacetWP is activated  

**v1.24.1** (28 Apr 2025)  
[update] Updated dependencies and added more safety checks to telemetry opt-ins/opt-outs  

**v1.24.0** (19 Feb 2025)  
[update] Licensing.  

**v1.23.0** (19 Nov 2024)  
[new] Developer filter: `iconic_wssv_actions_to_skip_on_variation_save`
[fix] Categories and tags been removed when bulk updating variations  
[fix] Compatibility with WPML when WooCommerce Multilingual & Multicurrency is not activated  

**v1.22.0** (25 Jun 2024)  
[fix] Facet WP attribute filter when the product variable (parent) is visible and variations are hidden  
[fix] WooCommerce Multilingual & Multicurrency variation synchronization data when duplicating a product  

**v1.21.0** (11 Jun 2024)  
[new] Compatibility with UpSolution Core  
[new] Developer filters: `iconic_wssv_private_to_unpublished_variable_post_statuses` and `iconic_wssv_should_add_private_to_unpublished_variable_post_statuses`
[fix] Filter out variations when the variable (parent) product is private  

**v1.20.0** (15 May 2024)  
[fix] WoodMart theme AJAX search not including product variations  

**v1.19.0** (30 Apr 2024)  
[new] WooCommerce Product Block editor compatibility  
[new] Compatibility with Ajax Search Lite  
[fix] Prevent WooThumbs' bulk edit actions from deleting SSV taxonomy meta  

**v1.18.0** (28 Mar 2024)  
[fix] FacetWP counter compatibility issue  

**v1.17.0** (20 Mar 2024)  
[new] Compatibility with Query Loop block  
[fix] Running the indexer for a specific category  

**v1.16.3** (11 Dec 2023)  
[update] Iconic dependencies  

**v1.16.2** (11 Dec 2023)  
[update] Iconic dependencies  

**v1.16.1** (16 Nov 2023)  
[fix] Indexer not indexing all products  
[fix] WPML compatibility  

**v1.16.0** (24 Oct 2023)  
[new] Partial indexing  
[new] Compatibility with ShopLentor  

**v1.15.2** (02 Oct 2023)  
[fix] Product visibility settings after processing checkout  
[fix] Updating product variation via WooCommerce REST API  

**v1.15.1** (31 Aug 2023)  
[fix] Fix license validation issues and type errors  

**v1.15.0** (24 Aug 2023)  
[new] New licensing system  
[new] Compatibility with YITH WooCommerce Badges Management plugin  

**v1.14.2** (27 Jul 2023)  
[fix] Product variation tags being deleted after cancelling an order  

**v1.14.1** (21 Jul 2023)  
[update] Updated Iconic dependencies.  

**v1.14.0** (06 Jul 2023)  
[new] Compatibility with High-Performance Order Storage (HPOS)  

**v1.13.1** (05 Jul 2023)  
[update] Update dependencies

**v1.13.0** (07 Jun 2023)  
[new] Filter `iconic_wssv_product_taxonomy_search_results`.  
[update] Show immediate parent category when selecting categories for a variation  
[fix] Compatibility with Add To Cart widget in Elementor  

**v1.12.0** (02 May 2023)  
[new] Compatibility with Perfect Brands for WooCommerce plugin  
[new] Compatibility with Product Badges by 99w  
[fix] Sort by popularity and rating  

**v1.11.2** (26 Apr 2023)  
[fix] FacetWP attribute list when the product variations are marked to show only on filtered results  
[fix] WooCommerce Wholesale Prices Premium compatibility issue on the category pages  
[fix] Missing `_listings_only` custom field in `wpml-config.xml`  
[fix] Woo would match "listings only" variations as purchasable products  

**v1.11.1** (2 Jan 2023)  
[fix] Filter by Stock Status count in WooCommerce AJAX Products Filter - BeRocket  

**v1.11.0** (23 Nov 2022)  
[new] Selecting categories and tags at variation level  
[fix] Category description in the product archive page for Kadence theme  

**v1.10.0** (27 Sep 2022)  
[new] Compatibility with SUMO Subscriptions plugin  
[fix] Manual selection of products in Elementor  
[fix] Ensure that `exclude-from-filtered` is checked when visibility is hidden in the indexing  

**v1.9.0** (27 Jul 2022)  
[new] Filters to customize the title of the product variations: `iconic_wssv_variation_attributes_used_in_the_title` and `iconic_wssv_variation_title_with_attributes`  
[fix] Variations field in the products endpoint on the WC REST API  

**v1.8.0** (14 Jul 2022)  
[new] Compatibility with Metro theme  
[fix] Attribute archive page to products with "Show in Filtered Results?" option disabled  

**v1.7.0** (25 May 2022)  
[fix] Compatibility issues with FacetWP  

**v1.6.1** (18 Apr 2022)  
[fix] Default sorting when the parent product has a high number of variations  

**v1.6.0** (5 Apr 2022)  
[new] Compatibility with SearchWP plugin   
[fix] Compatibility with YITH WooCommerce Ajax Product Filter when the option 'Hide out of stock products' is enabled  

**v1.5.0** (1 Mar 2022)  
[fix] Missing CSS classes  
[fix] Order by most recent products  
[fix] Prevent PHP warning on creating variation  
[fix] Security fix  

**v1.4.0** (7 Feb 2022)  
[new] Compatibility with WooCommerce Wholesale Prices plugin  
[fix] Issue where bulk setting won't apply to certain variations  
[fix] Issue where "Exclude from filtered results" would be marked unchecked for variable products on indexing  
[fix] Sorting issue with product variations  
[fix] FacetWP search  

**v1.3.0** (21 Dec 2021)  
[update] Improve performance on single product page by reducing SQL queries needed to fetch listing only setting  
[update] Compatibility with Divi BodyCommerce  
[update] Fix issue with Yith AJAX filters plugin where filters would go missing on archive page if all products are variations  
[fix] Fix issue where wrong template was shown on search result page when Relevanssi plugin is active  
[fix] Fix default parameter warning with PHP 8  
[fix] Fix issue where "listing only" setting wont work for variable products having large number of variations  
[fix] Variation visibility checkbox broken when using WoodMart Theme  
[fix] FacetWP compatibility: fix issue where incorrect product count would appear on category archive page  
[fix] Fix fatal error on running import using WP All Import  

**v1.2.1** (22 Sep 2021)  
[fix] Fix bug where Visibility checkboxes can't be unchecked  

**v1.2.0** (3 Sep 2021)  
[new] Getting started tab to help with onboarding  
[new] New setting to add variations to all Product queries including custom WP_Query instances  
[new] Bulk indexing tool  
[new] Compatibility with Yith recently viewed products  
[update] Update dependencies  
[update] Improved compatibility with Woodmart Quickview, Wishlist, and 'New' Badge  
[fix] Fix issue with Berocket AJAX filter plugin which prevents catalog excluded products to not appear in filtered results  
[fix] Fix issue which cleared WP Rocket cache for the whole site while saving variations  
[fix] Fixes issue where `$jck_wssv` is undefined in WP-CLI command  


**v1.1.22** (17 Jun 2021)  
[update] Compatibility with DHWC Ajax filter plugin  
[update] Maintain compatibility with FacetWP version 3.8  
[fix] Fix fatal error while processing orphan variation product  
[fix] URL encode attribute values in variation URL  
[fix] Performance improvement: prevent variation save process from happening during checkout (when adjusting stock)  

**v1.1.21** (16 Mar 2021)  
[new] Copy variation visiblity when product is duplicated  
[new] Compatibility with Elementor builder  
[new] Compatiblity with Woodmart theme  
[new] Flatsome live search compatibility  
[new] Better variation titles - setting to inherit from parent, append attributes, or override per-variation  
[update] Flatsome theme: add specific classes to add to cart button  
[update] Improve FacetWP compatibility  
[update] Process Product visiblity in linear manner to prevent timeouts  
[update] Update dependencies  
[fix] Filter widget missing issue  
[fix] Layered nav filters missing/incorrect count issue  
[fix] Variations visiblity not saving bug  
[fix] Load textdomain on init so plugins (like LocoTranslate) register it  

**v1.1.20** (13 Aug 2020)  
[update] Compatiblity with Relevanssi Search plugin  
[update] Compatibility with WordPress 5.5  
[update] Update dependencies  
[fix] Fix incorrect term count issue  
[fix] Fix filter result issue with "any" attributes  
[fix] Fix fatal error on product duplication  

**v1.1.19** (21 Apr 2020)  
[update] Update dependencies  
[update] Check if product is variable when saving  
[update] Add Flatsome live search compatibility  
[update] Add Flatsome sale flash bubble compatibility  
[update] Save variation rating  
[fix] Variation title incorrect on product save  
[fix] Don't remove "exclude from filtered" when processing visibility  
[fix] Remove deprecated function `update_woocommerce_term_meta()`  
[fix] Fatal error if displaying a [products] shortcode on a filtered page  
[fix] JavaScript: replace .size() with .length  
[fix] Fatal error when terms argument in the tax query is a string rather than array  
[fix] Remove "non variation" attributes from variations when removed from parent  

**v1.1.18** (18 Mar 2020)  
[update] Version compatibility  

**v1.1.17** (19 Nov 2019)  
[update] Version compatiblity  

**v1.1.16** (1 July 2019)  
[fix] Freemius Fix  

**v1.1.15** (2 Mar 2019)  
[fix] Security Fix  

**v1.1.14** (6 Dec 2018)  
[new] Added WooCommerce Ajax Filters by BeRocket compatibility  
[update] Ensured compatibility with WP 5.0  
[update] Ensured compatibility with Woo 3.5.0  
[update] Update deps  
[fix] Product visibility doesn't save when using a translated 'save' button on the product-edit page  
[fix] Improve compatibility with themes that rely on the type-product class on the loop  
[fix] Send all the parameters when triggering the switch_theme hook to avoid issues with other plugins/themes listening to this action  
[fix] Fatal error when themes change the $product global to a string  
[fix] Issue where checkboxes weren't saving  
[fix] Avada compatibility doesn't load on normal pages using the products shortcode  

**v1.1.13** (10 Sep 2018)  
[new] Atelier compatibility  
[new] Avada compatibility  
[new] Leto compatibility  
[change] Use product methods when adding loop classes  
[change] Refresh variation titles on product save  
[update] Implement Iconic core classes  
[update] Update dependencies  
[fix] Ensure taxonomies are assigned when processing visibility  
[fix] WP All Export visibility formatting (using official visibility field)  
[fix] WP All Import compatibility update (v4.5.5)  
[fix] Fix typo in query class for `include_children`  
[fix] Make sure filtered variation settings are maintained when updating via the API  
[fix] Ensure term counts are correct  
[fix] Ensure variations are only saved once  
[fix] Clear term count transient when saving attributes to variation  

**v1.1.12** (11 Jun 2018)  
[update] Update Freemius  
[update] Update POT  
[update] Don't load settings framework on frontend  
[fix] Issue when filtering visibility for non products  
[fix] Fix sorting issue on some category pages  
[fix] Fix issue when saving "Exclude from filtered results"  
[fix] Ensure products hidden from filtered don't show in FacetWP  
[fix] Make sure attributes "not for variations" work with variations when filtered via FacetWP  
[fix] Make sure filtered works for fwp_ and _ prefixes when using FacetWP  

**v1.1.11** (11 Apr 2018)  
[update] Add WP All Export compatibility  
[update] Ability to add products to show only in the product listings. Not purchasable on the product page.  
[update] FacetWP compatibility  
[update] Update freemius  
[update] Add POT file  
[fix] Featured/add to cart settings issue when stock is managed and variation is updated/purchased  
[fix] Ajax add to cart button when product is not purchasable  
[fix] Cart item links for hidden variations  
[fix] Flatsome lightbox button issue  
[fix] non-numeric value bug in term counts  
[fix] Hide variations if parent is not published  
[fix] Assume variations in cart are visible  
[fix] Don't show disabled variations in listings  
[fix] Issue preventing WP CLI from running  
[fix] Show variations in filtered when not shown in catalog  
[fix] Variation order when using a shortcode  
[fix] Fix links to docs

**v1.1.10** (7 Feb 2018)  
[update] Update Freemius  
[update] Update settings framework  
[update] Allow variation taxonomies to be filtered with iconic_wssv_variation_taxonomies  
[update] Allow add to cart button text to be filtered with iconic_wssv_add_to_cart_button_text  
[update] Add variations to related products module  
[update] Allow basic tags in variation title  
[update] Add Flatsome compatibility  
[update] Add option to exclude parent from filtered results  
[fix] Set transient for each term count instead of combined  
[fix] Fix menu order of variations when parent is set to menu_order 0  
[fix] Fix when saving "unset" checkboxes  
[fix] Term counts incorrect when not showing out of stock items  
[fix] Variation term counts auto reset when Woo resets product term counts  
[fix] Fix filtered visibility when bulk editing  
[fix] Prevent API update removing "featured" setting  
[fix] Only run menu order methods on frontend  
[fix] Don't escape button text  
[fix] Allow br in variation title

**v1.1.9** (9 Sep 2017)  
[update] Variations inherit parent star ratings  
[update] Variations inherit parent menu_order  
[update] Add is_visible filter to bypass parent settings  
[fix] Fix issue with bulk toggling visibility settings  
[fix] Disabled add to cart button not working  
[fix] Make sure hidden variations are not shown  
[fix] Issue where variation visibility was reset on bulk actions  
[fix] Potential issue where is_ajax() is undefined  
[fix] Prevent variations appearing in catalog after purchase  
[fix] Variation titles being overwritten

**v1.1.8** (7 Aug 2017)  
[fix] Visibility issues with WP All Import script for new variations  
[fix] wp_mail definition issue  
[fix] Remove Envato update script

**v1.1.7** (1 Jun 2017)  
[update] New licensing system

**v1.1.6** (24 May 2017)  
[update] WP All Import script for new WooCommerce  
[update] Move total sales update to batch process action in settings page  
[fix] Set new "outofstock" visibility  
[fix] Hide draft products from the catalog  
[fix] Only add button classes to variations  
[fix] Issue on with product visibility in Woo 3.*  
[fix] Set new "featured" visibility  
[fix] Only use AJAX add to cart if option is enabled  
[fix] Issue when new variations were added and no visibility settings were saved  
[fix] Error when sorting by popularity  
[fix] Total sales updated when new order placed

**v1.1.5** (6 may 2017)  
[fix] Modify loop class so it assigns correctly  
[fix] Fix database prefix for one method  
[fix] Make sure variations added via "Create all variations" are updated  
[fix] Add new taxonomy terms for visibility  
[fix] Fix filter and category counts  
[fix] Toggle "Show in..." to account for new visibility terms

**v1.1.4** (2 April 2017)  
[update] WooCommerce 3.0.0 compatibility  
[fix] Term count when not int issue

**v1.1.3** (22 Dec 2016)  
[update] Add filter to catalog add to cart button  
[update] Remove dashboard  
[fix] Allow WP All Import functions to run when not in admin  
[fix] Issue where wp_query was too resource intensive for getting term counts  
[fix] Update term counts when running a bulk toggle

**v1.1.2** (19 Sep 2016)  
[fix] Remove menu order filter, for now. Need to reassess and add back in.  
[fix] Don't get variations with missing parents  
[update] Add Iconic dashboard  
[fix] Don't get broken variations in shortcode  
[fix] Add filter to price filter widget to include variations (pending WooCommerce update)

**v1.1.1** (24 Aug 2016)  
[fix] Make sure the URL is always using a variation  
[fix] Issue where all variations were showing when translating a product via WPML  
[fix] Bulk actions reset the filter counts  
[fix] Issue when adding attributes not used for variations  
[fix] Disable function that updates product order temporarily  
[fix] Prevent infinite loop on wp_update_post  
[fix] Issue where no ID is present for title  
[update] Adding ability to import data to variations when using WP All Import

**v1.1.0** (14 Jun 2016)  
[update] Compatibility with WooCommerce 2.6.0 - filter counts will work again once WooCommerce release the next patch
[update] Change method for checking if parent variable product is published

**v1.0.13** (9 May 2016)  
[fix] Add padding to variation menu order to fix when there's more than 10 variations  
[fix] Load plugin textdomain

**v1.0.12** (4 May 2016)  
[update] Official WPML compatibility

**v1.0.11** (18 Apr 2016)  
[fix] Fix manual product ordering  
[fix] Variations were always being enabled one product save  
[fix] Fix product status transition to account for draft products

**v1.0.10** (10 Feb 2016)  
[update] Option to disable "Add to Cart" button per variation  
[update] Add "non-variation" attributes to variations on product save  
[fix] Change layered nav query to use filter  
[update] Add add_to_cart class on button

**v1.0.9** (3 Feb 2016)  
[fix] Update category counts on frontend to include variations  
[fix] Update attribute counts on frontend to include variations

**v1.0.8** (20 Jan 2016)  
[update] Compatibility with Atelier theme

**v1.0.7** (15 Jan 2016)  
[fix] Variations are now added to filter counts on save  
[update] Bulk option to update filter counts  
[update] Variations are now added to tags  
[fix] Variations are added when a category is added to an existing product

**v1.0.6** (30 Dec 2015)  
[update] You can now set individual variations as "Featured"

**v1.0.5** (30 Dec 2015)  
[fix] Index 'key' not set  
[update] Include display title in search  
[update] Bulk action - Update "total_sales" for popularity ordering  
[fix] Variations displayed in popularity order

**v1.0.4** (17 Dec 2015)  
[update] Added compatibility for shortcodes  
[fix] Variations removed if parent is deleted

**v1.0.3** (18 Nov 2015)  
[fix] Change is_purchasable method  
[fix] Change permalink method to cover more ground  
[update] Bulk actions for toggling visibility  
[fix] When variations filtered, they were being limited to 12

**v1.0.2** (8 Nov 2015)  
[fix] Price filter only fix

**v1.0.1** (6 Nov 2015)  
[fix] Change "add to cart" to "select options" if not all attributes for variation have been selected

**v1.0.0** (2 Nov 2015)  
Initial Release
