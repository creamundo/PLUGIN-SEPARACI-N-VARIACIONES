<?php $checkboxes = $this->get_variation_checkboxes( $variation, $loop ); ?>

<?php if ( ! empty( $checkboxes ) ) { ?>
	<?php foreach ( $checkboxes as $checkbox ) { ?>
		<label>
			<input type="checkbox" class="checkbox <?php echo $checkbox['class']; ?>" id="<?php echo $checkbox['id']; ?>" name="<?php echo $checkbox['name']; ?>" <?php checked( $checkbox['checked'], true ); ?> />
			<?php echo $checkbox['label']; ?>
			<?php if ( ! empty( $checkbox['desc'] ) ) { ?>
				<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $checkbox['desc'] ); ?>" style="font-size: 18px; margin-left: 4px;"></span>
			<?php } ?>
		</label>
	<?php } ?>
<?php } ?>
