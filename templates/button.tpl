<div class="col-md-2">
	<label for="bitcoin" class="gateway">
		<input type="radio" name="payment_type" value="bitcoin" id="bitcoin">
		<div class="coinbase-button" data-code="{$bitcoin_button_id}"></div>
		<script src="https://coinbase.com/assets/button.js" type="text/javascript"></script>
	</label>
</div>
{ia_add_js}
$(function()
{
	var $form = $('#payment_form');
	$('input[name="payment_type"]', $form).on('change', function(e)
	{
		$('button[type="submit"]', $form).prop('disabled', ('bitcoin' == $(this).val()));
	});
});
{/ia_add_js}