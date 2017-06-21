<div class="row">
	<div class="col-xs-12">
        <p class="payment_module">
            <a class="creditCard" href="#" title="Pagar con tarjeta de credito">
                Pagar con Tarjeta de credito<span></span>
            </a>
			{if $is_error == 1}		
			<p style="color: red;">			
				{if !empty($smarty.get.message)}
					{l s='Error detalle de cardinal :' mod='cardinalpayment'}
					{$smarty.get.message|htmlentities}
				{else}	
					{l s='Error, verifica tu tarjeta de credito' mod='cardinalpayment'}
				{/if}
			</p>
			{/if}
        </p>
    </div>

	<div class="col-xs-6">
		<div class="box">
			<div class="card-wrapper"></div>
		</div>
		<div class="clear"></div>
	</div>
	<div class="col-xs-6">
		<div class="box">
			<form id="payment" class="payment" method="post" action="{$api_url}" class="ui form">
				<input type="hidden" id="hash" name="hash" value="{$hash}" />
				<input type="hidden" id="time" name="time" value="{$time}" />  
				<input type="hidden" id="type" name="type" value="sale" />
				<input type="hidden" id="orderid" name="orderid" value="{$orderid}" />
				<input type="hidden" id="key_id" name="key_id" value="{$key_id}" />
				<input type="hidden" name="redirect" value="{$redirect}" />
				<input placeholder="Cantidad" type="hidden" id="amount" name="amount" value="{$amount}">
					<div class="form-group">
						<label for="ccnumber">Numero de tarjeta</label>
						<input class="form-control" placeholder="Numero de Tarjeta" type="tel" name="ccnumber" value="" >
					</div>
					<div class="form-group">
						<label for="firstname">Nombre completo</label>
						<input class="form-control" placeholder="Nombre Completo" type="text" name="firstname" value="">
					</div>
					<div class="form-group">
					    <label for="ccexp">Exp</label>
						<input class="form-control" placeholder="MM/YY" type="tel" name="ccexp" value="">
					</div>
					<div class="form-group">
					 	<label for="cvv">CVV</label>
						<input class="form-control" placeholder="CVV" type="number" name="cvv" value="">
					</div>
					<input type="submit" value="Enviar" class="ui primary button">
			</form>
		</div>
	</div>		
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/card/2.3.0/card.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.7.0/js/md5.min.js"></script>
	<script>
	 var s = jQuery.noConflict(true);
	 s(document).ready(function() {
			new Card({
				form: document.querySelector('form.payment'),
				container: '.card-wrapper',
				formSelectors: {
					numberInput: 'input[name="ccnumber"]',
					expiryInput: 'input[name="ccexp"]',
					cvcInput: 'input[name="cvv"]',
					nameInput: 'input[name="firstname"]'
				},
				placeholders: {
					number: '•••• •••• •••• ••••',
					name: 'Nombre Completo',
					expiry: '•• / ••',
					cvc: '•••'
				},
			});
			
			$('form.payment').submit(function(e) {
				e.preventDefault();

				var $form = $( this );
				var vccnumber = $form.find( "input[name='ccnumber']" ).val();
				var vccexp = $form.find( "input[name='ccexp']" ).val();

				$form.find( "input[name='ccnumber']" ).val(vccnumber.replace(/ /g , ""));
				$form.find( "input[name='ccexp']" ).val(vccexp.replace(/[\/| ]/g , ""));

				this.submit(); 
			});	
		});
    </script>
</div>