<form action='#save-enabled-checks' id='cms-health-enabled-checks-form' class='cms-health-ajax-options' method='POST'>
	<?php wp_nonce_field( 'cms-health-save-enable-checks' ); ?>
	<table class='form-table' role='presentation'>
		<tbody>
		<?php
			$checked = CMS_Health_Checks::get_all();

			$checks = CMS_Health_Checks::get_all( false );

			foreach ( $checks as $check_id => $check ) {
				?>
				<tr>
					<th scope='row'><label for='cms-health-enable-<?php echo $check_id; ?>'><?php echo $check['label'] ?? $check_id; ?></label></th>
					<td><input
						name='cms-health-enabled-checks[<?php echo $check_id; ?>]'
						type='checkbox'
						id='cms-health-enable-<?php echo $check_id; ?>'
						value='<?php echo $check_id; ?>'
						<?php echo ( array_key_exists( $check_id, $checked ) ? ' checked="checked"' : '' ); ?>
					></td>
				</tr>
				<?php
			}
		?>
		</tbody>
	</table>
	<p class='submit'><input
		type='submit'
		name='submit'
		id='submit'
		class='button button-primary'
		value='Änderungen speichern'
	></p>
</form>

<div id='cms-health-enable-checks-message'>
	<p class='success' style='
		display: none;
		background: #fff;
		border: 1px solid #c3c4c7;
		border-left-width: 4px;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
		padding: 1em 1.5em;
		border-left-color: #135e96;
	'>
		<?php _e( 'Settings updated.', 'cms-health' ); ?>
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
		<?php _e( 'Update failed', 'cms-health' ); ?>
	</p>
</div>