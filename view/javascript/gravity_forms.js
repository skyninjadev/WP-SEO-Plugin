jQuery(function($){

    $('.nitropack-gravityforms-block').each(function(){

        $(this).load( nitropack_gf_ajax.ajax_url + '?action=nitropack_gf_block_output_ajax&block_name=' + $(this).attr('data-block-name') + '&block_attributes=' + $(this).attr('data-block-attributes'));
    });

    $('.nitropack-gravityforms-shortcode').each(function(){

        $(this).load( nitropack_gf_ajax.ajax_url + '?action=nitropack_gf_shortcode_output_ajax&shortcode-attributes=' + $(this).attr('data-shortcode-attributes'));
    });
});