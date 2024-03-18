<?php
	if ( CMS_Health_Options::get( 'security-token', false ) ) {
		$info = __( 'Invalidate Token and generate new one', 'cms-health-check' );
		$button_text = __( 'Invalidate Token', 'cms-health-check' );
	} else {
		$info = __( 'Token doesn\'t exists. Generate one', 'cms-health-check' );
		$button_text = __( 'Generate Token', 'cms-health-check' );
	}
?>

<p><?php echo $info; ?></p>

<form action='#regenerate-token' id='cms-health-check-security-token-form' class='cms-health-check-ajax-options' method='POST'>
	<?php wp_nonce_field( 'cms-health-check-regenerate-token' ); ?>
	<p>
		<input type='submit' class='button button-primary' value='<?php echo $button_text; ?>' />
	</p>
</form>

<div id='cms-health-check-security-token-message'>
	<p class='success' style='
		display: none;
		background: #fff;
		border: 1px solid #c3c4c7;
		border-left-width: 4px;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
		padding: 1em 1.5em;
		border-left-color: #135e96;
	'>
		<?php _e( 'New Token:', 'cms-health-check' ); ?>
		<input id='cms-health-check-security-token' style='width: 30ch; text-align: center;' />
	</p>
	<p class='failed' style='
		display: none;
		background: #fff;
		border: 1px solid #c3c4c7;
		border-left-width: 4px;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
		padding: 1em 1.5em;
		border-left-color: #d63638;
	'>
		<?php _e( 'Token generation failed', 'cms-health-check' ); ?>
	</p>
</div>