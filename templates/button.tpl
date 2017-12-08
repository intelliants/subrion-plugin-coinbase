<div class="gw-list__item">
    <label for="coinbase" class="gateway">
        <input type="radio" name="payment_type" value="coinbase" id="coinbase">
        <div class="coinbase-button" data-code="{$coinbase_button_id}"></div>
        <script src="https://coinbase.com/assets/button.js" type="text/javascript"></script>
    </label>
</div>

{ia_add_js}
$(function() {
    var $form = $('#payment_form');
    $('input[name="payment_type"]', $form).on('change', function(e) {
        $('button[type="submit"]', $form).prop('disabled', ('coinbase' == $(this).val()));
    });
});
{/ia_add_js}