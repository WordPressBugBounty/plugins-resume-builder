<div id="rb-welcome-screen" class="rbws-static">
	<div class="rbws-main">
		
		<div class="rbws-banner">
			<img src="<?php echo RBUILDER_URL; ?>/assets/admin/images/welcome-banner.png" />
		</div>
		
		<div class="rbws-content">
			<div class="rbws-content-left">
				<h2>Welcome to Resume Builder.</h2>
				<p>
					<strong>Thank you for installing!</strong> Whether you need just one resume
					or hundreds of them, you will be able to easily view and sort them using this plugin.
				</p>
				<hr />
				<h2>What's new?</h2><?php
				
				$readme = new Resume_Builder_Readme_Parser( RBUILDER_DIR . '/readme.txt' );
				$version = $readme->stable_tag;
				$changelog = $readme->changelog;
				
				?><div class="rb-changelog">
					<div class="rb-changelog-version"><?php echo __( 'Version', 'resume-builder' ) . ' ' . $version; ?></div><?php
					?><div class="rb-changelog-items"><?php
					foreach( $changelog[$version] as $item ){
						echo '<div class="rb-changelog-item">' . wp_kses_post( str_replace( ['NEW: ', 'FIX: ', 'TWEAK: '], ['<span class="rb-changelog-new">NEW:</span>','<span class="rb-changelog-fix">FIX:</span>','<span class="rb-changelog-tweak">TWEAK:</span>'], $item ) ) . '</div>';
					}
					?></div>
				</div>
			</div>
			<div class="rbws-content-right">
				<h2>Ready to get started?</h2>
				<div class="rbws-quick-buttons">
					<a class="rbe-button rbe-button-with-icon rb-button-primary" href="./admin.php?page=rbuilder_main">
						<div>Create a Resume</div>
						<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
							<path d="M507.3 267.3c6.2-6.2 6.2-16.4 0-22.6l-144-144c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L457.4 240 16 240c-8.8 0-16 7.2-16 16s7.2 16 16 16l441.4 0L340.7 388.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0l144-144z"/>
						</svg>
					</a>
					<!-- <a class="rbe-button rbe-button-with-icon button" href="#">
						<div>Resume Examples</div>
						<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
							<path d="M384 64c17.7 0 32 14.3 32 32V416c0 17.7-14.3 32-32 32H64c-17.7 0-32-14.3-32-32V96c0-17.7 14.3-32 32-32H384zm64 32c0-35.3-28.7-64-64-64H64C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96zM168 160c-8.8 0-16 7.2-16 16s7.2 16 16 16h97.4L132.7 324.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L288 214.6V320c0 8.8 7.2 16 16 16s16-7.2 16-16V176c0-8.8-7.2-16-16-16H168z"/>
						</svg>
					</a>
					<a class="rbe-button rbe-button-with-icon button" href="#">
						<div>Get Support</div>
						<svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
							<path d="M384 64c17.7 0 32 14.3 32 32V416c0 17.7-14.3 32-32 32H64c-17.7 0-32-14.3-32-32V96c0-17.7 14.3-32 32-32H384zm64 32c0-35.3-28.7-64-64-64H64C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V96zM168 160c-8.8 0-16 7.2-16 16s7.2 16 16 16h97.4L132.7 324.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L288 214.6V320c0 8.8 7.2 16 16 16s16-7.2 16-16V176c0-8.8-7.2-16-16-16H168z"/>
						</svg>
					</a> -->
				</div>
			</div>
		</div>
			
	</div>
</div>