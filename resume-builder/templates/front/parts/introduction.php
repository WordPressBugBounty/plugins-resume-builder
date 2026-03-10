<?php

global $resume, $compact;

if ( isset( $resume['introduction']['content'] ) ):
	
	do_action( 'rb_resume_before_resume_header' );
	$rand_rbt = wp_rand( 100,999 );
	
	?><div id="<?php echo 'rb-template-' . esc_attr( $rand_rbt ); ?>" class="<?php echo 'rb-template-' . esc_attr( $resume['display']['template'] ); ?>">
		<?php echo '<div class="rbt-introduction" style="color:' . esc_attr( $resume['display']['text_color'] ) . ';">' . wp_kses_post( $resume['introduction']['content'] ) . '</div>'; ?>
	</div><?php
	
	do_action( 'rb_resume_after_resume_header' );
	
endif;