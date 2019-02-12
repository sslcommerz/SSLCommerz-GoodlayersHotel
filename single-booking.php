<?php get_header(); ?>
<div class="gdlr-content">

	<?php 
		global $gdlr_sidebar;
		
		$gdlr_sidebar = array( 'type'=>'no-sidebar' ); 
		$gdlr_sidebar = gdlr_get_sidebar_class($gdlr_sidebar);
	?>
	<div class="with-sidebar-wrapper">
		<div class="with-sidebar-container container gdlr-class-<?php echo $gdlr_sidebar['type']; ?>">
			<div class="with-sidebar-left <?php echo $gdlr_sidebar['outer']; ?> columns">
				<div class="with-sidebar-content <?php echo $gdlr_sidebar['center']; ?> columns">
					<div class="gdlr-item gdlr-item-start-content" id="gdlr-single-booking-content" data-ajax="<?php
						echo AJAX_URL; ?>">
						
						<?php echo gdlr_get_reservation_bar(); ?>
						
						<div class="gdlr-booking-content">
							<?php 
								if( !empty($_GET['state']) && $_GET['state'] == 4 && !empty($_GET['invoice']) ){
									echo gdlr_booking_process_bar(4);
								}else if( !empty($_POST['hotel_data']) ){
									echo gdlr_booking_process_bar(2);
								}else{
									echo gdlr_booking_process_bar(1);
								}
							?>
							
							<div class="gdlr-booking-content-wrapper" >
								<div class="gdlr-booking-content-inner" id="gdlr-booking-content-inner" >
									<?php
										if( !empty($_GET['state']) && $_GET['state'] == 4 && !empty($_GET['invoice']) ){
											echo gdlr_booking_complete_message();
										}else if( !empty($_POST['hotel_data']) ){
											echo gdlr_get_booking_room_query($_POST, 0);
										}else{
											echo gdlr_booking_date_range(); 
										}
									?>
								</div>
							</div>
							<div class="clear"></div>	
						</div>
						<div class="clear"></div>	
					</div>
				</div>
				<?php get_sidebar('left'); ?>
				<div class="clear"></div>
			</div>
			<?php get_sidebar('right'); ?>
			<div class="clear"></div>
		</div>				
	</div>				

</div><!-- gdlr-content -->
<?php get_footer(); ?>