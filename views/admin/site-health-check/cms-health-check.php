<?php if ( ! defined( 'ABSPATH' ) ) die(); ?>

<div class='health-check-body'>
	<h2>
		<?php
			esc_html_e( 'CMS Health Check', 'cms-health-check' ); ?>
	</h2>

	<div id="health-check-cms-health-check" role="presentation" class="health-check-accordion">
		<?php
			$tabs = apply_filters( 'cms-health-check/settings/sections', array() );

			foreach ( $tabs as $count => $tab ) :
				?>

				<h3 class="health-check-accordion-heading">
					<button
							aria-expanded="false"
							class="health-check-accordion-trigger"
							aria-controls="health-check-accordion-block-<?php
								echo esc_attr( $count ); ?>"
							type="button"
					>
			<span class="title">
				<?php
					echo $tab['label']; ?>
			</span>
						<span class="icon"></span>
					</button>
				</h3>
				<div
						id="health-check-accordion-block-<?php
							echo esc_attr( $count ); ?>" class="health-check-accordion-panel" hidden="hidden"
				>
					<?php
						if ( @file_exists( $tab['template'] ) ) {
							include( $tab['template'] );
						}
					?>
				</div>

			<?php
			endforeach; ?>
	</div>
</div>