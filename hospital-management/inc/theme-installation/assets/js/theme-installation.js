jQuery(document).ready(function ($) {

    /* If there are required actions, add an icon with the number of required actions in the About tm-theme-installation page -> Actions recommended tab */
    var tm_theme_info_count_box_actions_recommended = tm_theme_info_box_object.count_actions_recommended;

    if ( (typeof tm_theme_info_count_box_actions_recommended !== 'undefined') && (tm_theme_info_count_box_actions_recommended != '0') ) {
        jQuery('li.tm-theme-installation-w-red-tab a').append('<span class="tm-theme-installation-actions-count">' + tm_theme_info_count_box_actions_recommended + '</span>');
    }

    /* Dismiss required actions */
    jQuery(".tm-theme-installation-recommend-action-button,.reset-all").click(function() {

        var id = jQuery(this).attr('id'),
            action = jQuery(this).attr('data-action');

        jQuery.ajax({
            type      : "GET",
            data      : {
                action: 'tm_theme_info_update_recommend_action',
                id: id,
                todo: action,
                _ajax_nonce: tm_theme_info_box_object.recommend_action_nonce
            },
            dataType  : "html",
            url       : tm_theme_info_box_object.ajaxurl,
            beforeSend: function (data, settings) {
                jQuery('.tm-theme-installation-tab-pane#actions_required h1').append('<div id="temp_load" style="text-align:center"><img src="' + tm_theme_info_box_object.template_directory + '../loader.gif" /></div>');
            },
            success   : function (data) {
                location.reload();
                jQuery("#temp_load").remove();
                /* Remove loading gif */
            },
            error     : function (jqXHR, textStatus, errorThrown) {
                // Handle error silently
            }
        });
    });

});

jQuery(document).ready(function($) {
    var page = 1;
    var isLoading = false;

    $(window).scroll(function () {
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200 && !isLoading) {            
            loadMoreProducts();
        }
    });

    function productsAjax( endCursor, templateSearch, collection, actionValue ) {

        var progress = 0;
        var progressInterval = setInterval(function() {
            progress += 10;
            if (progress >= 100) {
                clearInterval(progressInterval);
            }
        }, 300);

        $.ajax({
            url: tm_theme_info_box_object.ajaxurl,
            type: 'POST',
            data: {
                action: 'free_mnssp_get_filtered_products',
                cursor: endCursor,
                search: templateSearch,
                collection: collection,
                mnssp_pagination_nonce: tm_theme_info_box_object.nonce
            },
            success: function (response) {

                clearInterval(progressInterval);
                jQuery('.mnssp-loader').hide();
                jQuery('.mnssp-loader-overlay').hide();

                if (response.content) {

                    isLoading = false;

                    if ( actionValue != 'load' ) {
                        jQuery('.mnssp-templates-grid.mnssp-main-grid').empty();
                    }
                    jQuery('.mnssp-templates-grid.mnssp-main-grid').append(response.content);

                    const hasNextPage = response?.pagination?.hasNextPage;
                    const endCursor = response?.pagination?.endCursor;

                    jQuery('[name="mnssp-end-cursor"]').val(endCursor);
                    if (!hasNextPage) {
                        jQuery('[name="mnssp-end-cursor"]').val('');
                        isLoading = true
                    }
                }
            },
            error: function () {
                
                clearInterval(progressInterval);
                jQuery('.mnssp-loader').hide();
                jQuery('.mnssp-loader-overlay').hide();

                // Error handled silently
            }
        });
    }

    function loadMoreProducts() {
        isLoading = true;
        page++;

        const endCursor = jQuery('[name="mnssp-end-cursor"]').val();
        const templateSearch = jQuery('[name="mnssp-templates-search"]').val();
        const collection = jQuery('[name="mnssp-collections"]').val();

        productsAjax( endCursor, templateSearch, collection, 'load' );
    }

    function debounce(func, delay) {
        let timeoutId;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(context, args);
            }, delay);
        };
    }

    jQuery('#mnssp-collections').on('change', function() {

        jQuery('.mnssp-loader').show();
        jQuery('.mnssp-loader-overlay').show();

        productsAjax( '', '', jQuery(this).val(), 'category' );
    });

    $('body').on("input", '[name="mnssp-templates-search"]', debounce(function (event) {

        const templateSearch = $('[name="mnssp-templates-search"]').val();

        jQuery('.mnssp-loader').show();
        jQuery('.mnssp-loader-overlay').show();
        
        productsAjax( '', templateSearch, '', 'search' );
        
    }, 1000));
});